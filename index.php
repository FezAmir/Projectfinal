<?php
// Start the session
session_start();

// Include database configuration
require_once 'config/db.php';

// Make sure we have a database connection
if (!isset($conn) || $conn->connect_error) {
    // Log the error instead of showing it to users
    error_log("Database connection error: " . ($conn->connect_error ?? "Connection variable not set"));
    
    // Display a user-friendly message
    echo '<div style="text-align: center; margin-top: 50px;">
            <h1>Site Maintenance</h1>
            <p>Sorry for the inconvenience. We\'re performing some maintenance at the moment.</p>
            <p>Please try again later.</p>
          </div>';
    exit;
}

// Set default values
$featured_result = null;
$categories_result = null;

// Try to fetch featured competitions only if we have a valid connection
try {
    $featured_query = "SELECT c.* 
                      FROM competitions c 
                      WHERE c.status = 'approved' 
                      AND c.end_date >= CURDATE()
                      ORDER BY c.created_at DESC 
                      LIMIT 6";
    $featured_result = $conn->query($featured_query);
    
    if ($featured_result === false) {
        error_log("Featured competitions query error: " . $conn->error);
    }
} catch (Exception $e) {
    error_log("Exception in featured competitions query: " . $e->getMessage());
}

// Define default categories
$default_categories = [
    ['name' => 'Gaming', 'icon' => 'fa-gamepad', 'description' => 'Video games, board games, and other gaming competitions'],
    ['name' => 'Sports', 'icon' => 'fa-running', 'description' => 'Athletic competitions and sporting events'],
    ['name' => 'Art', 'icon' => 'fa-palette', 'description' => 'Drawing, painting, and other artistic competitions'],
    ['name' => 'Music', 'icon' => 'fa-music', 'description' => 'Musical performances and contests'],
    ['name' => 'Programming', 'icon' => 'fa-code', 'description' => 'Coding challenges and hackathons'],
    ['name' => 'Photography', 'icon' => 'fa-camera', 'description' => 'Photography contests and exhibitions']
];

