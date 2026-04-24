<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// Get user data
$user_query = "SELECT first_name, last_name FROM user_table WHERE user_id = ? LIMIT 1";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

$first_name = $user_data['first_name'] ?? '';
$last_name = $user_data['last_name'] ?? '';
$fullName = trim($first_name . " " . $last_name);
$initials = !empty($first_name) && !empty($last_name) ? strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) : "U";

// student_id = user_id
$student_id = $user_id;

// Get all projects/theses
$projects_query = "SELECT t.*, 
                   (SELECT COUNT(*) FROM feedback_table WHERE thesis_id = t.thesis_id) as feedback_count
                   FROM thesis_table t
                   WHERE t.student_id = ?
                   ORDER BY t.date_submitted DESC";
$projects_stmt = $conn->prepare($projects_query);
$projects_stmt->bind_param("i", $student_id);
$projects_stmt->execute();
$projects_result = $projects_stmt->get_result();

$projects = [];
while ($row = $projects_result->fetch_assoc()) {
    if (!isset($row['status']) || $row['status'] === null) {
        $row['status'] = ($row['is_archived'] == 1) ? 'archived' : 'pending';
    }
    $projects[] = $row;
}
$projects_stmt->close();

// Helper functions
function getStatusClass($status) {
    $status = strtolower((string)$status);
    switch ($status) {
        case 'pending': return 'status-pending';
        case 'pending_coordinator': return 'status-pending-coordinator';
        case 'forwarded_to_dean': return 'status-forwarded';
        case 'approved': return 'status-approved';
        case 'rejected': return 'status-rejected';
        case 'archived': return 'status-archived';
        default: return 'status-pending';
    }
}

function getStatusText($status) {
    $status = strtolower((string)$status);
    switch ($status) {
        case 'pending': return 'Pending Faculty Review';
        case 'pending_coordinator': return 'Pending Coordinator Review';
        case 'forwarded_to_dean': return 'Forwarded to Dean';
        case 'approved': return 'Approved';
        case 'rejected': return 'Rejected';
        case 'archived': return 'Archived';
        default: return ucfirst($status);
    }
}

function calculateProgress($status, $feedback_count) {
    $status_lower = strtolower((string)$status);
    if ($status_lower == 'archived') return 100;
    if ($status_lower == 'approved') return 100;
    if ($status_lower == 'rejected') return 0;
    if ($status_lower == 'forwarded_to_dean') return 85;
    if ($status_lower == 'pending_coordinator') return 70;
    if ($status_lower == 'pending') return 50 + min($feedback_count * 5, 20);
    return 30;
}

// Get notification count
$notificationCount = 0;
$notif_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
if ($notif_row = $notif_result->fetch_assoc()) {
    $notificationCount = $notif_row['count'];
}
$notif_stmt->close();

