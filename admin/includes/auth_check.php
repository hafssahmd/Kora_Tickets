<?php
// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Vérifier si l'utilisateur est un administrateur
$user_id = $_SESSION['user_id'];
$query = "SELECT role FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    // Rediriger vers la page d'accueil si l'utilisateur n'est pas admin
    header('Location: ../index.php');
    exit;
}
?> 