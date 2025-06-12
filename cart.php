<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('/login.php');
}

// Récupérer le panier de l'utilisateur
$stmt = $conn->prepare("
    SELECT c.id as cart_id, ci.id as cart_item_id, ci.quantity,
           tc.name as category_name, tc.price,
           m.home_team, m.away_team, m.match_date,
           s.name as stadium_name
    FROM carts c
    JOIN cart_items ci ON c.id = ci.cart_id
    JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
    JOIN matches m ON tc.match_id = m.id
    JOIN stadiums s ON m.stadium_id = s.id
    WHERE c.user_id = ?
    ORDER BY m.match_date ASC
");
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer le total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<div class="container">
    <h1 class="mb-4">Mon Panier</h1>
    
    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info">
            Votre panier est vide. 
            <a href="matches.php">Découvrez nos matchs</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <!-- Liste des articles -->
                <?php foreach ($cart_items as $item): ?>
                    <div class="card mb-3" data-cart-item-id="<?php echo $item['cart_item_id']; ?>">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($item['home_team'] . ' vs ' . $item['away_team']); ?>
                                    </h5>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo date('d/m/Y H:i', strtotime($item['match_date'])); ?>
                                            <br>
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo htmlspecialchars($item['stadium_name']); ?>
                                        </small>
                                    </p>
                                    <p class="card-text">
                                        <strong>Catégorie :</strong> 
                                        <?php echo htmlspecialchars($item['category_name']); ?>
                                        <br>
                                        <strong>Prix unitaire :</strong> 
                                        <?php echo number_format($item['price'], 2); ?> MAD
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="input-group mb-2" style="width: 120px; margin-left: auto;">
                                        <input type="number" 
                                               class="form-control" 
                                               value="<?php echo $item['quantity']; ?>"
                                               min="1"
                                               name="quantity_<?php echo $item['cart_item_id']; ?>"
                                               onchange="updateCartItemUniversal(<?php echo $item['cart_item_id']; ?>, this.value)">
                                    </div>
                                    <button class="btn btn-danger btn-sm" 
                                            onclick="removeCartItemUniversal(<?php echo $item['cart_item_id']; ?>)">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="col-md-4">
                <!-- Résumé de la commande -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Résumé de la commande</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sous-total</span>
                            <span><?php echo number_format($total, 2); ?> MAD</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong><?php echo number_format($total, 2); ?> MAD</strong>
                        </div>
                        
                        <!-- Bouton PayPal -->
                        <div id="paypal-button-container"></div>
                        
                        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=MAD"></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                initPayPal();
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mt-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="fas fa-credit-card"></i> Payer
            </button>
        </div>

        <!-- Modal de paiement -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Paiement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <ul class="nav nav-tabs mb-3" id="paymentTab" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="card-tab" data-bs-toggle="tab" data-bs-target="#card" type="button" role="tab">Carte bancaire</button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link" id="paypal-tab" data-bs-toggle="tab" data-bs-target="#paypal" type="button" role="tab">PayPal</button>
                  </li>
                </ul>
                <div class="tab-content" id="paymentTabContent">
                  <div class="tab-pane fade show active" id="card" role="tabpanel">
                    <form id="cardPaymentForm">
                      <div class="mb-3">
                        <label for="cardName" class="form-label">Nom sur la carte</label>
                        <input type="text" class="form-control" id="cardName" required>
                      </div>
                      <div class="mb-3">
                        <label for="cardNumber" class="form-label">Numéro de carte</label>
                        <input type="text" class="form-control" id="cardNumber" maxlength="19" required>
                      </div>
                      <div class="row">
                        <div class="col">
                          <label for="cardExpiry" class="form-label">Expiration</label>
                          <input type="text" class="form-control" id="cardExpiry" placeholder="MM/AA" maxlength="5" required>
                        </div>
                        <div class="col">
                          <label for="cardCVC" class="form-label">CVC</label>
                          <input type="text" class="form-control" id="cardCVC" maxlength="4" required>
                        </div>
                      </div>
                      <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary">Payer par carte</button>
                      </div>
                    </form>
                  </div>
                  <div class="tab-pane fade" id="paypal" role="tabpanel">
                    <div id="paypal-button-container-modal"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Ajout du SDK PayPal pour le modal -->
        <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=MAD"></script>
        <script>
        // Initialisation du bouton PayPal dans le modal
        function renderPayPalButtonModal() {
            if (document.getElementById('paypal-button-container-modal')) {
                paypal.Buttons({
                    createOrder: function(data, actions) {
                        return fetch('ajax/create_paypal_order.php', {
                            method: 'POST'
                        })
                        .then(response => response.json())
                        .then(order => order.id);
                    },
                    onApprove: function(data, actions) {
                        return fetch('ajax/capture_paypal_payment.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ orderID: data.orderID })
                        })
                        .then(response => response.json())
                        .then(details => {
                            if (details.success) {
                                // Afficher un message de succès et rediriger
                                alert('Paiement réussi ! Votre ticket va vous être envoyé par email.');
                                window.location.href = 'order_confirmation.php?order_id=' + details.order_id;
                            } else {
                                alert('Erreur lors du paiement PayPal.');
                            }
                        });
                    }
                }).render('#paypal-button-container-modal');
            }
        }
        // Afficher le bouton PayPal à l'ouverture du modal
        var paymentModal = document.getElementById('paymentModal');
        paymentModal && paymentModal.addEventListener('shown.bs.modal', function () {
            renderPayPalButtonModal();
        });
        </script>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?> 