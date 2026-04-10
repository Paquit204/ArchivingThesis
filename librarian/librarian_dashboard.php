<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION - CHECK IF USER IS LOGGED IN AND IS A LIBRARIAN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'librarian') {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

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

$librarian_since = date('F Y');

// CREATE NOTIFICATIONS TABLE IF NOT EXISTS - USING 'status'
$check_notif_table = $conn->query("SHOW TABLES LIKE 'notifications'");
if (!$check_notif_table || $check_notif_table->num_rows == 0) {
    $create_notif_table = "
        CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            thesis_id INT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) DEFAULT 'info',
            link VARCHAR(255) NULL,
            status TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    $conn->query($create_notif_table);
}

// Determine the correct ID column name
$id_column = 'notification_id';
$check_id_col = $conn->query("SHOW COLUMNS FROM notifications LIKE 'id'");
if ($check_id_col && $check_id_col->num_rows > 0) {
    $id_column = 'id';
}

// GET NOTIFICATION COUNT - using 'status' (0 = unread, 1 = read)
$notificationCount = 0;
$notif_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND status = 0";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
if ($notif_row = $notif_result->fetch_assoc()) {
    $notificationCount = $notif_row['count'];
}
$notif_stmt->close();

// GET RECENT NOTIFICATIONS FOR DROPDOWN
$recentNotifications = [];
$notif_list_query = "SELECT $id_column as id, user_id, thesis_id, message, status, created_at, link FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$notif_list_stmt = $conn->prepare($notif_list_query);
$notif_list_stmt->bind_param("i", $user_id);
$notif_list_stmt->execute();
$notif_list_result = $notif_list_stmt->get_result();
while ($row = $notif_list_result->fetch_assoc()) {
    if ($row['thesis_id']) {
        $thesis_q = $conn->prepare("SELECT title FROM thesis_table WHERE thesis_id = ?");
        $thesis_q->bind_param("i", $row['thesis_id']);
        $thesis_q->execute();
        $thesis_result = $thesis_q->get_result();
        if ($thesis_row = $thesis_result->fetch_assoc()) {
            $row['thesis_title'] = $thesis_row['title'];
        }
        $thesis_q->close();
    }
    $recentNotifications[] = $row;
}
$notif_list_stmt->close();

