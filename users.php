<?php
/* users.php - UPDATED FOR NEW ROLES */
include 'audit_helper.php';

/* Only System Admin */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'system_admin') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    exit;
}

/* Role Labels */
$roles = [
    'management'      => 'Management',
    'ceo'             => 'CEO',
    'project_manager' => 'Project Manager',
    'finance'         => 'Finance',
    'engineer'        => 'Engineer',
    'supplier'        => 'Supplier / Contractor',
    'system_admin'    => 'System Admin'
];

/* ---------- CREATE USER ---------- */
if (isset($_POST['save'])) {

    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = trim($_POST['role']);
    $status   = trim($_POST['status']);

    $stmt = $conn->prepare("
        INSERT INTO users(username,password,role,status)
        VALUES(?,?,?,?)
    ");
    $stmt->bind_param("ssss", $username, $password, $role, $status);
    $stmt->execute();

    header("Location: ./?link=users.php&added=1");
    exit;
}

/* ---------- UPDATE USER ---------- */
if (isset($_POST['update'])) {

    $id       = (int)$_POST['id'];
    $username = trim($_POST['username']);
    $role     = trim($_POST['role']);
    $status   = trim($_POST['status']);

    $stmt = $conn->prepare("
        UPDATE users
        SET username=?, role=?, status=?
        WHERE id=?
    ");
    $stmt->bind_param("sssi", $username, $role, $status, $id);
    $stmt->execute();

    header("Location: ./?link=users.php&updated=1");
    exit;
}

/* ---------- RESET PASSWORD ---------- */
if (isset($_POST['reset'])) {

    $id = (int)$_POST['id'];
    $newPass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        UPDATE users
        SET password=?
        WHERE id=?
    ");
    $stmt->bind_param("si", $newPass, $id);
    $stmt->execute();

    header("Location: ./?link=users.php&reset=1");
    exit;
}

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {

    $id = (int)$_GET['delete'];

    if ($id != $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id=$id");
    }

    header("Location: ./?link=users.php&deleted=1");
    exit;
}

/* ---------- DATA ---------- */
$list = $conn->query("
    SELECT *
    FROM users
    ORDER BY id DESC
");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="text-white fw-bold mb-1">Users</h2>
        <small class="text-secondary">Manage accounts and roles</small>
    </div>

    <input type="text"
        class="form-control"
        style="max-width:260px"
        placeholder="Search user..."
        onkeyup="filterUsers(this.value)">
</div>

<?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success py-2">User created successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-info py-2">User updated successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['reset'])): ?>
    <div class="alert alert-warning py-2">Password reset successfully.</div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger py-2">User deleted successfully.</div>
<?php endif; ?>

<!-- ADD USER -->
<div class="card p-4 mb-4">
    <form method="post" class="row g-3 align-items-end">

        <div class="col-md-3">
            <label class="small text-secondary mb-1">Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>

        <div class="col-md-3">
            <label class="small text-secondary mb-1">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="col-md-2">
            <label class="small text-secondary mb-1">Role</label>
            <select name="role" class="form-select">
                <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= $key ?>"><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small text-secondary mb-1">Status</label>
            <select name="status" class="form-select">
                <option value="active">active</option>
                <option value="inactive">inactive</option>
            </select>
        </div>

        <div class="col-md-2">
            <button name="save" class="btn btn-gold w-100">
                <i class="bi bi-plus-lg"></i> Add User
            </button>
        </div>

    </form>
</div>

<!-- TABLE -->
<div class="card p-3">
    <div class="table-responsive">

        <table class="table table-hover align-middle mb-0" id="userTable">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th width="220">Action</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($r = $list->fetch_assoc()): ?>
                    <tr>

                        <td><?= $r['id'] ?></td>
                        <td><?= $r['username'] ?></td>

                        <td>
                            <span class="badge bg-primary">
                                <?= $roles[$r['role']] ?? $r['role'] ?>
                            </span>
                        </td>

                        <td>
                            <?php if ($r['status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="d-flex gap-1">

                                <button class="btn btn-sm btn-outline-dark"
                                    data-bs-toggle="modal"
                                    data-bs-target="#edit<?= $r['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <button class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reset<?= $r['id'] ?>">
                                    <i class="bi bi-key"></i>
                                </button>

                                <?php if ($r['id'] != $_SESSION['user_id']): ?>
                                    <a href="./?link=users.php&delete=<?= $r['id'] ?>"
                                        onclick="return confirm('Delete user?')"
                                        class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>

                            </div>
                        </td>

                    </tr>

                    <!-- EDIT MODAL -->
                    <div class="modal fade" id="edit<?= $r['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">

                                <form method="post">

                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit User</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">

                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">

                                        <label class="small mb-1">Username</label>
                                        <input type="text"
                                            name="username"
                                            value="<?= $r['username'] ?>"
                                            class="form-control mb-2">

                                        <label class="small mb-1">Role</label>
                                        <select name="role" class="form-select mb-2">
                                            <?php foreach ($roles as $key => $label): ?>
                                                <option value="<?= $key ?>"
                                                    <?= $r['role'] == $key ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <label class="small mb-1">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active"
                                                <?= $r['status'] == 'active' ? 'selected' : '' ?>>
                                                active
                                            </option>

                                            <option value="inactive"
                                                <?= $r['status'] == 'inactive' ? 'selected' : '' ?>>
                                                inactive
                                            </option>
                                        </select>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="submit"
                                            name="update"
                                            class="btn btn-gold">
                                            Save Changes
                                        </button>
                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>

                    <!-- RESET MODAL -->
                    <div class="modal fade" id="reset<?= $r['id'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">

                                <form method="post">

                                    <div class="modal-header">
                                        <h5 class="modal-title">Reset Password</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">

                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">

                                        <label class="small mb-1">New Password</label>
                                        <input type="password"
                                            name="new_password"
                                            class="form-control"
                                            required>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="submit"
                                            name="reset"
                                            class="btn btn-warning">
                                            Reset Password
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

<script>
    function filterUsers(keyword) {
        keyword = keyword.toLowerCase();

        document.querySelectorAll("#userTable tbody tr").forEach(row => {
            row.style.display =
                row.innerText.toLowerCase().includes(keyword) ?
                "" :
                "none";
        });
    }
</script>