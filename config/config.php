<?php
session_start();

// Configuration de base
define('SITE_NAME', 'Kora Tickets');
define('SITE_URL', 'http://localhost/Kora_Tickets');
define('ADMIN_EMAIL', 'admin@koratickets.com');

// Configuration PayPal
define('PAYPAL_CLIENT_ID', 'AWu5Am_Wf0v6DOhL1aimAHnOkEw9hgpig641YnYrCuFZIly-yhZFKR4PERzmpgjq8dZf9Sjgp5d2FsSs');
define('PAYPAL_CLIENT_SECRET', 'EC5M1A_x0s4_LLADQaaDokmN7U-aJJk7EosWIWhzsr67TQGaSapWQGvu9OIGCUU3HlVoobHDpWq9j8nN');
define('PAYPAL_MODE', 'sandbox'); // Change to 'live' for production

// Chemins des dossiers
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonctions utilitaires
function redirect($path) {
    header("Location: " . SITE_URL . $path);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    error_log("Checking admin status - Session role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'not set'));
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect('/index.php');
    }
}

// Fonction pour nettoyer les entrées
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fonction pour générer un token CSRF
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Fonction pour vérifier le token CSRF
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    return true;
}
?> 