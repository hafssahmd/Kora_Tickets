<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $match_id = (int)$_GET['id'];

    try {
        $stmt = $conn->prepare("
            SELECT m.*, s.name as stadium_name
            FROM matches m
            JOIN stadiums s ON m.stadium_id = s.id
            WHERE m.id = ?
        ");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$match) {
            throw new Exception("Le match n'existe pas.");
        }

        header('Content-Type: application/json');
        echo json_encode($match);
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Requête invalide']);
    exit();
} 