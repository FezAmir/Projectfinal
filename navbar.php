<?php
$current_page = basename($_SERVER['PHP_SELF']);
$active_class = 'active';

// Get user data based on role
$user_data = null;
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;

if (isset($user_id) && isset($user_role)) {
    $user_query = "";
    switch ($user_role) {
        case 'admin':
            $user_query = "SELECT * FROM admins WHERE id = ?";
            break;
        case 'organizer':
            $user_query = "SELECT * FROM organizers WHERE id = ?";
            break;
        case 'student':
            $user_query = "SELECT * FROM students WHERE id = ?";
            break;
    }

    if (!empty($user_query) && isset($conn)) {
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        if ($user_result && $user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
        }
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <strong>EasyComp</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? $active_class : ''; ?>" href="index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'admin-dashboard.php') ? $active_class : ''; ?>" href="admin-dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'admin-competitions.php') ? $active_class : ''; ?>" href="admin-competitions.php">
                                <i class="fas fa-trophy"></i> Competitions
                            </a>
                        </li>
                    <?php elseif ($_SESSION['role'] == 'organizer'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'organizer-dashboard.php') ? $active_class : ''; ?>" href="organizer-dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'organizer-competitions.php') ? $active_class : ''; ?>" href="organizer-competitions.php">
                                <i class="fas fa-trophy"></i> My Competitions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'organizer-analytics.php') ? $active_class : ''; ?>" href="organizer-analytics.php">
                                <i class="fas fa-chart-line"></i> Analytics
                            </a>
                        </li>
                    <?php elseif ($_SESSION['role'] == 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'student-dashboard.php') ? $active_class : ''; ?>" href="student-dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'about.php') ? $active_class : ''; ?>" href="about.php">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'contact.php') ? $active_class : ''; ?>" href="contact.php">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if (isset($user_data) && isset($user_data['profile_picture'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile" class="rounded-circle me-1" style="width: 25px; height: 25px; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                            <?php echo isset($user_data['name']) ? htmlspecialchars($user_data['name']) : 'User'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2"></i> Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'login.php') ? $active_class : ''; ?>" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'signup.php') ? $active_class : ''; ?>" href="signup.php">
                            <i class="fas fa-user-plus"></i> Sign Up
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Bootstrap CSS and JavaScript -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" defer></script> 