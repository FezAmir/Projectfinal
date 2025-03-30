<?php
require_once 'config.php';
require_once 'db.php';

// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['error'] = "Please log in to continue";
    header("Location: login.php");
    exit;
}

// Check if user is an organizer
if ($_SESSION['role'] !== 'organizer') {
    $_SESSION['error'] = "Access denied. Only organizers can view this page";
    header("Location: index.php");
    exit;
}

// Check if competition ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid competition ID";
    header("Location: organizer-competitions.php");
    exit;
}

$competition_id = $_GET['id'];
$organizer_id = $_SESSION['user_id'];

// Check if the competition belongs to this organizer
$competition_query = "SELECT c.*, cat.name as category_name 
                     FROM competitions c
                     LEFT JOIN categories cat ON c.category_id = cat.id
                     WHERE c.id = ? AND c.organizer_id = ?";
$stmt = $conn->prepare($competition_query);
$stmt->bind_param("ii", $competition_id, $organizer_id);
$stmt->execute();
$competition_result = $stmt->get_result();

if ($competition_result->num_rows === 0) {
    $_SESSION['error'] = "Competition not found or you don't have access to this competition";
    header("Location: organizer-competitions.php");
    exit;
}

$competition = $competition_result->fetch_assoc();

// Get participants
$participants_query = "SELECT cp.*, s.username as student_name, s.email as student_email, s.profile_picture
                     FROM competition_participants cp
                     JOIN students s ON cp.student_id = s.id
                     JOIN competitions c ON cp.competition_id = c.id
                     WHERE c.organizer_id = ?";
$stmt = $conn->prepare($participants_query);
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$participants_result = $stmt->get_result();

// Get participation stats
$stats_query = "SELECT COUNT(*) as total, 
               SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered,
               SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
               SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
               FROM competition_participants 
               WHERE competition_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Handle status filter
$status_filter = $_GET['status'] ?? 'all';
$filter_sql = "";
if ($status_filter !== 'all') {
    $filter_sql = " AND cp.status = '" . $conn->real_escape_string($status_filter) . "'";
}

// Get filtered participants
$filtered_query = "SELECT cp.*, s.username as student_name, s.email as student_email, s.profile_picture
                  FROM competition_participants cp
                  JOIN students s ON cp.student_id = s.id
                  WHERE cp.competition_id = ?" . $filter_sql . "
                  ORDER BY cp.registered_at DESC";
$stmt = $conn->prepare($filtered_query);
$stmt->bind_param("i", $competition_id);
$stmt->execute();
$filtered_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants - <?php echo htmlspecialchars($competition['title']); ?> - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .participant-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            background-color: var(--bg-primary);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .participant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .participant-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .participant-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .participant-info {
            flex: 1;
        }
        
        .participant-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 3px;
        }
        
        .participant-email {
            color: var(--text-secondary);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .participant-date {
            font-size: 0.85rem;
            color: var(--text-tertiary);
        }
        
        .participant-actions {
            display: flex;
            align-items: center;
        }
        
        .participant-status {
            margin-right: 15px;
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-registered {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }
        
        .status-completed {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }
        
        .status-cancelled {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .filter-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .filter-pills {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-pill {
            padding: 8px 15px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: background-color 0.3s, color 0.3s;
            background-color: var(--bg-secondary);
            color: var(--text-secondary);
        }
        
        .filter-pill:hover {
            background-color: var(--bg-tertiary);
        }
        
        .filter-pill.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            background-color: var(--bg-secondary);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: var(--bg-secondary);
            border-radius: 10px;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--text-secondary);
        }
        
        @media (max-width: 768px) {
            .participant-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .participant-avatar {
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .participant-actions {
                margin-top: 15px;
                width: 100%;
                justify-content: space-between;
            }
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
    
    <div class="container my-5">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users"></i> Participants</h1>
            
            <a href="view-competition.php?id=<?php echo $competition_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Competition
            </a>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($competition['title']); ?></h5>
                <p class="text-muted">
                    <i class="fas fa-calendar-alt me-2"></i> <?php echo date('M j, Y', strtotime($competition['start_date'])); ?> - <?php echo date('M j, Y', strtotime($competition['end_date'])); ?>
                </p>
                <p class="text-muted">
                    <i class="fas fa-tag me-2"></i> <?php echo htmlspecialchars($competition['category_name'] ?? 'Uncategorized'); ?>
                </p>
            </div>
        </div>
        
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Participants</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['registered']; ?></div>
                <div class="stat-label">Registered</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['cancelled']; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        
        <div class="filter-section">
            <div class="filter-pills">
                <a href="?id=<?php echo $competition_id; ?>" class="filter-pill <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?id=<?php echo $competition_id; ?>&status=registered" class="filter-pill <?php echo $status_filter === 'registered' ? 'active' : ''; ?>">Registered</a>
                <a href="?id=<?php echo $competition_id; ?>&status=completed" class="filter-pill <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                <a href="?id=<?php echo $competition_id; ?>&status=cancelled" class="filter-pill <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>
            
            <?php if ($filtered_result->num_rows > 0): ?>
                <a href="approve-all-participants.php?id=<?php echo $competition_id; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-check-circle"></i> Approve All
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($filtered_result->num_rows > 0): ?>
            <?php while ($participant = $filtered_result->fetch_assoc()): ?>
                <div class="participant-card">
                    <div class="participant-avatar">
                        <img src="uploads/<?php echo htmlspecialchars($participant['profile_picture'] ?? 'default.jpg'); ?>" alt="Profile Picture">
                    </div>
                    
                    <div class="participant-info">
                        <div class="participant-name"><?php echo htmlspecialchars($participant['student_name']); ?></div>
                        <div class="participant-email"><?php echo htmlspecialchars($participant['student_email']); ?></div>
                        <div class="participant-date">
                            <i class="fas fa-clock me-1"></i> Registered: <?php echo date('M j, Y, g:i a', strtotime($participant['registered_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="participant-actions">
                        <span class="participant-status status-<?php echo $participant['status']; ?>">
                            <?php echo ucfirst($participant['status']); ?>
                        </span>
                        
                        <div class="btn-group">
                            <a href="approve-participant.php?id=<?php echo $participant['id']; ?>&competition_id=<?php echo $competition_id; ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-check"></i> Approve
                            </a>
                            
                            <a href="reject-participant.php?id=<?php echo $participant['id']; ?>&competition_id=<?php echo $competition_id; ?>" class="btn btn-danger btn-sm">
                                <i class="fas fa-times"></i> Reject
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>No Participants Found</h3>
                <p class="text-muted">
                    <?php if ($status_filter !== 'all'): ?>
                        No participants with status "<?php echo ucfirst($status_filter); ?>". Try a different filter.
                    <?php else: ?>
                        There are no participants registered for this competition yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
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
</body>
</html> 