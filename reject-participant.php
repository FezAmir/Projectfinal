<?php
session_start();
require_once 'config.php';

// Check if user is logged in and has permission (admin or organizer)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'organizer')) {
    header('Location: login.php');
    exit;
}

// Check if competition ID and student ID are provided
if (!isset($_GET['competition_id']) || !is_numeric($_GET['competition_id']) || 
    !isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    $_SESSION['error'] = "Invalid parameters";
    
    // Redirect based on user role
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin-competitions.php');
    } else {
        header('Location: organizer-competitions.php');
    }
    exit;
}

$competition_id = $_GET['competition_id'];
$student_id = $_GET['student_id'];
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch competition details to check ownership
$competition_query = "SELECT c.*, o.username as organizer_name FROM competitions c 
                     JOIN organizers o ON c.organizer_id = o.id 
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

// Check if organizer is trying to reject a participant for someone else's competition
if ($role === 'organizer' && $competition['organizer_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to modify this competition";
    header('Location: organizer-competitions.php');
    exit;
}

// Fetch student details
$student_query = "SELECT s.*, cp.status FROM students s 
                 JOIN competition_participants cp ON s.id = cp.student_id 
                 WHERE s.id = ? AND cp.competition_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("ii", $student_id, $competition_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    $_SESSION['error'] = "Participant not found";
    header('Location: competition-participants.php?id=' . $competition_id);
    exit;
}

$student = $student_result->fetch_assoc();

// Get user data based on role
$user_data = null;
$user_query = "";

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rejection_reason = trim($_POST['rejection_reason'] ?? '');
    
    if (empty($rejection_reason)) {
        $error = "Please provide a reason for rejection";
    } else {
        // Update participant status to rejected and add notes
        $update_query = "UPDATE competition_participants SET status = 'rejected', notes = ? 
                        WHERE competition_id = ? AND student_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sii", $rejection_reason, $competition_id, $student_id);
        $result = $stmt->execute();
        
        if ($result) {
            // Create notification for student
            $competition_title = $competition['title'];
            $notification_message = "Your participation in \"" . $competition_title . "\" has been rejected.";
            $notification_query = "INSERT INTO notifications (user_id, user_role, message, link, created_at) 
                                 VALUES (?, 'student', ?, ?, NOW())";
            $notification_link = "view-competition.php?id=" . $competition_id;
            
            $stmt = $conn->prepare($notification_query);
            $stmt->bind_param("iss", $student_id, $notification_message, $notification_link);
            $stmt->execute();
            
            // Log admin action if admin is rejecting
            if ($role === 'admin') {
                $action = "Rejected participant (Student ID: $student_id) for competition: " . $competition_title;
                $log_query = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";
                $stmt = $conn->prepare($log_query);
                $stmt->bind_param("is", $user_id, $action);
                $stmt->execute();
            }
            
            $_SESSION['success'] = "Participant has been rejected successfully";
            header('Location: competition-participants.php?id=' . $competition_id);
            exit;
        } else {
            $error = "Failed to reject participant";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Participant - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="animations.css">
    <style>
        .student-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: var(--bg-secondary);
            border-radius: 8px;
        }
        
        .student-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            overflow: hidden;
            background-color: var(--bg-secondary);
        }
        
        .student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .student-details {
            display: flex;
            flex-direction: column;
        }
        
        .student-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .student-email {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 8px;
            display: inline-block;
        }
        
        .status-pending {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }
        
        .status-approved {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }
        
        .status-rejected {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .competition-details {
            margin-bottom: 20px;
            padding: 20px;
            background-color: var(--bg-secondary);
            border-radius: 8px;
        }
        
        .competition-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .competition-organizer {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        textarea {
            min-height: 120px;
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
                <?php if (isset($user_data['profile_picture']) && $user_data['profile_picture'] !== 'default.jpg'): ?>
                    <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="assets/img/default-avatar.jpg" alt="Default Avatar">
                <?php endif; ?>
                <span class="user-name"><?php echo htmlspecialchars($user_data['name']); ?></span>
                <div class="user-dropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="<?php echo $_SESSION['role']; ?>-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
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
                    <li class="breadcrumb-item"><a href="competition-participants.php?id=<?php echo $competition_id; ?>">Participants</a></li>
                    <li class="breadcrumb-item active">Reject Participant</li>
                </ul>
                <h1 class="animated-underline">Reject Participant</h1>
                <p class="fade-in">Provide a reason for rejecting this participant</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card scale-in">
                <div class="card-header">
                    <h2>Participant Information</h2>
                </div>
                <div class="card-body">
                    <!-- Student Information -->
                    <div class="student-info slide-in-left">
                        <div class="student-avatar">
                            <?php if (isset($student['profile_picture']) && $student['profile_picture'] !== 'default.jpg'): ?>
                                <img src="<?php echo htmlspecialchars($student['profile_picture']); ?>" alt="Profile Picture" class="student-image">
                            <?php else: ?>
                                <img src="assets/img/default-avatar.jpg" alt="Default Avatar" class="student-image">
                            <?php endif; ?>
                        </div>
                        <div class="student-details">
                            <span class="student-name"><?php echo htmlspecialchars($student['name']); ?></span>
                            <span class="student-email"><?php echo htmlspecialchars($student['email']); ?></span>
                            <span class="status-badge status-<?php echo $student['status']; ?>">
                                <?php echo ucfirst($student['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Competition Information -->
                    <div class="competition-details slide-in-right delay-1">
                        <div class="competition-title"><?php echo htmlspecialchars($competition['title']); ?></div>
                        <div class="competition-organizer">Organized by: <?php echo htmlspecialchars($competition['organizer_name']); ?></div>
                    </div>
                    
                    <!-- Rejection Form -->
                    <form action="" method="POST" class="slide-in-up delay-2">
                        <div class="form-group">
                            <label for="rejection_reason">Rejection Reason <span class="required">*</span></label>
                            <textarea id="rejection_reason" name="rejection_reason" class="form-control" required placeholder="Please provide a clear reason for rejection..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-times"></i> Reject Participant
                            </button>
                            <a href="competition-participants.php?id=<?php echo $competition_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </form>
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

    <script src="app.js"></script>
</body>
</html> 