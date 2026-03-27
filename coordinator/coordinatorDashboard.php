<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION - CHECK IF USER IS LOGGED IN AND IS A COORDINATOR
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

// GET LOGGED-IN USER INFO FROM SESSION
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$fullName = $first_name . " " . $last_name;
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// GET USER DATA FROM DATABASE - REMOVED created_at
$user_query = "SELECT user_id, username, email, first_name, last_name, role_id, status FROM user_table WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

if ($user_data) {
    $first_name = $user_data['first_name'];
    $last_name = $user_data['last_name'];
    $fullName = $first_name . " " . $last_name;
    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
    $user_email = $user_data['email'];
    $username = $user_data['username'];
}

// Check if created_at column exists
$user_created = date('F Y');
$check_created_column = $conn->query("SHOW COLUMNS FROM user_table LIKE 'created_at'");
if ($check_created_column && $check_created_column->num_rows > 0) {
    $user_query_full = "SELECT created_at FROM user_table WHERE user_id = ?";
    $user_stmt_full = $conn->prepare($user_query_full);
    $user_stmt_full->bind_param("i", $user_id);
    $user_stmt_full->execute();
    $user_result_full = $user_stmt_full->get_result();
    if ($user_row = $user_result_full->fetch_assoc()) {
        $user_created = date('F Y', strtotime($user_row['created_at']));
    }
    $user_stmt_full->close();
}

// GET COORDINATOR DATA FROM DEPARTMENT_COORDINATOR TABLE
$department_name = "Research Department";
$department_code = "RD";
$position = "Research Coordinator";
$assigned_date = $user_created;

$coordinator_query = "
    SELECT dc.*, d.department_name, d.department_code
    FROM department_coordinator dc
    JOIN department_table d ON dc.department_id = d.department_id
    WHERE dc.user_id = ?
";
$coordinator_stmt = $conn->prepare($coordinator_query);
$coordinator_stmt->bind_param("i", $user_id);
$coordinator_stmt->execute();
$coordinator_result = $coordinator_stmt->get_result();
$coordinator_data = $coordinator_result->fetch_assoc();

if ($coordinator_data) {
    $department_name = $coordinator_data['department_name'] ?? $department_name;
    $department_code = $coordinator_data['department_code'] ?? $department_code;
    $position = $coordinator_data['position'] ?? $position;
    $assigned_date = isset($coordinator_data['assigned_date']) ? date('F Y', strtotime($coordinator_data['assigned_date'])) : $user_created;
}
$coordinator_stmt->close();

