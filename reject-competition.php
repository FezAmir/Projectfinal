<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Check if competition ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid competition ID";
    header('Location: admin-competitions.php');
    exit;
}

$competition_id = $_GET['id'];
$admin_id = $_SESSION['user_id'];

// Fetch competition details
$competition_query = "SELECT c.*, o.username as organizer_name 
                     FROM competitions c 
                     JOIN organizers o ON c.organizer_id = o.id 
                     WHERE c.id = ?";
$stmt = $conn->prepare($competition_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$competition_result = $stmt->get_result();

if ($competition_result->num_rows === 0) {
    $_SESSION['error'] = "Competition not found";
    header('Location: admin-competitions.php');
    exit;
}

$competition = $competition_result->fetch_assoc();

// Handle rejection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    if (empty($rejection_reason)) {
        $_SESSION['error'] = "Rejection reason is required";
    } else {
        // Update competition status to rejected with reason
        $update_query = "UPDATE competitions SET 
                        status = 'rejected', 
                        rejection_reason = ?, 
                        updated_at = NOW() 
                        WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $rejection_reason, $competition_id);
        
        if ($stmt->execute()) {
            // Add notification for the organizer
            $notification_query = "INSERT INTO notifications 
                                  (user_id, user_type, message, related_id, related_type, created_at) 
                                  VALUES (?, 'organizer', ?, ?, 'competition', NOW())";
            $notification_message = "Your competition \"" . $competition['title'] . "\" has been rejected";
            $stmt = $conn->prepare($notification_query);
            $stmt->bind_param("isi", $competition['organizer_id'], $notification_message, $competition_id);
            $stmt->execute();
            
            // Log admin action
            $log_query = "INSERT INTO admin_logs 
                         (admin_id, action, details, created_at) 
                         VALUES (?, 'rejected_competition', ?, NOW())";
            $log_details = "Rejected competition ID: " . $competition_id . ", Title: " . $competition['title'] . ", Reason: " . $rejection_reason;
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("is", $admin_id, $log_details);
            $stmt->execute();
            
            $_SESSION['success'] = "Competition has been rejected";
            
            // Redirect back to competitions page
            header('Location: admin-competitions.php');
            exit;
        } else {
            $_SESSION['error'] = "Failed to reject competition: " . $conn->error;
        }
    }
}

// Fetch admin data
$admin_query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin_data = $admin_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Competition - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .reject-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .competition-summary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .competition-summary h2 {
            margin-bottom: 10px;
        }
        
        .competition-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
        }
        
        .meta-item i {
            margin-right: 8px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
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
            <div class="user-profile">
                <?php if (isset($admin_data['profile_picture']) && $admin_data['profile_picture'] !== 'default.jpg'): ?>
                    <img src="<?php echo htmlspecialchars($admin_data['profile_picture']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="assets/img/default-avatar.jpg" alt="Default Avatar">
                <?php endif; ?>
                <span class="user-name"><?php echo htmlspecialchars($admin_data['username']); ?></span>
                <div class="user-dropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <div class="divider"></div>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3>Admin Dashboard</h3>
            </div>
            <div class="sidebar-menu">
                <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin-competitions.php" class="active"><i class="fas fa-trophy"></i> Competitions</a>
                <a href="admin-organizers.php"><i class="fas fa-users-cog"></i> Organizers</a>
                <a href="admin-students.php"><i class="fas fa-user-graduate"></i> Students</a>
                <a href="admin-categories.php"><i class="fas fa-tags"></i> Categories</a>
                <a href="admin-settings.php"><i class="fas fa-cog"></i> Settings</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="admin-dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="admin-competitions.php">Competitions</a></li>
                    <li class="breadcrumb-item"><a href="view-competition.php?id=<?php echo $competition_id; ?>"><?php echo htmlspecialchars($competition['title']); ?></a></li>
                    <li class="breadcrumb-item active">Reject</li>
                </ul>
                <h1>Reject Competition</h1>
            </div>
            
            <div class="reject-container">
                <!-- Competition Summary -->
                <div class="competition-summary">
                    <h2><?php echo htmlspecialchars($competition['title']); ?></h2>
                    <div class="competition-meta">
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <span>Organizer: <?php echo htmlspecialchars($competition['organizer_name']); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('M d, Y', strtotime($competition['start_date'])); ?> - <?php echo date('M d, Y', strtotime($competition['end_date'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Rejection Form -->
                <div class="card">
                    <div class="card-header">
                        <h2>Rejection Details</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="reject-competition.php?id=<?php echo $competition_id; ?>" method="POST">
                            <div class="form-group">
                                <label for="rejection_reason">Rejection Reason*</label>
                                <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="5" required><?php echo isset($_POST['rejection_reason']) ? htmlspecialchars($_POST['rejection_reason']) : ''; ?></textarea>
                                <div class="form-text">Please provide a clear reason for rejection. This will be shown to the organizer.</div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-danger">Reject Competition</button>
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
</body>
</html> 