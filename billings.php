<?php
/* FULL PART 2 billings.php - FINAL RESET FIX */
include 'database.php';
/* SAVE */
if (isset($_POST['save'])) {
    $project_id      = (int)$_POST['project_id'];
    $contractor_id   = (int)$_POST['contractor_id'];
    $item_id         = (int)$_POST['item_id'];
    $billing_stage   = $conn->real_escape_string($_POST['billing_stage']);
    $amount          = (float)$_POST['amount'];
    $contract_amount = (float)$_POST['contract_amount'];
    $percent         = (float)$_POST['percent'];
    $status          = $conn->real_escape_string($_POST['status']);
    $billing_date    = $_POST['billing_date'];
    $conn->begin_transaction();
    try {
        $conn->query("
            INSERT INTO billings(
                project_id, contractor_id, item_id,
                billing_stage, amount, contract_amount,
                percent, status, billing_date
            )
            VALUES(
                $project_id, $contractor_id, $item_id,
                '$billing_stage', '$amount', '$contract_amount',
                '$percent', '$status', '$billing_date'
            )
        ");
        $billing_id = $conn->insert_id;
        /* create linked payable immediately */
        $due_date = date('Y-m-d', strtotime($billing_date . ' +30 days'));
        $paid_amount = 0;
        $payable_status = 'Pending';
        $stmt = $conn->prepare("
            INSERT INTO payables
            (billing_id, due_date, amount, paid_amount, status)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                due_date = VALUES(due_date),
                amount = VALUES(amount),
                paid_amount = VALUES(paid_amount),
                status = VALUES(status)
        ");
        $stmt->bind_param("isdds", $billing_id, $due_date, $amount, $paid_amount, $payable_status);
        $stmt->execute();
        $conn->commit();
        header("Location: ./?link=billings.php&added=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Billing save failed: " . $e->getMessage());
    }
}
/* DELETE */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM payables WHERE billing_id=$id");
        $conn->query("DELETE FROM billings WHERE id=$id");
        $conn->commit();
        header("Location: ./?link=billings.php&deleted=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Delete failed: " . $e->getMessage());
    }
}
/* DATA */
$projects = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name");
$contractors = $conn->query("SELECT id, name FROM contractors ORDER BY name");
$items = $conn->query("
SELECT ci.id,
       ci.contractor_id,
       ci.project_id,
       ci.activity_name,
       ci.zone_floor,
       ci.contract_amount,
       ci.number_of_payments,
       p.project_name,
       c.name contractor_name
FROM contractor_items ci
LEFT JOIN projects p ON ci.project_id = p.id
LEFT JOIN contractors c ON ci.contractor_id = c.id
ORDER BY ci.id DESC
");
$totalBillings = $conn->query("SELECT IFNULL(SUM(amount),0) t FROM billings")->fetch_assoc()['t'];
$totalPaid     = $conn->query("SELECT IFNULL(SUM(amount),0) t FROM billings WHERE status='Paid'")->fetch_assoc()['t'];
$pendingCount  = $conn->query("SELECT COUNT(*) t FROM billings WHERE status='Pending'")->fetch_assoc()['t'];
$list = $conn->query("
SELECT b.*, p.project_name, c.name contractor_name,
       ci.activity_name, ci.zone_floor
FROM billings b
LEFT JOIN projects p ON b.project_id=p.id
LEFT JOIN contractors c ON b.contractor_id=c.id
LEFT JOIN contractor_items ci ON b.item_id=ci.id
ORDER BY b.id DESC
");
?>
<style>
    .money-view {
        display: flex;
        align-items: center;
        font-weight: 700;
        color: #0f766e;
        background: #f8fafc;
    }
</style>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="text-white fw-bold mb-1">Billings</h2>
        <small class="text-secondary">Contractor Billing Management</small>
    </div>
    <div class="d-flex gap-2">
        <a href="export_billings_pdf.php"
            target="_blank"
            class="btn btn-danger shadow-sm px-3">
            Export PDF
        </a>
    </div>
</div>
<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success py-2">Billing added successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger py-2">Billing deleted successfully.</div>
<?php endif; ?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <small>Total Billings</small>
            <h3>₱<?= number_format($totalBillings, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <small>Paid</small>
            <h3 class="text-success">₱<?= number_format($totalPaid, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <small>Pending</small>
            <h3 class="text-warning"><?= $pendingCount ?></h3>
        </div>
    </div>
</div>
<div class="card p-4 mb-4">
    <form method="post" class="row g-3">
        <div class="col-md-2">
            <label>Project</label>
            <select name="project_id" id="project_id" class="form-select no-select2" required>
                <option value="">Select</option>
                <?php while ($p = $projects->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>"><?= $p['project_name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Contractor</label>
            <select name="contractor_id" id="contractor_id" class="form-select no-select2" required>
                <option value="">Select</option>
                <?php while ($c = $contractors->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label>Billable Scope</label>
            <select name="item_id" id="itemSelect" class="form-select no-select2" required>
                <option value="">Select Scope</option>
                <?php while ($i = $items->fetch_assoc()): ?>
                    <option
                        value="<?= $i['id'] ?>"
                        data-project="<?= $i['project_id'] ?>"
                        data-contractor="<?= $i['contractor_id'] ?>"
                        data-contract="<?= $i['contract_amount'] ?>"
                        data-payments="<?= $i['number_of_payments'] ?>">
                        <?= $i['project_name'] ?> / <?= $i['contractor_name'] ?> / <?= $i['activity_name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Stage</label>
            <select name="billing_stage" id="stageSelect" class="form-select no-select2" required>
                <option value="">Select</option>
            </select>
        </div>
        <div class="col-md-2">
            <label>Amount</label>
            <input type="hidden" name="amount" id="amount">
            <div class="money-view form-control" id="amount_view">₱0.00</div>
        </div>
        <div class="col-md-2">
            <label>Contract Amount</label>
            <input type="hidden" name="contract_amount" id="contract_amount">
            <div class="money-view form-control" id="contract_amount_view">₱0.00</div>
        </div>
        <div class="col-md-2">
            <label>%</label>
            <input type="number" name="percent" id="percent" class="form-control" readonly>
        </div>
        <div class="col-md-2">
            <label>Date</label>
            <input type="date" name="billing_date" id="billing_date" class="form-control" required>
        </div>
        <div class="col-md-2">
            <label>Status</label>
            <select name="status" id="status" class="form-select">
                <option value="Pending">Pending</option>
                <option value="Paid">Paid</option>
                <option value="Cancelled">Cancelled</option>
            </select>
        </div>
        <div class="col-md-1">
            <label>&nbsp;</label>
            <button name="save" class="btn btn-gold w-100">+</button>
        </div>
    </form>
</div>
<!-- TABLE -->
<div class="card p-3">
    <h4>Billing Record</h4>
      <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
        onclick="toggleFullscreen(this)">
        <i class="bi bi-arrows-fullscreen"></i>
    </button>
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="billingTable">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Contractor</th>
                    <th>Scope</th>
                    <th>Stage</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $list->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['project_name'] ?></td>
                        <td><?= $r['contractor_name'] ?></td>
                        <td>
                            <strong><?= $r['activity_name'] ?></strong><br>
                            <small class="text-secondary"><?= $r['zone_floor'] ?></small>
                        </td>
                        <td><?= $r['billing_stage'] ?></td>
                        <td class="fw-bold text-success">
                            ₱<?= number_format($r['amount'], 2) ?>
                        </td>
                        <td>
                            <?php if ($r['status'] == 'Paid'): ?>
                                <span class="badge bg-success">Paid</span>
                            <?php elseif ($r['status'] == 'Pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Cancelled</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $r['billing_date'] ?></td>
                        <td>
                            <a href="./?link=billings.php&delete=<?= $r['id'] ?>"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete billing?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>
<!-- EXECUTIVE BILLING MONITORING -->
<div class="card p-4 mt-4 shadow-sm" id="progress_billing">
    <div class="d-flex justify-content-between align-items-center mb-3">
          <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
        onclick="toggleFullscreen(this)">
        <i class="bi bi-arrows-fullscreen"></i>
    </button>
        <div>
            <h4 class="fw-bold mb-1">
                Contractor Progress Billing Template
            </h4>
            <small class="text-secondary">
                Current billing, retention, net payable and certification monitoring
            </small>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Billing No.</th>
                    <th>Contractor</th>
                    <th>Package</th>
                    <th>Billing Period</th>
                    <th>Contract Value</th>
                    <th>Previous Certified Billing</th>
                    <th>Current Work Accomplished</th>
                    <th>Gross Amount Due</th>
                    <th>Retention %</th>
                    <th>Retention Amount</th>
                    <th>Net Amount Due</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $executive = $conn->query("
                    SELECT
                        b.*,
                        c.name contractor_name,
                        ci.activity_name,
                        IFNULL(r.retention_percent,10)
                            retention_percent,
                        IFNULL(r.retention_amount,
                            (b.amount * .10)
                        ) retention_amount
                    FROM billings b
                    LEFT JOIN contractors c
                        ON b.contractor_id = c.id
                    LEFT JOIN contractor_items ci
                        ON b.item_id = ci.id
                    LEFT JOIN retentions r
                        ON r.billing_id = b.id
                    ORDER BY b.id DESC
                ");
                while($r = $executive->fetch_assoc()):
                    $previous = $conn->query("
                        SELECT IFNULL(SUM(amount),0) total
                        FROM billings
                        WHERE contractor_id = {$r['contractor_id']}
                        AND id < {$r['id']}
                    ")->fetch_assoc()['total'];
                    $gross = (float)$r['amount'];
                    $retention = (float)$r['retention_amount'];
                    $net = $gross - $retention;
                ?>
                <tr>
                    <td class="fw-bold">
                        PB-<?= str_pad($r['id'],3,'0',STR_PAD_LEFT) ?>
                    </td>
                    <td>
                        <?= $r['contractor_name'] ?>
                    </td>
                    <td>
                        <?= $r['activity_name'] ?>
                    </td>
                    <td>
                        <?= date('M-y', strtotime($r['billing_date'])) ?>
                    </td>
                    <td class="fw-semibold">
                        ₱<?= number_format($r['contract_amount'],2) ?>
                    </td>
                    <td>
                        ₱<?= number_format($previous,2) ?>
                    </td>
                    <td class="text-primary fw-semibold">
                        ₱<?= number_format($gross,2) ?>
                    </td>
                    <td>
                        ₱<?= number_format($gross,2) ?>
                    </td>
                    <td>
                        <?= number_format($r['retention_percent'],0) ?>%
                    </td>
                    <td class="text-warning fw-semibold">
                        ₱<?= number_format($retention,2) ?>
                    </td>
                    <td class="text-success fw-bold">
                        ₱<?= number_format($net,2) ?>
                    </td>
                    <td>
                        <?php if($r['status'] == 'Paid'): ?>
                            <span class="badge bg-success">
                                Paid
                            </span>
                        <?php elseif($r['status'] == 'Pending'): ?>
                            <span class="badge bg-warning text-dark">
                                For Approval
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger">
                                Cancelled
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    (function() {
        const projectSel = document.getElementById('project_id');
        const contractorSel = document.getElementById('contractor_id');
        const itemSel = document.getElementById('itemSelect');
        const stageSel = document.getElementById('stageSelect');
        const amount = document.getElementById('amount');
        const contractAmount = document.getElementById('contract_amount');
        const percent = document.getElementById('percent');
        const billingDate = document.getElementById('billing_date');
        const statusSel = document.getElementById('status');
        const originalOptions = itemSel.innerHTML;
        /* ---------- helpers ---------- */
        function peso(v) {
            return '₱' + Number(v || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        function refreshViews() {
            document.getElementById('amount_view').innerHTML =
                peso(amount.value);
            document.getElementById('contract_amount_view').innerHTML =
                peso(contractAmount.value);
        }
        function clearMoney() {
            amount.value = '';
            contractAmount.value = '';
            percent.value = '';
            refreshViews();
        }
        function resetSelect(el, text = 'Select') {
            el.innerHTML = `<option value="">${text}</option>`;
            el.value = '';
        }
        /* ---------- reset logic ---------- */
        function resetEverything() {
            contractorSel.value = '';
            contractorSel.selectedIndex = 0;
            resetSelect(itemSel, 'Select Scope');
            resetSelect(stageSel, 'Select');
            clearMoney();
            billingDate.value = '';
            statusSel.value = 'Pending';
            contractorSel.dispatchEvent(new Event('change'));
        }
        function resetAfterContractor() {
            resetSelect(itemSel, 'Select Scope');
            resetSelect(stageSel, 'Select');
            clearMoney();
        }
        /* ---------- load scopes ---------- */
        function loadScopes() {
            const pid = projectSel.value;
            const cid = contractorSel.value;
            resetSelect(itemSel, 'Select Scope');
            resetSelect(stageSel, 'Select');
            clearMoney();
            if (!pid || !cid) return;
            const temp = document.createElement('select');
            temp.innerHTML = originalOptions;
            Array.from(temp.options).forEach((opt, index) => {
                if (index === 0) return;
                if (
                    opt.dataset.project === pid &&
                    opt.dataset.contractor === cid
                ) {
                    itemSel.appendChild(opt.cloneNode(true));
                }
            });
        }
        /* ---------- build stages ---------- */
        function buildStages() {
            const opt = itemSel.options[itemSel.selectedIndex];
            if (!opt || !opt.value) return;
            const total = parseFloat(opt.dataset.contract || 0);
            const pays = parseInt(opt.dataset.payments || 1);
            contractAmount.value = total.toFixed(2);
            amount.value = (total / pays).toFixed(2);
            percent.value = (100 / pays).toFixed(2);
            refreshViews();
            resetSelect(stageSel, 'Select');
            for (let i = 1; i <= pays; i++) {
                let label = '';
                if (i === 1) label = 'Downpayment';
                else if (i === pays) label = 'Final Billing';
                else label = (i - 1) + ' Progress Billing';
                stageSel.innerHTML +=
                    `<option value="${label}">${label}</option>`;
            }
        }
        /* ---------- events ---------- */
        function onProjectChanged() {
            setTimeout(function() {
                resetEverything();
            }, 50);
        }
        projectSel.addEventListener('change', onProjectChanged);
        if (window.jQuery) {
            $('#project_id').on('change.select2', onProjectChanged);
        }
        contractorSel.addEventListener('change', function() {
            resetAfterContractor();
            loadScopes();
        });
        itemSel.addEventListener('change', buildStages);
        /* initial */
        refreshViews();
    })();
</script>