<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = (int)$_POST['match_id'];

    if (empty($match_id)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => "L'identifiant du match est requis."]);
        exit();
    }

    try {
        // Vérifier si le match existe
        $stmt = $conn->prepare("SELECT id FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Le match n'existe pas.");
        }

        // Vérifier si des commandes existent pour ce match
        $stmt = $conn->prepare("
            SELECT COUNT(*) 
            FROM order_items oi 
            JOIN ticket_categories tc ON oi.ticket_category_id = tc.id 
            WHERE tc.match_id = ?
        ");
        $stmt->execute([$match_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Impossible de supprimer ce match car des commandes y sont associées.");
        }

        // Démarrer la transaction
        $conn->beginTransaction();

        // Supprimer les catégories de billets associées
        $stmt = $conn->prepare("DELETE FROM ticket_categories WHERE match_id = ?");
        $stmt->execute([$match_id]);

        // Supprimer le match
        $stmt = $conn->prepare("DELETE FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);

        // Valider la transaction
        $conn->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit();
} 