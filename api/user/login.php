<?php

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['username'], $input['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing username or password."]);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(["error" => "Username and password cannot be empty."]);
    exit;
}

require_once __DIR__ . "/../../common/userdata.php";

$user = UserData::doUserLogin($username, $password);

if ($user === null) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid username or password."]);
    exit;
}

session_start();
session_regenerate_id(true);

$_SESSION['user_id'] = $user->id;
$_SESSION['is_admin'] = $user->is_admin;
$_SESSION['username'] = $user->username;

echo json_encode([
    "success" => true,
    "user" => [
        "id" => $user->id,
        "is_admin" => $user->is_admin,
        "username" => $user->username,
        "display_name" => $user->display_name
    ]
]);