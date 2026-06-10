<?php
ob_start();
include 'database.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$message = '';
$error   = '';

/* =========================================================
   LOAD USER + CONTRACTOR STAFF INFO
========================================================= */

$stmt = $conn->prepare("
    SELECT 
        u.*,
        cs.id AS staff_id,
        cs.first_name,
        cs.last_name,
        cs.middle_name,
        cs.contact_no,
        cs.system_id_no,
        cs.photo,
        cs.position_trade,
        cs.department,
        cs.employment_type,
        cs.project_assignment,
        cs.date_hired,
        cs.contract_start,
        cs.contract_end,
        cs.employment_status,
        c.name AS contractor_name
    FROM users u
    LEFT JOIN contractor_staff cs ON cs.user_id = u.id
    LEFT JOIN contractors c ON cs.contractor_id = c.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    exit("User not found.");
}

/* =========================================================
   SAVE PROFILE
========================================================= */

if (isset($_POST['save_profile'])) {

    $username   = trim($_POST['username']);
    $status = $user['status']; // keep existing status
    $contact_no = trim($_POST['contact_no']);

    if ($username == '') {

        $error = "Username is required.";
    } else {

        $chk = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            AND id != ?
            LIMIT 1
        ");
        $chk->bind_param("si", $username, $user_id);
        $chk->execute();

        if ($chk->get_result()->num_rows > 0) {

            $error = "Username already exists.";
        } else {

            /* update users */
            $up = $conn->prepare("
                UPDATE users
                SET username=?
                WHERE id=?
            ");
            $up->bind_param("si", $username, $user_id);
            $up->execute();

            /* =========================================================
   INSERT OR UPDATE CONTRACTOR STAFF
========================================================= */

            $staffCheck = $conn->prepare("
                    SELECT id
                    FROM contractor_staff
                    WHERE user_id = ?
                    LIMIT 1
                ");
            $staffCheck->bind_param("i", $user_id);
            $staffCheck->execute();

            $staffResult = $staffCheck->get_result();

            if ($staffResult->num_rows > 0) {

                $up2 = $conn->prepare("
                    UPDATE contractor_staff
                    SET
                        contact_no = ?,
                        system_id_no = ?
                    WHERE user_id = ?
                ");

                $up2->bind_param(
                    "ssi",
                    $contact_no,
                    $username,
                    $user_id
                );

                $up2->execute();
            } else {

                $contractor_id = 1; // replace with a valid contractor ID

                $insertStaff = $conn->prepare("
                    INSERT INTO contractor_staff (
                        contractor_id,
                        user_id,
                        contact_no,
                        system_id_no,
                        employment_status
                    )
                    VALUES (?, ?, ?, ?, 'Active')
                ");

                $insertStaff->bind_param(
                    "iiss",
                    $contractor_id,
                    $user_id,
                    $contact_no,
                    $username
                );

                $insertStaff->execute();
            }

            $_SESSION['user'] = $username;

            $message = "Profile updated successfully.";

            header("Location: ./?link=my_profile.php&saved=1");
            exit;
        }
    }
}

/* =========================================================
   CHANGE PASSWORD
========================================================= */

if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (!password_verify($current, $user['password'])) {

        $error = "Current password is incorrect.";
    } elseif ($new == '') {

        $error = "New password is required.";
    } elseif ($new != $confirm) {

        $error = "Password confirmation does not match.";
    } else {

        $hash = password_hash($new, PASSWORD_DEFAULT);

        $p = $conn->prepare("
            UPDATE users
            SET password=?
            WHERE id=?
        ");
        $p->bind_param("si", $hash, $user_id);
        $p->execute();

        $message = "Password changed successfully.";
    }
}

/* refresh after update */
if (isset($_GET['saved'])) {
    $message = "Profile updated successfully.";
}

$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* =========================================================
   PHOTO PATH
========================================================= */

$photoPath = "assets/images/default-avatar.png";

if (!empty($user['photo']) && file_exists("uploads/staff/" . $user['photo'])) {
    $photoPath = "uploads/staff/" . $user['photo'];
}
?>

<style>
    .profile-wrap {
        max-width: 1250px;
        margin: auto;
    }

    .glass-card {
        background: linear-gradient(145deg, #0f172a, #102447);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 24px;
        box-shadow: 0 20px 45px rgba(0, 0, 0, .28);
    }

    .avatar {
        width: 130px;
        height: 130px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, .12);
    }

    .label-ui {
        color: #cbd5e1;
        font-size: 14px;
        margin-bottom: 6px;
    }

    .form-control,
    .form-select {
        min-height: 46px;
        border-radius: 14px;
    }

    .btn-gold {
        background: linear-gradient(135deg, #facc15, #eab308);
        border: none;
        color: #111827;
        font-weight: 700;
        border-radius: 14px;
        padding: 12px 22px;
    }

    .btn-gold:hover {
        opacity: .92;
    }

    .muted {
        color: #94a3b8;
        font-size: 13px;
    }

    .info-box {
        background: rgba(255, 255, 255, .04);
        border-radius: 16px;
        padding: 14px;
    }

    .avatar-wrapper {
        width: 130px;
        height: 130px;
        margin: auto;
    }

    .avatar,
    .avatar-fallback {
        width: 130px;
        height: 130px;
        border-radius: 50%;
    }

    .avatar {
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, .12);
    }

    .avatar-fallback {
        background: rgba(255, 255, 255, .08);
        border: 4px solid rgba(255, 255, 255, .12);
        align-items: center;
        justify-content: center;
    }

    .avatar-fallback i {
        font-size: 70px;
        color: #cbd5e1;
    }
</style>

<div class="container-fluid py-4">
    <div class="profile-wrap">

        <div class="mb-4">
            <h2 class="text-white fw-bold mb-1">My Profile</h2>
            <div class="muted">Premium account settings and staff information</div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success rounded-4"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-4"><?= $error ?></div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- LEFT PANEL -->
            <div class="col-lg-4">

                <div class="glass-card p-4 text-center h-100">

                    <div class="avatar-wrapper">
                        <img src="<?= htmlspecialchars($photoPath) ?>"
                            class="avatar"
                            id="profilePhoto"
                            onerror="this.style.display='none';document.getElementById('avatarFallback').style.display='flex';">

                        <div id="avatarFallback" class="avatar-fallback" style="display:none;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                    </div>

                    <h4 class="text-white fw-bold mb-1">
                        <?= htmlspecialchars($user['first_name'] ?: $user['username']) ?>
                        <?= htmlspecialchars($user['last_name'] ?? '') ?>
                    </h4>

                    <div class="muted mb-2">
                        <?= strtoupper(str_replace('_', ' ', $user['role'])) ?>
                    </div>

                    <span class="badge rounded-pill bg-warning text-dark px-3 py-2 mb-4">
                        <?= ucfirst($user['employment_status']) ?>
                    </span>

                    <div class="row g-3 text-start">

                        <div class="col-12">
                            <div class="info-box">
                                <div class="muted">System ID</div>
                                <div class="text-white fw-semibold">
                                    <?= htmlspecialchars($user['system_id_no'] ?: $user['username']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="info-box">
                                <div class="muted">Contractor</div>
                                <div class="text-white fw-semibold">
                                    <?= htmlspecialchars($user['contractor_name'] ?: 'N/A') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="info-box">
                                <div class="muted">Position</div>
                                <div class="text-white fw-semibold">
                                    <?= htmlspecialchars($user['position_trade'] ?: 'N/A') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="info-box">
                                <div class="muted">Projects</div>
                                <div class="text-white fw-semibold">
                                    <?= htmlspecialchars($user['project_assignment'] ?: 'N/A') ?>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <!-- RIGHT PANEL -->
            <div class="col-lg-8">

                <!-- ACCOUNT -->
                <div class="glass-card p-4 mb-4">

                    <h4 class="text-white fw-bold mb-4">
                        <i class="bi bi-sliders me-2"></i>
                        Account Settings
                    </h4>

                    <form method="POST" class="row g-3">

                        <div class="col-md-6">
                            <label class="label-ui">Username / Login ID</label>
                            <input type="text"
                                name="username"
                                class="form-control"
                                value="<?= htmlspecialchars($user['username']) ?>"
                                required>
                        </div>

                        <div class="col-md-6">
                            <label class="label-ui">Contact No.</label>
                            <input type="text"
                                name="contact_no"
                                class="form-control"
                                value="<?= htmlspecialchars($user['contact_no']) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="label-ui">Role</label>
                            <input type="text"
                                class="form-control"
                                value="<?= ucwords(strtolower(htmlspecialchars(str_replace('_', ' ', $user['role'])))) ?>"
                                disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="label-ui">Status</label>
                            <select name="status" class="form-select" disabled>
                                <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <button type="submit"
                                name="save_profile"
                                class="btn btn-gold">
                                Save Changes
                            </button>
                        </div>

                    </form>

                </div>

                <!-- SECURITY -->
                <div class="glass-card p-4">

                    <h4 class="text-white fw-bold mb-4">
                        <i class="bi bi-shield-lock me-2"></i>
                        Security Settings
                    </h4>

                    <form method="POST" class="row g-3">

                        <div class="col-md-4">
                            <label class="label-ui">Current Password</label>
                            <input type="password"
                                name="current_password"
                                class="form-control"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="label-ui">New Password</label>
                            <input type="password"
                                name="new_password"
                                class="form-control"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="label-ui">Confirm Password</label>
                            <input type="password"
                                name="confirm_password"
                                class="form-control"
                                required>
                        </div>

                        <div class="col-12">
                            <button type="submit"
                                name="change_password"
                                class="btn btn-gold">
                                Update Password
                            </button>
                        </div>

                    </form>

                </div>

            </div>
        </div>

    </div>
</div>