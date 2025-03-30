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

$user_id = $_SESSION['user_id'];

// Get organizer details
$organizer_query = "SELECT * FROM organizers WHERE id = ?";
$stmt = $conn->prepare($organizer_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$organizer_result = $stmt->get_result();
$organizer = $organizer_result->fetch_assoc();

// Get competitions created by this organizer
$competition_query = "SELECT c.*, cat.name as category_name, 
                     (SELECT COUNT(*) FROM competition_participants WHERE competition_id = c.id) as participants_count
                     FROM competitions c
                     LEFT JOIN categories cat ON c.category_id = cat.id
                     WHERE c.organizer_id = ?
                     ORDER BY c.created_at DESC";
$stmt = $conn->prepare($competition_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$competitions_result = $stmt->get_result();

// Get competition statistics
$stats_query = "SELECT 
                COUNT(*) as total_competitions,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_competitions,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_competitions,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_competitions
                FROM competitions 
                WHERE organizer_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get total participants across all competitions
$participants_query = "SELECT COUNT(*) as total_participants
                      FROM competition_participants cp
                      JOIN competitions c ON cp.competition_id = c.id
                      WHERE c.organizer_id = ?";
$stmt = $conn->prepare($participants_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$participants_result = $stmt->get_result();
$participants = $participants_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Competitions - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .competitions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .competition-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .competition-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-img-container {
            height: 160px;
            overflow: hidden;
            position: relative;
        }
        
        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .card-img-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(0deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 50%);
            display: flex;
            align-items: flex-end;
            padding: 15px;
            color: white;
        }
        
        .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .card-text {
            margin-bottom: 15px;
            color: var(--text-secondary);
            flex-grow: 1;
        }
        
        .card-footer {
            padding: 10px 15px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--bg-secondary);
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-pending {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }
        
        .badge-approved {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }
        
        .badge-rejected {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            background-color: var(--bg-secondary);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background-color: var(--bg-secondary);
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .empty-icon {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--text-secondary);
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
            <h1><i class="fas fa-trophy"></i> My Competitions</h1>
            <a href="create-competition.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Competition
            </a>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_competitions'] ?? 0; ?></div>
                <div class="stat-label">Total Competitions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['approved_competitions'] ?? 0; ?></div>
                <div class="stat-label">Approved Competitions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending_competitions'] ?? 0; ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $participants['total_participants'] ?? 0; ?></div>
                <div class="stat-label">Total Participants</div>
            </div>
        </div>
        
        <?php if ($competitions_result->num_rows > 0): ?>
            <div class="competitions-grid">
                <?php while ($competition = $competitions_result->fetch_assoc()): ?>
                    <div class="card competition-card">
                        <div class="card-img-container">
                            <img src="images/competition-bg.jpg" alt="Competition Background">
                            <div class="card-img-overlay">
                                <span class="badge <?php echo 'badge-' . $competition['status']; ?>">
                                    <?php echo ucfirst($competition['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($competition['title']); ?></h5>
                            
                            <div class="card-text">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    <span><?php echo date('M j, Y', strtotime($competition['start_date'])); ?> - <?php echo date('M j, Y', strtotime($competition['end_date'])); ?></span>
                                </div>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-tag me-2"></i>
                                    <span><?php echo htmlspecialchars($competition['category_name'] ?? 'Uncategorized'); ?></span>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users me-2"></i>
                                    <span><?php echo $competition['participants_count']; ?> Participants</span>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-auto">
                                <a href="view-competition.php?id=<?php echo $competition['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                
                                <div>
                                    <a href="edit-competition.php?id=<?php echo $competition['id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    
                                    <a href="organizer-participants.php?id=<?php echo $competition['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-users"></i> Participants
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-footer text-muted">
                            <small>Created <?php echo date('M j, Y', strtotime($competition['created_at'])); ?></small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3>No Competitions Yet</h3>
                <p class="text-muted">You haven't created any competitions yet. Get started by creating your first competition!</p>
                <a href="create-competition.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus"></i> Create Your First Competition
                </a>
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