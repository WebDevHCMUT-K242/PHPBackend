<?php
// api/order/order.php
header('Content-Type: application/json');

// Load database connection
require_once __DIR__ . '/../../common/db.php';
session_start();
$conn = Database::getConnection();

// --- Authentication ---
$user_id  = $_SESSION['user_id']  ?? null;
$is_admin = $_SESSION['is_admin'] ?? false;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// --- Request Routing ---
$action = $_REQUEST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    // Add item to shopping cart
    case 'cart_add':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
        $product_id = intval($_POST['product_id'] ?? 0);
        $variant_id = intval($_POST['variant_id'] ?? 0);
        $amount     = max(1, intval($_POST['amount'] ?? 1));

        // Find or create cart order_id
        $stmt = $conn->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'shopping_cart' LIMIT 1");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $order_id = $row['order_id'];
        } else {
            $order_id = uniqid('order_', true);
        }

        // Check for existing cart item of same variant
        $stmt2 = $conn->prepare(
            "SELECT id, amount FROM orders 
             WHERE user_id = ? AND order_id = ? AND product_id = ? 
               AND variant_id = ? AND status = 'shopping_cart' LIMIT 1"
        );
        $stmt2->bind_param('isii', $user_id, $order_id, $product_id, $variant_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($item = $res2->fetch_assoc()) {
            // Update amount
            $newAmount = intval($item['amount']) + $amount;
            $upd = $conn->prepare("UPDATE orders SET amount = ? WHERE id = ?");
            $upd->bind_param('ii', $newAmount, $item['id']);
            $upd->execute();
        } else {
            // Insert new cart item
            $ins = $conn->prepare(
                "INSERT INTO orders 
                 (user_id, order_id, product_id, variant_id, amount, status) 
                 VALUES (?, ?, ?, ?, ?, 'shopping_cart')"
            );
            $ins->bind_param('isiii', $user_id, $order_id, $product_id, $variant_id, $amount);
            $ins->execute();
        }

        echo json_encode(['success' => true, 'order_id' => $order_id]);
        break;

    // Remove item from cart
    case 'cart_remove':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
        $item_id = intval($_POST['item_id'] ?? 0);
        $stmt = $conn->prepare(
            "DELETE FROM orders 
             WHERE id = ? AND user_id = ? AND status = 'shopping_cart'"
        );
        $stmt->bind_param('ii', $item_id, $user_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
        break;

    // Update variant or amount in cart
    case 'cart_update':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
        $item_id    = intval($_POST['item_id'] ?? 0);
        $variant_id = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;
        $amount     = isset($_POST['amount']) ? max(1, intval($_POST['amount'])) : null;

        $fields = [];
        $types  = '';
        $params = [];
        if ($variant_id !== null) {
            $fields[]  = 'variant_id = ?';
            $types    .= 'i';
            $params[]  = $variant_id;
        }
        if ($amount !== null) {
            $fields[]  = 'amount = ?';
            $types    .= 'i';
            $params[]  = $amount;
        }
        if (empty($fields)) {
            echo json_encode(['error' => 'No fields to update']);
            exit;
        }

        $sql = "UPDATE orders SET " . implode(', ', $fields) . " 
                WHERE id = ? AND user_id = ? AND status = 'shopping_cart'";
        $types   .= 'ii';
        $params[] = $item_id;
        $params[] = $user_id;

        $upd = $conn->prepare($sql);
        $upd->bind_param($types, ...$params);
        $upd->execute();

        echo json_encode(['success' => true]);
        break;

    // List cart items
    case 'cart_list':
        $stmt = $conn->prepare(
            "SELECT o.id, o.order_id, o.product_id, o.variant_id, o.amount,
                    v.price, p.name AS product_name, v.name AS variant_name, v.image
             FROM orders o
             JOIN variants v ON o.variant_id = v.id
             JOIN products p ON o.product_id = p.id
             WHERE o.user_id = ? AND o.status = 'shopping_cart'"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        $items = [];
        while ($row = $res->fetch_assoc()) {
            $row['total_price'] = floatval($row['price']) * intval($row['amount']);
            $items[] = $row;
        }
        echo json_encode(['items' => $items]);
        break;

    // Checkout: change cart to pending
    case 'cart_checkout':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
        $stmt = $conn->prepare(
            "UPDATE orders SET status = 'pending' 
             WHERE user_id = ? AND status = 'shopping_cart'"
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
        break;

    // Admin: update order status
    case 'order_update_status':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            exit;
        }
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
            exit;
        }
        $order_id   = $_POST['order_id'] ?? '';
        $new_status = $_POST['status']   ?? '';
        $allowed    = ['shipping', 'delivered', 'canceled'];
        if (!in_array($new_status, $allowed)) {
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }

        $stmt = $conn->prepare(
            "UPDATE orders SET status = ? 
             WHERE order_id = ?"
        );
        $stmt->bind_param('ss', $new_status, $order_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
        break;

    // List orders with pagination (includes display_name)
    case 'order_list':
        $page    = max(1, intval($_GET['page']  ?? 1));
        $limit   = max(1, intval($_GET['limit'] ?? 10));
        $offset  = ($page - 1) * $limit;

        // Count total entries
        if ($is_admin) {
            $count_sql  = "SELECT COUNT(*) AS cnt FROM orders";
            $count_stmt = $conn->prepare($count_sql);
        } else {
            $count_sql  = "SELECT COUNT(*) AS cnt FROM orders WHERE user_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param('i', $user_id);
        }
        $count_stmt->execute();
        $total       = $count_stmt->get_result()->fetch_assoc()['cnt'];
        $total_pages = ceil($total / $limit);

        // Fetch paginated data with user display_name
        if ($is_admin) {
            $sql = "SELECT o.id, o.user_id, u.display_name AS user_display_name, 
                           o.order_id, o.product_id, o.variant_id, o.amount, 
                           o.status, o.created_at, v.price, p.name AS product_name, v.name AS variant_name
                    FROM orders o
                    JOIN users u ON o.user_id = u.id
                    JOIN variants v ON o.variant_id = v.id
                    JOIN products p ON o.product_id = p.id
                    ORDER BY o.created_at DESC
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
        } else {
            $sql = "SELECT o.id, o.user_id, u.display_name AS user_display_name, 
                           o.order_id, o.product_id, o.variant_id, o.amount, 
                           o.status, o.created_at, v.price, p.name AS product_name, v.name AS variant_name
                    FROM orders o
                    JOIN users u ON o.user_id = u.id
                    JOIN variants v ON o.variant_id = v.id
                    JOIN products p ON o.product_id = p.id
                    WHERE o.user_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $user_id, $limit, $offset);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $orders = [];
        while ($row = $res->fetch_assoc()) {
            $row['total_price'] = floatval($row['price']) * intval($row['amount']);
            $orders[] = $row;
        }

        echo json_encode([
            'orders'       => $orders,
            'current_page' => $page,
            'total_pages'  => $total_pages
        ]);
        break;

    // Invalid action
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
