<?php include 'database.php';
$id = (int)$_GET['id'];
$conn->query("DELETE FROM activities WHERE id=$id");
header('Location: ./?link=activities.php');
