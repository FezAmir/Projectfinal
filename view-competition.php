<?php
require_once 'config.php';
require_once 'db.php';

// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if competition ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid competition ID";
    
    // Redirect based on user role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin-competitions.php');
            break;
        case 'organizer':
            header('Location: organizer-competitions.php');
            break;
        case 'student':
            header('Location: student-dashboard.php');
            break;
        default:
            header('Location: index.php');
            break;
    }
    exit;
}

$competition_id = $_GET['id'];

// Fetch competition details
$competition_query = "SELECT c.*, cat.name as category_name, o.username as organizer_name, o.email as organizer_email
                     FROM competitions c
                     JOIN categories cat ON c.category_id = cat.id
                     JOIN organizers o ON c.organizer_id = o.id
                     WHERE c.id = ?";
$stmt = $conn->prepare($competition_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$competition_result = $stmt->get_result();

if ($competition_result->num_rows === 0) {
    $_SESSION['error'] = "Competition not found";
    
    // Redirect based on user role
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: admin-competitions.php');
            break;
        case 'organizer':
            header('Location: organizer-competitions.php');
            break;
        case 'student':
            header('Location: student-dashboard.php');
            break;
        default:
            header('Location: index.php');
            break;
    }
    exit;
}

$competition = $competition_result->fetch_assoc();

// Check if organizer is trying to view someone else's competition
if ($_SESSION['role'] === 'organizer' && $competition['organizer_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = "You don't have permission to view this competition";
    header('Location: organizer-competitions.php');
    exit;
}

// Count participants
$participants_query = "SELECT COUNT(*) as total, 
                      SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                      SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
                      FROM competition_participants 
                      WHERE competition_id = ?";
$stmt = $conn->prepare($participants_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$participants_result = $stmt->get_result();
$participants_stats = $participants_result->fetch_assoc();

// Check if student is already registered
$is_registered = false;
$registration_status = '';

if ($_SESSION['role'] === 'student') {
    $check_query = "SELECT status FROM competition_participants 
                   WHERE competition_id = ? AND student_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $competition_id, $_SESSION['user_id']);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $is_registered = true;
        $registration_status = $check_result->fetch_assoc()['status'];
    }
}

// Get user data based on role
$user_data = null;
$user_query = "";

switch ($_SESSION['role']) {
    case 'admin':
        $user_query = "SELECT * FROM admins WHERE id = ?";
        break;
    case 'organizer':
        $user_query = "SELECT * FROM organizers WHERE id = ?";
        break;
    case 'student':
        $user_query = "SELECT * FROM students WHERE id = ?";
        break;
}

