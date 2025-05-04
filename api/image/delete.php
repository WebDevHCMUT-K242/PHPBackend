<?php
session_start();
include_once __DIR__ . "/../../common/userdata.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}
$user = UserData::getUser($_SESSION['user_id']);
if (!$user || !$user->is_admin) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

$uploadDir = __DIR__ . "/../../uploaded_images/";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['filename'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing filename"]);
        exit;
    }

    $fileToDelete = basename($_POST['filename']);
    if (!preg_match('/^[0-9a-zA-Z]+\.(jpg|jpeg|png|gif|webp)$/', $fileToDelete)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid filename"]);
        exit;
    }

    $filePath = $uploadDir . $fileToDelete;
    if (file_exists($filePath)) {
        unlink($filePath);
        echo json_encode(["success" => true, "deleted" => $fileToDelete]);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "File not found"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
