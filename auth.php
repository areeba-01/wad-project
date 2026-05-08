<?php
require_once 'config.php';

 $data = json_decode(file_get_contents("php://input"));
 $action = $_GET['action'] ?? '';

// SIGNUP
if ($action === 'signup') {
    $username = $data->username ?? '';
    $email = $data->email ?? '';
    $password = $data->password ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "Email or Username already exists."]);
        exit();
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $hash])) {
        $newId = $pdo->lastInsertId();
        $_SESSION['user_id'] = $newId;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = 0;
        echo json_encode(["success" => true, "user" => ["id" => $newId, "username" => $username, "is_admin" => false]]);
    } else {
        echo json_encode(["success" => false, "message" => "Signup failed."]);
    }
}