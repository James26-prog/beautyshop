<?php
require 'db_connect.php';
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

$headers = getallheaders();
$clientToken = $headers['X-CSRF-Token'] ?? '';

if (!$clientToken || $clientToken !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid or missing CSRF token"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

$products = $data['products'];
$results = [];

foreach($products as $p){
    $id = isset($p['id']) ? trim($p['id']) : null;
    $name = isset($p['name']) ? trim($p['name']) : null;
    $category = isset($p['category']) ? trim($p['category']) : null;
    $price = isset($p['price']) ? floatval($p['price']) : 0;
    $stock = isset($p['stock']) ? intval($p['stock']) : 0;
    $img = isset($p['img']) ? trim($p['img']) : '';
    $tags = isset($p['tags']) && is_array($p['tags']) ? implode(',', $p['tags']) : '';

    if (!$id || !$name || !$category) {
        $results[] = ["id" => $id, "success" => false, "error" => "Missing required fields"];
        continue;
    }

    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    if ($exists) {
        $stmt = $conn->prepare("
            UPDATE products 
            SET name = ?, category = ?, price = ?, stock = ?, img = ?, tags = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("ssdisss", $name, $category, $price, $stock, $img, $tags, $id);
        $success = $stmt->execute();
        $results[] = ["id" => $id, "success" => $success, "action" => "updated"];
        $stmt->close();
    } else {
        $stmt = $conn->prepare("
        INSERT INTO products (id, name, category, price, stock, img, tags)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            category = VALUES(category),
            price = VALUES(price),
            stock = VALUES(stock),
            img = VALUES(img),
            tags = VALUES(tags)
        ");
        $stmt->bind_param("sssdiss", $id, $name, $category, $price, $stock, $img, $tags);
        $success = $stmt->execute();
        $results[] = ["id" => $id, "success" => $success, "action" => "inserted"];
        $stmt->close();
    }
}
echo json_encode(["success" => true, "results" => $results], JSON_UNESCAPED_UNICODE);
$conn->close();
?>