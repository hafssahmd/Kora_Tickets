<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer l'ID de la commande PayPal
$data = json_decode(file_get_contents('php://input'), true);
$orderID = $data['orderID'] ?? null;

if (!$orderID) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de commande PayPal manquant']);
    exit;
}

// Capture du paiement PayPal (API REST)
$paypal_url = (PAYPAL_MODE === 'sandbox')
    ? "https://api.sandbox.paypal.com/v2/checkout/orders/$orderID/capture"
    : "https://api.paypal.com/v2/checkout/orders/$orderID/capture";

// Récupérer le token d'accès PayPal
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, (PAYPAL_MODE === 'sandbox')
    ? "https://api.sandbox.paypal.com/v1/oauth2/token"
    : "https://api.paypal.com/v1/oauth2/token");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Accept-Language: en_US"
]);
curl_setopt($ch, CURLOPT_USERPWD, PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$result = curl_exec($ch);
$token = json_decode($result, true)['access_token'] ?? null;
curl_close($ch);

if (!$token) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur d\'authentification PayPal']);
    exit;
}

// Capture du paiement
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypal_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 201 && $http_code !== 200) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la capture PayPal']);
    exit;
}

// Enregistrer la commande dans la base de données
try {
    $conn->beginTransaction();

    // Récupérer les articles du panier
    $stmt = $conn->prepare("
        SELECT ci.*, tc.name as category_name, tc.price, m.home_team, m.away_team, m.match_date, s.name as stadium_name
        FROM carts c
        JOIN cart_items ci ON c.id = ci.cart_id
        JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception('Panier vide');
    }

    // Calculer le total
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }

    // Créer la commande
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, paypal_order_id, status) VALUES (?, ?, ?, 'completed')");
    $stmt->execute([$_SESSION['user_id'], $total, $orderID]);
    $order_id = $conn->lastInsertId();

    // Ajouter les tickets à la commande
    foreach ($cart_items as $item) {
        $stmt = $conn->prepare("INSERT INTO order_details (order_id, ticket_category_id, quantity, price_per_ticket) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $item['ticket_category_id'], $item['quantity'], $item['price']]);
        // Mettre à jour la quantité disponible
        $stmt2 = $conn->prepare("UPDATE ticket_categories SET available_quantity = available_quantity - ? WHERE id = ?");
        $stmt2->execute([$item['quantity'], $item['ticket_category_id']]);
    }

    // Vider le panier
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cart_items[0]['cart_id']]);
    $stmt = $conn->prepare("DELETE FROM carts WHERE id = ?");
    $stmt->execute([$cart_items[0]['cart_id']]);

    $conn->commit();

    // Récupérer l'email de l'utilisateur
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $user_email = $user['email'];

    // Générer le contenu du ticket
    $ticket_content = "Merci pour votre achat !\n\nVoici votre ticket :\n";
    foreach ($cart_items as $item) {
        $ticket_content .= "Match : " . $item['home_team'] . " vs " . $item['away_team'] . "\n";
        $ticket_content .= "Stade : " . $item['stadium_name'] . "\n";
        $ticket_content .= "Date : " . $item['match_date'] . "\n";
        $ticket_content .= "Catégorie : " . $item['category_name'] . "\n";
        $ticket_content .= "Quantité : " . $item['quantity'] . "\n";
        $ticket_content .= "-----------------------------\n";
    }
    $ticket_content .= "\nMontant total : " . number_format($total, 2) . " MAD\n";

    // Envoyer l'email
    mail($user_email, "Votre ticket Kora Tickets", $ticket_content);

    echo json_encode(['success' => true, 'order_id' => $order_id]);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement de la commande : ' . $e->getMessage()]);
} 