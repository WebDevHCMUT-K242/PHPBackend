<?php

header("Content-Type: application/json");

session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(["error" => "Only admins can perform this action."]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['thread_id'], $input['is_locked'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing thread_id or is_locked."]);
    exit;
}

$thread_id = (int)$input['thread_id'];
$is_locked = (bool)$input['is_locked'];

require_once __DIR__ . "/../../common/qa.php";

$success = QaThread::setThreadIsLocked($thread_id, $is_locked);

if (!$success) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update thread lock status."]);
    exit;
}

echo json_encode([
    "success" => true,
    "thread_id" => $thread_id,
    "is_locked" => $is_locked
]);
