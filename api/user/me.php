<?php

header("Content-Type: application/json");

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

require_once __DIR__ . "/../../common/db.php";

$user = Database::getUser($_SESSION['user_id']);
if ($user === null) {
    http_response_code(404);
    echo json_encode(["error" => "User not found"]);
    exit;
}

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $user->id,
        "is_admin" => $user->is_admin,
        "username" => $user->username,
        "display_name" => $user->display_name
    ]
]);