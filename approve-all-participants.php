<?php
session_start();
require_once 'config.php';

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

// Check if organizer is trying to approve participants for someone else's competition
if ($role === 'organizer' && $competition['organizer_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to modify this competition";
    header('Location: organizer-competitions.php');
    exit;
}

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

// Get all pending participants
$participants_query = "SELECT cp.student_id, s.username as name, s.email, s.profile_picture
                       FROM competition_participants cp
                       JOIN students s ON cp.student_id = s.id
                       WHERE cp.competition_id = ? AND cp.status = 'pending'";
$stmt = $conn->prepare($participants_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$participants_result = $stmt->get_result();

if ($participants_result->num_rows === 0) {
    $_SESSION['info'] = "No pending participants found";
    header('Location: competition-participants.php?id=' . $competition_id);
    exit;
}

// If not processing yet, show the loading screen
if (!isset($_GET['process']) || $_GET['process'] !== 'true'):
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approving All Participants - EasyComp</title>
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
        
        .loading-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .loading-progress {
            width: 300px;
            height: 8px;
            background-color: var(--bg-secondary);
            border-radius: 4px;
            margin: 20px 0;
            overflow: hidden;
            position: relative;
        }
        
        .progress-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border-radius: 4px;
            transition: width 2s ease;
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
        
        .participant-avatars {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin: 20px 0;
            max-width: 500px;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 5px;
            background-color: var(--bg-tertiary);
            overflow: hidden;
            opacity: 0.5;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        
        .avatar.approved {
            opacity: 1;
            transform: scale(1.1);
            box-shadow: 0 0 0 2px var(--success-color);
        }
        
        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .count-info {
            font-size: 1rem;
            color: var(--text-primary);
            margin-top: 20px;
        }
        
        .count-number {
            font-weight: 700;
            color: var(--primary-color);
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
                <h1 class="loading-title">Bulk Approval</h1>
                <div class="loading-text">Approving all pending participants...</div>
                
                <div class="loading-progress">
                    <div class="progress-bar" id="progressBar"></div>
                </div>
                
                <div class="participant-avatars" id="avatarContainer">
                    <?php while ($participant = $participants_result->fetch_assoc()): ?>
                        <div class="avatar" data-id="<?php echo $participant['student_id']; ?>">
                            <div class="student-avatar">
                                <?php if (isset($participant['profile_picture']) && $participant['profile_picture'] !== 'default.jpg'): ?>
                                    <img src="<?php echo htmlspecialchars($participant['profile_picture']); ?>" alt="Student">
                                <?php else: ?>
                                    <img src="assets/img/default-avatar.jpg" alt="Default Avatar">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="count-info">
                    Approving <span class="count-number" id="approvedCount">0</span> of <span class="count-number"><?php echo $participants_result->num_rows; ?></span> participants
                </div>
                
                <div class="loading-subtext">
                    Please wait while we approve all pending participants for "<?php echo htmlspecialchars($competition['title']); ?>".
                    This may take a moment depending on the number of participants.
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const totalParticipants = <?php echo $participants_result->num_rows; ?>;
            const progressBar = document.getElementById('progressBar');
            const approvedCount = document.getElementById('approvedCount');
            const avatars = document.querySelectorAll('.avatar');
            
            // Animate progress bar
            setTimeout(() => {
                progressBar.style.width = '100%';
            }, 100);
            
            // Animate avatar approvals one by one
            let count = 0;
            const intervalId = setInterval(() => {
                if (count >= avatars.length) {
                    clearInterval(intervalId);
                    // Redirect to processing page after animation completes
                    setTimeout(() => {
                        window.location.href = "approve-all-participants.php?id=<?php echo $competition_id; ?>&process=true";
                    }, 500);
                    return;
                }
                
                avatars[count].classList.add('approved');
                count++;
                approvedCount.textContent = count;
            }, 200);
        });
    </script>
</body>
</html>
<?php
    exit; // Stop execution after showing the loading page
endif;

// Start transaction
$conn->begin_transaction();

try {
    // Update all pending participants to approved
    $update_query = "UPDATE competition_participants SET status = 'approved' WHERE competition_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $competition_id);
    $stmt->execute();
    
    $affected_rows = $stmt->affected_rows;
    
    // Prepare for notifications
    $competition_title = $competition['title'];
    $notification_link = "view-competition.php?id=" . $competition_id;
    $notification_message = "Your participation in \"" . $competition_title . "\" has been approved.";
    
    // Create notifications for all approved students
    $notification_query = "INSERT INTO notifications (user_id, user_role, message, link, created_at) VALUES (?, 'student', ?, ?, NOW())";
    $notification_stmt = $conn->prepare($notification_query);
    
    // Reset result pointer
    $participants_result->data_seek(0);
    
    // Add notifications for each student
    while ($participant = $participants_result->fetch_assoc()) {
        $student_id = $participant['student_id'];
        $notification_stmt->bind_param("iss", $student_id, $notification_message, $notification_link);
        $notification_stmt->execute();
    }
    
    // Log admin action if admin is approving
    if ($role === 'admin') {
        $action = "Bulk approved $affected_rows participants for competition: " . $competition_title;
        $log_query = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Successfully approved $affected_rows participants";
} catch (Exception $e) {
    // Rollback in case of error
    $conn->rollback();
    $_SESSION['error'] = "Failed to approve participants: " . $e->getMessage();
}

// Redirect back to participants page
header('Location: competition-participants.php?id=' . $competition_id);
exit;
?> 