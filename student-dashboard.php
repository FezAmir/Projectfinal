<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

// Fetch student data
$student_id = $_SESSION['user_id'];
$student_query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student_data = $student_result->fetch_assoc();

// Fetch student's participations
$participations_query = "SELECT cp.*, c.title, c.start_date, c.end_date, o.username as organizer_name 
                         FROM competition_participants cp 
                         JOIN competitions c ON cp.competition_id = c.id 
                         JOIN organizers o ON c.organizer_id = o.id 
                         WHERE cp.student_id = ? 
                         ORDER BY cp.created_at DESC";
$stmt = $conn->prepare($participations_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$participations_result = $stmt->get_result();

// Count statistics
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM competition_participants WHERE student_id = ?) as total_participations,
                (SELECT COUNT(*) FROM competition_participants WHERE student_id = ? AND status = 'approved') as approved_participations,
                (SELECT COUNT(*) FROM competition_participants WHERE student_id = ? AND status = 'pending') as pending_participations,
                (SELECT COUNT(*) FROM competition_participants cp 
                 JOIN competitions c ON cp.competition_id = c.id 
                 WHERE cp.student_id = ? AND cp.status = 'approved' AND c.end_date < CURDATE()) as completed_competitions";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iiii", $student_id, $student_id, $student_id, $student_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Fetch recommended competitions (not participating in yet)
$recommended_query = "SELECT c.*, o.username as organizer_name, 
                     (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id AND status = 'approved') as participant_count
                     FROM competitions c 
                     JOIN organizers o ON c.organizer_id = o.id 
                     WHERE c.status = 'approved' 
                     AND c.end_date >= CURDATE() 
                     AND c.id NOT IN (SELECT competition_id FROM competition_participants WHERE student_id = ?)
                     ORDER BY c.created_at DESC 
                     LIMIT 5";
$stmt = $conn->prepare($recommended_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$recommended_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Dashboard Specific Styles */
        .dashboard-action-btn {
            margin-left: auto;
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
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .view-btn {
            background-color: rgba(54, 185, 204, 0.1);
            color: var(--info-color);
            border: 1px solid rgba(54, 185, 204, 0.2);
        }
        
        .view-btn:hover {
            background-color: var(--info-color);
            color: white;
        }
        
        .join-btn {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(28, 200, 138, 0.2);
        }
        
        .join-btn:hover {
            background-color: var(--success-color);
            color: white;
        }
        
        .cancel-btn {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 74, 59, 0.2);
        }
        
        .cancel-btn:hover {
            background-color: var(--danger-color);
            color: white;
        }
        
        .sidebar-toggle {
            display: none;
        }
        
        @media (max-width: 992px) {
            .sidebar-toggle {
                display: flex;
            }
        }
        
        .competition-card {
            display: flex;
            flex-direction: column;
            background-color: var(--bg-secondary);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .competition-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .competition-card-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            padding: 15px;
            position: relative;
        }
        
        .competition-card-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .competition-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .competition-meta {
            margin: 10px 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        
        .competition-meta p {
            margin: 5px 0;
            display: flex;
            align-items: center;
        }
        
        .competition-meta i {
            width: 20px;
            margin-right: 8px;
        }
        
        .competition-actions {
            margin-top: auto;
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
                <a href="student-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="student-competitions.php"><i class="fas fa-trophy"></i> My Competitions</a>
                <a href="browse-competitions.php"><i class="fas fa-search"></i> Browse Competitions</a>
                <a href="student-achievements.php"><i class="fas fa-medal"></i> My Achievements</a>
                <a href="student-settings.php"><i class="fas fa-cog"></i> Settings</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Student Dashboard</li>
                </ul>
                <h1>Student Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($student_data['username']); ?>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-card-primary">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_participations']; ?></h3>
                        <p>Total Participations</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
                <div class="stat-card stat-card-warning">
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_participations']; ?></h3>
                        <p>Pending Approvals</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-card stat-card-success">
                    <div class="stat-info">
                        <h3><?php echo $stats['approved_participations']; ?></h3>
                        <p>Active Competitions</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-card stat-card-info">
                    <div class="stat-info">
                        <h3><?php echo $stats['completed_competitions']; ?></h3>
                        <p>Completed Competitions</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                </div>
            </div>

            <!-- My Participations -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h2>My Participations</h2>
                        <a href="browse-competitions.php" class="btn btn-primary dashboard-action-btn">Browse More</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Competition</th>
                                    <th>Organizer</th>
                                    <th>Dates</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($participations_result && $participations_result->num_rows > 0): ?>
                                    <?php while ($participation = $participations_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($participation['title']); ?></td>
                                            <td><?php echo htmlspecialchars($participation['organizer_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($participation['start_date'])) . ' - ' . date('M d, Y', strtotime($participation['end_date'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($participation['status']); ?>">
                                                    <?php echo ucfirst($participation['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-competition.php?id=<?php echo $participation['competition_id']; ?>" class="action-btn view-btn"><i class="fas fa-eye"></i> View</a>
                                                    <?php if ($participation['status'] == 'pending'): ?>
                                                        <a href="cancel-participation.php?id=<?php echo $participation['id']; ?>" class="action-btn cancel-btn" onclick="return confirm('Are you sure you want to cancel your participation?');"><i class="fas fa-times"></i> Cancel</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">You haven't participated in any competitions yet. <a href="browse-competitions.php">Browse competitions</a> to get started!</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recommended Competitions -->
            <div class="card">
                <div class="card-header">
                    <h2>Recommended for You</h2>
                </div>
                <div class="card-body">
                    <?php if ($recommended_result && $recommended_result->num_rows > 0): ?>
                        <div class="competition-grid">
                            <?php while ($competition = $recommended_result->fetch_assoc()): ?>
                                <div class="competition-card">
                                    <div class="competition-card-header">
                                        <h3><?php echo htmlspecialchars($competition['title']); ?></h3>
                                    </div>
                                    <div class="competition-card-body">
                                        <p><?php echo htmlspecialchars(substr($competition['description'], 0, 100)) . '...'; ?></p>
                                        <div class="competition-meta">
                                            <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($competition['organizer_name']); ?></p>
                                            <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($competition['start_date'])); ?> - <?php echo date('M d, Y', strtotime($competition['end_date'])); ?></p>
                                            <p><i class="fas fa-users"></i> <?php echo $competition['participant_count']; ?> participants</p>
                                        </div>
                                        <div class="competition-actions">
                                            <a href="view-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn view-btn"><i class="fas fa-eye"></i> View Details</a>
                                            <a href="join-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn join-btn"><i class="fas fa-plus"></i> Join Competition</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center;">No recommended competitions available at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar Toggle for Mobile -->
    <div class="sidebar-toggle" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </div>

    <!-- Theme Toggle -->
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </div>

    <script>
        // Theme Toggle
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

        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    </script>
</body>
</html> 