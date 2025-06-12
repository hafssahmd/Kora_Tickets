<?php
// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Fonction pour vérifier si la page active correspond à l'URL
function isActive($page) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return $current_page === $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #2c3e50;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
        }
        .sidebar .nav-link:hover {
            color: white;
            background-color: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,.2);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3 text-center">
                    <h4>Administration</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('matches.php'); ?>" href="<?php echo SITE_URL; ?>/admin/matches.php">
                            <i class="fas fa-futbol"></i> Matchs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('stadiums.php'); ?>" href="<?php echo SITE_URL; ?>/admin/stadiums.php">
                            <i class="fas fa-stadium"></i> Stades
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('ticket_categories.php'); ?>" href="<?php echo SITE_URL; ?>/admin/ticket_categories.php">
                            <i class="fas fa-ticket-alt"></i> Catégories de billets
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('orders.php'); ?>" href="<?php echo SITE_URL; ?>/admin/orders.php">
                            <i class="fas fa-shopping-cart"></i> Commandes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('users.php'); ?>" href="<?php echo SITE_URL; ?>/admin/users.php">
                            <i class="fas fa-users"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('admins.php'); ?>" href="<?php echo SITE_URL; ?>/admin/admins.php">
                            <i class="fas fa-user-shield"></i> Administrateurs
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="<?php echo SITE_URL; ?>/admin/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Top navbar -->
                <nav class="navbar navbar-expand-lg navbar-light mb-4">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbarNav">
                            <ul class="navbar-nav ms-auto">
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle"></i>
                                        <?php echo htmlspecialchars($_SESSION['admin_name'] ?? ''); ?>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/profile.php">
                                                <i class="fas fa-user-cog"></i> Profil
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/admin/logout.php">
                                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>

                <!-- Page content -->
                <div class="container-fluid"> 