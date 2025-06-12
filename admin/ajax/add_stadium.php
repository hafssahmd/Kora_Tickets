<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    // Validation
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
            $stmt = $conn->prepare("
                INSERT INTO stadiums (name, city, capacity, description)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $city, $capacity, $description]);

            $_SESSION['success'] = "Le stade a été ajouté avec succès.";
            redirect('/admin/stadiums.php');
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de l'ajout du stade.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        redirect('/admin/stadiums.php');
    }
} else {
    redirect('/admin/stadiums.php');
}