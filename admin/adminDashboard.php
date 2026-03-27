<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION - CHECK IF USER IS LOGGED IN AND IS AN ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

// CHECK IF AUDIT_LOGS TABLE EXISTS, IF NOT CREATE IT
$check_audit_table = $conn->query("SHOW TABLES LIKE 'audit_logs'");
if (!$check_audit_table || $check_audit_table->num_rows == 0) {
    $create_audit_table = "
    CREATE TABLE IF NOT EXISTS audit_logs (
        audit_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action_type VARCHAR(255),
        table_name VARCHAR(100),
        record_id INT,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($create_audit_table);
}

// LOG ADMIN ACCESS (using audit_logs table)
$action_type = "Admin accessed dashboard";
$table_name = "user_table";
$record_id = $user_id;
$description = "Admin $fullName accessed the admin dashboard";

$log_query = "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)";
$log_stmt = $conn->prepare($log_query);
$log_stmt->bind_param("issis", $user_id, $action_type, $table_name, $record_id, $description);
$log_stmt->execute();
$log_stmt->close();

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

// DEFINE ALL DASHBOARDS
$dashboards = [
    1 => [
        'name' => 'Admin',
        'icon' => 'fa-user-shield',
        'color' => '#d32f2f',
        'folder' => 'admin',
        'file' => 'admindashboard.php',
        'role_id' => 1
    ],
    2 => [
        'name' => 'Student',
        'icon' => 'fa-user-graduate',
        'color' => '#1976d2',
        'folder' => 'student',
        'file' => 'student_dashboard.php',
        'role_id' => 2
    ],
    3 => [
        'name' => 'Faculty',
        'icon' => 'fa-chalkboard-user',
        'color' => '#388e3c',
        'folder' => 'faculty',
        'file' => 'facultyDashboard.php',
        'role_id' => 3
    ],
    4 => [
        'name' => 'Dean',
        'icon' => 'fa-user-tie',
        'color' => '#f57c00',
        'folder' => 'departmentDeanDashboard',
        'file' => 'dean.php',
        'role_id' => 4
    ],
    5 => [
        'name' => 'Librarian',
        'icon' => 'fa-book-reader',
        'color' => '#7b1fa2',
        'folder' => 'librarian',
        'file' => 'librarian_dashboard.php',
        'role_id' => 5
    ],
    6 => [
        'name' => 'Coordinator',
        'icon' => 'fa-clipboard-list',
        'color' => '#e67e22',
        'folder' => 'coordinator',
        'file' => 'coordinatorDashboard.php',
        'role_id' => 6
    ]
];

// GET USER STATISTICS FROM DATABASE
$stats = [];
foreach ($dashboards as $dashboard) {
    $role_id = $dashboard['role_id'];
    $query = "SELECT COUNT(*) as count FROM user_table WHERE role_id = $role_id AND status = 'Active'";
    $result = $conn->query($query);
    $stats[$dashboard['name']] = ($result && $result->num_rows > 0) ? ($result->fetch_assoc())['count'] : 0;
}

// Total users
$users_query = "SELECT COUNT(*) as count FROM user_table WHERE status = 'Active'";
$users_result = $conn->query($users_query);
$stats['Total Users'] = ($users_result && $users_result->num_rows > 0) ? ($users_result->fetch_assoc())['count'] : 0;

// GET ALL USERS LIST
$users_list = [];

// Check if created_at column exists for user list
$has_created_at = false;
$check_created_col = $conn->query("SHOW COLUMNS FROM user_table LIKE 'created_at'");
if ($check_created_col && $check_created_col->num_rows > 0) {
    $has_created_at = true;
}

if ($has_created_at) {
    $users_query = "SELECT user_id, first_name, last_name, email, role_id, status, created_at FROM user_table ORDER BY user_id DESC";
} else {
    $users_query = "SELECT user_id, first_name, last_name, email, role_id, status FROM user_table ORDER BY user_id DESC";
}
$users_result = $conn->query($users_query);

if ($users_result && $users_result->num_rows > 0) {
    while ($row = $users_result->fetch_assoc()) {
        $role_name = '';
        foreach ($dashboards as $dashboard) {
            if ($dashboard['role_id'] == $row['role_id']) {
                $role_name = $dashboard['name'];
                break;
            }
        }
        if ($role_name == '') $role_name = 'Unknown';
        
        $joined_date = 'Recently';
        if ($has_created_at && isset($row['created_at'])) {
            $joined_date = date('M d, Y', strtotime($row['created_at']));
        }
        
        $users_list[] = [
            'id' => $row['user_id'],
            'name' => $row['first_name'] . " " . $row['last_name'],
            'email' => $row['email'],
            'role' => $role_name,
            'role_id' => $row['role_id'],
            'status' => $row['status'],
            'joined' => $joined_date
        ];
    }
}

// GET LOGS FROM AUDIT_LOGS TABLE
$all_logs = [];
$logs_query = "SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50";
$logs_result = $conn->query($logs_query);
if ($logs_result && $logs_result->num_rows > 0) {
    while ($row = $logs_result->fetch_assoc()) {
        // Get username from user_table
        $username = 'System';
        if ($row['user_id']) {
            $user_log_query = "SELECT username FROM user_table WHERE user_id = ?";
            $user_log_stmt = $conn->prepare($user_log_query);
            $user_log_stmt->bind_param("i", $row['user_id']);
            $user_log_stmt->execute();
            $user_log_result = $user_log_stmt->get_result();
            if ($user_log_row = $user_log_result->fetch_assoc()) {
                $username = $user_log_row['username'];
            }
            $user_log_stmt->close();
        }
        
        $all_logs[] = [
            'id' => $row['audit_id'],
            'user' => $username,
            'action' => $row['action_type'],
            'table_name' => $row['table_name'],
            'record_id' => $row['record_id'],
            'details' => $row['description'],
            'time' => date('M d, Y h:i A', strtotime($row['created_at']))
        ];
    }
}

$pageTitle = "Admin Dashboard";
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
    <link rel="stylesheet" href="css/admindashboard.css">
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
                <input type="text" id="searchInput" placeholder="Search users, logs...">
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
            <div class="admin-label">ADMIN</div>
        </div>
        
        <div class="nav-menu">
            <a href="admindashboard.php" class="nav-item active">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item" id="usersMenuBtn">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="#" class="nav-item" id="logsMenuBtn">
                <i class="fas fa-history"></i>
                <span>Audit Logs</span>
            </a>
        </div>
        
        <!-- DASHBOARD LINKS IN SIDEBAR -->
        <div class="dashboard-links">
            <div class="dashboard-links-header">
                <i class="fas fa-chalkboard-user"></i>
                <span>Dashboards</span>
            </div>
            <?php foreach ($dashboards as $dashboard): ?>
            <a href="/ArchivingThesis/<?= $dashboard['folder'] ?>/<?= $dashboard['file'] ?>" class="dashboard-link" target="_blank">
                <i class="fas <?= $dashboard['icon'] ?>" style="color: <?= $dashboard['color'] ?>"></i>
                <span><?= $dashboard['name'] ?> Dashboard</span>
                <i class="fas fa-external-link-alt link-icon"></i>
            </a>
            <?php endforeach; ?>
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
        <!-- WELCOME BANNER -->
        <div class="welcome-banner">
            <div class="welcome-info">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($first_name) ?>! • System Overview</p>
            </div>
            <div class="admin-info">
                <div class="admin-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="admin-since">Admin since <?= $user_created ?></div>
            </div>
        </div>

        <!-- STATS CARDS - User Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-details">
                    <h3><?= number_format($stats['Total Users']) ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="stat-details">
                    <h3><?= number_format($stats['Student']) ?></h3>
                    <p>Students</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
                <div class="stat-details">
                    <h3><?= number_format($stats['Faculty']) ?></h3>
                    <p>Faculty</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon secondary"><i class="fas fa-user-tie"></i></div>
                <div class="stat-details">
                    <h3><?= number_format($stats['Dean']) ?></h3>
                    <p>Deans</p>
                </div>
            </div>
        </div>

        <!-- SECOND ROW STATS -->
        <div class="stats-grid-second">
            <div class="stat-card-small">
                <div class="stat-icon-small"><i class="fas fa-book-reader"></i></div>
                <div class="stat-details-small">
                    <h4><?= number_format($stats['Librarian']) ?></h4>
                    <p>Librarians</p>
                </div>
            </div>
            <div class="stat-card-small">
                <div class="stat-icon-small"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-details-small">
                    <h4><?= number_format($stats['Coordinator']) ?></h4>
                    <p>Coordinators</p>
                </div>
            </div>
            <div class="stat-card-small">
                <div class="stat-icon-small"><i class="fas fa-user-shield"></i></div>
                <div class="stat-details-small">
                    <h4><?= number_format($stats['Admin']) ?></h4>
                    <p>Admins</p>
                </div>
            </div>
            <div class="stat-card-small">
                <div class="stat-icon-small"><i class="fas fa-chart-line"></i></div>
                <div class="stat-details-small">
                    <h4><?= number_format(count($all_logs)) ?></h4>
                    <p>Total Logs</p>
                </div>
            </div>
        </div>

        <!-- CHARTS SECTION -->
        <div class="charts-row">
            <!-- User Distribution Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-pie"></i> User Distribution by Role</h3>
                <div class="chart-container">
                    <canvas id="userDistributionChart"></canvas>
                </div>
            </div>
            
            <!-- Monthly User Registration Chart -->
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> User Registration Trend</h3>
                <div class="chart-container">
                    <canvas id="registrationChart"></canvas>
                </div>
            </div>
        </div>

        <!-- USERS SECTION -->
        <div id="usersSection" class="users-section">
            <div class="section-header">
                <h2><i class="fas fa-users"></i> All Users</h2>
                <button class="add-user-btn"><i class="fas fa-plus"></i> Add New User</button>
            </div>
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </thead>
                    <tbody>
                        <?php foreach ($users_list as $user): ?>
                        <tr>
                            <td>#<?= $user['id'] ?>  </td>
                            <td>
                                <div class="user-name-cell">
                                    <div class="user-avatar-small"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
                                    <span><?= htmlspecialchars($user['name']) ?></span>
                                </div>
                             </td>
                             <td><?= htmlspecialchars($user['email']) ?>   </td>
                             <td>
                                <?php 
                                    $dashboard_color = '';
                                    foreach ($dashboards as $dash) {
                                        if ($dash['name'] == $user['role']) {
                                            $dashboard_color = $dash['color'];
                                            break;
                                        }
                                    }
                                ?>
                                <span class="role-badge" style="background: <?= $dashboard_color ?>20; color: <?= $dashboard_color ?>">
                                    <i class="fas <?= $dashboards[$user['role_id']]['icon'] ?? 'fa-user' ?>"></i>
                                    <?= $user['role'] ?>
                                </span>
                             </td>
                             <td><span class="status-badge <?= strtolower($user['status']) ?>"><?= $user['status'] ?></span></td>
                             <td><?= $user['joined'] ?></td>
                             <td>
                                <button class="action-btn edit" title="Edit"><i class="fas fa-edit"></i></button>
                                <button class="action-btn delete" title="Delete"><i class="fas fa-trash"></i></button>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                 </table>
            </div>
        </div>

        <!-- AUDIT LOGS SECTION -->
        <div id="logsSection" class="logs-section" style="display: none;">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Audit Logs</h2>
                <button class="refresh-logs" id="refreshLogsBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <div class="table-responsive">
                <table class="logs-table">
                    <thead>
                         <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>Record ID</th>
                            <th>Details</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($all_logs)): ?>
                        <tr><td colspan="6" style="text-align: center;">No audit logs found</td></tr>
                        <?php else: ?>
                        <?php foreach ($all_logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['user']) ?></td>
                            <td><span class="action-badge"><?= htmlspecialchars($log['action']) ?></span></td>
                            <td><?= htmlspecialchars($log['table_name']) ?></td>
                            <td>#<?= $log['record_id'] ?></td>
                            <td><?= htmlspecialchars($log['details']) ?></td>
                            <td><?= $log['time'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        window.userData = {
            dashboards: <?= json_encode($dashboards) ?>,
            stats: {
                students: <?= $stats['Student'] ?? 0 ?>,
                faculty: <?= $stats['Faculty'] ?? 0 ?>,
                deans: <?= $stats['Dean'] ?? 0 ?>,
                librarians: <?= $stats['Librarian'] ?? 0 ?>,
                coordinators: <?= $stats['Coordinator'] ?? 0 ?>,
                admins: <?= $stats['Admin'] ?? 0 ?>
            }
        };
    </script>
    <script src="js/admindashboard.js"></script>
</body>
</html>