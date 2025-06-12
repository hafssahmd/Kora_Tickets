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

$order_id = (int)($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';

if ($order_id <= 0) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'ID de commande invalide']));
}

if (!in_array($status, ['completed', 'cancelled'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Statut invalide']));
}

try {
    // Vérifier si la commande existe et est en attente
    $stmt = $conn->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Commande non trouvée']));
    }

    if ($order['status'] !== 'pending') {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'error' => 'Seules les commandes en attente peuvent être modifiées']));
    }

    // Démarrer une transaction
    $conn->beginTransaction();

    // Mettre à jour le statut de la commande
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);

    // Si la commande est annulée, remettre les billets en stock
    if ($status === 'cancelled') {
        $stmt = $conn->prepare("
            UPDATE ticket_categories tc
            JOIN order_details od ON tc.id = od.ticket_category_id
            SET tc.available_quantity = tc.available_quantity + od.quantity
            WHERE od.order_id = ?
        ");
        $stmt->execute([$order_id]);
    }

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
    echo json_encode(['success' => false, 'error' => 'Une erreur est survenue lors de la mise à jour du statut']);
} 