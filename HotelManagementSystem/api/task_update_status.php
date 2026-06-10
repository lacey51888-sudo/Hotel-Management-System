<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('update');

$body = json_decode(file_get_contents('php://input'), true);
$task_id = $body['task_id'] ?? '';
$task_status = $body['task_status'] ?? '';
if (!$task_id || !$task_status) {
    http_response_code(400);
    echo json_encode(['ok'=>false]);
    exit;
}
$stmt = $conn->prepare("UPDATE `Cleaning_task` SET task_status=? WHERE task_id=?");
$stmt->bind_param('ss', $task_status, $task_id);
if ($stmt->execute()) {
    $updateTime = perf_stop('update');
    echo json_encode(['ok'=>true,'performance'=>['update_time'=>$updateTime,'formatted'=>number_format($updateTime * 1000, 2) . ' ms']]);
} else {
    http_response_code(500);
    echo json_encode(['ok'=>false]);
}
