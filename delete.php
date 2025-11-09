<?php
require 'db_connect.php';
header('Content-Type: application/json');
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

$headers = getallheaders();
$clientToken = $headers['X-CSRF-Token'] ?? '';

if (!$clientToken || $clientToken !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid or missing CSRF token"]);
    exit;
}

$id = $_GET['id'] ?? '';
$id = trim($id);

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing id"]);
    exit;
}

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to prepare statement"]);
    exit;
}

$stmt->bind_param("s", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "deleted" => $id]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "Product not found"]);
    }
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to delete product"]);
}

$stmt->close();
$conn->close();
?>
