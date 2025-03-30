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
$competition_query = "SELECT c.* FROM competitions c WHERE c.id = ?";
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

// Check if organizer is trying to approve a participant for someone else's competition
if ($role === 'organizer' && $competition['organizer_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to modify this competition";
    header('Location: organizer-competitions.php');
    exit;
}

// Check if the participant exists
$participant_query = "SELECT * FROM competition_participants WHERE competition_id = ? AND student_id = ?";
$stmt = $conn->prepare($participant_query);
$stmt->bind_param("ii", $competition_id, $student_id);
$stmt->execute();
$participant_result = $stmt->get_result();

if ($participant_result->num_rows === 0) {
    $_SESSION['error'] = "Participant not found";
    header('Location: competition-participants.php?id=' . $competition_id);
    exit;
}

$participant = $participant_result->fetch_assoc();

// Get user data for loading page
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

// Get student data
$student_query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();

// Display loading page first, then process with AJAX
if (!isset($_GET['process']) || $_GET['process'] !== 'true'):
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approving Participant - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="animations.css">
    <style>
        .loading-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 200px);
            text-align: center;
            padding: 20px;
        }
        
        .loading-spinner {
            margin-bottom: 20px;
            width: 60px;
            height: 60px;
            border: 5px solid rgba(74, 108, 247, 0.2);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }
        
        .loading-text {
            font-size: 1.2rem;
            color: var(--text-primary);
            margin-bottom: 20px;
        }
        
        .loading-subtext {
            font-size: 0.9rem;
            color: var(--text-secondary);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .participant-info {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            background-color: var(--bg-secondary);
            padding: 15px;
            border-radius: 10px;
            animation: pulse 2s infinite;
        }
        
        .participant-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            background-color: var(--bg-tertiary);
        }
        
        .participant-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Main Content -->
        <div class="main-content" style="margin-left: 0; width: 100%;">
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <div class="loading-text">Approving participant...</div>
                
                <div class="participant-info">
                    <div class="participant-avatar">
                        <?php if (isset($student_data['profile_picture']) && $student_data['profile_picture'] !== 'default.jpg'): ?>
                            <img src="<?php echo htmlspecialchars($student_data['profile_picture']); ?>" alt="Profile Picture">
                        <?php else: ?>
                            <img src="assets/img/default-avatar.jpg" alt="Default Avatar">
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong><?php echo htmlspecialchars($student_data['name']); ?></strong>
                    </div>
                </div>
                
                <div class="loading-subtext">
                    We're approving this participant's registration for the competition.
                    Please wait a moment while we process your request.
                </div>
            </div>
        </div>
    </div>

    <script>
        // Redirect to process page after showing loading animation
        setTimeout(function() {
            window.location.href = "approve-participant.php?competition_id=<?php echo $competition_id; ?>&student_id=<?php echo $student_id; ?>&process=true";
        }, 1500);
    </script>
</body>
</html>
<?php
    exit; // Stop execution after showing the loading page
endif;

// Process the approval
$update_query = "UPDATE competition_participants SET status = 'approved' WHERE competition_id = ? AND student_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ii", $competition_id, $student_id);
$result = $stmt->execute();

if ($result) {
    // Get student and competition details for notification
    $student_query = "SELECT name, email FROM students WHERE id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_result = $stmt->get_result();
    $student = $student_result->fetch_assoc();
    
    $competition_title = $competition['title'];
    
    // Create notification for student
    $notification_message = "Your participation in \"" . $competition_title . "\" has been approved.";
    $notification_query = "INSERT INTO notifications (user_id, user_role, message, link, created_at) 
                          VALUES (?, 'student', ?, ?, NOW())";
    $notification_link = "view-competition.php?id=" . $competition_id;
    
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("iss", $student_id, $notification_message, $notification_link);
    $stmt->execute();
    
    // Log admin action if admin is approving
    if ($role === 'admin') {
        $action = "Approved participant (Student ID: $student_id) for competition: " . $competition_title;
        $log_query = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
    }
    
    $_SESSION['success'] = "Participant has been approved successfully";
} else {
    $_SESSION['error'] = "Failed to approve participant";
}

// Redirect back to participants page
header('Location: competition-participants.php?id=' . $competition_id);
exit;
?> 