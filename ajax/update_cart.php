<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

// Vérifier si les données sont fournies
if (!isset($_POST['cart_item_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$cart_item_id = (int)$_POST['cart_item_id'];
$quantity = (int)$_POST['quantity'];

// Vérifier la quantité
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantité invalide']);
    exit;
}

try {
    // Vérifier si l'article appartient à l'utilisateur
    $stmt = $conn->prepare("
        SELECT ci.id, tc.available_quantity
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
        WHERE ci.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_item_id, $_SESSION['user_id']]);
    $cart_item = $stmt->fetch();

    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
        exit;
    }

    // Vérifier si la nouvelle quantité est disponible
    if ($cart_item['available_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Quantité non disponible']);
        exit;
    }

    // Mettre à jour la quantité
    $stmt = $conn->prepare("
        UPDATE cart_items 
        SET quantity = ? 
        WHERE id = ?
    ");
    $stmt->execute([$quantity, $cart_item_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour du panier : ' . $e->getMessage()]);
}
?> 