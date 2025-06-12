<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($category_id <= 0) {
        $_SESSION['error'] = "L'identifiant de la catégorie est invalide.";
        redirect('/admin/ticket_categories.php');
    }

    try {
        // Vérifier si la catégorie existe
        $stmt = $pdo->prepare("SELECT id FROM ticket_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "La catégorie n'existe pas.";
            redirect('/admin/ticket_categories.php');
        }

        // Vérifier si la catégorie est utilisée dans des commandes
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE oi.ticket_category_id = ? AND o.status != 'cancelled'
        ");
        $stmt->execute([$category_id]);
        $result = $stmt->fetch();
        if ($result['count'] > 0) {
            $_SESSION['error'] = "Impossible de supprimer cette catégorie car elle est utilisée dans des commandes.";
            redirect('/admin/ticket_categories.php');
        }

        // Supprimer la catégorie
        $stmt = $pdo->prepare("DELETE FROM ticket_categories WHERE id = ?");
        $stmt->execute([$category_id]);

        $_SESSION['success'] = "La catégorie a été supprimée avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Une erreur est survenue lors de la suppression de la catégorie.";
    }

    redirect('/admin/ticket_categories.php');
} else {
    redirect('/admin/ticket_categories.php');
} 