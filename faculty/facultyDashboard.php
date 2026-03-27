<?php
session_start();
include("../config/db.php");
include("../config/archive_manager.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION - CHECK IF USER IS LOGGED IN AND IS A FACULTY
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'faculty') {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

// GET LOGGED-IN USER INFO FROM SESSION
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$fullName = $first_name . " " . $last_name;
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// GET USER DATA FROM DATABASE
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

// GET STATISTICS
$pendingCount = 0;
$approvedCount = 0;
$rejectedCount = 0;
$archivedCount = 0;
$totalCount = 0;

try {
    $countsQuery = "SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived,
        COUNT(*) as total
    FROM thesis_table";
    
    $countsResult = $conn->query($countsQuery);
    if ($countsResult) {
        $counts = $countsResult->fetch_assoc();
        $pendingCount = $counts['pending'] ?? 0;
        $approvedCount = $counts['approved'] ?? 0;
        $rejectedCount = $counts['rejected'] ?? 0;
        $archivedCount = $counts['archived'] ?? 0;
        $totalCount = $counts['total'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Counts error: " . $e->getMessage());
}

// GET PENDING THESES
$pendingTheses = [];
try {
    $query = "SELECT t.*, u.first_name, u.last_name, u.email 
              FROM thesis_table t
              JOIN user_table u ON t.student_id = u.user_id
              WHERE t.status = 'pending'
              ORDER BY t.date_submitted DESC 
              LIMIT 10";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pendingTheses[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Thesis query error: " . $e->getMessage());
}

// GET ALL SUBMISSIONS
$allSubmissions = [];
$currentFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

try {
    $sql = "SELECT 
            t.*, 
            u.first_name, 
            u.last_name, 
            u.email,
            s.student_id,
            (SELECT COUNT(*) FROM feedback_table f WHERE f.thesis_id = t.thesis_id) as feedback_count,
            (SELECT MAX(feedback_date) FROM feedback_table f WHERE f.thesis_id = t.thesis_id) as last_feedback_date,
            (SELECT comments FROM feedback_table f WHERE f.thesis_id = t.thesis_id ORDER BY feedback_date DESC LIMIT 1) as latest_feedback
            FROM thesis_table t
            JOIN user_table u ON t.student_id = u.user_id
            JOIN student_table s ON u.user_id = s.user_id";
    
    if ($currentFilter != 'all') {
        $sql .= " WHERE t.status = '" . $conn->real_escape_string($currentFilter) . "'";
    }
    
    $sql .= " ORDER BY t.date_submitted DESC";
    
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $allSubmissions[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Submissions query error: " . $e->getMessage());
}

$pageTitle = "Faculty Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Thesis Management System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/facultyDashboard.css">
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
                    <a href="facultyProfile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="facultyEditProfile.php"><i class="fas fa-edit"></i> Edit Profile</a>
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
            <div class="logo-sub">RESEARCH ADVISER</div>
        </div>
        
        <div class="nav-menu">
            <a href="facultyDashboard.php" class="nav-item active">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="reviewThesis.php" class="nav-item">
                <i class="fas fa-file-alt"></i>
                <span>Review Theses</span>
                <?php if ($pendingCount > 0): ?>
                    <span class="notification-badge-sidebar"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="facultyFeedback.php" class="nav-item">
                <i class="fas fa-comment-dots"></i>
                <span>My Feedback</span>
            </a>
            <a href="notification.php" class="nav-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <?php if ($notificationCount > 0): ?>
                    <span class="notification-badge-sidebar"><?= $notificationCount ?></span>
                <?php endif; ?>
            </a>
            <a href="archived_theses.php" class="nav-item">
                <i class="fas fa-archive"></i>
                <span>Archived Theses</span>
                <?php if ($archivedCount > 0): ?>
                    <span class="notification-badge-sidebar"><?= $archivedCount ?></span>
                <?php endif; ?>
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
                <h1>Research Adviser Dashboard</h1>
                <p><strong>FACULTY</strong> • Welcome back, <?= htmlspecialchars($first_name) ?>! • Overview of your advising and review activities</p>
            </div>
            <div class="faculty-info">
                <div class="faculty-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="faculty-since">Faculty since <?= $user_created ?></div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content">
                    <h3><?= number_format($pendingCount) ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <h3><?= number_format($approvedCount) ?></h3>
                    <p>Approved</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-content">
                    <h3><?= number_format($rejectedCount) ?></h3>
                    <p>Rejected</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-archive"></i></div>
                <div class="stat-content">
                    <h3><?= number_format($archivedCount) ?></h3>
                    <p>Archived</p>
                </div>
            </div>
        </div>

        <!-- Theses Waiting for Review -->
        <div class="theses-card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Theses Waiting for Review</h3>
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
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?></span>
                                <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($thesis['date_submitted'])) ?></span>
                            </div>
                        </div>
                        <a href="reviewThesis.php?id=<?= $thesis['thesis_id'] ?>" class="review-btn">Review <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- All Thesis Submissions -->
        <div class="submissions-card">
            <div class="card-header">
                <h3><i class="fas fa-file-alt"></i> All Thesis Submissions</h3>
                <div class="filter-tabs">
                    <a href="?status=all" class="filter-btn <?= $currentFilter == 'all' ? 'active' : '' ?>">All (<?= $totalCount ?>)</a>
                    <a href="?status=pending" class="filter-btn <?= $currentFilter == 'pending' ? 'active' : '' ?>">Pending (<?= $pendingCount ?>)</a>
                    <a href="?status=approved" class="filter-btn <?= $currentFilter == 'approved' ? 'active' : '' ?>">Approved (<?= $approvedCount ?>)</a>
                    <a href="?status=rejected" class="filter-btn <?= $currentFilter == 'rejected' ? 'active' : '' ?>">Rejected (<?= $rejectedCount ?>)</a>
                    <a href="?status=archived" class="filter-btn <?= $currentFilter == 'archived' ? 'active' : '' ?>">Archived (<?= $archivedCount ?>)</a>
                </div>
            </div>
            <div class="table-responsive">
                <?php if (empty($allSubmissions)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No thesis submissions yet.</p>
                    </div>
                <?php else: ?>
                    <table class="theses-table">
                        <thead>
                            <tr>
                                <th>Thesis Title</th>
                                <th>Student</th>
                                <th>Date Submitted</th>
                                <th>Status</th>
                                <th>Feedback</th>
                                <th>Action</th>
                            </thead>
                        <tbody>
                            <?php foreach ($allSubmissions as $submission): ?>
                            <tr class="status-<?= $submission['status'] ?>">
                                <td><strong><?= htmlspecialchars($submission['title']) ?></strong></td>
                                <td><?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($submission['date_submitted'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $submission['status'] ?>">
                                        <?= ucfirst($submission['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $submission['feedback_count'] ?> feedback(s)
                                    <?php if ($submission['last_feedback_date']): ?>
                                        <br><small>Last: <?= date('M d', strtotime($submission['last_feedback_date'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($submission['status'] == 'pending'): ?>
                                        <a href="reviewThesis.php?id=<?= $submission['thesis_id'] ?>" class="btn-review-small">
                                            <i class="fas fa-check-circle"></i> Review
                                        </a>
                                    <?php else: ?>
                                        <a href="reviewThesis.php?id=<?= $submission['thesis_id'] ?>" class="btn-view-small">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        window.userData = {
            stats: {
                pending: <?= $pendingCount ?>,
                approved: <?= $approvedCount ?>,
                rejected: <?= $rejectedCount ?>,
                archived: <?= $archivedCount ?>
            }
        };
    </script>
    <script src="js/facultyDashboard.js"></script>
</body>
</html>