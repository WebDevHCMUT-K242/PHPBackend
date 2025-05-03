<?php
header("Content-Type: application/json");
session_start();
require_once __DIR__ . "/../../common/userdata.php";
require_once __DIR__ . "/../../common/database.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$user = UserData::getUser($_SESSION['user_id']);
if (!$user || !$user->is_admin) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$id = intval($_GET["id"] ?? 0);
$db = Database::getInstance();

$db->beginTransaction();
$stmt = $db->prepare("DELETE FROM product_variants WHERE product_id = ?");
$stmt->execute([$id]);

$stmt = $db->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);

$db->commit();

echo json_encode(["success" => true]);
