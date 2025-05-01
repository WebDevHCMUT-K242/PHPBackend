<?php

header("Content-Type: application/json");

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized."]);
    exit;
}

require_once __DIR__ . "/../../common/editable_about.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['type']) || !isset($input['text']) || !isset($input['index'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing type, text, or index."]);
    exit;
}

EditableAbout::insertElement($input['type'], $input['text'], (int)$input['index']);
$new_json = EditableAbout::buildJSON();

echo json_encode([
    "success" => true,
    "data" => $new_json,
]);