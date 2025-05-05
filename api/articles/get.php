<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['article_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing article_id."]);
    exit;
}
$article_id = (int)$input['article_id'];

require_once __DIR__ . "/../../common/db.php";
require_once __DIR__ . "/../../common/article.php";
require_once __DIR__ . "/../../common/userdata.php";

// Ensure tables exist
Article::maybeCreateArticlesTable();
ArticleComment::maybeCreateCommentsTable();

// Fetch article
$article = Article::getArticle($article_id);
if (!$article) {
    http_response_code(404);
    echo json_encode(["error" => "Article not found."]);
    exit;
}

// Fetch comments
$comments = ArticleComment::getCommentsForArticle($article_id);

// Collect user IDs
$user_id = $article->user_id;
$user = UserData::getUser($user_id);

// Build user mapping


// Prepare response
$response = [
    "success" => true,
    "article" => [
        'id' => $article->id,
        'user_id' => $article->user_id,
        'title' => $article->title,
        'content' => $article->content,
        'timestamp' => $article->timestamp,
        'last_updated' => $article->last_updated,
        'users' => $user->display_name,
    ],
    "comments" => [],
];

foreach ($comments as $c) {
    $user_comment_ids = UserData::getUser($c->user_id);
    $response['comments'][] = [
        'id' => $c->id,
        'article_id' => $c->article_id,
        'user_id' => $c->user_id,
        'users' => $user_comment_ids->display_name,
        'message' => $c->message,
        'timestamp' => $c->timestamp,
    ];
}

echo json_encode($response);