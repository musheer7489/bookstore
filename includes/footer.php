</main>
        
        <!-- Footer -->
        <footer class="bg-dark text-white py-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-4 mb-md-0">
                        <h5>About <?php echo SITE_NAME; ?></h5>
                        <p>Your one-stop shop for all your reading needs. We offer a wide selection of paperback books across various genres.</p>
                        <div class="social-icons">
                            <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="text-white"><i class="fab fa-pinterest"></i></a>
                        </div>
                    </div>
                    
                    <div class="col-md-2 mb-4 mb-md-0">
                        <h5>Quick Links</h5>
                        <ul class="list-unstyled">
                            <li><a href="<?php echo SITE_URL; ?>" class="text-white">Home</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/products/" class="text-white">All Books</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/blog/" class="text-white">Blog</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/about.php" class="text-white">About Us</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/contact.php" class="text-white">Contact</a></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-3 mb-4 mb-md-0">
                        <h5>Customer Service</h5>
                        <ul class="list-unstyled">
                            <li><a href="<?php echo SITE_URL; ?>/faq.php" class="text-white">FAQ</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/shipping.php" class="text-white">Shipping Policy</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/returns.php" class="text-white">Return Policy</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/privacy.php" class="text-white">Privacy Policy</a></li>
                            <li><a href="<?php echo SITE_URL; ?>/terms.php" class="text-white">Terms & Conditions</a></li>
                        </ul>
                    </div>
                    
                    <div class="col-md-3">
                        <h5>Contact Info</h5>
                        <address>
                            <p><i class="fas fa-map-marker-alt me-2"></i> 123 Book Street, Agra, India</p>
                            <p><i class="fas fa-phone me-2"></i> +91 9876543210</p>
                            <p><i class="fas fa-envelope me-2"></i> info@bookhaven.com</p>
                        </address>
                        <h6>Newsletter</h6>
                        <form class="mb-3">
                            <div class="input-group">
                                <input type="email" class="form-control" placeholder="Your email">
                                <button class="btn btn-primary" type="submit">Subscribe</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <hr class="my-4 bg-secondary">
                
                <div class="row">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <img src="<?php echo SITE_URL; ?>/assets/images/payment-methods.png" alt="Accepted payment methods" class="img-fluid" style="max-height: 30px;">
                    </div>
                </div>
            </div>
        </footer>
        
        <!-- Bootstrap 5 JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Custom JS -->
        <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    </body>
</html>
<?php
// Flush output buffer
ob_end_flush();
?>