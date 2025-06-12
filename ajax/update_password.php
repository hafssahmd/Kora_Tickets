<?php   
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Non autorisé']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'error' => 'Méthode non autorisée']));
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];

// Validation
if (empty($current_password)) {
    $errors[] = "Le mot de passe actuel est requis.";
}
if (empty($new_password)) {
    $errors[] = "Le nouveau mot de passe est requis.";
} elseif (strlen($new_password) < 8) {
    $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
}
if ($new_password !== $confirm_password) {
    $errors[] = "Les mots de passe ne correspondent pas.";
}

if (empty($errors)) {
    try {
        // Vérifier le mot de passe actuel
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!password_verify($current_password, $user['password'])) {
            $errors[] = "Le mot de passe actuel est incorrect.";
        } else {
            // Mettre à jour le mot de passe
            $stmt = $pdo->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");
            $stmt->execute([
                password_hash($new_password, PASSWORD_DEFAULT),
                $_SESSION['user_id']
            ]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = "Une erreur est survenue lors de la mise à jour du mot de passe.";
    }
}

if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => implode("<br>", $errors)]);
} 