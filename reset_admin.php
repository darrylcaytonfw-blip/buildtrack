<?php
include 'database.php';

$newHash = password_hash('admin123', PASSWORD_DEFAULT);

$conn->query("
    UPDATE users
    SET password='$newHash',
        role='admin',
        status='active'
    WHERE username='admin'
");

echo "Admin password reset success.";
?>