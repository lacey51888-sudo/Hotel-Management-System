<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');
$res = $conn->query("SELECT task_id, room_id, customer_id, cleaner_id, task_status, wish_time, actual_start_time, actual_end_time, notes FROM `Cleaning_task` ORDER BY task_id DESC");
if ($res === false) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'query_failed','debug'=>$conn->error]);
    exit;
}
$list = [];
while ($row = $res->fetch_assoc()) {
    $list[] = $row;
}
$queryTime = perf_stop('query');

echo json_encode(['ok'=>true,'data'=>$list,'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']]);
