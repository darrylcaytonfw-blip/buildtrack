<?php
/* reports.php */
include 'database.php';

/* =========================
   SUMMARY DATA
========================= */
$totalProjects = $conn->query("
    SELECT COUNT(*) total FROM projects
")->fetch_assoc()['total'];

$totalActivities = $conn->query("
    SELECT COUNT(*) total FROM activities
")->fetch_assoc()['total'];

$avgProgress = $conn->query("
    SELECT IFNULL(AVG(progress),0) total
    FROM activities
")->fetch_assoc()['total'];

$totalBillings = $conn->query("
    SELECT IFNULL(SUM(amount),0) total
    FROM billings
")->fetch_assoc()['total'];

$totalPaid = $conn->query("
    SELECT IFNULL(SUM(amount),0) total
    FROM billings
    WHERE status='Paid'
")->fetch_assoc()['total'];

$totalPending = $conn->query("
    SELECT IFNULL(SUM(amount),0) total
    FROM billings
    WHERE status='Pending'
")->fetch_assoc()['total'];

$retentionHeld = $conn->query("
    SELECT IFNULL(SUM(retention_amount),0) total
    FROM retentions
    WHERE status IN ('Held','Partial')
")->fetch_assoc()['total'];

$totalOrdered = $conn->query("
    SELECT IFNULL(SUM(ordered_qty),0) total
    FROM deliveries
")->fetch_assoc()['total'];

$totalDelivered = $conn->query("
    SELECT IFNULL(SUM(delivered_qty),0) total
    FROM deliveries
")->fetch_assoc()['total'];

$deliveryCompletion = ($totalOrdered > 0)
    ? ($totalDelivered / $totalOrdered) * 100
    : 0;

/* =========================
   CONTRACTOR PERFORMANCE
========================= */
$contractorPerf = $conn->query("
    SELECT c.name,
           c.contractor_type,
           COUNT(a.id) total_tasks,
           IFNULL(AVG(a.progress),0) avg_progress
    FROM contractors c
    LEFT JOIN activities a ON c.id = a.contractor_id
    GROUP BY c.id
    ORDER BY avg_progress DESC
");

/* =========================
   PROJECT BILLINGS
========================= */
$projectBillings = $conn->query("
    SELECT p.project_name,
           IFNULL(SUM(b.amount),0) total_amount
    FROM projects p
    LEFT JOIN billings b ON p.id = b.project_id
    GROUP BY p.id
    ORDER BY total_amount DESC
");

/* =========================
   EXPORT CSV
========================= */
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=buildtrack_report.csv");

    echo "Metric,Value\n";
    echo "Projects,$totalProjects\n";
    echo "Activities,$totalActivities\n";
    echo "Average Progress," . number_format($avgProgress, 2) . "%\n";
    echo "Total Billings,$totalBillings\n";
    echo "Total Paid,$totalPaid\n";
    echo "Pending,$totalPending\n";
    echo "Retention Held,$retentionHeld\n";
    echo "Delivery Completion," . number_format($deliveryCompletion, 2) . "%\n";
    exit;
}
?>
<style>
    .btn-outline-light:hover {
        background: linear-gradient(90deg, var(--gold), var(--gold-soft));
        font-weight: 700;
        color: var(--navy-dark);
        border: none;
        border-radius: 12px;
        padding: 10px 10px;
        transition: all .25s ease;
    }
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">

    <div>
        <h2 class="text-white fw-bold mb-1">Reports</h2>
        <small class="text-secondary">
            Executive construction summary and financial analytics
        </small>
    </div>

    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-light">
            <i class="bi bi-printer"></i> Print
        </button>

        <a href="./?link=reports.php&export=csv" class="btn btn-gold">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>

</div>

<!-- KPI CARDS -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Projects</small>
            <h3><?= $totalProjects ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Activities</small>
            <h3><?= $totalActivities ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Avg Progress</small>
            <h3><?= number_format($avgProgress, 1) ?>%</h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Delivery Completion</small>
            <h3><?= number_format($deliveryCompletion, 1) ?>%</h3>
        </div>
    </div>

</div>

<!-- FINANCE -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Total Billings</small>
            <h4>₱<?= number_format($totalBillings, 2) ?></h4>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Paid</small>
            <h4 class="text-success">₱<?= number_format($totalPaid, 2) ?></h4>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Pending</small>
            <h4 class="text-warning">₱<?= number_format($totalPending, 2) ?></h4>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Retention Held</small>
            <h4 class="text-danger">₱<?= number_format($retentionHeld, 2) ?></h4>
        </div>
    </div>

</div>

<!-- MAIN GRID -->
<div class="row g-4">

    <!-- CONTRACTOR PERFORMANCE -->
    <div class="col-lg-6">
        <div class="card p-4 h-100">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>Contractor Performance</strong>
                <i class="bi bi-people text-warning"></i>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Tasks</th>
                            <th width="180">Progress</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($r = $contractorPerf->fetch_assoc()): ?>
                            <tr>
                                <td><?= $r['name'] ?></td>
                                <td><?= $r['contractor_type'] ?></td>
                                <td><?= $r['total_tasks'] ?></td>

                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1"
                                            style="height:8px;background:rgba(255,255,255,.08)">
                                            <div class="progress-bar bg-warning"
                                                style="width:<?= $r['avg_progress'] ?>%">
                                            </div>
                                        </div>
                                        <small><?= number_format($r['avg_progress'], 0) ?>%</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

    <!-- BILLINGS PER PROJECT -->
    <div class="col-lg-6">
        <div class="card p-4 h-100">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <strong>Project Billings</strong>
                <i class="bi bi-cash-stack text-success"></i>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Total Billing</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($r = $projectBillings->fetch_assoc()): ?>
                            <tr>
                                <td><?= $r['project_name'] ?></td>
                                <td>₱<?= number_format($r['total_amount'], 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<!-- PROGRESS BAR -->
<div class="card p-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Overall Project Completion</strong>
        <span><?= number_format($avgProgress, 1) ?>%</span>
    </div>

    <div class="progress"
        style="height:14px;background:rgba(255,255,255,.08)">
        <div class="progress-bar bg-success"
            style="width:<?= min(100, $avgProgress) ?>%">
        </div>
    </div>
</div><!-- REPORT TABS -->
<div class="card p-4 my-4">

    <div class="row g-3">

        <div class="col-md-6">
            <a href="./?link=schedule.php"
                class="btn btn-outline-light w-100 py-3 fw-bold fs-6">
                Work Schedule Monitoring
            </a>
        </div>

        <div class="col-md-6">
            <a href="./?link=contractors.php"
                class="btn btn-outline-light w-100 py-3 fw-bold fs-6">
                Contractor Summary Report
            </a>
        </div>

        <div class="col-md-4">
            <a href="./?link=financial_control.php"
                class="btn btn-outline-light w-100 py-3 fw-bold fs-6">
                Budget and Expenses
            </a>
        </div>

        <div class="col-md-4">
            <a href="./?link=billings.php"
                class="btn btn-outline-light w-100 py-3 fw-bold fs-6">
                Billing
            </a>
        </div>

        <div class="col-md-4">
            <a href="./?link=payables.php"
                class="btn btn-outline-light w-100 py-3 fw-bold fs-6">
                Payables
            </a>
        </div>

        <div class="col-md-12">
            <a href="./?link=deliveries.php"
                class="btn btn-outline-light w-100 py-3 fw-bold fs-6">
                Deliveries
            </a>
        </div>

    </div>

</div>