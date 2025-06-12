<?php
require_once __DIR__ . '/includes/header.php';

// Vérifier si l'ID du match est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('/matches.php');
}

$match_id = (int)$_GET['id'];

// Récupérer les informations du match
$stmt = $conn->prepare("
    SELECT m.*, s.name as stadium_name, s.location as stadium_location
    FROM matches m
    JOIN stadiums s ON m.stadium_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$match_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

// Vérifier si le match existe
if (!$match) {
    redirect('/matches.php');
}

// Récupérer les catégories de billets disponibles
$stmt = $conn->prepare("
    SELECT *
    FROM ticket_categories
    WHERE match_id = ? AND available_quantity > 0
    ORDER BY price ASC
");
$stmt->execute([$match_id]);
$ticket_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <!-- En-tête du match -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3">
                <?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?>
            </h1>
            <div class="mb-3">
                <p class="mb-2">
                    <i class="fas fa-calendar"></i> 
                    <?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?>
                </p>
                <p class="mb-2">
                    <i class="fas fa-map-marker-alt"></i> 
                    <?php echo htmlspecialchars($match['stadium_name']); ?> - 
                    <?php echo htmlspecialchars($match['stadium_location']); ?>
                </p>
            </div>
            <?php if ($match['description']): ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Description</h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($match['description'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <img src="<?php echo htmlspecialchars($match['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?>"
                 class="img-fluid rounded">
        </div>
    </div>

    <!-- Catégories de billets -->
    <h2 class="mb-4">Billets disponibles</h2>
    
    <?php if (empty($ticket_categories)): ?>
        <div class="alert alert-info">
            Désolé, il n'y a plus de billets disponibles pour ce match.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($ticket_categories as $category): ?>
                <div class="col-md-6 mb-4">
                    <div class="card ticket-category">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                            <p class="card-text">
                                <strong>Prix :</strong> <?php echo number_format($category['price'], 2); ?> MAD
                            </p>
                            <p class="card-text">
                                <strong>Disponibles :</strong> <?php echo $category['available_quantity']; ?> billets
                            </p>
                            
                            <?php if (isLoggedIn()): ?>
                                <form class="d-flex align-items-center" onsubmit="return addToCart(<?php echo $category['id']; ?>, this.quantity.value)">
                                    <div class="input-group me-2" style="width: 120px;">
                                        <input type="number" 
                                               class="form-control" 
                                               name="quantity" 
                                               min="1" 
                                               max="<?php echo $category['available_quantity']; ?>" 
                                               value="1">
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-cart-plus"></i> Ajouter
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    <a href="login.php">Connectez-vous</a> pour acheter des billets
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 