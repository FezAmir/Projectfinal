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
$success = false;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Student-specific field
    $registration_number = isset($_POST['registration_number']) ? trim($_POST['registration_number']) : '';
    
    // Validate input
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($role)) {
        $errors[] = "Please select your role";
    }
    
    // Student-specific validation
    if ($role === 'student' && empty($registration_number)) {
        $errors[] = "Registration number is required for students";
    }
    
    // If no validation errors, proceed with registration
    if (empty($errors)) {
        // Choose the correct table based on role
        $table = '';
        switch ($role) {
            case 'student':
                $table = 'students';
                break;
            case 'organizer':
                $table = 'organizers';
                break;
            case 'admin':
                // Admins cannot register themselves, only through the admin panel
                $errors[] = "Admin registration is not allowed through this form";
                break;
            default:
                $errors[] = "Invalid role selected";
        }
        
        if (!empty($table) && empty($errors)) {
            // Check if email already exists
            $check_query = "SELECT id FROM $table WHERE email = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors[] = "Email is already registered. Please use a different email or login instead.";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user based on role
                if ($role === 'student') {
                    $insert_query = "INSERT INTO students (username, email, password, registration_number) VALUES (?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $registration_number);
                } else {
                    // Organizer
                    $insert_query = "INSERT INTO $table (username, email, password) VALUES (?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_query);
                    $insert_stmt->bind_param("sss", $username, $email, $hashed_password);
                }
                
                if ($insert_stmt->execute()) {
                    $success = true;
                    // Clear form data on success
                    unset($username, $email, $password, $confirm_password, $registration_number);
                } else {
                    $errors[] = "Registration failed: " . $conn->error;
                }
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
    <title>Register - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .register-container {
            max-width: 600px;
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
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
    
    <div class="container register-container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-plus"></i> Create an Account</h2>
            </div>
            
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert-success">
                        <i class="fas fa-check-circle"></i> Registration successful! You can now <a href="login.php">login</a> to your account.
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="register.php" method="POST">
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
                        </div>
                        <input type="hidden" name="role" id="role" value="<?php echo isset($_POST['role']) ? htmlspecialchars($_POST['role']) : 'student'; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div id="student-fields" style="<?php echo (!isset($_POST['role']) || $_POST['role'] === 'student') ? '' : 'display: none;'; ?>">
                        <div class="form-group">
                            <label for="registration_number">Student Registration Number</label>
                            <input type="text" id="registration_number" name="registration_number" class="form-control" value="<?php echo isset($registration_number) ? htmlspecialchars($registration_number) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleOptions = document.querySelectorAll('.role-option');
            const roleInput = document.getElementById('role');
            const studentFields = document.getElementById('student-fields');
            
            function updateStudentFields() {
                if (roleInput.value === 'student') {
                    studentFields.style.display = '';
                } else {
                    studentFields.style.display = 'none';
                }
            }
            
            roleOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    roleOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Update hidden input value
                    roleInput.value = this.dataset.role;
                    
                    // Update visibility of student-specific fields
                    updateStudentFields();
                });
            });
        });
    </script>
</body>
</html> 