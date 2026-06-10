<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');
$sql = "SELECT rt.type_name, i.available_count FROM Inventory i JOIN Room_Type rt ON i.type_id=rt.type_id";
$res = $conn->query($sql);
if ($res === false) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'query_failed','debug'=>$conn->error]);
    exit;
}
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[$row['type_name']] = (int)$row['available_count'];
}
$queryTime = perf_stop('query');

echo json_encode(['ok'=>true,'data'=>$data,'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']]);
