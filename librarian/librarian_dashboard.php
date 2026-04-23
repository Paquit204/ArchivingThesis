<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'librarian') {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$fullName = $first_name . " " . $last_name;
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// GET USER DATA
$user_query = "SELECT first_name, last_name, email FROM user_table WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();
$user_stmt->close();

if ($user_data) {
    $first_name = $user_data['first_name'];
    $last_name = $user_data['last_name'];
    $fullName = $first_name . " " . $last_name;
    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
}

$librarian_since = date('F Y');

// GET FILTERS FROM URL
$selected_department = isset($_GET['department']) ? $_GET['department'] : '';
$selected_year = isset($_GET['year']) ? $_GET['year'] : '';
$selected_sort = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';

// Department list - fixed to only 5 departments
$departments = ['BSIT', 'BSCRIM', 'BSHTM', 'BSED', 'BSBA'];

// Department colors
$dept_colors = [
    'BSIT' => '#3b82f6',
    'BSCRIM' => '#10b981',
    'BSHTM' => '#f59e0b',
    'BSED' => '#8b5cf6',
    'BSBA' => '#ef4444'
];
$default_color = '#6b7280';

// GET PENDING FOR ARCHIVING (theses that are NOT archived - is_archived = 0)
$pending_theses = [];
$pending_query = "SELECT t.*, u.first_name, u.last_name, u.email 
                  FROM thesis_table t
                  JOIN user_table u ON t.student_id = u.user_id
                  WHERE (t.is_archived = 0 OR t.is_archived IS NULL)
                  AND t.department IN ('BSIT', 'BSCRIM', 'BSHTM', 'BSED', 'BSBA')
                  ORDER BY t.date_submitted DESC";
$pending_result = $conn->query($pending_query);
if ($pending_result && $pending_result->num_rows > 0) {
    while ($row = $pending_result->fetch_assoc()) {
        $pending_theses[] = $row;
    }
}

// BUILD QUERY FOR ARCHIVED THESES WITH FILTERS
$archived_query = "SELECT t.*, u.first_name, u.last_name, u.email 
                   FROM thesis_table t
                   JOIN user_table u ON t.student_id = u.user_id
                   WHERE t.is_archived = 1
                   AND t.department IN ('BSIT', 'BSCRIM', 'BSHTM', 'BSED', 'BSBA')";

if (!empty($selected_department)) {
    $archived_query .= " AND t.department = '" . $conn->real_escape_string($selected_department) . "'";
}

if (!empty($selected_year)) {
    $archived_query .= " AND YEAR(t.date_submitted) = '" . $conn->real_escape_string($selected_year) . "'";
}

switch ($selected_sort) {
    case 'date_asc':
        $archived_query .= " ORDER BY t.archived_date ASC";
        break;
    case 'title_asc':
        $archived_query .= " ORDER BY t.title ASC";
        break;
    case 'title_desc':
        $archived_query .= " ORDER BY t.title DESC";
        break;
    default:
        $archived_query .= " ORDER BY t.archived_date DESC";
        break;
}

$archived_result = $conn->query($archived_query);
$archived_theses = [];
if ($archived_result && $archived_result->num_rows > 0) {
    while ($row = $archived_result->fetch_assoc()) {
        $archived_theses[] = $row;
    }
}

// GET UNIQUE YEARS FOR FILTER
$years = [];
$year_query = "SELECT DISTINCT YEAR(date_submitted) as year 
               FROM thesis_table 
               WHERE date_submitted IS NOT NULL 
               AND department IN ('BSIT', 'BSCRIM', 'BSHTM', 'BSED', 'BSBA')
               ORDER BY year DESC";
$year_result = $conn->query($year_query);
if ($year_result && $year_result->num_rows > 0) {
    while ($row = $year_result->fetch_assoc()) {
        $years[] = $row['year'];
    }
}

$stats = [
    'pending_archive' => count($pending_theses),
    'archived' => count($archived_theses),
    'total_archived' => count($archived_theses)
];

$pageTitle = "Librarian Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Thesis Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fef2f2; color: #1f2937; overflow-x: hidden; }

        .top-nav {
            position: fixed; top: 0; right: 0; left: 0; height: 70px;
            background: white; display: flex; align-items: center;
            justify-content: space-between; padding: 0 32px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05); z-index: 99;
            border-bottom: 1px solid #fee2e2;
        }
        .nav-left { display: flex; align-items: center; gap: 24px; }
        .hamburger { display: flex; flex-direction: column; gap: 5px; width: 40px; height: 40px;
            background: #fef2f2; border: none; border-radius: 8px; cursor: pointer;
            align-items: center; justify-content: center; }
        .hamburger span { display: block; width: 22px; height: 2px; background: #dc2626; border-radius: 2px; }
        .hamburger:hover { background: #fee2e2; }
        .logo { font-size: 1.3rem; font-weight: 700; color: #991b1b; }
        .logo span { color: #dc2626; }
        
        .profile-wrapper { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 5px 10px; border-radius: 40px; }
        .profile-trigger:hover { background: #fee2e2; }
        .profile-name { font-weight: 500; font-size: 0.9rem; }
        .profile-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #dc2626, #991b1b); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .profile-dropdown { position: absolute; top: 55px; right: 0; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); min-width: 200px; display: none; overflow: hidden; z-index: 1000; border: 1px solid #ffcdd2; }
        .profile-dropdown.show { display: block; }
        .profile-dropdown a { display: flex; align-items: center; gap: 12px; padding: 12px 18px; text-decoration: none; color: #1f2937; transition: 0.2s; font-size: 0.85rem; }
        .profile-dropdown a:hover { background: #fef2f2; color: #dc2626; }
        .profile-dropdown hr { margin: 5px 0; border-color: #ffcdd2; }
        
        .sidebar { position: fixed; top: 0; left: -300px; width: 280px; height: 100%; background: linear-gradient(180deg, #991b1b 0%, #dc2626 100%); display: flex; flex-direction: column; z-index: 1000; transition: left 0.3s ease; }
        .sidebar.open { left: 0; }
        .logo-container { padding: 28px 24px; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .logo-container .logo { color: white; }
        .logo-container .logo span { color: #fecaca; }
        .logo-sub { font-size: 0.7rem; color: #fecaca; margin-top: 6px; }
        .nav-menu { flex: 1; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 12px; text-decoration: none; color: #fecaca; transition: all 0.2s; font-weight: 500; }
        .nav-item i { width: 22px; }
        .nav-item:hover { background: rgba(255,255,255,0.15); color: white; transform: translateX(5px); }
        .nav-item.active { background: rgba(255,255,255,0.2); color: white; }
        .nav-footer { padding: 20px 16px; border-top: 1px solid rgba(255,255,255,0.15); }
        .logout-btn { display: flex; align-items: center; gap: 12px; padding: 10px 12px; text-decoration: none; color: #fecaca; border-radius: 10px; }
        .logout-btn:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 999; display: none; }
        .sidebar-overlay.show { display: block; }
        
        .main-content { margin-left: 0; margin-top: 70px; padding: 32px; transition: margin-left 0.3s ease; }
        
        .librarian-banner { background: linear-gradient(135deg, #991b1b, #dc2626); border-radius: 24px; padding: 32px; margin-bottom: 32px; color: white; display: flex; justify-content: space-between; align-items: center; }
        .librarian-info h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 8px; }
        .librarian-info p { opacity: 0.9; font-size: 0.9rem; }
        .librarian-details { text-align: right; }
        .librarian-name { font-size: 1rem; font-weight: 600; margin-bottom: 4px; }
        .librarian-since { font-size: 0.7rem; opacity: 0.8; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .stat-card { background: white; border-radius: 20px; padding: 24px; display: flex; align-items: center; gap: 18px; border: 1px solid #ffcdd2; transition: all 0.3s; }
        .stat-icon { width: 55px; height: 55px; background: #fef2f2; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #dc2626; }
        .stat-details h3 { font-size: 1.8rem; font-weight: 700; color: #991b1b; margin-bottom: 5px; }
        .stat-details p { font-size: 0.8rem; color: #6b7280; }
        
        /* Pending Theses by Department Styles */
        .pending-card { background: white; border-radius: 24px; padding: 24px; margin-bottom: 32px; border: 1px solid #ffcdd2; }
        .pending-card h3 { font-size: 1rem; font-weight: 600; color: #991b1b; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .pending-dept-section {
            margin-bottom: 24px;
            background: #fef2f2;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #fee2e2;
        }
        .pending-dept-header {
            padding: 12px 20px;
            background: #fee2e2;
            border-bottom: 1px solid #fecaca;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pending-dept-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        .pending-dept-header h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .pending-dept-badge {
            background: #dc2626;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            margin-left: 10px;
        }
        .pending-dept-list {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .pending-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            background: white;
            border-radius: 12px;
            border: 1px solid #fee2e2;
            flex-wrap: wrap;
            gap: 12px;
        }
        .pending-item:hover {
            background: #fff5f5;
            transform: translateX(3px);
        }
        .pending-info {
            flex: 1;
        }
        .pending-title {
            font-weight: 600;
            font-size: 0.85rem;
            color: #1f2937;
            margin-bottom: 5px;
        }
        .pending-meta {
            font-size: 0.7rem;
            color: #6b7280;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .button-group {
            display: flex;
            gap: 8px;
        }
        .btn-view {
            background: #3b82f6;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
        }
        .btn-view:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        .btn-archive {
            background: #10b981;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
        }
        .btn-archive:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        /* Filter Bar */
        .filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 25px; border: 1px solid #ffcdd2; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .filter-select { padding: 10px 16px; border-radius: 40px; border: 1px solid #ffcdd2; background: #fef2f2; font-size: 0.85rem; cursor: pointer; min-width: 150px; }
        .sort-select { padding: 10px 16px; border-radius: 40px; border: 1px solid #ffcdd2; background: #fef2f2; font-size: 0.85rem; cursor: pointer; min-width: 180px; }
        .clear-btn { background: #fef2f2; color: #6b7280; border: 1px solid #ffcdd2; padding: 10px 20px; border-radius: 40px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block; }
        .clear-btn:hover { background: #fee2e2; }
        
        /* Archived Section */
        .archived-section { background: white; border-radius: 24px; padding: 24px; margin-bottom: 32px; border: 1px solid #ffcdd2; }
        .archived-section h3 { font-size: 1rem; font-weight: 600; color: #991b1b; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .archived-stats { font-size: 0.75rem; color: #6b7280; margin-left: 10px; }
        .table-responsive { overflow-x: auto; }
        .theses-table { width: 100%; border-collapse: collapse; }
        .theses-table th { text-align: left; padding: 12px; color: #6b7280; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border-bottom: 1px solid #ffcdd2; }
        .theses-table td { padding: 12px; border-bottom: 1px solid #fef2f2; font-size: 0.85rem; }
        .theses-table tr:hover td { background: #fef2f2; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 30px; font-size: 0.7rem; font-weight: 500; background: #d1ecf1; color: #0c5460; }
        
        .empty-state { text-align: center; padding: 40px; color: #9ca3af; }
        .empty-state i { font-size: 3rem; margin-bottom: 12px; color: #dc2626; }
        
        /* Modal Styles */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1100; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 24px; width: 500px; max-width: 90%; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h3 { font-size: 1.2rem; font-weight: 600; color: #991b1b; }
        .close-modal { font-size: 1.5rem; cursor: pointer; color: #9ca3af; }
        .close-modal:hover { color: #dc2626; }
        .modal-body { padding: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px; }
        .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #fee2e2; border-radius: 12px; font-size: 0.85rem; }
        .modal-footer { padding: 20px 24px; border-top: 1px solid #fee2e2; display: flex; justify-content: flex-end; gap: 12px; }
        .btn-cancel { padding: 10px 20px; background: #fef2f2; color: #6b7280; border: none; border-radius: 10px; cursor: pointer; }
        .btn-confirm { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
        .btn-confirm:hover { background: #059669; }
        
        @media (max-width: 768px) {
            .main-content { padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .pending-item { flex-direction: column; align-items: flex-start; }
            .button-group { width: 100%; justify-content: flex-start; }
            .librarian-banner { flex-direction: column; text-align: center; gap: 15px; }
            .librarian-details { text-align: center; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .filter-select, .sort-select, .clear-btn { width: 100%; }
            .pending-dept-header { flex-wrap: wrap; }
        }
        
        body.dark-mode { background: #1a1a1a; }
        body.dark-mode .top-nav, body.dark-mode .stat-card, body.dark-mode .pending-card, body.dark-mode .archived-section, body.dark-mode .modal-content, body.dark-mode .filter-bar { background: #2d2d2d; border-color: #991b1b; }
        body.dark-mode .stat-details h3 { color: #fecaca; }
        body.dark-mode .pending-dept-section { background: #3d3d3d; border-color: #991b1b; }
        body.dark-mode .pending-dept-header { background: #4a4a4a; border-bottom-color: #991b1b; }
        body.dark-mode .pending-dept-header h4 { color: #fecaca; }
        body.dark-mode .pending-item { background: #2d2d2d; border-color: #991b1b; }
        body.dark-mode .pending-title { color: #e5e7eb; }
        body.dark-mode .pending-meta { color: #cbd5e1; }
        body.dark-mode .pending-item:hover { background: #4a4a4a; }
        body.dark-mode .profile-dropdown { background: #2d2d2d; }
        body.dark-mode .profile-dropdown a { color: #e5e7eb; }
        body.dark-mode .form-group select, body.dark-mode .form-group textarea { background: #3d3d3d; border-color: #991b1b; color: white; }
        body.dark-mode .filter-select, body.dark-mode .sort-select { background: #3d3d3d; border-color: #991b1b; color: #e5e7eb; }
        body.dark-mode .clear-btn { background: #3d3d3d; border-color: #991b1b; color: #e5e7eb; }
        body.dark-mode .theses-table td { color: #e5e7eb; border-bottom-color: #3d3d3d; }
        body.dark-mode .theses-table tr:hover td { background: #3d3d3d; }
        body.dark-mode .status-badge { background: #1e3a5f; color: #60a5fa; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <header class="top-nav">
        <div class="nav-left">
            <button class="hamburger" id="hamburgerBtn"><span></span><span></span><span></span></button>
            <div class="logo">Thesis<span>Manager</span></div>
        </div>
        <div class="nav-right">
            <div class="profile-wrapper" id="profileWrapper">
                <div class="profile-trigger">
                    <span class="profile-name"><?= htmlspecialchars($fullName) ?></span>
                    <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="librarian_profile.php"><i class="fas fa-user"></i> Profile</a>
                    <hr>
                    <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="logo-container"><div class="logo">Thesis<span>Manager</span></div><div class="logo-sub">LIBRARIAN</div></div>
        <div class="nav-menu">
            <a href="librarian_dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="archived_list.php" class="nav-item"><i class="fas fa-folder-open"></i><span>Archived List</span></a>
        </div>
        <div class="nav-footer">
            <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="librarian-banner">
            <div class="librarian-info">
                <h1>Librarian Dashboard</h1>
                <p>Manage and archive approved theses</p>
            </div>
            <div class="librarian-details">
                <div class="librarian-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="librarian-since">Librarian since <?= htmlspecialchars($librarian_since) ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-details"><h3><?= number_format($stats['pending_archive']) ?></h3><p>Pending Archive</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-archive"></i></div><div class="stat-details"><h3><?= number_format($stats['archived']) ?></h3><p>Archived Theses</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-book"></i></div><div class="stat-details"><h3><?= number_format($stats['total_archived']) ?></h3><p>Total Archived</p></div></div>
        </div>

        <!-- PENDING FOR ARCHIVING SECTION - GROUPED BY DEPARTMENT -->
        <div class="pending-card">
            <h3><i class="fas fa-clock"></i> Theses Pending for Archiving (<?= count($pending_theses) ?>)</h3>
            <?php if (empty($pending_theses)): ?>
                <div class="empty-state"><i class="fas fa-check-circle"></i><p>No pending theses for archiving</p></div>
            <?php else: 
                // Group pending theses by department
                $pending_by_dept = [];
                foreach ($pending_theses as $thesis) {
                    $dept = $thesis['department'] ?? 'N/A';
                    if (!isset($pending_by_dept[$dept])) {
                        $pending_by_dept[$dept] = [];
                    }
                    $pending_by_dept[$dept][] = $thesis;
                }
                
                // Sort departments
                uksort($pending_by_dept, function($a, $b) use ($departments) {
                    $pos_a = array_search($a, $departments);
                    $pos_b = array_search($b, $departments);
                    if ($pos_a === false) $pos_a = 999;
                    if ($pos_b === false) $pos_b = 999;
                    return $pos_a - $pos_b;
                });
            ?>
                <?php foreach ($pending_by_dept as $dept => $theses): ?>
                <div class="pending-dept-section">
                    <div class="pending-dept-header">
                        <span class="pending-dept-dot" style="background: <?= $dept_colors[$dept] ?? $default_color ?>;"></span>
                        <h4><?= htmlspecialchars($dept) ?></h4>
                        <span class="pending-dept-badge"><?= count($theses) ?> pending</span>
                    </div>
                    <div class="pending-dept-list">
                        <?php foreach ($theses as $thesis): ?>
                        <div class="pending-item">
                            <div class="pending-info">
                                <div class="pending-title"><?= htmlspecialchars($thesis['title']) ?></div>
                                <div class="pending-meta">
                                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?></span>
                                    <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($thesis['date_submitted'])) ?></span>
                                </div>
                            </div>
                            <div class="button-group">
                                <a href="view_thesis.php?id=<?= $thesis['thesis_id'] ?>" class="btn-view"><i class="fas fa-eye"></i> View</a>
                                <button type="button" class="btn-archive" onclick="openArchiveModal(<?= $thesis['thesis_id'] ?>)"><i class="fas fa-archive"></i> Archive</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- FILTER BAR FOR ARCHIVED THESES -->
        <div class="filter-bar">
            <form method="GET" action="" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; width: 100%;">
                <select name="department" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept) ?>" <?= $selected_department == $dept ? 'selected' : '' ?>><?= htmlspecialchars($dept) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="year" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>" <?= $selected_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="date_desc" <?= $selected_sort == 'date_desc' ? 'selected' : '' ?>>Latest First</option>
                    <option value="date_asc" <?= $selected_sort == 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="title_asc" <?= $selected_sort == 'title_asc' ? 'selected' : '' ?>>Title A-Z</option>
                    <option value="title_desc" <?= $selected_sort == 'title_desc' ? 'selected' : '' ?>>Title Z-A</option>
                </select>
                
                <?php if (!empty($selected_department) || !empty($selected_year) || $selected_sort != 'date_desc'): ?>
                    <a href="librarian_dashboard.php" class="clear-btn"><i class="fas fa-times"></i> Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- ARCHIVED THESES SECTION -->
        <div class="archived-section">
            <h3>
                <i class="fas fa-archive"></i> Archived Theses 
                <span class="archived-stats">(<?= count($archived_theses) ?> found)</span>
            </h3>
            <?php if (empty($archived_theses)): ?>
                <div class="empty-state">
                    <i class="fas fa-archive"></i>
                    <p>No archived theses found</p>
                    <?php if (!empty($selected_department) || !empty($selected_year)): ?>
                        <p style="margin-top: 10px;"><a href="librarian_dashboard.php" style="color: #dc2626;">Clear filters</a> to see all archives</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="theses-table">
                        <thead>
                            <tr>
                                <th>Thesis Title</th>
                                <th>Author</th>
                                <th>Department</th>
                                <th>Archived Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </thead>
                        <tbody>
                            <?php foreach ($archived_theses as $thesis): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($thesis['title']) ?></strong></td>
                                <td><?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?></td>
                                <td><?= htmlspecialchars($thesis['department'] ?? 'N/A') ?></td>
                                <td><?= isset($thesis['archived_date']) ? date('M d, Y', strtotime($thesis['archived_date'])) : date('M d, Y', strtotime($thesis['date_submitted'])) ?></td>
                                <td><span class="status-badge"><i class="fas fa-check-circle"></i> Archived</span></td>
                                <td><a href="view_thesis.php?id=<?= $thesis['thesis_id'] ?>" class="btn-view"><i class="fas fa-eye"></i> View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Archive Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-archive"></i> Archive Thesis</h3>
                <span class="close-modal" onclick="closeArchiveModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="archive_thesis_id" value="">
                <div class="form-group">
                    <label>Retention Period</label>
                    <select id="retention_period">
                        <option value="5">5 years</option>
                        <option value="10">10 years</option>
                        <option value="20">20 years</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Archive Notes</label>
                    <textarea id="archive_notes" rows="3" placeholder="Optional notes about this archive..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeArchiveModal()">Cancel</button>
                <button type="button" class="btn-confirm" onclick="confirmArchive()"><i class="fas fa-archive"></i> Confirm Archive</button>
            </div>
        </div>
    </div>

    <script>
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const profileWrapper = document.getElementById('profileWrapper');
        const profileDropdown = document.getElementById('profileDropdown');
        const archiveModal = document.getElementById('archiveModal');

        function openSidebar() { 
            sidebar.classList.add('open'); 
            sidebarOverlay.classList.add('show'); 
            document.body.style.overflow = 'hidden'; 
        }
        
        function closeSidebar() { 
            sidebar.classList.remove('open'); 
            sidebarOverlay.classList.remove('show'); 
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
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }
        
        function toggleProfileDropdown(e) { 
            e.stopPropagation(); 
            profileDropdown.classList.toggle('show'); 
        }
        
        function closeProfileDropdown(e) { 
            if (!profileWrapper.contains(e.target)) {
                profileDropdown.classList.remove('show');
            } 
        }
        
        if (profileWrapper) { 
            profileWrapper.addEventListener('click', toggleProfileDropdown); 
            document.addEventListener('click', closeProfileDropdown); 
        }
        
        function openArchiveModal(id) {
            if (!id || id === '' || id === 'undefined') {
                alert('Error: Invalid thesis ID!');
                return;
            }
            document.getElementById('archive_thesis_id').value = id;
            document.getElementById('retention_period').value = '5';
            document.getElementById('archive_notes').value = '';
            if (archiveModal) {
                archiveModal.style.display = 'flex';
                archiveModal.classList.add('show');
            }
        }
        
        function closeArchiveModal() {
            if (archiveModal) {
                archiveModal.style.display = 'none';
                archiveModal.classList.remove('show');
                document.getElementById('archive_thesis_id').value = '';
            }
        }
        
        function confirmArchive() {
            const thesisId = document.getElementById('archive_thesis_id').value;
            const retentionPeriod = document.getElementById('retention_period').value;
            const archiveNotes = document.getElementById('archive_notes').value;
            
            if (!thesisId || thesisId === '' || thesisId === '0') {
                alert('Error: Invalid thesis ID!');
                return;
            }
            
            const confirmBtn = event.target;
            const originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Archiving...';
            confirmBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('thesis_id', thesisId);
            formData.append('retention_period', retentionPeriod);
            formData.append('archive_notes', archiveNotes);
            
            fetch('librarian_archive.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Error: ' + data.message);
                    confirmBtn.innerHTML = originalText;
                    confirmBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            });
        }
        
        window.onclick = function(event) {
            if (event.target === archiveModal) {
                closeArchiveModal();
            }
        }
        
        // Dark mode
        const isDark = localStorage.getItem('darkMode') === 'true';
        if (isDark) {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>