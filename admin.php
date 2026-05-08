<?php
require_once 'config.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(["message" => "Access Denied"]);
    exit();
}

// GET ALL ORDERS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT o.id, o.total_amount, o.status, o.created_at, u.username, u.email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

// UPDATE ORDER STATUS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $id = $data->id;
    $status = $data->status;

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if($stmt->execute([$status, $id])) {
        echo json_encode(["success" => true]);
    }
}
?>