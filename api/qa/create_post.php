<?php

header("Content-Type: application/json");

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in."]);
    exit;
}

require_once __DIR__ . "/../../common/qa.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['thread_id'], $input['message'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing thread_id or message."]);
    exit;
}

$thread_id = (int)$input['thread_id'];
$message = trim($input['message']);

if ($message === '') {
    http_response_code(400);
    echo json_encode(["error" => "Message cannot be empty."]);
    exit;
}

$thread = QaThread::getThread($thread_id);
if (!$thread) {
    http_response_code(404);
    echo json_encode(["error" => "Thread not found."]);
    exit;
}

if ($thread->is_locked && empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Thread is locked."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = QaPost::createPost($thread_id, $user_id, $message);

echo json_encode([
    "success" => true,
    "post_id" => $post_id
]);
