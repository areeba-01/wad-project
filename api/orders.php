<?php
require_once 'config.php';

// GET USER ORDERS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([]);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT o.id, o.total_amount, o.status, o.created_at, o.delivery_address,
               GROUP_CONCAT(m.name, ' x', oi.quantity SEPARATOR ', ') as items_list
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($orders);
}

// PLACE ORDER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["success" => false, "message" => "Please login to order."]);
        exit();
    }

    $data = json_decode(file_get_contents("php://input"));
    $items = $data->items; // Array of {id, qty}
    $total = $data->total;
    $address = $data->address;
    $phone = $data->phone;

    try {
        $pdo->beginTransaction();

        // Insert Order
        $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, phone) VALUES (?, ?, ?, ?)");
        $orderStmt->execute([$_SESSION['user_id'], $total, $address, $phone]);
        $orderId = $pdo->lastInsertId();

        // Insert Order Items
        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
        
        // We need price at time of order, let's fetch it quickly or trust frontend total
        foreach ($items as $item) {
            // Fetch current price to ensure data integrity
            $pStmt = $pdo->prepare("SELECT price FROM menu_items WHERE id = ?");
            $pStmt->execute([$item->id]);
            $price = $pStmt->fetchColumn();
            
            $itemStmt->execute([$orderId, $item->id, $item->qty, $price]);
        }

        $pdo->commit();
        echo json_encode(["success" => true, "orderId" => $orderId]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Order failed."]);
    }
}
?>