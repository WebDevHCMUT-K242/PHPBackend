<?php
// root/api/product/search.php

require_once __DIR__ . '/../../common/db.php';

$conn = DataBase::getConnection();
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
$offset = ($page - 1) * $limit;

// First, count total matching products
if (empty($search)) {
    $countSql = "
        SELECT COUNT(DISTINCT p.id) as total
        FROM products p
        LEFT JOIN variants v ON p.id = v.product_id
    ";
    $countStmt = $conn->prepare($countSql);
} else {
    $countSql = "
        SELECT COUNT(DISTINCT p.id) as total
        FROM products p
        LEFT JOIN variants v ON p.id = v.product_id
        WHERE
            p.name LIKE ? OR
            p.description LIKE ? OR
            v.name LIKE ?
    ";
    $countStmt = $conn->prepare($countSql);
    $searchParam = '%' . $search . '%';
    $countStmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
}

$countStmt->execute();
$countResult = $countStmt->get_result();
$total = $countResult->fetch_assoc()['total'];
$totalPages = ceil($total / $limit);
$isLastPage = $page >= $totalPages;

$countStmt->close();

// Now fetch product IDs for the current page
if (empty($search)) {
    $sql = "
        SELECT DISTINCT p.id
        FROM products p
        LEFT JOIN variants v ON p.id = v.product_id
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $limit, $offset);
} else {
    $sql = "
        SELECT DISTINCT p.id
        FROM products p
        LEFT JOIN variants v ON p.id = v.product_id
        WHERE
            p.name LIKE ? OR
            p.description LIKE ? OR
            v.name LIKE ?
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $searchParam, $searchParam, $searchParam, $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

$productIds = [];
while ($row = $result->fetch_assoc()) {
    $productIds[] = $row['id'];
}

echo json_encode([
    'page' => $page,
    'limit' => $limit,
    'product_ids' => $productIds,
    'total' => $total,
    'totalPages' => $totalPages,
    'isLastPage' => $isLastPage
]);

$stmt->close();
$conn->close();
