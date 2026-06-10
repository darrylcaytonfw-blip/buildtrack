<?php
include 'database.php';
include 'audit_helper.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) exit('Invalid contractor.');

function zoneOptions($floors, $ph)
{
    $html = '';
    for ($i = 1; $i <= $floors; $i++) {
        $html .= '<label class="dropdown-item"><input type="checkbox" value="Floor ' . $i . '"> Floor ' . $i . '</label>';
    }

    for ($x = 1; $x <= $ph; $x++) {
        $html .= '<label class="dropdown-item"><input type="checkbox" value="Penthouse ' . $x . '"> Penthouse ' . $x . '</label>';
    }

    return $html;
}

/* SAVE HEADER */
if (isset($_POST['save_header'])) {

    $stmt = $conn->prepare("
        UPDATE contractors SET
        name=?,
        location=?,
        contact=?,
        contact_person=?,
        target_contract=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "ssssdi",
        $_POST['name'],
        $_POST['location'],
        $_POST['contact'],
        $_POST['contact_person'],
        $_POST['target_contract'],
        $id
    );
    $stmt->execute();

    header("Location: ./?link=contractor_profile.php&id=$id&saved=1");
    exit;
}

/* SAVE ITEM */
if (isset($_POST['save_item'])) {

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {

        $project_id         = (int)$_POST['project_id'];
        $activity_id        = (int)$_POST['activity_id'];
        $zone_floor         = trim($_POST['zone_floor']);
        $price              = (float)($_POST['price'] ?? 0);
        $downpayment        = (float)($_POST['downpayment'] ?? 0);
        $sqm                = (float)($_POST['sqm'] ?? 0);
        $actual             = (float)($_POST['actual'] ?? 0);
        $start_date         = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $target_date        = !empty($_POST['target_date']) ? $_POST['target_date'] : null;
        $number_of_payments = (int)($_POST['number_of_payments'] ?? 1);
        $contract_amount    = (float)($_POST['contract_amount'] ?? 0);

        $meta = $conn->query("
            SELECT wa.activity_name, wc.category_name
            FROM work_activities wa
            LEFT JOIN work_categories wc ON wa.category_id = wc.id
            WHERE wa.id = $activity_id
            LIMIT 1
        ")->fetch_assoc();

        $work_category = $meta['category_name'] ?? '';
        $activity_name = $meta['activity_name'] ?? '';

        $sql = "
            INSERT INTO contractor_items(
                contractor_id,
                project_id,
                work_category,
                activity_id,
                activity_name,
                price,
                downpayment,
                location_name,
                zone_floor,
                sqm,
                actual,
                start_date,
                target_date,
                number_of_payments,
                contract_amount
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "iisisddssddssid",
            $id,
            $project_id,
            $work_category,
            $activity_id,
            $activity_name,
            $price,
            $downpayment,
            $zone_floor,
            $zone_floor,
            $sqm,
            $actual,
            $start_date,
            $target_date,
            $number_of_payments,
            $contract_amount
        );

        $stmt->execute();

        header("Location: ./?link=contractor_profile.php&id=$id&added=1");
        exit;
    } catch (Throwable $e) {

        echo "<div class='alert alert-danger'>";
        echo "<strong>Save Failed:</strong><br>";
        echo $e->getMessage();
        echo "</div>";
    }
}

$contractor = $conn->query("
    SELECT * FROM contractors WHERE id=$id LIMIT 1
")->fetch_assoc();

$projects = $conn->query("
    SELECT * FROM projects ORDER BY project_name
");

$activities = $conn->query("
    SELECT wa.id, wa.activity_name, wc.category_name
    FROM work_activities wa
    LEFT JOIN work_categories wc ON wa.category_id=wc.id
    ORDER BY wc.category_name, wa.activity_name
");

$items = $conn->query("
    SELECT ci.*, p.project_name
    FROM contractor_items ci
    LEFT JOIN projects p ON ci.project_id=p.id
    WHERE ci.contractor_id=$id
    ORDER BY ci.id DESC
");

$total = $conn->query("
    SELECT IFNULL(SUM(contract_amount),0) total
    FROM contractor_items
    WHERE contractor_id=$id
")->fetch_assoc()['total'];
?>

<style>
    .modern-table thead th {
        background: linear-gradient(135deg, #f8fafc, #eef2f7);
        color: #0f172a;
        border: none;
        padding: 16px;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .3px;
        white-space: nowrap;
        border-bottom: 1px solid #e5e7eb;
    }

    .modern-table tbody td {
        padding: 18px 14px;
        vertical-align: top;
        background: #ffffff;
        color: #1e293b;
        border-color: #e5e7eb;
    }

    .modern-table tbody tr:nth-child(even) td {
        background: #f8fafc;
    }

    .modern-table tbody tr:hover td {
        background: #eef6ff;
        transition: .2s ease;
    }

    .modern-table tfoot td {
        background: #f1f5f9;
        color: #0f172a;
        padding: 18px 14px;
        border-top: 2px solid #dbeafe;
    }

    .zone-box {
        max-width: 420px;
        line-height: 1.7;
        color: #334155;
        word-break: break-word;
    }

    .card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 10px 25px rgba(15, 23, 42, .06);
    }

    .zone-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        max-width: 420px;
    }

    .floor-chip {
        display: inline-block;
        padding: 3px 6px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        white-space: nowrap;
    }

    .floor-chip:hover {
        background: #dbeafe;
        color: #1e3a8a;
        transition: .2s ease;
    }

    .date-stack {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .date-card {
        border-radius: 14px;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }

    .start-date {
        background: linear-gradient(135deg, #ecfeff, #f0fdfa);
        border-color: #a5f3fc;
    }

    .target-date {
        background: linear-gradient(135deg, #fff7ed, #fffbeb);
        border-color: #fde68a;
    }

    .date-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: #64748b;
        margin-bottom: 2px;
    }

    .date-value {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }
</style>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-white">Contractor Profile</h2>

    <a href="./?link=contractors.php" class="btn btn-outline-light">
        Back
    </a>
</div>

<a href="export_contractor_pdf.php?id=<?= $id ?>" class="btn btn-danger mb-4">
    Export PDF
</a>

<?php if(!$isManagement): ?>

<div class="card p-4 mb-4">
    <form method="post" class="row g-3">

        <div class="col-md-3">
            <label>Name</label>
            <input name="name" class="form-control" value="<?= $contractor['name'] ?>">
        </div>

        <div class="col-md-3">
            <label>Address</label>
            <input name="location" class="form-control" value="<?= $contractor['location'] ?>">
        </div>

        <div class="col-md-2">
            <label>Contact</label>
            <input name="contact" class="form-control" value="<?= $contractor['contact'] ?>">
        </div>
        <div class="col-md-2">
            <label>Contact Person</label>
            <input name="contact_person" class="form-control" value="<?= $contractor['contact_person'] ?>">
        </div>
        <div class="col-md-2">
            <label>Target Contract</label>

            <input type="hidden"
                name="target_contract"
                id="target_contract"
                value="<?= $contractor['target_contract'] ?>">

            <input type="text"
                id="target_contract_view"
                class="form-control"
                autocomplete="off">
        </div>



        <div class="col-12">
            <button name="save_header" class="btn btn-gold">Save Profile</button>
        </div>

    </form>
</div>

<div class="card p-4 mb-4">
    <form method="post" class="row g-3">

        <div class="col-md-3">
            <label>Project</label>
            <select name="project_id" id="projectSelect" class="form-select no-select2" required>
                <option value="">Select Project</option>

                <?php while ($p = $projects->fetch_assoc()): ?>
                    <option
                        value="<?= $p['id'] ?>"
                        data-floors="<?= $p['floor_count'] ?>"
                        data-ph="<?= $p['penthouse_count'] ?>">
                        <?= $p['project_name'] ?> (<?= $p['floor_count'] ?> Floors)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label>Activity</label>
            <select name="activity_id" class="form-select" required>
                <option value="">Select</option>
                <?php while ($a = $activities->fetch_assoc()): ?>
                    <option value="<?= $a['id'] ?>">
                        <?= $a['category_name'] ?> / <?= $a['activity_name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>


        <div class="col-md-2">
            <label>Zone/Floor</label>

            <div class="dropdown">
                <button class="btn btn-light w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    Select Floor
                </button>

                <div class="dropdown-menu p-2 w-100" id="zoneBox" style="max-height:320px;overflow:auto;">
                    <small class="text-muted">Select project first</small>
                </div>
            </div>

            <input type="hidden" name="zone_floor" id="zone_floor">
        </div>

        <div class="col-md-2">
            <label>Price</label>

            <input type="hidden" name="price" id="price">

            <input type="text"
                id="price_view"
                class="form-control money-input"
                autocomplete="off">
        </div>

        <div class="col-md-2">
            <label>Downpayment</label>

            <input type="hidden" name="downpayment" id="downpayment">

            <input type="text"
                id="downpayment_view"
                class="form-control money-input"
                autocomplete="off">
        </div>
        <div class="col-md-2">
            <label>SQM</label>
            <input type="number" step="0.01" name="sqm" class="form-control">
        </div>

        <div class="col-md-2">
            <label>Actual</label>
            <input type="number" step="0.01" name="actual" class="form-control">
        </div>

        <div class="col-md-2">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control">
        </div>

        <div class="col-md-2">
            <label>Target Date</label>
            <input type="date" name="target_date" class="form-control">
        </div>

        <div class="col-md-2">
            <label>No. Payments</label>
            <input type="number" name="number_of_payments" class="form-control" value="1">
        </div>

        <div class="col-md-2">
            <label>Contract Amount</label>

            <input type="hidden" name="contract_amount" id="contract_amount">

            <input type="text"
                id="contract_amount_view"
                class="form-control money-input"
                autocomplete="off">
        </div>
        <div class="col-12">
            <button name="save_item" class="btn btn-gold">
                Add Contract Item
            </button>
        </div>

    </form>
</div>
<?php endif; ?>
<div class="card border-0 shadow-lg rounded-4 overflow-hidden">

    <div class="px-4 py-3 border-bottom bg-light bg-gradient">
        <h4 class="mb-0 text-dark fw-bold">
            <i class="bi bi-table me-2 text-warning"></i>
            Contract Breakdown
        </h4>
    </div>

    <div class="table-responsive">

        <table class="table align-middle mb-0 modern-table">

            <thead>
                <tr>
                    <th>Project</th>
                    <th>Activity</th>
                    <th>Zone / Floor</th>
                    <th>Dates</th>
                    <th class="text-center">Payments</th>
                    <th class="text-end">Contract</th>
                </tr>
            </thead>

            <tbody>
                <?php while ($r = $items->fetch_assoc()): ?>
                    <tr>

                        <td class="fw-semibold">
                            <?= $r['project_name'] ?>
                        </td>

                        <td>
                            <span class="badge bg-primary-subtle text-dark px-3 py-2 rounded-pill">
                                <?= $r['activity_name'] ?>
                            </span>
                        </td>

                        <td style="min-width:240px;">
                            <div class="zone-list">
                                <?php
                                $floors = array_filter(array_map('trim', explode(',', $r['zone_floor'])));
                                foreach ($floors as $f):
                                ?>
                                    <span class="floor-chip"><?= $f ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>

                        <td style="min-width:150px;">
                            <div class="date-stack">

                                <div class="date-card start-date">
                                    <div class="date-label">Start Date</div>
                                    <div class="date-value">
                                        <?= date('M d, Y', strtotime($r['start_date'])) ?>
                                    </div>
                                </div>

                                <div class="date-card target-date">
                                    <div class="date-label">Target Date</div>
                                    <div class="date-value">
                                        <?= date('M d, Y', strtotime($r['target_date'])) ?>
                                    </div>
                                </div>

                            </div>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                                <?= $r['number_of_payments'] ?>
                            </span>
                        </td>

                        <td class="text-end fw-bold text-success">
                            ₱<?= number_format($r['contract_amount'], 2) ?>
                        </td>

                    </tr>
                <?php endwhile; ?>
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="5" class="fw-bold fs-5 text-uppercase">
                        Total
                    </td>

                    <td class="text-end fw-bold fs-4 text-warning">
                        ₱<?= number_format($total, 2) ?>
                    </td>
                </tr>
            </tfoot>

        </table>

    </div>
</div>

<script>
    (function() {

        const project = document.getElementById('projectSelect');
        const zoneBox = document.getElementById('zoneBox');
        const zoneVal = document.getElementById('zone_floor');

        if (!project || !zoneBox || !zoneVal) return;

        function buildFloors() {

            const selected = project.options[project.selectedIndex];

            const floors = parseInt(selected.dataset.floors || 0);
            const ph = parseInt(selected.dataset.ph || 0);

            let html = '';

            // SELECT ALL
            html += `
            <label class="dropdown-item fw-bold border-bottom mb-1">
                <input type="checkbox" id="allFloors" class="me-2">
                All Floors
            </label>
        `;

            // FLOORS
            for (let i = 1; i <= floors; i++) {

                if (i === 13) continue;

                html += `
                <label class="dropdown-item">
                    <input type="checkbox" class="zf me-2" value="Floor ${i}">
                    Floor ${i}
                </label>
            `;
            }

            // PENTHOUSE
            for (let x = 1; x <= ph; x++) {
                html += `
                <label class="dropdown-item">
                    <input type="checkbox" class="zf me-2" value="Penthouse ${x}">
                    Penthouse ${x}
                </label>
            `;
            }

            zoneBox.innerHTML = html;
        }

        function updateValue() {
            let vals = [];

            document.querySelectorAll('.zf:checked').forEach(cb => {
                vals.push(cb.value);
            });

            zoneVal.value = vals.join(', ');
        }

        project.onchange = buildFloors;

        document.addEventListener('change', function(e) {

            // ALL FLOORS
            if (e.target.id === 'allFloors') {

                const checked = e.target.checked;

                document.querySelectorAll('.zf').forEach(cb => {
                    cb.checked = checked;
                });

                updateValue();
            }

            // INDIVIDUAL FLOOR
            if (e.target.classList.contains('zf')) {

                updateValue();

                const all = document.getElementById('allFloors');
                const total = document.querySelectorAll('.zf').length;
                const marked = document.querySelectorAll('.zf:checked').length;

                if (all) {
                    all.checked = (total === marked);
                }
            }

        });

    })();

    (function() {

        const real = document.getElementById('target_contract');
        const view = document.getElementById('target_contract_view');

        if (!real || !view) return;

        function formatPeso(val) {
            return Number(val || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function rawNumber(str) {
            return str.replace(/,/g, '').replace(/[^\d.]/g, '');
        }

        // initial display
        view.value = formatPeso(real.value);

        view.addEventListener('input', function() {
            const clean = rawNumber(this.value);
            real.value = clean;
        });

        view.addEventListener('blur', function() {
            this.value = formatPeso(real.value);
        });

        view.addEventListener('focus', function() {
            this.value = real.value;
        });

    })();

    (function() {

        function formatPeso(val) {
            return Number(val || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function cleanNumber(str) {
            return str.replace(/,/g, '').replace(/[^\d.]/g, '');
        }

        function bindMoney(realId, viewId) {

            const real = document.getElementById(realId);
            const view = document.getElementById(viewId);

            if (!real || !view) return;

            view.value = formatPeso(real.value);

            view.addEventListener('input', function() {
                real.value = cleanNumber(this.value);
            });

            view.addEventListener('focus', function() {
                this.value = real.value;
            });

            view.addEventListener('blur', function() {
                this.value = formatPeso(real.value);
            });
        }

        bindMoney('price', 'price_view');
        bindMoney('downpayment', 'downpayment_view');
        bindMoney('contract_amount', 'contract_amount_view');

    })();
</script>