<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php"><?php echo SITE_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/matches.php">Matchs</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php 
                    // Debug information
                    echo "<!-- Debug: ";
                    echo "isLoggedIn: " . (isLoggedIn() ? 'true' : 'false') . ", ";
                    echo "isAdmin: " . (isAdmin() ? 'true' : 'false') . ", ";
                    if (isset($_SESSION['user_role'])) {
                        echo "user_role: " . $_SESSION['user_role'];
                    }
                    echo " -->";
                    
                    // Admin button - now always visible
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/index.php" title="Administration">
                            <i class="fas fa-user-shield"></i> Admin
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <!-- Cart dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle position-relative" href="#" id="cartDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-shopping-cart"></i> Panier
                                <span class="badge bg-danger cart-count position-absolute top-0 start-100 translate-middle">0</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="cartDropdown"
                                style="min-width: 300px;" id="cart-dropdown-content">
                                <li class="text-center text-muted">Chargement...</li>
                            </ul>
                        </li>

                        <!-- Profile dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">Mon Profil</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/orders.php">Mes Commandes</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container mt-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                <?php
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- SCRIPTS JS - Déplacés ici pour éviter les conflits -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM loaded');
            console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
            console.log('Dropdown function:', typeof bootstrap.Dropdown !== 'undefined');
            
            // Mettre à jour le compteur du panier
            updateCartCount();

            // Initialiser tous les dropdowns manuellement
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });

            // Gestion spéciale pour le dropdown du panier
            var cartDropdown = document.getElementById('cartDropdown');
            if (cartDropdown) {
                cartDropdown.addEventListener('shown.bs.dropdown', function () {
                    console.log('Cart dropdown opened');
                    loadCartDropdown();
                });
            }
        });
    </script>
</body>

</html>