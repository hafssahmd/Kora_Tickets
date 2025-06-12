<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

try {
    // Récupérer les articles du panier
    $stmt = $conn->prepare("
        SELECT ci.quantity, tc.price, tc.name as category_name,
               m.home_team, m.away_team, m.match_date,
               s.name as stadium_name
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Panier vide']);
        exit;
    }

    // Calculer le total
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Créer la commande PayPal
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYPAL_MODE === 'sandbox' 
        ? 'https://api-m.sandbox.paypal.com/v2/checkout/orders'
        : 'https://api-m.paypal.com/v2/checkout/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'amount' => [
                'currency_code' => 'MAD',
                'value' => number_format($total, 2, '.', '')
            ],
            'description' => 'Billets de match de football'
        ]]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET)
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 201) {
        $order = json_decode($response, true);
        echo json_encode(['id' => $order['id']]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la création de la commande PayPal']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?> 