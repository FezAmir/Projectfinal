<?php
session_start();
require_once 'config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : 0;

// Fetch admin data
$admin_id = $_SESSION['user_id'];
$admin_query = "SELECT * FROM admins WHERE id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin_data = $admin_result->fetch_assoc();

// Fetch competitions
$sql = "SELECT c.*, t.username as creator_name, t.email as creator_email, t.profile_picture as creator_profile_pic 
        FROM competitions c
        LEFT JOIN teachers t ON c.created_by = t.id
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);

// Fetch categories for filter dropdown
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Competitions - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-item {
            flex: 1;
            min-width: 200px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding-left: 35px;
        }
        
        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .status-menu {
            display: flex;
            background-color: var(--bg-secondary);
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .status-item {
            padding: 10px 15px;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-secondary);
            flex: 1;
            text-align: center;
            border-bottom: 2px solid transparent;
        }
        
        .status-item:hover {
            color: var(--text-primary);
            background-color: rgba(0, 0, 0, 0.03);
        }
        
        .status-item.active {
            color: var(--link-color);
            border-bottom-color: var(--link-color);
            background-color: rgba(58, 134, 255, 0.05);
        }
        
        .competition-count {
            font-size: 0.75rem;
            background-color: var(--bg-primary);
            color: var(--text-secondary);
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
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
                    <li class="breadcrumb-item active">Competitions</li>
                </ul>
                <h1>Manage Competitions</h1>
                <p>View, approve, and manage all competitions on the platform.</p>
            </div>

            <!-- Status Menu -->
            <div class="status-menu">
                <a href="admin-competitions.php" class="status-item <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                    All Competitions
                </a>
                <a href="admin-competitions.php?status=pending" class="status-item <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                    Pending
                    <?php 
                    $count_query = "SELECT COUNT(*) as count FROM competitions WHERE status = 'pending'";
                    $count_result = $conn->query($count_query);
                    $count = $count_result->fetch_assoc()['count'];
                    echo "<span class='competition-count'>$count</span>";
                    ?>
                </a>
                <a href="admin-competitions.php?status=approved" class="status-item <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                    Approved
                </a>
                <a href="admin-competitions.php?status=rejected" class="status-item <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                    Rejected
                </a>
            </div>

            <!-- Filters -->
            <div class="card">
                <div class="card-header">
                    <h2>Filters</h2>
                </div>
                <div class="card-body">
                    <form action="admin-competitions.php" method="GET">
                        <div class="filter-bar">
                            <div class="filter-item search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search competitions..." class="form-control" value="<?php echo htmlspecialchars($search_term); ?>">
                            </div>
                            <div class="filter-item">
                                <select name="category" class="form-control">
                                    <option value="0">All Categories</option>
                                    <?php while($category = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <?php if ($status_filter !== 'all'): ?>
                                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                            <?php endif; ?>
                            <div class="filter-item">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Apply Filters</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Competitions Table -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center">
                        <h2>Competitions List</h2>
                        <span style="margin-left: 10px; color: var(--text-secondary);">(<?php echo $result->num_rows; ?> competitions found)</span>
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
                                    <th>Category</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($competition = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $competition['id']; ?></td>
                                            <td><?php echo htmlspecialchars($competition['title']); ?></td>
                                            <td><?php echo htmlspecialchars($competition['creator_name']); ?></td>
                                            <td><?php echo htmlspecialchars($competition['category'] ?? 'Uncategorized'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($competition['start_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($competition['end_date'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($competition['status']); ?>">
                                                    <?php echo ucfirst($competition['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn view-btn"><i class="fas fa-eye"></i> View</a>
                                                    <?php if ($competition['status'] === 'pending'): ?>
                                                        <a href="approve-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn approve-btn"><i class="fas fa-check"></i> Approve</a>
                                                        <a href="reject-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn reject-btn"><i class="fas fa-times"></i> Reject</a>
                                                    <?php endif; ?>
                                                    <?php if ($competition['status'] === 'rejected'): ?>
                                                        <a href="approve-competition.php?id=<?php echo $competition['id']; ?>" class="action-btn approve-btn"><i class="fas fa-check"></i> Approve</a>
                                                    <?php endif; ?>
                                                    <a href="edit-competition.php?id=<?php echo $competition['id']; ?>&admin=true" class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</a>
                                                    <a href="delete-competition.php?id=<?php echo $competition['id']; ?>&admin=true" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this competition?');"><i class="fas fa-trash"></i> Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center;">No competitions found matching your criteria.</td>
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