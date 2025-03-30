<?php
require_once 'config.php';
require_once 'db.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
        default:
            header('Location: index.php');
    }
    exit;
}

$errors = [];

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validate input
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    if (empty($role)) {
        $errors[] = "Please select your role";
    }
    
    // If no validation errors, proceed with login
    if (empty($errors)) {
        // Choose the correct table based on role
        $table = '';
        switch ($role) {
            case 'admin':
                $table = 'admins';
                break;
            case 'organizer':
                $table = 'organizers';
                break;
            case 'student':
                $table = 'students';
                break;
            default:
                $errors[] = "Invalid role selected";
        }
        
        if (!empty($table)) {
            // Query to get user with the provided email
            $query = "SELECT id, email, password, username FROM $table WHERE email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $role;
                    $_SESSION['username'] = $user['username'];
                    
                    // Redirect based on role
                    switch ($role) {
                        case 'admin':
                            header('Location: admin-dashboard.php');
                            break;
                        case 'organizer':
                            header('Location: organizer-dashboard.php');
                            break;
                        case 'student':
                            header('Location: student-dashboard.php');
                            break;
                        default:
                            header('Location: index.php');
                    }
                    exit;
                } else {
                    $errors[] = "Invalid email or password";
                }
            } else {
                $errors[] = "Invalid email or password";
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
    <title>Login - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-container {
            max-width: 450px;
            margin: 80px auto;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            font-size: 16px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color-dark);
        }
        
        .role-selection {
            display: flex;
            margin-bottom: 20px;
            gap: 10px;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
            padding: 15px 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .role-option:hover {
            border-color: var(--primary-color);
        }
        
        .role-option.selected {
            border-color: var(--primary-color);
            background-color: rgba(var(--primary-rgb), 0.1);
        }
        
        .role-option i {
            display: block;
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php 
    $navbar_path = 'includes/navbar.php';
    if (file_exists($navbar_path)) {
        include $navbar_path;
    }
    ?>
    
    <div class="container login-container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-sign-in-alt"></i> Login to EasyComp</h2>
            </div>
            
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="role">I am a:</label>
                        <div class="role-selection">
                            <div class="role-option <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>" data-role="student">
                                <i class="fas fa-user-graduate"></i>
                                <span>Student</span>
                            </div>
                            <div class="role-option <?php echo (isset($_POST['role']) && $_POST['role'] === 'organizer') ? 'selected' : ''; ?>" data-role="organizer">
                                <i class="fas fa-user-tie"></i>
                                <span>Organizer</span>
                            </div>
                            <div class="role-option <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>" data-role="admin">
                                <i class="fas fa-user-shield"></i>
                                <span>Admin</span>
                            </div>
                        </div>
                        <input type="hidden" name="role" id="role" value="<?php echo isset($_POST['role']) ? htmlspecialchars($_POST['role']) : 'student'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="register.php">Register now</a></p>
                    <p><a href="forgot-password.php">Forgot your password?</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleOptions = document.querySelectorAll('.role-option');
            const roleInput = document.getElementById('role');
            
            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    roleOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Update hidden input value
                    roleInput.value = this.dataset.role;
                });
            });
        });
    </script>
</body>
</html> 