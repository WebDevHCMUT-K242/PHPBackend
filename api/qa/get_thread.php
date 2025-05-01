<?php

header("Content-Type: application/json");

require_once __DIR__ . "/../../common/qa.php";
require_once __DIR__ . "/../../common/userdata.php";

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

$thread = QaThread::getThread($thread_id);

if (!$thread) {
    http_response_code(404);
    echo json_encode(["error" => "Thread not found."]);
    exit;
}

$posts = QaPost::getPostsForThread($thread_id);

$user_ids = [$thread->user_id];
foreach ($posts as $post) {
    $user_ids[] = $post->user_id;
}
$user_ids = array_unique($user_ids);

$users = UserData::getUsers($user_ids);

$users_by_id = [];
foreach ($users as $user) {
    $users_by_id[$user->id] = [
        "username" => $user->username,
        "display_name" => $user->display_name,
        "is_admin" => $user->is_admin,
    ];
}

echo json_encode([
    "success" => true,
    "thread" => [
        "id" => $thread->id,
        "user_id" => $thread->user_id,
        "title" => $thread->title,
        "message" => $thread->message,
        "timestamp" => $thread->timestamp,
        "last_updated" => $thread->last_updated,
        "is_locked" => (bool)$thread->is_locked
    ],
    "posts" => array_map(function($post) {
        return [
            "id" => $post->id,
            "thread_id" => $post->thread_id,
            "user_id" => $post->user_id,
            "message" => $post->message,
            "timestamp" => $post->timestamp,
            "last_updated" => $post->last_updated
        ];
    }, $posts),
    "users" => $users
]);
