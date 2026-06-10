<?php
/* financial_control.php
   FINAL FUNCTIONAL VERSION
   BuildTrack Financial Control Center
*/
include 'database.php';
/* ==================================================
   FILTERS
================================================== */
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
/* ==================================================
   HELPERS
================================================== */
function one($conn, $sql)
{
    $q = $conn->query($sql);
    if (!$q) return 0;
    $r = $q->fetch_row();
    return (float)($r[0] ?? 0);
}
function esc($v)
{
    return htmlspecialchars((string)$v);
}
$whereProject = $project_id > 0 ? " WHERE p.id = $project_id " : "";
$whereBilling = $project_id > 0 ? " WHERE b.project_id = $project_id " : "";
$whereAct     = $project_id > 0 ? " WHERE a.project_id = $project_id " : "";
/* ==================================================
   PROJECT LIST
================================================== */
$projects = [];
$pr = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");
if ($pr) {
    while ($row = $pr->fetch_assoc()) $projects[] = $row;
}
/* ==================================================
   PROJECT INFO
================================================== */
$pageTitle = "All Projects";
$owner = "-";
$location = "-";
if ($project_id > 0) {
    $info = $conn->query("
        SELECT project_name, owner, location
        FROM projects
        WHERE id = $project_id
        LIMIT 1
    ");
    if ($info && $info->num_rows) {
        $r = $info->fetch_assoc();
        $pageTitle = $r['project_name'];
        $owner     = $r['owner'];
        $location  = $r['location'];
    }
}
/* ==================================================
   KPI QUERIES
================================================== */
/* Contract Value = approved/total billings */
$contract = one($conn, "
    SELECT IFNULL(SUM(amount),0)
    FROM billings b
    $whereBilling
");
/* Budget = estimated costs */
$budget = one($conn, "
    SELECT IFNULL(SUM(wi.estimated_price),0)
    FROM work_items wi
    LEFT JOIN activities a ON wi.activity_id = a.id
    $whereAct
");
/* Actual Cost = actual prices */
$actual = one($conn, "
    SELECT IFNULL(SUM(wi.actual_price),0)
    FROM work_items wi
    LEFT JOIN activities a ON wi.activity_id = a.id
    $whereAct
");
/* Paid Collections */
$collections = one(
    $conn,
    "
    SELECT IFNULL(SUM(paid_amount),0)
    FROM payables py
    LEFT JOIN billings b ON py.billing_id = b.id
    " . ($project_id > 0 ? "WHERE b.project_id = $project_id" : "")
);
/* Payables Paid Out */
$paidOut = one(
    $conn,
    "
    SELECT IFNULL(SUM(paid_amount),0)
    FROM payables py
    LEFT JOIN billings b ON py.billing_id = b.id
    " . ($project_id > 0 ? "WHERE b.project_id = $project_id" : "")
);
$profit = $contract - $actual;
$cash   = $collections - $paidOut;
/* Progress */
$plannedPct = 100;
$actualPct = one($conn, "
    SELECT IFNULL(AVG(progress),0)
    FROM activities a
    $whereAct
");
if ($actualPct > 100) $actualPct = 100;
$variance = $actualPct - $plannedPct;
$status = $variance >= 0 ? "On Schedule" : "Delayed";
/* ==================================================
   CHART 1 - COST CATEGORY
================================================== */
$catLabels = [];
$catBudget = [];
$catActual = [];
$q1 = $conn->query("
    SELECT
        COALESCE(wc.category_name,'Uncategorized') AS cat,
        SUM(wi.estimated_price) AS est,
        SUM(wi.actual_price) AS act
    FROM work_items wi
    LEFT JOIN activities a ON wi.activity_id = a.id
    LEFT JOIN work_activities wa ON wi.activity_id = wa.id
    LEFT JOIN work_disciplines wd ON wa.discipline_id = wd.id
    LEFT JOIN work_categories wc ON wd.category_id = wc.id
    " . ($project_id > 0 ? "WHERE a.project_id = $project_id" : "") . "
    GROUP BY wc.category_name
    ORDER BY wc.category_name
");
if ($q1) {
    while ($r = $q1->fetch_assoc()) {
        $catLabels[] = $r['cat'];
        $catBudget[] = (float)$r['est'];
        $catActual[] = (float)$r['act'];
    }
}
/* ==================================================
   CHART 2 - CASHFLOW TREND
================================================== */
$months = [];
$inflow = [];
$outflow = [];
$q2 = $conn->query("
    SELECT
        DATE_FORMAT(billing_date,'%b') m,
        SUM(b.amount) amt
    FROM billings b
    " . ($project_id > 0 ? "WHERE b.project_id = $project_id" : "") . "
    GROUP BY MONTH(billing_date), DATE_FORMAT(billing_date,'%b')
    ORDER BY MONTH(billing_date)
");
if ($q2) {
    while ($r = $q2->fetch_assoc()) {
        $months[] = $r['m'];
        $inflow[] = (float)$r['amt'];
        $outflow[] = 0;
    }
}
/* fallback */
if (!$months) {
    $months = ['Jan', 'Feb', 'Mar', 'Apr'];
    $inflow = [0, 0, 0, 0];
    $outflow = [0, 0, 0, 0];
}
/* ==================================================
   CONSOLIDATION TABLE
================================================== */
$rows = [];
$tbl = $conn->query("
    SELECT p.id, p.project_name
    FROM projects p
    ORDER BY p.project_name
");
if ($tbl) {
    while ($p = $tbl->fetch_assoc()) {
        $pid = (int)$p['id'];
        $c = one($conn, "SELECT IFNULL(SUM(amount),0) FROM billings WHERE project_id=$pid");
        $b = one($conn, "
            SELECT IFNULL(SUM(wi.estimated_price),0)
            FROM work_items wi
            LEFT JOIN activities a ON wi.activity_id=a.id
            WHERE a.project_id=$pid
        ");
        $a = one($conn, "
            SELECT IFNULL(SUM(wi.actual_price),0)
            FROM work_items wi
            LEFT JOIN activities ac ON wi.activity_id=ac.id
            WHERE ac.project_id=$pid
        ");
        $forecast = $c - $a;
        $rows[] = [
            'name' => $p['project_name'],
            'contract' => $c,
            'budget' => $b,
            'actual' => $a,
            'forecast' => $forecast
        ];
    }
}
?>
<style>
    .fc-card {
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(212, 175, 55, .15);
        border-radius: 22px;
        box-shadow: 0 18px 40px rgba(0, 0, 0, .30);
        color: #fff;
    }

    .fc-title {
        font-size: 2rem;
        font-weight: 800;
    }

    .fc-sub {
        color: #94a3b8;
    }

    .fc-kpi .label {
        color: #94a3b8;
        font-size: .82rem;
        text-transform: uppercase;
    }

    .fc-kpi .value {
        font-size: 1.8rem;
        font-weight: 800;
    }

    .fc-pill {
        padding: 10px 16px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, .1);
        display: inline-block;
    }

    .text-num {
        text-align: right !important;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .table-fin th,
    .table-fin td {
        color: #fff !important;
        border-color: rgba(255, 255, 255, .08) !important;
    }

    .table-fin thead th {
        color: #94a3b8 !important;
    }

    canvas {
        max-height: 320px;
    }

    .table td,
    .table th {
        background: transparent !important;
    }

    canvas {
        width: 100% !important;
        height: 320px !important;
    }

    .fc-filter-label {
        display: block;
        font-size: .78rem;
        color: #94a3b8;
        margin-bottom: 6px;
        font-weight: 600;
    }

    .fc-filter-label i {
        margin-right: 6px;
    }

    .fc-input {
        height: 44px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .12);
        color: #fff;
    }

    .fc-input:focus {
        border-color: #d4af37;
        box-shadow: 0 0 0 .2rem rgba(212, 175, 55, .15);
    }

    .fc-userbox {
        height: 44px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, .12);
        padding: 6px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: rgba(255, 255, 255, .03);
        color: #fff;
    }

    .fc-userbox i {
        font-size: 1.2rem;
        color: #94a3b8;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 44px !important;
        border-radius: 12px !important;
        background: rgba(255, 255, 255, .03) !important;
        border: 1px solid rgba(255, 255, 255, .12) !important;
        color: #fff !important;
    }

    .select2-selection__rendered {
        line-height: 42px !important;
        color: #fff !important;
    }

    .select2-selection__arrow {
        height: 44px !important;
    }
</style>
<div class="container-fluid">
    <!-- HEADER -->
    <!-- ================= FULL UPGRADED FILTER BAR ================= -->
    <div class="fc-card p-3 mb-4">
        <form id="financeFilterForm" method="get">
            <input type="hidden" name="link" value="financial_control.php">
            <div class="row g-3 align-items-end">
                <!-- PROJECT -->
                <div class="col-lg-3 col-md-6">
                    <label class="fc-filter-label">
                        <i class="bi bi-building"></i> Project
                    </label>
                    <select name="project_id"
                        id="projectFilter"
                        class="form-select fc-input select2-filter">
                        <option value="0">All Projects</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= $project_id == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['project_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- DATE RANGE -->
                <div class="col-lg-3 col-md-6">
                    <label class="fc-filter-label">
                        <i class="bi bi-calendar-range"></i> Date From
                    </label>
                    <input type="date"
                        name="date_from"
                        id="dateFrom"
                        class="form-control fc-input"
                        value="<?= $_GET['date_from'] ?? date('Y-m-01') ?>">
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="fc-filter-label">
                        <i class="bi bi-calendar-check"></i> Date To
                    </label>
                    <input type="date"
                        name="date_to"
                        id="dateTo"
                        class="form-control fc-input"
                        value="<?= $_GET['date_to'] ?? date('Y-m-t') ?>">
                </div>
                <!-- COST CODE -->
                <div class="col-lg-3 col-md-6">
                    <label class="fc-filter-label">
                        <i class="bi bi-tag"></i> Cost Code
                    </label>
                    <select name="cost_code"
                        id="costCodeFilter"
                        class="form-select fc-input select2-filter">
                        <option value="">All Cost Codes</option>
                        <option value="Materials">Materials</option>
                        <option value="Labor">Labor</option>
                        <option value="Equipment">Equipment</option>
                        <option value="Subcontractor">Subcontractor</option>
                        <option value="Overhead">Overhead</option>
                    </select>
                </div>
                <!-- USER -->
                <div class="col-lg-4 col-md-6">
                    <!-- ACTION BUTTONS -->
                    <div class="d-flex justify-content-start gap-2 mt-3 flex-wrap">
                        <button type="submit" class="btn btn-gold">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                        <button type="button" id="resetFilters" class="btn btn-dark">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-lg col-md-6">
            <div class="fc-card p-3 fc-kpi">
                <div class="label">Contract Value</div>
                <div class="value text-num">₱<?= number_format($contract, 2) ?></div>
            </div>
        </div>
        <div class="col-lg col-md-6">
            <div class="fc-card p-3 fc-kpi">
                <div class="label">Approved Budget</div>
                <div class="value text-num">₱<?= number_format($budget, 2) ?></div>
            </div>
        </div>
        <div class="col-lg col-md-6">
            <div class="fc-card p-3 fc-kpi">
                <div class="label">Actual Cost</div>
                <div class="value text-num">₱<?= number_format($actual, 2) ?></div>
            </div>
        </div>
        <div class="col-lg col-md-6">
            <div class="fc-card p-3 fc-kpi">
                <div class="label">Forecast Profit</div>
                <div class="value text-num">₱<?= number_format($profit, 2) ?></div>
            </div>
        </div>
        <div class="col-lg col-md-6">
            <div class="fc-card p-3 fc-kpi">
                <div class="label">Cash Balance</div>
                <div class="value text-num">₱<?= number_format($cash, 2) ?></div>
            </div>
        </div>
    </div>
    <!-- ROW -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="fc-card p-4">
                <h5 class="mb-3">Budget vs Actual by Cost Category</h5>
                <canvas id="barChart"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="fc-card p-4">
                <h5 class="mb-3">Auto Insights</h5>
                <p class="mb-2">Status:
                    <span class="fc-pill"><?= $status ?></span>
                </p>
                <p class="mb-2">Progress:
                    <strong><?= number_format($actualPct, 2) ?>%</strong>
                </p>
                <p class="mb-2">Budget Variance:
                    <strong class="<?= $actual > $budget ? 'text-danger' : 'text-success' ?>">
                        ₱<?= number_format($budget - $actual, 2) ?>
                    </strong>
                </p>
                <p class="mb-0">
                    Margin:
                    <strong><?= $contract > 0 ? number_format(($profit / $contract) * 100, 2) : 0 ?>%</strong>
                </p>
            </div>
        </div>
    </div>
    <!-- SECOND -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="fc-card p-4">
                <h5 class="mb-3">Cashflow Trend</h5>
                <canvas id="lineChart"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="fc-card p-4">
                <h5 class="mb-3">Completion Mix</h5>
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>
    <!-- TABLE -->
    <div class="fc-card p-4">
        <h5 class="mb-3">Multi-Project Consolidation</h5>
        <div class="table-responsive">
            <table class="table table-fin align-middle">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th class="text-num">Contract</th>
                        <th class="text-num">Budget</th>
                        <th class="text-num">Actual</th>
                        <th class="text-num">Forecast</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= esc($r['name']) ?></td>
                            <td class="text-num"><?= number_format($r['contract'], 2) ?></td>
                            <td class="text-num"><?= number_format($r['budget'], 2) ?></td>
                            <td class="text-num"><?= number_format($r['actual'], 2) ?></td>
                            <td class="text-num"><?= number_format($r['forecast'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- CASH FLOW / CASH READINESS -->
<div class="card p-4 mt-4 shadow-sm" id="budget_cost_summary">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">
                Cash Flow / Cash Readiness Template
            </h4>
            <small class="text-secondary">
                Cash exposure, funding gap and payment readiness monitoring
            </small>
        </div>
    </div>
    <?php
    /* AVAILABLE CASH */
    $availableCash = 12000000;
    /* CONTRACTOR PAYMENTS - 30 DAYS */
    $contractorPayments = $conn->query("
        SELECT IFNULL(SUM(amount),0) total
        FROM payables
        WHERE due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ")->fetch_assoc()['total'];
    /* SUPPLIER */
    $supplierPayments = $conn->query("
        SELECT IFNULL(SUM(amount),0) total
        FROM payables
        WHERE due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        AND status != 'Paid'
    ")->fetch_assoc()['total'];
    /* EQUIPMENT */
    $equipmentPayables = $conn->query("
    SELECT IFNULL(SUM(amount),0) total
    FROM payables
")->fetch_assoc()['total'];
    /* TOTAL EXPOSURE */
    $cashExposure =
        $contractorPayments +
        $supplierPayments +
        $equipmentPayables;
    /* URGENT */
    $urgentPayables = $conn->query("
        SELECT IFNULL(SUM(amount),0) total
        FROM payables
        WHERE due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND status != 'Paid'
    ")->fetch_assoc()['total'];
    /* RESERVE */
    $minimumReserve = 5000000;
    /* DAILY BURN */
    $dailyBurn = 620000;
    /* DAYS CASH */
    $daysCash =
        $dailyBurn > 0
        ? $availableCash / $dailyBurn
        : 0;
    /* FUNDING GAP */
    $fundingGap =
        (
            $cashExposure +
            $urgentPayables +
            $minimumReserve
        ) - $availableCash;
    /* DEADLINE */
    $deadline = $conn->query("
        SELECT MIN(due_date) due_date
        FROM payables
        WHERE due_date >= CURDATE()
        AND status != 'Paid'
    ")->fetch_assoc()['due_date'];
    ?>
    <div class="row">
        <div class="col-lg-7">
            <div class="table-responsive bg-white">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th width="220">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Available Cash</td>
                            <td class="fw-bold">
                                ₱<?= number_format($availableCash, 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Expected Contractor Payments - 30 Days</td>
                            <td>
                                ₱<?= number_format($contractorPayments, 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Supplier Payments Due - 30 Days</td>
                            <td>
                                ₱<?= number_format($supplierPayments, 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Equipment / Site Payables</td>
                            <td>
                                ₱<?= number_format($equipmentPayables, 2) ?>
                            </td>
                        </tr>
                        <tr class="table-warning">
                            <td class="fw-bold">
                                Total 30-Day Cash Exposure
                            </td>
                            <td class="fw-bold text-danger">
                                ₱<?= number_format($cashExposure, 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Urgent Payables</td>
                            <td>
                                ₱<?= number_format($urgentPayables, 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Minimum Reserve</td>
                            <td>
                                ₱<?= number_format($minimumReserve, 2) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Average Daily Cash Burn</td>
                            <td>
                                ₱<?= number_format($dailyBurn, 2) ?>
                            </td>
                        </tr>
                        <tr class="table-danger">
                            <td class="fw-bold">
                                Cash Required Deadline
                            </td>
                            <td class="fw-bold">
                                <?= $deadline
                                    ? date('d-M-y', strtotime($deadline))
                                    : '-' ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="border rounded p-3 mb-3">
                <h5 class="fw-bold mb-3">
                    Formula Logic
                </h5>
                <table class="table table-sm bg-white">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Formula</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                Days Cash Remaining
                            </td>
                            <td>
                                Available Cash ÷ Daily Burn
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Funding Gap
                            </td>
                            <td>
                                Cash Exposure + Urgent Payables + Minimum Reserve - Available Cash
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Cash Required Deadline
                            </td>
                            <td>
                                Earliest payable due date
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border rounded p-3">
                <h5 class="fw-bold mb-3">
                    Dashboard Output Produced
                </h5>
                <div class="mb-2">
                    <strong>
                        Days Cash Remaining:
                    </strong>
                    <?= number_format($daysCash, 1) ?> days
                </div>
                <div class="mb-2">
                    <strong>
                        Funding Gap:
                    </strong>
                    <span class="<?= $fundingGap > 0 ? 'text-danger' : 'text-success' ?>">
                        ₱<?= number_format($fundingGap, 2) ?>
                    </span>
                </div>
                <div>
                    <strong>
                        Cash Exposure:
                    </strong>
                    ₱<?= number_format($cashExposure, 2) ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof Chart === 'undefined') return;
        if (typeof $ === 'undefined') return;
        Chart.defaults.color = '#e5e7eb';
        Chart.defaults.borderColor = 'rgba(255,255,255,.08)';
        const gridColor = 'rgba(255,255,255,.06)';
        const tickColor = '#e5e7eb';
        /* PREMIUM COLORS */
        const gold = '#d4af37';
        const goldSoft = 'rgba(212,175,55,.22)';
        const blue = '#3b82f6';
        const blueSoft = 'rgba(59,130,246,.22)';
        const green = '#22c55e';
        const red = '#ef4444';
        const slate = '#94a3b8';
        const barLabels = <?= json_encode($catLabels ?: ['No Data']) ?>;
        const barBudget = <?= json_encode($catBudget ?: [0]) ?>;
        const barActual = <?= json_encode($catActual ?: [0]) ?>;
        const lineLabels = <?= json_encode($months ?: ['Jan']) ?>;
        const inflowData = <?= json_encode($inflow ?: [0]) ?>;
        const outflowData = <?= json_encode($outflow ?: [0]) ?>;
        const completed = <?= round($actualPct, 2) ?>;
        const remaining = <?= max(0, 100 - round($actualPct, 2)) ?>;
        /* ===============================
           BAR CHART
        =============================== */
        const barEl = document.getElementById('barChart');
        if (barEl) {
            new Chart(barEl, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                            label: 'Budget',
                            data: barBudget,
                            backgroundColor: goldSoft,
                            borderColor: gold,
                            borderWidth: 2,
                            borderRadius: 8
                        },
                        {
                            label: 'Actual',
                            data: barActual,
                            backgroundColor: blueSoft,
                            borderColor: blue,
                            borderWidth: 2,
                            borderRadius: 8
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: tickColor
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: tickColor
                            },
                            grid: {
                                color: gridColor
                            }
                        },
                        y: {
                            ticks: {
                                color: tickColor,
                                callback: function(value) {
                                    return '₱' + Number(value).toLocaleString();
                                }
                            },
                            grid: {
                                color: gridColor
                            }
                        }
                    }
                }
            });
        }
        /* ===============================
           LINE CHART
        =============================== */
        const lineEl = document.getElementById('lineChart');
        if (lineEl) {
            new Chart(lineEl, {
                type: 'line',
                data: {
                    labels: lineLabels,
                    datasets: [{
                            label: 'Inflow',
                            data: inflowData,
                            borderColor: green,
                            backgroundColor: green,
                            tension: .4,
                            borderWidth: 3,
                            pointRadius: 3
                        },
                        {
                            label: 'Outflow',
                            data: outflowData,
                            borderColor: red,
                            backgroundColor: red,
                            tension: .4,
                            borderWidth: 3,
                            pointRadius: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: {
                                color: tickColor
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: tickColor
                            },
                            grid: {
                                color: gridColor
                            }
                        },
                        y: {
                            ticks: {
                                color: tickColor,
                                callback: function(value) {
                                    return '₱' + Number(value).toLocaleString();
                                }
                            },
                            grid: {
                                color: gridColor
                            }
                        }
                    }
                }
            });
        }
        /* ===============================
           DOUGHNUT CHART
        =============================== */
        const pieEl = document.getElementById('pieChart');
        if (pieEl) {
            new Chart(pieEl, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Remaining'],
                    datasets: [{
                        data: [completed, remaining],
                        backgroundColor: [gold, slate],
                        borderWidth: 0,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: tickColor
                            }
                        }
                    }
                }
            });
        }
        /* ===============================
           SELECT2 FILTERS
        =============================== */
        if ($('.select2-filter').length) {
            $('.select2-filter').select2({
                width: '100%',
                minimumResultsForSearch: 0
            });
        }
        /* ===============================
           AUTO SUBMIT FILTERS
        =============================== */
        $('#projectFilter, #costCodeFilter, #dateFrom, #dateTo').on('change', function() {
            $('#financeFilterForm').submit();
        });
        /* ===============================
           RESET FILTERS
        =============================== */
        $('#resetFilters').on('click', function() {
            window.location = '?link=financial_control.php';
        });
    });
</script>