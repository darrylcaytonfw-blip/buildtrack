<?php
/* charts.php - FIXED HEIGHT VERSION */
include 'database.php';

/* =========================
   CHART 1 - ACTIVITY PROGRESS
========================= */
$progressData = $conn->query("
    SELECT item_no, progress
    FROM activities
    ORDER BY id DESC
    LIMIT 10
");

$labels1 = [];
$data1   = [];

while($r = $progressData->fetch_assoc()){
    $labels1[] = $r['item_no'];
    $data1[]   = (float)$r['progress'];
}

/* =========================
   CHART 2 - BILLINGS BY PROJECT
========================= */
$billingData = $conn->query("
    SELECT p.project_name,
           IFNULL(SUM(b.amount),0) total
    FROM projects p
    LEFT JOIN billings b ON p.id = b.project_id
    GROUP BY p.id
    ORDER BY total DESC
");

$labels2 = [];
$data2   = [];

while($r = $billingData->fetch_assoc()){
    $labels2[] = $r['project_name'];
    $data2[]   = (float)$r['total'];
}

/* =========================
   CHART 3 - DELIVERY %
========================= */
$deliveryData = $conn->query("
    SELECT item_name, ordered_qty, delivered_qty
    FROM deliveries
    ORDER BY id DESC
    LIMIT 10
");

$labels3 = [];
$data3   = [];

while($r = $deliveryData->fetch_assoc()){

    $percent = ($r['ordered_qty'] > 0)
        ? ($r['delivered_qty'] / $r['ordered_qty']) * 100
        : 0;

    $labels3[] = $r['item_name'];
    $data3[]   = round($percent,2);
}

/* =========================
   CHART 4 - RETENTION STATUS
========================= */
$held = $conn->query("
    SELECT IFNULL(SUM(retention_amount),0) total
    FROM retentions
    WHERE status IN ('Held','Partial')
")->fetch_assoc()['total'];

$released = $conn->query("
    SELECT IFNULL(SUM(retention_amount),0) total
    FROM retentions
    WHERE status='Released'
")->fetch_assoc()['total'];

/* =========================
   CHART 5 - CONTRACTOR PERFORMANCE
========================= */
$contractorData = $conn->query("
    SELECT c.name,
           IFNULL(AVG(a.progress),0) avg_progress
    FROM contractors c
    LEFT JOIN activities a ON c.id = a.contractor_id
    GROUP BY c.id
    ORDER BY avg_progress DESC
");

$labels5 = [];
$data5   = [];

while($r = $contractorData->fetch_assoc()){
    $labels5[] = $r['name'];
    $data5[]   = round($r['avg_progress'],2);
}
?>

<style>
.chart-box{
    position:relative;
    height:320px;
    width:100%;
}
.chart-box.sm{
    height:260px;
}
.chart-box.lg{
    height:420px;
}
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="fw-bold text-white mb-1">Charts & Analytics</h2>
        <small class="text-secondary">
            Real-time visual performance dashboard
        </small>
    </div>
</div>

<div class="row g-4">

    <!-- CHART 1 -->
    <div class="col-lg-6">
        <div class="card p-4">
            <h5 class="mb-3">Activity Progress</h5>
            <div class="chart-box">
                <canvas id="chart1"></canvas>
            </div>
        </div>
    </div>

    <!-- CHART 2 -->
    <div class="col-lg-6">
        <div class="card p-4">
            <h5 class="mb-3">Billings by Project</h5>
            <div class="chart-box">
                <canvas id="chart2"></canvas>
            </div>
        </div>
    </div>

    <!-- CHART 3 -->
    <div class="col-lg-6">
        <div class="card p-4">
            <h5 class="mb-3">Delivery Completion %</h5>
            <div class="chart-box">
                <canvas id="chart3"></canvas>
            </div>
        </div>
    </div>

    <!-- CHART 4 -->
    <div class="col-lg-6">
        <div class="card p-4">
            <h5 class="mb-3">Retention Status</h5>
            <div class="chart-box sm">
                <canvas id="chart4"></canvas>
            </div>
        </div>
    </div>

    <!-- CHART 5 -->
    <div class="col-12">
        <div class="card p-4">
            <h5 class="mb-3">Contractor Performance</h5>
            <div class="chart-box lg">
                <canvas id="chart5"></canvas>
            </div>
        </div>
    </div>

</div>

<script>
const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { color: '#ffffff' }
        }
    },
    scales: {
        x: {
            ticks: { color: '#ffffff' },
            grid: { color: 'rgba(255,255,255,.05)' }
        },
        y: {
            beginAtZero: true,
            ticks: { color: '#ffffff' },
            grid: { color: 'rgba(255,255,255,.05)' }
        }
    }
};

/* CHART 1 */
new Chart(document.getElementById('chart1'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels1) ?>,
        datasets: [{
            label: 'Progress %',
            data: <?= json_encode($data1) ?>,
            borderWidth: 1
        }]
    },
    options: commonOptions
});

/* CHART 2 */
new Chart(document.getElementById('chart2'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels2) ?>,
        datasets: [{
            label: 'Billing Amount',
            data: <?= json_encode($data2) ?>,
            borderWidth: 1
        }]
    },
    options: commonOptions
});

/* CHART 3 */
new Chart(document.getElementById('chart3'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels3) ?>,
        datasets: [{
            label: 'Delivery %',
            data: <?= json_encode($data3) ?>,
            tension: .4,
            fill: false
        }]
    },
    options: commonOptions
});

/* CHART 4 */
new Chart(document.getElementById('chart4'), {
    type: 'doughnut',
    data: {
        labels: ['Held','Released'],
        datasets: [{
            data: [<?= $held ?>, <?= $released ?>]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color:'#ffffff' }
            }
        }
    }
});

/* CHART 5 */
new Chart(document.getElementById('chart5'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels5) ?>,
        datasets: [{
            label: 'Avg Progress %',
            data: <?= json_encode($data5) ?>,
            borderWidth: 1
        }]
    },
    options: commonOptions
});
</script>