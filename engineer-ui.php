<?php
if (!isset($conn)) {
    include 'database.php';
}

$allowedRoles = ['system_admin', 'management', 'project_manager', 'engineer'];
if (!in_array($_SESSION['role'] ?? '', $allowedRoles)) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    exit;
}

function engineerUiH($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function engineerUiScalar($conn, $sql, $types = '', $params = [])
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

function engineerUiQuery($conn, $sql, $types = '', $params = [])
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

if (isset($_POST['review_update'])) {
    $workUpdateId = (int)$_POST['work_update_id'];
    $review = trim($_POST['qaqc_result']);
    $stmt = $conn->prepare("UPDATE work_updates SET qaqc_result=? WHERE id=?");
    $stmt->bind_param("si", $review, $workUpdateId);
    $stmt->execute();
    header("Location: ./?link=engineer-ui.php&reviewed=1");
    exit;
}

if (isset($_POST['create_punch'])) {
    $activityId = (int)$_POST['activity_id'];
    $activity = engineerUiQuery($conn, "SELECT project_id, contractor_id FROM activities WHERE id=? LIMIT 1", "i", [$activityId])->fetch_assoc();
    if ($activity) {
        $title = trim($_POST['issue_title']);
        $description = trim($_POST['description']);
        $reportedDate = date('Y-m-d');
        $status = 'Open';
        $projectId = (int)$activity['project_id'];
        $contractorId = (int)$activity['contractor_id'];
        $stmt = $conn->prepare("
            INSERT INTO punchlist(project_id, contractor_id, issue_title, description, reported_date, status)
            VALUES(?,?,?,?,?,?)
        ");
        $stmt->bind_param("iissss", $projectId, $contractorId, $title, $description, $reportedDate, $status);
        $stmt->execute();
        header("Location: ./?link=engineer-ui.php&punch=1");
        exit;
    }
}

if (isset($_POST['resolve_punch'])) {
    $punchId = (int)$_POST['punch_id'];
    $status = $_POST['status'];
    $resolvedDate = in_array($status, ['Closed', 'Resolved']) ? date('Y-m-d') : null;
    $stmt = $conn->prepare("UPDATE punchlist SET status=?, resolved_date=? WHERE id=?");
    $stmt->bind_param("ssi", $status, $resolvedDate, $punchId);
    $stmt->execute();
    header("Location: ./?link=engineer-ui.php&punch_updated=1");
    exit;
}

$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($projectId <= 0 && $projects && $firstProject = $projects->fetch_assoc()) {
    $projectId = (int)$firstProject['id'];
    $projects->data_seek(0);
}

$projectSql = $projectId > 0 ? " AND a.project_id=?" : "";
$projectTypes = $projectId > 0 ? "i" : "";
$projectParams = $projectId > 0 ? [$projectId] : [];

$activityCount = engineerUiScalar($conn, "SELECT COUNT(*) FROM activities a WHERE 1=1 {$projectSql}", $projectTypes, $projectParams);
$avgProgress = engineerUiScalar($conn, "SELECT IFNULL(AVG(a.progress),0) FROM activities a WHERE 1=1 {$projectSql}", $projectTypes, $projectParams);
$pendingReviews = engineerUiScalar($conn, "
    SELECT COUNT(*)
    FROM work_updates wu
    INNER JOIN activities a ON a.id=wu.activity_id
    WHERE (wu.qaqc_result IS NULL OR wu.qaqc_result='' OR wu.qaqc_result='Pending engineer review') {$projectSql}
", $projectTypes, $projectParams);
$openPunch = engineerUiScalar($conn, "
    SELECT COUNT(*)
    FROM punchlist p
    INNER JOIN activities a ON a.contractor_id=p.contractor_id AND a.project_id=p.project_id
    WHERE IFNULL(p.status,'Open') NOT IN ('Closed','Resolved') {$projectSql}
", $projectTypes, $projectParams);
$behindActivities = engineerUiScalar($conn, "SELECT COUNT(*) FROM activities a WHERE IFNULL(a.progress,0) < 80 {$projectSql}", $projectTypes, $projectParams);

$recentUpdates = engineerUiQuery($conn, "
    SELECT wu.*, a.item_no, a.description activity_name, c.name contractor_name, p.project_name
    FROM work_updates wu
    INNER JOIN activities a ON a.id=wu.activity_id
    LEFT JOIN contractors c ON c.id=a.contractor_id
    LEFT JOIN projects p ON p.id=wu.project_id
    WHERE 1=1 {$projectSql}
    ORDER BY wu.update_date DESC, wu.id DESC
    LIMIT 10
", $projectTypes, $projectParams);

$riskActivities = engineerUiQuery($conn, "
    SELECT a.*, c.name contractor_name, p.project_name
    FROM activities a
    LEFT JOIN contractors c ON c.id=a.contractor_id
    LEFT JOIN projects p ON p.id=a.project_id
    WHERE IFNULL(a.progress,0) < 80 {$projectSql}
    ORDER BY a.progress ASC, a.item_no ASC
    LIMIT 10
", $projectTypes, $projectParams);

$punchlist = engineerUiQuery($conn, "
    SELECT p.*, c.name contractor_name, pr.project_name
    FROM punchlist p
    LEFT JOIN contractors c ON c.id=p.contractor_id
    LEFT JOIN projects pr ON pr.id=p.project_id
    WHERE (?=0 OR p.project_id=?)
    ORDER BY FIELD(p.status,'Open','For Verification','Resolved','Closed'), p.reported_date DESC, p.id DESC
    LIMIT 10
", "ii", [$projectId, $projectId]);

$activities = engineerUiQuery($conn, "
    SELECT a.id, a.item_no, a.description, c.name contractor_name
    FROM activities a
    LEFT JOIN contractors c ON c.id=a.contractor_id
    WHERE 1=1 {$projectSql}
    ORDER BY a.item_no ASC
", $projectTypes, $projectParams);
?>

<style>
    .role-wrap {
        color: #0f172a;
    }

    .role-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 18px;
        height: 100%;
    }

    .role-muted {
        color: #64748b;
        font-size: .84rem;
    }

    .role-kpi {
        font-size: 1.55rem;
        font-weight: 800;
        color: #0b1f3a;
    }

    .role-table th {
        color: #475569 !important;
        font-size: .76rem;
        text-transform: uppercase;
    }

    .role-table td {
        vertical-align: middle;
    }

    .quick-action {
        border: 1px solid #dbe3ef;
        border-radius: 8px;
        padding: 12px;
        color: #0f172a;
        background: #f8fafc;
        display: block;
        height: 100%;
    }

    .quick-action:hover {
        background: #eef6ff;
        color: #0b1f3a;
    }
</style>

<div class="role-wrap">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center mb-3">
        <div>
            <div class="role-muted">Engineer Field Control</div>
            <h2 class="fw-bold text-white mb-0">Verify updates, track quality issues, and manage field risks</h2>
        </div>
        <form method="get" class="d-flex gap-2">
            <input type="hidden" name="link" value="engineer-ui.php">
            <select name="project_id" class="form-select" onchange="this.form.submit()">
                <option value="0">All projects</option>
                <?php if ($projects): while ($p = $projects->fetch_assoc()): ?>
                        <option value="<?= (int)$p['id'] ?>" <?= $projectId === (int)$p['id'] ? 'selected' : '' ?>>
                            <?= engineerUiH($p['project_name']) ?>
                        </option>
                <?php endwhile;
                endif; ?>
            </select>
            <button class="btn btn-gold"><i class="bi bi-funnel"></i></button>
        </form>
    </div>

    <?php if (isset($_GET['reviewed'])): ?><div class="alert alert-success">QA/QC review saved.</div><?php endif; ?>
    <?php if (isset($_GET['punch'])): ?><div class="alert alert-success">Punchlist item created.</div><?php endif; ?>
    <?php if (isset($_GET['punch_updated'])): ?><div class="alert alert-success">Punchlist status updated.</div><?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="role-panel">
                <div class="role-muted">Activities</div>
                <div class="role-kpi"><?= number_format($activityCount) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="role-panel">
                <div class="role-muted">Average Progress</div>
                <div class="role-kpi"><?= number_format($avgProgress, 1) ?>%</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="role-panel">
                <div class="role-muted">Pending QA Reviews</div>
                <div class="role-kpi"><?= number_format($pendingReviews) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="role-panel">
                <div class="role-muted">Open Punchlist</div>
                <div class="role-kpi"><?= number_format($openPunch) ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><a class="quick-action" href="./?link=work_update.php"><i class="bi bi-clipboard-check me-2"></i>Full work update log</a></div>
        <div class="col-md-3"><a class="quick-action" href="./?link=activities.php"><i class="bi bi-list-task me-2"></i>Manage activities</a></div>
        <div class="col-md-3"><a class="quick-action" href="./?link=schedule.php"><i class="bi bi-calendar-week me-2"></i>Review schedule</a></div>
        <div class="col-md-3"><a class="quick-action" href="./?link=documents.php"><i class="bi bi-folder2-open me-2"></i>Project documents</a></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="role-panel">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Daily Updates For Verification</h5>
                        <div class="role-muted">Record accepted quantities, rejected work, or corrective notes.</div>
                    </div>
                    <span class="badge text-bg-warning"><?= number_format($pendingReviews) ?> pending</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm role-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Activity</th>
                                <th>Qty</th>
                                <th>QA/QC</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recentUpdates && $recentUpdates->num_rows): while ($u = $recentUpdates->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= engineerUiH($u['update_date']) ?><br><span class="role-muted"><?= engineerUiH($u['zone']) ?></span></td>
                                        <td><strong><?= engineerUiH($u['item_no']) ?></strong> <?= engineerUiH($u['activity_name']) ?><br><span class="role-muted"><?= engineerUiH($u['contractor_name']) ?></span></td>
                                        <td><?= number_format((float)$u['cover_sqm'], 2) ?></td>
                                        <td><span class="badge text-bg-light"><?= engineerUiH($u['qaqc_result'] ?: 'Pending engineer review') ?></span></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-gold" data-bs-toggle="modal" data-bs-target="#reviewModal" data-id="<?= (int)$u['id'] ?>" data-title="<?= engineerUiH($u['item_no'] . ' - ' . $u['activity_name']) ?>" data-review="<?= engineerUiH($u['qaqc_result']) ?>"><i class="bi bi-pencil-square"></i></button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No work updates found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="role-panel mb-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Field Risk Watch</h5>
                        <div class="role-muted"><?= number_format($behindActivities) ?> activities under 80% progress.</div>
                    </div>
                </div>
                <?php if ($riskActivities && $riskActivities->num_rows): while ($a = $riskActivities->fetch_assoc()): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between gap-2">
                                <strong><?= engineerUiH($a['item_no'] . ' - ' . $a['description']) ?></strong>
                                <span><?= number_format((float)$a['progress'], 1) ?>%</span>
                            </div>
                            <div class="progress my-1" style="height:8px">
                                <div class="progress-bar bg-warning" style="width:<?= min((float)$a['progress'], 100) ?>%"></div>
                            </div>
                            <div class="role-muted"><?= engineerUiH($a['project_name']) ?> · <?= engineerUiH($a['contractor_name']) ?></div>
                        </div>
                    <?php endwhile;
                else: ?>
                    <div class="text-muted">No low-progress activities found.</div>
                <?php endif; ?>
            </div>

            <div class="role-panel">
                <h5 class="fw-bold mb-3">Create Punchlist Item</h5>
                <form method="post" class="row g-2">
                    <div class="col-12">
                        <select name="activity_id" class="form-select" required>
                            <option value="">Select activity</option>
                            <?php if ($activities): while ($a = $activities->fetch_assoc()): ?>
                                    <option value="<?= (int)$a['id'] ?>"><?= engineerUiH($a['item_no'] . ' - ' . $a['description'] . ' / ' . $a['contractor_name']) ?></option>
                            <?php endwhile;
                            endif; ?>
                        </select>
                    </div>
                    <div class="col-12"><input name="issue_title" class="form-control" placeholder="Issue title" required></div>
                    <div class="col-12"><textarea name="description" class="form-control" rows="3" placeholder="Location, defect, required correction, target date" required></textarea></div>
                    <div class="col-12 text-end"><button name="create_punch" class="btn btn-gold"><i class="bi bi-plus-circle"></i> Add Punchlist</button></div>
                </form>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="role-panel">
                <h5 class="fw-bold mb-3">Punchlist Control</h5>
                <div class="table-responsive">
                    <table class="table table-sm role-table">
                        <thead>
                            <tr>
                                <th>Issue</th>
                                <th>Project</th>
                                <th>Contractor</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($punchlist && $punchlist->num_rows): while ($p = $punchlist->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?= engineerUiH($p['issue_title']) ?></strong><br><span class="role-muted"><?= engineerUiH($p['description']) ?></span></td>
                                        <td><?= engineerUiH($p['project_name']) ?></td>
                                        <td><?= engineerUiH($p['contractor_name']) ?></td>
                                        <td><span class="badge text-bg-light"><?= engineerUiH($p['status'] ?: 'Open') ?></span></td>
                                        <td><?= engineerUiH($p['reported_date']) ?></td>
                                        <td>
                                            <form method="post" class="d-flex gap-2">
                                                <input type="hidden" name="punch_id" value="<?= (int)$p['id'] ?>">
                                                <select name="status" class="form-select form-select-sm no-select2">
                                                    <?php foreach (['Open', 'For Verification', 'Resolved', 'Closed'] as $status): ?>
                                                        <option value="<?= $status ?>" <?= ($p['status'] ?: 'Open') === $status ? 'selected' : '' ?>><?= $status ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button name="resolve_punch" class="btn btn-sm btn-outline-primary"><i class="bi bi-check2"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No punchlist items found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">QA/QC Review</h5>
                    <small id="reviewLabel" class="text-muted"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="work_update_id" id="reviewInput">
                <label class="form-label">Review Result</label>
                <select name="qaqc_result" id="reviewResult" class="form-select no-select2" required>
                    <option>Accepted</option>
                    <option>Accepted with comments</option>
                    <option>For correction</option>
                    <option>Rejected</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button name="review_update" class="btn btn-gold"><i class="bi bi-check2-circle"></i> Save Review</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('reviewModal')?.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('reviewInput').value = button.getAttribute('data-id');
        document.getElementById('reviewLabel').textContent = button.getAttribute('data-title');
        const current = button.getAttribute('data-review');
        if (current) {
            document.getElementById('reviewResult').value = current;
        }
    });
</script>