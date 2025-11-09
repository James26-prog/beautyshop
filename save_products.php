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

$id = isset($data['id']) ? trim($data['id']) : null;
$name = isset($data['name']) ? trim($data['name']) : null;
$category = isset($data['category']) ? trim($data['category']) : null;
$price = isset($data['price']) ? floatval($data['price']) : 0;
$stock = isset($data['stock']) ? intval($data['stock']) : 0;
$img = isset($data['img']) ? trim($data['img']) : '';
$tags = isset($data['tags']) && is_array($data['tags']) ? implode(',', $data['tags']) : '';

if (!$id || !$name || !$category) {
    http_response_code(422);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
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

    if ($success) {
        echo json_encode(["success" => true, "action" => "updated"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update product"]);
    }
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

    if ($success) {
        echo json_encode(["success" => true, "action" => "inserted"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to insert product"]);
    }
    $stmt->close();
}
$conn->close();
?>
