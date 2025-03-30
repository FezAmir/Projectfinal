<?php
require_once 'config.php';
require_once 'db.php';

// Only start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Special error handling for database queries
$dbError = false;
$errorMessage = '';

try {
    // Test connection
    $conn->query("SELECT 1");
} catch (Exception $e) {
    $dbError = true;
    $errorMessage = $e->getMessage();
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

// Database setup check
function checkDatabaseSetup() {
    global $conn;
    try {
        $query = "SHOW TABLES LIKE 'competitions'";
        $result = $conn->query($query);
        if ($result->num_rows == 0) {
            return false;
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Check if database is set up properly
$databaseSetupOk = checkDatabaseSetup();

if (!$databaseSetupOk) {
    // Display database setup message
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Setup Required - EasyComp</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link rel="stylesheet" href="styles.css">
        <style>
            .setup-container {
                max-width: 800px;
                margin: 50px auto;
                padding: 30px;
                background-color: var(--bg-secondary);
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .setup-icon {
                font-size: 3rem;
                color: var(--warning-color);
                margin-bottom: 20px;
            }
            .setup-title {
                font-size: 1.8rem;
                margin-bottom: 20px;
                color: var(--text-primary);
            }
            .setup-message {
                margin-bottom: 20px;
                line-height: 1.6;
                color: var(--text-secondary);
            }
            .setup-button {
                display: inline-block;
                padding: 10px 20px;
                background-color: var(--primary-color);
                color: white;
                border-radius: 5px;
                text-decoration: none;
                font-weight: 600;
                transition: background-color 0.3s;
            }
            .setup-button:hover {
                background-color: var(--primary-color-dark);
            }
        </style>
    </head>
    <body>';
    
    // Include navbar if exists
    if (file_exists('includes/navbar.php')) {
        include 'includes/navbar.php';
    }
    
    echo '<div class="container setup-container">
        <div class="text-center">
            <div class="setup-icon">
                <i class="fas fa-database"></i>
            </div>
            <h1 class="setup-title">Database Setup Required</h1>
            <div class="setup-message">
                <p>The analytics page cannot load because your database is not set up properly.</p>
                <p>Click the button below to run the database setup script which will create all necessary tables.</p>
            </div>
            <a href="setup_database.php" class="setup-button">
                <i class="fas fa-tools"></i> Run Database Setup
            </a>
        </div>
    </div>';
    
    // Include footer if exists
    if (file_exists('includes/footer.php')) {
        include 'includes/footer.php';
    } else {
        echo '<div class="container text-center text-muted py-3">&copy; ' . date('Y') . ' EasyComp. All rights reserved.</div>';
    }
    
    echo '</body></html>';
    exit;
}

// Get organizer details
$organizer_query = "SELECT * FROM organizers WHERE id = ?";
$stmt = $conn->prepare($organizer_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$organizer_result = $stmt->get_result();
$organizer = $organizer_result->fetch_assoc();

// Get general statistics
try {
    $stats_query = "SELECT 
                   COUNT(*) as total_competitions,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_competitions,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_competitions,
                   SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_competitions,
                   SUM(CASE WHEN CURDATE() BETWEEN start_date AND end_date THEN 1 ELSE 0 END) as active_competitions,
                   SUM(CASE WHEN CURDATE() > end_date THEN 1 ELSE 0 END) as past_competitions,
                   SUM(CASE WHEN CURDATE() < start_date THEN 1 ELSE 0 END) as upcoming_competitions
                   FROM competitions
                   WHERE organizer_id = ?";
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $stats = $stats_result->fetch_assoc();
} catch (Exception $e) {
    // Set default values if query fails
    $stats = [
        'total_competitions' => 0,
        'approved_competitions' => 0,
        'pending_competitions' => 0,
        'rejected_competitions' => 0,
        'active_competitions' => 0,
        'past_competitions' => 0,
        'upcoming_competitions' => 0
    ];
}

// Get participant statistics - add limit to optimize
try {
    $participants_query = "SELECT 
                          COUNT(*) as total_participants,
                          SUM(CASE WHEN cp.status = 'approved' THEN 1 ELSE 0 END) as registered_participants,
                          SUM(CASE WHEN cp.status = 'pending' THEN 1 ELSE 0 END) as pending_participants,
                          SUM(CASE WHEN cp.status = 'rejected' THEN 1 ELSE 0 END) as rejected_participants,
                          COUNT(DISTINCT cp.student_id) as unique_students
                          FROM competition_participants cp
                          JOIN competitions c ON cp.competition_id = c.id
                          WHERE c.organizer_id = ?
                          LIMIT 10000";
    $stmt = $conn->prepare($participants_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $participants_result = $stmt->get_result();
    $participants = $participants_result->fetch_assoc();
} catch (Exception $e) {
    // Set default values if query fails
    $participants = [
        'total_participants' => 0,
        'registered_participants' => 0,
        'pending_participants' => 0,
        'rejected_participants' => 0,
        'unique_students' => 0
    ];
}

// Get competitions by category - add limit for better performance
try {
    $categories_query = "SELECT cat.name, COUNT(*) as count
                        FROM competitions c
                        JOIN categories cat ON c.category_id = cat.id
                        WHERE c.organizer_id = ?
                        GROUP BY cat.name
                        ORDER BY count DESC
                        LIMIT 10";
    $stmt = $conn->prepare($categories_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $categories_result = $stmt->get_result();
} catch (Exception $e) {
    // Create an empty result set
    $categories_result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Get most popular competitions - already has LIMIT
try {
    $popular_query = "SELECT c.id, c.title, COUNT(cp.id) as participants_count
                     FROM competitions c
                     LEFT JOIN competition_participants cp ON c.id = cp.competition_id
                     WHERE c.organizer_id = ?
                     GROUP BY c.id
                     ORDER BY participants_count DESC
                     LIMIT 5";
    $stmt = $conn->prepare($popular_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $popular_result = $stmt->get_result();
} catch (Exception $e) {
    // Create an empty result set
    $popular_result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Get recent competitions - already has LIMIT
try {
    $recent_query = "SELECT c.id, c.title, c.status, c.created_at, cat.name as category_name
                    FROM competitions c
                    LEFT JOIN categories cat ON c.category_id = cat.id
                    WHERE c.organizer_id = ?
                    ORDER BY c.created_at DESC
                    LIMIT 5";
    $stmt = $conn->prepare($recent_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_result = $stmt->get_result();
} catch (Exception $e) {
    // Create an empty result set
    $recent_result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Get monthly participants count for the last 6 months - optimize with date-specific filtering
try {
    $monthly_query = "SELECT 
                     DATE_FORMAT(cp.created_at, '%Y-%m') as month,
                     COUNT(*) as count
                     FROM competition_participants cp
                     JOIN competitions c ON cp.competition_id = c.id
                     WHERE c.organizer_id = ?
                     AND cp.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY month
                     ORDER BY month ASC
                     LIMIT 6";
    $stmt = $conn->prepare($monthly_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $monthly_result = $stmt->get_result();
    $monthly_data = [];
    while ($row = $monthly_result->fetch_assoc()) {
        $month_label = date('M Y', strtotime($row['month'] . '-01'));
        $monthly_data[$month_label] = $row['count'];
    }
} catch (Exception $e) {
    // Set empty monthly data if query fails
    $monthly_data = [];
}

// Fill in missing months
$end_date = new DateTime();
$start_date = new DateTime();
$start_date->modify('-5 months');
$interval = new DateInterval('P1M');
$period = new DatePeriod($start_date, $interval, $end_date);

$complete_monthly_data = [];
foreach ($period as $date) {
    $month_label = $date->format('M Y');
    $complete_monthly_data[$month_label] = $monthly_data[$month_label] ?? 0;
}

// Convert to JSON for charts
$categories_data = [];
$categories_labels = [];
while ($category = $categories_result->fetch_assoc()) {
    $categories_labels[] = $category['name'];
    $categories_data[] = $category['count'];
}

$monthly_labels = array_keys($complete_monthly_data);
$monthly_counts = array_values($complete_monthly_data);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - EasyComp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: 10px;
            background-color: var(--bg-secondary);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
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
        
        .chart-container {
            background-color: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            position: relative;
            min-height: 300px;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .popular-competition {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            background-color: var(--bg-primary);
            transition: transform 0.2s;
        }
        
        .popular-competition:hover {
            transform: translateX(5px);
        }
        
        .loader {
            display: flex;
            justify-content: center;
            align-items: center;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(var(--bg-secondary-rgb), 0.7);
            z-index: 1;
        }
        
        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid rgba(var(--primary-rgb), 0.2);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .page-loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .page-loader-spinner {
            width: 60px;
            height: 60px;
            border: 6px solid rgba(var(--primary-rgb), 0.2);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        .page-loader-text {
            font-size: 1.2rem;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .recent-competition {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            background-color: var(--bg-primary);
            transition: transform 0.2s;
        }
        
        .recent-competition:hover {
            transform: translateX(5px);
        }
        
        .recent-competition .competition-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(var(--primary-rgb), 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-right: 15px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 10px;
        }
        
        .status-approved {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }
        
        .status-pending {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }
        
        .status-rejected {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .competition-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .competition-info {
            flex: 1;
        }
        
        .competition-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .competition-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .competition-participants {
            font-weight: 600;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Page Loader -->
    <div class="page-loader" id="pageLoader">
        <div class="page-loader-spinner"></div>
        <div class="page-loader-text">Loading analytics...</div>
    </div>

    <?php 
    $navbar_path = 'includes/navbar.php';
    if (file_exists($navbar_path)) {
        include $navbar_path;
    } else {
        echo '<div class="alert alert-warning">Navigation menu not found.</div>';
    }
    ?>
    
    <div class="container my-5">
        <h1 class="mb-4"><i class="fas fa-chart-line"></i> Analytics Dashboard</h1>
        
        <?php if ($dbError): ?>
        <div class="alert alert-warning mb-4">
            <h4><i class="fas fa-exclamation-triangle"></i> Database Connection Issue</h4>
            <p>There was a problem connecting to the database. Some data may not display correctly.</p>
            <p>Error: <?php echo htmlspecialchars($errorMessage); ?></p>
            <a href="setup_database.php" class="btn btn-warning">
                <i class="fas fa-tools"></i> Run Database Setup
            </a>
        </div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon mb-3">
                    <i class="fas fa-trophy fa-2x text-primary"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_competitions'] ?? 0; ?></div>
                <div class="stat-label">Total Competitions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon mb-3">
                    <i class="fas fa-users fa-2x text-primary"></i>
                </div>
                <div class="stat-number"><?php echo $participants['total_participants'] ?? 0; ?></div>
                <div class="stat-label">Total Participants</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon mb-3">
                    <i class="fas fa-user-check fa-2x text-primary"></i>
                </div>
                <div class="stat-number"><?php echo $participants['unique_students'] ?? 0; ?></div>
                <div class="stat-label">Unique Students</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon mb-3">
                    <i class="fas fa-calendar-check fa-2x text-success"></i>
                </div>
                <div class="stat-number"><?php echo $stats['active_competitions'] ?? 0; ?></div>
                <div class="stat-label">Active Competitions</div>
            </div>
        </div>
        
        <div class="analytics-grid">
            <div class="main-analytics">
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Participant Trends (Last 6 Months)</div>
                    </div>
                    <div class="loader" id="trendChartLoader">
                        <div class="loader-spinner"></div>
                    </div>
                    <canvas id="participantsTrendChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Competitions by Category</div>
                    </div>
                    <div class="loader" id="categoriesChartLoader">
                        <div class="loader-spinner"></div>
                    </div>
                    <canvas id="categoriesChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Most Popular Competitions</div>
                    </div>
                    <div class="loader" id="popularLoader">
                        <div class="loader-spinner"></div>
                    </div>
                    
                    <?php if ($popular_result->num_rows > 0): ?>
                        <div class="popular-competitions-list">
                            <?php $rank = 1; while ($competition = $popular_result->fetch_assoc()): ?>
                                <div class="popular-competition">
                                    <div class="competition-rank"><?php echo $rank++; ?></div>
                                    
                                    <div class="competition-info">
                                        <div class="competition-title"><?php echo htmlspecialchars($competition['title']); ?></div>
                                    </div>
                                    
                                    <div class="competition-participants">
                                        <i class="fas fa-users me-1"></i> <?php echo $competition['participants_count']; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No competitions data available yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="side-analytics">
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Competition Status</div>
                    </div>
                    <div class="loader" id="statusChartLoader">
                        <div class="loader-spinner"></div>
                    </div>
                    <canvas id="statusChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Recent Competitions</div>
                    </div>
                    <div class="loader" id="recentLoader">
                        <div class="loader-spinner"></div>
                    </div>
                    
                    <?php if ($recent_result->num_rows > 0): ?>
                        <div class="recent-competitions-list">
                            <?php while ($competition = $recent_result->fetch_assoc()): ?>
                                <div class="recent-competition">
                                    <div class="competition-icon">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    
                                    <div class="competition-info">
                                        <div class="competition-title d-flex align-items-center">
                                            <?php echo htmlspecialchars($competition['title']); ?>
                                            <span class="status-badge status-<?php echo $competition['status']; ?>">
                                                <?php echo ucfirst($competition['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="competition-meta">
                                            <i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($competition['category_name'] ?? 'Uncategorized'); ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('M j, Y', strtotime($competition['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No recent competitions yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="chart-container">
                    <div class="chart-header">
                        <div class="chart-title">Participant Status</div>
                    </div>
                    <div class="loader" id="participantStatusChartLoader">
                        <div class="loader-spinner"></div>
                    </div>
                    <canvas id="participantStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <?php 
    $footer_path = 'includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    } else {
        echo '<div class="container text-center text-muted py-3">&copy; ' . date('Y') . ' EasyComp. All rights reserved.</div>';
    }
    ?>
    
    <script>
        // Data for charts
        const monthlyLabels = <?php echo json_encode($monthly_labels); ?>;
        const monthlyCounts = <?php echo json_encode($monthly_counts); ?>;
        const categoriesLabels = <?php echo json_encode($categories_labels); ?>;
        const categoriesData = <?php echo json_encode($categories_data); ?>;
        const statusData = [
            <?php echo $stats['approved_competitions'] ?? 0; ?>,
            <?php echo $stats['pending_competitions'] ?? 0; ?>,
            <?php echo $stats['rejected_competitions'] ?? 0; ?>
        ];
        const participantStatusData = [
            <?php echo $participants['registered_participants'] ?? 0; ?>,
            <?php echo $participants['pending_participants'] ?? 0; ?>,
            <?php echo $participants['rejected_participants'] ?? 0; ?>
        ];

        // Hide loaders function
        function hideLoader(loaderId) {
            const loader = document.getElementById(loaderId);
            if (loader) {
                loader.style.display = 'none';
            }
        }

        // Initialize charts asynchronously
        document.addEventListener('DOMContentLoaded', function() {
            // Hide page loader after initial content is loaded
            setTimeout(() => {
                document.getElementById('pageLoader').style.display = 'none';
            }, 500);

            // Initialize charts with small delays to prevent rendering bottleneck
            setTimeout(() => {
                initTrendChart();
                hideLoader('trendChartLoader');
            }, 100);

            setTimeout(() => {
                initCategoriesChart();
                hideLoader('categoriesChartLoader');
            }, 300);

            setTimeout(() => {
                initStatusChart();
                hideLoader('statusChartLoader');
            }, 500);

            setTimeout(() => {
                initParticipantStatusChart();
                hideLoader('participantStatusChartLoader');
            }, 700);

            setTimeout(() => {
                hideLoader('popularLoader');
                hideLoader('recentLoader');
            }, 200);
        });

        // Participants Trend Chart
        function initTrendChart() {
            const trendCtx = document.getElementById('participantsTrendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: monthlyLabels,
                    datasets: [{
                        label: 'New Participants',
                        data: monthlyCounts,
                        borderColor: 'rgba(78, 115, 223, 1)',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                        pointBorderColor: '#fff',
                        pointHoverBackgroundColor: '#fff',
                        pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Categories Chart
        function initCategoriesChart() {
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'bar',
                data: {
                    labels: categoriesLabels,
                    datasets: [{
                        label: 'Number of Competitions',
                        data: categoriesData,
                        backgroundColor: [
                            'rgba(78, 115, 223, 0.8)',
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(246, 194, 62, 0.8)',
                            'rgba(231, 74, 59, 0.8)',
                            'rgba(54, 185, 204, 0.8)',
                            'rgba(133, 135, 150, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Status Chart
        function initStatusChart() {
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Approved', 'Pending', 'Rejected'],
                    datasets: [{
                        data: statusData,
                        backgroundColor: [
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(246, 194, 62, 0.8)',
                            'rgba(231, 74, 59, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        
        // Participant Status Chart
        function initParticipantStatusChart() {
            const participantStatusCtx = document.getElementById('participantStatusChart').getContext('2d');
            new Chart(participantStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Registered', 'Pending', 'Rejected'],
                    datasets: [{
                        data: participantStatusData,
                        backgroundColor: [
                            'rgba(28, 200, 138, 0.8)',
                            'rgba(78, 115, 223, 0.8)',
                            'rgba(231, 74, 59, 0.8)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    </script>
    <script src="app.js"></script>
</body>
</html> 