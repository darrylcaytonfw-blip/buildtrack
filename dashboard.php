<?php
include 'database.php';
/* ==================================================
   EXECUTIVE DASHBOARD - ONE PROJECT VIEW
================================================== */
function val($conn, $sql, $field = 'total')
{
    $q = @$conn->query($sql);
    if ($q && $r = $q->fetch_assoc()) return $r[$field];
    return 0;
}
/* PROJECT LIST */
$projects = $conn->query("SELECT id, project_name, owner, location FROM projects ORDER BY project_name");
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($project_id <= 0) {
    $first = $conn->query("SELECT id FROM projects ORDER BY id ASC LIMIT 1");
    if ($first && $row = $first->fetch_assoc()) {
        $project_id = (int)$row['id'];
    }
}
/* SELECTED PROJECT */
$project = $conn->query("
    SELECT *
    FROM projects
    WHERE id = $project_id
    LIMIT 1
")->fetch_assoc();
$projectName = $project['project_name'] ?? 'No Project';
$owner       = $project['owner'] ?? '-';
$location    = $project['location'] ?? '-';
/* ==================================================
   KPI
================================================== */
$contractAmount = val($conn, "
SELECT IFNULL(SUM(estimated_price),0) total
FROM work_items
");
$totalActualScope = val($conn, "
SELECT IFNULL(SUM(actual_price),0) total
FROM work_items
");
$actualSqm = val($conn, "
SELECT COUNT(*) total
FROM work_items
");
$totalBillings = val($conn, "
SELECT IFNULL(SUM(amount),0) total
FROM billings
WHERE project_id = $project_id
");
$retention = val($conn, "
SELECT IFNULL(SUM(retention_amount),0) total
FROM retentions r
LEFT JOIN billings b ON r.billing_id=b.id
WHERE b.project_id = $project_id
");
$netAmount = $totalBillings - $retention;
$progressPlanned = 100;
$progressActual = val($conn, "
SELECT IFNULL(AVG(progress),0) total
FROM activities
");
$variance = $progressActual - $progressPlanned;
$status = $variance >= 0 ? 'On Schedule' : 'Slightly Behind Schedule';
/* BILLING */
$totalPaid = val($conn, "
SELECT IFNULL(SUM(amount),0) total
FROM billings
WHERE project_id=$project_id
AND status='Paid'
");
$outstanding = $totalBillings - $totalPaid;
/* PAYABLES */
$payables = val($conn, "
SELECT IFNULL(SUM(paid_amount),0) total
FROM payables
");
/* ==================================================
   TABLES
================================================== */
$workRows = $conn->query("
SELECT wi.item_name,
       wi.estimated_price planned,
       wi.actual_price actual
FROM work_items wi
ORDER BY wi.id ASC
LIMIT 6
");
$billingRows = $conn->query("
SELECT *
FROM billings
WHERE project_id=$project_id
ORDER BY id ASC
LIMIT 10
");
$deliveryRows = $conn->query("
SELECT item_name, ordered_qty, delivered_qty
FROM deliveries
ORDER BY id DESC
LIMIT 5
");
?>
<style>
    * {
        font-family: Arial, Helvetica, sans-serif;
    }
    .exec-wrap {
        background: #FFF;
        border-radius: 22px;
        padding: 24px;
        color: #0a1b33;
    }
    .exec-card {
        background: #FFF;
        border: 1px solid #e6e8eb;
        border-radius: 18px;
        padding: 18px;
        height: 100%;
        color: #0a1b33;
    }
    .kpi-title {
        font-size: .72rem;
        color: #6c757d;
        text-transform: uppercase;
    }
    .kpi-value {
        font-size: 1.45rem;
        font-weight: 700;
    }
    .nav-pills .nav-link {
        border-radius: 0px;
        color: #7b7b7b;
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        font-weight: bold;
    }
    .nav-pills .nav-link.active {
        background: #FFF;
        color: #0a1b33;
        border: 1px solid #dee2e6;
        border-bottom: none;
        box-shadow: 0 -3px 3px rgba(0, 0, 0, 0.2);
    }
    .table-sm td,
    .table-sm th {
        padding: .55rem;
    }
</style>
<div class="exec-wrap">
    
    <!-- TOP BAR -->
    <div class="exec-card mb-4">
        
        <div class="row g-3 align-items-center">
            <div class="col-lg-5">
                <small class="text-secondary d-block">Contractor Progress Billing & Executive Monitoring Report</small>
                <h2 class="fw-bold mb-1"><?= $projectName ?></h2>
                <small class="text-secondary">
                    Owner: <?= $owner ?> · Location: <?= $location ?>
                </small>
            </div>
            <div class="col-lg-7">
                <form method="get" class="row g-2">
                    <input type="hidden" name="link" value="dashboard.php">
                    <div class="col-md-12">
                        <select name="project_id" class="form-select" onchange="this.form.submit()">
                            <?php while ($p = $projects->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['id'] == $project_id ? 'selected' : '' ?>>
                                    <?= $p['project_name'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100">
                                    <div class="kpi-title">Contract</div>
                                    <div class="fw-bold">₱<?= number_format($contractAmount, 0) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100">
                                    <div class="kpi-title">Actual SQM</div>
                                    <div class="fw-bold"><?= number_format($actualSqm) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100">
                                    <div class="kpi-title">Net Due</div>
                                    <div class="fw-bold">₱<?= number_format($netAmount, 0) ?></div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="border rounded p-2 h-100">
                                    <div class="kpi-title">Status</div>
                                    <div class="fw-bold small"><?= $status ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- TABS -->
    <ul class="nav nav-pills nav-fill mb-4" id="dashTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#ceo">CEO View</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#info">Project Info</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#work">Work Progress</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#billing">Billing</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#risk">Supply / Risks</button>
        </li>
    </ul>
    <div class="tab-content border p-3" style="margin-top:-25px;">
        <!-- CEO VIEW -->
        <div class="tab-pane fade show active" id="ceo">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Project Status</div>
                        <div class="kpi-value mt-2"><?= $status ?></div>
                        <small class="text-secondary">Operational schedule health</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Planned vs Actual</div>
                        <div class="kpi-value mt-2"><?= number_format($progressPlanned, 0) ?>% vs
                            <?= number_format($progressActual, 0) ?>%</div>
                        <small class="<?= $variance < 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($variance, 1) ?>% variance
                        </small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Cash Position</div>
                        <div class="kpi-value mt-2">₱<?= number_format($totalPaid, 0) ?></div>
                        <small class="text-secondary">Outstanding ₱<?= number_format($outstanding, 0) ?></small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Schedule Index</div>
                        <div class="kpi-value mt-2"><?= number_format($progressActual / 100, 2) ?></div>
                        <small class="text-secondary">Performance factor</small>
                    </div>
                </div>
            </div>
            <!-- EXECUTIVE COMMAND CENTER -->
            <div class="exec-card">
                <h4 class="fw-bold mb-1">
                    Three-Tower Progress Summary — Executive Command Center
                </h4>
                <small class="text-secondary d-block mb-4">
                    Tower average progress, tower variance, risk per tower
                </small>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Tower</th>
                                <th>Use / Type</th>
                                <th>No. of Floors</th>
                                <th>Total SQM</th>
                                <th>Planned Progress %</th>
                                <th>Actual Progress %</th>
                                <th>Variance %</th>
                                <th>Status</th>
                                <th>Major Risk</th>
                                <th>Required CEO Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $towerSummary = $conn->query("
                    SELECT
                        p.project_name,
                        p.floor_count,
                        IFNULL(SUM(ci.sqm),0) total_sqm,
                        IFNULL(AVG(a.progress),0) actual_progress,
                        100 AS planned_progress
                    FROM projects p
                    LEFT JOIN contractor_items ci
                        ON p.id = ci.project_id
                    LEFT JOIN activities a
                        ON p.id = a.project_id
                    GROUP BY p.id
                    ORDER BY p.id ASC
                ");
                            while ($t = $towerSummary->fetch_assoc()):
                                $planned = (float)$t['planned_progress'];
                                $actual = (float)$t['actual_progress'];
                                $varianceTower = $actual - $planned;
                                if ($varianceTower >= 0) {
                                    $towerStatus = 'On Track';
                                    $risk = 'Low operational risk';
                                    $action = 'Continue monitoring';
                                } elseif ($varianceTower >= -10) {
                                    $towerStatus = 'Watch';
                                    $risk = 'Potential manpower delay';
                                    $action = 'Weekly technical meeting';
                                } else {
                                    $towerStatus = 'Behind';
                                    $risk = 'Critical construction delay';
                                    $action = 'Contractor recovery plan';
                                }
                            ?>
                                <tr>
                                    <td class="fw-bold">
                                        <?= $t['project_name'] ?>
                                    </td>
                                    <td>
                                        Residential Tower
                                    </td>
                                    <td>
                                        <?= $t['floor_count'] ?>
                                    </td>
                                    <td>
                                        <?= number_format($t['total_sqm'], 0) ?>
                                    </td>
                                    <td>
                                        <?= number_format($planned, 0) ?>%
                                    </td>
                                    <td>
                                        <?= number_format($actual, 0) ?>%
                                    </td>
                                    <td class="<?= $varianceTower < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($varianceTower, 1) ?>%
                                    </td>
                                    <td>
                                        <?php if ($towerStatus == 'On Track'): ?>
                                            <span class="badge bg-success">
                                                <?= $towerStatus ?>
                                            </span>
                                        <?php elseif ($towerStatus == 'Watch'): ?>
                                            <span class="badge bg-warning text-dark">
                                                <?= $towerStatus ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <?= $towerStatus ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $risk ?>
                                    </td>
                                    <td>
                                        <?= $action ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- PROJECT INFO -->
        <div class="tab-pane fade" id="info">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="exec-card">
                        <h4 class="fw-bold mb-3">Contract Information</h4>
                        <table class="table table-sm">
                            <tr>
                                <td>Project Owner</td>
                                <td class="text-end"><?= $owner ?></td>
                            </tr>
                            <tr>
                                <td>Location</td>
                                <td class="text-end"><?= $location ?></td>
                            </tr>
                            <tr>
                                <td>Contract Type</td>
                                <td class="text-end">Progress Billing</td>
                            </tr>
                            <tr>
                                <td>Contract Amount</td>
                                <td class="text-end">₱<?= number_format($contractAmount, 0) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="exec-card">
                        <h4 class="fw-bold mb-3">Timeline Utilization</h4>
                        <?php $elapsed = min(100, $progressActual); ?>
                        <div class="d-flex justify-content-between">
                            <small>Time Elapsed</small>
                            <small><?= number_format($elapsed, 1) ?>%</small>
                        </div>
                        <div class="progress mt-2" style="height:10px;">
                            <div class="progress-bar" style="width:<?= $elapsed ?>%"></div>
                        </div>
                        <p class="mt-3 text-secondary mb-0">
                            This package has consumed project timeline proportionally to actual progress.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- WORK -->
        <div class="tab-pane fade" id="work">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Planned</div>
                        <div class="kpi-value"><?= number_format($contractAmount, 0) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Actual</div>
                        <div class="kpi-value"><?= number_format($totalActualScope, 0) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Variance</div>
                        <div class="kpi-value"><?= number_format($totalActualScope - $contractAmount, 0) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">% Completion</div>
                        <div class="kpi-value"><?= number_format($progressActual, 1) ?>%</div>
                    </div>
                </div>
            </div>
            <div class="exec-card">
                <h4 class="fw-bold mb-3">Detailed Work Accomplishment</h4>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Work Item</th>
                                <th>Planned</th>
                                <th>Actual</th>
                                <th>Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($w = $workRows->fetch_assoc()):
                                $v = $w['actual'] - $w['planned'];
                            ?>
                                <tr>
                                    <td><?= $w['item_name'] ?></td>
                                    <td><?= number_format($w['planned'], 0) ?></td>
                                    <td><?= number_format($w['actual'], 0) ?></td>
                                    <td class="<?= $v < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= number_format($v, 0) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- BILLING -->
        <div class="tab-pane fade" id="billing">
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Previous Billing</div>
                        <div class="kpi-value">₱<?= number_format($totalPaid, 0) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Current</div>
                        <div class="kpi-value">₱<?= number_format($outstanding, 0) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Earned To Date</div>
                        <div class="kpi-value">₱<?= number_format($totalBillings, 0) ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="exec-card">
                        <div class="kpi-title">Retention</div>
                        <div class="kpi-value">₱<?= number_format($retention, 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="exec-card">
                <h4 class="fw-bold mb-3">Payment History</h4>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Stage</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = $billingRows->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $b['id'] ?></td>
                                    <td><?= $b['billing_stage'] ?></td>
                                    <td>₱<?= number_format($b['amount'], 2) ?></td>
                                    <td><?= $b['status'] ?></td>
                                    <td><?= $b['billing_date'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- RISKS -->
        <div class="tab-pane fade" id="risk">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="exec-card">
                        <h4 class="fw-bold mb-3">Supply Status</h4>
                        <?php while ($d = $deliveryRows->fetch_assoc()):
                            $p = $d['ordered_qty'] > 0 ? ($d['delivered_qty'] / $d['ordered_qty']) * 100 : 0;
                        ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span><?= $d['item_name'] ?></span>
                                    <small><?= number_format($p, 0) ?>%</small>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-success" style="width:<?= min(100, $p) ?>%"></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="exec-card">
                        <h4 class="fw-bold mb-3">Issues, Delays, Corrective Actions</h4>
                        <div class="border rounded p-3 mb-3">
                            <strong>Late Material Delivery</strong>
                            <div class="small text-secondary">Impact: Work slowdown</div>
                            <div class="small">Action: Expedite supplier delivery.</div>
                        </div>
                        <div class="border rounded p-3 mb-3">
                            <strong>Labor Shortage</strong>
                            <div class="small text-secondary">Impact: Reduced productivity</div>
                            <div class="small">Action: Deploy additional manpower.</div>
                        </div>
                        <div class="border rounded p-3">
                            <strong>Pending Owner Materials</strong>
                            <div class="small text-secondary">Impact: Finishing delay</div>
                            <div class="small">Action: Follow-up owner procurement.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>