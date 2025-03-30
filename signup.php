<?php
session_start();
require_once 'config.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Redirect based on role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin-dashboard.php');
            break;
        case 'organizer':
            header('Location: organizer-dashboard.php');
            break;
        case 'student':
            header('Location: student-dashboard.php');
            break;
    }
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Form validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($role)) {
        $errors[] = "Role selection is required";
    }
    
    // Check if email already exists in any role table
    $tables = ['students', 'organizers', 'admins'];
    $email_exists = false;
    
    foreach ($tables as $table) {
        $check_query = "SELECT COUNT(*) as count FROM $table WHERE email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            $email_exists = true;
            break;
        }
    }
    
    if ($email_exists) {
        $errors[] = "Email address is already in use";
    }
    
    // If no validation errors, create the account
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Determine table based on role
        $table = '';
        switch ($role) {
            case 'student':
                $table = 'students';
                break;
            case 'organizer':
                $table = 'organizers';
                break;
            case 'admin':
                $table = 'admins';
                break;
            default:
                $errors[] = "Invalid role selected";
                break;
        }
        
        if (!empty($table)) {
            // Insert new user
            if ($role === 'student') {
                $stmt = $conn->prepare("INSERT INTO students (username, email, password, profile_picture) VALUES (?, ?, ?, 'default.jpg')");
            } else {
                $stmt = $conn->prepare("INSERT INTO teachers (username, email, password, profile_picture) VALUES (?, ?, ?, 'default.jpg')");
            }
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Account created successfully! You can now login.";
                header('Location: login.php');
                exit;
            } else {
                $errors[] = "Error creating account: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .auth-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .auth-card {
            background-color: var(--bg-primary);
            border-radius: 10px;
            box-shadow: 0 10px 30px var(--shadow-color);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        
        .auth-header {
            padding: 30px;
            text-align: center;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
        }
        
        .auth-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .auth-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--link-color);
            box-shadow: 0 0 0 3px rgba(58, 134, 255, 0.1);
        }
        
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.3s;
        }
        
        .role-option i {
            font-size: 1.5rem;
            margin-bottom: 5px;
            display: block;
        }
        
        .role-option.active {
            border-color: var(--link-color);
            background-color: rgba(58, 134, 255, 0.05);
            color: var(--link-color);
        }
        
        .role-option:hover {
            background-color: var(--bg-secondary);
        }
        
        .btn-signup {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .auth-footer {
            padding: 15px 30px;
            text-align: center;
            border-top: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .auth-footer a {
            color: var(--link-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: white;
            font-size: 0.9rem;
        }
        
        .alert-danger {
            background-color: var(--danger-color);
        }
        
        .alert-success {
            background-color: var(--success-color);
        }
        
        .password-requirements {
            margin-top: 5px;
            color: var(--text-secondary);
            font-size: 0.8rem;
        }
        
        .form-check {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .form-check input {
            margin-right: 10px;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-trophy" style="font-size: 3rem; margin-bottom: 15px;"></i>
                <h1>EasyComp</h1>
                <p>Competition Management Platform</p>
            </div>
            
            <div class="auth-body">
                <h2 style="margin-bottom: 20px; text-align: center; color: var(--text-primary);">Create an Account</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form action="signup.php" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Create a password" required>
                        <div class="password-requirements">Password must be at least 8 characters long</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                    </div>
                    
                    <p style="margin-bottom: 10px; color: var(--text-primary);">I am registering as:</p>
                    
                    <div class="role-selector">
                        <label class="role-option" data-role="student">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student</span>
                            <input type="radio" name="role" value="student" style="display: none;" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'checked' : ''; ?>>
                        </label>
                        <label class="role-option" data-role="organizer">
                            <i class="fas fa-users-cog"></i>
                            <span>Organizer</span>
                            <input type="radio" name="role" value="organizer" style="display: none;" <?php echo (isset($_POST['role']) && $_POST['role'] === 'organizer') ? 'checked' : ''; ?>>
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                    </div>
                    
                    <button type="submit" class="btn-signup">Create Account</button>
                </form>
            </div>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Log In</a></p>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </div>

    <script>
        // Role selector functionality
        const roleOptions = document.querySelectorAll('.role-option');
        
        roleOptions.forEach(option => {
            const input = option.querySelector('input[type="radio"]');
            
            // Set initial active state based on checked status
            if (input.checked) {
                option.classList.add('active');
            }
            
            option.addEventListener('click', () => {
                // Remove active class from all options
                roleOptions.forEach(opt => opt.classList.remove('active'));
                
                // Add active class to clicked option
                option.classList.add('active');
                
                // Check the radio input
                input.checked = true;
            });
        });
        
        // Password match validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        
        function validatePassword() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        password.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
        
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