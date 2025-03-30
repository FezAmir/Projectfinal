<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f5f5f5;
            --text-primary: #333333;
            --text-secondary: #666666;
            --gradient-start: #007bff;
            --gradient-end: #0056b3;
            --card-bg: #ffffff;
            --border-color: #dddddd;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --icon-color: #666666;
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --gradient-start: #4a90e2;
            --gradient-end: #357abd;
            --card-bg: #2d2d2d;
            --border-color: #404040;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --icon-color: #cccccc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        /* Navigation Bar Styles */
        .navbar {
            background-color: var(--bg-primary);
            box-shadow: 0 2px 5px var(--shadow-color);
            padding: 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            height: 60px;
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

        /* Menu Styles */
        .menu-container {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 260px;
            background-color: var(--bg-primary);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            z-index: 1001;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            display: block;
        }

        .menu-container.active {
            transform: translateX(0);
        }

        .menu-btn {
            font-size: 24px;
            color: var(--icon-color);
            cursor: pointer;
            padding: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            height: 60px;
            position: absolute;
            left: 0;
            z-index: 1002;
            background-color: var(--bg-primary);
        }

        .menu-btn:hover {
            color: var(--icon-hover);
        }

        .menu-btn i::after {
            content: "Menu";
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 10px;
            font-size: 24px;
            font-weight: bold;
            color: var(--text-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .menu-container.active + .menu-btn i::after {
            opacity: 1;
        }

        .dropdown-menu {
            position: relative;
            top: 60px;
            left: 0;
            background-color: var(--bg-primary);
            width: 100%;
            height: calc(100% - 60px);
            overflow-y: auto;
            display: block;
            z-index: 1003;
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-primary);
            text-decoration: none;
            transition: background-color 0.3s ease;
            border-bottom: 1px solid var(--border-color);
        }

        .dropdown-menu a:hover {
            background-color: var(--menu-hover);
        }

        .dropdown-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .dropdown-menu .divider {
            height: 1px;
            background-color: var(--border-color);
            margin: 5px 0;
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
            color: var(--icon-color);
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
            color: var(--icon-color);
            text-decoration: none;
            transition: color 0.3s ease;
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
            transform: translateY(-50%) translateX(0);
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
            background: var(--bg-primary);
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

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--bg-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px var(--shadow-color);
            transition: transform 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .theme-toggle i {
            font-size: 1.5rem;
            color: var(--text-primary);
        }

        /* Mobile Menu */
        .menu-btn {
            display: none;
            font-size: 1.5rem;
            color: var(--text-primary);
            cursor: pointer;
        }

        .menu-container {
            display: none;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* About Section */
        .about-section {
            padding: 120px 0 60px;
            background: var(--bg-secondary);
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .about-header {
            text-align: center;
            margin-bottom: 60px;
            animation: slideUp 0.5s ease forwards;
        }

        .about-header h1 {
            font-size: 3rem;
            color: var(--text-primary);
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }

        .about-header h1::after {
            content: '';
            display: block;
            width: 70px;
            height: 4px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .about-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 800px;
            margin: 0 auto;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }

        .about-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeIn 0.5s ease forwards;
            animation-delay: calc(var(--card-index) * 0.1s);
            opacity: 0;
        }

        .about-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .about-card i {
            font-size: 3rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 20px;
            display: inline-block;
        }

        .about-card h3 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .about-card p {
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .mission-section {
            margin-top: 80px;
            text-align: center;
            padding: 80px 0;
            background: var(--bg-primary);
            border-radius: 30px;
            box-shadow: 0 5px 15px var(--shadow-color);
            animation: fadeIn 0.5s ease forwards;
            animation-delay: 0.3s;
            opacity: 0;
        }

        .mission-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .mission-content h2 {
            color: var(--text-primary);
            font-size: 2.5rem;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .mission-content h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .mission-content p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .team-section {
            padding: 80px 0;
            text-align: center;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .team-card {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeIn 0.5s ease forwards;
            animation-delay: calc(var(--card-index) * 0.1s);
            opacity: 0;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .team-image {
            height: 250px;
            position: relative;
            overflow: hidden;
        }

        .team-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .team-card:hover .team-image img {
            transform: scale(1.1);
        }

        .team-content {
            padding: 20px;
        }

        .team-content h3 {
            color: var(--text-primary);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .team-content p {
            color: var(--text-secondary);
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }

        .social-links a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            transform: translateY(-3px);
        }

        @media (max-width: 768px) {
            .nav-middle {
                display: none;
            }

            .menu-btn {
                display: block;
            }

            .menu-container {
                display: block;
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                height: 100%;
                background: var(--bg-primary);
                padding: 20px;
                transition: left 0.3s ease;
                z-index: 1000;
            }

            .menu-container.active {
                left: 0;
            }

            .dropdown-menu {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .dropdown-menu a {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 10px;
                color: var(--text-primary);
                text-decoration: none;
                transition: background-color 0.3s ease;
            }

            .dropdown-menu a:hover {
                background: var(--bg-secondary);
            }

            .user-profile {
                display: none;
            }

            .about-header h1 {
                font-size: 2.5rem;
            }
            
            .about-grid {
                grid-template-columns: 1fr;
            }
            
            .mission-content h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-content">
            <div class="menu-btn">
                <i class="fas fa-bars"></i>
            </div>
            <div class="menu-container">
                <div class="dropdown-menu">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="about.php"><i class="fas fa-info-circle"></i> About Us</a>
                    <a href="contact.php"><i class="fas fa-envelope"></i> Contact Us</a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="divider"></div>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    <?php endif; ?>
                </div>
            </div>
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
                    <?php if (isset($_SESSION['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>" alt="Profile Picture">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 35px; color: var(--icon-color);"></i>
                    <?php endif; ?>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['name'] ?? 'User'); ?></span>
                    <div class="user-dropdown">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <?php if ($_SESSION['role'] === 'student'): ?>
                            <a href="student-dashboard.php"><i class="fas fa-trophy"></i> My Competitions</a>
                        <?php elseif ($_SESSION['role'] === 'organizer'): ?>
                            <a href="organizer-dashboard.php"><i class="fas fa-trophy"></i> My Competitions</a>
                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                            <a href="admin-dashboard.php"><i class="fas fa-trophy"></i> Manage Competitions</a>
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

    <!-- About Section -->
    <section class="about-section">
        <div class="about-container">
            <div class="about-header">
                <h1>About EasyComp</h1>
                <p>Your one-stop platform for discovering and participating in exciting competitions across various categories.</p>
            </div>

            <div class="about-grid">
                <div class="about-card" style="--card-index: 1">
                    <i class="fas fa-trophy"></i>
                    <h3>Our Mission</h3>
                    <p>To create a vibrant community where organizers and participants come together to create, compete, and celebrate excellence. We aim to provide a platform that makes competition management and participation seamless and enjoyable for everyone.</p>
                </div>
                <div class="about-card" style="--card-index: 2">
                    <i class="fas fa-users"></i>
                    <h3>Our Community</h3>
                    <p>Join thousands of passionate individuals who share your interests and compete in various categories. Our community spans across different fields including arts, sports, academics, and technology, bringing together diverse talents and interests.</p>
                </div>
                <div class="about-card" style="--card-index: 3">
                    <i class="fas fa-lightbulb"></i>
                    <h3>Our Vision</h3>
                    <p>To become the leading platform for competitions, fostering creativity, innovation, and personal growth. We envision a world where competitions are accessible to everyone, providing opportunities for learning, collaboration, and achievement.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mission-section">
        <div class="mission-content">
            <h2>Why Choose EasyComp?</h2>
            <p>We provide a seamless experience for both organizers and participants. Our platform offers easy competition creation, management, and participation. Whether you're looking to showcase your talents or organize an event, EasyComp is here to support your journey.</p>
            <p>With our user-friendly interface, robust features, and dedicated support, we make it easy for you to focus on what matters most - the competition itself. Join us today and experience the difference!</p>
        </div>
    </section>

    <section class="team-section about-container">
        <div class="about-header">
            <h1>Our Team</h1>
            <p>Meet the dedicated professionals behind EasyComp</p>
        </div>
        
        <div class="team-grid">
            <div class="team-card" style="--card-index: 1">
                <div class="team-image">
                    <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=774&q=80" alt="John Doe">
                </div>
                <div class="team-content">
                    <h3>John Doe</h3>
                    <p>Founder & CEO</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="team-card" style="--card-index: 2">
                <div class="team-image">
                    <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=776&q=80" alt="Jane Smith">
                </div>
                <div class="team-content">
                    <h3>Jane Smith</h3>
                    <p>CTO</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="team-card" style="--card-index: 3">
                <div class="team-image">
                    <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=774&q=80" alt="Michael Johnson">
                </div>
                <div class="team-content">
                    <h3>Michael Johnson</h3>
                    <p>Lead Designer</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="team-card" style="--card-index: 4">
                <div class="team-image">
                    <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=774&q=80" alt="Emily Brown">
                </div>
                <div class="team-content">
                    <h3>Emily Brown</h3>
                    <p>Marketing Director</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Theme Toggle -->
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </div>

    <script>
        // Menu Toggle
        const menuBtn = document.querySelector('.menu-btn');
        const menuContainer = document.querySelector('.menu-container');
        const overlay = document.querySelector('.overlay');

        menuBtn.addEventListener('click', () => {
            menuContainer.classList.toggle('active');
            overlay.style.display = menuContainer.classList.contains('active') ? 'block' : 'none';
        });

        overlay.addEventListener('click', () => {
            menuContainer.classList.remove('active');
            overlay.style.display = 'none';
        });

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