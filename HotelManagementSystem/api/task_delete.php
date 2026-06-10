<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('delete');

$body = json_decode(file_get_contents('php://input'), true);
$task_id = $body['task_id'] ?? '';

if (!$task_id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid_params']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM `Cleaning_task` WHERE task_id=?");
$stmt->bind_param('s', $task_id);

if ($stmt->execute()) {
    $deleteTime = perf_stop('delete');
    echo json_encode(['ok'=>true,'performance'=>['delete_time'=>$deleteTime,'formatted'=>number_format($deleteTime * 1000, 2) . ' ms']]);
} else {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'delete_failed']);
}
