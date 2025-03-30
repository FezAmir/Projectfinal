<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Fetch admin data
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin_data = $admin_result->fetch_assoc();

// Fetch pending competitions
$pending_query = "SELECT c.*, o.username as organizer_name 
                  FROM competitions c 
                  JOIN organizers o ON c.organizer_id = o.id 
                  WHERE c.status = 'pending' 
                  ORDER BY c.created_at DESC";
$pending_result = $conn->query($pending_query);

// Fetch recent competitions
$recent_query = "SELECT c.*, o.username as organizer_name 
                FROM competitions c 
                JOIN organizers o ON c.organizer_id = o.id 
                ORDER BY c.created_at DESC 
                LIMIT 5";
$recent_result = $conn->query($recent_query);

// Count total competitions, organizers, students
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM competitions) as total_competitions,
                (SELECT COUNT(*) FROM organizers) as total_organizers,
                (SELECT COUNT(*) FROM students) as total_students,
                (SELECT COUNT(*) FROM competitions WHERE status = 'pending') as pending_competitions";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Dashboard Specific Styles */
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        
        .page-description {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }
        
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
        
        .approve-btn {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(28, 200, 138, 0.2);
        }
        
        .approve-btn:hover {
            background-color: var(--success-color);
            color: white;
        }
        
        .reject-btn {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(231, 74, 59, 0.2);
        }
        
        .reject-btn:hover {
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
                <a href="admin-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin-competitions.php"><i class="fas fa-trophy"></i> Competitions</a>
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
                    <li class="breadcrumb-item active">Admin Dashboard</li>
                </ul>
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($admin_data['name']); ?>!</p>
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
                <div class="stat-card stat-card-success">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_organizers']; ?></h3>
                        <p>Organizers</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                </div>
                <div class="stat-card stat-card-info">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p>Students</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                <div class="stat-card stat-card-warning">
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_competitions']; ?></h3>
                        <p>Pending Approvals</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Approvals -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h2>Pending Approvals</h2>
                        <a href="admin-competitions.php?status=pending" class="btn btn-primary dashboard-action-btn">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Organizer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                                    <?php while ($competition = $pending_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $competition['id']; ?></td>
                                            <td><?php echo htmlspecialchars($competition['title']); ?></td>
                                            <td><?php echo htmlspecialchars($competition['organizer_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($competition['created_at'])); ?></td>
                                            <td><span class="status-badge status-pending">Pending</span></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn view-btn"><i class="fas fa-eye"></i> View</a>
                                                    <a href="approve-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn approve-btn"><i class="fas fa-check"></i> Approve</a>
                                                    <a href="reject-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn reject-btn"><i class="fas fa-times"></i> Reject</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">No pending competitions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Competitions -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h2>Recent Competitions</h2>
                        <a href="admin-competitions.php" class="btn btn-primary dashboard-action-btn">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Organizer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                                    <?php while ($competition = $recent_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $competition['id']; ?></td>
                                            <td><?php echo htmlspecialchars($competition['title']); ?></td>
                                            <td><?php echo htmlspecialchars($competition['organizer_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($competition['created_at'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($competition['status']); ?>">
                                                    <?php echo ucfirst($competition['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn view-btn"><i class="fas fa-eye"></i> View</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">No competitions found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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