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
if (!isset($_POST['ticket_category_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$ticket_category_id = (int)$_POST['ticket_category_id'];
$quantity = (int)$_POST['quantity'];

// Vérifier la quantité
if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantité invalide']);
    exit;
}

try {
    // Vérifier si la catégorie de billet existe et si la quantité est disponible
    $stmt = $conn->prepare("
        SELECT available_quantity 
        FROM ticket_categories 
        WHERE id = ?
    ");
    $stmt->execute([$ticket_category_id]);
    $category = $stmt->fetch();

    if (!$category) {
        echo json_encode(['success' => false, 'message' => 'Catégorie de billet invalide']);
        exit;
    }

    if ($category['available_quantity'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Quantité non disponible']);
        exit;
    }

    // Récupérer ou créer le panier de l'utilisateur
    $stmt = $conn->prepare("
        SELECT id FROM carts 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart = $stmt->fetch();

    if (!$cart) {
        $stmt = $conn->prepare("
            INSERT INTO carts (user_id) 
            VALUES (?)
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $cart_id = $conn->lastInsertId();
    } else {
        $cart_id = $cart['id'];
    }

    // Vérifier si l'article existe déjà dans le panier
    $stmt = $conn->prepare("
        SELECT id, quantity 
        FROM cart_items 
        WHERE cart_id = ? AND ticket_category_id = ?
    ");
    $stmt->execute([$cart_id, $ticket_category_id]);
    $cart_item = $stmt->fetch();

    if ($cart_item) {
        // Mettre à jour la quantité
        $new_quantity = $cart_item['quantity'] + $quantity;
        if ($category['available_quantity'] < $new_quantity) {
            echo json_encode(['success' => false, 'message' => 'Quantité totale non disponible']);
            exit;
        }
        $stmt = $conn->prepare("
            UPDATE cart_items 
            SET quantity = ? 
            WHERE id = ?
        ");
        $stmt->execute([$new_quantity, $cart_item['id']]);
    } else {
        // Ajouter un nouvel article
        $stmt = $conn->prepare("
            INSERT INTO cart_items (cart_id, ticket_category_id, quantity) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$cart_id, $ticket_category_id, $quantity]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'ajout au panier : ' . $e->getMessage()]);
}
?> 