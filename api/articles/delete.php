<?php

header("Content-Type: application/json");
session_start();

// Only POST
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

// Verify login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in."]);
    exit;
}

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
$sessionAdmin = $_SESSION['is_admin'] ?? false;

// Authorize: only owner or admin
if ($user_id !== $article->user_id && !$sessionAdmin) {
    http_response_code(403);
    echo json_encode(["error" => "You are not authorized to delete this article."]);
    exit;
}

// Delete article
$success = Article::deleteArticle($article_id);
if (!$success) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to delete the article."]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Article deleted successfully.",
    "article_id" => $article_id
]);