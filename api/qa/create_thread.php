<?php

header("Content-Type: application/json");

session_start();

// Logged in?
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['title'], $input['message'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing title or message."]);
    exit;
}

$title = trim($input['title']);
$message = trim($input['message']);

if ($title === '' || $message === '') {
    http_response_code(400);
    echo json_encode(["error" => "Title and message cannot be empty."]);
    exit;
}

require_once __DIR__ . "/../../common/qa.php";

$thread_id = QaThread::createThread($_SESSION['user_id'], $title, $message);
if ($thread_id === null) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create thread."]);
    exit;
}

echo json_encode([
    "success" => true,
    "thread_id" => $thread_id,
]);
