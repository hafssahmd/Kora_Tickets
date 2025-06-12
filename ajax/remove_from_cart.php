<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

// Vérifier si l'ID de l'article est fourni
if (!isset($_POST['cart_item_id'])) {
    echo json_encode(['success' => false, 'message' => "ID de l'article manquant"]);
    exit;
}

$cart_item_id = (int)$_POST['cart_item_id'];

try {
    // Vérifier si l'article appartient à l'utilisateur
    $stmt = $conn->prepare("
        SELECT ci.id
        FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        WHERE ci.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_item_id, $_SESSION['user_id']]);
    $cart_item = $stmt->fetch();

    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Article non trouvé']);
        exit;
    }

    // Supprimer l'article
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
    $stmt->execute([$cart_item_id]);

    // Vérifier si le panier est vide
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM cart_items
        WHERE cart_id = (
            SELECT id FROM carts WHERE user_id = ?
        )
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch();

    // Si le panier est vide, le supprimer
    if ($cart_count['count'] == 0) {
        $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Erreur lors de la suppression de l'article : " . $e->getMessage()]);
}
?> 