<?php
/* login.php - UPDATED WITH ROLE + STATUS */
include 'database.php';
session_start();

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT id, username, password, role, status
        FROM users
        WHERE username=?
        LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        if ($user['status'] != 'active') {
            $error = "Account is inactive.";
        } elseif (password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user']    = $user['username'];
            $_SESSION['role']    = $user['role'];

            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BuildTrack Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top right, rgba(212, 175, 55, .08), transparent 30%),
                linear-gradient(135deg, #0b1f3a, #08111f);
        }

        .login-wrap {
            width: 100%;
            max-width: 430px;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(212, 175, 55, .18);
            border-radius: 24px;
            padding: 35px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, .35);
        }

        .logo-box {
            width: 75px;
            height: 75px;
            margin: auto;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #d4af37, #f5d76e);
            color: #08111f;
            font-size: 32px;
        }

        .form-control {
            height: 50px;
            padding-left: 42px;
        }

        .input-wrap {
            position: relative;
        }

        .input-group-text {
            position: absolute;
            z-index: 5;
            width: 42px;
            height: 50px;
            border: none;
            background: transparent;
            color: #94a3b8;
        }

        .btn-login {
            height: 50px;
            width: 100%;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            background: linear-gradient(90deg, #d4af37, #f5d76e);
        }
    </style>
</head>

<body>

    <div class="login-wrap">
        <div class="login-card">

            <div class="text-center mb-4">
                <img src="assets/images/logo.png" alt="" class="w-75">
                <div class="text-secondary">Role-Based Secure Login</div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger py-2 small">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <div class="mb-3 input-wrap">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>

                    <input type="text"
                        name="username"
                        class="form-control"
                        placeholder="Username"
                        required>
                </div>

                <div class="mb-3 input-wrap">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>

                    <input type="password"
                        name="password"
                        class="form-control"
                        placeholder="Password"
                        required>
                </div>

                <button type="submit"
                    name="login"
                    class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    Sign In
                </button>

            </form>

        </div>
    </div>

</body>

</html>