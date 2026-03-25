<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a librarian
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'librarian') {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

// Get logged-in user info
$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? 'Joyce';
$last_name = $_SESSION['last_name'] ?? '';
$fullName = $first_name . " " . $last_name;
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get notification count
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

// Statistics data
$stats = [
    'total_faculty' => 28,
    'total_students' => 342,
    'active_projects' => 87,
    'upcoming_defenses' => 4,
    'pending_reviews' => 23,
    'approved_this_sem' => 15
];

// Faculty workload data
$faculty_workload = [
    ['name' => 'Dr. Maria Santos', 'projects' => 10, 'initials' => 'MS'],
    ['name' => 'Prof. Juan Cruz', 'projects' => 9, 'initials' => 'JC'],
    ['name' => 'Dr. Ana Reyes', 'projects' => 8, 'initials' => 'AR'],
    ['name' => 'Prof. Pedro Garcia', 'projects' => 7, 'initials' => 'PG'],
    ['name' => 'Dr. Lisa Villanueva', 'projects' => 6, 'initials' => 'LV']
];

// Recent activities
$recent_activities = [
    ['description' => 'New thesis submitted by John Doe', 'time' => '2 minutes ago'],
    ['description' => 'Thesis approved: "Machine Learning in Education"', 'time' => '1 hour ago'],
    ['description' => 'Feedback given on thesis by Dr. Santos', 'time' => '3 hours ago'],
    ['description' => 'New faculty account created', 'time' => '1 day ago'],
    ['description' => 'Thesis archived: "Web Development Framework"', 'time' => '2 days ago']
];

$pageTitle = "Librarian Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Thesis Management System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/librarian_dashboard.css">
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
                <input type="text" placeholder="Search faculty, activities...">
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
                    <a href="librarian_profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="edit_profile.php"><i class="fas fa-edit"></i> Edit Profile</a>
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
            <div class="logo-sub">LIBRARIAN</div>
        </div>
        
        <div class="nav-menu">
            <a href="librarian_dashboard.php" class="nav-item active">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-archive"></i>
                <span>Archive</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-building"></i>
                <span>Departments</span>
            </a>
            <a href="librarian_profile.php" class="nav-item">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
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
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <h1>Welcome back, <?= htmlspecialchars($first_name) ?>!</h1>
            <p><span class="role-badge">LIBRARIAN</span> · Dashboard Overview</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_faculty'] ?></div>
                <div class="stat-label">TOTAL FACULTY</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_students'] ?></div>
                <div class="stat-label">TOTAL STUDENTS</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['active_projects'] ?></div>
                <div class="stat-label">ACTIVE PROJECTS</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['upcoming_defenses'] ?></div>
                <div class="stat-label">UPCOMING DEFENSES</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending_reviews'] ?></div>
                <div class="stat-label">PENDING REVIEWS</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['approved_this_sem'] ?></div>
                <div class="stat-label">APPROVED THIS SEM</div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> Theses Status Distribution</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Monthly Thesis Submissions</h3>
                <div class="chart-container">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Bottom Grid -->
        <div class="bottom-grid">
            <div class="info-card">
                <h3><i class="fas fa-chalkboard-user"></i> Faculty Workload</h3>
                <div class="faculty-list">
                    <?php foreach ($faculty_workload as $faculty): ?>
                    <div class="faculty-item">
                        <div class="faculty-info">
                            <div class="faculty-avatar-small"><?= $faculty['initials'] ?></div>
                            <span class="faculty-name"><?= htmlspecialchars($faculty['name']) ?></span>
                        </div>
                        <span class="faculty-projects"><?= $faculty['projects'] ?> projects</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="info-card">
                <h3><i class="fas fa-history"></i> Recent Activity</h3>
                <div class="activity-list">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-circle"></i>
                        </div>
                        <div class="activity-details">
                            <div class="activity-description"><?= htmlspecialchars($activity['description']) ?></div>
                            <div class="activity-time"><?= $activity['time'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="js/librarian_dashboard.js"></script>
</body>
</html>