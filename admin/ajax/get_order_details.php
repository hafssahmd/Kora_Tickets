<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    die('Non autorisé');
}

$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die('ID de commande invalide');
}

try {
    // Récupérer les informations de la commande
    $stmt = $conn->prepare("
        SELECT o.*, u.email as user_email, u.first_name, u.last_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        die('Commande non trouvée');
    }

    // Récupérer les articles de la commande
    $stmt = $conn->prepare("
        SELECT od.*, tc.name as category_name,
               m.home_team, m.away_team, m.match_date,
               s.name as stadium_name, s.location as stadium_city
        FROM order_details od
        JOIN ticket_categories tc ON od.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE od.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h6>Informations client</h6>
            <p>
                <strong>Nom :</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                <strong>Email :</strong> <?php echo htmlspecialchars($order['user_email']); ?>
            </p>
        </div>
        <div class="col-md-6">
            <h6>Informations commande</h6>
            <p>
                <strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?><br>
                <strong>Statut :</strong> 
                <span class="badge bg-<?php
                    echo match($order['status']) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary'
                    };
                ?>">
                    <?php
                    echo match($order['status']) {
                        'pending' => 'En attente',
                        'completed' => 'Complétée',
                        'cancelled' => 'Annulée',
                        default => 'Inconnu'
                    };
                    ?>
                </span>
            </p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Match</th>
                    <th>Stade</th>
                    <th>Catégorie</th>
                    <th>Quantité</th>
                    <th>Prix unitaire</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item['home_team'] . ' vs ' . $item['away_team']); ?><br>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($item['match_date'])); ?>
                            </small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($item['stadium_name']); ?><br>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($item['stadium_city']); ?>
                            </small>
                        </td>
                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price_per_ticket'], 2); ?> €</td>
                        <td><?php echo number_format($item['price_per_ticket'] * $item['quantity'], 2); ?> €</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-end"><strong>Total</strong></td>
                    <td>
                        <strong>
                            <?php
                            $total = array_reduce($items, function($carry, $item) {
                                return $carry + ($item['price_per_ticket'] * $item['quantity']);
                            }, 0);
                            echo number_format($total, 2);
                            ?> €
                        </strong>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php
} catch (PDOException $e) {
    die('Une erreur est survenue lors de la récupération des détails de la commande');
} 