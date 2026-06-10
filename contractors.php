<?php
/* contractors.php - PREMIUM FULL FIXED */
include 'database.php';
include 'audit_helper.php';
$isManagement = isset($_SESSION['role']) && $_SESSION['role'] === 'management';
/* =========================
CREATE
========================= */
if (isset($_POST['save'])) {
    $name            = trim($_POST['name']);
    $contact         = trim($_POST['contact']);
    $contractor_type = trim($_POST['contractor_type']);
    $billing_type    = trim($_POST['billing_type']);
    $stmt = $conn->prepare("
        INSERT INTO contractors
        (name, contact, contractor_type, billing_type)
        VALUES (?,?,?,?)
    ");
    $stmt->bind_param(
        "ssss",
        $name,
        $contact,
        $contractor_type,
        $billing_type
    );
    $stmt->execute();
    logAction(
        $conn,
        'CREATE',
        'contractors',
        $conn->insert_id,
        'Added contractor ' . $name
    );
    echo "<script>location='./?link=contractors.php&added=1';</script>";
    exit;
}
/* =========================
UPDATE
========================= */
if (isset($_POST['update'])) {
    $id              = (int)$_POST['id'];
    $name            = trim($_POST['name']);
    $contact         = trim($_POST['contact']);
    $contractor_type = trim($_POST['contractor_type']);
    $billing_type    = trim($_POST['billing_type']);
    $stmt = $conn->prepare("
        UPDATE contractors SET
        name=?,
        contact=?,
        contractor_type=?,
        billing_type=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "ssssi",
        $name,
        $contact,
        $contractor_type,
        $billing_type,
        $id
    );
    $stmt->execute();
    logAction(
        $conn,
        'UPDATE',
        'contractors',
        $id,
        'Updated contractor ' . $name
    );
    echo "<script>location='./?link=contractors.php&updated=1';</script>";
    exit;
}
/* =========================
DELETE
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $conn->query("
        SELECT name
        FROM contractors
        WHERE id=$id
        LIMIT 1
    ")->fetch_assoc();
    $conn->query("DELETE FROM contractors WHERE id=$id");
    logAction(
        $conn,
        'DELETE',
        'contractors',
        $id,
        'Deleted contractor ' . ($row['name'] ?? '')
    );
    echo "<script>location='./?link=contractors.php&deleted=1';</script>";
    exit;
}
/* =========================
DATA
========================= */
$res = $conn->query("
    SELECT *
    FROM contractors
    ORDER BY id DESC
");
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
}
/* SUMMARY */
$total = count($rows);
$suppliers = $conn->query("
    SELECT COUNT(*) total
    FROM contractors
    WHERE contractor_type LIKE '%Supplier%'
")->fetch_assoc()['total'];
$installers = $conn->query("
    SELECT COUNT(*) total
    FROM contractors
    WHERE contractor_type LIKE '%Installer%'
")->fetch_assoc()['total'];
?>
<style>
    td{
        min-width: 120px;
    }
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="text-white fw-bold mb-1">Contractors</h2>
        <small class="text-secondary">
            Manage suppliers, installers, labor and vendors
        </small>
    </div>
    <input type="text"
        class="form-control"
        style="max-width:280px"
        placeholder="Search contractor..."
        onkeyup="filterContractors(this.value)">
</div>
<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success py-2">Contractor added successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-info py-2">Contractor updated successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger py-2">Contractor deleted successfully.</div>
<?php endif; ?>
<!-- SUMMARY -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Total Contractors</small>
            <h3><?= $total ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Suppliers</small>
            <h3><?= $suppliers ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center h-100">
            <small class="text-secondary">Installers</small>
            <h3><?= $installers ?></h3>
        </div>
    </div>
</div>
<!-- ADD FORM -->
<?php if (!$isManagement): ?>
    <!-- ADD FORM -->
    <div class="card p-4 mb-4">
        <form method="post" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="small text-secondary mb-1">Contractor Name</label>
                <input type="text"
                    name="name"
                    class="form-control"
                    required>
            </div>
            <div class="col-md-2">
                <label class="small text-secondary mb-1">Contact</label>
                <input type="text"
                    name="contact"
                    class="form-control">
            </div>
            <div class="col-md-3">
                <label class="small text-secondary mb-1">Contractor Type</label>
                <select name="contractor_type" class="form-select select2" required>
                    <option value="">Select Type</option>
                    <option>Installation only</option>
                    <option>Labor only</option>
                    <option>Supply + Installation</option>
                    <option>Supply + Application</option>
                    <option>Supply + Delivery</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="small text-secondary mb-1">Billing Type</label>
                <select name="billing_type"
                    class="form-select select2"
                    required>
                    <option value="">Select Billing</option>
                    <option>Progress Billing</option>
                    <option>Per Delivery</option>
                    <option>Lump Sum</option>
                    <option>Milestone</option>
                    <option>Monthly</option>
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
<!-- CONTRACTOR PROJECT SUMMARY -->
<div class="card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0 contractor-summary-table">
            <thead>
                <tr>
                    <th>Contractor</th>
                    <th>Package / Scope</th>
                    <th>Tower / Area</th>
                    <th>Contract Value</th>
                    <th>Start Date</th>
                    <th>Target Completion</th>
                    <th>Contract Status</th>
                    <th>Billing Terms</th>
                    <th>Retention %</th>
                    <th>Remarks</th>
                    <?php if (!$isManagement): ?>
                        <th width="120">Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $summaryQuery = $conn->query("
    SELECT
        c.id,
        c.name,
        c.contractor_type,
        c.billing_type,
        p.project_name,
        ci.contract_amount,
        ci.start_date,
        ci.target_date,
        ci.number_of_payments,
        ci.zone_floor,
        CASE
            WHEN CURDATE() > ci.target_date THEN 'Delayed'
            WHEN CURDATE() >= ci.start_date THEN 'Active'
            ELSE 'Pending'
        END AS contract_status
    FROM contractors c
    LEFT JOIN contractor_items ci
        ON c.id = ci.contractor_id
    LEFT JOIN projects p
        ON ci.project_id = p.id
    ORDER BY ci.id DESC
");
                while ($r = $summaryQuery->fetch_assoc()):
                ?>
                    <tr>
                        <td class="contractor-name">
                            <?= $r['name'] ?>
                        </td>
                        <td>
                            <?= $r['contractor_type'] ?>
                        </td>
                        <td>
                            <?= !empty($r['project_name']) ? $r['project_name'] : '-' ?>
                        </td>
                        <td class="contract-value">
                            ₱<?= number_format($r['contract_amount'], 2) ?>
                        </td>
                        <td>
                            <?= !empty($r['start_date'])
                                ? date('d-M-y', strtotime($r['start_date']))
                                : '-' ?>
                        </td>
                        <td>
                            <?= !empty($r['target_date'])
                                ? date('d-M-y', strtotime($r['target_date']))
                                : '-' ?>
                        </td>
                        <td>
                            <?php if ($r['contract_status'] == 'Active'): ?>
                                <span class="badge bg-success">
                                    Active
                                </span>
                            <?php elseif ($r['contract_status'] == 'Delayed'): ?>
                                <span class="badge bg-danger">
                                    Delayed
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $r['billing_type'] ?>
                        </td>
                        <td>
                            <?= $r['number_of_payments'] ?> Payments
                        </td>
                        <td class="text-muted">
                            <?php
                            if ($r['contract_status'] == 'Delayed') {
                                echo 'Target completion overdue';
                            } elseif ($r['contract_status'] == 'Active') {
                                echo 'Ongoing construction works';
                            } else {
                                echo 'Waiting for mobilization';
                            }
                            ?>
                        </td>
                        <?php if (!$isManagement): ?>
                            <td>
                                <div class="action-btns">
                                    <a href="./?link=contractor_profile.php&id=<?= $r['id'] ?>"
                                        class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="#"
                                        class="btn btn-sm btn-outline-dark"
                                        data-bs-toggle="modal"
                                        data-bs-target="#edit<?= $r['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="./?link=contractors.php&delete=<?= $r['id'] ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this contractor?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- MODALS OUTSIDE TABLE -->
<?php foreach ($rows as $r): ?>
    <div class="modal fade" id="edit<?= $r['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title">Edit Contractor</h5>
                        <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="small mb-1">Name</label>
                                <input type="text"
                                    name="name"
                                    class="form-control"
                                    value="<?= $r['name'] ?>"
                                    required>
                            </div>
                            <div class="col-md-2">
                                <label class="small mb-1">Contact</label>
                                <input type="text"
                                    name="contact"
                                    class="form-control"
                                    value="<?= $r['contact'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="small mb-1">Type</label>
                                <select name="contractor_type" class="form-select select2">
                                    <option <?= $r['contractor_type'] == 'Installation only' ? 'selected' : '' ?>>
                                        Installation only
                                    </option>
                                    <option <?= $r['contractor_type'] == 'Labor only' ? 'selected' : '' ?>>
                                        Labor only
                                    </option>
                                    <option <?= $r['contractor_type'] == 'Supply + Installation' ? 'selected' : '' ?>>
                                        Supply + Installation
                                    </option>
                                    <option <?= $r['contractor_type'] == 'Supply + Application' ? 'selected' : '' ?>>
                                        Supply + Application
                                    </option>
                                    <option <?= $r['contractor_type'] == 'Supply + Delivery' ? 'selected' : '' ?>>
                                        Supply + Delivery
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small mb-1">Billing</label>
                                <select name="billing_type" class="form-select select2">
                                    <option <?= $r['billing_type'] == 'Progress Billing' ? 'selected' : '' ?>>
                                        Progress Billing
                                    </option>
                                    <option <?= $r['billing_type'] == 'Per Delivery' ? 'selected' : '' ?>>
                                        Per Delivery
                                    </option>
                                    <option <?= $r['billing_type'] == 'Lump Sum' ? 'selected' : '' ?>>
                                        Lump Sum
                                    </option>
                                    <option <?= $r['billing_type'] == 'Milestone' ? 'selected' : '' ?>>
                                        Milestone
                                    </option>
                                    <option <?= $r['billing_type'] == 'Monthly' ? 'selected' : '' ?>>
                                        Monthly
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Close
                        </button>
                        <button name="update"
                            class="btn btn-gold">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    function filterContractors(keyword) {
        keyword = keyword.toLowerCase();
        document.querySelectorAll("#contractorTable tbody tr").forEach(row => {
            row.style.display =
                row.innerText.toLowerCase().includes(keyword) ?
                "" :
                "none";
        });
    }
</script>
<script>
    function openEditModal(id) {
        let modalEl = document.getElementById('edit' + id);
        if (!modalEl) {
            console.log('Modal not found:', id);
            return;
        }
        let modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
</script>