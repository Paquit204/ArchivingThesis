<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION - CHECK IF USER IS LOGGED IN
if (!isset($_SESSION['user_id'])) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

// CHECK USER ROLE FROM DATABASE (para sure)
$user_id = $_SESSION['user_id'];
$role_query = "SELECT role_id FROM user_table WHERE user_id = ?";
$role_stmt = $conn->prepare($role_query);
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();
$user_role = $role_result->fetch_assoc();

if (!$user_role) {
    // User not found in database
    session_destroy();
    header("Location: /ArchivingThesis/authentication/login.php?error=user_not_found");
    exit;
}

$role_id = $user_role['role_id'];

// ALLOWED ROLES FOR THIS PAGE (Dean only)
$allowed_roles = [4]; // role_id 4 = Dean

if (!in_array($role_id, $allowed_roles)) {
    // If user is not dean, redirect to their respective dashboard
    if ($role_id == 1) {
        header("Location: /ArchivingThesis/admin/admindashboard.php");
    } elseif ($role_id == 2) {
        header("Location: /ArchivingThesis/student/student_dashboard.php");
    } elseif ($role_id == 3) {
        header("Location: /ArchivingThesis/faculty/facultyDashboard.php");
    } elseif ($role_id == 5) {
        header("Location: /ArchivingThesis/librarian/librarian_dashboard.php");
    } else {
        // Invalid role
        session_destroy();
        header("Location: /ArchivingThesis/authentication/login.php?error=invalid_role");
    }
    exit;
}

// Set session role if not set
if (!isset($_SESSION['role'])) {
    if ($role_id == 1) $_SESSION['role'] = 'admin';
    elseif ($role_id == 2) $_SESSION['role'] = 'student';
    elseif ($role_id == 3) $_SESSION['role'] = 'faculty';
    elseif ($role_id == 4) $_SESSION['role'] = 'dean';
    elseif ($role_id == 5) $_SESSION['role'] = 'librarian';
}

// GET LOGGED-IN USER INFO FROM SESSION
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$fullName = $first_name . " " . $last_name;
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// GET USER DATA FROM DATABASE
$user_query = "SELECT user_id, username, email, first_name, last_name, role_id, status, created_at FROM user_table WHERE user_id = ?";
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
    $user_created = $user_data['created_at'];
}

// GET DEPARTMENT INFO
$department = "College of Arts and Sciences";
$dean_since = $user_created ? date('F Y', strtotime($user_created)) : date('F Y');

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

// GET STATISTICS FROM DATABASE
$stats = [];

// Total students
$students_query = "SELECT COUNT(*) as count FROM user_table WHERE role_id = 2 AND status = 'Active'";
$students_result = $conn->query($students_query);
$stats['total_students'] = ($students_result && $students_result->num_rows > 0) ? ($students_result->fetch_assoc())['count'] : 0;

// Total faculty
$faculty_query = "SELECT COUNT(*) as count FROM user_table WHERE role_id = 3 AND status = 'Active'";
$faculty_result = $conn->query($faculty_query);
$stats['total_faculty'] = ($faculty_result && $faculty_result->num_rows > 0) ? ($faculty_result->fetch_assoc())['count'] : 0;

