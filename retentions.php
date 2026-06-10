<?php
/* upgraded retentions.php */
include 'database.php';

/* ---------- AJAX BILLING LOOKUP ---------- */
if (isset($_GET['ajax']) && $_GET['ajax'] == 'billing') {
    $id = (int)$_GET['id'];

    $row = $conn->query("
        SELECT amount
        FROM billings
        WHERE id = $id
        LIMIT 1
    ")->fetch_assoc();

    $amount = $row ? (float)$row['amount'] : 0;
    $retention = $amount * 0.10;

    header('Content-Type: application/json');
    echo json_encode([
        'amount' => $amount,
        'retention' => $retention
    ]);
    exit;
}

/* ---------- CREATE ---------- */
if (isset($_POST['save'])) {

    $billing_id        = (int)$_POST['billing_id'];
    $retention_percent = (float)$_POST['retention_percent'];
    $retention_amount  = (float)$_POST['retention_amount'];
    $release_type      = $conn->real_escape_string(trim($_POST['release_type']));
    $release_date      = $_POST['release_date'];
    $status            = $conn->real_escape_string(trim($_POST['status']));

    $exists = $conn->query("
        SELECT id FROM retentions
        WHERE billing_id = $billing_id
        LIMIT 1
    ");

    if ($exists->num_rows > 0) {
        header("Location: ./?link=retentions.php&duplicate=1");
        exit;
    }

    $conn->query("
        INSERT INTO retentions(
            billing_id,
            retention_percent,
            retention_amount,
            release_type,
            release_date,
            status
        )
        VALUES(
            $billing_id,
            '$retention_percent',
            '$retention_amount',
            '$release_type',
            '$release_date',
            '$status'
        )
    ");

    header("Location: ./?link=retentions.php&added=1");
    exit;
}

/* ---------- UPDATE ---------- */
if (isset($_POST['update'])) {

    $id                = (int)$_POST['id'];
    $billing_id        = (int)$_POST['billing_id'];
    $retention_percent = (float)$_POST['retention_percent'];
    $retention_amount  = (float)$_POST['retention_amount'];
    $release_type      = $conn->real_escape_string(trim($_POST['release_type']));
    $release_date      = $_POST['release_date'];
    $status            = $conn->real_escape_string(trim($_POST['status']));

    $conn->query("
        UPDATE retentions SET
            billing_id        = $billing_id,
            retention_percent = '$retention_percent',
            retention_amount  = '$retention_amount',
            release_type      = '$release_type',
            release_date      = '$release_date',
            status            = '$status'
        WHERE id = $id
    ");

    header("Location: ./?link=retentions.php&updated=1");
    exit;
}

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $conn->query("DELETE FROM retentions WHERE id=$id");

    header("Location: ./?link=retentions.php&deleted=1");
    exit;
}

/* ---------- DATA ---------- */
$billings = $conn->query("
    SELECT b.id,
           b.billing_stage,
           b.amount,
           p.project_name,
           c.name contractor_name
    FROM billings b
    LEFT JOIN projects p ON b.project_id = p.id
    LEFT JOIN contractors c ON b.contractor_id = c.id
    ORDER BY b.id DESC
");

$totalRetention = $conn->query("
    SELECT IFNULL(SUM(retention_amount),0) total
    FROM retentions
")->fetch_assoc()['total'];

$released = $conn->query("
    SELECT IFNULL(SUM(retention_amount),0) total
    FROM retentions
    WHERE status='Released'
")->fetch_assoc()['total'];

$held = $conn->query("
    SELECT IFNULL(SUM(retention_amount),0) total
    FROM retentions
    WHERE status IN ('Held','Partial')
")->fetch_assoc()['total'];

$dueSoon = $conn->query("
    SELECT COUNT(*) total
    FROM retentions
    WHERE status IN ('Held','Partial')
      AND release_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetch_assoc()['total'];

$list = $conn->query("
    SELECT r.*,
           b.billing_stage,
           b.amount billing_amount,
           p.project_name,
           c.name contractor_name
    FROM retentions r
    LEFT JOIN billings b ON r.billing_id = b.id
    LEFT JOIN projects p ON b.project_id = p.id
    LEFT JOIN contractors c ON b.contractor_id = c.id
    ORDER BY r.id DESC
");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h2 class="text-white fw-bold mb-1">Retentions</h2>
        <small class="text-secondary">
            Smart retention release and warranty hold tracking
        </small>
    </div>

    <input type="text"
        class="form-control search-box"
        style="max-width:280px"
        placeholder="Search retention..."
        onkeyup="filterRetentions(this.value)">
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success py-2">Retention saved successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-info py-2">Retention updated successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger py-2">Retention deleted successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['duplicate'])): ?>
    <div class="alert alert-warning py-2">Retention already exists for this billing.</div>
<?php endif; ?>

<?php if ($dueSoon > 0): ?>
    <div class="alert alert-warning py-2">
        <i class="bi bi-bell"></i>
        <?= $dueSoon ?> retention release(s) due within 30 days.
    </div>
<?php endif; ?>

<!-- STATS -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Total Retention</small>
            <h3>₱<?= number_format($totalRetention, 2) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Held</small>
            <h3 class="text-warning">₱<?= number_format($held, 2) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Released</small>
            <h3 class="text-success">₱<?= number_format($released, 2) ?></h3>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-4 text-center">
            <small class="text-secondary">Due Soon</small>
            <h3><?= $dueSoon ?></h3>
        </div>
    </div>

</div>
<?php if (!$isManagement): ?>

    <!-- FORM -->
    <div class="card p-4 mb-4">
        <form method="post" class="row g-3 align-items-end">

            <div class="col-md-3">
                <label class="small text-secondary mb-1">Billing Reference</label>
                <select name="billing_id"
                    id="billing_id"
                    class="form-select select2"
                    onchange="loadBilling()"
                    required>
                    <option value="">Select</option>
                    <?php while ($b = $billings->fetch_assoc()): ?>
                        <option value="<?= $b['id'] ?>">
                            #<?= $b['id'] ?> |
                            <?= $b['project_name'] ?> |
                            <?= $b['contractor_name'] ?> |
                            <?= $b['billing_stage'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="small text-secondary mb-1">Billing Amount</label>
                <input type="text"
                    id="billing_amount"
                    class="form-control"
                    readonly>
            </div>

            <div class="col-md-1">
                <label class="small text-secondary mb-1">%</label>
                <input type="number"
                    name="retention_percent"
                    id="retention_percent"
                    class="form-control"
                    value="10"
                    oninput="recalcRetention()">
            </div>

            <div class="col-md-2">
                <label class="small text-secondary mb-1">Retention</label>
                <input type="number"
                    step="0.01"
                    name="retention_amount"
                    id="retention_amount"
                    class="form-control"
                    required>
            </div>

            <div class="col-md-2">
                <label class="small text-secondary mb-1">Release Type</label>
                <select name="release_type" class="form-select">
                    <option>Gradual</option>
                    <option>Upon Completion</option>
                    <option>After 1 Year Warranty</option>
                </select>
            </div>

            <div class="col-md-1">
                <label class="small text-secondary mb-1">Date</label>
                <input type="date"
                    name="release_date"
                    class="form-control"
                    required>
            </div>

            <div class="col-md-1">
                <label class="small text-secondary mb-1">Status</label>
                <select name="status" class="form-select select2">
                    <option>Held</option>
                    <option>Partial</option>
                    <option>Released</option>
                </select>
            </div>

            <div class="col-md-1">
                <button name="save" class="btn btn-gold w-100">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>

        </form>
    </div>
<?php endif; ?>
<!-- TABLE -->
<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="retentionTable">

            <thead>
                <tr>
                    <th>Project</th>
                    <th>Contractor</th>
                    <th>Billing</th>
                    <th>Retention</th>
                    <th>%</th>
                    <th>Release Type</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th width="140">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($r = $list->fetch_assoc()): ?>
                    <tr>

                        <td><?= $r['project_name'] ?></td>

                        <td><?= $r['contractor_name'] ?></td>

                        <td>
                            <?= $r['billing_stage'] ?><br>
                            <small class="text-secondary">
                                ₱<?= number_format($r['billing_amount'], 2) ?>
                            </small>
                        </td>

                        <td class="text-warning">
                            ₱<?= number_format($r['retention_amount'], 2) ?>
                        </td>

                        <td><?= $r['retention_percent'] ?>%</td>

                        <td><?= $r['release_type'] ?></td>

                        <td><?= $r['release_date'] ?></td>

                        <td>
                            <?php if ($r['status'] == 'Released'): ?>
                                <span class="badge bg-success">Released</span>
                            <?php elseif ($r['status'] == 'Partial'): ?>
                                <span class="badge bg-info">Partial</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Held</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if (!$isManagement): ?>

                                <a href="./?link=retentions.php&delete=<?= $r['id'] ?>"
                                    class="btn btn-sm btn-danger"
                                    onclick="return confirm('Delete retention?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            <?php endif; ?>

                        </td>

                    </tr>
                <?php endwhile; ?>
            </tbody>

        </table>
    </div>
</div>

<script>
    let currentBillingAmount = 0;

    function filterRetentions(keyword) {
        keyword = keyword.toLowerCase();

        document.querySelectorAll("#retentionTable tbody tr").forEach(row => {
            row.style.display =
                row.innerText.toLowerCase().includes(keyword) ?
                "" :
                "none";
        });
    }

    function loadBilling() {
        let id = document.getElementById('billing_id').value;
        if (!id) return;

        fetch('./?link=retentions.php&ajax=billing&id=' + id)
            .then(res => res.json())
            .then(data => {
                currentBillingAmount = parseFloat(data.amount) || 0;
                document.getElementById('billing_amount').value =
                    currentBillingAmount.toFixed(2);

                document.getElementById('retention_amount').value =
                    parseFloat(data.retention).toFixed(2);
            });
    }

    function recalcRetention() {
        let percent = parseFloat(
            document.getElementById('retention_percent').value
        ) || 0;

        let value = (currentBillingAmount * percent) / 100;

        document.getElementById('retention_amount').value =
            value.toFixed(2);
    }
</script>