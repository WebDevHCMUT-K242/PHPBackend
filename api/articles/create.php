<?php

header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Kiểm tra đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests are allowed."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
// Kiểm tra input
if (!isset($input['title'], $input['summary'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing title or summary."]);
    exit;
}

$title = trim($input['title']);
$summary = trim($input['summary']);

if ($title === '' || $summary === '') {
    http_response_code(400);
    echo json_encode(["error" => "Title and summary cannot be empty."]);
    exit;
}

require_once __DIR__ . "/../../common/db.php";
require_once __DIR__ . "/../../common/article.php";
require_once __DIR__ . "/../../common/userdata.php";

// Đảm bảo bảng tồn tại
Database::maybeCreateTables();
Article::maybeCreateArticlesTable();
ArticleComment::maybeCreateCommentsTable();

$userId = $_SESSION['user_id'];
// Chưa xử lý upload ảnh, mặc định null
$article_id = Article::createArticle($userId, $title, $summary, null);
if ($article_id === null) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to create article."]);
    exit;
}

echo json_encode([
    "success" => true,
    "article_id" => $article_id,
]);