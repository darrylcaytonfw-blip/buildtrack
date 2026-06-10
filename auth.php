<?php
/* auth.php - SECURE VERSION */
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

/* Optional timeout: 30 mins */
if (isset($_SESSION['last_activity']) &&
    (time() - $_SESSION['last_activity'] > 1800)) {

    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}

$_SESSION['last_activity'] = time();
?>