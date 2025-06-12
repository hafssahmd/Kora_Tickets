<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = (int)$_POST['match_id'];
    $home_team = trim($_POST['home_team']);
    $away_team = trim($_POST['away_team']);
    $stadium_id = (int)$_POST['stadium_id'];
    $match_date = $_POST['match_date'];
    $description = trim($_POST['description'] ?? '');

    // Validation des données
    $errors = [];
    if (empty($match_id)) {
        $errors[] = "L'identifiant du match est requis.";
    }
    if (empty($home_team)) {
        $errors[] = "L'équipe à domicile est requise.";
    }
    if (empty($away_team)) {
        $errors[] = "L'équipe à l'extérieur est requise.";
    }
    if (empty($stadium_id)) {
        $errors[] = "Le stade est requis.";
    }
    if (empty($match_date)) {
        $errors[] = "La date du match est requise.";
    }

    if (empty($errors)) {
        try {
            // Vérifier si le match existe
            $stmt = $conn->prepare("SELECT id FROM matches WHERE id = ?");
            $stmt->execute([$match_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Le match n'existe pas.");
            }

            // Mettre à jour le match
            $stmt = $conn->prepare("
                UPDATE matches 
                SET home_team = ?, away_team = ?, stadium_id = ?, match_date = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$home_team, $away_team, $stadium_id, $match_date, $description, $match_id]);

            $_SESSION['success'] = "Le match a été mis à jour avec succès.";
            header('Location: ' . SITE_URL . '/admin/matches.php');
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Une erreur est survenue lors de la mise à jour du match : " . $e->getMessage();
            header('Location: ' . SITE_URL . '/admin/matches.php');
            exit();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header('Location: ' . SITE_URL . '/admin/matches.php');
        exit();
    }
} else {
    header('Location: ' . SITE_URL . '/admin/matches.php');
    exit();
} 