// Check if theses table exists
$theses_table_exists = false;
$check_theses = $conn->query("SHOW TABLES LIKE 'theses'");
if ($check_theses && $check_theses->num_rows > 0) {
    $theses_table_exists = true;
    
    $projects_query = "SELECT COUNT(*) as count FROM theses";
    $projects_result = $conn->query($projects_query);
    $stats['total_projects'] = ($projects_result && $projects_result->num_rows > 0) ? ($projects_result->fetch_assoc())['count'] : 0;
    
    $pending_query = "SELECT COUNT(*) as count FROM theses WHERE status = 'Pending' OR status = 'For Review'";
    $pending_result = $conn->query($pending_query);
    $stats['pending_reviews'] = ($pending_result && $pending_result->num_rows > 0) ? ($pending_result->fetch_assoc())['count'] : 0;
    
    $completed_query = "SELECT COUNT(*) as count FROM theses WHERE status = 'Approved' OR status = 'Completed'";
    $completed_result = $conn->query($completed_query);
    $stats['completed_projects'] = ($completed_result && $completed_result->num_rows > 0) ? ($completed_result->fetch_assoc())['count'] : 0;
    
    $ongoing_query = "SELECT COUNT(*) as count FROM theses WHERE status = 'In Progress' OR status = 'Ongoing'";
    $ongoing_result = $conn->query($ongoing_query);
    $stats['ongoing_projects'] = ($ongoing_result && $ongoing_result->num_rows > 0) ? ($ongoing_result->fetch_assoc())['count'] : 0;
    
    $approved_query = "SELECT COUNT(*) as count FROM theses WHERE status = 'Approved' AND YEAR(created_at) = YEAR(CURDATE()) AND QUARTER(created_at) = QUARTER(CURDATE())";
    $approved_result = $conn->query($approved_query);
    $stats['theses_approved'] = ($approved_result && $approved_result->num_rows > 0) ? ($approved_result->fetch_assoc())['count'] : 0;
    
    $archived_query = "SELECT COUNT(*) as count FROM theses WHERE status = 'Archived'";
    $archived_result = $conn->query($archived_query);
    $stats['archived_count'] = ($archived_result && $archived_result->num_rows > 0) ? ($archived_result->fetch_assoc())['count'] : 0;
}

// GET FACULTY MEMBERS
$faculty_members = [];
$faculty_query = "SELECT user_id, first_name, last_name, email, status FROM user_table WHERE role_id = 3 ORDER BY first_name ASC LIMIT 6";
$faculty_result = $conn->query($faculty_query);
if ($faculty_result && $faculty_result->num_rows > 0) {
    while ($row = $faculty_result->fetch_assoc()) {
        $projects_count = 0;
        if ($theses_table_exists) {
            $check_advisor = $conn->query("SHOW COLUMNS FROM theses LIKE 'faculty_adviser_id'");
            if ($check_advisor && $check_advisor->num_rows > 0) {
                $advisor_stmt = $conn->prepare("SELECT COUNT(*) as count FROM theses WHERE faculty_adviser_id = ?");
                $advisor_stmt->bind_param("i", $row['user_id']);
                $advisor_stmt->execute();
                $advisor_result = $advisor_stmt->get_result();
                if ($advisor_row = $advisor_result->fetch_assoc()) {
                    $projects_count = $advisor_row['count'];
                }
                $advisor_stmt->close();
            }
        }
        
        $faculty_members[] = [
            'id' => $row['user_id'],
            'name' => $row['first_name'] . " " . $row['last_name'],
            'specialization' => 'Faculty Member',
            'projects_supervised' => $projects_count,
            'status' => $row['status']
        ];
    }
}

// GET DEPARTMENT PROJECTS
$department_projects = [];
if ($theses_table_exists) {
    $projects_query = "SELECT thesis_id, title, student_name, adviser_name, created_at, status, defense_date FROM theses ORDER BY created_at DESC LIMIT 5";
    $projects_result = $conn->query($projects_query);
    if ($projects_result && $projects_result->num_rows > 0) {
        while ($row = $projects_result->fetch_assoc()) {
            $department_projects[] = [
                'id' => $row['thesis_id'],
                'title' => $row['title'],
                'student' => $row['student_name'] ?? 'Unknown',
                'adviser' => $row['adviser_name'] ?? 'Unknown',
                'submitted' => $row['created_at'],
                'status' => strtolower($row['status']),
                'defense_date' => $row['defense_date']
            ];
        }
    }
}

// GET UPCOMING DEFENSES
$upcoming_defenses = [];
if ($theses_table_exists) {
    $defenses_query = "SELECT thesis_id, title, student_name, defense_date, defense_time, panelists FROM theses WHERE defense_date >= CURDATE() AND status = 'Approved' ORDER BY defense_date ASC LIMIT 4";
    $defenses_result = $conn->query($defenses_query);
    if ($defenses_result && $defenses_result->num_rows > 0) {
        while ($row = $defenses_result->fetch_assoc()) {
            $upcoming_defenses[] = [
                'id' => $row['thesis_id'],
                'student' => $row['student_name'],
                'title' => $row['title'],
                'date' => $row['defense_date'],
                'time' => $row['defense_time'] ?? '10:00 AM',
                'panelists' => $row['panelists'] ?? 'To be announced'
            ];
        }
    }
}

