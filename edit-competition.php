<?php
require_once 'config.php';
require_once 'db.php';

// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has permission (admin or organizer)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    header('Location: login.php');
    exit;
}

// Check if competition ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid competition ID";
    
    // Redirect based on user role
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin-competitions.php');
    } else {
        header('Location: organizer-competitions.php');
    }
    exit;
}

$competition_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch competition details
$competition_query = "SELECT c.*, cat.name as category_name 
                     FROM competitions c
                     JOIN categories cat ON c.category_id = cat.id
                     WHERE c.id = ?";
$stmt = $conn->prepare($competition_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$competition_result = $stmt->get_result();

if ($competition_result->num_rows === 0) {
    $_SESSION['error'] = "Competition not found";
    
    // Redirect based on user role
    if ($role === 'admin') {
        header('Location: admin-competitions.php');
    } else {
        header('Location: organizer-competitions.php');
    }
    exit;
}

$competition = $competition_result->fetch_assoc();

// Check if organizer is trying to edit someone else's competition
if ($role === 'organizer' && $competition['organizer_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to edit this competition";
    header('Location: organizer-competitions.php');
    exit;
}

// Check if the competition is already ended
$today = new DateTime();
$end_date = new DateTime($competition['end_date']);
if ($today > $end_date) {
    $_SESSION['error'] = "You cannot edit a competition that has already ended";
    
    if ($role === 'admin') {
        header('Location: admin-competitions.php');
    } else {
        header('Location: organizer-competitions.php');
    }
    exit;
}

// Fetch categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category_id = $_POST['category_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Form validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required";
    }
    
    if (empty($start_date)) {
        $errors[] = "Start date is required";
    }
    
    if (empty($end_date)) {
        $errors[] = "End date is required";
    }
    
    // Check if start date is not after end date
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    if ($start > $end) {
        $errors[] = "Start date cannot be after end date";
    }
    
    // If no validation errors, update competition
    if (empty($errors)) {
        $update_query = "UPDATE competitions SET 
                        title = ?,
                        description = ?,
                        category_id = ?,
                        start_date = ?,
                        end_date = ?,
                        updated_at = NOW()
                        WHERE id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssissi", $title, $description, $category_id, $start_date, $end_date, $competition_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Competition updated successfully";
            
            header('Location: view-competition.php?id=' . $competition_id);
            exit;
        } else {
            $errors[] = "Failed to update competition: " . $conn->error;
        }
    }
}

// Get user data based on role
$user_data = null;
if ($role === 'admin') {
    $user_query = "SELECT * FROM admins WHERE id = ?";
} else {
    $user_query = "SELECT * FROM organizers WHERE id = ?";
}

$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Competition - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Ensure no animations on this page */
        body, .container, .card, .card *, form, form * {
            transition: none !important;
            animation: none !important;
            transform: none !important;
            perspective: none !important;
            animation-delay: 0s !important;
            animation-duration: 0s !important;
        }
        
        .edit-container {
            max-width: 900px;
            margin: 0 auto;
            transform: none !important;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-text {
            margin-top: 5px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .form-check input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .dates-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .dates-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php 
    $navbar_path = 'includes/navbar.php';
    if (file_exists($navbar_path)) {
        include $navbar_path;
    } else {
        echo '<div class="alert alert-warning">Navigation menu not found.</div>';
    }
    ?>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><?php echo ucfirst($_SESSION['role']); ?> Dashboard</h3>
            </div>
            <div class="sidebar-menu">
                <a href="<?php echo $_SESSION['role']; ?>-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin-competitions.php" class="active"><i class="fas fa-trophy"></i> Competitions</a>
                    <a href="admin-organizers.php"><i class="fas fa-users-cog"></i> Organizers</a>
                    <a href="admin-students.php"><i class="fas fa-user-graduate"></i> Students</a>
                    <a href="admin-categories.php"><i class="fas fa-tags"></i> Categories</a>
                    <a href="admin-settings.php"><i class="fas fa-cog"></i> Settings</a>
                <?php else: ?>
                    <a href="organizer-competitions.php" class="active"><i class="fas fa-trophy"></i> My Competitions</a>
                    <a href="create-competition.php"><i class="fas fa-plus-circle"></i> Create Competition</a>
                    <a href="organizer-participants.php"><i class="fas fa-users"></i> Participants</a>
                    <a href="organizer-analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
                    <a href="organizer-settings.php"><i class="fas fa-cog"></i> Settings</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo $_SESSION['role']; ?>-dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo $_SESSION['role']; ?>-competitions.php">Competitions</a></li>
                    <li class="breadcrumb-item"><a href="view-competition.php?id=<?php echo $competition_id; ?>"><?php echo htmlspecialchars($competition['title']); ?></a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ul>
                <h1>Edit Competition</h1>
            </div>
            
            <div class="edit-container">
                <div class="card form-card">
                    <div class="card-header">
                        <h2>Competition Details</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <div><?php echo $error; ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="edit-competition.php?id=<?php echo $competition_id; ?>" method="POST">
                            <div class="form-group">
                                <label for="title">Title*</label>
                                <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($competition['title']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">Category*</label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $competition['category_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="dates-container">
                                <div class="form-group">
                                    <label for="start_date">Start Date*</label>
                                    <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $competition['start_date']; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="end_date">End Date*</label>
                                    <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $competition['end_date']; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description*</label>
                                <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($competition['description']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Competition</button>
                                <a href="view-competition.php?id=<?php echo $competition_id; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </div>

    <script>
        // Theme Toggle
        document.addEventListener('DOMContentLoaded', function() {
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
        });
        
        // User dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const userProfile = document.querySelector('.user-profile');
            
            userProfile.addEventListener('click', function() {
                this.classList.toggle('active');
            });
            
            document.addEventListener('click', function(e) {
                if (!userProfile.contains(e.target)) {
                    userProfile.classList.remove('active');
                }
            });
        });
    </script>

    <?php 
    $footer_path = 'includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    } else {
        echo '<div class="container text-center text-muted py-3">&copy; ' . date('Y') . ' EasyComp. All rights reserved.</div>';
    }
    ?>
    <script src="app.js"></script>
</body>
</html> 