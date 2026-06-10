<?php
include 'audit_helper.php';
$allowedRoles = ['system_admin', 'management', 'project_manager', 'engineer', 'ceo'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    exit;
}
/* ===============================
HELPERS
=============================== */
function statusByMonth($monthPct, $monthNo, $currentMonth)
{
    if ($monthPct > 0) return 'tl';
    if ($monthNo < $currentMonth) return 'catch';
    if ($monthNo >= $currentMonth) return 'award';
    return 'standby';
}
function recalcActivity($conn, $activity_id)
{
    $activity_id = (int)$activity_id;
    $a = $conn->query("
        SELECT target_qty
        FROM activities
        WHERE id=$activity_id
        LIMIT 1
    ")->fetch_assoc();
    if (!$a) return;
    $target = (float)($a['target_qty'] ?? 100);
    if ($target <= 0) $target = 100;
    $map = [
        1 => 'jan',
        2 => 'feb',
        3 => 'mar',
        4 => 'apr',
        5 => 'may',
        6 => 'jun',
        7 => 'jul',
        8 => 'aug',
        9 => 'sep',
        10 => 'oct',
        11 => 'nov',
        12 => 'decm'
    ];
    $sets = [];
    foreach ($map as $m => $key) {
        $r = $conn->query("
            SELECT IFNULL(SUM(cover_sqm),0) t
            FROM work_updates
            WHERE activity_id=$activity_id
            AND MONTH(update_date)=$m
        ")->fetch_assoc();
        $done = (float)$r['t'];
        $pct  = ($done / $target) * 100;
        if ($pct > 100) $pct = 100;
        $sets[] = $key . "_pct='" . round($pct, 2) . "'";
        $sets[] = $key . "='" . statusByMonth($pct, $m, (int)date('n')) . "'";
    }
    $r2 = $conn->query("
        SELECT IFNULL(SUM(cover_sqm),0) t
        FROM work_updates
        WHERE activity_id=$activity_id
    ")->fetch_assoc();
    $overall = ((float)$r2['t'] / $target) * 100;
    if ($overall > 100) $overall = 100;
    $sets[] = "progress='" . round($overall, 2) . "'";
    $conn->query("
        UPDATE activities
        SET " . implode(',', $sets) . "
        WHERE id=$activity_id
    ");
}
function ensureFolder($path)
{
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}
function compressAndSaveImage($tmp, $dest, $ext)
{
    $info = getimagesize($tmp);
    if (!$info) return false;
    $width  = $info[0];
    $height = $info[1];
    $maxW = 1600;
    if ($width > $maxW) {
        $newW = $maxW;
        $newH = intval(($height / $width) * $newW);
    } else {
        $newW = $width;
        $newH = $height;
    }
    $src = null;
    if ($ext == 'jpg' || $ext == 'jpeg') {
        $src = imagecreatefromjpeg($tmp);
    } elseif ($ext == 'png') {
        $src = imagecreatefrompng($tmp);
    } elseif ($ext == 'webp') {
        $src = imagecreatefromwebp($tmp);
    } else {
        return move_uploaded_file($tmp, $dest);
    }
    $dst = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
    imagejpeg($dst, $dest, 72);
    imagedestroy($src);
    imagedestroy($dst);
    return true;
}
function saveUploads($conn, $work_update_id)
{
    if (empty($_FILES['attachments']['name'][0])) return;
    $folder = 'uploads/work_updates/';
    ensureFolder($folder);
    foreach ($_FILES['attachments']['name'] as $i => $name) {
        if ($_FILES['attachments']['error'][$i] != 0) continue;
        $tmp  = $_FILES['attachments']['tmp_name'][$i];
        $size = (int)$_FILES['attachments']['size'][$i];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = time() . '_' . rand(1000, 9999) . '_' . $i;
        $type = 'file';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $type = 'image';
            $newName = $base . '.jpg';
            $path = $folder . $newName;
            compressAndSaveImage($tmp, $path, $ext);
        } elseif ($ext == 'pdf') {
            $type = 'pdf';
            $newName = $base . '.pdf';
            $path = $folder . $newName;
            move_uploaded_file($tmp, $path);
        } elseif (in_array($ext, ['mp4', 'mov', 'webm'])) {
            $type = 'video';
            $newName = $base . '.' . $ext;
            $path = $folder . $newName;
            move_uploaded_file($tmp, $path);
        } else {
            continue;
        }
        $stmt = $conn->prepare("
            INSERT INTO work_update_files(
                work_update_id,file_name,file_path,file_type,file_size
            )
            VALUES(?,?,?,?,?)
        ");
        $stmt->bind_param(
            "isssi",
            $work_update_id,
            $name,
            $path,
            $type,
            $size
        );
        $stmt->execute();
    }
}
/* ===============================
SAVE
=============================== */
if (isset($_POST['save'])) {
    $update_date      = $_POST['update_date'];
    $project_id       = (int)$_POST['project_id'];
    $activity_id      = (int)$_POST['activity_id'];
    $zone             = trim($_POST['zone']);
    $cover_sqm        = (float)$_POST['cover_sqm'];
    $account_name     = trim($_POST['account_name']);
    $planned_manpower = (int)$_POST['planned_manpower'];
    $actual_manpower  = (int)$_POST['actual_manpower'];
    $work_done        = trim($_POST['work_done']);
    $issues           = trim($_POST['issues']);
    $qaqc_result      = trim($_POST['qaqc_result']);
    $created_by       = (int)($_SESSION['user_id'] ?? 0);
    $meta = $conn->query("
        SELECT description
        FROM activities
        WHERE id=$activity_id
        LIMIT 1
    ")->fetch_assoc();
    $work_category = $meta['description'] ?? '';
    $stmt = $conn->prepare("
        INSERT INTO work_updates(
            update_date,project_id,zone,work_category,
            activity_id,cover_sqm,account_name,
            planned_manpower,actual_manpower,
            work_accomplished,issues_encountered,
            qaqc_result,created_by
        )
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "sissidsiisssi",
        $update_date,
        $project_id,
        $zone,
        $work_category,
        $activity_id,
        $cover_sqm,
        $account_name,
        $planned_manpower,
        $actual_manpower,
        $work_done,
        $issues,
        $qaqc_result,
        $created_by
    );
    $stmt->execute();
    $work_update_id = $conn->insert_id;
    saveUploads($conn, $work_update_id);
    recalcActivity($conn, $activity_id);
    header("Location: ./?link=work_update.php&added=1");
    exit;
}
/* ===============================
DATA
=============================== */
/* ===============================
SEARCH + PAGINATION
=============================== */
$search = trim($_GET['search'] ?? '');

$perPage = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$start = ($page - 1) * $perPage;

$where = '';

if ($search != '') {
    $searchEsc = $conn->real_escape_string($search);

    $where = "
    WHERE
        p.project_name LIKE '%$searchEsc%'
        OR a.item_no LIKE '%$searchEsc%'
        OR a.description LIKE '%$searchEsc%'
        OR w.account_name LIKE '%$searchEsc%'
        OR w.issues_encountered LIKE '%$searchEsc%'
        OR w.qaqc_result LIKE '%$searchEsc%'
    ";
}

$countQuery = $conn->query("
SELECT COUNT(*) total
FROM work_updates w
LEFT JOIN projects p ON w.project_id=p.id
LEFT JOIN activities a ON w.activity_id=a.id
$where
");

$totalRows = (int)$countQuery->fetch_assoc()['total'];
$totalPages = max(1, ceil($totalRows / $perPage));

$projects = $conn->query("SELECT id,project_name FROM projects ORDER BY project_name");
$activities = $conn->query("
SELECT a.id,a.project_id,a.item_no,a.description,a.target_qty,
       wi.unit,wa.activity_name,wc.category_name
FROM activities a
LEFT JOIN work_items wi ON a.item_id=wi.id
LEFT JOIN work_activities wa ON wi.activity_id=wa.id
LEFT JOIN work_categories wc ON wa.category_id=wc.id
ORDER BY a.item_no
");
$activityData = [];
while ($x = $activities->fetch_assoc()) $activityData[] = $x;
$search = trim($_GET['search'] ?? '');

$where = '';

if ($search != '') {
    $search = $conn->real_escape_string($search);

    $where = "
    WHERE
        p.project_name LIKE '%$search%'
        OR a.item_no LIKE '%$search%'
        OR a.description LIKE '%$search%'
        OR w.account_name LIKE '%$search%'
        OR w.issues_encountered LIKE '%$search%'
        OR w.qaqc_result LIKE '%$search%'
    ";
}
$list = $conn->query("
SELECT w.*,p.project_name,a.item_no,a.description
FROM work_updates w
LEFT JOIN projects p ON w.project_id=p.id
LEFT JOIN activities a ON w.activity_id=a.id
$where
ORDER BY w.id DESC
LIMIT $start,$perPage
");
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="text-white fw-bold mb-1">Work Log</h2>
        <small class="text-secondary">3-Level Synced Daily Progress Logging</small>
    </div>
    <form method="GET" class="d-flex gap-2">
        <input
            type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            class="form-control"
            placeholder="Search project, activity, PIC...">

        <input type="hidden" name="link" value="work_update.php">

        <button class="btn btn-primary">
            Search
        </button>
    </form>
</div>
<?php if (!$isManagement): ?>
    <div class="card p-4 mb-4 rounded-4 shadow-sm">
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-3">
                <label>Date</label>
                <input type="date" name="update_date" class="form-control"
                    value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label>Project</label>
                <select id="projectSelect" name="project_id"
                    class="form-select select2" onchange="loadActivities()" required>
                    <option value="">Select</option>
                    <?php while ($p = $projects->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>"><?= $p['project_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label>Activity</label>
                <select name="activity_id" id="activitySelect"
                    class="form-select select2" onchange="showPreview()" required>
                    <option value="">Select Project</option>
                </select>
            </div>
            <div class="col-md-12">
                <label>Preview</label>
                <input type="text" id="previewBox"
                    class="form-control" readonly>
            </div>
            <div class="col-md-3">
                <label>Qty</label>
                <input type="number" step="0.01"
                    name="cover_sqm" class="form-control" value="0">
            </div>
            <div class="col-md-3">
                <label>Person In Charge</label>
                <input type="text" name="account_name" class="form-control">
            </div>
            <div class="col-md-3">
                <label>Planned Man Power</label>
                <input type="number" name="planned_manpower"
                    class="form-control" value="0">
            </div>
            <div class="col-md-3">
                <label>Actual Man Power</label>
                <input type="number" name="actual_manpower"
                    class="form-control" value="0">
            </div>
            <div class="col-md-6">
                <label>Work Done</label>
                <textarea name="work_done" class="form-control"></textarea>
            </div>
            <div class="col-md-6">
                <label>Issues</label>
                <textarea name="issues" class="form-control"></textarea>
            </div>
            <div class="col-md-6">
                <label>QA/QC</label>
                <textarea name="qaqc_result" class="form-control"></textarea>
            </div>
            <div class="col-md-6">
                <label>Upload Images / PDF / Video</label>
                <input type="file"
                    name="attachments[]"
                    multiple
                    class="form-control"
                    accept="image/*,.pdf,video/*">
                <small class="text-secondary">
                    Multiple files allowed. Images auto compressed.
                </small>
            </div>
            <div class="col-md-12">
                <button name="save" class="btn btn-gold">
                    Save Work Update
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>
<div class="card p-3 rounded-4 shadow-sm">

    <div class="position-relative">
        <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
            onclick="toggleFullscreen(this)">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
        <h4 class="fw-bold mb-1">
            Work Inspection and Validation
        </h4>
        <small class="text-secondary">
            Real-time monitoring of work progress, quality and manpower deployment
        </small>
    </div>
    <div class="table-responsive w-100">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th style="min-width: 200px;">Project</th>
                    <th>Activity</th>
                    <th>Qty</th>
                    <th style="min-width: 200px;">Person In Charge</th>
                    <th>Files</th>
                    <th style="min-width: 200px;">Issues / Cause</th>
                    <th>QA/QC</th>
                    <th style="min-width: 200px;">Planned Man Power</th>
                    <th style="min-width: 200px;">Actual Man Power</th>
                    <th>Status</th>
                    <th style="min-width: 200px;">Corrective Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $list->fetch_assoc()): ?>
                    <?php
                    $files = $conn->query("
                        SELECT *
                        FROM work_update_files
                        WHERE work_update_id=" . $r['id'] . "
                    ");
                    $planned = (int)$r['planned_manpower'];
                    $actual  = (int)$r['actual_manpower'];
                    if ($actual >= $planned) {
                        $status = 'Healthy';
                        $badge  = 'success';
                        $action = 'Maintain deployment';
                    } elseif ($actual >= ($planned * .7)) {
                        $status = 'Watch';
                        $badge  = 'warning';
                        $action = 'Add manpower';
                    } else {
                        $status = 'Critical';
                        $badge  = 'danger';
                        $action = 'Immediate recovery plan';
                    }
                    ?>
                    <tr>
                        <td class="text-nowrap">
                            <?= date('d-M-y', strtotime($r['update_date'])) ?>
                        </td>
                        <td class="fw-semibold">
                            <?= $r['project_name'] ?>
                        </td>
                        <td style="min-width:260px;">
                            <div class="fw-semibold">
                                <?= $r['item_no'] ?>
                            </div>
                            <small class="text-secondary">
                                <?= $r['description'] ?>
                            </small>
                        </td>
                        <td class="fw-semibold">
                            <?= number_format($r['cover_sqm'], 2) ?>
                        </td>
                        <td>
                            <?= $r['account_name'] ?>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <?php while ($f = $files->fetch_assoc()): ?>
                                    <?php if ($f['file_type'] == 'image'): ?>
                                        <a href="<?= $f['file_path'] ?>"
                                            target="_blank">
                                            <img src="<?= $f['file_path'] ?>"
                                                style="
                                                    width:50px;
                                                    height:50px;
                                                    object-fit:cover;
                                                    border-radius:8px;
                                                    border:1px solid #dee2e6;
                                                ">
                                        </a>
                                    <?php elseif ($f['file_type'] == 'pdf'): ?>
                                        <a href="<?= $f['file_path'] ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-danger">
                                            PDF
                                        </a>
                                    <?php elseif ($f['file_type'] == 'video'): ?>
                                        <a href="<?= $f['file_path'] ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-dark">
                                            VIDEO
                                        </a>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            </div>
                        </td>
                        <td style="min-width:220px;">
                            <?php if (!empty($r['issues_encountered'])): ?>
                                <span class="text-danger fw-semibold">
                                    <?= $r['issues_encountered'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-secondary">
                                    No reported issue
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="min-width:220px;">
                            <?php if (!empty($r['qaqc_result'])): ?>
                                <?= $r['qaqc_result'] ?>
                            <?php else: ?>
                                <span class="text-secondary">
                                    Pending QA/QC
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?= $planned ?>
                        </td>
                        <td class="text-center">
                            <?= $actual ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $status ?>
                            </span>
                        </td>
                        <td style="min-width:220px;">
                            <?= $action ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-center mt-3">
    <nav>
        <ul class="pagination">

            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link"
                        href="./?link=work_update.php&page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                        Previous
                    </a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link"
                        href="./?link=work_update.php&page=<?= $i ?>&search=<?= urlencode($search) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link"
                        href="./?link=work_update.php&page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                        Next
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </nav>
</div>
<!-- MANPOWER & PRODUCTIVITY TEMPLATE -->
<div class="card p-4 mt-4 shadow-sm">

    <div class="position-relative">

        <div>
            <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
                onclick="toggleFullscreen(this)">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <h4 class="fw-bold mb-1">
                Manpower & Productivity
            </h4>
            <small class="text-secondary">
                Required manpower, productivity index and workforce monitoring
            </small>

        </div>

    </div>
    <div class="table-responsive w-100">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Contractor</th>
                    <th>Package</th>
                    <th>Tower</th>
                    <th>Required Manpower</th>
                    <th>Actual Manpower</th>
                    <th>Shortage</th>
                    <th>Productivity %</th>
                    <th>Absenteeism %</th>
                    <th>Overtime Dependency %</th>
                    <th>Critical Trade</th>
                    <th>Action Required</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $manpower = $conn->query("
                    SELECT
                        w.*,
                        p.project_name,
                        a.description
                    FROM work_updates w
                    LEFT JOIN projects p
                        ON w.project_id = p.id
                    LEFT JOIN activities a
                        ON w.activity_id = a.id
                    ORDER BY w.id DESC
                ");
                while ($r = $manpower->fetch_assoc()):
                    $required =
                        (int)$r['planned_manpower'];
                    $actual =
                        (int)$r['actual_manpower'];
                    $shortage =
                        $required - $actual;
                    /* PRODUCTIVITY */
                    $productivity =
                        $required > 0
                        ? ($actual / $required) * 100
                        : 0;
                    if ($productivity > 100) {
                        $productivity = 100;
                    }
                    /* ABSENTEEISM */
                    $absent =
                        $required > 0
                        ? (($required - $actual) / $required) * 100
                        : 0;
                    if ($absent < 0) {
                        $absent = 0;
                    }
                    /* OT DEPENDENCY */
                    if ($actual < $required) {
                        $overtime =
                            min(100, $absent * 1.8);
                    } else {
                        $overtime = 10;
                    }
                    /* TRADE */
                    $trade = $r['description'];
                    /* ACTION */
                    if ($shortage <= 0) {
                        $action =
                            'Maintain workforce level';
                        $badge =
                            'success';
                    } elseif ($shortage <= 5) {
                        $action =
                            'Deploy additional manpower';
                        $badge =
                            'warning';
                    } else {
                        $action =
                            'Immediate workforce recovery';
                        $badge =
                            'danger';
                    }
                ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= $r['account_name'] ?>
                        </td>
                        <td>
                            <?= $r['work_category'] ?>
                        </td>
                        <td>
                            <?= $r['project_name'] ?>
                        </td>
                        <td class="text-center">
                            <?= $required ?>
                        </td>
                        <td class="text-center">
                            <?= $actual ?>
                        </td>
                        <td class="<?= $shortage > 0 ? 'text-danger' : 'text-success' ?> fw-semibold">
                            <?= $shortage ?>
                        </td>
                        <td>
                            <?= number_format($productivity, 0) ?>%
                        </td>
                        <td>
                            <?= number_format($absent, 0) ?>%
                        </td>
                        <td>
                            <?= number_format($overtime, 0) ?>%
                        </td>
                        <td>
                            <?= $trade ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $action ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    const acts = <?= json_encode($activityData) ?>;

    function loadActivities() {
        let pid = document.getElementById('projectSelect').value;
        let box = document.getElementById('activitySelect');
        box.innerHTML = '<option value="">Select Activity</option>';
        acts.forEach(a => {
            if (a.project_id == pid) {
                box.innerHTML += `
            <option value="${a.id}">
            ${a.item_no} - ${a.description}
            </option>`;
            }
        });
        // Reinitialize select2 for dynamic content
        $('#activitySelect').select2();
    }

    function showPreview() {
        let id = document.getElementById('activitySelect').value;
        let a = acts.find(x => x.id == id);
        if (a) {
            document.getElementById('previewBox').value =
                a.category_name + ' / ' +
                a.activity_name + ' / ' +
                a.item_no + ' - ' + a.description +
                ' / Target: ' + a.target_qty + ' ' + a.unit;
        }
    }

    function filterUpdates(value) {
        value = value.toLowerCase();

        document.querySelectorAll("table tbody tr").forEach(row => {
            let text = row.innerText.toLowerCase();

            if (text.indexOf(value) > -1) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }
</script>