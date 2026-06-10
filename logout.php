<?php
/* logout.php - UPDATED WITH AUDIT LOGS + SAFE DESTROY */

session_start();
include 'database.php';
include 'audit_helper.php';

/* Log logout before session destroy */
if (isset($_SESSION['user_id'])) {
    logAction(
        $conn,
        'LOGOUT',
        'auth',
        $_SESSION['user_id'],
        'User logged out'
    );
}

/* Clear all session variables */
$_SESSION = [];

/* Remove session cookie */
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

/* Destroy session */
session_destroy();

/* Redirect to login */
header("Location: login.php");
exit;
?>