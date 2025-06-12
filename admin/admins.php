<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/header.php';

// Sécurité : seuls les admins peuvent accéder
if (!isset($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Traitement ajout admin
$add_error = '';
$add_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$email || !$password) {
        $add_error = "Tous les champs sont obligatoires.";
    } else {
        // Vérifier unicité
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $add_error = "Nom d'utilisateur ou email déjà utilisé.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$username, $email, $hash]);
            $add_success = "Administrateur ajouté avec succès.";
        }
    }
}

// Traitement suppression admin
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $admin_id = (int)$_GET['delete'];
    // On ne peut pas supprimer son propre compte
    if ($admin_id == $_SESSION['admin_id']) {
        $add_error = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$admin_id]);
        $add_success = "Administrateur supprimé.";
    }
}

// Liste des admins
$stmt = $conn->query("SELECT id, username, email, created_at FROM users WHERE role = 'admin' ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container">
    <h1>Gestion des administrateurs</h1>
    <?php if ($add_error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($add_error); ?></div>
    <?php endif; ?>
    <?php if ($add_success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($add_success); ?></div>
    <?php endif; ?>
    <div class="card mb-4">
        <div class="card-header">Ajouter un administrateur</div>
        <div class="card-body">
            <form method="post">
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="username" class="form-control" placeholder="Nom d'utilisateur" required>
                    </div>
                    <div class="col-md-4">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="col-md-3">
                        <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add_admin" class="btn btn-primary w-100">Ajouter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">Liste des administrateurs</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                                <td>
                                    <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                        <a href="?delete=<?php echo $admin['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer cet administrateur ?');">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">(Vous)</span>
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
<?php require_once __DIR__ . '/includes/footer.php'; ?> 