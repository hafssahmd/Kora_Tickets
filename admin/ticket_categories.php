<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/header.php';

// Traitement de la suppression
if (isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    try {
        $conn->beginTransaction();
        
        // Vérifier si la catégorie est utilisée dans des commandes
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM order_details 
            WHERE ticket_category_id = ?
        ");
        $stmt->execute([$category_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Cette catégorie ne peut pas être supprimée car elle est utilisée dans des commandes.");
        }
        
        // Supprimer la catégorie
        $stmt = $conn->prepare("DELETE FROM ticket_categories WHERE id = ?");
        $stmt->execute([$category_id]);
        
        $conn->commit();
        $success = "La catégorie a été supprimée avec succès.";
    } catch (Exception $e) {
        $conn->rollBack();
        $error = $e->getMessage();
    }
}

// Récupérer la liste des matchs pour le formulaire
$stmt = $conn->query("
    SELECT m.*, s.name as stadium_name
    FROM matches m
    JOIN stadiums s ON m.stadium_id = s.id
    WHERE m.match_date > NOW()
    ORDER BY m.match_date ASC
");
$matches = $stmt->fetchAll();

// Récupérer la liste des catégories
$stmt = $conn->query("
    SELECT tc.*, m.home_team, m.away_team, m.match_date, s.name as stadium_name,
           (SELECT COUNT(*) FROM order_details WHERE ticket_category_id = tc.id) as orders_count
    FROM ticket_categories tc
    JOIN matches m ON tc.match_id = m.id
    JOIN stadiums s ON m.stadium_id = s.id
    ORDER BY m.match_date DESC, tc.price ASC
");
$categories = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Gestion des catégories de billets</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fas fa-plus"></i> Nouvelle catégorie
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
                        <th>Match</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Commandes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo $category['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($category['home_team'] . ' vs ' . $category['away_team']); ?>
                                <br>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($category['match_date'])); ?>
                                    - <?php echo htmlspecialchars($category['stadium_name']); ?>
                                </small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($category['name']); ?>
                                <?php if ($category['description']): ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($category['description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($category['price'], 2); ?> MAD</td>
                            <td>
                                <?php echo number_format($category['total_quantity']); ?> billets
                                <br>
                                <small class="text-muted">
                                    <?php echo number_format($category['total_quantity'] - $category['sold_quantity']); ?> disponibles
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo $category['orders_count']; ?> commande(s)
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>')">
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

<!-- Modal Ajout Catégorie -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="ajax/add_ticket_category.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="match_id" class="form-label">Match</label>
                        <select class="form-select" id="match_id" name="match_id" required>
                            <option value="">Sélectionner un match</option>
                            <?php foreach ($matches as $match): ?>
                                <option value="<?php echo $match['id']; ?>">
                                    <?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?>
                                    (<?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Prix (MAD)</label>
                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantité</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
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

<!-- Modal Modification Catégorie -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="ajax/update_ticket_category.php" method="POST">
                <input type="hidden" name="category_id" id="edit_category_id">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier la catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_match_id" class="form-label">Match</label>
                        <select class="form-select" id="edit_match_id" name="match_id" required>
                            <option value="">Sélectionner un match</option>
                            <?php foreach ($matches as $match): ?>
                                <option value="<?php echo $match['id']; ?>">
                                    <?php echo htmlspecialchars($match['home_team'] . ' vs ' . $match['away_team']); ?>
                                    (<?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Prix (MAD)</label>
                        <input type="number" class="form-control" id="edit_price" name="price" min="0" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_quantity" class="form-label">Quantité</label>
                        <input type="number" class="form-control" id="edit_quantity" name="quantity" min="1" required>
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
<form id="deleteCategoryForm" method="POST" style="display: none;">
    <input type="hidden" name="category_id" id="delete_category_id">
    <input type="hidden" name="delete_category" value="1">
</form>

<script>
function editCategory(category) {
    document.getElementById('edit_category_id').value = category.id;
    document.getElementById('edit_match_id').value = category.match_id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_price').value = category.price;
    document.getElementById('edit_quantity').value = category.total_quantity;
    document.getElementById('edit_description').value = category.description || '';
    
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function deleteCategory(categoryId, categoryName) {
    if (confirmDelete('Voulez-vous vraiment supprimer la catégorie "' + categoryName + '" ?')) {
        document.getElementById('delete_category_id').value = categoryId;
        document.getElementById('deleteCategoryForm').submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 