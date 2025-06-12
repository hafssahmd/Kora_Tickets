<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

// Vérifier que l'ID du match est fourni
if (!isset($_GET['match_id']) || !is_numeric($_GET['match_id'])) {
    redirect('/matches.php');
}
$match_id = (int)$_GET['match_id'];

// Récupérer les infos du match
$stmt = $conn->prepare("
    SELECT m.*, s.name as stadium_name, s.location as stadium_location
    FROM matches m
    JOIN stadiums s ON m.stadium_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$match_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    redirect('/matches.php');
}

// Récupérer les catégories de tickets disponibles
$stmt = $conn->prepare("
    SELECT *
    FROM ticket_categories
    WHERE match_id = ? AND available_quantity > 0
    ORDER BY price ASC
");
$stmt->execute([$match_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h1>
        <?php echo htmlspecialchars($match['home_team']); ?> vs <?php echo htmlspecialchars($match['away_team']); ?>
    </h1>
    <p>
        <strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?><br>
        <strong>Stade :</strong> <?php echo htmlspecialchars($match['stadium_name']); ?> (<?php echo htmlspecialchars($match['stadium_location']); ?>)
    </p>
    <hr>
    <h3>Catégories de billets disponibles</h3>
    <?php if (empty($categories)): ?>
        <div class="alert alert-warning">Aucune catégorie de billet disponible pour ce match.</div>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Quantité disponible</th>
                    <th>Quantité à réserver</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cat['name']); ?></td>
                        <td><?php echo number_format($cat['price'], 2); ?> MAD</td>
                        <td><?php echo $cat['available_quantity']; ?></td>
                        <td>
                            <input type="number" id="qty_<?php echo $cat['id']; ?>" min="1" max="<?php echo $cat['available_quantity']; ?>" value="1" class="form-control" style="width:80px;">
                        </td>
                        <td>
                            <button class="btn btn-primary btn-sm" onclick="addToCart(<?php echo $cat['id']; ?>)">
                                <i class="fas fa-cart-plus"></i> Ajouter au panier
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function addToCart(categoryId) {
    var qty = document.getElementById('qty_' + categoryId).value;
    console.log('addToCart called with:', categoryId, qty);
    if (!categoryId || !qty || qty < 1) {
        alert('Veuillez choisir une quantité valide.');
        return;
    }
    if (typeof addToCartUniversal === 'function') {
        addToCartUniversal(categoryId, qty);
    } else {
        alert('Fonction panier non disponible.');
    }
}
</script>


<script src="assets/js/main.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?> 