// GET RECENT ACTIVITIES
$department_activities = [];
$check_activities = $conn->query("SHOW TABLES LIKE 'user_activities'");
if ($check_activities && $check_activities->num_rows > 0) {
    $activities_query = "SELECT * FROM user_activities ORDER BY created_at DESC LIMIT 6";
    $activities_result = $conn->query($activities_query);
    if ($activities_result && $activities_result->num_rows > 0) {
        while ($activity = $activities_result->fetch_assoc()) {
            $department_activities[] = [
                'icon' => 'check-circle',
                'description' => $activity['description'],
                'user' => $activity['user_name'] ?? 'System',
                'created_at' => date('M d, Y h:i A', strtotime($activity['created_at']))
            ];
        }
    }
}

// FACULTY WORKLOAD DATA FOR CHART
$workload_labels = [];
$workload_data = [];
if ($theses_table_exists) {
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
            $workload_labels[] = $row['name'];
            $workload_data[] = $row['workload'];
        }
    }
}

// If no workload data, use sample
if (empty($workload_labels)) {
    $workload_labels = ['Prof. Dela Cruz', 'Dr. Lopez', 'Prof. Reyes', 'Dr. Garcia', 'Prof. Santiago'];
    $workload_data = [8, 6, 4, 5, 7];
}

// STATUS DATA FOR CHART
$status_data = [
    'pending' => $stats['pending_reviews'] ?? 0,
    'in_progress' => $stats['ongoing_projects'] ?? 0,
    'completed' => $stats['completed_projects'] ?? 0,
    'archived' => $stats['archived_count'] ?? 0
];

// FACULTY WORKLOAD STATS
$workload_stats = [
    'max_supervised' => !empty($workload_data) ? max($workload_data) : 8,
    'avg_supervised' => !empty($workload_data) ? round(array_sum($workload_data) / count($workload_data), 1) : 5.5,
    'under_load' => 0,
    'over_load' => 0
];

foreach ($workload_data as $w) {
    if ($w < 3) $workload_stats['under_load']++;
    if ($w > 6) $workload_stats['over_load']++;
}

