<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

$type_id = isset($_GET['type_id']) ? $_GET['type_id'] : null;

if (!$type_id) {
    echo json_encode(['ok' => false, 'error' => 'type_id is required']);
    exit;
}

perf_start('query');

// Get all comments for this room type
$stmt = $conn->prepare("
    SELECT c.comment_id, c.user_id, u.user_name, c.score, c.text, c.comment_time 
    FROM Comment c
    LEFT JOIN User u ON c.user_id = u.user_id
    WHERE c.type_id = ?
    ORDER BY c.comment_time DESC
");
$stmt->bind_param("s", $type_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = $row;
}

$queryTime = perf_stop('query');

$stmt->close();
$conn->close();

echo json_encode(['ok' => true, 'data' => $comments, 'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']]);
?>
