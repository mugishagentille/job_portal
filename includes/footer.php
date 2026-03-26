    </div> <!-- Close main-content -->

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>About <?php echo SITE_NAME; ?></h5>
                    <p class="small">Connecting talented professionals with great employers. Find your dream job or the perfect candidate today.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>" class="text-white text-decoration-none">
                                <i class="fas fa-home"></i> Home
                            </a></li>
                        <li class="mb-2"><a href="<?php echo BASE_URL; ?>jobs.php" class="text-white text-decoration-none">
                                <i class="fas fa-briefcase"></i> Browse Jobs
                            </a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li class="mb-2"><a href="<?php echo BASE_URL; ?>register.php" class="text-white text-decoration-none">
                                    <i class="fas fa-user-plus"></i> Register
                                </a></li>
                        <?php endif; ?>
                        <li>
                            <i class="fas fa-envelope"></i> Contact
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-envelope"></i> info@<?php echo strtolower(SITE_NAME); ?>.com</li>
                        <li class="mb-2"><i class="fas fa-phone"></i> +250 784 567 890</li>
                        <li class="mb-2"><i class="fas fa-map-marker-alt"></i> Kigali, Rwanda</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0 small">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // Flush output buffer at the end
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    ?>
    </body>

    </html>