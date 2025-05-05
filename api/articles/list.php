<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
    // if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    //     http_response_code(200);
    //     exit();
    // }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

$page = isset($data['page']) ? (int)$data['page'] : 1;
$per_page = isset($data['per_page']) ? (int)$data['per_page'] : 20;

if ($page < 1 || $per_page < 1) {
    http_response_code(400);
    echo json_encode(["error" => "Page and per_page must be positive integers."]);
    exit;
}

// Include necessary classes
require_once __DIR__ . "/../../common/db.php";
require_once __DIR__ . "/../../common/article.php";
require_once __DIR__ . "/../../common/userdata.php";

// Initialize tables if not exist
Article::maybeCreateArticlesTable();
ArticleComment::maybeCreateCommentsTable();

// Fetch paginated articles
$articles = Article::listArticles($page, $per_page);
$page_count = Article::getPageCount($per_page);

// Gather user IDs to fetch display names

// Prepare response
$response = [
    "success" => true,
    "articles" => [],
    "pages" => $page_count,
];

foreach ($articles as $art) {
    $users = UserData::getUser($art->user_id);
    $response['articles'][] = [
        'id' => $art->id,
        'user_id' => $art->user_id,
        "users" => $users->display_name,
        'title' => $art->title,
        'content' => $art->content,
        'timestamp' => $art->timestamp,
        'last_updated' => $art->last_updated,
    ];
}

echo json_encode($response);