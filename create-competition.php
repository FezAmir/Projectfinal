<?php
require_once 'config.php';
require_once 'db.php';

// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['error'] = "Please log in to continue";
    header("Location: login.php");
    exit;
}

// Check if user is an organizer or admin
if ($_SESSION['role'] !== 'organizer' && $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Only organizers can create competitions";
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

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
    
    // Check if the start date is not before today
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    
    if ($start < $today) {
        $errors[] = "Start date cannot be in the past";
    }
    
    // If no validation errors, create competition
    if (empty($errors)) {
        // Set status based on role (auto-approve for admin)
        $status = $role === 'admin' ? 'approved' : 'pending';
        
        $create_query = "INSERT INTO competitions (title, description, category_id, start_date, end_date, 
                        organizer_id, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($create_query);
        $stmt->bind_param("ssissss", $title, $description, $category_id, $start_date, $end_date, 
                         $user_id, $status);
        
        if ($stmt->execute()) {
            $competition_id = $conn->insert_id;
            $_SESSION['success'] = "Competition created successfully";
            
            if ($role === 'admin') {
                header('Location: admin-competitions.php');
            } else {
                header('Location: organizer-competitions.php');
            }
            exit;
        } else {
            $errors[] = "Failed to create competition: " . $conn->error;
        }
    }
}

// Get user data
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
    <title>Create Competition - EasyComp</title>
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
        
        .create-container {
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
    
    <div class="container create-container my-5">
        <div class="card form-card">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle"></i> Create Competition</h2>
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
                
                <form action="create-competition.php" method="POST">
                    <div class="form-group">
                        <label for="title">Competition Title</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select a category</option>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="start_date">Start Date</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="end_date">End Date</label>
                                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Competition
                        </button>
                        
                        <?php if ($role === 'admin'): ?>
                            <a href="admin-competitions.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php else: ?>
                            <a href="organizer-competitions.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
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