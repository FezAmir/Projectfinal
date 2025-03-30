<?php
session_start();
require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Here you would typically send the email or save to database
        // For now, we'll just show a success message
        $message = "Thank you for your message! We'll get back to you soon.";
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - EasyComp</title>
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

        /* Navigation Bar */
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

        /* Contact Section */
        .contact-section {
            padding: 120px 0 60px;
            background: var(--bg-secondary);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 60px;
            animation: slideUp 0.5s ease forwards;
        }

        .contact-header h1 {
            font-size: 3rem;
            color: var(--text-primary);
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }

        .contact-header h1::after {
            content: '';
            display: block;
            width: 70px;
            height: 4px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            margin: 10px auto 0;
            border-radius: 2px;
        }

        .contact-header p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 800px;
            margin: 0 auto;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        .contact-info {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            animation: fadeIn 0.5s ease forwards;
            animation-delay: 0.1s;
            opacity: 0;
            height: 100%;
        }

        .contact-info:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px var(--shadow-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-info h2 {
            color: var(--text-primary);
            font-size: 2rem;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }

        .contact-info h2::after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            margin: 10px 0 0;
            border-radius: 2px;
        }

        .contact-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
        }

        .contact-item i {
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-right: 20px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2px solid var(--border-color);
            flex-shrink: 0;
        }

        .contact-item div h3 {
            color: var(--text-primary);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .contact-item div p {
            color: var(--text-secondary);
            line-height: 1.7;
        }

        .contact-form {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 5px 15px var(--shadow-color);
            animation: fadeIn 0.5s ease forwards;
            animation-delay: 0.2s;
            opacity: 0;
        }

        .contact-form:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px var(--shadow-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--gradient-start);
            box-shadow: 0 0 0 3px rgba(78, 115, 223, 0.1);
        }

        textarea.form-control {
            height: 180px;
            resize: vertical;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--gradient-end), var(--gradient-start));
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.3);
        }

        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .message.success {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(28, 200, 138, 0.2);
        }

        .message.success:before {
            content: '\f058';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 10px;
            font-size: 1.25rem;
        }

        .message.error {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 74, 59, 0.2);
        }

        .message.error:before {
            content: '\f057';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 10px;
            font-size: 1.25rem;
        }

        .map-section {
            margin-top: 80px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            height: 400px;
            animation: fadeIn 0.5s ease forwards;
            animation-delay: 0.3s;
            opacity: 0;
        }

        .map-section iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        @media (max-width: 992px) {
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .contact-info, .contact-form {
                padding: 30px;
            }
        }

        @media (max-width: 768px) {
            .contact-header h1 {
                font-size: 2.5rem;
            }
            
            .contact-info h2 {
                font-size: 1.8rem;
            }
            
            .submit-btn {
                padding: 12px 20px;
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

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="contact-container">
            <div class="contact-header">
                <h1>Contact Us</h1>
                <p>Have questions or feedback? We'd love to hear from you. Get in touch with us through the form below or use our contact information.</p>
            </div>

            <div class="contact-grid">
                <div class="contact-info">
                    <h2>Get in Touch</h2>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h3>Address</h3>
                            <p>123 Competition Street<br>City, State 12345</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h3>Phone</h3>
                            <p>+1 (555) 123-4567</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h3>Email</h3>
                            <p>support@easycomp.com</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h3>Working Hours</h3>
                            <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                        </div>
                    </div>
                </div>

                <div class="contact-form">
                    <?php if ($message): ?>
                        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Your Message</label>
                            <textarea id="message" name="message" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>
            </div>

            <div class="map-section">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d387193.30594994064!2d-74.25986652425023!3d40.69714941680757!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c24fa5d33f083b%3A0xc80b8f06e177fe62!2sNew%20York%2C%20NY%2C%20USA!5e0!3m2!1sen!2suk!4v1615374533648!5m2!1sen!2suk" allowfullscreen="" loading="lazy"></iframe>
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