<?php
include 'database.php';

$current_role = $_SESSION['role'] ?? '';

$contractor_positions = [
    "CEO",
    "Engineer",
    "Project Manager",
    "Construction Manager",
    "Staff"
];

/* ===============================
HELPER
=============================== */

function uploadPhoto($field)
{
    if (empty($_FILES[$field]['name'])) return '';

    $folder = 'uploads/staff/';

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    $name = time() . '_' . basename($_FILES[$field]['name']);

    move_uploaded_file($_FILES[$field]['tmp_name'], $folder . $name);

    return $name;
}

/* ===============================
AUTO SYSTEM ID
=============================== */

$year = date('Y');

$q = $conn->query("
    SELECT system_id_no
    FROM contractor_staff
    WHERE system_id_no LIKE 'CTR-$year-%'
    ORDER BY id DESC
    LIMIT 1
");

$nextNo = 1;

if ($q && $q->num_rows > 0) {

    $row = $q->fetch_assoc();

    if (preg_match('/CTR-\d{4}-(\d+)/', $row['system_id_no'], $m)) {
        $nextNo = ((int)$m[1]) + 1;
    }
}

$autoSystemId = 'CTR-' . $year . '-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);

/* ===============================
SAVE
=============================== */

if (isset($_POST['save'])) {

    $contractor_id      = (int)$_POST['contractor_id'];
    $first_name         = trim($_POST['first_name']);
    $last_name          = trim($_POST['last_name']);
    $middle_name        = trim($_POST['middle_name']);
    $contact_no         = trim($_POST['contact_no']);
    $system_id_no       = trim($_POST['system_id_no']);
    $position_trade     = trim($_POST['position_trade']);
    $department         = trim($_POST['department']);
    $employment_type    = trim($_POST['employment_type']);
    $project_assignment = implode(', ', $_POST['project_assignment'] ?? []);
    $date_hired         = $_POST['date_hired'];
    $contract_start     = $_POST['contract_start'];
    $contract_end       = $_POST['contract_end'];
    $employment_status  = trim($_POST['employment_status']);

    $photo = uploadPhoto('photo');

    $check = $conn->prepare("
        SELECT id
        FROM users
        WHERE username = ?
        LIMIT 1
    ");

    $check->bind_param("s", $contact_no);
    $check->execute();

    $res = $check->get_result();

    $user_id = 0;

    if ($res->num_rows > 0) {

        $urow = $res->fetch_assoc();
        $user_id = (int)$urow['id'];
    } else {

        $pass   = password_hash("123456", PASSWORD_DEFAULT);
        $role   = strtolower(str_replace(' ', '_', $position_trade));
        $status = "active";

        $u = $conn->prepare("
            INSERT INTO users(username,password,role,status)
            VALUES(?,?,?,?)
        ");

        $u->bind_param("ssss", $contact_no, $pass, $role, $status);
        $u->execute();

        $user_id = $conn->insert_id;
    }
    $registry_type = 'contractor';
    $stmt = $conn->prepare("
        INSERT INTO contractor_staff(
            user_id,
            contractor_id,
            first_name,
            last_name,
            middle_name,
            contact_no,
            system_id_no,
            photo,
            position_trade,
            department,
            employment_type,
            project_assignment,
            date_hired,
            contract_start,
            contract_end,
            employment_status,
            registry_type
        )
        VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "iisssssssssssssss",
        $user_id,
        $contractor_id,
        $first_name,
        $last_name,
        $middle_name,
        $contact_no,
        $system_id_no,
        $photo,
        $position_trade,
        $department,
        $employment_type,
        $project_assignment,
        $date_hired,
        $contract_start,
        $contract_end,
        $employment_status,
        $registry_type
    );

    $stmt->execute();

    header("Location: ./?link=contractor_staff.php&ok=1");
    exit;
}

/* ===============================
ROLE FILTERING
=============================== */

$where = "AND s.registry_type='contractor'";

if ($current_role != '' && $current_role != 'system_admin') {

    $role_map = [
        'ceo' => 'CEO',
        'engineer' => 'Engineer',
        'project_manager' => 'Project Manager',
        'construction_manager' => 'Construction Manager',
        'staff' => 'Staff'
    ];

    $formatted_role = $role_map[$current_role] ?? '';

    if ($formatted_role != '') {

        $safe_role = $conn->real_escape_string($formatted_role);

        $where .= " AND s.position_trade = '$safe_role'";
    }
}

$contractors = $conn->query("
    SELECT id, name
    FROM contractors
    ORDER BY name
");

$list = $conn->query("
    SELECT
        s.*,
        c.name AS contractor_name,
        u.username,
        u.role,
        u.status
    FROM contractor_staff s
    LEFT JOIN contractors c ON s.contractor_id = c.id
    LEFT JOIN users u ON s.user_id = u.id
    WHERE u.role != 'system_admin'
    $where
    ORDER BY s.id DESC
");
?>
<style>
    .staff-card {
        border: 0;
        border-radius: 22px;
        box-shadow: 0 12px 25px rgba(0, 0, 0, .08);
    }

    .staff-photo {
        width: 55px;
        height: 55px;
        object-fit: cover;
        border-radius: 50%;
    }
</style>
<div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">

        <div>
            <h2 class="text-white fw-bold mb-1">Contractor Staff Registry</h2>
            <small class="text-secondary">Ultimate HR Module</small>
        </div>

        <div style="min-width:280px; max-width:320px; width:100%;">
            <input type="text"
                id="staffSearch"
                class="form-control"
                placeholder="Search staff..."
                onkeyup="filterStaff(this.value)">
        </div>

    </div>
    <form method="post" enctype="multipart/form-data" class="row g-3">

        <div class="col-md-4">
            <label>First Name</label>
            <input name="first_name" class="form-control" required>
        </div>

        <div class="col-md-4">
            <label>Last Name</label>
            <input name="last_name" class="form-control" required>
        </div>

        <div class="col-md-4">
            <label>Middle Name</label>
            <input name="middle_name" class="form-control">
        </div>

        <div class="col-md-4">
            <label>Contractor</label>

            <select name="contractor_id" class="form-select" required>

                <option value="">Select</option>

                <?php while ($c = $contractors->fetch_assoc()): ?>

                    <option value="<?= $c['id'] ?>">
                        <?= $c['name'] ?>
                    </option>

                <?php endwhile; ?>

            </select>
        </div>

        <div class="col-md-4">
            <label>Contact</label>
            <input name="contact_no" class="form-control">
        </div>

        <div class="col-md-4">
            <label>System ID</label>

            <input type="text"
                name="system_id_no"
                class="form-control"
                value="<?= $autoSystemId ?>"
                readonly>
        </div>

        <div class="col-md-4">
            <label>Photo</label>
            <input type="file" name="photo" class="form-control">
        </div>

        <div class="col-md-4">
            <label>Position</label>

            <select name="position_trade" class="form-select" required>

                <option value="">Select Position</option>

                <?php foreach ($contractor_positions as $pos): ?>

                    <option value="<?= $pos ?>">
                        <?= $pos ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div class="col-md-4">
            <label>Department</label>

            <select name="department" class="form-select">
                <option>Engineering</option>
                <option>Operations</option>
                <option>Safety</option>
                <option>Admin</option>
                <option>Finance</option>
            </select>
        </div>

        <div class="col-md-4">
            <label>Employment Type</label>

            <select name="employment_type" class="form-select">
                <option>Regular</option>
                <option>Project-Based</option>
                <option>Contractual</option>
                <option>Subcontracted</option>
            </select>
        </div>

        <div class="col-md-8">
            <label>Projects</label>

            <select name="project_assignment[]" class="form-select" multiple>

                <?php
                $pr = $conn->query("
                    SELECT project_name
                    FROM projects
                    ORDER BY project_name
                ");

                while ($p = $pr->fetch_assoc()):
                ?>

                    <option value="<?= $p['project_name'] ?>">
                        <?= $p['project_name'] ?>
                    </option>

                <?php endwhile; ?>

            </select>
        </div>

        <div class="col-md-4">
            <label>Date Hired</label>
            <input type="date" name="date_hired" class="form-control">
        </div>

        <div class="col-md-4">
            <label>Contract Start</label>
            <input type="date" name="contract_start" class="form-control">
        </div>

        <div class="col-md-4">
            <label>Contract End</label>
            <input type="date" name="contract_end" class="form-control">
        </div>

        <div class="col-md-4">
            <label>Status</label>

            <select name="employment_status" class="form-select">
                <option>Active</option>
                <option>Inactive</option>
                <option>On Leave</option>
                <option>Ended</option>
            </select>
        </div>

        <div class="col-md-12">
            <button name="save" class="btn btn-primary">
                Register Staff
            </button>
        </div>

    </form>

</div>
<!-- TABLE -->
<div class="card staff-card p-3">
    <div>
        <button class="btn btn-sm btn-outline-secondary position-absolute end-0 me-3"
            onclick="toggleFullscreen(this)">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>
        <h4 class="fw-bold mb-1">
            Staff List
        </h4>
        <small class="text-secondary">
            Comprehensive directory of all staff, their roles, and employment status
        </small>

    </div>
    <div class="table-responsive">

        <table class="table table-hover align-middle" id="staffTable">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Contractor</th>
                    <th>Position</th>
                    <th>Status</th>
                    <th width="160">Action</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($r = $list->fetch_assoc()): ?>

                    <tr>

                        <td><?= $r['system_id_no'] ?></td>

                        <td>
                            <?php if ($r['photo']): ?>
                                <img src="uploads/staff/<?= $r['photo'] ?>" class="staff-photo">
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= $r['first_name'] . ' ' . $r['last_name'] ?>
                        </td>

                        <td><?= $r['contractor_name'] ?></td>

                        <td><?= $r['position_trade'] ?></td>

                        <td><?= $r['employment_status'] ?></td>

                        <td>

                            <button
                                class="btn btn-sm btn-info"
                                data-bs-toggle="modal"
                                data-bs-target="#view<?= $r['id'] ?>">
                                <i class="bi bi-eye"></i>
                            </button>

                            <a href="./?link=contractor_staff.php&delete=<?= $r['id'] ?>"
                                class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete?')">

                                <i class="bi bi-trash"></i>

                            </a>
                            <!-- VIEW MODAL -->
                            <div class="modal fade" id="view<?= $r['id'] ?>" tabindex="-1">

                                <div class="modal-dialog modal-lg modal-dialog-centered">

                                    <div class="modal-content border-0 shadow-lg">

                                        <div class="modal-header bg-light text-dark">

                                            <h5 class="modal-title">
                                                Staff Details
                                            </h5>

                                            <button
                                                type="button"
                                                class="btn-close btn-close-white"
                                                data-bs-dismiss="modal">
                                            </button>

                                        </div>

                                        <div class="modal-body">

                                            <div class="row g-4">

                                                <div class="col-md-4 text-center">

                                                    <?php if ($r['photo']): ?>

                                                        <img
                                                            src="uploads/staff/<?= $r['photo'] ?>"
                                                            class="img-fluid rounded-circle border"
                                                            style="width:180px;height:180px;object-fit:cover;">

                                                    <?php else: ?>

                                                        <div class="border rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                                            style="width:180px;height:180px;">

                                                            No Photo

                                                        </div>

                                                    <?php endif; ?>

                                                </div>

                                                <div class="col-md-8">

                                                    <table class="table table-bordered">

                                                        <tr>
                                                            <th width="35%">System ID</th>
                                                            <td><?= $r['system_id_no'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Full Name</th>
                                                            <td>
                                                                <?= $r['first_name'] ?>
                                                                <?= $r['middle_name'] ?>
                                                                <?= $r['last_name'] ?>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Contact Number</th>
                                                            <td><?= $r['contact_no'] ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Contractor</th>
                                                            <td><?= $r['contractor_name'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Position</th>
                                                            <td><?= $r['position_trade'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Department</th>
                                                            <td><?= $r['department'] ?></td>
                                                        </tr>


                                                        <tr>
                                                            <th>Employment Type</th>
                                                            <td><?= $r['employment_type'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Projects</th>
                                                            <td><?= $r['project_assignment'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Date Hired</th>
                                                            <td><?= $r['date_hired'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Contract Start</th>
                                                            <td><?= $r['contract_start'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Contract End</th>
                                                            <td><?= $r['contract_end'] ?></td>
                                                        </tr>

                                                        <tr>
                                                            <th>Status</th>
                                                            <td><?= $r['employment_status'] ?></td>
                                                        </tr>

                                                    </table>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>
                        </td>

                    </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $('.select2').select2({
        width: '100%'
    });

    function filterStaff(keyword) {

        keyword = keyword.trim().toLowerCase();

        document.querySelectorAll("#staffTable tbody tr").forEach(row => {

            let text = "";

            row.querySelectorAll("td").forEach((cell, index) => {

                if (index < 6) {
                    text += " " + cell.textContent;
                }

            });

            text = text.toLowerCase();

            row.style.display = text.includes(keyword) ? "" : "none";
        });
    }
</script>