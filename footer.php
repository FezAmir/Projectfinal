<footer class="bg-light py-4 mt-5 border-top">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3">EasyComp</h5>
                <p class="text-muted mb-3">The easiest way to organize and participate in competitions.</p>
                <div class="social-icons">
                    <a href="#" class="me-2 text-decoration-none">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="me-2 text-decoration-none">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="me-2 text-decoration-none">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="text-decoration-none">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-md-2 mb-4 mb-md-0">
                <h6 class="mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="index.php" class="text-decoration-none text-muted">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="about.php" class="text-decoration-none text-muted">
                            <i class="fas fa-info-circle me-1"></i> About
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="contact.php" class="text-decoration-none text-muted">
                            <i class="fas fa-envelope me-1"></i> Contact
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="col-md-3 mb-4 mb-md-0">
                <h6 class="mb-3">For Users</h6>
                <ul class="list-unstyled">
                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
                        <li class="mb-2">
                            <a href="profile.php" class="text-decoration-none text-muted">
                                <i class="fas fa-user me-1"></i> Profile
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['role'] == 'organizer'): ?>
                            <li class="mb-2">
                                <a href="organizer-competitions.php" class="text-decoration-none text-muted">
                                    <i class="fas fa-trophy me-1"></i> My Competitions
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="create-competition.php" class="text-decoration-none text-muted">
                                    <i class="fas fa-plus-circle me-1"></i> Create Competition
                                </a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'student'): ?>
                            <li class="mb-2">
                                <a href="student-dashboard.php" class="text-decoration-none text-muted">
                                    <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="mb-2">
                            <a href="logout.php" class="text-decoration-none text-muted">
                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="mb-2">
                            <a href="login.php" class="text-decoration-none text-muted">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="signup.php" class="text-decoration-none text-muted">
                                <i class="fas fa-user-plus me-1"></i> Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h6 class="mb-3">Contact Us</h6>
                <address class="text-muted">
                    <div class="mb-2">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        123 Competition Street, Suite 100
                    </div>
                    <div class="mb-2">
                        <i class="fas fa-phone me-2"></i>
                        (123) 456-7890
                    </div>
                    <div>
                        <i class="fas fa-envelope me-2"></i>
                        info@easycomp.com
                    </div>
                </address>
            </div>
        </div>
        
        <hr>
        
        <div class="text-center text-muted">
            <small>&copy; <?php echo date('Y'); ?> EasyComp. All rights reserved.</small>
        </div>
    </div>
</footer> 