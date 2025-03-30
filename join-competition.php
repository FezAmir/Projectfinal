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

// Check if competition exists and is approved
$competition_query = "SELECT c.*, t.username as creator FROM competitions c 
                      JOIN teachers t ON c.created_by = t.id
                      WHERE c.id = ?";
$stmt = $conn->prepare($competition_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$competition_result = $stmt->get_result();

if ($competition_result->num_rows === 0) {
    $_SESSION['error'] = "Competition not found or not available for registration";
    header('Location: student-dashboard.php');
    exit;
}

$competition = $competition_result->fetch_assoc();

// Check if already registered
$check_query = "SELECT * FROM competition_participants WHERE competition_id = ? AND student_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $competition_id, $student_id);
$stmt->execute();
$check_result = $stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['warning'] = "You are already registered for this competition";
    header('Location: view-competition.php?id=' . $competition_id);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // If competition requires manual approval
    $status = $competition['auto_approve'] ? 'approved' : 'pending';
    
    // Insert participation
    $insert_query = "INSERT INTO competition_participants 
                    (competition_id, student_id, status, notes, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiss", $competition_id, $student_id, $status, $notes);
    
    if ($stmt->execute()) {
        // Add notification for organizer if pending approval
        if ($status === 'pending') {
            $notification_query = "INSERT INTO notifications 
                                  (user_id, user_type, message, related_id, related_type, created_at) 
                                  VALUES (?, 'organizer', ?, ?, 'participation', NOW())";
            $notification_message = "New student participation request for competition: " . $competition['title'];
            $stmt = $conn->prepare($notification_query);
            $stmt->bind_param("isi", $competition['organizer_id'], $notification_message, $competition_id);
            $stmt->execute();
            
            $_SESSION['success'] = "Your participation request has been submitted and is awaiting approval";
        } else {
            $_SESSION['success'] = "You have successfully joined the competition";
        }
        
        header('Location: view-competition.php?id=' . $competition_id);
        exit;
    } else {
        $_SESSION['error'] = "Failed to join competition: " . $conn->error;
    }
}

// Fetch student data
$student_query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();

$participants_query = "SELECT cp.*, s.username as name, s.email, s.profile_picture 
                      FROM competition_participants cp
                      JOIN students s ON cp.student_id = s.id
                      WHERE cp.competition_id = ?
                      ORDER BY cp.created_at DESC";

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Competition - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .join-container {
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
                <a href="student-competitions.php" class="active"><i class="fas fa-trophy"></i> Competitions</a>
                <a href="student-participations.php"><i class="fas fa-flag-checkered"></i> My Participations</a>
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
                    <li class="breadcrumb-item"><a href="view-competition.php?id=<?php echo $competition_id; ?>"><?php echo htmlspecialchars($competition['title']); ?></a></li>
                    <li class="breadcrumb-item active">Join Competition</li>
                </ul>
                <h1>Join Competition</h1>
            </div>
            
            <div class="join-container">
                <!-- Competition Summary -->
                <div class="competition-summary">
                    <h2><?php echo htmlspecialchars($competition['title']); ?></h2>
                    <div class="competition-meta">
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo date('M d, Y', strtotime($competition['start_date'])); ?> - <?php echo date('M d, Y', strtotime($competition['end_date'])); ?></span>
                        </div>
                        <?php if (isset($competition['location']) && !empty($competition['location'])): ?>
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($competition['location']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Join Form -->
                <div class="card">
                    <div class="card-header">
                        <h2>Registration Form</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="join-competition.php?id=<?php echo $competition_id; ?>" method="POST">
                            <div class="form-group">
                                <label for="notes">Additional Notes (Optional)</label>
                                <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="Any additional information you'd like to share with the organizer"></textarea>
                                <p class="form-text">This information will be shared with the competition organizer.</p>
                            </div>
                            
                            <?php if (!$competition['auto_approve']): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Note: Your participation will require approval from the organizer.
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Submit Registration</button>
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