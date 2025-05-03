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

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid data"]);
    exit;
}

$id = intval($data["id"] ?? 0);
$name = $data["name"] ?? "";
$description = $data["description"] ?? "";
$variants = $data["variants"] ?? [];

$db = Database::getInstance();

$stmt = $db->prepare("UPDATE products SET name = ?, description = ? WHERE id = ?");
$stmt->execute([$name, $description, $id]);

$stmt = $db->prepare("DELETE FROM product_variants WHERE product_id = ?");
$stmt->execute([$id]);

$stmt = $db->prepare("INSERT INTO product_variants (product_id, name, image, price) VALUES (?, ?, ?, ?)");
foreach ($variants as $variant) {
    $stmt->execute([
        $id,
        $variant["name"] ?? "",
        $variant["image"] ?? "",
        $variant["price"] ?? ""
    ]);
}

echo json_encode(["success" => true]);
