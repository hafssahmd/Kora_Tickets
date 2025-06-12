<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer l'historique des commandes
$stmt = $conn->prepare("
    SELECT o.*, COUNT(od.id) as total_items, SUM(od.quantity) as total_tickets
    FROM orders o
    JOIN order_details od ON o.id = od.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Kora Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Informations du profil -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Mon Profil</h5>
                    </div>
                    <div class="card-body">
                        <form action="ajax/update_profile.php" method="POST" id="profileForm">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">Prénom</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Nom</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Changer le mot de passe</h5>
                    </div>
                    <div class="card-body">
                        <form action="ajax/update_password.php" method="POST" id="passwordForm">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Historique des commandes -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Mes Commandes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <p class="text-center">Vous n'avez pas encore de commandes.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>N° Commande</th>
                                            <th>Date</th>
                                            <th>Statut</th>
                                            <th>Billets</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
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
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                        <i class="fas fa-eye"></i> Détails
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
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
        // Gestion du formulaire de profil
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('ajax/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profil mis à jour avec succès');
                } else {
                    alert(data.error || 'Une erreur est survenue');
                }
            });
        });

        // Gestion du formulaire de mot de passe
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            if (formData.get('new_password') !== formData.get('confirm_password')) {
                alert('Les mots de passe ne correspondent pas');
                return;
            }

            fetch('ajax/update_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Mot de passe mis à jour avec succès');
                    this.reset();
                } else {
                    alert(data.error || 'Une erreur est survenue');
                }
            });
        });

        // Fonction pour voir les détails d'une commande
        function viewOrder(orderId) {
            fetch(`ajax/get_order_details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetails').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('orderModal')).show();
                });
        }
    </script>
</body>
</html> 