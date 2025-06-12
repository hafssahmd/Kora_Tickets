<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/header.php';

// Traitement de la suppression
if (isset($_POST['delete_stadium'])) {
    $stadium_id = (int)$_POST['stadium_id'];
    try {
        $pdo->beginTransaction();
        
        // Vérifier si le stade est utilisé dans des matchs
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM matches WHERE stadium_id = ?");
        $stmt->execute([$stadium_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Ce stade ne peut pas être supprimé car il est utilisé dans des matchs.");
        }
        
        // Supprimer le stade
        $stmt = $pdo->prepare("DELETE FROM stadiums WHERE id = ?");
        $stmt->execute([$stadium_id]);
        
        $pdo->commit();
        $success = "Le stade a été supprimé avec succès.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Récupérer la liste des stades
$stmt = $pdo->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM matches WHERE stadium_id = s.id) as matches_count
    FROM stadiums s
    ORDER BY s.name
");
$stadiums = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Gestion des stades</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStadiumModal">
        <i class="fas fa-plus"></i> Nouveau stade
    </button>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Ville</th>
                        <th>Capacité</th>
                        <th>Matchs</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stadiums as $stadium): ?>
                        <tr>
                            <td><?php echo $stadium['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($stadium['name']); ?>
                                <?php if ($stadium['description']): ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($stadium['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($stadium['city']); ?></td>
                            <td><?php echo number_format($stadium['capacity']); ?> places</td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo $stadium['matches_count']; ?> match(s)
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="editStadium(<?php echo htmlspecialchars(json_encode($stadium)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="deleteStadium(<?php echo $stadium['id']; ?>, '<?php echo htmlspecialchars($stadium['name']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ajout Stade -->
<div class="modal fade" id="addStadiumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="ajax/add_stadium.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau stade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label">Ville</label>
                        <input type="text" class="form-control" id="city" name="city" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacité</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
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

<!-- Modal Modification Stade -->
<div class="modal fade" id="editStadiumModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="ajax/update_stadium.php" method="POST">
                <input type="hidden" name="stadium_id" id="edit_stadium_id">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le stade</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_city" class="form-label">Ville</label>
                        <input type="text" class="form-control" id="edit_city" name="city" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_capacity" class="form-label">Capacité</label>
                        <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" required>
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

<!-- Formulaire de suppression -->
<form id="deleteStadiumForm" method="POST" style="display: none;">
    <input type="hidden" name="stadium_id" id="delete_stadium_id">
    <input type="hidden" name="delete_stadium" value="1">
</form>

<script>
function editStadium(stadium) {
    document.getElementById('edit_stadium_id').value = stadium.id;
    document.getElementById('edit_name').value = stadium.name;
    document.getElementById('edit_city').value = stadium.city;
    document.getElementById('edit_capacity').value = stadium.capacity;
    document.getElementById('edit_description').value = stadium.description || '';
    
    new bootstrap.Modal(document.getElementById('editStadiumModal')).show();
}

function deleteStadium(stadiumId, stadiumName) {
    if (confirmDelete('Voulez-vous vraiment supprimer le stade "' + stadiumName + '" ?')) {
        document.getElementById('delete_stadium_id').value = stadiumId;
        document.getElementById('deleteStadiumForm').submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 