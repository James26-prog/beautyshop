<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

$host = "localhost";
$user = "root";
$pass = ""; 
$dbname = "beautyshop";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
  http_response_code(500);
  die(json_encode(["error"=>"DB connect failed: ".$conn->connect_error]));
}
$conn->set_charset("utf8mb4");
?>


