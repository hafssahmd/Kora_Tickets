<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_admin = (int)($_POST['is_admin'] ?? 0);

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
    if (empty($password)) {
        $errors[] = "Le mot de passe est requis.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if (empty($errors)) {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé.";
            } else {
                // Ajouter l'utilisateur
                $stmt = $conn->prepare("
                    INSERT INTO users (first_name, last_name, email, password, is_admin)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $first_name,
                    $last_name,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $is_admin
                ]);

                $_SESSION['success'] = "L'utilisateur a été ajouté avec succès.";
                redirect('/admin/users.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de l'ajout de l'utilisateur.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        redirect('/admin/users.php');
    }
} else {
    redirect('/admin/users.php');
} 