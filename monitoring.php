<?php
include 'database.php';
/* SAVE */
if (isset($_POST['save'])) {
    $project_id    = (int)$_POST['project_id'];
    $section_type  = $_POST['section_type'];
    $category_name = trim($_POST['category_name']);
    $scope_of_work = trim($_POST['scope_of_work']);
    $sub_scope     = trim($_POST['sub_scope']);
    $day_label     = trim($_POST['day_label']);
    $contractor_id = (int)($_POST['contractor_id'] ?? 0);
    $contract_amount = (float)$_POST['contract_amount'];
    $progress_rate   = (float)$_POST['progress_rate'];
    $target_acc   = (float)$_POST['target_acc'];
    $actual_acc   = (float)$_POST['actual_acc'];
    $monitor_date = $_POST['monitor_date'];
    /* formulas */
    $downpayment      = $contract_amount * 0.20;
    $progress_billing = $contract_amount * $progress_rate;
    $balance          = $contract_amount - $downpayment - $progress_billing;
    $percent = $target_acc > 0 ? ($actual_acc / $target_acc) * 100 : 0;
    $stmt = $conn->prepare("
        INSERT INTO monitoring_entries(
            project_id, section_type, category_name, scope_of_work,
            sub_scope, day_label,
            contractor_id, contract_amount, downpayment,
            progress_billing, balance,
            target_acc, actual_acc, monitor_date,
            progress_rate, percent_accomplishment
        )
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
        "isssssiddddddsdd",
        $project_id,
        $section_type,
        $category_name,
        $scope_of_work,
        $sub_scope,
        $day_label,
        $contractor_id,
        $contract_amount,
        $downpayment,
        $progress_billing,
        $balance,
        $target_acc,
        $actual_acc,
        $monitor_date,
        $progress_rate,
        $percent
    );
    $stmt->execute();
    header("Location: ./?link=monitoring.php&saved=1");
    exit;
}
/* DATA */
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");
$contractors = $conn->query("SELECT id, name FROM contractors ORDER BY name");
$rows = $conn->query("
    SELECT m.*, p.project_name, c.name contractor_name
    FROM monitoring_entries m
    LEFT JOIN projects p ON m.project_id=p.id
    LEFT JOIN contractors c ON m.contractor_id=c.id
    ORDER BY m.id DESC
");
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-white fw-bold">Monitoring</h2>
        <small class="text-secondary">Budget + Daily Monitoring</small>
    </div>
    <a href="export_monitoring_pdf.php" class="btn btn-danger">
        Export PDF
    </a>
</div>
<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success py-2">Saved successfully.</div>
<?php endif; ?>
<div class="card p-4 mb-4">
    <form method="post" class="row g-3">
        <div class="col-md-2">
            <label>Project</label>
            <select name="project_id" class="form-select select2">
                <?php while ($p = $projects->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['project_name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Section</label>
            <select name="section_type" class="form-select select2">
                <option value="budget">Budget</option>
                <option value="daily">Daily</option>
            </select>
        </div>
        <div class="col-md-2">
            <label>Category</label>
            <input type="text" name="category_name" class="form-control">
        </div>
        <div class="col-md-2">
            <label>Scope</label>
            <input type="text" name="scope_of_work" class="form-control">
        </div>
        <div class="col-md-2">
            <label>Sub Scope</label>
            <input type="text" name="sub_scope" class="form-control" placeholder="CHB">
        </div>
        <div class="col-md-2">
            <label>Floor / Zone</label>
            <input type="text" name="day_label" class="form-control" placeholder="4th">
        </div>
        <div class="col-md-2">
            <label>Contractor</label>
            <select name="contractor_id" class="form-select">
                <option value="0">-</option>
                <?php while ($c = $contractors->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Date</label>
            <input type="date" name="monitor_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label>Contract Amount</label>
            <input type="number" step="0.01" name="contract_amount" class="form-control">
        </div>
        <div class="col-md-2">
            <label>Progress Rate</label>
            <input type="number" step="0.01" name="progress_rate" class="form-control" placeholder="0.70">
        </div>
        <div class="col-md-2">
            <label>Target Acc</label>
            <input type="number" step="0.01" name="target_acc" class="form-control">
        </div>
        <div class="col-md-2">
            <label>Actual Acc</label>
            <input type="number" step="0.01" name="actual_acc" class="form-control">
        </div>
        <div class="col-md-12">
            <button name="save" class="btn btn-gold">Save Entry</button>
        </div>
    </form>
</div>
<div class="card p-3">
    <h4>Monitoring</h4>
    <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
        onclick="toggleFullscreen(this)">
        <i class="bi bi-arrows-fullscreen"></i>
    </button>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr>
                    <th>Work Item</th>
                    <th>Tower</th>
                    <th>Floor / Area</th>
                    <th>Contractor</th>
                    <th>BOQ / Scope Category</th>
                    <th>Planned SQM</th>
                    <th>Actual Accomplished SQM</th>
                    <th>Variance SQM</th>
                    <th>% Accomplishment</th>
                    <th>Inspected By</th>
                    <th>Date Inspected</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $rows->fetch_assoc()):
                    $variance = $r['actual_acc'] - $r['target_acc'];
                    if ($variance >= 0) {
                        $remarks = 'Ahead';
                    } elseif ($variance >= -50) {
                        $remarks = 'Minor delay';
                    } else {
                        $remarks = 'Critical delay';
                    }
                ?>
                    <tr>
                        <td>
                            <strong>
                                <?= $r['category_name'] ?>
                            </strong>
                            <div class="small text-secondary">
                                <?= $r['scope_of_work'] ?>
                            </div>
                        </td>
                        <td>
                            <?= $r['project_name'] ?>
                        </td>
                        <td>
                            <?= $r['day_label'] ?>
                        </td>
                        <td>
                            <?= $r['contractor_name'] ?>
                        </td>
                        <td>
                            <?= $r['sub_scope'] ?>
                        </td>
                        <td>
                            <?= number_format($r['target_acc'], 2) ?>
                        </td>
                        <td>
                            <?= number_format($r['actual_acc'], 2) ?>
                        </td>
                        <td class="<?= $variance < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($variance, 2) ?>
                        </td>
                        <td>
                            <?= number_format($r['percent_accomplishment'], 2) ?>%
                        </td>
                        <td>
                            Site Engineer
                        </td>
                        <td>
                            <?= date('d-M-y', strtotime($r['monitor_date'])) ?>
                        </td>
                        <td>
                            <span class="<?= $variance < 0 ? 'text-danger' : 'text-success' ?>">
                                <?= $remarks ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>