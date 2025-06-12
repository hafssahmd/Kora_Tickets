<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stadium_id = (int)($_POST['stadium_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    // Validation
    if ($stadium_id <= 0) {
        $errors[] = "ID de stade invalide.";
    }
    if (empty($name)) {
        $errors[] = "Le nom du stade est requis.";
    }
    if (empty($city)) {
        $errors[] = "La ville est requise.";
    }
    if ($capacity <= 0) {
        $errors[] = "La capacité doit être supérieure à 0.";
    }

    if (empty($errors)) {
        try {
            // Vérifier si le stade existe
            $stmt = $conn->prepare("SELECT id FROM stadiums WHERE id = ?");
            $stmt->execute([$stadium_id]);
            if (!$stmt->fetch()) {
                $errors[] = "Le stade n'existe pas.";
            } else {
                // Mettre à jour le stade
                $stmt = $conn->prepare("
                    UPDATE stadiums 
                    SET name = ?, city = ?, capacity = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $city, $capacity, $description, $stadium_id]);

                $_SESSION['success'] = "Le stade a été modifié avec succès.";
                redirect('/admin/stadiums.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de la modification du stade.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        redirect('/admin/stadiums.php');
    }
} else {
    redirect('/admin/stadiums.php');
} 