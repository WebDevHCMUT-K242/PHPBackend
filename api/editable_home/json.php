<?php
header("Content-Type: application/json");
session_start(); 

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); 
    echo json_encode(["error" => "Only GET requests are allowed."]);
    exit;
}

require_once __DIR__ . "/../../common/editable_home.php";

try {
    $home_json = EditableHome::buildJSON();
    echo json_encode([
        "success" => true,
        "data" => $home_json,
    ]);
} catch (Exception $e) {
    http_response_code(500); 
    error_log("Error in json.php: " . $e->getMessage()); 
    echo json_encode(["success" => false, "error" => "An internal error occurred."]);
}