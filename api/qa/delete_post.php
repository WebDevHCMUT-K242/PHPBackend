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

if (!isset($input['thread_id']) || !isset($input['post_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing thread_id and post_id."]);
    exit;
}

$thread_id = (int)$input['thread_id'];
$post_id = (int)$input['post_id'];

$post = QaPost::getPost($thread_id, $post_id);

if ($post === null) {
    http_response_code(404);
    echo json_encode(["error" => "Post not found."]);
    exit;
}

if ($post->user_id !== $_SESSION['user_id'] && !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(["error" => "Not authorized to delete this post."]);
    exit;
}

$p = QaPost::deletePost($thread_id, $post_id);

echo json_encode([
    "success" => true
]);