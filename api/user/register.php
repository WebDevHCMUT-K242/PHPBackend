<?php

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username'], $input['display_name'], $input['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields."]);
    exit;
}

$username = trim($input['username']);
$display_name = trim($input['display_name']);
$password = $input['password'];

if ($username === '' || $display_name === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["error" => "Fields cannot be empty."]);
    exit;
}

require_once __DIR__ . "/../../common/db.php";

$user = Database::createUser($username, $display_name, $password);

if ($user === false) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create user."]);
} else if (is_string($user)) {
    http_response_code(409);
    echo json_encode(["error" => $user]);
} else {
    echo json_encode([
        "success" => true,
        "user" => [
            "id" => $user->id,
            "is_admin" => $user->is_admin,
            "username" => $user->username,
            "display_name" => $user->display_name
        ]
    ]);
}