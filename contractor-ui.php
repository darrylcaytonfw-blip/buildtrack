<?php
if (!isset($conn)) {
    include 'database.php';
}

$allowedRoles = ['system_admin', 'management', 'project_manager', 'engineer', 'supplier', 'contractor_staff'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    exit;
}

function contractorUiH($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function contractorUiScalar($conn, $sql, $types = '', $params = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? (float)array_values($row)[0] : 0;
}

function contractorUiQuery($conn, $sql, $types = '', $params = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result();
}

function contractorUiEnsureFolder($path)
{
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
}

function contractorUiSaveFiles($conn, $workUpdateId)
{
    if (empty($_FILES['attachments']['name'][0])) {
        return;
    }

    $folder = 'uploads/work_updates/';
    contractorUiEnsureFolder($folder);
    $allowed = ['jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'webp' => 'image', 'pdf' => 'pdf', 'mp4' => 'video', 'mov' => 'video', 'webm' => 'video'];

    foreach ($_FILES['attachments']['name'] as $i => $name) {
        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) {
            continue;
        }

        $safeName = time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', basename($name));
        $path = $folder . $safeName;

        if (!move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $path)) {
            continue;
        }

        $type = $allowed[$ext];
        $size = (int)$_FILES['attachments']['size'][$i];
        $stmt = $conn->prepare("
            INSERT INTO work_update_files(work_update_id, file_name, file_path, file_type, file_size)
            VALUES(?,?,?,?,?)
        ");
        $stmt->bind_param("isssi", $workUpdateId, $name, $path, $type, $size);
        $stmt->execute();
    }
}

function contractorUiRecalcActivity($conn, $activityId)
{
    $activityId = (int)$activityId;
    $activity = $conn->query("SELECT target_qty FROM activities WHERE id=$activityId LIMIT 1")->fetch_assoc();
    if (!$activity) {
        return;
    }

    $target = max((float)$activity['target_qty'], 1);
    $doneRow = $conn->query("SELECT IFNULL(SUM(cover_sqm),0) total_done FROM work_updates WHERE activity_id=$activityId")->fetch_assoc();
    $progress = min((((float)$doneRow['total_done'] / $target) * 100), 100);

    $stmt = $conn->prepare("UPDATE activities SET progress=? WHERE id=?");
    $stmt->bind_param("di", $progress, $activityId);
    $stmt->execute();
}

$role = $_SESSION['role'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$username = $_SESSION['user'] ?? '';
$contractorId = 0;
$contractorFilterSql = '';
$contractorFilterTypes = '';
$contractorFilterParams = [];

if ($role === 'contractor_staff') {
    $stmt = $conn->prepare("
        SELECT contractor_id
        FROM contractor_staff
        WHERE user_id=? OR system_id_no=?
        LIMIT 1
    ");
    $stmt->bind_param("is", $userId, $username);
    $stmt->execute();
    $staff = $stmt->get_result()->fetch_assoc();

    if (!$staff) {
        echo "<div class='alert alert-warning'>No contractor profile is linked to this account yet.</div>";
        return;
    }

    $contractorId = (int)$staff['contractor_id'];
    $contractorFilterSql = ' AND a.contractor_id=?';
    $contractorFilterTypes = 'i';
    $contractorFilterParams = [$contractorId];
}

if (isset($_POST['save_work_update'])) {
    $activityId = (int)$_POST['activity_id'];

    if ($contractorId > 0) {
        $authorized = contractorUiScalar($conn, "SELECT COUNT(*) FROM activities WHERE id=? AND contractor_id=?", "ii", [$activityId, $contractorId]);
        if ($authorized <= 0) {
            echo "<div class='alert alert-danger'>This activity is not assigned to your contractor account.</div>";
            return;
        }
    }

    $activity = contractorUiQuery($conn, "SELECT project_id, description FROM activities WHERE id=? LIMIT 1", "i", [$activityId])->fetch_assoc();
    if ($activity) {
        $updateDate = $_POST['update_date'];
        $projectId = (int)$activity['project_id'];
        $zone = trim($_POST['zone']);
        $qty = max((float)$_POST['cover_sqm'], 0);
        $plannedManpower = max((int)$_POST['planned_manpower'], 0);
        $actualManpower = max((int)$_POST['actual_manpower'], 0);
        $workDone = trim($_POST['work_accomplished']);
        $issues = trim($_POST['issues_encountered']);
        $qaqc = 'Pending engineer review';
        $workCategory = $activity['description'];
        $accountName = $_SESSION['user'] ?? '';

        $stmt = $conn->prepare("
            INSERT INTO work_updates(
                update_date, project_id, zone, work_category, activity_id, cover_sqm,
                account_name, planned_manpower, actual_manpower, work_accomplished,
                issues_encountered, qaqc_result, created_by
            )
            VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "sissidsiisssi",
            $updateDate,
            $projectId,
            $zone,
            $workCategory,
            $activityId,
            $qty,
            $accountName,
            $plannedManpower,
            $actualManpower,
            $workDone,
            $issues,
            $qaqc,
            $userId
        );
        $stmt->execute();
        contractorUiSaveFiles($conn, $conn->insert_id);
        contractorUiRecalcActivity($conn, $activityId);

        header("Location: ./?link=contractor-ui.php&saved=1");
        exit;
    }
}

if (isset($_POST['report_issue'])) {
    $activityId = (int)$_POST['issue_activity_id'];
    $activity = contractorUiQuery($conn, "
        SELECT project_id, contractor_id
        FROM activities
        WHERE id=? {$contractorFilterSql}
        LIMIT 1
    ", 'i' . $contractorFilterTypes, array_merge([$activityId], $contractorFilterParams))->fetch_assoc();

    if ($activity) {
        $title = trim($_POST['issue_title']);
        $description = trim($_POST['issue_description']);
        $status = 'Open';
        $today = date('Y-m-d');
        $issueContractorId = (int)$activity['contractor_id'];
        $projectId = (int)$activity['project_id'];

        $stmt = $conn->prepare("
            INSERT INTO punchlist(project_id, contractor_id, issue_title, description, reported_date, status)
            VALUES(?,?,?,?,?,?)
        ");
        $stmt->bind_param("iissss", $projectId, $issueContractorId, $title, $description, $today, $status);
        $stmt->execute();

        header("Location: ./?link=contractor-ui.php&issue=1");
        exit;
    }
}

$projectRows = contractorUiQuery($conn, "
    SELECT DISTINCT p.id, p.project_name
    FROM projects p
    INNER JOIN activities a ON a.project_id=p.id
    WHERE 1=1 {$contractorFilterSql}
    ORDER BY p.project_name
", $contractorFilterTypes, $contractorFilterParams);

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0 && $projectRows && $firstProject = $projectRows->fetch_assoc()) {
    $projectId = (int)$firstProject['id'];
    $projectRows->data_seek(0);
}

$projectFilter = $projectId > 0 ? ' AND a.project_id=?' : '';
$baseTypes = $contractorFilterTypes . ($projectId > 0 ? 'i' : '');
$baseParams = array_merge($contractorFilterParams, $projectId > 0 ? [$projectId] : []);

$assignedActivities = contractorUiScalar($conn, "SELECT COUNT(*) FROM activities a WHERE 1=1 {$contractorFilterSql} {$projectFilter}", $baseTypes, $baseParams);
$avgProgress = contractorUiScalar($conn, "SELECT IFNULL(AVG(a.progress),0) FROM activities a WHERE 1=1 {$contractorFilterSql} {$projectFilter}", $baseTypes, $baseParams);
$openIssues = contractorUiScalar($conn, "
    SELECT COUNT(*)
    FROM punchlist p
    INNER JOIN activities a ON a.contractor_id=p.contractor_id AND a.project_id=p.project_id
    WHERE IFNULL(p.status,'Open') NOT IN ('Closed','Resolved') {$contractorFilterSql} {$projectFilter}
", $baseTypes, $baseParams);
$updatesThisWeek = contractorUiScalar($conn, "
    SELECT COUNT(*)
    FROM work_updates wu
    INNER JOIN activities a ON a.id=wu.activity_id
    WHERE wu.update_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) {$contractorFilterSql} {$projectFilter}
", $baseTypes, $baseParams);

$activities = contractorUiQuery($conn, "
    SELECT a.id, a.item_no, a.description, a.target_qty, a.progress, c.name contractor_name, p.project_name
    FROM activities a
    LEFT JOIN contractors c ON c.id=a.contractor_id
    LEFT JOIN projects p ON p.id=a.project_id
    WHERE 1=1 {$contractorFilterSql} {$projectFilter}
    ORDER BY a.progress ASC, a.item_no ASC
    LIMIT 12
", $baseTypes, $baseParams);

$recentUpdates = contractorUiQuery($conn, "
    SELECT wu.*, a.item_no, a.description activity_name, p.project_name
    FROM work_updates wu
    INNER JOIN activities a ON a.id=wu.activity_id
    LEFT JOIN projects p ON p.id=wu.project_id
    WHERE 1=1 {$contractorFilterSql} {$projectFilter}
    ORDER BY wu.update_date DESC, wu.id DESC
    LIMIT 8
", $baseTypes, $baseParams);

$deliveries = contractorUiQuery($conn, "
    SELECT d.*, p.project_name
    FROM deliveries d
    LEFT JOIN projects p ON p.id=d.project_id
    WHERE (?=0 OR d.project_id=?) " . ($contractorId > 0 ? " AND d.contractor_id=?" : "") . "
    ORDER BY d.delivery_date DESC, d.id DESC
    LIMIT 6
", $contractorId > 0 ? "iii" : "ii", $contractorId > 0 ? [$projectId, $projectId, $contractorId] : [$projectId, $projectId]);

$documents = contractorUiQuery($conn, "
    SELECT d.*
    FROM documents d
    WHERE (?=0 OR d.project_id=?)
    ORDER BY d.uploaded_at DESC
    LIMIT 6
", "ii", [$projectId, $projectId]);
?>

<style>
    .role-wrap { color: #0f172a; }
    .role-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 18px; }
    .role-wrap .row > [class*="col-"] > .role-panel:only-child { height: 100%; }
    .role-muted { color: #64748b; font-size: .84rem; }
    .role-kpi { font-size: 1.55rem; font-weight: 800; color: #0b1f3a; }
    .role-table th { color: #475569 !important; font-size: .76rem; text-transform: uppercase; }
    .role-table td { vertical-align: middle; white-space: normal; overflow-wrap: anywhere; }
    .role-table td:nth-child(2),
    .role-table td:nth-child(3) { white-space: nowrap; }
    .quick-action { border: 1px solid #dbe3ef; border-radius: 8px; padding: 12px; color: #0f172a; background: #f8fafc; display: block; height: 100%; }
    .quick-action:hover { background: #eef6ff; color: #0b1f3a; }
</style>

<div class="role-wrap">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
        <div>
            <div class="role-muted">Contractor Field Workspace</div>
            <h2 class="fw-bold text-white mb-0">Assigned work, site updates, issues, and documents</h2>
        </div>
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="link" value="contractor-ui.php">
            <select name="project_id" class="form-select" onchange="this.form.submit()">
                <option value="0">All projects</option>
                <?php if ($projectRows): while ($p = $projectRows->fetch_assoc()): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $projectId === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= contractorUiH($p['project_name']) ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
            <button class="btn btn-gold"><i class="bi bi-funnel"></i></button>
        </form>
    </div>

    <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Daily work update saved.</div><?php endif; ?>
    <?php if (isset($_GET['issue'])): ?><div class="alert alert-success">Issue reported to the punchlist.</div><?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="role-panel"><div class="role-muted">Assigned Activities</div><div class="role-kpi"><?= number_format($assignedActivities) ?></div></div></div>
        <div class="col-md-3"><div class="role-panel"><div class="role-muted">Average Progress</div><div class="role-kpi"><?= number_format($avgProgress, 1) ?>%</div></div></div>
        <div class="col-md-3"><div class="role-panel"><div class="role-muted">Updates This Week</div><div class="role-kpi"><?= number_format($updatesThisWeek) ?></div></div></div>
        <div class="col-md-3"><div class="role-panel"><div class="role-muted">Open Issues</div><div class="role-kpi"><?= number_format($openIssues) ?></div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><a class="quick-action" href="./?link=contractor_worklog.php"><i class="bi bi-journal-check me-2"></i>Detailed work log</a></div>
        <div class="col-md-3"><a class="quick-action" href="./?link=purchase_request.php"><i class="bi bi-bag-plus me-2"></i>Create material request</a></div>
        <div class="col-md-3"><a class="quick-action" href="./?link=deliveries.php"><i class="bi bi-truck me-2"></i>Track deliveries</a></div>
        <div class="col-md-3"><a class="quick-action" href="./?link=documents.php"><i class="bi bi-folder2-open me-2"></i>View project documents</a></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="role-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Activity Board</h5>
                        <div class="role-muted">Use this for daily accomplishment reporting.</div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle role-table">
                        <thead><tr><th>Activity</th><th>Project</th><th>Progress</th><th class="text-end">Target</th><th></th></tr></thead>
                        <tbody>
                            <?php if ($activities && $activities->num_rows): while ($a = $activities->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= contractorUiH($a['item_no']) ?></strong><br><span class="role-muted"><?= contractorUiH($a['description']) ?></span></td>
                                    <td><?= contractorUiH($a['project_name']) ?></td>
                                    <td style="min-width:150px">
                                        <div class="progress" style="height:8px"><div class="progress-bar bg-success" style="width:<?= min((float)$a['progress'], 100) ?>%"></div></div>
                                        <small><?= number_format((float)$a['progress'], 1) ?>%</small>
                                    </td>
                                    <td class="text-end"><?= number_format((float)$a['target_qty'], 2) ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-gold" data-bs-toggle="modal" data-bs-target="#updateModal" data-activity="<?= (int)$a['id'] ?>" data-title="<?= contractorUiH($a['item_no'] . ' - ' . $a['description']) ?>"><i class="bi bi-plus-circle"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No assigned activities found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="role-panel mb-3">
                <h5 class="fw-bold mb-3">Report Site Issue</h5>
                <form method="post" class="row g-2">
                    <div class="col-12">
                        <select name="issue_activity_id" class="form-select" required>
                            <option value="">Select affected activity</option>
                            <?php
                            $issueActivities = contractorUiQuery($conn, "
                                SELECT a.id, a.item_no, a.description
                                FROM activities a
                                WHERE 1=1 {$contractorFilterSql} {$projectFilter}
                                ORDER BY a.item_no ASC
                            ", $baseTypes, $baseParams);
                            if ($issueActivities): while ($ia = $issueActivities->fetch_assoc()):
                            ?>
                                <option value="<?= (int)$ia['id'] ?>"><?= contractorUiH($ia['item_no'] . ' - ' . $ia['description']) ?></option>
                            <?php endwhile; endif; ?>
                        </select>
                    </div>
                    <div class="col-12"><input name="issue_title" class="form-control" placeholder="Issue title" required></div>
                    <div class="col-12"><textarea name="issue_description" class="form-control" rows="3" placeholder="Describe blocker, location, and needed action" required></textarea></div>
                    <div class="col-12 text-end"><button name="report_issue" class="btn btn-gold"><i class="bi bi-exclamation-triangle"></i> Submit Issue</button></div>
                </form>
            </div>

            <div class="role-panel">
                <h5 class="fw-bold mb-3">Recent Updates</h5>
                <?php if ($recentUpdates && $recentUpdates->num_rows): while ($u = $recentUpdates->fetch_assoc()): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= contractorUiH($u['activity_name']) ?></strong>
                            <span class="badge text-bg-light"><?= contractorUiH($u['qaqc_result'] ?: 'Pending') ?></span>
                        </div>
                        <div class="role-muted"><?= contractorUiH($u['update_date']) ?> · <?= number_format((float)$u['cover_sqm'], 2) ?> completed · <?= contractorUiH($u['zone']) ?></div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="text-muted">No recent updates.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="role-panel">
                <h5 class="fw-bold mb-3">Material Deliveries</h5>
                <div class="table-responsive">
                    <table class="table table-sm role-table">
                        <thead><tr><th>Item</th><th>Delivered</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php if ($deliveries && $deliveries->num_rows): while ($d = $deliveries->fetch_assoc()): ?>
                                <tr><td><?= contractorUiH($d['item_name']) ?><br><span class="role-muted"><?= contractorUiH($d['project_name']) ?></span></td><td><?= number_format((float)$d['delivered_qty']) ?> / <?= number_format((float)$d['ordered_qty']) ?> <?= contractorUiH($d['unit']) ?></td><td><?= contractorUiH($d['delivery_date']) ?></td></tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No deliveries found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="role-panel">
                <h5 class="fw-bold mb-3">Latest Documents</h5>
                <?php if ($documents && $documents->num_rows): while ($doc = $documents->fetch_assoc()): ?>
                    <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                        <div><strong><?= contractorUiH($doc['title']) ?></strong><br><span class="role-muted"><?= contractorUiH($doc['category']) ?> · <?= contractorUiH($doc['uploaded_at']) ?></span></div>
                        <a class="btn btn-sm btn-outline-primary" href="uploads/documents/<?= contractorUiH($doc['file_name']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a>
                    </div>
                <?php endwhile; else: ?>
                    <div class="text-muted">No documents uploaded yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Daily Work Update</h5>
                    <small id="activityLabel" class="text-muted"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="activity_id" id="activityInput">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="update_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-4"><label class="form-label">Zone / Floor</label><input name="zone" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label">Completed Qty</label><input type="number" step="0.01" min="0" name="cover_sqm" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Planned Manpower</label><input type="number" min="0" name="planned_manpower" class="form-control" value="0"></div>
                    <div class="col-md-6"><label class="form-label">Actual Manpower</label><input type="number" min="0" name="actual_manpower" class="form-control" value="0"></div>
                    <div class="col-12"><label class="form-label">Work Accomplished</label><textarea name="work_accomplished" class="form-control" rows="3" required></textarea></div>
                    <div class="col-12"><label class="form-label">Issues / Blockers</label><textarea name="issues_encountered" class="form-control" rows="2"></textarea></div>
                    <div class="col-12"><label class="form-label">Attachments</label><input type="file" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4,.mov,.webm"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button name="save_work_update" class="btn btn-gold"><i class="bi bi-check2-circle"></i> Save Update</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('updateModal')?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('activityInput').value = button.getAttribute('data-activity');
    document.getElementById('activityLabel').textContent = button.getAttribute('data-title');
});
</script>
