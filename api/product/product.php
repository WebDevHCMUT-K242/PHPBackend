<?php

header("Content-Type: application/json");
session_start();


require_once __DIR__ . "/../../common/userdata.php";
require_once __DIR__ . "/../../common/db.php";




$action = $_GET["action"] ?? "";

$conn = Database::getConnection();

if ($action === "get") {
    $id = intval($_GET["id"] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        http_response_code(404);
        echo json_encode(["error" => "Product not found"]);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM variants WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $variants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode(["product" => $product, "variants" => $variants]);

} else if ($action === "delete") {
    $user = UserData::getUser($_SESSION['user_id']);
    if ($user === null || !$user->is_admin) {
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }
    $conn->begin_transaction();
    try {
        $productId = intval($_GET["id"]);

        $stmt = $conn->prepare("DELETE FROM variants WHERE product_id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["error" => "Database error", "details" => $e->getMessage()]);
    }
    $conn->commit();
    echo json_encode(["success" => true, "id" => $productId]);
} elseif ($action === "save" || $action === "create") {
    $user = UserData::getUser($_SESSION['user_id']);
    if ($user === null || !$user->is_admin) {
        http_response_code(403);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data["name"], $data["description"], $data["variants"])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing data"]);
        exit;
    }

    $conn->begin_transaction();

    try {
        if ($action === "create") {
            $stmt = $conn->prepare("INSERT INTO products (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $data["name"], $data["description"]);
            $stmt->execute();
            $productId = $stmt->insert_id;
        } else {
            $productId = intval($data["id"]);
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ? WHERE id = ?");
            $stmt->bind_param("ssi", $data["name"], $data["description"], $productId);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM variants WHERE product_id = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
        }

        $stmt = $conn->prepare("INSERT INTO variants (product_id, name, price, image) VALUES (?, ?, ?, ?)");
        foreach ($data["variants"] as $variant) {
            $stmt->bind_param("isss", $productId, $variant["name"], $variant["price"], $variant["image"]);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode(["success" => true, "id" => $productId]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(["error" => "Database error", "details" => $e->getMessage()]);
    }

} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid action"]);
}
