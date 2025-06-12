<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$count = 0;

if (isLoggedIn()) {
    $stmt = $conn->prepare("
        SELECT SUM(ci.quantity) as count
        FROM carts c
        JOIN cart_items ci ON c.id = ci.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    $count = (int)($row['count'] ?? 0);
}

header('Content-Type: application/json');
echo json_encode(['count' => $count]);
?> 