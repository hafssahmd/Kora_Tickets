<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Récupérer les commandes avec les détails des utilisateurs et des billets
$stmt = $conn->query("
    SELECT o.*, u.email as user_email,
           COUNT(od.id) as total_items,
           SUM(od.quantity) as total_tickets,
           SUM(od.price_per_ticket * od.quantity) as total_amount
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN order_details od ON o.id = od.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commandes - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Commandes</h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Billets</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['user_email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
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
                                    </td>
                                    <td>
                                        <?php echo $order['total_tickets']; ?> billets
                                        (<?php echo $order['total_items']; ?> catégories)
                                    </td>
                                    <td><?php echo number_format($order['total_amount'], 2); ?> €</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-success" onclick="updateStatus(<?php echo $order['id']; ?>, 'completed')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="updateStatus(<?php echo $order['id']; ?>, 'cancelled')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour voir les détails de la commande -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Détails de la commande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetails">
                    <!-- Le contenu sera chargé dynamiquement -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewOrder(orderId) {
            fetch(`ajax/get_order_details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetails').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('orderModal')).show();
                });
        }

        function updateStatus(orderId, status) {
            if (confirm('Êtes-vous sûr de vouloir ' + (status === 'completed' ? 'valider' : 'annuler') + ' cette commande ?')) {
                const formData = new FormData();
                formData.append('order_id', orderId);
                formData.append('status', status);

                fetch('ajax/update_order_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.error || 'Une erreur est survenue');
                    }
                });
            }
        }
    </script>
</body>
</html> 