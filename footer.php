<footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> AuthSystem. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Toggle between login and register forms
        $(document).ready(function() {
            $('.auth-tab').click(function() {
                $('.auth-tab').removeClass('active');
                $(this).addClass('active');
                
                if ($(this).data('form') === 'login') {
                    $('#login-form').show();
                    $('#register-form').hide();
                } else {
                    $('#login-form').hide();
                    $('#register-form').show();
                }
            });
        });
    </script>
</body>
</html>