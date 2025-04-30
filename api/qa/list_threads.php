<?php

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Only GET requests are allowed."]);
    exit;
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

if ($page < 1 || $per_page < 1) {
    http_response_code(400);
    echo json_encode(["error" => "Page and per_page must be positive integers."]);
    exit;
}

require_once __DIR__ . "/../../common/qa.php";
require_once __DIR__ . "/../../common/userdata.php";

$threads = QaThread::listThreads($page, $per_page);

$user_ids = [];
foreach ($threads as $thread) {
    $user_ids[] = $thread->user_id;
}

$user_ids = array_unique($user_ids);
$users = UserData::getUsers($user_ids);

echo json_encode([
    "success" => true,
    "threads" => $threads,
    "users" => $users
]);