if (!empty($user_query)) {
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user_data = $user_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($competition['title']); ?> - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .competition-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .competition-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
        }
        
        .meta-item i {
            margin-right: 8px;
            opacity: 0.8;
        }
        
        .competition-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 992px) {
            .competition-content {
                grid-template-columns: 1fr;
            }
        }
        
        .competition-description {
            white-space: pre-line;
            line-height: 1.6;
        }
        
        .action-panel {
            position: sticky;
            top: 20px;
        }
        
        .status-large {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        
        .requirements-list {
            margin-top: 20px;
        }
        
        .requirements-list li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 25px;
        }
        
        .requirements-list li:before {
            content: '\f058';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            color: var(--success-color);
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
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
        
        .status-expired {
            background-color: rgba(108, 117, 125, 0.1);
            color: var(--text-secondary);
        }
        
        .action-btn {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .btn-view-participants {
            background-color: rgba(54, 185, 204, 0.1);
            color: var(--info-color);
            border: 1px solid rgba(54, 185, 204, 0.2);
        }
        
        .btn-view-participants:hover {
            background-color: var(--info-color);
            color: white;
        }
        
        .btn-approve {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(28, 200, 138, 0.2);
        }
        
        .btn-approve:hover {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-reject {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 74, 59, 0.2);
        }
        
        .btn-reject:hover {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-edit {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(246, 194, 62, 0.2);
        }
        
        .btn-edit:hover {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-join {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(28, 200, 138, 0.2);
        }
        
        .btn-join:hover {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-cancel {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 74, 59, 0.2);
        }
        
        .btn-cancel:hover {
            background-color: var(--danger-color);
            color: white;
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
                <?php elseif ($_SESSION['role'] === 'organizer'): ?>
                    <a href="organizer-competitions.php" class="active"><i class="fas fa-trophy"></i> My Competitions</a>
                    <a href="create-competition.php"><i class="fas fa-plus-circle"></i> Create Competition</a>
                    <a href="organizer-participants.php"><i class="fas fa-users"></i> Participants</a>
                    <a href="organizer-analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
                    <a href="organizer-settings.php"><i class="fas fa-cog"></i> Settings</a>
                <?php elseif ($_SESSION['role'] === 'student'): ?>
                    <a href="student-competitions.php" class="active"><i class="fas fa-trophy"></i> Competitions</a>
                    <a href="student-participations.php"><i class="fas fa-flag-checkered"></i> My Participations</a>
                    <a href="student-achievements.php"><i class="fas fa-medal"></i> Achievements</a>
                    <a href="student-settings.php"><i class="fas fa-cog"></i> Settings</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo $_SESSION['role']; ?>-dashboard.php">Dashboard</a></li>
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'organizer'): ?>
                        <li class="breadcrumb-item"><a href="<?php echo $_SESSION['role']; ?>-competitions.php">Competitions</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($competition['title']); ?></li>
                </ul>
            </div>
            
            <!-- Competition Header -->
            <div class="competition-header">
                <h1><?php echo htmlspecialchars($competition['title']); ?></h1>
                <div class="competition-meta">
                    <div class="meta-item">
                        <i class="fas fa-folder"></i>
                        <span><?php echo htmlspecialchars($competition['category_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($competition['organizer_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('M d, Y', strtotime($competition['start_date'])); ?> - <?php echo date('M d, Y', strtotime($competition['end_date'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $participants_stats['approved']; ?> participants</span>
                    </div>
                    <?php if (isset($competition['location']) && !empty($competition['location'])): ?>
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($competition['location']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Competition Content -->
            <div class="competition-content">
                <div class="main-details">
                    <div class="card">
                        <div class="card-header">
                            <h2>Competition Details</h2>
                        </div>
                        <div class="card-body">
                            <div class="competition-description">
                                <?php echo nl2br(htmlspecialchars($competition['description'])); ?>
                            </div>
                            
                            <?php if (!empty($competition['requirements'])): ?>
                                <h3>Requirements</h3>
                                <div class="requirements-list">
                                    <?php 
                                        $requirements = explode("\n", $competition['requirements']);
                                        echo '<ul>';
                                        foreach ($requirements as $requirement) {
                                            if (trim($requirement) !== '') {
                                                echo '<li>' . htmlspecialchars(trim($requirement)) . '</li>';
                                            }
                                        }
                                        echo '</ul>';
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($competition['prizes'])): ?>
                                <h3>Prizes</h3>
                                <div class="competition-description">
                                    <?php echo nl2br(htmlspecialchars($competition['prizes'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="side-details">
                    <div class="card action-panel">
                        <div class="card-body">
                            <?php
                                $today = new DateTime();
                                $end_date = new DateTime($competition['end_date']);
                                $has_expired = $today > $end_date;
                                
                                if ($has_expired) {
                                    echo '<div class="status-large status-expired">Ended</div>';
                                } else {
                                    $status_class = 'status-' . $competition['status'];
                                    echo '<div class="status-large ' . $status_class . '">' . ucfirst($competition['status']) . '</div>';
                                }
                            ?>
                            
                            <!-- Admin Actions -->
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <?php if ($competition['status'] === 'pending'): ?>
                                    <a href="approve-competition.php?id=<?php echo $competition_id; ?>" class="action-btn btn-approve"><i class="fas fa-check-circle"></i> Approve Competition</a>
                                    <a href="reject-competition.php?id=<?php echo $competition_id; ?>" class="action-btn btn-reject"><i class="fas fa-times-circle"></i> Reject Competition</a>
                                <?php elseif ($competition['status'] === 'approved' && !$has_expired): ?>
                                    <a href="reject-competition.php?id=<?php echo $competition_id; ?>" class="action-btn btn-reject"><i class="fas fa-times-circle"></i> Reject Competition</a>
                                <?php elseif ($competition['status'] === 'rejected'): ?>
                                    <a href="approve-competition.php?id=<?php echo $competition_id; ?>" class="action-btn btn-approve"><i class="fas fa-check-circle"></i> Approve Competition</a>
                                <?php endif; ?>
                                
                                <a href="competition-participants.php?id=<?php echo $competition_id; ?>" class="action-btn btn-view-participants"><i class="fas fa-users"></i> View Participants</a>
                                <a href="edit-competition.php?id=<?php echo $competition_id; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i> Edit Competition</a>
                                <button class="action-btn btn-reject" onclick="confirmDelete(<?php echo $competition_id; ?>)"><i class="fas fa-trash-alt"></i> Delete Competition</button>
                            
                            <!-- Organizer Actions -->
                            <?php elseif ($_SESSION['role'] === 'organizer'): ?>
                                <?php if (!$has_expired && $competition['status'] !== 'rejected'): ?>
                                    <a href="edit-competition.php?id=<?php echo $competition_id; ?>" class="action-btn btn-edit"><i class="fas fa-edit"></i> Edit Competition</a>
                                <?php endif; ?>
                                
                                <a href="competition-participants.php?id=<?php echo $competition_id; ?>" class="action-btn btn-view-participants"><i class="fas fa-users"></i> View Participants</a>
                                
                                <?php if ($competition['status'] === 'pending'): ?>
                                    <div class="alert alert-warning" style="margin-top: 15px;">
                                        <i class="fas fa-info-circle"></i> This competition is pending approval by an admin.
                                    </div>
                                <?php elseif ($competition['status'] === 'rejected'): ?>
                                    <div class="alert alert-danger" style="margin-top: 15px;">
                                        <i class="fas fa-exclamation-circle"></i> This competition has been rejected.
                                        <?php if (!empty($competition['rejection_reason'])): ?>
                                            <p style="margin-top: 10px;"><strong>Reason:</strong> <?php echo htmlspecialchars($competition['rejection_reason']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <button class="action-btn btn-reject" onclick="confirmDelete(<?php echo $competition_id; ?>)"><i class="fas fa-trash-alt"></i> Delete Competition</button>
                            
                            <!-- Student Actions -->
                            <?php elseif ($_SESSION['role'] === 'student'): ?>
                                <?php if ($competition['status'] === 'approved' && !$has_expired): ?>
                                    <?php if (!$is_registered): ?>
                                        <a href="join-competition.php?id=<?php echo $competition_id; ?>" class="action-btn btn-join"><i class="fas fa-sign-in-alt"></i> Join Competition</a>
                                    <?php elseif ($registration_status === 'pending'): ?>
                                        <div class="alert alert-warning" style="margin-bottom: 15px;">
                                            <i class="fas fa-clock"></i> Your participation request is pending approval.
                                        </div>
                                        <a href="cancel-participation.php?id=<?php echo $competition_id; ?>" class="action-btn btn-cancel"><i class="fas fa-times"></i> Cancel Participation</a>
                                    <?php elseif ($registration_status === 'approved'): ?>
                                        <div class="alert alert-success" style="margin-bottom: 15px;">
                                            <i class="fas fa-check-circle"></i> You are registered for this competition.
                                        </div>
                                        <a href="cancel-participation.php?id=<?php echo $competition_id; ?>" class="action-btn btn-cancel"><i class="fas fa-times"></i> Cancel Participation</a>
                                    <?php endif; ?>
                                <?php elseif ($has_expired): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> This competition has ended.
                                    </div>
                                <?php elseif ($competition['status'] === 'pending'): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-clock"></i> This competition is pending approval.
                                    </div>
                                <?php elseif ($competition['status'] === 'rejected'): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-times-circle"></i> This competition has been rejected.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <!-- Contact Organizer -->
                            <?php if ($_SESSION['role'] === 'student' || $_SESSION['role'] === 'admin'): ?>
                                <h3 style="margin-top: 20px;">Contact Organizer</h3>
                                <p>
                                    <i class="fas fa-envelope"></i> 
                                    <a href="mailto:<?php echo htmlspecialchars($competition['organizer_email']); ?>"><?php echo htmlspecialchars($competition['organizer_email']); ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
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
    <script>
        // Sidebar Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const themeToggle = document.getElementById('themeToggle');
            const html = document.documentElement;
            const icon = themeToggle.querySelector('i');
            
            // Theme Toggle
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
        
        // Confirm delete
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this competition? This action cannot be undone.')) {
                window.location.href = 'delete-competition.php?id=' + id;
            }
        }
    </script>
</body>
</html> 