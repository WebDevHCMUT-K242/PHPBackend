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
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function base36_encode($num) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $base = 36;
    $result = '';
    do {
        $result = $chars[$num % $base] . $result;
        $num = (int)($num / $base);
    } while ($num > 0);
    return $result;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["error" => "No image uploaded or upload failed"]);
        exit;
    }

    $timestamp = round(microtime(true) * 1000000);
    $delta = $timestamp - 1746243600000000;
    $base62name = base36_encode($delta*100000+random_int(0,99999));

    $imageType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!in_array($imageType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        http_response_code(400);
        echo json_encode(["error" => "Unsupported file type"]);
        exit;
    }

    $filename = $base62name . "." . $imageType;
    $destination = $uploadDir . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
        echo json_encode(["success" => true, "filename" => $filename]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to save file"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
