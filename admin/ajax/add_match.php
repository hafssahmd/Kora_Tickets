<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $home_team = trim($_POST['home_team']);
    $away_team = trim($_POST['away_team']);
    $stadium_id = (int)$_POST['stadium_id'];
    $match_date = $_POST['match_date'];
    $description = trim($_POST['description'] ?? '');

    // Validation des données
    $errors = [];
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
            $stmt = $conn->prepare("
                INSERT INTO matches (home_team, away_team, stadium_id, match_date, description)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$home_team, $away_team, $stadium_id, $match_date, $description]);

            $_SESSION['success'] = "Le match a été ajouté avec succès.";
            header('Location: ' . SITE_URL . '/admin/matches.php');
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Une erreur est survenue lors de l'ajout du match : " . $e->getMessage();
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