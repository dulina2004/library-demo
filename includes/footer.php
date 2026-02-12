<?php
/**
 * Footer Include
 * 
 * This file is included at the bottom of every page.
 * It closes the main container, adds the footer, and includes JS files.
 */
?>
</main><!-- End Main Content Container -->

<!-- Footer -->
<footer class="footer-custom mt-auto py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">
                    <i class="bi bi-book-half me-1"></i>
                    <strong>BookFlow</strong> &copy; <?php echo date('Y'); ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 text-muted">University Group Project — Built with PHP, MySQL &amp; Bootstrap 5</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo baseUrl(); ?>/assets/js/main.js"></script>

</body>
</html>
