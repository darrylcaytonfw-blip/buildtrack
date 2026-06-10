<?php
/* audit_logs.php */


if (!isset($_SESSION['role']) || $_SESSION['role'] != 'system_admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    exit;
}

/* ---------- CLEAR LOGS ---------- */
if (isset($_GET['clear'])) {
    $conn->query("TRUNCATE TABLE audit_logs");
    header("Location: ./?link=audit_logs.php&cleared=1");
    exit;
}

/* ---------- DATA ---------- */
$list = $conn->query("
    SELECT *
    FROM audit_logs
    ORDER BY id DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-white fw-bold mb-1">Audit Logs</h2>
        <small class="text-secondary">Track all user actions in the system</small>
    </div>

    <div class="d-flex gap-2">
        <input type="text"
               class="form-control"
               style="width:250px"
               placeholder="Search logs..."
               onkeyup="filterLogs(this.value)">

        <a href="./?link=audit_logs.php&clear=1"
           onclick="return confirm('Clear all logs?')"
           class="btn btn-danger">
           <i class="bi bi-trash"></i>
        </a>
    </div>
</div>

<?php if(isset($_GET['cleared'])): ?>
<div class="alert alert-danger py-2">
    Audit logs cleared.
</div>
<?php endif; ?>

<!-- SUMMARY -->
<?php
$totalLogs = $conn->query("
    SELECT COUNT(*) total FROM audit_logs
")->fetch_assoc()['total'];

$todayLogs = $conn->query("
    SELECT COUNT(*) total
    FROM audit_logs
    WHERE DATE(log_time)=CURDATE()
")->fetch_assoc()['total'];

$usersActive = $conn->query("
    SELECT COUNT(DISTINCT user_id) total
    FROM audit_logs
")->fetch_assoc()['total'];
?>

<div class="row g-4 mb-4">

<div class="col-md-4">
<div class="card p-4 text-center">
<small class="text-secondary">Total Logs</small>
<h3><?= $totalLogs ?></h3>
</div>
</div>

<div class="col-md-4">
<div class="card p-4 text-center">
<small class="text-secondary">Today</small>
<h3><?= $todayLogs ?></h3>
</div>
</div>

<div class="col-md-4">
<div class="card p-4 text-center">
<small class="text-secondary">Active Users</small>
<h3><?= $usersActive ?></h3>
</div>
</div>

</div>

<!-- TABLE -->
<div class="card p-3">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" id="logsTable">

<thead>
<tr>
<th>ID</th>
<th>User</th>
<th>Action</th>
<th>Module</th>
<th>Record</th>
<th>Details</th>
<th>Date</th>
</tr>
</thead>

<tbody>
<?php while($r = $list->fetch_assoc()): ?>
<tr>

<td><?= $r['id'] ?></td>

<td>
<?= $r['username'] ?><br>
<small class="text-secondary">#<?= $r['user_id'] ?></small>
</td>

<td>
<span class="badge bg-primary">
<?= $r['action'] ?>
</span>
</td>

<td><?= $r['module_name'] ?></td>

<td>#<?= $r['record_id'] ?></td>

<td><?= $r['details'] ?></td>

<td>
<?= date('M d, Y', strtotime($r['log_time'])) ?><br>
<small class="text-secondary">
<?= date('h:i A', strtotime($r['log_time'])) ?>
</small>
</td>

</tr>
<?php endwhile; ?>
</tbody>

</table>
</div>
</div>

<script>
function filterLogs(keyword){
    keyword = keyword.toLowerCase();

    document.querySelectorAll("#logsTable tbody tr").forEach(row=>{
        row.style.display =
            row.innerText.toLowerCase().includes(keyword)
            ? ""
            : "none";
    });
}
</script>