// MARK NOTIFICATION AS READ (via AJAX)
if (isset($_POST['mark_read']) && isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    $update_query = "UPDATE notifications SET status = 1 WHERE $id_column = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $notif_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// MARK ALL NOTIFICATIONS AS READ
if (isset($_POST['mark_all_read'])) {
    $update_query = "UPDATE notifications SET status = 1 WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// ==================== HANDLE ARCHIVE SUBMISSION ====================
$archive_success = '';
$archive_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['archive_thesis'])) {
    $thesis_id = intval($_POST['thesis_id']);
    $retention_period = intval($_POST['retention_period']);
    $archive_notes = trim($_POST['archive_notes'] ?? '');
    
    // Get thesis details first
    $thesis_query = "SELECT title, student_id, adviser FROM thesis_table WHERE thesis_id = ?";
    $thesis_stmt = $conn->prepare($thesis_query);
    $thesis_stmt->bind_param("i", $thesis_id);
    $thesis_stmt->execute();
    $thesis_data = $thesis_stmt->get_result()->fetch_assoc();
    $thesis_stmt->close();
    
    if ($thesis_data) {
        // Update thesis status to 'archived'
        $update = "UPDATE thesis_table SET status = 'archived', archived_date = NOW() WHERE thesis_id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("i", $thesis_id);
        
        if ($stmt->execute()) {
            $archive_success = "Thesis \"" . htmlspecialchars($thesis_data['title']) . "\" has been archived successfully!";
            
            // Notify student
            if ($thesis_data['student_id']) {
                $student_msg = "📚 Your thesis \"" . $thesis_data['title'] . "\" has been ARCHIVED by Librarian " . $fullName;
                $notif = "INSERT INTO notifications (user_id, thesis_id, message, type, link, status, created_at) VALUES (?, ?, ?, 'student_archived', NULL, 0, NOW())";
                $notif_stmt = $conn->prepare($notif);
                $notif_stmt->bind_param("iis", $thesis_data['student_id'], $thesis_id, $student_msg);
                $notif_stmt->execute();
                $notif_stmt->close();
            }
        } else {
            $archive_error = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $archive_error = "Thesis not found.";
    }
}

// CHECK IF THESIS_TABLE EXISTS
$thesis_table_exists = false;
$check_thesis_table = $conn->query("SHOW TABLES LIKE 'thesis_table'");
if ($check_thesis_table && $check_thesis_table->num_rows > 0) {
    $thesis_table_exists = true;
}

// GET APPROVED THESES FOR ARCHIVING (status = 'approved')
$approved_theses = [];
if ($thesis_table_exists) {
    $approved_query = "SELECT thesis_id, title, adviser, department, year, status, date_submitted, file_path 
                       FROM thesis_table 
                       WHERE status = 'approved'
                       ORDER BY date_submitted DESC";
    $approved_result = $conn->query($approved_query);
    if ($approved_result && $approved_result->num_rows > 0) {
        while ($row = $approved_result->fetch_assoc()) {
            $approved_theses[] = [
                'id' => $row['thesis_id'],
                'title' => $row['title'],
                'author' => $row['adviser'] ?? 'Unknown',
                'department' => $row['department'] ?? 'N/A',
                'year' => $row['year'] ?? 'N/A',
                'date' => isset($row['date_submitted']) ? date('M d, Y', strtotime($row['date_submitted'])) : date('M d, Y'),
                'status' => $row['status'],
                'file_path' => $row['file_path']
            ];
        }
    }
}

// GET ARCHIVED THESES
$archived_theses = [];
if ($thesis_table_exists) {
    $archived_query = "SELECT thesis_id, title, adviser, department, year, status, date_submitted, file_path, archived_date 
                       FROM thesis_table 
                       WHERE status = 'archived'
                       ORDER BY archived_date DESC";
    $archived_result = $conn->query($archived_query);
    if ($archived_result && $archived_result->num_rows > 0) {
        while ($row = $archived_result->fetch_assoc()) {
            $archived_theses[] = [
                'id' => $row['thesis_id'],
                'title' => $row['title'],
                'author' => $row['adviser'] ?? 'Unknown',
                'department' => $row['department'] ?? 'N/A',
                'year' => $row['year'] ?? 'N/A',
                'date' => isset($row['archived_date']) ? date('M d, Y', strtotime($row['archived_date'])) : date('M d, Y'),
                'status' => $row['status']
            ];
        }
    }
}

$stats = [
    'pending_archive' => count($approved_theses),
    'archived' => count($archived_theses),
    'total_archived' => count($archived_theses)
];

$pageTitle = "Librarian Dashboard";
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
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
        .search-area { display: flex; align-items: center; background: #fef2f2; padding: 8px 16px; border-radius: 40px; gap: 10px; }
        .search-area i { color: #dc2626; }
        .search-area input { border: none; background: none; outline: none; font-size: 0.85rem; width: 200px; }
        
        .nav-right { display: flex; align-items: center; gap: 20px; position: relative; }
        
        .notification-container { position: relative; }
        .notification-icon { position: relative; cursor: pointer; width: 40px; height: 40px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
        .notification-icon:hover { background: #fee2e2; }
        .notification-icon i { font-size: 1.2rem; color: #dc2626; }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.6rem; font-weight: 600; min-width: 18px; height: 18px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; }
        
        .notification-dropdown { position: absolute; top: 55px; right: 0; width: 380px; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: none; overflow: hidden; z-index: 1000; border: 1px solid #ffcdd2; animation: fadeSlideDown 0.2s ease; }
        .notification-dropdown.show { display: block; }
        @keyframes fadeSlideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .notification-header { padding: 16px 20px; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center; }
        .notification-header h3 { font-size: 1rem; font-weight: 600; color: #991b1b; margin: 0; }
        .mark-all-read { font-size: 0.7rem; color: #dc2626; cursor: pointer; background: none; border: none; }
        .notification-list { max-height: 400px; overflow-y: auto; }
        .notification-item { display: flex; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #fef2f2; cursor: pointer; transition: background 0.2s; text-decoration: none; color: inherit; }
        .notification-item:hover { background: #fef2f2; }
        .notification-item.unread { background: #fff5f5; border-left: 3px solid #dc2626; }
        .notification-item.empty { justify-content: center; color: #9ca3af; cursor: default; }
        .notif-icon { width: 36px; height: 36px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; flex-shrink: 0; }
        .notif-content { flex: 1; }
        .notif-message { font-size: 0.8rem; color: #1f2937; margin-bottom: 4px; line-height: 1.4; }
        .notif-time { font-size: 0.65rem; color: #9ca3af; }
        .notification-footer { padding: 12px 20px; border-top: 1px solid #fee2e2; text-align: center; }
        .notification-footer a { color: #dc2626; text-decoration: none; font-size: 0.8rem; }
        
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
        
        .sidebar { position: fixed; top: 0; left: 0; width: 280px; height: 100%; background: linear-gradient(180deg, #991b1b 0%, #dc2626 100%); display: flex; flex-direction: column; z-index: 100; transform: translateX(-100%); transition: transform 0.3s ease; box-shadow: 2px 0 10px rgba(0,0,0,0.05); }
        .sidebar.open { transform: translateX(0); }
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
        .theme-toggle { margin-bottom: 12px; }
        .theme-toggle input { display: none; }
        .toggle-label { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .toggle-label i { font-size: 1rem; color: #fecaca; }
        .logout-btn { display: flex; align-items: center; gap: 12px; padding: 10px 12px; text-decoration: none; color: #fecaca; border-radius: 10px; }
        .logout-btn:hover { background: rgba(255,255,255,0.15); color: white; }
        
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 99; display: none; }
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
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(220, 38, 38, 0.1); }
        .stat-icon { width: 55px; height: 55px; background: #fef2f2; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: #dc2626; }
        .stat-details h3 { font-size: 1.8rem; font-weight: 700; color: #991b1b; margin-bottom: 5px; }
        .stat-details p { font-size: 0.8rem; color: #6b7280; }
        
        .pending-card { background: white; border-radius: 24px; padding: 24px; margin-bottom: 32px; border: 1px solid #ffcdd2; }
        .pending-card h3 { font-size: 1rem; font-weight: 600; color: #991b1b; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .pending-list { display: flex; flex-direction: column; gap: 12px; }
        .pending-item { display: flex; justify-content: space-between; align-items: center; padding: 16px; background: #fef2f2; border-radius: 16px; border-left: 3px solid #dc2626; flex-wrap: wrap; gap: 12px; }
        .pending-item:hover { background: #fee2e2; }
        .pending-info { flex: 1; }
        .pending-title { font-weight: 600; font-size: 0.9rem; color: #1f2937; margin-bottom: 5px; }
        .pending-meta { font-size: 0.7rem; color: #6b7280; display: flex; gap: 15px; flex-wrap: wrap; }
        .btn-archive { background: #10b981; color: white; padding: 8px 20px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; transition: all 0.2s; border: none; cursor: pointer; }
        .btn-archive:hover { background: #059669; transform: translateY(-2px); }
        .btn-view { background: #dc2626; color: white; padding: 6px 16px; border-radius: 20px; text-decoration: none; font-size: 0.75rem; font-weight: 500; transition: all 0.2s; }
        .btn-view:hover { background: #991b1b; transform: translateY(-2px); }
        
        .archived-section { background: white; border-radius: 24px; padding: 24px; margin-bottom: 32px; border: 1px solid #ffcdd2; }
        .archived-section h3 { font-size: 1rem; font-weight: 600; color: #991b1b; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .table-responsive { overflow-x: auto; }
        .theses-table { width: 100%; border-collapse: collapse; }
        .theses-table th { text-align: left; padding: 12px; color: #6b7280; font-weight: 600; font-size: 0.7rem; text-transform: uppercase; border-bottom: 1px solid #ffcdd2; }
        .theses-table td { padding: 12px; border-bottom: 1px solid #fef2f2; font-size: 0.85rem; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 30px; font-size: 0.7rem; font-weight: 500; }
        .status-badge.archived { background: #d1ecf1; color: #0c5460; }
        
        .empty-state { text-align: center; padding: 40px; color: #9ca3af; }
        .empty-state i { font-size: 3rem; margin-bottom: 12px; color: #dc2626; }
        
        .alert-success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        
        @media (max-width: 768px) {
            .main-content { padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .search-area, .profile-name { display: none; }
            .librarian-banner { flex-direction: column; text-align: center; gap: 15px; }
            .librarian-details { text-align: center; }
            .pending-item { flex-direction: column; align-items: flex-start; }
            .notification-dropdown { width: 320px; right: -10px; }
        }
        
        body.dark-mode { background: #1a1a1a; }
        body.dark-mode .top-nav, body.dark-mode .stat-card, body.dark-mode .pending-card, body.dark-mode .archived-section, body.dark-mode .notification-dropdown { background: #2d2d2d; border-color: #991b1b; }
        body.dark-mode .stat-details h3, body.dark-mode .section-title, body.dark-mode .notification-header h3, body.dark-mode .pending-title { color: #fecaca; }
        body.dark-mode .pending-meta, body.dark-mode .notif-message { color: #e5e7eb; }
        body.dark-mode .notification-item:hover, body.dark-mode .pending-item:hover { background: #3d3d3d; }
        body.dark-mode .notification-item.unread { background: #3a2a2a; }
        body.dark-mode .empty-state { color: #9ca3af; }
        body.dark-mode .alert-success { background: #155724; color: #d4edda; }
        body.dark-mode .alert-error { background: #721c24; color: #f8d7da; }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <header class="top-nav">
        <div class="nav-left">
            <button class="hamburger" id="hamburgerBtn"><span></span><span></span><span></span></button>
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="search-area"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Search..."></div>
        </div>
        <div class="nav-right">
            <div class="notification-container">
                <div class="notification-icon" id="notificationIcon">
                    <i class="far fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge" id="notificationBadge"><?= $notificationCount ?></span>
                    <?php endif; ?>
                </div>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <?php if ($notificationCount > 0): ?>
                            <button class="mark-all-read" id="markAllReadBtn">Mark all as read</button>
                        <?php endif; ?>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <?php if (empty($recentNotifications)): ?>
                            <div class="notification-item empty"><div class="notif-icon"><i class="far fa-bell-slash"></i></div><div class="notif-content"><div class="notif-message">No notifications yet</div></div></div>
                        <?php else: ?>
                            <?php foreach ($recentNotifications as $notif): ?>
                                <a href="<?= $notif['link'] ?? 'archive_thesis.php?id=' . $notif['thesis_id'] ?>" class="notification-item <?= $notif['status'] == 0 ? 'unread' : '' ?>" data-id="<?= $notif['id'] ?>">
                                    <div class="notif-icon"><?php if(strpos($notif['message'], 'approved') !== false) echo '<i class="fas fa-check-circle"></i>'; elseif(strpos($notif['message'], 'archive') !== false) echo '<i class="fas fa-archive"></i>'; else echo '<i class="fas fa-bell"></i>'; ?></div>
                                    <div class="notif-content">
                                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="notif-time"><i class="far fa-clock"></i> <?php $date = new DateTime($notif['created_at']); echo $date->format('M d, Y h:i A'); ?></div>
                                        <?php if (isset($notif['thesis_title'])): ?>
                                            <div class="notif-thesis" style="font-size:0.7rem; color:#6b7280; margin-top:4px;"><i class="fas fa-book"></i> <?= htmlspecialchars($notif['thesis_title']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notification-footer"><a href="notifications.php">View all notifications <i class="fas fa-arrow-right"></i></a></div>
                </div>
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

    <aside class="sidebar" id="sidebar">
        <div class="logo-container"><div class="logo">Thesis<span>Manager</span></div><div class="logo-sub">LIBRARIAN</div></div>
        <div class="nav-menu">
            <a href="librarian_dashboard.php" class="nav-item active"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="librarian_archive.php" class="nav-item"><i class="fas fa-archive"></i><span>Archive Theses</span></a>
            <a href="archived_list.php" class="nav-item"><i class="fas fa-folder-open"></i><span>Archived List</span></a>
        </div>
        <div class="nav-footer">
            <div class="theme-toggle"><input type="checkbox" id="darkmode"><label for="darkmode" class="toggle-label"><i class="fas fa-sun"></i><i class="fas fa-moon"></i></label></div>
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

        <?php if ($archive_success): ?>
            <div class="alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($archive_success) ?></div>
        <?php endif; ?>
        
        <?php if ($archive_error): ?>
            <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($archive_error) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-details"><h3><?= number_format($stats['pending_archive']) ?></h3><p>Pending Archive</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-archive"></i></div><div class="stat-details"><h3><?= number_format($stats['archived']) ?></h3><p>Archived Theses</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-book"></i></div><div class="stat-details"><h3><?= number_format($stats['total_archived']) ?></h3><p>Total Archived</p></div></div>
        </div>

        <!-- PENDING FOR ARCHIVING SECTION -->
        <div class="pending-card">
            <h3><i class="fas fa-clock"></i> Theses Pending for Archiving (<?= count($approved_theses) ?>)</h3>
            <?php if (empty($approved_theses)): ?>
                <div class="empty-state"><i class="fas fa-check-circle"></i><p>No pending theses for archiving</p></div>
            <?php else: ?>
                <div class="pending-list">
                    <?php foreach ($approved_theses as $thesis): ?>
                    <div class="pending-item">
                        <div class="pending-info">
                            <div class="pending-title"><?= htmlspecialchars($thesis['title']) ?></div>
                            <div class="pending-meta">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($thesis['author']) ?></span>
                                <span><i class="fas fa-calendar"></i> <?= $thesis['date'] ?></span>
                                <span><i class="fas fa-building"></i> <?= htmlspecialchars($thesis['department']) ?></span>
                            </div>
                        </div>
                        <button class="btn-archive" onclick="openArchiveModal(<?= $thesis['id'] ?>, '<?= htmlspecialchars(addslashes($thesis['title'])) ?>')">
                            <i class="fas fa-archive"></i> Archive Thesis
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RECENTLY ARCHIVED SECTION -->
        <div class="archived-section">
            <h3><i class="fas fa-archive"></i> Recently Archived Theses (<?= count($archived_theses) ?>)</h3>
            <?php if (empty($archived_theses)): ?>
                <div class="empty-state"><i class="fas fa-archive"></i><p>No archived theses yet</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="theses-table">
                        <thead>
                            <tr><th>Thesis Title</th><th>Author</th><th>Department</th><th>Archived Date</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($archived_theses, 0, 10) as $thesis): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($thesis['title']) ?></strong></td>
                                <td><?= htmlspecialchars($thesis['author']) ?></td>
                                <td><?= htmlspecialchars($thesis['department']) ?></td>
                                <td><?= $thesis['date'] ?></td>
                                <td><span class="status-badge archived">Archived</span></td>
                                <td><a href="view_thesis.php?id=<?= $thesis['id'] ?>" class="btn-view"><i class="fas fa-eye"></i> View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Archive Modal -->
    <div id="archiveModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1100; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; border-radius: 24px; width: 500px; max-width: 90%; animation: slideUp 0.3s ease;">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="color:#10b981;"><i class="fas fa-archive"></i> Archive Thesis</h3>
                <span class="close-modal" onclick="closeArchiveModal()" style="font-size: 1.5rem; cursor: pointer; color: #9ca3af;">&times;</span>
            </div>
            <form method="POST" action="">
                <div class="modal-body" style="padding: 24px;">
                    <input type="hidden" name="thesis_id" id="archive_thesis_id">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Thesis Title</label>
                        <p id="thesis_title_display" style="font-weight: bold; padding: 8px; background: #fef2f2; border-radius: 8px;"></p>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Retention Period</label>
                        <select name="retention_period" style="width: 100%; padding: 12px; border: 1px solid #fee2e2; border-radius: 12px;">
                            <option value="5">5 years</option>
                            <option value="10">10 years</option>
                            <option value="20">20 years</option>
                            <option value="50">50 years (Permanent)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 8px;">Archive Notes</label>
                        <textarea name="archive_notes" rows="3" placeholder="Optional notes about this archive..." style="width: 100%; padding: 12px; border: 1px solid #fee2e2; border-radius: 12px; resize: vertical;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 20px 24px; border-top: 1px solid #fee2e2; display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="button" class="btn-cancel" onclick="closeArchiveModal()" style="padding: 10px 20px; background: #fef2f2; color: #6b7280; border: none; border-radius: 10px; cursor: pointer;">Cancel</button>
                    <button type="submit" name="archive_thesis" class="btn-archive" style="background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 10px; cursor: pointer;">Confirm Archive</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal { display: none; }
        .modal.show { display: flex; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        body.dark-mode .modal-content { background: #2d2d2d; }
        body.dark-mode .modal-header { border-bottom-color: #991b1b; }
        body.dark-mode .form-group label { color: #e5e7eb; }
        body.dark-mode .form-group select, body.dark-mode .form-group textarea { background: #3d3d3d; border-color: #991b1b; color: white; }
        body.dark-mode #thesis_title_display { background: #3d3d3d; color: #e5e7eb; }
    </style>

    <script>
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const profileWrapper = document.getElementById('profileWrapper');
        const profileDropdown = document.getElementById('profileDropdown');
        const darkModeToggle = document.getElementById('darkmode');
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        const archiveModal = document.getElementById('archiveModal');

        function openSidebar() { sidebar.classList.add('open'); sidebarOverlay.classList.add('show'); document.body.style.overflow = 'hidden'; }
        function closeSidebar() { sidebar.classList.remove('open'); sidebarOverlay.classList.remove('show'); document.body.style.overflow = ''; }
        function toggleSidebar(e) { e.stopPropagation(); if (sidebar.classList.contains('open')) closeSidebar(); else openSidebar(); }
        
        if (hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
        
        document.addEventListener('keydown', function(e) { if (e.key === 'Escape') {
            if (sidebar.classList.contains('open')) closeSidebar();
            if (profileDropdown.classList.contains('show')) profileDropdown.classList.remove('show');
            if (notificationDropdown.classList.contains('show')) notificationDropdown.classList.remove('show');
            if (archiveModal && archiveModal.classList.contains('show')) closeArchiveModal();
        }});
        
        window.addEventListener('resize', function() { if (window.innerWidth > 768 && sidebar.classList.contains('open')) closeSidebar(); });
        
        function toggleProfileDropdown(e) { e.stopPropagation(); profileDropdown.classList.toggle('show'); if (notificationDropdown.classList.contains('show')) notificationDropdown.classList.remove('show'); }
        function closeProfileDropdown(e) { if (!profileWrapper.contains(e.target)) profileDropdown.classList.remove('show'); }
        if (profileWrapper) { profileWrapper.addEventListener('click', toggleProfileDropdown); document.addEventListener('click', closeProfileDropdown); }
        
        function toggleNotificationDropdown(e) { e.stopPropagation(); notificationDropdown.classList.toggle('show'); if (profileDropdown.classList.contains('show')) profileDropdown.classList.remove('show'); }
        function closeNotificationDropdown(e) { if (!notificationIcon.contains(e.target) && !notificationDropdown.contains(e.target)) notificationDropdown.classList.remove('show'); }
        if (notificationIcon) { notificationIcon.addEventListener('click', toggleNotificationDropdown); document.addEventListener('click', closeNotificationDropdown); }
        
        function markNotificationAsRead(notifId, element) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'mark_read=1&notif_id=' + notifId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    element.classList.remove('unread');
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        let c = parseInt(badge.textContent);
                        if (c > 0) { c--; if (c === 0) badge.style.display = 'none'; else badge.textContent = c; }
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        function markAllAsRead() {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'mark_all_read=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => item.classList.remove('unread'));
                    const badge = document.getElementById('notificationBadge');
                    if (badge) badge.style.display = 'none';
                    if (markAllReadBtn) markAllReadBtn.style.display = 'none';
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        function initNotifications() {
            document.querySelectorAll('.notification-item').forEach(item => {
                if (!item.classList.contains('empty')) {
                    item.addEventListener('click', function(e) {
                        if (e.target.closest('.notification-footer')) return;
                        const id = this.dataset.id;
                        if (id && this.classList.contains('unread')) markNotificationAsRead(id, this);
                    });
                }
            });
            if (markAllReadBtn) markAllReadBtn.addEventListener('click', function(e) { e.stopPropagation(); markAllAsRead(); });
        }
        
        function initDarkMode() {
            const isDark = localStorage.getItem('darkMode') === 'true';
            if (isDark) { document.body.classList.add('dark-mode'); if (darkModeToggle) darkModeToggle.checked = true; }
            if (darkModeToggle) {
                darkModeToggle.addEventListener('change', function() {
                    if (this.checked) { document.body.classList.add('dark-mode'); localStorage.setItem('darkMode', 'true'); }
                    else { document.body.classList.remove('dark-mode'); localStorage.setItem('darkMode', 'false'); }
                });
            }
        }
        
        // ========== FIXED: ARCHIVE MODAL FUNCTIONS ==========
        function openArchiveModal(id, title) {
            console.log("Opening archive modal for ID: " + id);
            console.log("Thesis Title: " + title);
            document.getElementById('archive_thesis_id').value = id;
            document.getElementById('thesis_title_display').textContent = title;
            if (archiveModal) {
                archiveModal.style.display = 'flex';
                archiveModal.classList.add('show');
            }
        }
        
        function closeArchiveModal() {
            if (archiveModal) {
                archiveModal.style.display = 'none';
                archiveModal.classList.remove('show');
            }
        }
        
        window.onclick = function(event) {
            if (event.target === archiveModal) {
                closeArchiveModal();
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            initDarkMode();
            initNotifications();
            console.log("Librarian Dashboard Loaded - Pending Theses: <?= count($approved_theses) ?>");
        });
    </script>
</body>
</html>