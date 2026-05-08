<?php
require_once 'config.php';

 $stmt = $pdo->query("SELECT * FROM menu_items ORDER BY id DESC");
 $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($items);
?>