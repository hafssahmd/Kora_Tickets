<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
    
// Vérifier si l'admin est connecté
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer la liste des stades pour le formulaire
try {
    $stmt = $conn->query("SELECT id, name FROM stadiums ORDER BY name");
    $stadiums = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des stades : " . $e->getMessage();
    $stadiums = [];
}

// Récupérer la liste des matchs
try {
    $stmt = $conn->query("
        SELECT m.*, s.name as stadium_name,
               (SELECT COUNT(*) FROM ticket_categories tc WHERE tc.match_id = m.id) as categories_count,
               (SELECT SUM(tc.quantity) FROM ticket_categories tc WHERE tc.match_id = m.id) as total_tickets,
               (SELECT SUM(oi.quantity) FROM order_details oi 
                JOIN ticket_categories tc ON oi.ticket_category_id = tc.id 
                WHERE tc.match_id = m.id) as tickets_sold
        FROM matches m
        JOIN stadiums s ON m.stadium_id = s.id
        ORDER BY m.match_date DESC
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
    <title>Gestion des matchs - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        .match-card {
            transition: transform 0.2s;
        }
        .match-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Gestion des matchs</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMatchModal">
                <i class="fas fa-plus"></i> Ajouter un match
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

        <div class="row">
            <?php foreach ($matches as $match): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card match-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?>
                            </h5>
                            <p class="card-text">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($match['stadium_name']); ?><br>
                                <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?><br>
                                <i class="fas fa-ticket-alt"></i> <?php echo $match['categories_count']; ?> catégories<br>
                                <i class="fas fa-chart-pie"></i> <?php echo number_format($match['tickets_sold'] ?? 0); ?> / <?php echo number_format($match['total_tickets']); ?> billets vendus
                            </p>
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-primary btn-sm" onclick="editMatch(<?php echo $match['id']; ?>)">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <button type="button" class="btn btn-info btn-sm" onclick="manageCategories(<?php echo $match['id']; ?>)">
                                    <i class="fas fa-tags"></i> Catégories
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteMatch(<?php echo $match['id']; ?>)">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal Ajout Match -->
    <div class="modal fade" id="addMatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter un match</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="ajax/add_match.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="home_team" class="form-label">Équipe à domicile</label>
                            <input type="text" class="form-control" id="home_team" name="home_team" required>
                        </div>
                        <div class="mb-3">
                            <label for="away_team" class="form-label">Équipe à l'extérieur</label>
                            <input type="text" class="form-control" id="away_team" name="away_team" required>
                        </div>
                        <div class="mb-3">
                            <label for="stadium_id" class="form-label">Stade</label>
                            <select class="form-select" id="stadium_id" name="stadium_id" required>
                                <option value="">Sélectionner un stade</option>
                                <?php foreach ($stadiums as $stadium): ?>
                                    <option value="<?php echo $stadium['id']; ?>">
                                        <?php echo htmlspecialchars($stadium['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="match_date" class="form-label">Date et heure</label>
                            <input type="text" class="form-control" id="match_date" name="match_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
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

    <!-- Modal Modification Match -->
    <div class="modal fade" id="editMatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le match</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="ajax/update_match.php" method="POST">
                    <input type="hidden" id="edit_match_id" name="match_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_home_team" class="form-label">Équipe à domicile</label>
                            <input type="text" class="form-control" id="edit_home_team" name="home_team" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_away_team" class="form-label">Équipe à l'extérieur</label>
                            <input type="text" class="form-control" id="edit_away_team" name="away_team" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stadium_id" class="form-label">Stade</label>
                            <select class="form-select" id="edit_stadium_id" name="stadium_id" required>
                                <option value="">Sélectionner un stade</option>
                                <?php foreach ($stadiums as $stadium): ?>
                                    <option value="<?php echo $stadium['id']; ?>">
                                        <?php echo htmlspecialchars($stadium['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_match_date" class="form-label">Date et heure</label>
                            <input type="text" class="form-control" id="edit_match_date" name="match_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
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
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <script>
        // Initialisation de Flatpickr pour les champs de date
        flatpickr("#match_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            locale: "fr",
            minDate: "today"
        });

        flatpickr("#edit_match_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            locale: "fr"
        });

        // Fonction pour éditer un match
        function editMatch(matchId) {
            fetch(`<?php echo SITE_URL; ?>/admin/ajax/get_match.php?id=${matchId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_match_id').value = data.id;
                    document.getElementById('edit_home_team').value = data.home_team;
                    document.getElementById('edit_away_team').value = data.away_team;
                    document.getElementById('edit_stadium_id').value = data.stadium_id;
                    document.getElementById('edit_match_date').value = data.match_date;
                    document.getElementById('edit_description').value = data.description;
                    
                    new bootstrap.Modal(document.getElementById('editMatchModal')).show();
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la récupération des données du match.');
                });
        }

        // Fonction pour gérer les catégories
        function manageCategories(matchId) {
            window.location.href = `<?php echo SITE_URL; ?>/admin/ticket_categories.php?match_id=${matchId}`;
        }

        // Fonction pour supprimer un match
        function deleteMatch(matchId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce match ?')) {
                fetch('<?php echo SITE_URL; ?>/admin/ajax/delete_match.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `match_id=${matchId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.error || 'Une erreur est survenue lors de la suppression du match.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression du match.');
                });
            }
        }
    </script>
</body>
</html> 