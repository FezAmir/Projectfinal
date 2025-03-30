<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an organizer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    header('Location: login.php');
    exit;
}

// Fetch organizer data
$organizer_id = $_SESSION['user_id'];
$organizer_query = "SELECT * FROM organizers WHERE id = ?";
$stmt = $conn->prepare($organizer_query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$organizer_result = $stmt->get_result();
$organizer_data = $organizer_result->fetch_assoc();

// Fetch organizer's competitions
$competitions_query = "SELECT * FROM competitions WHERE organizer_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($competitions_query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$competitions_result = $stmt->get_result();

// Count statistics
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM competitions WHERE organizer_id = ?) as total_competitions,
                (SELECT COUNT(*) FROM competitions WHERE organizer_id = ? AND status = 'pending') as pending_competitions,
                (SELECT COUNT(*) FROM competitions WHERE organizer_id = ? AND status = 'approved') as approved_competitions,
                (SELECT COUNT(*) FROM competition_participants cp 
                 JOIN competitions c ON cp.competition_id = c.id 
                 WHERE c.organizer_id = ? AND cp.status = 'approved') as total_participants";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iiii", $organizer_id, $organizer_id, $organizer_id, $organizer_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer Dashboard - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Dashboard Specific Styles */
        .dashboard-action-btn {
            margin-left: auto;
        }
        
        .table-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
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
        
        .edit-btn {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(246, 194, 62, 0.2);
        }
        
        .edit-btn:hover {
            background-color: var(--warning-color);
            color: white;
        }
        
        .delete-btn {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 74, 59, 0.2);
        }
        
        .delete-btn:hover {
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
                <?php if (isset($organizer_data['profile_picture']) && $organizer_data['profile_picture'] !== 'default.jpg'): ?>
                    <img src="<?php echo htmlspecialchars($organizer_data['profile_picture']); ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="assets/img/default-avatar.jpg" alt="Default Avatar">
                <?php endif; ?>
                <span class="user-name"><?php echo htmlspecialchars($organizer_data['username']); ?></span>
                <div class="user-dropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                    <a href="organizer-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
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
                <h3>Organizer Dashboard</h3>
            </div>
            <div class="sidebar-menu">
                <a href="organizer-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="organizer-competitions.php"><i class="fas fa-trophy"></i> My Competitions</a>
                <a href="create-competition.php"><i class="fas fa-plus-circle"></i> Create Competition</a>
                <a href="organizer-participants.php"><i class="fas fa-users"></i> Participants</a>
                <a href="organizer-analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
                <a href="organizer-settings.php"><i class="fas fa-cog"></i> Settings</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="dashboard-header">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Organizer Dashboard</li>
                </ul>
                <h1>Organizer Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($organizer_data['name']); ?>!</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card stat-card-primary">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_competitions']; ?></h3>
                        <p>Total Competitions</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
                <div class="stat-card stat-card-warning">
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_competitions']; ?></h3>
                        <p>Pending Approval</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-card stat-card-success">
                    <div class="stat-info">
                        <h3><?php echo $stats['approved_competitions']; ?></h3>
                        <p>Active Competitions</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-card stat-card-info">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_participants']; ?></h3>
                        <p>Total Participants</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <!-- My Competitions -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h2>My Competitions</h2>
                        <a href="create-competition.php" class="btn btn-primary dashboard-action-btn">Create New</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Participants</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($competitions_result && $competitions_result->num_rows > 0): ?>
                                    <?php while ($competition = $competitions_result->fetch_assoc()): ?>
                                        <?php
                                        // Count participants for this competition
                                        $participant_query = "SELECT COUNT(*) as count FROM competition_participants WHERE competition_id = ? AND status = 'approved'";
                                        $stmt = $conn->prepare($participant_query);
                                        $stmt->bind_param("i", $competition['id']);
                                        $stmt->execute();
                                        $participant_result = $stmt->get_result();
                                        $participant_count = $participant_result->fetch_assoc()['count'];
                                        ?>
                                        <tr>
                                            <td><?php echo $competition['id']; ?></td>
                                            <td><?php echo htmlspecialchars($competition['title']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($competition['start_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($competition['end_date'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($competition['status']); ?>">
                                                    <?php echo ucfirst($competition['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $participant_count; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn view-btn"><i class="fas fa-eye"></i> View</a>
                                                    <?php if ($competition['status'] !== 'rejected'): ?>
                                                        <a href="edit-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                                    <?php endif; ?>
                                                    <a href="delete-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this competition?');"><i class="fas fa-trash"></i> Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">No competitions found. <a href="create-competition.php">Create your first competition!</a></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="create-competition.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <i class="fas fa-plus-circle"></i>
                            </div>
                            <div class="quick-action-text">
                                <h3>Create Competition</h3>
                                <p>Create a new competition for students to participate in</p>
                            </div>
                        </a>
                        <a href="organizer-participants.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="quick-action-text">
                                <h3>Manage Participants</h3>
                                <p>View and manage participants for all your competitions</p>
                            </div>
                        </a>
                        <a href="organizer-analytics.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="quick-action-text">
                                <h3>View Analytics</h3>
                                <p>See statistics and insights about your competitions</p>
                            </div>
                        </a>
                        <a href="profile.php" class="quick-action-card">
                            <div class="quick-action-icon">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <div class="quick-action-text">
                                <h3>Update Profile</h3>
                                <p>Update your organizer profile and settings</p>
                            </div>
                        </a>
                    </div>
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