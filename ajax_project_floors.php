<?php
include 'database.php';

header('Content-Type: application/json');

$project_id = (int)($_GET['project_id'] ?? 0);

if ($project_id <= 0) {
    echo json_encode([
        'success' => false,
        'floors' => 0,
        'ph' => 0,
        'message' => 'Invalid project id'
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT floor_count, penthouse_count
    FROM projects
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $project_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {

    echo json_encode([
        'success' => true,
        'floors' => (int)$row['floor_count'],
        'ph' => (int)$row['penthouse_count']
    ]);

} else {

    echo json_encode([
        'success' => false,
        'floors' => 0,
        'ph' => 0,
        'message' => 'Project not found'
    ]);
}