// GET NOTIFICATION COUNT
$notificationCount = 0;
$check_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($check_table && $check_table->num_rows > 0) {
    $notif_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $notif_stmt = $conn->prepare($notif_query);
    $notif_stmt->bind_param("i", $user_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    if ($notif_row = $notif_result->fetch_assoc()) {
        $notificationCount = $notif_row['count'];
    }
    $notif_stmt->close();
}

// CHECK IF THESES TABLE EXISTS
$theses_table_exists = false;
$check_theses = $conn->query("SHOW TABLES LIKE 'theses'");
if ($check_theses && $check_theses->num_rows > 0) {
    $theses_table_exists = true;
}

// GET THESIS DATA FROM DATABASE
$allSubmissions = [
    'pending_coordinator' => [],
    'forwarded_to_dean' => [],
    'rejected' => []
];

if ($theses_table_exists) {
    // Get pending theses
    $pending_query = "SELECT * FROM theses WHERE status = 'Pending' OR status = 'For Review' ORDER BY created_at DESC";
    $pending_result = $conn->query($pending_query);
    if ($pending_result && $pending_result->num_rows > 0) {
        while ($row = $pending_result->fetch_assoc()) {
            $allSubmissions['pending_coordinator'][] = [
                'title' => $row['title'],
                'author' => $row['student_name'] ?? 'Unknown',
                'date' => isset($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : date('M d, Y'),
                'id' => $row['thesis_id']
            ];
        }
    }
    
    // Get forwarded to dean theses
    $forwarded_query = "SELECT * FROM theses WHERE status = 'Forwarded to Dean' OR status = 'Dean Review' ORDER BY created_at DESC";
    $forwarded_result = $conn->query($forwarded_query);
    if ($forwarded_result && $forwarded_result->num_rows > 0) {
        while ($row = $forwarded_result->fetch_assoc()) {
            $allSubmissions['forwarded_to_dean'][] = [
                'title' => $row['title'],
                'author' => $row['student_name'] ?? 'Unknown',
                'date' => isset($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : date('M d, Y'),
                'id' => $row['thesis_id']
            ];
        }
    }
    
    // Get rejected theses
    $rejected_query = "SELECT * FROM theses WHERE status = 'Rejected' ORDER BY created_at DESC";
    $rejected_result = $conn->query($rejected_query);
    if ($rejected_result && $rejected_result->num_rows > 0) {
        while ($row = $rejected_result->fetch_assoc()) {
            $allSubmissions['rejected'][] = [
                'title' => $row['title'],
                'author' => $row['student_name'] ?? 'Unknown',
                'date' => isset($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : date('M d, Y'),
                'id' => $row['thesis_id']
            ];
        }
    }
}

// Compute statistics
$stats = [
    'forwarded' => count($allSubmissions['forwarded_to_dean']),
    'rejected'  => count($allSubmissions['rejected']),
    'pending'   => count($allSubmissions['pending_coordinator'])
];

$pendingTheses = $allSubmissions['pending_coordinator'];

// Flatten all theses for the main table
$allThesesWithStatus = [];
foreach ($allSubmissions as $status => $theses) {
    foreach ($theses as $thesis) {
        $thesis['status'] = $status;
        $allThesesWithStatus[] = $thesis;
    }
}

// Monthly submissions data for chart
$monthly_data = array_fill(0, 12, 0);
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

if ($theses_table_exists) {
    $monthly_query = "SELECT MONTH(created_at) as month, COUNT(*) as count FROM theses WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at)";
    $monthly_result = $conn->query($monthly_query);
    if ($monthly_result && $monthly_result->num_rows > 0) {
        while ($row = $monthly_result->fetch_assoc()) {
            $monthly_data[$row['month'] - 1] = $row['count'];
        }
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Research Coordinator Dashboard | Thesis Management System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- Overlay for sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Top Navigation Bar -->
    <header class="top-nav">
        <div class="nav-left">
            <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="search-area">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search theses...">
            </div>
        </div>
        <div class="nav-right">
            <div class="notification-icon">
                <i class="far fa-bell"></i>
                <?php if ($notificationCount > 0): ?>
                    <span class="notification-badge"><?= $notificationCount ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-wrapper" id="profileWrapper">
                <div class="profile-trigger">
                    <span class="profile-name"><?= htmlspecialchars($fullName) ?></span>
                    <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="editProfile.php"><i class="fas fa-edit"></i> Edit Profile</a>
                    <a href="#"><i class="fas fa-cog"></i> Settings</a>
                    <hr>
                    <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="logo-container">
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="logo-sub">RESEARCH COORDINATOR</div>
        </div>
        
        <div class="nav-menu">
            <a href="coordinatorDashboard.php" class="nav-item active">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="reviewThesis.php" class="nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Review Theses</span>
            </a>
            <a href="myFeedback.php" class="nav-item">
                <i class="fas fa-comment"></i>
                <span>My Feedback</span>
            </a>
            <a href="notification.php" class="nav-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="forwardedTheses.php" class="nav-item">
                <i class="fas fa-arrow-right"></i>
                <span>Forwarded to Dean</span>
            </a>
        </div>
        
        <div class="nav-footer">
            <div class="theme-toggle">
                <input type="checkbox" id="darkmode">
                <label for="darkmode" class="toggle-label">
                    <i class="fas fa-sun"></i>
                    <i class="fas fa-moon"></i>
                    <span class="slider"></span>
                </label>
            </div>
            <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-info">
                <h1>Research Coordinator Dashboard</h1>
                <p><strong>COORDINATOR</strong> • Welcome back, <?= htmlspecialchars($first_name) ?>! • <?= htmlspecialchars($department_name) ?></p>
            </div>
            <div class="coordinator-info">
                <div class="coordinator-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="coordinator-position"><?= htmlspecialchars($position) ?></div>
                <div class="coordinator-since">Since <?= $assigned_date ?></div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-arrow-right"></i></div>
                <div class="stat-content">
                    <h3><?= number_format($stats['forwarded']) ?></h3>
                    <p>Forwarded to Dean</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-content">
                    <h3><?= number_format($stats['rejected']) ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content">
                    <h3><?= number_format($stats['pending']) ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-row">
            <!-- Status Distribution Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Thesis Status Distribution</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="status-labels">
                    <div class="status-label-item">
                        <span class="status-color pending"></span>
                        <span>Pending Review (<?= $stats['pending'] ?>)</span>
                    </div>
                    <div class="status-label-item">
                        <span class="status-color forwarded"></span>
                        <span>Forwarded to Dean (<?= $stats['forwarded'] ?>)</span>
                    </div>
                    <div class="status-label-item">
                        <span class="status-color rejected"></span>
                        <span>Rejected (<?= $stats['rejected'] ?>)</span>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Submissions Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Monthly Thesis Submissions</h3>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
                <div class="monthly-stats">
                    <?php for ($i = 0; $i < 12; $i++): ?>
                    <div class="month-item">
                        <span class="month-name"><?= $months[$i] ?>:</span>
                        <span class="month-count"><?= $monthly_data[$i] ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Theses Waiting for Review -->
        <div class="theses-card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Theses Waiting for Your Review</h3>
                <a href="reviewThesis.php" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($pendingTheses)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p>No pending theses to review</p>
                </div>
            <?php else: ?>
                <div class="theses-list">
                    <?php foreach ($pendingTheses as $thesis): ?>
                    <div class="thesis-item">
                        <div class="thesis-info">
                            <div class="thesis-title"><?= htmlspecialchars($thesis['title']) ?></div>
                            <div class="thesis-meta">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($thesis['author']) ?></span>
                                <span><i class="fas fa-calendar"></i> <?= $thesis['date'] ?></span>
                            </div>
                        </div>
                        <a href="reviewThesis.php?id=<?= $thesis['id'] ?? urlencode($thesis['title']) ?>" class="review-btn">Review <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Thesis Submissions -->
        <div class="submissions-card">
            <div class="card-header">
                <h3><i class="fas fa-file-alt"></i> All Thesis Submissions</h3>
                <div class="search-area-small">
                    <i class="fas fa-search"></i>
                    <input type="text" id="thesisSearchInput" placeholder="Search theses...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="theses-table">
                    <thead>
                        <tr>
                            <th>Thesis Title</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </thead>
                    <tbody id="thesisTableBody">
                        <?php foreach ($allThesesWithStatus as $thesis): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($thesis['title']) ?></strong>\\
                            <td><?= htmlspecialchars($thesis['author']) ?>\\
                            <td><?= $thesis['date'] ?>\\
                            <td>
                                <span class="status-badge <?= $thesis['status'] ?>">
                                    <?php 
                                        $status_text = ucfirst(str_replace('_', ' ', $thesis['status']));
                                        if ($status_text == 'Pending_coordinator') $status_text = 'Pending Review';
                                        echo $status_text;
                                    ?>
                                </span>
                            \\
                            <td>
                                <a href="reviewThesis.php?id=<?= $thesis['id'] ?? urlencode($thesis['title']) ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                             \\
                         \\
                        <?php endforeach; ?>
                    </tbody>
                 \\
            </div>
        </div>
    </main>

    <script>
        window.chartData = {
            status: {
                pending: <?= $stats['pending'] ?>,
                forwarded: <?= $stats['forwarded'] ?>,
                rejected: <?= $stats['rejected'] ?>
            },
            monthly: <?= json_encode($monthly_data) ?>,
            months: <?= json_encode($months) ?>
        };
    </script>
    <script src="js/coordinatorDashboard.js"></script>
</body>
</html>