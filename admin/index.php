<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

// Redirection vers le dashboard
header('Location: dashboard.php');
exit;
?> 