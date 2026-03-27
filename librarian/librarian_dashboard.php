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

// Check if theses table exists
$theses_table_exists = false;
$check_theses = $conn->query("SHOW TABLES LIKE 'theses'");
if ($check_theses && $check_theses->num_rows > 0) {
    $theses_table_exists = true;
}

// Get real statistics from database
$stats = [
    'total_faculty' => 0,
    'total_students' => 0,
    'active_projects' => 0,
    'upcoming_defenses' => 4,
    'pending_reviews' => 0,
    'approved_this_sem' => 0
];

// Get total faculty
$faculty_query = "SELECT COUNT(*) as count FROM user_table WHERE role_id = 3 AND status = 'Active'";
$faculty_result = $conn->query($faculty_query);
$stats['total_faculty'] = ($faculty_result && $faculty_result->num_rows > 0) ? ($faculty_result->fetch_assoc())['count'] : 28;

// Get total students
$students_query = "SELECT COUNT(*) as count FROM user_table WHERE role_id = 2 AND status = 'Active'";
$students_result = $conn->query($students_query);
$stats['total_students'] = ($students_result && $students_result->num_rows > 0) ? ($students_result->fetch_assoc())['count'] : 342;

// Get theses statistics ONLY IF TABLE EXISTS
if ($theses_table_exists) {
    // Active projects
    $active_query = "SELECT COUNT(*) as count FROM theses WHERE status = 'In Progress' OR status = 'Ongoing'";
    $active_result = $conn->query($active_query);
    $stats['active_projects'] = ($active_result && $active_result->num_rows > 0) ? ($active_result->fetch_assoc())['count'] : 87;
    
    // Pending reviews
    $pending_query = "SELECT COUNT(*) as count FROM theses WHERE status = 'Pending' OR status = 'For Review'";
    $pending_result = $conn->query($pending_query);
    $stats['pending_reviews'] = ($pending_result && $pending_result->num_rows > 0) ? ($pending_result->fetch_assoc())['count'] : 23;
    
    // Approved this semester
    $approved_query = "SELECT COUNT(*) as count FROM theses WHERE status = 'Approved'";
    $approved_result = $conn->query($approved_query);
    $stats['approved_this_sem'] = ($approved_result && $approved_result->num_rows > 0) ? ($approved_result->fetch_assoc())['count'] : 15;
} else {
    // Use sample data if theses table doesn't exist
    $stats['active_projects'] = 87;
    $stats['pending_reviews'] = 23;
    $stats['approved_this_sem'] = 15;
}

// Get faculty workload from database (only if theses table exists)
$faculty_workload = [];

if ($theses_table_exists) {
    // Check if faculty_adviser_id column exists
    $check_advisor_column = $conn->query("SHOW COLUMNS FROM theses LIKE 'faculty_adviser_id'");
    if ($check_advisor_column && $check_advisor_column->num_rows > 0) {
        $workload_query = "SELECT u.user_id, CONCAT(u.first_name, ' ', u.last_name) as name, COUNT(t.thesis_id) as workload 
                           FROM user_table u 
                           LEFT JOIN theses t ON t.faculty_adviser_id = u.user_id 
                           WHERE u.role_id = 3 AND u.status = 'Active' 
                           GROUP BY u.user_id 
                           ORDER BY workload DESC 
                           LIMIT 5";
        $workload_result = $conn->query($workload_query);
        if ($workload_result && $workload_result->num_rows > 0) {
            while ($row = $workload_result->fetch_assoc()) {
                $faculty_workload[] = [
                    'name' => $row['name'],
                    'projects' => $row['workload'],
                    'initials' => strtoupper(substr($row['name'], 0, 1) . substr(explode(' ', $row['name'])[1] ?? '', 0, 1))
                ];
            }
        }
    }
}

// If no workload data, use sample
if (empty($faculty_workload)) {
    $faculty_workload = [
        ['name' => 'Dr. Maria Santos', 'projects' => 10, 'initials' => 'MS'],
        ['name' => 'Prof. Juan Cruz', 'projects' => 9, 'initials' => 'JC'],
        ['name' => 'Dr. Ana Reyes', 'projects' => 8, 'initials' => 'AR'],
        ['name' => 'Prof. Pedro Garcia', 'projects' => 7, 'initials' => 'PG'],
        ['name' => 'Dr. Lisa Villanueva', 'projects' => 6, 'initials' => 'LV']
    ];
}

// Monthly data for chart
$monthly_data = [3, 5, 8, 6, 9, 11, 12, 10, 16, 15, 18, 22];
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

if ($theses_table_exists) {
    $monthly_query = "SELECT MONTH(created_at) as month, COUNT(*) as count FROM theses WHERE YEAR(created_at) = YEAR(CURDATE()) GROUP BY MONTH(created_at)";
    $monthly_result = $conn->query($monthly_query);
    $monthly_counts = array_fill(1, 12, 0);
    if ($monthly_result && $monthly_result->num_rows > 0) {
        while ($row = $monthly_result->fetch_assoc()) {
            $monthly_counts[(int)$row['month']] = (int)$row['count'];
        }
        if (array_sum($monthly_counts) > 0) {
            $monthly_data = array_values($monthly_counts);
        }
    }
}

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
                <div class="status-labels">
                    <div class="status-label-item">
                        <span class="status-color pending"></span>
                        <span>Pending Review (<?= $stats['pending_reviews'] ?>)</span>
                    </div>
                    <div class="status-label-item">
                        <span class="status-color forwarded"></span>
                        <span>Forwarded to Dean (15)</span>
                    </div>
                    <div class="status-label-item">
                        <span class="status-color rejected"></span>
                        <span>Rejected (8)</span>
                    </div>
                </div>
            </div>
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

    <script>
        window.chartData = {
            status: {
                pending: <?= $stats['pending_reviews'] ?>,
                forwarded: 15,
                rejected: 8
            },
            monthly: <?= json_encode($monthly_data) ?>,
            months: <?= json_encode($months) ?>
        };
    </script>
    <script src="js/librarian_dashboard.js"></script>
</body>
</html>