<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../common/db.php';
require_once __DIR__ . '/../../common/userdata.php';
require_once __DIR__ . '/../../common/productdata.php';

// if (!isset($_SESSION['user']) || !$_SESSION['user'] instanceof UserData || !$_SESSION['user']->is_admin) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

function sanitize($value) {
    return htmlspecialchars(strip_tags($value));
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET' && $action === 'get') {
    $product_id = intval($_GET['id'] ?? 0);
    $product = ProductData::getById($product_id);
    $variants = ProductData::getVariants($product_id);
    echo json_encode(['product' => $product, 'variants' => $variants]);
    exit;
}

if ($method === 'POST' && $action === 'save') {
    $input = json_decode(file_get_contents("php://input"), true);

    $product = new ProductData(
        intval($input['id'] ?? 0),
        sanitize($input['name'] ?? ''),
        sanitize($input['description'] ?? '')
    );

    $product_id = $product->save();

    ProductData::deleteVariants($product_id);

    $variants = array_map(function ($v) {
        return [
            'name' => sanitize($v['name'] ?? ''),
            'price' => sanitize($v['price'] ?? ''),
            'image' => sanitize($v['image'] ?? '')
        ];
    }, $input['variants'] ?? []);

    ProductData::insertVariants($product_id, $variants);

    echo json_encode(['success' => true, 'product_id' => $product_id]);
    exit;
}

if ($method === 'DELETE' && $action === 'delete') {
    $product_id = intval($_GET['id'] ?? 0);
    ProductData::deleteVariants($product_id);
    ProductData::delete($product_id);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
