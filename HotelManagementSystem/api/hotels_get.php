<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');
$res = $conn->query("SELECT hotel_id, hotel_name, address, country, hotel_phone, score, description FROM Hotel");
$list = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $list[] = $row;
    }
}
$queryTime = perf_stop('query');

echo json_encode(['ok'=>true,'data'=>$list,'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']]);
