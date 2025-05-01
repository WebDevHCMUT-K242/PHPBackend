<?php

header("Content-Type: application/json");

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET requests are allowed."]);
    exit;
}

require_once __DIR__ . "/../../common/editable_about.php";

$new_json = EditableAbout::buildJSON();

echo json_encode([
    "success" => true,
    "data" => $new_json,
]);