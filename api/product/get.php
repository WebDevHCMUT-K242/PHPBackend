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

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    echo json_encode(["error" => "Product not found"]);
    exit;
}

$stmt = $db->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$stmt->execute([$id]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "product" => $product,
    "variants" => $variants
]);
