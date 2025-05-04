<?php

header("Content-Type: application/json");
session_start();

// Only POST allowed
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

// Read input
$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['comment_id'], $input['message'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing comment_id or message."]);
    exit;
}

$comment_id = (int) $input['comment_id'];
$message = trim($input['message']);
if ($message === '') {
    http_response_code(400);
    echo json_encode(["error" => "Message cannot be empty."]);
    exit;
}

require_once __DIR__ . "/../../common/db.php";
// Ensure tables exist
Database::getConnection();

// Fetch comment to check ownership
$conn = Database::getConnection();
$stmt = $conn->prepare("SELECT user_id FROM article_comments WHERE id = ?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(["error" => "Comment not found."]);
    exit;
}
$row = $result->fetch_assoc();
$comment_user = (int) $row['user_id'];

$user_id = $_SESSION['user_id'];
$is_admin = $_SESSION['is_admin'] ?? false;
// Authorize: only owner or admin
if ($user_id !== $comment_user && !$is_admin) {
    http_response_code(403);
    echo json_encode(["error" => "Not authorized to update this comment."]);
    exit;
}

// Perform update
$upd = $conn->prepare("UPDATE article_comments SET message = ?, timestamp = CURRENT_TIMESTAMP WHERE id = ?");
$upd->bind_param("si", $message, $comment_id);
$success = $upd->execute();
if (!$success) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to update the comment."]);
    exit;
}

echo json_encode(["success" => true, "comment_id" => $comment_id]);
