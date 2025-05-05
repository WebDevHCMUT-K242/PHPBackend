<?php
header("Content-Type: application/json");
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized. Admin privileges required."]);
    exit;
}

require_once __DIR__ . "/../../common/editable_home.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing id."]);
    exit;
}

try {
    EditableHome::removeElement((int)$input['id']);
    $new_json = EditableHome::buildJSON(); 

    echo json_encode([
        "success" => true,
        "data" => $new_json,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("Error in remove_element.php: " . $e->getMessage());
    echo json_encode(["success" => false, "error" => "Failed to remove element."]);
}