// Use default categories
$categories_result = new ArrayObject($default_categories);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyComp - Competition Platform</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Theme Variables */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #333333;
            --text-secondary: #6c757d;
            --gradient-start: #4e73df;
            --gradient-end: #224abe;
            --card-bg: #ffffff;
            --border-color: #e3e6f0;
            --shadow-color: rgba(0, 0, 0, 0.05);
            --icon-color: #5a5c69;
            --icon-hover: #4e73df;
            --dropdown-hover: #f8f9fa;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
        }

        :root[data-theme="dark"] {
            --bg-primary: #1e1e2f;
            --bg-secondary: #27293d;
            --text-primary: #ffffff;
            --text-secondary: #a9a9a9;
            --gradient-start: #3358f4;
            --gradient-end: #1d43e6;
            --card-bg: #27293d;
            --border-color: #404358;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --icon-color: #a9a9a9;
            --icon-hover: #3358f4;
            --dropdown-hover: #2d2d44;
            --success-color: #00a65a;
            --danger-color: #c9302c;
            --warning-color: #c29d0b;
            --info-color: #0097bc;
        }

        body {
            font-family: 'Nunito', 'Segoe UI', 'Roboto', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.7;
            transition: background-color 0.3s, color 0.3s;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Navigation Bar Styles */
        .navbar {
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background-color: rgba(var(--bg-primary-rgb, 255, 255, 255), 0.8);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 70px;
        }

        .nav-content {
            max-width: 100%;
            margin: 0;
            display: flex;
            align-items: center;
            padding: 0;
            position: relative;
            height: 100%;
            justify-content: space-between;
        }

        .logo {
            position: absolute;
            left: 20px;
            font-size: 24px;
            font-weight: bold;
            color: var(--icon-color);
            display: flex;
            align-items: center;
            gap: 10px;
            height: 60px;
            padding: 0 0px;
        }

        .logo i {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .logo-text {
            font-weight: 800;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            transition: all 0.3s;
        }

        .logo-text:hover {
            color: var(--icon-hover);
        }

        .nav-middle {
            display: flex;
            gap: 50px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }

        .nav-icon {
            color: var(--icon-color);
            font-size: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            padding: 5px 10px;
        }

        .nav-icon i {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
        }

        .nav-icon span {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: -10px;
            font-size: 14px;
            color: var(--icon-color);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            pointer-events: none;
            z-index: 100;
        }

        .nav-icon:hover {
            color: var(--icon-hover);
        }

        .nav-icon:hover i {
            transform: translateX(-8px);
        }

        .nav-icon:hover span {
            opacity: 1;
            transform: translateY(-67%) translateX(0);
        }

        .user-profile {
            position: absolute;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            height: 60px;
            padding: 0 10px;
            cursor: pointer;
        }

        .user-profile img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-profile .user-name {
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--dropdown-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px var(--shadow-color);
            padding: 10px 0;
            min-width: 200px;
            display: none;
            z-index: 1000;
        }

        .user-profile:hover .user-dropdown {
            display: block;
        }

        .user-dropdown a {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            color: var(--text-primary);
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .user-dropdown a:hover {
            background-color: var(--dropdown-hover);
        }

        .user-dropdown i {
            width: 20px;
            margin-right: 10px;
        }

        .user-dropdown .divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 5px 0;
        }

        .account-icon {
            position: absolute;
            right: 20px;
            color: var(--icon-color);
            font-size: 24px;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .account-icon:hover {
            color: var(--icon-hover);
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 120px 0 80px;
            text-align: center;
            position: relative;
            border-radius: 0 0 50px 50px;
            margin-bottom: 30px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1541746972996-4e0b0f43e02a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1950&q=80') center/cover;
            opacity: 0.1;
            border-radius: 0 0 50px 50px;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .hero-text {
            font-size: 1.25rem;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            border-radius: 8px;
            font-weight: 600;
            padding: 10px 20px;
            transition: all 0.3s;
            letter-spacing: 0.3px;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gradient-end), var(--gradient-start));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-outline-primary {
            border: 2px solid var(--gradient-start);
            color: var(--gradient-start);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background-color: var(--gradient-start);
            color: white;
            transform: translateY(-2px);
        }

        /* Featured Competitions */
        .featured-section {
            padding: 60px 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: inline-block;
            margin-bottom: 15px;
        }

        .section-title h2::after {
            content: '';
            display: block;
            width: 70px;
            height: 4px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .section-title p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }

        .competitions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .competition-card {
            border-radius: 12px;
            overflow: hidden;
            height: 100%;
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: var(--card-bg);
            border: none;
            box-shadow: 0 5px 15px var(--shadow-color);
        }

        .competition-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--shadow-color);
        }

        .competition-img {
            height: 200px;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .competition-card:hover .competition-img {
            transform: scale(1.05);
        }

        .competition-body {
            padding: 20px;
        }

        .competition-title {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 10px;
            font-size: 1.25rem;
        }

        .competition-text {
            color: var(--text-secondary);
            margin-bottom: 15px;
        }

        .competition-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .competition-date, .competition-category {
            font-size: 0.875rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .competition-meta i {
            color: var(--gradient-start);
        }

        .competition-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .status-open {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }

        .status-closed {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }

        .status-upcoming {
            background-color: rgba(54, 185, 204, 0.1);
            color: var(--info-color);
        }

        /* Categories Section */
        .category-section {
            padding: 60px 0;
            background-color: var(--bg-primary);
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 0 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .category-card {
            height: 100%;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            box-shadow: 0 5px 15px var(--shadow-color);
            background-color: var(--card-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 20px;
            text-align: center;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px var(--shadow-color);
        }

        .category-icon {
            font-size: 3rem;
            color: var(--gradient-start);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .category-card:hover .category-icon {
            transform: scale(1.1);
        }

        .category-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-primary);
        }

        .category-description {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Call to Action Section */
        .cta-section {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            padding: 80px 0;
            color: white;
            text-align: center;
            border-radius: 50px;
            margin: 60px 0;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-text {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .cta-btn {
            background-color: white;
            color: var(--gradient-start);
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .cta-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
            background-color: rgba(255, 255, 255, 0.9);
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--card-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px var(--shadow-color);
            transition: all 0.3s;
            z-index: 1010;
            border: 2px solid transparent;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            border-color: var(--gradient-start);
        }

        .theme-toggle i {
            font-size: 1.5rem;
            color: var(--text-primary);
            transition: all 0.3s;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-section {
                padding: 100px 0 60px;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-text {
                font-size: 1rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .cta-title {
                font-size: 2rem;
            }
            
            .section-title h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay -->
    <div class="overlay"></div>
    
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-content">
            <div class="logo">
                <i class="fas fa-trophy"></i>
                <span class="logo-text">EasyComp</span>
            </div>
            <div class="nav-middle">
                <a href="index.php" class="nav-icon"><i class="fas fa-home"></i><span>Home</span></a>
                <a href="about.php" class="nav-icon"><i class="fas fa-info-circle"></i><span>About Us</span></a>
                <a href="contact.php" class="nav-icon"><i class="fas fa-envelope"></i><span>Contact Us</span></a>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-profile">
                    <?php if (isset($_SESSION['profile_picture']) && $_SESSION['profile_picture'] != 'default.jpg'): ?>
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <img src="assets/img/default-avatar.jpg" alt="Default Avatar">
                    <?php endif; ?>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['name'] ?? 'User'); ?></span>
                    <div class="user-dropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student'): ?>
                            <a href="student-dashboard.php"><i class="fas fa-trophy"></i> My Competitions</a>
                        <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher'): ?>
                            <a href="teacher-dashboard.php"><i class="fas fa-trophy"></i> My Competitions</a>
                        <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                            <a href="admin-dashboard.php"><i class="fas fa-trophy"></i> Manage Competitions</a>
                        <?php else: ?>
                            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Complete Login</a>
                        <?php endif; ?>
                        <div class="divider"></div>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="account-icon"><i class="fas fa-user"></i></a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Welcome to EasyComp</h1>
            <p class="hero-text">Your one-stop platform for participating in and organizing competitions.</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary">Get Started</a>
                    <a href="signup.php" class="btn btn-outline-primary">Sign Up</a>
                </div>
            <?php else: ?>
                <div class="hero-buttons">
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'student'): ?>
                        <a href="student-dashboard.php" class="btn btn-primary">View Competitions</a>
                    <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher'): ?>
                        <a href="teacher-dashboard.php" class="btn btn-primary">Create Competition</a>
                    <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                        <a href="admin-dashboard.php" class="btn btn-primary">Manage Competitions</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Complete Login</a>
                    <?php endif; ?>
                    <a href="profile.php" class="btn btn-outline-primary">My Profile</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Competitions -->
    <section class="featured-section">
        <div class="section-title">
            <h2>Featured Competitions</h2>
            <p>Discover exciting competitions happening right now</p>
        </div>
        <div class="competitions-grid">
            <?php if ($featured_result && $featured_result->num_rows > 0): ?>
                <?php while ($competition = $featured_result->fetch_assoc()): ?>
                    <div class="competition-card">
                        <div class="competition-img">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="competition-body">
                            <h3 class="competition-title"><?php echo htmlspecialchars($competition['title']); ?></h3>
                            <div class="competition-meta">
                                <span class="competition-date"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($competition['start_date'])); ?></span>
                                <span class="competition-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($competition['category'] ?? 'General'); ?></span>
                            </div>
                            <p class="competition-text"><?php echo htmlspecialchars(substr($competition['description'], 0, 100)) . '...'; ?></p>
                            <div class="competition-footer">
                                <span class="competition-status status-<?php echo strtolower($competition['status']); ?>">
                                    <?php echo htmlspecialchars($competition['status']); ?>
                                </span>
                                <a href="competition-details.php?id=<?php echo $competition['id']; ?>" class="btn btn-primary">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No featured competitions at the moment.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="category-section">
        <div class="section-title">
            <h2>Competition Categories</h2>
            <p>Browse competitions by category</p>
        </div>
        <div class="categories-grid">
            <?php if ($categories_result && ($categories_result instanceof ArrayObject || $categories_result->num_rows > 0)): ?>
                <?php if ($categories_result instanceof ArrayObject): ?>
                    <?php foreach ($categories_result as $category): ?>
                        <a href="competitions.php?category=<?php echo urlencode($category['name']); ?>" class="category-card">
                            <div class="category-icon">
                                <i class="fas <?php echo $category['icon']; ?>"></i>
                            </div>
                            <h3 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <a href="competitions.php?category=<?php echo urlencode($category['name']); ?>" class="category-card">
                            <div class="category-icon">
                                <i class="fas fa-gamepad"></i>
                            </div>
                            <h3 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            <?php else: ?>
                <p>No categories available.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section">
        <div class="cta-content">
            <h2 class="cta-title">Ready to Join?</h2>
            <p class="cta-text">Join our community and start participating in exciting competitions.</p>
            <a href="signup.php" class="cta-btn">Sign Up</a>
        </div>
    </section>

    <!-- Theme Toggle -->
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        const icon = themeToggle.querySelector('i');

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            icon.className = newTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            
            localStorage.setItem('theme', newTheme);
        });

        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            html.setAttribute('data-theme', savedTheme);
            icon.className = savedTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
    </script>
</body>
</html>