<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les statistiques
try {
    // Nombre total d'utilisateurs
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Nombre total de commandes
    $stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Chiffre d'affaires total
    $stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Nombre total de billets vendus
    $stmt = $conn->query("SELECT SUM(quantity) as total FROM order_details");
    $totalTickets = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Statistiques des 30 derniers jours
    $stmt = $conn->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as orders_count,
            SUM(total_amount) as daily_revenue,
            (SELECT SUM(quantity) FROM order_details od WHERE od.order_id IN 
                (SELECT id FROM orders WHERE DATE(created_at) = DATE(o.created_at))) as tickets_sold
        FROM orders o
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques par stade
    $stmt = $conn->query("
        SELECT 
            s.name as stadium_name,
            COUNT(DISTINCT m.id) as matches_count,
            SUM(tc.total_quantity) as total_tickets,
            (SELECT SUM(od.quantity) 
             FROM order_details od 
             JOIN ticket_categories tc2 ON od.ticket_category_id = tc2.id 
             JOIN matches m2 ON tc2.match_id = m2.id 
             WHERE m2.stadium_id = s.id) as tickets_sold
        FROM stadiums s
        LEFT JOIN matches m ON s.id = m.stadium_id
        LEFT JOIN ticket_categories tc ON m.id = tc.match_id
        GROUP BY s.id
        ORDER BY tickets_sold DESC
    ");
    $stadiumStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Commandes récentes
    $stmt = $conn->query("
        SELECT o.*, u.username, u.email,
               COUNT(od.id) as total_items,
               SUM(od.quantity) as total_tickets
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_details od ON o.id = od.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Matchs à venir
    $stmt = $conn->query("
        SELECT m.*, s.name as stadium_name,
               (SELECT COUNT(*) FROM ticket_categories tc WHERE tc.match_id = m.id) as categories_count,
               (SELECT SUM(tc.quantity) FROM ticket_categories tc WHERE tc.match_id = m.id) as total_tickets
        FROM matches m
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE m.match_date > NOW()
        ORDER BY m.match_date ASC
        LIMIT 5
    ");
    $upcomingMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques des ventes par catégorie
    $stmt = $conn->query("
        SELECT tc.name, SUM(od.quantity) as total_sold, SUM(od.price_per_ticket * od.quantity) as total_revenue
        FROM order_details od
        JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        JOIN orders o ON od.order_id = o.id
        WHERE o.status = 'completed'
        GROUP BY tc.id
        ORDER BY total_revenue DESC
        LIMIT 5
    ");
    $categoryStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des statistiques : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            opacity: 0.8;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<?php
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
?>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4">
        <h1 class="mb-4">Tableau de bord</h1>

        <!-- Cartes de statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Utilisateurs</h6>
                                <h2 class="mb-0"><?php echo number_format($totalUsers); ?></h2>
                            </div>
                            <i class="fas fa-users stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Commandes</h6>
                                <h2 class="mb-0"><?php echo number_format($totalOrders); ?></h2>
                            </div>
                            <i class="fas fa-shopping-cart stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Billets vendus</h6>
                                <h2 class="mb-0"><?php echo number_format($totalTickets); ?></h2>
                            </div>
                            <i class="fas fa-ticket-alt stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Chiffre d'affaires</h6>
                                <h2 class="mb-0"><?php echo number_format($totalRevenue, 2); ?> €</h2>
                            </div>
                            <i class="fas fa-euro-sign stat-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique d'évolution des ventes -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Évolution des ventes (30 derniers jours)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Commandes récentes -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Commandes récentes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Total</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo number_format($order['total_amount'], 2); ?> €</td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matchs à venir -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Matchs à venir</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Match</th>
                                        <th>Stade</th>
                                        <th>Date</th>
                                        <th>Billets</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcomingMatches as $match): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?></td>
                                        <td><?php echo htmlspecialchars($match['stadium_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?></td>
                                        <td>
                                            <?php echo $match['categories_count']; ?> catégories<br>
                                            <small><?php echo number_format($match['total_tickets']); ?> billets</small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques par stade -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Statistiques par stade</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Stade</th>
                                        <th>Matchs</th>
                                        <th>Billets disponibles</th>
                                        <th>Billets vendus</th>
                                        <th>Taux de vente</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stadiumStats as $stadium): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stadium['stadium_name']); ?></td>
                                        <td><?php echo number_format($stadium['matches_count']); ?></td>
                                        <td><?php echo number_format($stadium['total_tickets']); ?></td>
                                        <td><?php echo number_format($stadium['tickets_sold'] ?? 0); ?></td>
                                        <td>
                                            <?php
                                            $sold = $stadium['tickets_sold'] ?? 0;
                                            $total = $stadium['total_tickets'];
                                            $rate = $total > 0 ? ($sold / $total) * 100 : 0;
                                            ?>
                                            <div class="progress">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $rate; ?>%"
                                                     aria-valuenow="<?php echo $rate; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    <?php echo number_format($rate, 1); ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique des ventes par catégorie -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ventes par catégorie</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Graphique d'évolution des ventes
        const dailyData = <?php echo json_encode(is_array($dailyStats) ? $dailyStats : []); ?>;
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(item => item.date),
                datasets: [{
                    label: 'Commandes',
                    data: dailyData.map(item => item.orders_count),
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    yAxisID: 'y',
                    fill: true
                }, {
                    label: 'Revenus (€)',
                    data: dailyData.map(item => item.daily_revenue),
                    borderColor: 'rgba(255, 159, 64, 1)',
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                    yAxisID: 'y1',
                    fill: true
                }, {
                    label: 'Billets vendus',
                    data: dailyData.map(item => item.tickets_sold),
                    borderColor: 'rgba(153, 102, 255, 1)',
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    yAxisID: 'y',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Nombre'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenus (€)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Graphique des ventes par catégorie
        const categoryData = <?php echo json_encode(is_array($categoryStats) ? $categoryStats : []); ?>;
        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: categoryData.map(item => item.name),
                datasets: [{
                    label: 'Billets vendus',
                    data: categoryData.map(item => item.total_sold),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                }, {
                    label: 'Revenus (€)',
                    data: categoryData.map(item => item.total_revenue),
                    backgroundColor: 'rgba(255, 206, 86, 0.5)',
                    borderColor: 'rgba(255, 206, 86, 1)',
                    borderWidth: 1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de billets'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Revenus (€)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 