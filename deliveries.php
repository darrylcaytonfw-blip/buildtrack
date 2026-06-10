<?php
/* deliveries.php - EDIT ONLY + AUDIT HISTORY */
include 'database.php';
include 'audit_helper.php';
$isManagement = isset($_SESSION['role']) && $_SESSION['role'] === 'management';
/* ---------- CREATE ---------- */
if (isset($_POST['save'])) {
    $project_id    = (int)$_POST['project_id'];
    $contractor_id = (int)$_POST['contractor_id'];
    $item_name     = $conn->real_escape_string(trim($_POST['item_name']));
    $ordered_qty   = (float)$_POST['ordered_qty'];
    $delivered_qty = (float)$_POST['delivered_qty'];
    $unit          = $conn->real_escape_string(trim($_POST['unit']));
    $delivery_date = $_POST['delivery_date'];
    $remarks       = $conn->real_escape_string(trim($_POST['remarks']));
    if ($delivered_qty > $ordered_qty) {
        header("Location: ./?link=deliveries.php&over=1");
        exit;
    }
    $conn->query("
        INSERT INTO deliveries(
            project_id, contractor_id, item_name,
            ordered_qty, delivered_qty, unit,
            delivery_date, remarks
        )
        VALUES(
            $project_id, $contractor_id, '$item_name',
            '$ordered_qty', '$delivered_qty', '$unit',
            '$delivery_date', '$remarks'
        )
    ");
    logAction(
        $conn,
        'CREATE',
        'deliveries',
        $conn->insert_id,
        'Added delivery: ' . $item_name
    );
    header("Location: ./?link=deliveries.php&added=1");
    exit;
}
/* ---------- UPDATE ---------- */
if (isset($_POST['update'])) {
    $id            = (int)$_POST['id'];
    $project_id    = (int)$_POST['project_id'];
    $contractor_id = (int)$_POST['contractor_id'];
    $item_name     = $conn->real_escape_string(trim($_POST['item_name']));
    $ordered_qty   = (float)$_POST['ordered_qty'];
    $delivered_qty = (float)$_POST['delivered_qty'];
    $unit          = $conn->real_escape_string(trim($_POST['unit']));
    $delivery_date = $_POST['delivery_date'];
    $remarks       = $conn->real_escape_string(trim($_POST['remarks']));
    if ($delivered_qty > $ordered_qty) {
        header("Location: ./?link=deliveries.php&over=1");
        exit;
    }
    $conn->query("
        UPDATE deliveries SET
            project_id    = $project_id,
            contractor_id = $contractor_id,
            item_name     = '$item_name',
            ordered_qty   = '$ordered_qty',
            delivered_qty = '$delivered_qty',
            unit          = '$unit',
            delivery_date = '$delivery_date',
            remarks       = '$remarks'
        WHERE id = $id
    ");
    logAction(
        $conn,
        'UPDATE',
        'deliveries',
        $id,
        'Edited delivery: ' . $item_name
    );
    header("Location: ./?link=deliveries.php&updated=1");
    exit;
}
/* ---------- DATA ---------- */
$projects = $conn->query("
    SELECT id, project_name
    FROM projects
    ORDER BY project_name
");
$contractors = $conn->query("
    SELECT id, name, contractor_type
    FROM contractors
    ORDER BY name
");
$totalOrdered = $conn->query("
    SELECT IFNULL(SUM(ordered_qty),0) total
    FROM deliveries
")->fetch_assoc()['total'];
$totalDelivered = $conn->query("
    SELECT IFNULL(SUM(delivered_qty),0) total
    FROM deliveries
")->fetch_assoc()['total'];
$completion = ($totalOrdered > 0)
    ? ($totalDelivered / $totalOrdered) * 100
    : 0;
$list = $conn->query("
    SELECT d.*,
           p.project_name,
           c.name contractor_name,
           c.contractor_type
    FROM deliveries d
    LEFT JOIN projects p ON d.project_id=p.id
    LEFT JOIN contractors c ON d.contractor_id=c.id
    ORDER BY d.id DESC
");
?>
<div class="mb-4">
    <!-- HEADER -->
    <div class="text-center mb-4">
        <h2 class="text-white fw-bold mb-1">Deliveries</h2>
        <small class="text-secondary">
            Edit records only + full transaction history
        </small>
    </div>
    <!-- FILTER CARD -->

</div>
<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success py-2">Delivery saved successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-info py-2">Delivery updated successfully.</div>
<?php endif; ?>
<?php if (isset($_GET['over'])): ?>
    <div class="alert alert-warning py-2">
        Delivered quantity cannot exceed ordered quantity.
    </div>
<?php endif; ?>
<!-- STATS -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <small class="text-secondary">Ordered Qty</small>
            <h3><?= number_format($totalOrdered, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <small class="text-secondary">Delivered Qty</small>
            <h3 class="text-success"><?= number_format($totalDelivered, 2) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <small class="text-secondary">Completion</small>
            <h3 class="text-warning"><?= number_format($completion, 2) ?>%</h3>
        </div>
    </div>
</div>
<?php if (!$isManagement): ?>
    <!-- ADD FORM -->
    <div class="card p-4 mb-4">

        <form method="post" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="small text-secondary mb-1">Project</label>
                <select name="project_id" class="form-select select2" required>
                    <option value="">Select</option>
                    <?php while ($p = $projects->fetch_assoc()): ?>
                        <option value="<?= $p['id'] ?>"><?= $p['project_name'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-secondary mb-1">Supplier</label>
                <select name="contractor_id" class="form-select select2" required>
                    <option value="">Select</option>
                    <?php while ($c = $contractors->fetch_assoc()): ?>
                        <option value="<?= $c['id'] ?>">
                            <?= $c['name'] ?> (<?= $c['contractor_type'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-secondary mb-1">Item</label>
                <input type="text" name="item_name" class="form-control" required>
            </div>
            <div class="col-md-1">
                <label class="small text-secondary mb-1">Ordered</label>
                <input type="number" step="0.01" id="ordered"
                    name="ordered_qty" class="form-control"
                    oninput="calcDelivery()" required>
            </div>
            <div class="col-md-1">
                <label class="small text-secondary mb-1">Delivered</label>
                <input type="number" step="0.01" id="delivered"
                    name="delivered_qty" class="form-control"
                    oninput="calcDelivery()" required>
            </div>
            <div class="col-md-1">
                <label class="small text-secondary mb-1">Balance</label>
                <input type="text" id="balance_preview"
                    class="form-control" readonly>
            </div>
            <div class="col-md-1">
                <label class="small text-secondary mb-1">%</label>
                <input type="text" id="percent_preview"
                    class="form-control" readonly>
            </div>
            <div class="col-md-1">
                <label class="small text-secondary mb-1">Unit</label>
                <input type="text" name="unit"
                    class="form-control">
            </div>
            <div class="col-md-2">
                <label class="small text-secondary mb-1">Date</label>
                <input type="date" name="delivery_date"
                    class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="small text-secondary mb-1">Remarks</label>
                <input type="text" name="remarks"
                    class="form-control">
            </div>
            <div class="col-md-1">
                <button name="save" class="btn btn-gold w-100">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>
<div class="card shadow-sm border-0 rounded-4 p-4 w-100 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="mb-0 fw-bold text-light">Advanced Search Filters</h5>
            <small class="text-secondary">Filter deliveries instantly</small>
        </div>
        <div class="badge bg-primary fs-6 px-3 py-2 rounded-pill">
            Results: <span id="resultCount">0</span>
        </div>
    </div>
    <!-- ROW 1 -->
    <div class="row g-3 mb-2">
        <div class="col-lg-4 col-md-6">
            <label class="small text-secondary mb-1">Keyword</label>
            <input type="text"
                id="fKeyword"
                class="form-control rounded-3"
                placeholder="Item / Contractor / Project"
                onkeyup="advancedFilter()">
        </div>
        <div class="col-lg-4 col-md-6">
            <label class="small text-secondary mb-1">Project</label>
            <select id="fProject"
                class="form-select rounded-3"
                onchange="advancedFilter()">
                <option value="">All Projects</option>
                <?php
                $fp = $conn->query("SELECT project_name FROM projects ORDER BY project_name");
                while ($p = $fp->fetch_assoc()):
                ?>
                    <option value="<?= strtolower($p['project_name']) ?>">
                        <?= $p['project_name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="col-lg-4 col-md-6">
            <label class="small text-secondary mb-1">Supplier</label>
            <select id="fSupplier"
                class="form-select rounded-3"
                onchange="advancedFilter()">
                <option value="">All Suppliers</option>
                <?php
                $fc = $conn->query("SELECT name FROM contractors ORDER BY name");
                while ($c = $fc->fetch_assoc()):
                ?>
                    <option value="<?= strtolower($c['name']) ?>">
                        <?= $c['name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    <!-- ROW 2 -->
    <div class="row g-3 align-items-end">
        <div class="col-lg-3 col-md-6">
            <label class="small text-secondary mb-1">From Date</label>
            <input type="date"
                id="fFrom"
                class="form-control rounded-3"
                onchange="advancedFilter()">
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="small text-secondary mb-1">To Date</label>
            <input type="date"
                id="fTo"
                class="form-control rounded-3"
                onchange="advancedFilter()">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="small text-secondary mb-1">Min %</label>
            <input type="number"
                id="fMinPct"
                class="form-control rounded-3"
                placeholder="0"
                oninput="advancedFilter()">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="small text-secondary mb-1">Max %</label>
            <input type="number"
                id="fMaxPct"
                class="form-control rounded-3"
                placeholder="100"
                oninput="advancedFilter()">
        </div>
        <div class="col-lg-2 col-md-12 d-grid">
            <button type="button"
                class="btn btn-outline-secondary rounded-3"
                onclick="resetAdvanced()">
                <i class="bi bi-arrow-counterclockwise me-1"></i>
                Reset
            </button>
        </div>
    </div>
</div>
<!-- TABLE -->
<div class="card p-3">
    <div>
        <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
            onclick="toggleFullscreen(this)">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
        <h4 class="fw-bold mb-1">
            Delivery Records
        </h4>
        <small class="text-secondary">
            View and edit delivery records. Click pencil icon to edit.
        </small>

    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="deliveryTable">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Contractor</th>
                    <th>Item</th>
                    <th>Ordered</th>
                    <th>Delivered</th>
                    <th>Balance</th>
                    <th>%</th>
                    <th>Date</th>
                    <th width="90">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $list->fetch_assoc()): ?>
                    <?php
                    $balance = $r['ordered_qty'] - $r['delivered_qty'];
                    $percent = ($r['ordered_qty'] > 0)
                        ? ($r['delivered_qty'] / $r['ordered_qty']) * 100
                        : 0;
                    ?>
                    <tr>
                        <td><?= $r['project_name'] ?></td>
                        <td>
                            <?= $r['contractor_name'] ?><br>
                            <small class="text-secondary"><?= $r['contractor_type'] ?></small>
                        </td>
                        <td><?= $r['item_name'] ?></td>
                        <td><?= $r['ordered_qty'] ?> <?= $r['unit'] ?></td>
                        <td><?= $r['delivered_qty'] ?> <?= $r['unit'] ?></td>
                        <td class="text-warning"><?= $balance ?> <?= $r['unit'] ?></td>
                        <td>
                            <div class="progress" style="height:8px;background:rgba(255,255,255,.08)">
                                <div class="progress-bar bg-success"
                                    style="width:<?= min(100, $percent) ?>%"></div>
                            </div>
                            <small><?= number_format($percent, 0) ?>%</small>
                        </td>
                        <td><?= $r['delivery_date'] ?></td>
                        <td>
                            <?php if (!$isManagement): ?>
                                <a href="#"
                                    class="btn btn-sm btn-outline-dark"
                                    data-bs-toggle="modal"
                                    data-bs-target="#edit<?= $r['id'] ?>"
                                    onclick="return false;">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <!-- EDIT MODAL -->
                    <div class="modal fade" id="edit<?= $r['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">
                                <form method="post">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Delivery</h5>
                                        <button type="button" class="btn-close"
                                            data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="small">Item</label>
                                                <input type="text" name="item_name"
                                                    class="form-control"
                                                    value="<?= $r['item_name'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small">Ordered</label>
                                                <input type="number" step="0.01"
                                                    name="ordered_qty"
                                                    class="form-control"
                                                    value="<?= $r['ordered_qty'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small">Delivered</label>
                                                <input type="number" step="0.01"
                                                    name="delivered_qty"
                                                    class="form-control"
                                                    value="<?= $r['delivered_qty'] ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <label class="small">Unit</label>
                                                <input type="text" name="unit"
                                                    class="form-control"
                                                    value="<?= $r['unit'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small">Date</label>
                                                <input type="date" name="delivery_date"
                                                    class="form-control"
                                                    value="<?= $r['delivery_date'] ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small">Remarks</label>
                                                <input type="text" name="remarks"
                                                    class="form-control"
                                                    value="<?= $r['remarks'] ?>">
                                            </div>
                                            <input type="hidden" name="project_id" value="<?= $r['project_id'] ?>">
                                            <input type="hidden" name="contractor_id" value="<?= $r['contractor_id'] ?>">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button name="update" class="btn btn-gold">
                                            Save Changes
                                        </button>
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
<!-- EASY TO READ HISTORY -->
<div class="card p-4 mt-4">

    <div class="mb-3">
        <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
            onclick="toggleFullscreen(this)">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
        <h4 class="fw-bold mb-1">
            Delivery History
        </h4>
        <small class="text-secondary">
            View recent changes to delivery records. Full audit history available upon request.
        </small>

    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>ID Number</th>
                    <th>Full Name</th>
                    <th>What Happened</th>
                    <th>Item No.</th>
                    <th>Notes</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $logs = $conn->query("
                    SELECT user_id, username, action, record_id, details, log_time
                    FROM audit_logs
                    WHERE module_name='deliveries'
                    ORDER BY id DESC
                    LIMIT 20
                ");
                while ($log = $logs->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $log['user_id'] ?></td>
                        <td class="fw-semibold">
                            <?= ucfirst($log['username']) ?>
                        </td>
                        <td>
                            <?php
                            if ($log['action'] == 'CREATE') {
                                echo 'Added Record';
                            } elseif ($log['action'] == 'UPDATE') {
                                echo 'Changed Record';
                            } else {
                                echo $log['action'];
                            }
                            ?>
                        </td>
                        <td><?= $log['record_id'] ?></td>
                        <td><?= $log['details'] ?></td>
                        <td>
                            <?= date('M d, Y', strtotime($log['log_time'])) ?><br>
                            <small class="text-secondary">
                                <?= date('g:i A', strtotime($log['log_time'])) ?>
                            </small>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($logs->num_rows == 0): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">
                            No history found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- OWNER SUPPLIED MATERIALS TRACKER -->
<div class="card p-4 mt-4 shadow-sm" id="owner_supplied_tracker">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="mb-3">
            <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
                onclick="toggleFullscreen(this)">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>
            <h4 class="fw-bold mb-1">
                Delivery / Owner-Supplied Materials Tracker
            </h4>
            <small class="text-secondary">
                Track delivery status of critical owner-supplied materials
            </small>

        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Source</th>
                    <th>Contractor Affected</th>
                    <th>Tower / Area</th>
                    <th>Required Date</th>
                    <th>Actual / Expected Delivery</th>
                    <th>Status</th>
                    <th>Value</th>
                    <th>Impact</th>
                    <th>CEO Decision</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $tracker = $conn->query("
                    SELECT
                        d.*,
                        p.project_name,
                        c.name contractor_name
                    FROM deliveries d
                    LEFT JOIN projects p
                        ON d.project_id = p.id
                    LEFT JOIN contractors c
                        ON d.contractor_id = c.id
                    ORDER BY d.delivery_date ASC
                ");
                while ($r = $tracker->fetch_assoc()):
                    $balance =
                        $r['ordered_qty'] -
                        $r['delivered_qty'];
                    $percent =
                        $r['ordered_qty'] > 0
                        ? ($r['delivered_qty'] / $r['ordered_qty']) * 100
                        : 0;
                    /* STATUS */
                    if ($percent >= 100) {
                        $status = 'Delivered';
                        $badge  = 'success';
                    } elseif ($percent >= 50) {
                        $status = 'Partial';
                        $badge  = 'warning';
                    } else {
                        $status = 'Critical';
                        $badge  = 'danger';
                    }
                    /* VALUE */
                    $estimatedValue =
                        $r['ordered_qty'] * 5000;
                    /* IMPACT */
                    if ($status == 'Delivered') {
                        $impact =
                            'No operational delay';
                        $decision =
                            'Continue monitoring';
                    } elseif ($status == 'Partial') {
                        $impact =
                            'Possible manpower slowdown';
                        $decision =
                            'Follow-up delivery by floor';
                    } else {
                        $impact =
                            'Critical construction delay';
                        $decision =
                            'Expedite supplier / owner approval';
                    }
                ?>
                    <tr>
                        <td class="fw-semibold">
                            <?= $r['item_name'] ?>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                Owner-Supplied
                            </span>
                        </td>
                        <td>
                            <?= $r['contractor_name'] ?>
                        </td>
                        <td>
                            <?= $r['project_name'] ?>
                        </td>
                        <td>
                            <?= date(
                                'd-M-y',
                                strtotime($r['delivery_date'])
                            ) ?>
                        </td>
                        <td>
                            <?php if ($percent >= 100): ?>
                                <?= date(
                                    'd-M-y',
                                    strtotime($r['delivery_date'])
                                ) ?>
                            <?php else: ?>
                                No ETA
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= $status ?>
                            </span>
                        </td>
                        <td class="fw-semibold">
                            ₱<?= number_format($estimatedValue, 2) ?>
                        </td>
                        <td style="min-width:220px;">
                            <?= $impact ?>
                        </td>
                        <td style="min-width:260px;">
                            <?= $decision ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    function advancedFilter() {
        let keyword = val('fKeyword');
        let project = val('fProject');
        let supplier = val('fSupplier');
        let from = val('fFrom');
        let to = val('fTo');
        let minPct = parseFloat(val('fMinPct')) || 0;
        let maxPct = parseFloat(val('fMaxPct')) || 999;
        let rows = document.querySelectorAll("#deliveryTable tbody tr");
        let visible = 0;
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            let rowProject = row.children[0].innerText.toLowerCase();
            let rowSupplier = row.children[1].innerText.toLowerCase();
            let rowDate = row.children[7].innerText.trim();
            let pctText = row.children[6].innerText.replace('%', '').trim();
            let pct = parseFloat(pctText) || 0;
            let show = true;
            if (keyword && !text.includes(keyword)) show = false;
            if (project && rowProject !== project) show = false;
            if (supplier && !rowSupplier.includes(supplier)) show = false;
            if (from && rowDate < from) show = false;
            if (to && rowDate > to) show = false;
            if (pct < minPct || pct > maxPct) show = false;
            row.style.display = show ? "" : "none";
            if (show) visible++;
        });
        document.getElementById("resultCount").innerText = visible;
    }

    function resetAdvanced() {
        [
            'fKeyword', 'fProject', 'fSupplier',
            'fFrom', 'fTo', 'fMinPct', 'fMaxPct'
        ].forEach(id => {
            document.getElementById(id).value = '';
        });
        advancedFilter();
    }

    function val(id) {
        return document.getElementById(id).value.toLowerCase();
    }
    window.onload = advancedFilter;

    function calcDelivery() {
        let ordered = parseFloat(document.getElementById('ordered').value) || 0;
        let delivered = parseFloat(document.getElementById('delivered').value) || 0;
        let balance = ordered - delivered;
        let percent = ordered > 0 ? (delivered / ordered) * 100 : 0;
        document.getElementById('balance_preview').value = balance.toFixed(2);
        document.getElementById('percent_preview').value = percent.toFixed(2) + '%';
        if (delivered > ordered) {
            document.getElementById('percent_preview').value = 'Over!';
        }
    }
</script>