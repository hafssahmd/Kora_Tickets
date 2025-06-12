<?php
date_default_timezone_set('Africa/Casablanca');
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Récupérer les matchs (diagnostic : sans condition de date)
try {
    $stmt = $conn->query("
        SELECT m.*, s.name as stadium_name, s.location as city,
               (SELECT COUNT(*) FROM ticket_categories tc WHERE tc.match_id = m.id) as categories_count,
               (SELECT MIN(tc.price) FROM ticket_categories tc WHERE tc.match_id = m.id) as min_price,
               (SELECT MAX(tc.price) FROM ticket_categories tc WHERE tc.match_id = m.id) as max_price,
               (SELECT SUM(tc.total_quantity) FROM ticket_categories tc WHERE tc.match_id = m.id) as total_tickets,
               (SELECT SUM(od.quantity) FROM order_details od 
                JOIN ticket_categories tc ON od.ticket_category_id = tc.id 
                WHERE tc.match_id = m.id) as tickets_sold
        FROM matches m
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE 1
        ORDER BY m.match_date ASC
    ");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des matchs : " . $e->getMessage();
    $matches = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matchs - Kora Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .match-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .match-card:hover {
            transform: translateY(-5px);
        }
        .match-date {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .match-stadium {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .match-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #28a745;
        }
        .match-teams {
            font-size: 1.2rem;
            font-weight: bold;
        }
        .match-vs {
            color: #6c757d;
            font-weight: normal;
        }
        .ticket-progress {
            height: 5px;
        }
        .ticket-info {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <h1 class="mb-4">Matchs à venir</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($matches)): ?>
            <div class="alert alert-info">
                Aucun match n'est actuellement disponible.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($matches as $match): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card match-card">
                            <div class="card-body">
                                <div class="match-teams mb-2">
                                    <?php echo htmlspecialchars($match['home_team']); ?>
                                    <span class="match-vs">vs</span>
                                    <?php echo htmlspecialchars($match['away_team']); ?>
                                </div>
                                
                                <div class="match-date mb-2">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?>
                                </div>
                                
                                <div class="match-stadium mb-3">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($match['stadium_name'] . ', ' . $match['city']); ?>
                                </div>

                                <?php if ($match['description']): ?>
                                    <p class="card-text mb-3"><?php echo htmlspecialchars($match['description']); ?></p>
                                <?php endif; ?>

                                <div class="ticket-info mb-2">
                                    <?php
                                    $sold = $match['tickets_sold'] ?? 0;
                                    $total = $match['total_tickets'];
                                    $rate = $total > 0 ? ($sold / $total) * 100 : 0;
                                    ?>
                                    <div class="progress ticket-progress mb-1">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $rate; ?>%"
                                             aria-valuenow="<?php echo $rate; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <?php echo number_format($sold); ?> / <?php echo number_format($total); ?> billets vendus
                                </div>

                                <div class="match-price mb-3">
                                    <?php if ($match['min_price'] == $match['max_price']): ?>
                                        <?php echo number_format($match['min_price'], 2); ?> €
                                    <?php else: ?>
                                        <?php echo number_format($match['min_price'], 2); ?> € - <?php echo number_format($match['max_price'], 2); ?> €
                                    <?php endif; ?>
                                </div>

                                <a href="tickets.php?match_id=<?php echo $match['id']; ?>" class="btn btn-primary w-100">
                                    <i class="fas fa-ticket-alt"></i> Voir les billets
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 