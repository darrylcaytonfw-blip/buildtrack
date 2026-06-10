<?php
ob_start();
include 'database.php';
include 'audit_helper.php';
/* =========================================================
   ACCESS
========================================================= */
$allowedRoles = ['system_admin', 'management', 'project_manager', 'engineer', 'contractor_staff', 'supplier'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    exit("Access Denied");
}

/* =========================================================
   HELPERS
========================================================= */
function statusByMonth($pct)
{
    if ($pct <= 0) return 'Not Started';
    if ($pct >= 100) return 'Completed';
    return 'Ongoing';
}

function ensureFolder($path)
{
    if (!is_dir($path)) mkdir($path, 0777, true);
}

function saveUploads($conn, $update_id)
{
    if (empty($_FILES['attachments']['name'][0])) return;

    $folder = "uploads/work_logs/";
    ensureFolder($folder);

    foreach ($_FILES['attachments']['tmp_name'] as $k => $tmp) {

        if (!$tmp) continue;

        $name = time() . '_' . $k . '_' . basename($_FILES['attachments']['name'][$k]);
        $path = $folder . $name;

        move_uploaded_file($tmp, $path);

        $stmt = $conn->prepare("
            INSERT INTO work_update_files(work_update_id,file_path)
            VALUES(?,?)
        ");
        $stmt->bind_param("is", $update_id, $path);
        $stmt->execute();
    }
}

function recalcActivity($conn, $activity_id)
{

    $sum = $conn->query("
        SELECT IFNULL(SUM(cover_sqm),0) total_done
        FROM work_updates
        WHERE activity_id=$activity_id
    ")->fetch_assoc();

    $done = (float)$sum['total_done'];

    $tar = $conn->query("
        SELECT target_qty
        FROM activities
        WHERE id=$activity_id
        LIMIT 1
    ")->fetch_assoc();

    $target = (float)($tar['target_qty'] ?? 0);

    $pct = $target > 0 ? ($done / $target) * 100 : 0;
    if ($pct > 100) $pct = 100;

    $status = statusByMonth($pct);

$stmt = $conn->prepare("
    UPDATE activities
    SET progress=?
    WHERE id=?
");

$stmt->bind_param(
    "di",
    $pct,
    $activity_id
);

$stmt->execute();
}

/* =========================================================
   SESSION
========================================================= */
$user_id  = (int)($_SESSION['user_id'] ?? 0);
$username = $_SESSION['user'] ?? '';
$role     = $_SESSION['role'] ?? '';
$contractor_id = 0;

/* =========================================================
   DETECT CONTRACTOR STAFF
========================================================= */
if ($role == 'contractor_staff') {

    $stmt = $conn->prepare("
        SELECT contractor_id
        FROM contractor_staff
        WHERE user_id=?
        LIMIT 1
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        $stmt2 = $conn->prepare("
            SELECT contractor_id
            FROM contractor_staff
            WHERE system_id_no=?
            LIMIT 1
        ");
        $stmt2->bind_param("s", $username);
        $stmt2->execute();
        $res = $stmt2->get_result();
    }

    if ($res->num_rows) {
        $row = $res->fetch_assoc();
        $contractor_id = (int)$row['contractor_id'];
    } else {
        exit("No contractor mapping found.");
    }
}

/* =========================================================
   SAVE
========================================================= */
if (isset($_POST['save'])) {

    $activity_id = (int)$_POST['activity_id'];
    $update_date = $_POST['update_date'];
    $zone        = trim($_POST['zone']);
    $cover_sqm   = (float)$_POST['cover_sqm'];
    $work_done   = trim($_POST['work_done']);
    $issues      = trim($_POST['issues']);
    $created_by  = $user_id;

    if ($role == 'contractor_staff') {

        $chk = $conn->query("
            SELECT id
            FROM activities
            WHERE id=$activity_id
            AND contractor_id=$contractor_id
            LIMIT 1
        ");

        if (!$chk->num_rows) {
            exit("Unauthorized activity.");
        }
    }

    $meta = $conn->query("
        SELECT project_id, description
        FROM activities
        WHERE id=$activity_id
        LIMIT 1
    ")->fetch_assoc();

    $project_id    = (int)$meta['project_id'];
    $work_category = $meta['description'];

    $account_name     = '';
    $planned_manpower = 0;
    $actual_manpower  = 0;
    $qaqc_result      = '';

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

    $update_id = $conn->insert_id;

    saveUploads($conn, $update_id);
    recalcActivity($conn, $activity_id);

    header("Location: ./?link=contractor_worklog.php&success=1");
    exit;
}

/* =========================================================
   DELETE
========================================================= */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    $row = $conn->query("
        SELECT wu.activity_id, a.contractor_id
        FROM work_updates wu
        LEFT JOIN activities a ON wu.activity_id=a.id
        WHERE wu.id=$id
        LIMIT 1
    ")->fetch_assoc();

    if ($role == 'contractor_staff' && (int)$row['contractor_id'] !== $contractor_id) {
        exit("Unauthorized delete.");
    }

    $conn->query("DELETE FROM work_update_files WHERE update_id=$id");
    $conn->query("DELETE FROM work_updates WHERE id=$id");

    recalcActivity($conn, (int)$row['activity_id']);

    header("Location: ./?link=contractor_worklog.php&deleted=1");
    exit;
}

/* =========================================================
   FILTERS
========================================================= */
$where = "";
$whereLogs = "";

if ($role == 'contractor_staff') {
    $where     = "WHERE a.contractor_id=$contractor_id";
    $whereLogs = "WHERE a.contractor_id=$contractor_id";
}

/* =========================================================
   ACTIVITIES
========================================================= */
$activities = $conn->query("
    SELECT
        a.id,
        a.project_id,
        a.item_no,
        a.description,
        a.target_qty,
        p.project_name
    FROM activities a
    LEFT JOIN projects p ON a.project_id=p.id
    $where
    ORDER BY p.project_name, a.item_no
");

$activityList = [];
while ($r = $activities->fetch_assoc()) {
    $activityList[] = $r;
}

/* =========================================================
   LOGS
========================================================= */
$logs = $conn->query("
    SELECT
        wu.*,
        p.project_name,
        a.item_no,
        a.description
    FROM work_updates wu
    LEFT JOIN projects p ON wu.project_id=p.id
    LEFT JOIN activities a ON wu.activity_id=a.id
    $whereLogs
    ORDER BY wu.id DESC
");
?>

<style>
    #zoneBox label {
        cursor: pointer;
        width: 100%;
    }

    .dropdown-menu {
        z-index: 9999;
    }
</style>

<div class="container-fluid py-4">

    <h2 class="text-white fw-bold mb-4">Contractor Work Log</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Saved successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-danger">Deleted successfully.</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card p-4 mb-4 rounded-4 border-0 shadow">
        <div class="row g-3">

            <div class="col-md-4">
                <label>Date</label>
                <input type="date"
                    name="update_date"
                    value="<?= date('Y-m-d') ?>"
                    class="form-control"
                    required>
            </div>

            <div class="col-md-8">
                <label>Activity</label>
                <select name="activity_id"
                    id="activitySelect"
                    class="form-select no-select2"
                    required>
                    <option value="">Select Activity</option>

                    <?php foreach ($activityList as $a): ?>
                        <option value="<?= $a['id'] ?>"
                            data-project="<?= $a['project_id'] ?>">
                            <?= $a['project_name'] ?> /
                            <?= $a['description'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 position-relative">
                <label>Zone / Floor</label>

                <div class="dropdown w-100">
                    <button class="btn btn-light w-100 dropdown-toggle text-start overflow-hiddent"
                        type="button"
                        id="zoneBtn"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="outside">
                        Select Floor
                    </button>

                    <div class="dropdown-menu w-100 shadow p-2"
                        id="zoneBox"
                        style="max-height:320px;overflow:auto;">
                        <small class="text-muted">Select activity first</small>
                    </div>
                </div>

                <input type="hidden" name="zone" id="zone_value" required>
            </div>

            <div class="col-md-4">
                <label>Quantity / Area (sqm)</label>
                <input type="number"
                    step="0.01"
                    name="cover_sqm"
                    class="form-control"
                    required>
            </div>

            <div class="col-md-4">
                <label>Issues Encountered</label>
                <input type="text"
                    name="issues"
                    class="form-control">
            </div>

            <div class="col-md-12">
                <label>Work Accomplished</label>
                <textarea name="work_done"
                    rows="2"
                    class="form-control"></textarea>
            </div>

            <div class="col-md-8">
                <label>Attachments</label>
                <input type="file"
                    name="attachments[]"
                    multiple
                    class="form-control">
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button name="save"
                    class="btn btn-gold w-100 fw-bold">
                    Save Work Log
                </button>
            </div>

        </div>
    </form>

    <div class="card p-3 rounded-4 border-0 shadow">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Project</th>
                        <th>Activity</th>
                        <th>Zone</th>
                        <th>Qty</th>
                        <th>Files</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>

                    <?php while ($row = $logs->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['update_date'] ?></td>
                            <td><?= $row['project_name'] ?></td>
                            <td><?= $row['item_no'] ?> - <?= $row['description'] ?></td>
                            <td><?= $row['zone'] ?></td>
                            <td><?= number_format($row['cover_sqm'], 0) ?></td>
                            <td>
                                <?php
                                $files = $conn->query("
                                        SELECT *
                                        FROM work_update_files
                                        WHERE work_update_id = {$row['id']}
                                    ");
                                ?>

                                <?php if ($files->num_rows): ?>

                                    <?php while ($f = $files->fetch_assoc()): ?>

                                        <a href="<?= htmlspecialchars($f['file_path']) ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-info mb-1">
                                            <i class="bi bi-paperclip"></i>
                                            File
                                        </a><br>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <span class="text-muted">No Files</span>

                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="./?link=contractor_worklog.php&delete=<?= $row['id'] ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this record?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
    (function() {

        const activity = document.getElementById('activitySelect');
        const zoneBox = document.getElementById('zoneBox');
        const zoneVal = document.getElementById('zone_value');
        const zoneBtn = document.getElementById('zoneBtn');

        if (!activity || !zoneBox || !zoneVal || !zoneBtn) return;

        function resetBox(msg = 'Select activity first') {
            zoneBox.innerHTML = '<small class="text-muted">' + msg + '</small>';
            zoneVal.value = '';
            zoneBtn.innerText = 'Select Floor';
        }

        function updateValues() {
            let vals = [];

            document.querySelectorAll('.zf:checked').forEach(cb => {
                vals.push(cb.value);
            });

            zoneVal.value = vals.join(', ');
            zoneBtn.innerText = vals.length ? vals.join(', ') : 'Select Floor';
        }

        activity.addEventListener('change', function() {

            const selected = this.options[this.selectedIndex];
            const projectId = selected.getAttribute('data-project');

            if (!projectId) {
                resetBox();
                return;
            }

            zoneBox.innerHTML = '<small class="text-muted">Loading...</small>';

            fetch('ajax_project_floors.php?project_id=' + encodeURIComponent(projectId))
                .then(res => {
                    if (!res.ok) throw new Error('HTTP Error');
                    return res.json();
                })
                .then(data => {

                    console.log('Floor Response:', data);

                    const floors = parseInt(data.floors || 0);
                    const ph = parseInt(data.ph || 0);

                    let html = `
                <label class="dropdown-item fw-bold border-bottom mb-1">
                    <input type="checkbox" id="allFloors" class="me-2">
                    All Floors
                </label>
            `;

                    for (let i = 1; i <= floors; i++) {
                        if (i === 13) continue;

                        html += `
                    <label class="dropdown-item">
                        <input type="checkbox" class="zf me-2" value="Floor ${i}">
                        Floor ${i}
                    </label>
                `;
                    }

                    for (let x = 1; x <= ph; x++) {
                        html += `
                    <label class="dropdown-item">
                        <input type="checkbox" class="zf me-2" value="Penthouse ${x}">
                        Penthouse ${x}
                    </label>
                `;
                    }

                    if (floors === 0 && ph === 0) {
                        html += `<small class="text-danger px-2">No floors configured</small>`;
                    }

                    zoneBox.innerHTML = html;
                    zoneVal.value = '';
                    zoneBtn.innerText = 'Select Floor';
                })
                .catch(err => {
                    console.error(err);
                    resetBox('Failed to load floors');
                });

        });

        document.addEventListener('change', function(e) {

            if (e.target.id === 'allFloors') {
                const checked = e.target.checked;

                document.querySelectorAll('.zf').forEach(cb => {
                    cb.checked = checked;
                });

                updateValues();
            }

            if (e.target.classList.contains('zf')) {
                updateValues();
            }

        });

    })();
</script>