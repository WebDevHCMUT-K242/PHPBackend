<?php

header("Content-Type: application/json");

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['thread_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing thread_id."]);
    exit;
}

$thread_id = (int)$input['thread_id'];

require_once __DIR__ . "/../../common/qa.php";
require_once __DIR__ . "/../../common/userdata.php";

$thread = QaThread::getThread($thread_id);

if (!$thread) {
    http_response_code(404);
    echo json_encode(["error" => "Thread not found."]);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($user_id !== $thread->user_id && !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(["error" => "You are not authorized to delete this thread."]);
    exit;
}

$success = QaThread::deleteThread($thread_id);

if (!$success) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to delete the thread."]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Thread deleted successfully.",
    "thread_id" => $thread_id
]);
