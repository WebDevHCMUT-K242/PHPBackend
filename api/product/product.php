<?php

header("Content-Type: application/json");

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

require_once __DIR__ . "/../../common/userdata.php";
require_once __DIR__ . "/../../common/database.php";

$user = UserData::getUser($_SESSION['user_id']);
if ($user === null || !$user->is_admin) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$action = $_GET["action"] ?? "";

if ($action === "get") {
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
    exit;
}

if ($action === "delete") {
    $id = intval($_GET["id"] ?? 0);
    $db = Database::getInstance();
    $db->beginTransaction();
    $stmt = $db->prepare("DELETE FROM product_variants WHERE product_id = ?");
    $stmt->execute([$id]);
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $db->commit();
    echo json_encode(["success" => true]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid data"]);
    exit;
}

if ($action === "save") {
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
    exit;
}

if ($action === "create") {
    $name = $data["name"] ?? "";
    $description = $data["description"] ?? "";
    $variants = $data["variants"] ?? [];

    $db = Database::getInstance();
    $stmt = $db->prepare("INSERT INTO products (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    $product_id = $db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO product_variants (product_id, name, image, price) VALUES (?, ?, ?, ?)");
    foreach ($variants as $variant) {
        $stmt->execute([
            $product_id,
            $variant["name"] ?? "",
            $variant["image"] ?? "",
            $variant["price"] ?? ""
        ]);
    }

    echo json_encode(["success" => true, "product_id" => $product_id]);
    exit;
}

http_response_code(400);
echo json_encode(["error" => "Invalid action"]);
