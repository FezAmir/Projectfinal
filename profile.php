<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$user_data = null;

// Fetch user data based on role
switch ($role) {
    case 'admin':
        $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
        break;
    case 'organizer':
        $stmt = $conn->prepare("SELECT * FROM organizers WHERE id = ?");
        break;
    case 'student':
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        break;
    default:
        header('Location: login.php');
        exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    header('Location: login.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate current password
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user_data['password'])) {
            $errors[] = "Current password is incorrect";
        }
    }
    
    // Validate new password
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters long";
        }
        if ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }
    
    // Check if email is already taken by another user
    if ($email !== $user_data['email']) {
        $check_email = $conn->prepare("SELECT id FROM " . $role . "s WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $errors[] = "Email is already taken";
        }
    }
    
    if (empty($errors)) {
        // Update profile
        $update_query = "UPDATE " . $role . "s SET name = ?, email = ?";
        $params = [$name, $email];
        $types = "ss";
        
        if (!empty($new_password)) {
            $update_query .= ", password = ?";
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $params[] = $hashed_password;
            $types .= "s";
        }
        
        $update_query .= " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $_SESSION['success_message'] = "Profile updated successfully";
            header('Location: profile.php');
            exit();
        } else {
            $errors[] = "Error updating profile";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            left: 60px;
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

        /* Profile Section */
        .profile-section {
            padding: 120px 0 60px;
            background: var(--bg-secondary);
        }

        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .profile-header h1 {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .profile-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        .profile-card {
            background: var(--card-bg);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px var(--shadow-color);
        }

        .profile-picture {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-picture img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .profile-picture .upload-btn {
            background: var(--gradient-start);
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .profile-picture .upload-btn:hover {
            background: var(--gradient-end);
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

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--gradient-start);
        }

        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .success-message {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #28a745;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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
            transition: transform 0.3s;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        .theme-toggle i {
            font-size: 1.5rem;
            color: var(--text-primary);
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
            }

            .user-profile {
                display: none;
            }

            .profile-header h1 {
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
                    <?php if (isset($user_data['profile_picture']) && $user_data['profile_picture'] !== 'default.jpg'): ?>
                        <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Picture" class="profile-pic">
                    <?php else: ?>
                        <img src="assets/img/default-avatar.jpg" alt="Default Avatar" class="profile-pic">
                    <?php endif; ?>
                    <span class="user-name"><?php echo htmlspecialchars($user_data['username'] ?? $user_data['name'] ?? 'User'); ?></span>
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

    <!-- Profile Section -->
    <section class="profile-section">
        <div class="profile-container">
            <div class="profile-header">
                <h1>My Profile</h1>
                <p>Manage your account information and preferences</p>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-picture">
                    <div class="profile-pic-container">
                        <?php if (isset($user_data['profile_picture']) && $user_data['profile_picture'] !== 'default.jpg'): ?>
                            <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Picture" class="profile-pic-preview">
                        <?php else: ?>
                            <img src="assets/img/default-avatar.jpg" alt="Default Avatar" class="profile-pic-preview">
                        <?php endif; ?>
                    </div>
                    <form id="uploadForm" enctype="multipart/form-data">
                        <input type="file" id="profilePicInput" name="profile_picture" accept="image/jpeg,image/png,image/gif" style="display: none;">
                        <div class="upload-btn" onclick="document.getElementById('profilePicInput').click()">
                            <i class="fas fa-camera"></i> Change Photo
                        </div>
                    </form>
                </div>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user_data['username'] ?? $user_data['name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>

                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <button type="submit" class="submit-btn">Update Profile</button>
                </form>
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

        // Profile Picture Upload
        const profilePicInput = document.getElementById('profilePicInput');
        if (profilePicInput) {
            profilePicInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    console.log('File selected:', file.name, 'Type:', file.type, 'Size:', file.size);

                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Invalid file type. Only JPG, PNG and GIF are allowed.');
                        return;
                    }

                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File too large. Maximum size is 5MB.');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('profile_picture', file);

                    // Show loading state
                    const uploadBtn = document.querySelector('.upload-btn');
                    const originalText = uploadBtn.innerHTML;
                    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                    uploadBtn.style.pointerEvents = 'none';

                    console.log('Starting upload...');

                    fetch('upload_profile_picture.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Upload response:', data);
                        if (data.success) {
                            // Update profile picture
                            const profileImage = document.querySelector('.profile-pic-preview');
                            if (profileImage) {
                                profileImage.src = data.filepath;
                            }
                            
                            // Show success message
                            alert('Profile picture updated successfully!');
                        } else {
                            console.error('Upload failed:', data.error);
                            alert(data.error || 'Failed to upload image');
                        }
                    })
                    .catch(error => {
                        console.error('Upload error:', error);
                        alert('Failed to upload image. Please try again.');
                    })
                    .finally(() => {
                        // Reset upload button
                        uploadBtn.innerHTML = originalText;
                        uploadBtn.style.pointerEvents = 'auto';
                    });
                }
            });
        }
    </script>
</body>
</html> 