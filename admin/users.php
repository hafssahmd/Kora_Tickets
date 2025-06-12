<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Récupérer la liste des utilisateurs avec leurs statistiques
$stmt = $conn->query("
    SELECT u.*,
           COUNT(DISTINCT o.id) as total_orders,
           SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
           SUM(CASE WHEN o.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
           SUM(od.quantity) as total_tickets
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN order_details od ON o.id = od.order_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des Utilisateurs</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus"></i> Ajouter un utilisateur
            </button>
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
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Date d'inscription</th>
                                <th>Commandes</th>
                                <th>Billets achetés</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $user['completed_orders']; ?> complétées</span>
                                        <span class="badge bg-danger"><?php echo $user['cancelled_orders']; ?> annulées</span>
                                        <span class="badge bg-warning"><?php echo $user['total_orders'] - $user['completed_orders'] - $user['cancelled_orders']; ?> en attente</span>
                                    </td>
                                    <td><?php echo $user['total_tickets'] ?? 0; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" onclick="viewUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] !== $_SESSION['admin_id']): ?>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
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

    <!-- Modal pour ajouter un utilisateur -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="ajax/add_user.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="is_admin" class="form-label">Rôle</label>
                            <select class="form-select" id="is_admin" name="is_admin">
                                <option value="0">Utilisateur</option>
                                <option value="1">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal pour modifier un utilisateur -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="ajax/update_user.php" method="POST">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_first_name" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_is_admin" class="form-label">Rôle</label>
                            <select class="form-select" id="edit_is_admin" name="is_admin">
                                <option value="0">Utilisateur</option>
                                <option value="1">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewUser(userId) {
            fetch(`ajax/get_user_details.php?user_id=${userId}`)
                .then(response => response.text())
                .then(html => {
                    const modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
                    document.getElementById('userDetails').innerHTML = html;
                    modal.show();
                });
        }

        function editUser(userId) {
            fetch(`ajax/get_user.php?user_id=${userId}`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('edit_user_id').value = user.id;
                    document.getElementById('edit_first_name').value = user.first_name;
                    document.getElementById('edit_last_name').value = user.last_name;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_is_admin').value = user.is_admin;
                    new bootstrap.Modal(document.getElementById('editUserModal')).show();
                });
        }

        function deleteUser(userId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                const formData = new FormData();
                formData.append('user_id', userId);

                fetch('ajax/delete_user.php', {
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