<?php
/* dashboard.php - FULL UPDATED CONNECTED TO work_setup.php */
include 'database.php';

/* =========================================
HELPER
========================================= */
function total($conn, $sql)
{
    $res = @$conn->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
        return (float)$row['total'];
    }
    return 0;
}

/* =========================================
CORE KPI
========================================= */
$totalProjects = total($conn, "SELECT COUNT(*) total FROM projects");
$totalCategories = total($conn, "SELECT COUNT(*) total FROM work_categories");
$totalWorkItems = total($conn, "SELECT COUNT(*) total FROM work_items");

$totalEstimate = total($conn, "
    SELECT IFNULL(SUM(estimated_price),0) total
    FROM work_items
");

$totalActualScope = total($conn, "
    SELECT IFNULL(SUM(actual_price),0) total
    FROM work_items
");

$totalBillings = total($conn, "
    SELECT IFNULL(SUM(amount),0) total
    FROM billings
");

$totalPaid = total($conn, "
    SELECT IFNULL(SUM(amount),0) total
    FROM billings
    WHERE status='Paid'
");

$pendingBillings = total($conn, "
    SELECT COUNT(*) total
    FROM billings
    WHERE status='Pending'
");

$retentionHeld = total($conn, "
    SELECT IFNULL(SUM(retention_amount),0) total
    FROM retentions
    WHERE status IN('Held','Partial')
");

$payablesPaid = total($conn, "
    SELECT IFNULL(SUM(paid_amount),0) total
    FROM payables
");

$forecastProfit = $totalBillings - $payablesPaid;
$cashBalance = $totalPaid - $payablesPaid;

$billingCoverage = $totalEstimate > 0
    ? ($totalBillings / $totalEstimate) * 100
    : 0;

/* =========================================
WORK PROGRESS
========================================= */
$progressAvg = total($conn, "
SELECT IFNULL(AVG(progress),0) total
FROM activities
");

$lowProgress = total($conn, "
SELECT COUNT(*) total
FROM activities
WHERE progress < 50
");

/* =========================================
DELIVERIES
========================================= */
$totalOrdered = total($conn, "
SELECT IFNULL(SUM(ordered_qty),0) total
FROM deliveries
");

$totalDelivered = total($conn, "
SELECT IFNULL(SUM(delivered_qty),0) total
FROM deliveries
");

$deliveryRate = $totalOrdered > 0
    ? ($totalDelivered / $totalOrdered) * 100
    : 0;

/* =========================================
RECENT DATA
========================================= */
$recentBillings = @$conn->query("
SELECT b.amount,b.billing_stage,b.status,p.project_name
FROM billings b
LEFT JOIN projects p ON b.project_id=p.id
ORDER BY b.id DESC
LIMIT 5
");

$recentItems = @$conn->query("
SELECT wi.item_no, wi.item_name,
       wc.category_name, wa.activity_name
FROM work_items wi
LEFT JOIN work_activities wa ON wi.activity_id=wa.id
LEFT JOIN work_categories wc ON wa.category_id=wc.id
ORDER BY wi.id DESC
LIMIT 5
");

$recentDeliveries = @$conn->query("
SELECT item_name, ordered_qty, delivered_qty
FROM deliveries
ORDER BY id DESC
LIMIT 5
");
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold text-white mb-1">Dashboard</h2>
        <small class="text-secondary">Live Work + Finance + Procurement Intelligence</small>
    </div>

    <span class="badge bg-success px-3 py-2">
        <i class="bi bi-circle-fill small me-1"></i> Live System
    </span>
</div>

<!-- ALERTS -->
<?php if ($pendingBillings > 0): ?>
    <div class="alert alert-warning py-2">
        <?= $pendingBillings ?> pending billing(s) need attention.
    </div>
<?php endif; ?>

<?php if ($lowProgress > 0): ?>
    <div class="alert alert-danger py-2">
        <?= $lowProgress ?> activity(ies) below 50% progress.
    </div>
<?php endif; ?>
<!-- TOP KPI -->
<div class="row g-4 mb-4">

    <div class="col-lg col-md-6">
        <div class="card p-4 h-100 text-center">
            <small class="text-secondary">Projects</small>
            <h3><?= $totalProjects ?></h3>
        </div>
    </div>

    <div class="col-lg col-md-6">
        <div class="card p-4 h-100 text-center">
            <small class="text-secondary">Work Categories</small>
            <h3><?= $totalCategories ?></h3>
        </div>
    </div>

    <div class="col-lg col-md-6">
        <div class="card p-4 h-100 text-center">
            <small class="text-secondary">Work Items</small>
            <h3><?= $totalWorkItems ?></h3>
        </div>
    </div>

    <div class="col-lg col-md-6">
        <div class="card p-4 h-100 text-center">
            <small class="text-secondary">Avg Progress</small>
            <h3><?= number_format($progressAvg, 0) ?>%</h3>
        </div>
    </div>

</div>

<!-- FINANCIAL KPI -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Estimated Scope</small>
            <h3>₱<?= number_format($totalEstimate, 0) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Billings</small>
            <h3>₱<?= number_format($totalBillings, 0) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Paid</small>
            <h3 class="text-success">₱<?= number_format($totalPaid, 0) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Cash Balance</small>
            <h3>₱<?= number_format($cashBalance, 0) ?></h3>
        </div>
    </div>

</div>


<!-- SECOND ROW -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Retention Held</small>
            <h3>₱<?= number_format($retentionHeld, 0) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Payables Paid</small>
            <h3>₱<?= number_format($payablesPaid, 0) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Forecast Profit</small>
            <h3>₱<?= number_format($forecastProfit, 0) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Billing Coverage</small>
            <h3><?= number_format($billingCoverage, 0) ?></h3>
        </div>
    </div>

</div>

<div class="row g-4">

    <!-- LEFT -->
    <div class="col-lg-8">

        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between mb-3">
                <strong>Recent Scope Items</strong>
                <i class="bi bi-list-check text-warning"></i>
            </div>

            <?php if ($recentItems): ?>
                <?php while ($r = $recentItems->fetch_assoc()): ?>
                    <div class="border-bottom border-secondary py-2">

                        <div class="fw-semibold">
                            <?= $r['item_no'] ?> <?= $r['activity_name'] ?> - <?= $r['item_name'] ?>
                        </div>

                        <small class="text-secondary">
                            <?= $r['category_name'] ?>
                        </small>

                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between mb-3">
                <strong>Delivery Monitoring</strong>
                <i class="bi bi-truck text-success"></i>
            </div>

            <?php if ($recentDeliveries): ?>
                <?php while ($d = $recentDeliveries->fetch_assoc()): ?>
                    <?php
                    $percent = ($d['ordered_qty'] > 0)
                        ? ($d['delivered_qty'] / $d['ordered_qty']) * 100
                        : 0;
                    ?>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span><?= $d['item_name'] ?></span>
                            <small><?= number_format($percent, 0) ?>%</small>
                        </div>

                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-success"
                                style="width:<?= min(100, $percent) ?>%">
                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php endif; ?>

        </div>

    </div>

    <!-- RIGHT -->
    <div class="col-lg-4">

        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between mb-3">
                <strong>Latest Billings</strong>
                <i class="bi bi-cash-stack text-warning"></i>
            </div>

            <?php if ($recentBillings): ?>
                <?php while ($b = $recentBillings->fetch_assoc()): ?>
                    <div class="border-bottom border-secondary py-2">

                        <div class="fw-semibold"><?= $b['billing_stage'] ?></div>
                        <small class="text-secondary"><?= $b['project_name'] ?></small>

                        <div class="d-flex justify-content-between mt-1">
                            <span>₱<?= number_format($b['amount'], 2) ?></span>

                            <?php if ($b['status'] == 'Paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php elseif ($b['status'] == 'Pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-danger"><?= $b['status'] ?></span>
                            <?php endif; ?>

                        </div>

                    </div>
                <?php endwhile; ?>
            <?php endif; ?>

        </div>

        <div class="card p-4">
            <strong class="mb-3 d-block">Quick Actions</strong>

            <div class="d-grid gap-2">
                <a href="./?link=projects.php" class="btn btn-gold">Projects</a>
                <a href="./?link=work_setup.php" class="btn btn-outline-light">Work Setup</a>
                <a href="./?link=billings.php" class="btn btn-outline-light">Billings</a>
                <a href="./?link=deliveries.php" class="btn btn-outline-light">Deliveries</a>
                <a href="./?link=retentions.php" class="btn btn-outline-light">Retentions</a>
                <a href="./?link=reports.php" class="btn btn-outline-light">Reports</a>
            </div>
        </div>

    </div>

</div>