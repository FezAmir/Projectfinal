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

// Fetch competition details
$competition_query = "SELECT c.*, o.username as organizer_name 
                     FROM competitions c 
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

// Check if organizer is trying to view someone else's competition
if ($role === 'organizer' && $competition['organizer_id'] != $user_id) {
    $_SESSION['error'] = "You don't have permission to view this competition";
    header('Location: organizer-competitions.php');
    exit;
}

// Set filter defaults
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare the base query
$participants_query = "SELECT cp.*, s.username as name, s.email, s.profile_picture
                      FROM competition_participants cp
                      JOIN students s ON cp.student_id = s.id
                      WHERE cp.competition_id = ?";

$params = [$competition_id];
$types = "i";

// Add status filter if specified
if ($status_filter !== 'all') {
    $participants_query .= " AND cp.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add search filter if specified
if (!empty($search_term)) {
    $search_param = "%" . $search_term . "%";
    $participants_query .= " AND (s.username LIKE ? OR s.email LIKE ?)";
    array_push($params, $search_param, $search_param);
    $types .= "ss";
}

// Add sorting
$participants_query .= " ORDER BY cp.created_at DESC";

$stmt = $conn->prepare($participants_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$participants_result = $stmt->get_result();

// Count participants by status
$count_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM competition_participants 
                WHERE competition_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$count_result = $stmt->get_result();
$counts = $count_result->fetch_assoc();

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
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants - <?php echo htmlspecialchars($competition['title']); ?> - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="animations.css">
    <style>
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
        
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-group label {
            font-weight: 500;
            color: var(--text-primary);
            white-space: nowrap;
        }
        
        .stats-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .stat-item {
            padding: 10px 15px;
            border-radius: 8px;
            background-color: var(--bg-secondary);
            flex: 1;
            min-width: 120px;
            text-align: center;
        }
        
        .stat-count {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
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
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .student-email {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .bulk-actions {
            margin-top: 20px;
            padding: 15px;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-form {
            flex-grow: 1;
            max-width: 300px;
        }
        
        .search-input {
            position: relative;
            display: flex;
        }
        
        .search-input input {
            padding-right: 30px;
        }
        
        .search-input button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
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
                    <li class="breadcrumb-item"><a href="view-competition.php?id=<?php echo $competition_id; ?>"><?php echo htmlspecialchars($competition['title']); ?></a></li>
                    <li class="breadcrumb-item active">Participants</li>
                </ul>
                <h1 class="animated-underline">Competition Participants</h1>
                <p class="fade-in"><?php echo htmlspecialchars($competition['title']); ?></p>
            </div>
            
            <!-- Statistics -->
            <div class="stats-container">
                <div class="stat-card delay-1">
                    <div class="stat-count"><?php echo $counts['total']; ?></div>
                    <div class="stat-label">Total Participants</div>
                    <i class="fas fa-users icon"></i>
                </div>
                <div class="stat-card delay-2">
                    <div class="stat-count" style="color: var(--success-color);"><?php echo $counts['approved']; ?></div>
                    <div class="stat-label">Approved</div>
                    <i class="fas fa-check-circle icon"></i>
                </div>
                <div class="stat-card delay-3">
                    <div class="stat-count" style="color: var(--warning-color);"><?php echo $counts['pending']; ?></div>
                    <div class="stat-label">Pending</div>
                    <i class="fas fa-clock icon"></i>
                </div>
                <div class="stat-card delay-4">
                    <div class="stat-count" style="color: var(--danger-color);"><?php echo $counts['rejected']; ?></div>
                    <div class="stat-label">Rejected</div>
                    <i class="fas fa-times-circle icon"></i>
                </div>
            </div>
            
            <!-- Filter and Search -->
            <div class="card slide-in-up">
                <div class="card-body">
                    <div class="filter-bar">
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select id="status" class="form-control" onchange="applyFilters()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="search-form">
                            <div class="search-input">
                                <input type="text" id="search" class="form-control" placeholder="Search participants..." value="<?php echo htmlspecialchars($search_term); ?>">
                                <button type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                    </div>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Bulk Actions for Pending Participants -->
                    <?php if ($counts['pending'] > 0): ?>
                        <div class="bulk-actions slide-in-up">
                            <div>
                                <strong><?php echo $counts['pending']; ?> pending requests</strong>
                            </div>
                            <div>
                                <button type="button" class="btn btn-success" onclick="if(confirm('Approve all pending participants?')) window.location.href='approve-all-participants.php?id=<?php echo $competition_id; ?>'">
                                    <i class="fas fa-check"></i> Approve All
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Participants Table -->
            <div class="card scale-in">
                <div class="card-header">
                    <h2>Participants List</h2>
                </div>
                <div class="card-body">
                    <?php if ($participants_result->num_rows === 0): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            No participants found. <?php echo !empty($search_term) ? 'Try a different search term or filter.' : ''; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Registration Date</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0; while ($participant = $participants_result->fetch_assoc()): $i++; ?>
                                        <tr class="animated slide-in-left delay-<?php echo min($i, 5); ?>">
                                            <td>
                                                <div class="student-info">
                                                    <div class="student-avatar">
                                                        <?php if (isset($participant['profile_picture']) && $participant['profile_picture'] !== 'default.jpg'): ?>
                                                            <img src="<?php echo htmlspecialchars($participant['profile_picture']); ?>" alt="Profile Picture">
                                                        <?php else: ?>
                                                            <img src="assets/img/default-avatar.jpg" alt="Default Avatar">
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="student-details">
                                                        <span class="student-name"><?php echo htmlspecialchars($participant['name']); ?></span>
                                                        <span class="student-email"><?php echo htmlspecialchars($participant['email']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($participant['created_at'])); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo $participant['status']; ?>">
                                                    <?php echo ucfirst($participant['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo !empty($participant['notes']) ? htmlspecialchars($participant['notes']) : '<em>No notes</em>'; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view-student.php?id=<?php echo $participant['student_id']; ?>" class="action-btn view-btn">
                                                        <i class="fas fa-user"></i> View
                                                    </a>
                                                    
                                                    <?php if ($participant['status'] === 'pending'): ?>
                                                        <a href="approve-participant.php?competition_id=<?php echo $competition_id; ?>&student_id=<?php echo $participant['student_id']; ?>" class="action-btn approve-btn">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                        <a href="reject-participant.php?competition_id=<?php echo $competition_id; ?>&student_id=<?php echo $participant['student_id']; ?>" class="action-btn reject-btn">
                                                            <i class="fas fa-times"></i> Reject
                                                        </a>
                                                    <?php elseif ($participant['status'] === 'approved'): ?>
                                                        <a href="reject-participant.php?competition_id=<?php echo $competition_id; ?>&student_id=<?php echo $participant['student_id']; ?>" class="action-btn reject-btn">
                                                            <i class="fas fa-times"></i> Reject
                                                        </a>
                                                    <?php elseif ($participant['status'] === 'rejected'): ?>
                                                        <a href="approve-participant.php?competition_id=<?php echo $competition_id; ?>&student_id=<?php echo $participant['student_id']; ?>" class="action-btn approve-btn">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme Toggle -->
    <div class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </div>

    <script src="app.js"></script>
    <script>
        // Apply filters
        function applyFilters() {
            const status = document.getElementById('status').value;
            const search = document.getElementById('search').value.trim();
            
            let url = `competition-participants.php?id=<?php echo $competition_id; ?>`;
            
            if (status !== 'all') {
                url += `&status=${status}`;
            }
            
            if (search) {
                url += `&search=${encodeURIComponent(search)}`;
            }
            
            window.location.href = url;
        }
        
        // Reset filters
        function resetFilters() {
            window.location.href = `competition-participants.php?id=<?php echo $competition_id; ?>`;
        }
        
        // Allow search on Enter key
        document.getElementById('search').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyFilters();
            }
        });
        
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