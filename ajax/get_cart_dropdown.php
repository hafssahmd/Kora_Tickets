<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

if (!isLoggedIn()) {
    echo '<li class="text-center text-muted">Connectez-vous pour voir votre panier.</li>';
    exit;
}

$stmt = $conn->prepare("
    SELECT ci.quantity, tc.name, tc.price, m.home_team, m.away_team
    FROM carts c
    JOIN cart_items ci ON c.id = ci.cart_id
    JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
    JOIN matches m ON tc.match_id = m.id
    WHERE c.user_id = ?
    ORDER BY ci.id DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    echo '<li class="text-center text-muted">Votre panier est vide.</li>';
} else {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
        echo '<li class="mb-2 d-flex justify-content-between align-items-center">';
        echo '<span>' . htmlspecialchars($item['home_team'] . ' vs ' . $item['away_team']) . '<br><small>' . htmlspecialchars($item['name']) . '</small> <span class="badge bg-secondary">' . $item['quantity'] . '</span></span>';
        echo '<span>' . number_format($item['price'] * $item['quantity'], 2) . ' MAD</span>';
        echo '</li>';
    }
    echo '<li class="d-flex justify-content-between mt-2"><strong>Total</strong><strong>' . number_format($total, 2) . ' MAD</strong></li>';
    echo '<li><hr class="dropdown-divider"></li>';
    echo '<li class="text-center"><a href="cart.php" class="btn btn-primary btn-sm w-100">Voir le panier</a></li>';
} 