// Fonction pour mettre à jour le nombre d'articles dans le panier
function updateCartCount() {
  $.ajax({
    url: "ajax/get_cart_count.php",
    method: "GET",
    success: function (response) {
      $(".cart-count").text(response.count);
    },
  });
}

// Fonction universelle pour ajouter au panier (compatible JS natif et jQuery)
function addToCartUniversal(ticketCategoryId, quantity) {
  quantity = quantity || 1;
  $.ajax({
    url: "ajax/add_to_cart.php",
    method: "POST",
    data: {
      ticket_category_id: ticketCategoryId,
      quantity: quantity,
    },
    success: function (response) {
      if (response.success) {
        updateCartCount();
        if (typeof loadCartDropdown === "function") loadCartDropdown();
        showAlert("success", "Billets ajoutés au panier !");
        // Feedback visuel sur le bouton d'ajout si possible
        var btn = document.querySelector(
          '[onclick*="addToCart("' + ticketCategoryId + '""]'
        );
        if (btn) {
          btn.classList.add("btn-success");
          setTimeout(function () {
            btn.classList.remove("btn-success");
          }, 800);
        }
      } else {
        showAlert(
          "danger",
          response.message || "Erreur lors de l'ajout au panier"
        );
      }
    },
    error: function () {
      showAlert("danger", "Erreur de communication avec le serveur");
    },
  });
}

// Fonction universelle pour mettre à jour la quantité
function updateCartItemUniversal(cartItemId, quantity) {
  $.ajax({
    url: "ajax/update_cart.php",
    method: "POST",
    data: {
      cart_item_id: cartItemId,
      quantity: quantity,
    },
    success: function (response) {
      if (response.success) {
        updateCartCount();
        if (typeof loadCartDropdown === "function") loadCartDropdown();
        showAlert("success", "Quantité mise à jour !");
        // Mettre à jour le total et la ligne sans recharger (optionnel)
        // location.reload(); // supprimé pour du full dynamique
      } else {
        showAlert(
          "danger",
          response.message || "Erreur lors de la mise à jour du panier"
        );
      }
    },
  });
}

// Fonction universelle pour supprimer un article
function removeCartItemUniversal(cartItemId) {
  console.log("removeCartItemUniversal called with:", cartItemId);
  if (!cartItemId) {
    showAlert("danger", "ID de l'article manquant (JS)");
    return;
  }
  if (confirm("Êtes-vous sûr de vouloir supprimer cet article ?")) {
    $.ajax({
      url: "ajax/remove_from_cart.php",
      method: "POST",
      data: {
        cart_item_id: cartItemId,
      },
      success: function (response) {
        if (response.success) {
          updateCartCount();
          if (typeof loadCartDropdown === "function") loadCartDropdown();
          showAlert("success", "Article supprimé du panier !");
          var row = document.querySelector(
            '[data-cart-item-id="' + cartItemId + '"]'
          );
          if (row) row.remove();
          if (document.querySelectorAll("[data-cart-item-id]").length === 0) {
            location.reload();
          }
        } else {
          showAlert(
            "danger",
            response.message || "Erreur lors de la suppression"
          );
        }
      },
    });
  }
}

// Fonction pour afficher une alerte
function showAlert(type, message) {
  const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
  $(".container").prepend(alertHtml);
}

// Fonction pour initialiser PayPal
function initPayPal() {
  if (typeof paypal === "undefined" || !paypal.Buttons) return;
  paypal
    .Buttons({
      createOrder: function (data, actions) {
        return fetch("ajax/create_paypal_order.php", {
          method: "POST",
        })
          .then((response) => response.json())
          .then((order) => order.id);
      },
      onApprove: function (data, actions) {
        return fetch("ajax/capture_paypal_payment.php", {
          method: "POST",
          body: JSON.stringify({
            orderID: data.orderID,
          }),
        })
          .then((response) => response.json())
          .then((details) => {
            if (details.success) {
              window.location.href =
                "order_confirmation.php?order_id=" + details.order_id;
            } else {
              showAlert("danger", "Erreur lors du paiement");
            }
          });
      },
    })
    .render("#paypal-button-container");
}

// Initialisation des tooltips Bootstrap
$(function () {
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Mise à jour initiale du nombre d'articles dans le panier
  updateCartCount();
});

// Validation des formulaires
function validateForm(formId) {
  const form = document.getElementById(formId);
  if (!form) return true;

  let isValid = true;
  const requiredFields = form.querySelectorAll("[required]");

  requiredFields.forEach((field) => {
    if (!field.value.trim()) {
      isValid = false;
      field.classList.add("is-invalid");
    } else {
      field.classList.remove("is-invalid");
    }
  });

  return isValid;
}

// Gestion des images de profil
function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      $("#profile-preview").attr("src", e.target.result);
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Fonction pour charger plus de matchs (pagination infinie)
// Désactivée par défaut, décommentez si besoin
var page = 1;
 function loadMoreMatches() {
   $.ajax({
     url: "ajax/load_matches.php",
     method: "GET",
     data: {
       page: page,
     },
     success: function (response) {
       if (response.matches.length > 0) {
         $("#matches-container").append(response.matches);
         page++;
       }
     },
   });
   }

 $(window).scroll(function () {
   if (
     $(window).scrollTop() + $(window).height() >=
     $(document).height() - 100
   ) {
     loadMoreMatches();
   }
 });

// Correction du chargement du dropdown sur toutes les pages
function loadCartDropdown() {
  $.ajax({
    url: "ajax/get_cart_dropdown.php",
    method: "GET",
    dataType: "html",
    success: function (html) {
      $("#cart-dropdown-content").html(html);
    },
    error: function () {
      $("#cart-dropdown-content").html(
        '<li class="text-danger p-2">Erreur de chargement du panier.</li>'
      );
    },
  });
}

// Ouvre le dropdown et charge le contenu à chaque ouverture
$(document).on("show.bs.dropdown", "#cartDropdown", function () {
  loadCartDropdown();
});

// Rendre les fonctions globales accessibles pour les pages natives
window.addToCartUniversal = addToCartUniversal;
window.updateCartItemUniversal = updateCartItemUniversal;
window.removeCartItemUniversal = removeCartItemUniversal;
