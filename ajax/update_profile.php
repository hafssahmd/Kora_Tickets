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

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');

$errors = [];

// Validation
if (empty($first_name)) {
    $errors[] = "Le prénom est requis.";
}
if (empty($last_name)) {
    $errors[] = "Le nom est requis.";
}
if (empty($email)) {
    $errors[] = "L'email est requis.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "L'email n'est pas valide.";
}

if (empty($errors)) {
    try {
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            // Mettre à jour le profil
            $stmt = $pdo->prepare("
                UPDATE users
                SET first_name = ?, last_name = ?, email = ?
                WHERE id = ?
            ");
            $stmt->execute([$first_name, $last_name, $email, $_SESSION['user_id']]);

            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (PDOException $e) {
        $errors[] = "Une erreur est survenue lors de la mise à jour du profil.";
    }
}

if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => implode("<br>", $errors)]);
} 