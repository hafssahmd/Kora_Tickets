<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Non autorisé']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Méthode non autorisée']));
}

$user_id = (int)($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'ID utilisateur invalide']));
}

// Empêcher la suppression de son propre compte
if ($user_id === $_SESSION['admin_id']) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Vous ne pouvez pas supprimer votre propre compte']));
}

try {
    // Vérifier si l'utilisateur existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']));
    }

    // Vérifier si l'utilisateur a des commandes
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Impossible de supprimer cet utilisateur car il a des commandes']));
    }

    // Démarrer une transaction
    $conn->beginTransaction();

    // Supprimer les articles du panier de l'utilisateur
    $stmt = $conn->prepare("
        DELETE ci FROM cart_items ci
        JOIN carts c ON ci.cart_id = c.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);

    // Supprimer le panier de l'utilisateur
    $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Supprimer l'utilisateur
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    // Valider la transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Une erreur est survenue lors de la suppression de l\'utilisateur']);
} 