<?php

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

$page = isset($data['page']) ? (int)$data['page'] : 1;
$per_page = isset($data['per_page']) ? (int)$data['per_page'] : 10;

if ($page < 1 || $per_page < 1) {
    http_response_code(400);
    echo json_encode(["error" => "Page and per_page must be positive integers."]);
    exit;
}

require_once __DIR__ . "/../../common/qa.php";
require_once __DIR__ . "/../../common/userdata.php";

$threads = QaThread::listThreads($page, $per_page);
$page_count = QaThread::getPageCount($per_page);

$user_ids = [];
foreach ($threads as $thread) {
    $user_ids[] = $thread->user_id;
}

$user_ids = array_unique($user_ids);
$users = UserData::getUsers($user_ids);

echo json_encode([
    "success" => true,
    "threads" => $threads,
    "users" => $users,
    "pages" => $page_count,
]);
