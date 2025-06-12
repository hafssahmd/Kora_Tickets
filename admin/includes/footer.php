                </div><!-- /.container-fluid -->
            </div><!-- /.main-content -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fonction pour afficher les messages de confirmation
        function confirmDelete(message) {
            return Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            });
        }

        // Fonction pour afficher les messages de succès
        function showSuccess(message) {
            Swal.fire({
                title: 'Succès !',
                text: message,
                icon: 'success',
                confirmButtonColor: '#3085d6'
            });
        }

        // Fonction pour afficher les messages d'erreur
        function showError(message) {
            Swal.fire({
                title: 'Erreur !',
                text: message,
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }

        // Activer les tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html> 