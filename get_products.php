<?php
require 'db_connect.php';
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

$stmt = $conn->prepare("SELECT * FROM products ORDER BY seq_order DESC");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to prepare statement"]);
    exit;
}

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to execute query"]);
    exit;
}

$result = $stmt->get_result();
$out = [];

while ($r = $result->fetch_assoc()) {
    $r['price'] = isset($r['price']) ? floatval($r['price']) : 0.0;
    $r['stock'] = isset($r['stock']) ? intval($r['stock']) : 0;
    $r['tags']  = !empty($r['tags']) ? explode(',', $r['tags']) : [];
    
    $r['name'] = htmlspecialchars($r['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $r['category'] = htmlspecialchars($r['category'] ?? '', ENT_QUOTES, 'UTF-8');
    $r['img'] = htmlspecialchars($r['img'] ?? '', ENT_QUOTES, 'UTF-8');
    
    $out[] = $r;
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
$stmt->close();
$conn->close();
?>

