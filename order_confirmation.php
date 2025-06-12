<?php
require_once __DIR__ . '/includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('/login.php');
}

// Vérifier si l'ID de la commande est fourni
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    redirect('/index.php');
}

$order_id = (int)$_GET['order_id'];

// Récupérer les détails de la commande
$stmt = $pdo->prepare("
    SELECT o.*, od.quantity, od.price_per_ticket,
           tc.name as category_name,
           m.home_team, m.away_team, m.match_date,
           s.name as stadium_name
    FROM orders o
    JOIN order_details od ON o.id = od.order_id
    JOIN ticket_categories tc ON od.ticket_category_id = tc.id
    JOIN matches m ON tc.match_id = m.id
    JOIN stadiums s ON m.stadium_id = s.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si la commande existe
if (empty($order_details)) {
    redirect('/index.php');
}

// Récupérer les informations générales de la commande
$order = [
    'id' => $order_details[0]['id'],
    'total_amount' => $order_details[0]['total_amount'],
    'created_at' => $order_details[0]['created_at'],
    'status' => $order_details[0]['status']
];
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Commande confirmée !</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Votre commande a été confirmée avec succès. Un email contenant vos billets a été envoyé à votre adresse email.
                    </div>

                    <h4 class="mb-3">Détails de la commande</h4>
                    <p>
                        <strong>Numéro de commande :</strong> #<?php echo $order['id']; ?><br>
                        <strong>Date :</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?><br>
                        <strong>Statut :</strong> 
                        <span class="badge bg-success"><?php echo ucfirst($order['status']); ?></span>
                    </p>

                    <h4 class="mb-3 mt-4">Billets commandés</h4>
                    <?php foreach ($order_details as $detail): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($detail['home_team'] . ' vs ' . $detail['away_team']); ?>
                                </h5>
                                <p class="card-text">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($detail['match_date'])); ?>
                                    <br>
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?php echo htmlspecialchars($detail['stadium_name']); ?>
                                </p>
                                <p class="card-text">
                                    <strong>Catégorie :</strong> 
                                    <?php echo htmlspecialchars($detail['category_name']); ?>
                                    <br>
                                    <strong>Quantité :</strong> 
                                    <?php echo $detail['quantity']; ?> billet(s)
                                    <br>
                                    <strong>Prix unitaire :</strong> 
                                    <?php echo number_format($detail['price_per_ticket'], 2); ?> MAD
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-end mt-4">
                        <h4>
                            Total : <?php echo number_format($order['total_amount'], 2); ?> MAD
                        </h4>
                    </div>

                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Retour à l'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 