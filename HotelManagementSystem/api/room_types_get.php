<?php
require __DIR__ . '/cors.php';
require __DIR__ . '/db.php';
require __DIR__ . '/performance.php';
header('Content-Type: application/json');

perf_start('query');
$res = $conn->query("SELECT * FROM Room_Type");
$list = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $list[] = [
            'type_id' => $row['type_id'] ?? '',
            'type_name' => $row['type_name'] ?? '',
            'capacity' => isset($row['capacity']) ? (int)$row['capacity'] : null,
            'amount' => isset($row['basic_amount']) ? (int)$row['basic_amount'] : (isset($row['basic amount']) ? (int)$row['basic amount'] : null),
            'score' => isset($row['score']) ? (float)$row['score'] : 0.00,
            'description' => $row['description'] ?? ''
        ];
    }
}
$queryTime = perf_stop('query');

echo json_encode(['ok'=>true,'data'=>$list,'performance'=>['query_time'=>$queryTime,'formatted'=>number_format($queryTime * 1000, 2) . ' ms']]);
