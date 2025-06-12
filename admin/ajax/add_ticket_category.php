<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = (int)($_POST['match_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    // Validation
    if ($match_id <= 0) {
        $errors[] = "Le match est requis.";
    }
    if (empty($name)) {
        $errors[] = "Le nom de la catégorie est requis.";
    }
    if ($price <= 0) {
        $errors[] = "Le prix doit être supérieur à 0.";
    }
    if ($quantity <= 0) {
        $errors[] = "La quantité doit être supérieure à 0.";
    }

    if (empty($errors)) {
        try {
            // Vérifier si le match existe
            $stmt = $conn->prepare("SELECT id FROM matches WHERE id = ?");
            $stmt->execute([$match_id]);
            if (!$stmt->fetch()) {
                $errors[] = "Le match n'existe pas.";
            } else {
                // Ajouter la catégorie
                $stmt = $conn->prepare("
                    INSERT INTO ticket_categories (match_id, name, price, total_quantity, available_quantity, description)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$match_id, $name, $price, $quantity, $quantity, $description]);

                $_SESSION['success'] = "La catégorie a été ajoutée avec succès.";
                redirect('/admin/ticket_categories.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de l'ajout de la catégorie.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        redirect('/admin/ticket_categories.php');
    }
} else {
    redirect('/admin/ticket_categories.php');
} 