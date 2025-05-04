<?php

header("Content-Type: application/json");
session_start();

// Only allow POST
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
if (!isset($input['article_id'], $input['title'], $input['content'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing article_id, title, or content."]);
    exit;
}

$article_id = (int)$input['article_id'];
$title = trim($input['title']);
$content = trim($input['content']);

if ($title === '' || $content === '') {
    http_response_code(400);
    echo json_encode(["error" => "Title and content cannot be empty."]);
    exit;
}

require_once __DIR__ . "/../../common/db.php";
require_once __DIR__ . "/../../common/article.php";
require_once __DIR__ . "/../../common/userdata.php";

// Ensure tables exist
Database::getConnection();
Article::maybeCreateArticlesTable();
ArticleComment::maybeCreateCommentsTable();

// Fetch article
$article = Article::getArticle($article_id);
if (!$article) {
    http_response_code(404);
    echo json_encode(["error" => "Article not found."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'] ?? false;

// Authorize: only owner or admin
if ($user_id !== $article->user_id && !$is_admin) {
    http_response_code(403);
    echo json_encode(["error" => "Not authorized to update this article."]);
    exit;
}

// Perform update
$success = Article::updateArticle($article_id, $title, $content);
if (!$success) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update the article."]);
    exit;
}

echo json_encode(["success" => true, "article_id" => $article_id]);
