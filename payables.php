<?php
/* =====================================================
   PAYABLES.PHP - FULL UPGRADED PRODUCTION VERSION
===================================================== */
include 'audit_helper.php';
/* =====================================================
   AUTO OVERDUE UPDATE
===================================================== */
$conn->query("
    UPDATE payables
    SET status='Overdue'
    WHERE status IN ('Pending','Partial')
      AND due_date < CURDATE()
      AND paid_amount < amount
");
/* =====================================================
   SYNC UPDATED BILLING AMOUNT TO PAYABLES
===================================================== */
$conn->query("
    UPDATE payables py
    INNER JOIN billings b ON py.billing_id = b.id
    SET py.amount = b.amount
");
/* =====================================================
   CREATE MANUAL PAYABLE
===================================================== */
if (isset($_POST['save'])) {
    $billing_id  = (int)$_POST['billing_id'];
    $due_date    = $_POST['due_date'];
    $amount      = (float)$_POST['amount'];
    $paid_amount = (float)$_POST['paid_amount'];
    if ($paid_amount >= $amount) {
        $status = 'Paid';
    } elseif ($paid_amount > 0) {
        $status = 'Partial';
    } else {
        $status = 'Pending';
    }
    $stmt = $conn->prepare("
        INSERT INTO payables
        (billing_id,due_date,amount,paid_amount,status)
        VALUES (?,?,?,?,?)
    ");
    $stmt->bind_param("isdds", $billing_id, $due_date, $amount, $paid_amount, $status);
    $stmt->execute();
    logAction($conn, 'CREATE', 'payables', $conn->insert_id, 'Created payable');
    echo "<script>location='./?link=payables.php&added=1';</script>";
    exit;
}
/* =====================================================
   UPDATE PAYMENT
===================================================== */
if (isset($_POST['pay'])) {
    $id          = (int)$_POST['id'];
    $paid_amount = (float)$_POST['paid_amount'];
    $row = $conn->query("SELECT amount FROM payables WHERE id=$id LIMIT 1")->fetch_assoc();
    $amount = (float)$row['amount'];
    if ($paid_amount >= $amount) {
        $status = 'Paid';
    } elseif ($paid_amount > 0) {
        $status = 'Partial';
    } else {
        $status = 'Pending';
    }
    if ($status != 'Paid' && $paid_amount < $amount) {
        $due = $conn->query("SELECT due_date FROM payables WHERE id=$id")->fetch_assoc()['due_date'];
        if ($due < date('Y-m-d')) {
            $status = 'Overdue';
        }
    }
    $stmt = $conn->prepare("
        UPDATE payables
        SET paid_amount=?, status=?
        WHERE id=?
    ");
    $stmt->bind_param("dsi", $paid_amount, $status, $id);
    $stmt->execute();
    logAction($conn, 'UPDATE', 'payables', $id, 'Updated payment');
    echo "<script>location='./?link=payables.php&updated=1';</script>";
    exit;
}
/* =====================================================
   EDIT PAYABLE
===================================================== */
if (isset($_POST['edit'])) {
    $id          = (int)$_POST['id'];
    $due_date    = $_POST['due_date'];
    $amount      = (float)$_POST['amount'];
    $paid_amount = (float)$_POST['paid_amount'];
    if ($paid_amount >= $amount) {
        $status = 'Paid';
    } elseif ($paid_amount > 0) {
        $status = 'Partial';
    } else {
        $status = 'Pending';
    }
    if ($status != 'Paid' && $due_date < date('Y-m-d')) {
        $status = 'Overdue';
    }
    $stmt = $conn->prepare("
        UPDATE payables
        SET due_date=?, amount=?, paid_amount=?, status=?
        WHERE id=?
    ");
    $stmt->bind_param("sddsi", $due_date, $amount, $paid_amount, $status, $id);
    $stmt->execute();
    logAction($conn, 'UPDATE', 'payables', $id, 'Edited payable');
    echo "<script>location='./?link=payables.php&edited=1';</script>";
    exit;
}
/* =====================================================
   DELETE
===================================================== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM payables WHERE id=$id");
    logAction($conn, 'DELETE', 'payables', $id, 'Deleted payable');
    echo "<script>location='./?link=payables.php&deleted=1';</script>";
    exit;
}
/* =====================================================
   DATA
===================================================== */
$billings = $conn->query("
    SELECT b.id,
           b.billing_stage,
           b.amount,
           p.project_name,
           c.name contractor_name
    FROM billings b
    LEFT JOIN projects p ON b.project_id=p.id
    LEFT JOIN contractors c ON b.contractor_id=c.id
    ORDER BY b.id DESC
");
$list = $conn->query("
    SELECT py.*,
           COALESCE(b.billing_stage,'N/A') billing_stage,
           COALESCE(p.project_name,'N/A') project_name,
           COALESCE(c.name,'N/A') contractor_name
    FROM payables py
    LEFT JOIN billings b ON py.billing_id=b.id
    LEFT JOIN projects p ON b.project_id=p.id
    LEFT JOIN contractors c ON b.contractor_id=c.id
    ORDER BY py.id DESC
");
/* =====================================================
   SUMMARY
===================================================== */
$total = $conn->query("SELECT IFNULL(SUM(amount),0) total FROM payables")->fetch_assoc()['total'];
$paid  = $conn->query("SELECT IFNULL(SUM(paid_amount),0) total FROM payables")->fetch_assoc()['total'];
$balance = $total - $paid;
$overdue = $conn->query("SELECT COUNT(*) total FROM payables WHERE status='Overdue'")->fetch_assoc()['total'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-white fw-bold mb-1">Payables</h2>
        <small class="text-secondary">Track obligations, due dates and contractor payments</small>
    </div>
    <input type="text"
        class="form-control"
        style="max-width:280px"
        placeholder="Search payables..."
        onkeyup="filterPayables(this.value)">
</div>
<?php if ($overdue > 0): ?>
    <div class="alert alert-danger py-2"><?= $overdue ?> overdue payable(s) require action.</div>
<?php endif; ?>
<?php foreach (['added' => 'success', 'updated' => 'info', 'edited' => 'primary', 'deleted' => 'danger'] as $k => $cls): ?>
    <?php if (isset($_GET[$k])): ?>
        <div class="alert alert-<?= $cls ?> py-2 text-capitalize"><?= $k ?> successfully.</div>
    <?php endif; ?>
<?php endforeach; ?>
<!-- SUMMARY -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card p-4 text-center shadow-sm">
            <small class="text-secondary">Total Payables</small>
            <h3>₱<?= number_format($total, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center shadow-sm">
            <small class="text-secondary">Paid</small>
            <h3 class="text-success">₱<?= number_format($paid, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center shadow-sm">
            <small class="text-secondary">Balance</small>
            <h3 class="text-warning">₱<?= number_format($balance, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-4 text-center shadow-sm">
            <small class="text-secondary">Overdue</small>
            <h3 class="text-danger"><?= $overdue ?></h3>
        </div>
    </div>
</div>
<?php if (!$isManagement): ?>
    <!-- ADD FORM -->
    <div class="card p-4 mb-4 shadow-sm">
        <form method="post" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="small text-secondary mb-1">Billing</label>
                <select name="billing_id" class="form-select" required>
                    <option value="">Select Billing</option>
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
                <label class="small text-secondary mb-1">Due Date</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="small text-secondary mb-1">Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="small text-secondary mb-1">Paid</label>
                <input type="number" step="0.01" name="paid_amount" value="0" class="form-control">
            </div>
            <div class="col-md-2">
                <button name="save" class="btn btn-gold w-100">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>
<!-- TABLE -->
<div class="card p-3 shadow-sm">
    <div>
        <h4 class="fw-bold mb-1">Payables Record
        </h4>
        <small class="text-secondary">
            Comprehensive list of payables, payment status and due dates
        </small>
    </div>
    <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
        onclick="toggleFullscreen(this)">
        <i class="bi bi-arrows-fullscreen"></i>
    </button>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="payableTable">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Project</th>
                    <th>Contractor</th>
                    <th>Billing</th>
                    <th>Due Date</th>
                    <th>Amount</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th width="220">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $list->fetch_assoc()): ?>
                    <?php $bal = $r['amount'] - $r['paid_amount']; ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= $r['project_name'] ?></td>
                        <td><?= $r['contractor_name'] ?></td>
                        <td><?= $r['billing_stage'] ?></td>
                        <td><?= $r['due_date'] ?></td>
                        <td>₱<?= number_format($r['amount'], 2) ?></td>
                        <td>₱<?= number_format($r['paid_amount'], 2) ?></td>
                        <td>₱<?= number_format($bal, 2) ?></td>
                        <td>
                            <?php if ($r['status'] == 'Paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php elseif ($r['status'] == 'Partial'): ?>
                                <span class="badge bg-info">Partial</span>
                            <?php elseif ($r['status'] == 'Overdue'): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td class="d-flex gap-1 flex-wrap">
                            <button class="btn btn-sm btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#pay<?= $r['id'] ?>">
                                <i class="bi bi-cash"></i>
                            </button>
                            <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#edit<?= $r['id'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <a href="./?link=payables.php&delete=<?= $r['id'] ?>"
                                onclick="return confirm('Delete payable?')"
                                class="btn btn-sm btn-danger">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <!-- PAYMENT MODAL -->
                    <div class="modal fade" id="pay<?= $r['id'] ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Payment</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <label class="small mb-1">Paid Amount</label>
                                        <input type="number" step="0.01" name="paid_amount"
                                            value="<?= $r['paid_amount'] ?>" class="form-control">
                                    </div>
                                    <div class="modal-footer">
                                        <button name="pay" class="btn btn-gold">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- EDIT MODAL -->
                    <div class="modal fade" id="edit<?= $r['id'] ?>">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Payable</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <label class="small mb-1">Due Date</label>
                                        <input type="date" name="due_date"
                                            value="<?= $r['due_date'] ?>" class="form-control mb-2">
                                        <label class="small mb-1">Amount</label>
                                        <input type="number" step="0.01" name="amount"
                                            value="<?= $r['amount'] ?>" class="form-control mb-2">
                                        <label class="small mb-1">Paid Amount</label>
                                        <input type="number" step="0.01" name="paid_amount"
                                            value="<?= $r['paid_amount'] ?>" class="form-control">
                                    </div>
                                    <div class="modal-footer">
                                        <button name="edit" class="btn btn-warning">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- PAYMENT HISTORY TEMPLATE -->
<div class="card p-4 mt-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">
                Payment History Template
            </h4>
            <small class="text-secondary">
                Billing certification, payment tracking and contractor balances
            </small>
        </div>
        <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
            onclick="toggleFullscreen(this)">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Contractor</th>
                    <th>Billing No.</th>
                    <th>Billing Period</th>
                    <th>Amount Billed</th>
                    <th>Amount Certified</th>
                    <th>Amount Paid</th>
                    <th>Date Paid</th>
                    <th>Balance</th>
                    <th>Payment Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $history = $conn->query("
                    SELECT
                        py.*,
                        b.billing_date,
                        b.amount billed_amount,
                        c.name contractor_name
                    FROM payables py
                    LEFT JOIN billings b
                        ON py.billing_id = b.id
                    LEFT JOIN contractors c
                        ON b.contractor_id = c.id
                    ORDER BY py.id DESC
                ");
                while ($r = $history->fetch_assoc()):
                    $balance = $r['amount'] - $r['paid_amount'];
                    if ($r['status'] == 'Paid') {
                        $remarks = 'Fully settled';
                    } elseif ($r['status'] == 'Partial') {
                        $remarks = 'Awaiting final validation';
                    } elseif ($r['status'] == 'Pending') {
                        $remarks = 'Subject to approval';
                    } else {
                        $remarks = 'Outstanding payable';
                    }
                ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= $r['contractor_name'] ?>
                        </td>
                        <td>
                            PB-<?= str_pad($r['billing_id'], 3, '0', STR_PAD_LEFT) ?>
                        </td>
                        <td>
                            <?= date('M-y', strtotime($r['billing_date'])) ?>
                        </td>
                        <td>
                            ₱<?= number_format($r['billed_amount'], 2) ?>
                        </td>
                        <td class="text-primary fw-semibold">
                            ₱<?= number_format($r['amount'], 2) ?>
                        </td>
                        <td class="text-success fw-semibold">
                            ₱<?= number_format($r['paid_amount'], 2) ?>
                        </td>
                        <td>
                            <?php if ($r['paid_amount'] > 0): ?>
                                <?= date('d-M-y', strtotime($r['due_date'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                            ₱<?= number_format($balance, 2) ?>
                        </td>
                        <td>
                            <?php if ($r['status'] == 'Paid'): ?>
                                <span class="badge bg-success">
                                    Paid
                                </span>
                            <?php elseif ($r['status'] == 'Partial'): ?>
                                <span class="badge bg-info">
                                    Partial
                                </span>
                            <?php elseif ($r['status'] == 'Overdue'): ?>
                                <span class="badge bg-danger">
                                    Overdue
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $remarks ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- ACCOUNTS PAYABLE SUMMARY -->
<div class="card p-4 mt-4 shadow-sm" id="accounts_payable_summary">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">
                Accounts Payable Summary
            </h4>
            <small class="text-secondary">
                Urgent payables, operational exposure and payment readiness monitoring
            </small>
        </div>
        <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
            onclick="toggleFullscreen(this)">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Contractor</th>
                    <th>Type</th>
                    <th>Related Package</th>
                    <th>Amount Due</th>
                    <th>Due Date</th>
                    <th>Priority</th>
                    <th>Consequence if Delayed</th>
                    <th>Recommended Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $summary = $conn->query("
                    SELECT
                        py.*,
                        c.name contractor_name,
                        ci.activity_name
                    FROM payables py
                    LEFT JOIN billings b
                        ON py.billing_id = b.id
                    LEFT JOIN contractors c
                        ON b.contractor_id = c.id
                    LEFT JOIN contractor_items ci
                        ON b.item_id = ci.id
                    ORDER BY py.due_date ASC
                ");
                while ($r = $summary->fetch_assoc()):
                    $today = strtotime(date('Y-m-d'));
                    $due = strtotime($r['due_date']);
                    $days = ($due - $today) / 86400;
                    if ($days <= 3) {
                        $priority = 'Urgent';
                        $consequence =
                            'Critical work interruption possible';
                        $action =
                            'Immediate payment approval required';
                        $badge = 'danger';
                    } elseif ($days <= 7) {
                        $priority = 'Due Soon';
                        $consequence =
                            'Contractor manpower may reduce';
                        $action =
                            'Prepare AP release schedule';
                        $badge = 'warning';
                    } else {
                        $priority = 'Scheduled';
                        $consequence =
                            'Low operational risk';
                        $action =
                            'Include in regular payment cycle';
                        $badge = 'success';
                    }
                ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= $r['contractor_name'] ?>
                        </td>
                        <td>
                            <?php
                            if (stripos($r['contractor_name'], 'supplier') !== false) {
                                echo 'Supplier';
                            } elseif (stripos($r['contractor_name'], 'rental') !== false) {
                                echo 'Rental';
                            } else {
                                echo 'Contractor';
                            }
                            ?>
                        </td>
                        <td>
                            <?= $r['activity_name'] ?>
                        </td>
                        <td class="fw-bold text-danger">
                            ₱<?= number_format($r['amount'], 2) ?>
                        </td>
                        <td>
                            <?= date('d-M-y', strtotime($r['due_date'])) ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $priority ?>
                            </span>
                        </td>
                        <td style="min-width:260px;">
                            <?= $consequence ?>
                        </td>
                        <td style="min-width:260px;">
                            <?= $action ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    function filterPayables(keyword) {
        keyword = keyword.toLowerCase();
        document.querySelectorAll("#payableTable tbody tr").forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(keyword) ?
                "" :
                "none";
        });
    }
</script>