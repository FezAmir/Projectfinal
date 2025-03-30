<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

// Check if competition ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid competition ID";
    header('Location: student-dashboard.php');
    exit;
}

$competition_id = $_GET['id'];
$student_id = $_SESSION['user_id'];

// Check if the student is actually registered for this competition
$check_query = "SELECT cp.*, c.title as competition_title, o.id as organizer_id
                FROM competition_participants cp
                JOIN competitions c ON cp.competition_id = c.id
                JOIN organizers o ON c.organizer_id = o.id
                WHERE cp.competition_id = ? AND cp.student_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $competition_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "You are not registered for this competition";
    header('Location: student-dashboard.php');
    exit;
}

$participation = $result->fetch_assoc();

// Handle confirmation
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Delete the participation
    $delete_query = "DELETE FROM competition_participants WHERE competition_id = ? AND student_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("ii", $competition_id, $student_id);
    
    if ($stmt->execute()) {
        // Add notification for the organizer
        $notification_query = "INSERT INTO notifications 
                              (user_id, user_type, message, related_id, related_type, created_at) 
                              VALUES (?, 'organizer', ?, ?, 'participation', NOW())";
        $notification_message = "A student has cancelled their participation in competition: " . $participation['competition_title'];
        $stmt = $conn->prepare($notification_query);
        $stmt->bind_param("isi", $participation['organizer_id'], $notification_message, $competition_id);
        $stmt->execute();
        
        $_SESSION['success'] = "Your participation has been cancelled successfully";
        header('Location: student-dashboard.php');
        exit;
    } else {
        $_SESSION['error'] = "Failed to cancel participation: " . $conn->error;
    }
}

// Fetch student data
$student_query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Participation - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .cancel-container {
            max-width: 700px;
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
                <?php if (isset($student_data['profile_picture']) && $student_data['profile_picture'] !== 'default.jpg'): ?>
                    <img src="<?php echo htmlspecialchars($student_data['profile_picture']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="assets/img/default-avatar.jpg" alt="Default Avatar">
                <?php endif; ?>
                <span class="user-name"><?php echo htmlspecialchars($student_data['username']); ?></span>
                <div class="user-dropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="student-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
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
                <h3>Student Dashboard</h3>
            </div>
            <div class="sidebar-menu">
                <a href="student-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="student-competitions.php"><i class="fas fa-trophy"></i> Competitions</a>
                <a href="student-participations.php" class="active"><i class="fas fa-flag-checkered"></i> My Participations</a>
                <a href="student-achievements.php"><i class="fas fa-medal"></i> Achievements</a>
                <a href="student-settings.php"><i class="fas fa-cog"></i> Settings</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="student-dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="view-competition.php?id=<?php echo $competition_id; ?>"><?php echo htmlspecialchars($participation['competition_title']); ?></a></li>
                    <li class="breadcrumb-item active">Cancel Participation</li>
                </ul>
                <h1>Cancel Participation</h1>
            </div>
            
            <div class="cancel-container">
                <!-- Competition Summary -->
                <div class="competition-summary">
                    <h2><?php echo htmlspecialchars($participation['competition_title']); ?></h2>
                    <div class="status-badge status-<?php echo $participation['status']; ?>">
                        <?php echo ucfirst($participation['status']); ?>
                    </div>
                </div>

                <!-- Confirm Cancellation -->
                <div class="card">
                    <div class="card-header">
                        <h2>Confirm Cancellation</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Are you sure you want to cancel your participation in this competition? This action cannot be undone.
                        </div>
                        
                        <form action="cancel-participation.php?id=<?php echo $competition_id; ?>" method="POST">
                            <input type="hidden" name="confirm" value="yes">
                            
                            <div class="form-group" style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-danger">Yes, Cancel My Participation</button>
                                <a href="view-competition.php?id=<?php echo $competition_id; ?>" class="btn btn-secondary">No, Go Back</a>
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