<?php
/* audit_helper.php */
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

function logAction($conn, $action, $module, $recordId = 0, $details = '')
{
    $userId   = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['user'] ?? 'system';

    $stmt = $conn->prepare("
        INSERT INTO audit_logs
        (user_id, username, action, module_name, record_id, details)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "isssis",
        $userId,
        $username,
        $action,
        $module,
        $recordId,
        $details
    );

    $stmt->execute();
}
?>
