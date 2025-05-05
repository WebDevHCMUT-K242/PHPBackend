<?php

header("Content-Type: application/json");
session_start();

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['article_id'], $input['message'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing article_id or message."]);
    exit;
}

$article_id = (int)$input['article_id'];
$message = trim($input['message']);

if ($message === '') {
    http_response_code(400);
    echo json_encode(["error" => "Message cannot be empty."]);
    exit;
}

require_once __DIR__ . "/../../common/db.php";
require_once __DIR__ . "/../../common/article.php";
require_once __DIR__ . "/../../common/userdata.php";

// Ensure tables exist
Article::maybeCreateArticlesTable();
ArticleComment::maybeCreateCommentsTable();

// Check article exists
$article = Article::getArticle($article_id);
if (!$article) {
    http_response_code(404);
    echo json_encode(["error" => "Article not found."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$comment_id = ArticleComment::createComment($article_id, $user_id, $message);

if (!$comment_id) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to add comment."]);
    exit;
}

echo json_encode([
    "success" => true,
    "comment_id" => $comment_id
]);
