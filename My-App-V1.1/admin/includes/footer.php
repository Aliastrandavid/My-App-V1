    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (for some Bootstrap components) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Admin custom scripts -->
    <script>
        // Confirm delete actions
        document.querySelectorAll('.confirm-delete').forEach(item => {
            item.addEventListener('click', event => {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    event.preventDefault();
                }
            });
        });
        
        // Toggle sidebar on mobile
        const sidebar = document.querySelector('#sidebarMenu');
        if (sidebar) {
            document.querySelector('.navbar-toggler').addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }
    </script>
</body>
</html>