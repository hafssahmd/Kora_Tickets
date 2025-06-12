<?php
// Paramètres de connexion à la base de données
$db_host = 'localhost';
$db_name = 'kora_tickets';
$db_user = 'root';
$db_pass = '';

try {
    // Création de la connexion PDO
    $conn = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // En cas d'erreur, afficher un message et arrêter l'exécution
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?> 