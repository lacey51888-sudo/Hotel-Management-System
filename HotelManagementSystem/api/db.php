<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'hotel_mgmt';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'db_connect_failed']);
    exit;
}
$conn->set_charset('utf8mb4');