$pageTitle = "My Projects";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Theses Archiving System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #fef2f2;
            color: #1f2937;
            overflow-x: hidden;
        }

        body.dark-mode {
            background: #1a1a1a;
        }

        /* Layout */
        .layout {
            margin-left: 0;
        }

        .main-content {
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #fee2e2;
        }

        body.dark-mode .topbar {
            border-bottom-color: #991b1b;
        }

        .hamburger-menu {
            cursor: pointer;
            font-size: 1.2rem;
            color: #dc2626;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .hamburger-menu:hover {
            background: #fee2e2;
        }

        body.dark-mode .hamburger-menu:hover {
            background: #3d3d3d;
        }

        .topbar h1 {
            font-size: 1.5rem;
            color: #991b1b;
        }

        body.dark-mode .topbar h1 {
            color: #fecaca;
        }

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .notification-container {
            position: relative;
        }

        .notification-bell {
            position: relative;
            color: #dc2626;
            font-size: 1.2rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
            text-decoration: none;
        }

        .notification-bell:hover {
            background: #fee2e2;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.6rem;
            font-weight: 600;
            border-radius: 10px;
            padding: 0 5px;
            min-width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Avatar Dropdown */
        .avatar-dropdown {
            position: relative;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #dc2626, #991b1b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .avatar:hover {
            transform: scale(1.05);
        }

        .dropdown-content {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-width: 180px;
            display: none;
            z-index: 100;
            border: 1px solid #fee2e2;
            overflow: hidden;
        }

        .dropdown-content.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: #1f2937;
            font-size: 0.85rem;
            transition: background 0.2s;
        }

        .dropdown-content a:hover {
            background: #fef2f2;
        }

        .dropdown-content hr {
            margin: 0.25rem 0;
            border-color: #fee2e2;
        }

        body.dark-mode .dropdown-content {
            background: #2d2d2d;
            border-color: #991b1b;
        }

        body.dark-mode .dropdown-content a {
            color: #e5e7eb;
        }

        body.dark-mode .dropdown-content a:hover {
            background: #3d3d3d;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100%;
            background: linear-gradient(180deg, #991b1b 0%, #dc2626 100%);
            z-index: 1000;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.open {
            left: 0;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header h2 {
            color: white;
            margin: 0;
            font-size: 1.3rem;
        }

        .sidebar-header p {
            color: #fecaca;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            text-decoration: none;
            color: #fecaca;
            transition: all 0.2s;
            font-weight: 500;
        }

        .sidebar-nav .nav-link i {
            width: 22px;
        }

        .sidebar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .sidebar-footer {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            text-decoration: none;
            color: #fecaca;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        /* Overlay */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 999;
            display: none;
        }

        .overlay.show {
            display: block;
        }

        /* Projects Container */
        .projects-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Project Card */
        .project-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #fee2e2;
            transition: all 0.3s ease;
        }

        body.dark-mode .project-card {
            background: #2d2d2d;
            border-color: #991b1b;
        }

        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 38, 38, 0.1);
        }

        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            gap: 1rem;
        }

        .project-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
            flex: 1;
        }

        body.dark-mode .project-header h2 {
            color: #fecaca;
        }

        .feedback-badge {
            background: #fee2e2;
            color: #dc2626;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Status Badges */
        .status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-pending-coordinator {
            background: #fed7aa;
            color: #b45309;
        }

        .status-forwarded {
            background: #dbeafe;
            color: #2563eb;
        }

        .status-approved {
            background: #d1fae5;
            color: #059669;
        }

        .status-rejected {
            background: #fee2e2;
            color: #dc2626;
        }

        .status-archived {
            background: #e5e7eb;
            color: #6b7280;
        }

        body.dark-mode .status-pending {
            background: #78350f;
            color: #fbbf24;
        }

        body.dark-mode .status-pending-coordinator {
            background: #9a3412;
            color: #fdba74;
        }

        body.dark-mode .status-forwarded {
            background: #1e3a8a;
            color: #93c5fd;
        }

        body.dark-mode .status-approved {
            background: #064e3b;
            color: #6ee7b7;
        }

        body.dark-mode .status-rejected {
            background: #7f1d1d;
            color: #fca5a5;
        }

        body.dark-mode .status-archived {
            background: #374151;
            color: #9ca3af;
        }

        /* Progress Bar */
        .project-progress {
            margin: 1rem 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            color: #6b7280;
        }

        .progress-bar {
            height: 8px;
            background: #fee2e2;
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #dc2626, #991b1b);
            border-radius: 999px;
            width: 0%;
            transition: width 0.5s ease;
        }

        body.dark-mode .progress-bar {
            background: #3d3d3d;
        }

        /* Project Meta */
        .project-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
            margin: 1rem 0;
            font-size: 0.85rem;
            color: #4b5563;
        }

        .project-meta i {
            width: 20px;
            color: #dc2626;
        }

        body.dark-mode .project-meta {
            color: #cbd5e1;
        }

        /* Buttons */
        .project-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #991b1b;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fee2e2;
        }

        .btn-secondary:hover {
            background: #fee2e2;
        }

        body.dark-mode .btn-secondary {
            background: #3d3d3d;
            color: #fecaca;
            border-color: #991b1b;
        }

        /* No Projects */
        .no-projects {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 16px;
            border: 1px solid #fee2e2;
        }

        body.dark-mode .no-projects {
            background: #2d2d2d;
        }

        .no-projects i {
            font-size: 3rem;
            color: #dc2626;
            margin-bottom: 1rem;
        }

        .no-projects h3 {
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        body.dark-mode .no-projects h3 {
            color: #fecaca;
        }

        .no-projects p {
            color: #6b7280;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .project-header {
                flex-direction: column;
            }
            
            .project-meta {
                grid-template-columns: 1fr;
            }
            
            .project-actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
                width: 100%;
            }
            
            .topbar h1 {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .project-card {
                padding: 1rem;
            }
            
            .project-header h2 {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Theses Archive</h2>
        <p>Student Portal</p>
    </div>
    <nav class="sidebar-nav">
        <a href="student_dashboard.php" class="nav-link">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="projects.php" class="nav-link active">
            <i class="fas fa-folder-open"></i> My Projects
        </a>
        <a href="submission.php" class="nav-link">
            <i class="fas fa-upload"></i> Submit Thesis
        </a>
        <a href="archived.php" class="nav-link">
            <i class="fas fa-archive"></i> Archived Theses
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="profile.php" class="nav-link" style="margin-bottom: 0.5rem;">
            <i class="fas fa-user-circle"></i> Profile
        </a>
        <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<div class="layout">
    <main class="main-content">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="hamburger-menu" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>
                <h1>My Projects</h1>
            </div>
            <div class="user-info">
                <div class="notification-container">
                    <a href="notification.php" class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="notification-badge"><?= $notificationCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="avatar-dropdown">
                    <div class="avatar" id="avatarBtn">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <div class="dropdown-content" id="dropdownMenu">
                        <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <hr>
                        <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="projects-container">
            <?php if (empty($projects)): ?>
                <div class="no-projects">
                    <i class="fas fa-folder-open"></i>
                    <h3>No Projects Yet</h3>
                    <p>You haven't submitted any thesis projects yet.</p>
                    <a href="submission.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-upload"></i> Submit Your First Thesis
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($projects as $project): 
                    $progress = calculateProgress($project['status'], $project['feedback_count'] ?? 0);
                    $statusClass = getStatusClass($project['status']);
                    $statusText = getStatusText($project['status']);
                ?>
                    <div class="project-card" id="project-<?= $project['thesis_id'] ?>">
                        <div class="project-header">
                            <h2>
                                <?= htmlspecialchars($project['title']) ?>
                                <?php if (($project['feedback_count'] ?? 0) > 0): ?>
                                    <span class="feedback-badge">
                                        <i class="fas fa-comment"></i> <?= $project['feedback_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </h2>
                            <span class="status <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>

                        <div class="project-progress">
                            <div class="progress-label">
                                <span>Overall Progress</span>
                                <span><?= $progress ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $progress ?>%;"></div>
                            </div>
                        </div>

                        <div class="project-meta">
                            <div><i class="fas fa-user-tie"></i> <strong>Adviser:</strong> <?= htmlspecialchars($project['adviser'] ?? 'Not Assigned') ?></div>
                            <div><i class="fas fa-tags"></i> <strong>Keywords:</strong> <?= htmlspecialchars($project['keywords'] ?? 'None') ?></div>
                            <div><i class="fas fa-building"></i> <strong>Department:</strong> <?= htmlspecialchars($project['department'] ?? 'N/A') ?></div>
                            <div><i class="fas fa-calendar"></i> <strong>Year:</strong> <?= htmlspecialchars($project['year'] ?? 'N/A') ?></div>
                            <div><i class="fas fa-calendar-alt"></i> <strong>Submitted:</strong> <?= date('F d, Y', strtotime($project['date_submitted'])) ?></div>
                        </div>

                        <div class="project-actions">
                            <a href="view_project.php?id=<?= $project['thesis_id'] ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if (!empty($project['file_path'])): ?>
                                <a href="../<?= htmlspecialchars($project['file_path']) ?>" class="btn btn-secondary" download>
                                    <i class="fas fa-download"></i> Download Manuscript
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
// DOM Elements
const hamburgerBtn = document.getElementById('hamburgerBtn');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const avatarBtn = document.getElementById('avatarBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

// Sidebar Functions
function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

function toggleSidebar(e) {
    e.stopPropagation();
    if (sidebar.classList.contains('open')) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', toggleSidebar);
}

if (overlay) {
    overlay.addEventListener('click', closeSidebar);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        }
        if (dropdownMenu && dropdownMenu.classList.contains('show')) {
            dropdownMenu.classList.remove('show');
        }
    }
});

window.addEventListener('resize', function() {
    if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
        closeSidebar();
    }
});

// Profile Dropdown
function toggleProfileDropdown(e) {
    e.stopPropagation();
    dropdownMenu.classList.toggle('show');
}

function closeProfileDropdown(e) {
    const avatarDropdown = document.querySelector('.avatar-dropdown');
    if (avatarDropdown && !avatarDropdown.contains(e.target)) {
        if (dropdownMenu) {
            dropdownMenu.classList.remove('show');
        }
    }
}

if (avatarBtn) {
    avatarBtn.addEventListener('click', toggleProfileDropdown);
    document.addEventListener('click', closeProfileDropdown);
}

// Dark Mode
function initDarkMode() {
    const isDark = localStorage.getItem('darkMode') === 'true';
    if (isDark) {
        document.body.classList.add('dark-mode');
    }
}

initDarkMode();
console.log('Projects Page Initialized');
</script>
</body>
</html>