$pageTitle = "Department Dean Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Thesis Management System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/deanDashboard.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <header class="top-nav">
        <div class="nav-left">
            <button class="hamburger" id="hamburgerBtn"><span></span><span></span><span></span></button>
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="search-area"><i class="fas fa-search"></i><input type="text" placeholder="Search faculty, students, projects..."></div>
        </div>
        <div class="nav-right">
            <div class="notification-icon"><i class="far fa-bell"></i><?php if ($notificationCount > 0): ?><span class="notification-badge"><?= $notificationCount ?></span><?php endif; ?></div>
            <div class="profile-wrapper" id="profileWrapper">
                <div class="profile-trigger"><span class="profile-name"><?= htmlspecialchars($fullName) ?></span><div class="profile-avatar"><?= htmlspecialchars($initials) ?></div></div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="#"><i class="fas fa-cog"></i> Settings</a>
                    <hr>
                    <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="logo-container"><div class="logo">Thesis<span>Manager</span></div><div class="logo-sub">DEPARTMENT DEAN</div></div>
        <div class="nav-menu">
            <a href="deanDashboard.php" class="nav-item active"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="#" class="nav-item"><i class="fas fa-users"></i><span>Faculty</span></a>
            <a href="#" class="nav-item"><i class="fas fa-user-graduate"></i><span>Students</span></a>
            <a href="#" class="nav-item"><i class="fas fa-project-diagram"></i><span>Projects</span></a>
            <a href="#" class="nav-item"><i class="fas fa-calendar-check"></i><span>Defenses</span></a>
            <a href="#" class="nav-item"><i class="fas fa-archive"></i><span>Archive</span></a>
            <a href="#" class="nav-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
            <a href="#" class="nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
        </div>
        <div class="nav-footer">
            <div class="theme-toggle"><input type="checkbox" id="darkmode"><label for="darkmode" class="toggle-label"><i class="fas fa-sun"></i><i class="fas fa-moon"></i><span class="slider"></span></label></div>
            <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="dept-banner">
            <div class="dept-info"><h1><?= htmlspecialchars($department) ?></h1><p>Department Dashboard • Overview of faculty, students, and projects</p></div>
            <div class="dean-info"><div class="dean-name"><?= htmlspecialchars($fullName) ?></div><div class="dean-since">Dean since <?= htmlspecialchars($dean_since) ?></div></div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-user-graduate"></i></div><div class="stat-details"><h3><?= number_format($stats['total_students'] ?? 0) ?></h3><p>Students</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div><div class="stat-details"><h3><?= number_format($stats['total_faculty'] ?? 0) ?></h3><p>Faculty</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-project-diagram"></i></div><div class="stat-details"><h3><?= number_format($stats['total_projects'] ?? 0) ?></h3><p>Total Projects</p></div></div>
            <div class="stat-card"><div class="stat-icon secondary"><i class="fas fa-clock"></i></div><div class="stat-details"><h3><?= number_format($stats['pending_reviews'] ?? 0) ?></h3><p>Pending Reviews</p></div></div>
        </div>

        <div class="dept-stats">
            <div class="dept-stat-card"><div class="dept-stat-header"><i class="fas fa-check-circle"></i><span>Completed</span></div><div class="dept-stat-value"><?= number_format($stats['completed_projects'] ?? 0) ?></div><div class="dept-stat-label">theses & projects</div></div>
            <div class="dept-stat-card"><div class="dept-stat-header"><i class="fas fa-spinner"></i><span>Ongoing</span></div><div class="dept-stat-value"><?= number_format($stats['ongoing_projects'] ?? 0) ?></div><div class="dept-stat-label">active projects</div></div>
            <div class="dept-stat-card"><div class="dept-stat-header"><i class="fas fa-gavel"></i><span>Defenses</span></div><div class="dept-stat-value"><?= number_format(count($upcoming_defenses)) ?></div><div class="dept-stat-label">upcoming defenses</div></div>
            <div class="dept-stat-card"><div class="dept-stat-header"><i class="fas fa-check-double"></i><span>Approved</span></div><div class="dept-stat-value"><?= number_format($stats['theses_approved'] ?? 0) ?></div><div class="dept-stat-label">theses this sem</div></div>
        </div>

        <div class="charts-section">
            <div class="chart-card"><div class="chart-header"><h3>Project Status Distribution</h3></div><div class="chart-container"><canvas id="projectStatusChart"></canvas></div></div>
            <div class="chart-card"><div class="chart-header"><h3>Faculty Workload</h3><select id="workloadSelect"><option>This Semester</option><option>Last Semester</option></select></div><div class="chart-container"><canvas id="workloadChart"></canvas></div></div>
        </div>

        <div class="faculty-section">
            <div class="section-header"><h2 class="section-title">Department Faculty</h2><a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a></div>
            <div class="faculty-grid">
                <?php foreach (array_slice($faculty_members, 0, 6) as $faculty): ?>
                <div class="faculty-card">
                    <div class="faculty-header"><div class="faculty-avatar"><?= strtoupper(substr($faculty['name'], 0, 1) . substr(explode(' ', $faculty['name'])[1] ?? '', 0, 1)) ?></div><div><div class="faculty-name"><?= htmlspecialchars($faculty['name']) ?></div><div class="faculty-spec"><?= htmlspecialchars($faculty['specialization']) ?></div></div></div>
                    <div class="faculty-stats"><div class="faculty-stat"><div class="faculty-stat-value"><?= $faculty['projects_supervised'] ?></div><div class="faculty-stat-label">Projects</div></div><div class="faculty-stat"><div class="faculty-stat-value"><span class="status-badge <?= $faculty['status'] ?>"><?= ucfirst($faculty['status']) ?></span></div><div class="faculty-stat-label">Status</div></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="projects-section">
            <div class="section-header"><h2 class="section-title">Recent Department Projects</h2><a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a></div>
            <div class="table-responsive">
                <table><thead><tr><th>PROJECT TITLE</th><th>STUDENT</th><th>ADVISER</th><th>DEFENSE DATE</th><th>STATUS</th><th>ACTION</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($department_projects, 0, 5) as $project): ?>
                    <tr><td><?= htmlspecialchars($project['title']) ?></td><td><?= htmlspecialchars($project['student']) ?></td><td><?= htmlspecialchars($project['adviser']) ?></td><td><?= $project['defense_date'] ? date('M d, Y', strtotime($project['defense_date'])) : 'Not scheduled' ?></td><td><div class="status"><span class="status-dot <?= $project['status'] ?>"></span><span class="status-text"><?= ucfirst(str_replace('-', ' ', $project['status'])) ?></span></div></td><td><a href="#" class="btn-view"><i class="fas fa-eye"></i> View</a></td></tr>
                    <?php endforeach; ?>
                </tbody></table>
            </div>
        </div>

        <div class="defenses-section">
            <div class="section-header"><h2 class="section-title">Upcoming Thesis Defenses</h2><a href="#" class="view-all">Schedule New <i class="fas fa-plus"></i></a></div>
            <?php foreach ($upcoming_defenses as $defense): ?>
            <div class="defense-item">
                <div class="defense-date-box"><div class="defense-day"><?= date('d', strtotime($defense['date'])) ?></div><div class="defense-month"><?= strtoupper(date('M', strtotime($defense['date']))) ?></div></div>
                <div class="defense-details"><div class="defense-title"><?= htmlspecialchars($defense['title']) ?></div><div class="defense-meta"><span><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($defense['student']) ?></span><span><i class="far fa-clock"></i> <?= $defense['time'] ?></span></div><div class="defense-panel"><i class="fas fa-users"></i> Panel: <?= htmlspecialchars($defense['panelists']) ?></div></div>
                <a href="#" class="btn-view"><i class="fas fa-calendar-check"></i> Details</a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="bottom-grid">
            <div class="activities-section">
                <div class="section-header"><h2 class="section-title">Department Activities</h2><a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a></div>
                <div class="activities-list">
                    <?php foreach (array_slice($department_activities, 0, 5) as $activity): ?>
                    <div class="activity-item"><div class="activity-icon"><i class="fas fa-<?= $activity['icon'] ?>"></i></div><div class="activity-details"><div class="activity-text"><?= htmlspecialchars($activity['description']) ?></div><div class="activity-meta"><span><i class="far fa-clock"></i> <?= $activity['created_at'] ?></span><span class="activity-user"><i class="fas fa-user"></i> <?= htmlspecialchars($activity['user']) ?></span></div></div></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="workload-section">
                <div class="section-header"><h2 class="section-title">Faculty Workload Summary</h2><a href="#" class="view-all">Details <i class="fas fa-arrow-right"></i></a></div>
                <div class="workload-item"><span class="workload-label">Average Projects per Faculty</span><span class="workload-value"><?= $workload_stats['avg_supervised'] ?></span></div>
                <div class="workload-item"><span class="workload-label">Maximum Projects Supervised</span><span class="workload-value"><?= $workload_stats['max_supervised'] ?></span></div>
                <div class="workload-item"><span class="workload-label">Faculty Under Load (&lt; 3 projects)</span><span class="workload-value"><?= $workload_stats['under_load'] ?></span></div>
                <div class="workload-item"><span class="workload-label">Faculty Over Load (&gt; 6 projects)</span><span class="workload-value"><?= $workload_stats['over_load'] ?></span></div>
                <div style="margin-top: 20px;"><div style="display: flex; justify-content: space-between; margin-bottom: 5px;"><span class="workload-label">Workload Distribution</span><span class="workload-value">70%</span></div><div class="progress-bar"><div class="progress-fill" style="width: 70%;"></div></div></div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="#" class="quick-action-btn"><i class="fas fa-calendar-plus"></i><span>Schedule Defense</span></a>
            <a href="#" class="quick-action-btn"><i class="fas fa-file-pdf"></i><span>Department Report</span></a>
            <a href="#" class="quick-action-btn"><i class="fas fa-chart-line"></i><span>View Analytics</span></a>
            <a href="#" class="quick-action-btn"><i class="fas fa-user-plus"></i><span>Add Faculty</span></a>
            <a href="#" class="quick-action-btn"><i class="fas fa-envelope"></i><span>Announcement</span></a>
        </div>
    </main>

    <script>
        window.chartData = {
            status: { pending: <?= $status_data['pending'] ?? 0 ?>, in_progress: <?= $status_data['in_progress'] ?? 0 ?>, completed: <?= $status_data['completed'] ?? 0 ?>, archived: <?= $status_data['archived'] ?? 0 ?> },
            workload_labels: <?= json_encode($workload_labels) ?>,
            workload_data: <?= json_encode($workload_data) ?>
        };
    </script>
    <script src="js/deanDashboard.js"></script>
</body>
</html>