<?php
require_once __DIR__ . '/includes/header.php';

// Récupérer les matchs à venir
$stmt = $conn->prepare("
    SELECT m.*, s.name as stadium_name, s.location as stadium_location
    FROM matches m
    JOIN stadiums s ON m.stadium_id = s.id
    WHERE m.match_date > NOW()
    ORDER BY m.match_date ASC
    LIMIT 6
");
$stmt->execute();
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Hero Section -->
<div class="bg-primary text-white py-5 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4">Bienvenue sur <?php echo SITE_NAME; ?></h1>
                <p class="lead">Réservez vos billets pour les meilleurs matchs de football au Maroc</p>
                <a href="matches.php" class="btn btn-light btn-lg">Voir les matchs</a>
            </div>
            <!-- <div class="col-md-6">
                <img src="assets/images/hero-image.jpg" alt="Hero" />
            </div> -->
        </div>
    </div>
</div>

<!-- Featured Matches Section -->
<div class="container">
    <h2 class="mb-4">Prochains Matchs</h2>
    <div class="row" id="matches-container">
        <?php foreach ($matches as $match): ?>
            <div class="col-md-4 mb-4">
                <div class="card match-card h-100">
                    <img src="<?php echo htmlspecialchars($match['image_url']); ?>" 
                         class="card-img-top" 
                         alt="<?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?>
                        </h5>
                        <p class="card-text">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?>
                        </p>
                        <p class="card-text">
                            <i class="fas fa-map-marker-alt"></i> 
                            <?php echo htmlspecialchars($match['stadium_name']); ?>
                        </p>
                        <a href="match.php?id=<?php echo $match['id']; ?>" class="btn btn-primary">
                            Voir les billets
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (count($matches) >= 6): ?>
        <div class="text-center mt-4">
            <a href="matches.php" class="btn btn-outline-primary">Voir tous les matchs</a>
        </div>
    <?php endif; ?>
</div>

<!-- Features Section -->
<div class="container mt-5">
    <div class="row">
        <div class="col-md-4 text-center mb-4">
            <i class="fas fa-ticket-alt fa-3x text-primary mb-3"></i>
            <h3>Billets Garantis</h3>
            <p>Des billets authentiques pour tous les matchs</p>
        </div>
        <div class="col-md-4 text-center mb-4">
            <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
            <h3>Paiement Sécurisé</h3>
            <p>Transactions sécurisées via PayPal</p>
        </div>
        <div class="col-md-4 text-center mb-4">
            <i class="fas fa-headset fa-3x text-primary mb-3"></i>
            <h3>Support 24/7</h3>
            <p>Une équipe à votre écoute</p>
        </div>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>