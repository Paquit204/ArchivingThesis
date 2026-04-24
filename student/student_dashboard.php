<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION
if (!isset($_SESSION['user_id'])) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// GET USER DATA
$user_query = "SELECT user_id, username, email, first_name, last_name, role_id, status FROM user_table WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

if (!$user_data) {
    session_destroy();
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

// CHECK IF ROLE IS STUDENT
if ($user_data['role_id'] != 2) {
    if ($user_data['role_id'] == 3) {
        header("Location: /ArchivingThesis/faculty/facultyDashboard.php");
    } else {
        header("Location: /ArchivingThesis/authentication/login.php");
    }
    exit;
}

$first_name = $user_data['first_name'] ?? '';
$last_name = $user_data['last_name'] ?? '';
$initials = !empty($first_name) && !empty($last_name) ? strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) : 
            (!empty($first_name) ? strtoupper(substr($first_name, 0, 1)) : "U");

$student_id = $user_id;

// ==================== GET THESIS COUNTS ====================
$thesis_table_exists = false;
$check_thesis = $conn->query("SHOW TABLES LIKE 'thesis_table'");
if ($check_thesis && $check_thesis->num_rows > 0) {
    $thesis_table_exists = true;
}

$pendingCount = $approvedCount = $rejectedCount = $archivedCount = $totalCount = 0;

if ($thesis_table_exists) {
    $pending_stmt = $conn->prepare("SELECT COUNT(*) as count FROM thesis_table WHERE student_id = ? AND (is_archived = 0 OR is_archived IS NULL) AND status = 'pending'");
    $pending_stmt->bind_param("i", $student_id);
    $pending_stmt->execute();
    $pendingCount = $pending_stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $pending_stmt->close();
    
    $approved_stmt = $conn->prepare("SELECT COUNT(*) as count FROM thesis_table WHERE student_id = ? AND status = 'approved'");
    $approved_stmt->bind_param("i", $student_id);
    $approved_stmt->execute();
    $approvedCount = $approved_stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $approved_stmt->close();
    
    $rejected_stmt = $conn->prepare("SELECT COUNT(*) as count FROM thesis_table WHERE student_id = ? AND status = 'rejected'");
    $rejected_stmt->bind_param("i", $student_id);
    $rejected_stmt->execute();
    $rejectedCount = $rejected_stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $rejected_stmt->close();
    
    $archived_stmt = $conn->prepare("SELECT COUNT(*) as count FROM thesis_table WHERE student_id = ? AND is_archived = 1");
    $archived_stmt->bind_param("i", $student_id);
    $archived_stmt->execute();
    $archivedCount = $archived_stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $archived_stmt->close();
    
    $total_stmt = $conn->prepare("SELECT COUNT(*) as count FROM thesis_table WHERE student_id = ?");
    $total_stmt->bind_param("i", $student_id);
    $total_stmt->execute();
    $totalCount = $total_stmt->get_result()->fetch_assoc()['count'] ?? 0;
    $total_stmt->close();
}

// Member since
$member_since = date('F Y');
$check_created = $conn->query("SHOW COLUMNS FROM user_table LIKE 'created_at'");
if ($check_created && $check_created->num_rows > 0) {
    $created_stmt = $conn->prepare("SELECT created_at FROM user_table WHERE user_id = ?");
    $created_stmt->bind_param("i", $user_id);
    $created_stmt->execute();
    $created_result = $created_stmt->get_result();
    if ($created_row = $created_result->fetch_assoc()) {
        $member_since = date('F Y', strtotime($created_row['created_at']));
    }
    $created_stmt->close();
}

// ==================== NOTIFICATION SYSTEM ====================
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    thesis_id INT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    link VARCHAR(255) NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id),
    INDEX (is_read)
)");

// Handle notification actions
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $notif_id = intval($_GET['mark_read']);
    $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $update_stmt->bind_param("ii", $notif_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    header("Location: student_dashboard.php?view=notifications");
    exit;
}

if (isset($_GET['mark_all_read'])) {
    $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    header("Location: student_dashboard.php?view=notifications");
    exit;
}

// AJAX handlers
if (isset($_POST['mark_read']) && isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $update_stmt->bind_param("ii", $notif_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

if (isset($_POST['mark_all_read'])) {
    $update_stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// GET NOTIFICATION COUNT
$unreadCount = 0;
$notif_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$unreadCount = $notif_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$notif_stmt->close();

// GET ALL NOTIFICATIONS
$allNotifications = [];
$notif_list = $conn->prepare("SELECT notification_id, user_id, thesis_id, message, type, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$notif_list->bind_param("i", $user_id);
$notif_list->execute();
$notif_result = $notif_list->get_result();
while ($row = $notif_result->fetch_assoc()) {
    if ($row['thesis_id'] && $thesis_table_exists) {
        $thesis_q = $conn->prepare("SELECT title FROM thesis_table WHERE thesis_id = ?");
        $thesis_q->bind_param("i", $row['thesis_id']);
        $thesis_q->execute();
        $thesis_title = $thesis_q->get_result()->fetch_assoc();
        $row['thesis_title'] = $thesis_title['title'] ?? '';
        $thesis_q->close();
    }
    $allNotifications[] = $row;
}
$notif_list->close();

// GET RECENT NOTIFICATIONS (for dropdown)
$recentNotifications = array_slice($allNotifications, 0, 5);

// GET RECENT FEEDBACK
$recentFeedback = [];
$check_feedback = $conn->query("SHOW TABLES LIKE 'feedback'");
if ($check_feedback && $check_feedback->num_rows > 0) {
    $feedback_query = "SELECT f.*, t.title as thesis_title, u.first_name as faculty_first, u.last_name as faculty_last 
                       FROM feedback f
                       JOIN thesis_table t ON f.thesis_id = t.thesis_id
                       JOIN user_table u ON f.faculty_id = u.user_id
                       WHERE t.student_id = ?
                       ORDER BY f.created_at DESC LIMIT 5";
    $feedback_stmt = $conn->prepare($feedback_query);
    $feedback_stmt->bind_param("i", $student_id);
    $feedback_stmt->execute();
    $feedback_result = $feedback_stmt->get_result();
    while ($row = $feedback_result->fetch_assoc()) {
        $recentFeedback[] = $row;
    }
    $feedback_stmt->close();
}

$chart_data = [
    'pending' => $pendingCount,
    'approved' => $approvedCount,
    'rejected' => $rejectedCount,
    'archived' => $archivedCount
];

// Determine which view to show
$current_view = isset($_GET['view']) && $_GET['view'] == 'notifications' ? 'notifications' : 'dashboard';
$pageTitle = $current_view == 'notifications' ? "My Notifications" : "Student Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?= htmlspecialchars($pageTitle) ?> | Thesis Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #000000;
            line-height: 1.5;
            transition: background 0.2s ease, color 0.2s ease;
        }
        body.dark-mode { background: #2d2d2d; color: #e0e0e0; }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #FE4853 0%, #732529 100%);
            color: white;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: left 0.3s ease;
            box-shadow: 5px 0 20px rgba(0,0,0,0.3);
        }
        .sidebar.show { left: 0; }
        .sidebar-header { padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .sidebar-header h2 { font-size: 1.5rem; margin-bottom: 0.25rem; color: white; }
        .sidebar-header p { font-size: 0.875rem; color: rgba(255,255,255,0.9); }
        .sidebar-nav { flex: 1; padding: 1.5rem 0.5rem; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
            font-weight: 500;
        }
        .nav-link i { width: 20px; color: white; }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,0.2); color: white; }
        .sidebar-footer { padding: 1.5rem; border-top: 1px solid rgba(255,255,255,0.2); }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.2); }
        .theme-toggle { margin-bottom: 1rem; }
        .theme-toggle input { display: none; }
        .toggle-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            background: rgba(255,255,255,0.2);
            border-radius: 30px;
            cursor: pointer;
            position: relative;
        }
        .toggle-label i { font-size: 1rem; color: white; }
        .slider {
            position: absolute;
            width: 50%;
            height: 80%;
            background: #732529;
            border-radius: 20px;
            transition: transform 0.3s;
            top: 10%;
            left: 0;
        }
        #darkmode:checked ~ .toggle-label .slider { transform: translateX(100%); }
        .overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        .overlay.show { display: block; }
        
        /* Main layout */
        .layout { min-height: 100vh; }
        .main-content { flex: 1; margin-left: 0; padding: 2rem; }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(110,110,110,0.1);
        }
        body.dark-mode .topbar { background: #3a3a3a; }
        .topbar h1 { font-size: 1.5rem; color: #732529; }
        body.dark-mode .topbar h1 { color: #FE4853; }
        .hamburger-menu {
            font-size: 1.5rem;
            cursor: pointer;
            color: #FE4853;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        .hamburger-menu:hover { background: rgba(254,72,83,0.1); }
        .user-info { display: flex; align-items: center; gap: 1.5rem; }
        
        /* Notifications Dropdown */
        .notification-container { position: relative; }
        .notification-bell {
            position: relative;
            font-size: 1.2rem;
            color: #6E6E6E;
            cursor: pointer;
            transition: color 0.3s;
        }
        .notification-bell:hover { color: #FE4853; }
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #FE4853;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }
        .notification-dropdown {
            position: absolute;
            top: 45px;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            display: none;
            z-index: 100;
            border: 1px solid #eee;
        }
        body.dark-mode .notification-dropdown { background: #3a3a3a; border-color: #6E6E6E; }
        .notification-dropdown.show { display: block; animation: fadeSlideDown 0.2s ease; }
        @keyframes fadeSlideDown { from { opacity:0; transform:translateY(-10px);} to { opacity:1; transform:translateY(0);} }
        .notification-header { padding: 10px 15px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .notification-header h4 { font-size: 0.9rem; font-weight: 600; color: #732529; }
        body.dark-mode .notification-header h4 { color: #FE4853; }
        .notification-header a { font-size: 0.75rem; color: #FE4853; text-decoration: none; font-weight: 500; }
        .notification-list { max-height: 350px; overflow-y: auto; }
        .notification-item { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s; }
        .notification-item:hover { background: #f5f5f5; }
        body.dark-mode .notification-item { border-bottom-color: #4a4a4a; }
        body.dark-mode .notification-item:hover { background: #4a4a4a; }
        .notification-item.unread { background: #fff3f3; border-left: 3px solid #FE4853; }
        body.dark-mode .notification-item.unread { background: #4a1a1a; }
        .notif-message { font-size: 0.8rem; color: #333; margin-bottom: 4px; }
        body.dark-mode .notif-message { color: #e0e0e0; }
        .notif-thesis { font-size: 0.7rem; color: #6E6E6E; margin: 4px 0; }
        .notif-time { font-size: 0.65rem; color: #9ca3af; }
        .no-notifications { text-align: center; padding: 20px; color: #9ca3af; font-size: 0.8rem; }
        .notification-footer { padding: 10px 15px; border-top: 1px solid #eee; text-align: center; }
        .notification-footer a { font-size: 0.75rem; color: #FE4853; text-decoration: none; font-weight: 500; }
        
        /* Avatar */
        .avatar-dropdown { position: relative; cursor: pointer; }
        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FE4853 0%, #732529 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            top: 55px;
            right: 0;
            background: white;
            min-width: 200px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }
        body.dark-mode .dropdown-content { background: #3a3a3a; }
        .dropdown-content.show { display: block; animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity:0; transform:translateY(-10px);} to { opacity:1; transform:translateY(0);} }
        .dropdown-content a { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            padding: 12px 16px; 
            color: #333; 
            text-decoration: none; 
            transition: background 0.2s;
            cursor: pointer;
        }
        body.dark-mode .dropdown-content a { color: #e0e0e0; }
        .dropdown-content a:hover { background: #f5f5f5; }
        body.dark-mode .dropdown-content a:hover { background: #4a4a4a; }
        .dropdown-content hr { margin: 0; border: none; border-top: 1px solid #e0e0e0; }
        .mobile-menu-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1001;
            border: none;
            background: #FE4853;
            color: white;
            padding: 12px 15px;
            border-radius: 10px;
            cursor: pointer;
            display: none;
            font-size: 1.2rem;
        }
        
        /* Dashboard specific */
        .welcome-section { margin-bottom: 2rem; }
        .welcome-section h2 { font-size: 1.5rem; color: #732529; margin-bottom: 0.25rem; }
        body.dark-mode .welcome-section h2 { color: #FE4853; }
        .welcome-section p { color: #6E6E6E; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(110,110,110,0.1);
        }
        body.dark-mode .stat-card { background: #3a3a3a; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(254,72,83,0.15); }
        .stat-icon { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .stat-card.pending .stat-icon { color: #f59e0b; }
        .stat-card.approved .stat-icon { color: #10b981; }
        .stat-card.rejected .stat-icon { color: #ef4444; }
        .stat-card.archived .stat-icon { color: #6b7280; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #732529; }
        body.dark-mode .stat-value { color: #FE4853; }
        .stat-label { font-size: 0.7rem; color: #6E6E6E; }
        
        /* Charts Section */
        .charts-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(110,110,110,0.1);
        }
        body.dark-mode .chart-card { background: #3a3a3a; }
        .chart-header { 
            display: flex; 
            justify-content: flex-start;
            align-items: center; 
            margin-bottom: 1rem;
        }
        .chart-header h3 { 
            font-size: 1rem; 
            color: #732529; 
            font-weight: 600;
        }
        body.dark-mode .chart-header h3 { color: #FE4853; }
        
        .chart-container { 
            position: relative; 
            height: 250px;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .chart-container canvas {
            max-width: 100%;
            max-height: 100%;
            margin: 0 auto;
            display: block;
        }
        
        .recent-feedback {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            box-shadow: 0 2px 8px rgba(110,110,110,0.1);
        }
        body.dark-mode .recent-feedback { background: #3a3a3a; }
        .recent-feedback h3 { font-size: 1rem; color: #732529; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
        body.dark-mode .recent-feedback h3 { color: #FE4853; }
        .table-responsive { overflow-x: auto; }
        .feedback-table { width: 100%; border-collapse: collapse; }
        .feedback-table th { text-align: left; padding: 0.75rem; background: #f8fafc; color: #6E6E6E; font-weight: 600; font-size: 0.7rem; border-bottom: 1px solid #eee; }
        body.dark-mode .feedback-table th { background: #4a4a4a; border-bottom-color: #6E6E6E; }
        .feedback-table td { padding: 0.75rem; border-bottom: 1px solid #f0f0f0; font-size: 0.8rem; }
        body.dark-mode .feedback-table td { border-bottom-color: #4a4a4a; }
        
        /* Notifications Page */
        .notifications-page {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(110,110,110,0.1);
        }
        body.dark-mode .notifications-page { background: #3a3a3a; }
        .notif-header-page {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #fee2e2;
        }
        .notif-header-page h2 { font-size: 1.3rem; color: #732529; display: flex; align-items: center; gap: 0.5rem; }
        body.dark-mode .notif-header-page h2 { color: #FE4853; }
        .mark-all-btn {
            background: #FE4853;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s;
        }
        .mark-all-btn:hover { background: #732529; }
        .notif-list-page { display: flex; flex-direction: column; gap: 0.75rem; }
        .notif-card {
            background: #fef2f2;
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            transition: all 0.2s;
            border: 1px solid #fee2e2;
        }
        body.dark-mode .notif-card { background: #4a1a1a; border-color: #991b1b; }
        .notif-card.unread { background: #fff5f5; border-left: 4px solid #dc2626; }
        body.dark-mode .notif-card.unread { background: #5a2a2a; }
        .notif-card:hover { transform: translateX(5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .notif-icon-page {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        body.dark-mode .notif-icon-page { background: #3d3d3d; }
        .notif-icon-page i { font-size: 1.1rem; color: #dc2626; }
        .notif-content-page { flex: 1; }
        .notif-message-page { font-size: 0.9rem; color: #1f2937; margin-bottom: 0.25rem; }
        body.dark-mode .notif-message-page { color: #e5e7eb; }
        .notif-thesis-page { font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem; }
        .notif-time-page { font-size: 0.7rem; color: #9ca3af; margin-top: 0.5rem; }
        .btn-mark-read {
            background: #6b7280;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-mark-read:hover { background: #4b5563; }
        .empty-state-page { text-align: center; padding: 3rem; color: #9ca3af; }
        .empty-state-page i { font-size: 3rem; margin-bottom: 1rem; color: #dc2626; }
        
        @media (max-width: 1024px) { 
            .stats-grid { grid-template-columns: repeat(3,1fr); } 
        }
        @media (max-width: 768px) {
            .mobile-menu-btn { display: block; }
            .main-content { padding: 1rem; margin-top: 60px; }
            .topbar { display: none; }
            .stats-grid { grid-template-columns: repeat(2,1fr); }
            .charts-section { grid-template-columns: 1fr; }
            .notif-card { flex-direction: column; }
        }
        @media (max-width: 480px) { 
            .stats-grid { grid-template-columns: 1fr; } 
            .chart-container { height: 200px; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay"></div>
<button class="mobile-menu-btn" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><h2>ThesisManager</h2><p>STUDENT</p></div>
    <nav class="sidebar-nav">
        <a href="student_dashboard.php" class="nav-link <?= $current_view == 'dashboard' ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a>
        <a href="student_dashboard.php?view=notifications" class="nav-link <?= $current_view == 'notifications' ? 'active' : '' ?>">
            <i class="fas fa-bell"></i> Notifications 
            <?php if($unreadCount > 0): ?>
                <span style="background:#FE4853; padding:2px 8px; border-radius:10px; font-size:10px; margin-left:5px;"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>
        <a href="projects.php" class="nav-link"><i class="fas fa-folder-open"></i> My Projects</a>
        <a href="submission.php" class="nav-link"><i class="fas fa-upload"></i> Submit Thesis</a>
        <a href="archived.php" class="nav-link"><i class="fas fa-archive"></i> Archived Theses</a>
    </nav>
    <div class="sidebar-footer">
        <div class="theme-toggle">
            <input type="checkbox" id="darkmode" />
            <label for="darkmode" class="toggle-label"><i class="fas fa-sun"></i><i class="fas fa-moon"></i><span class="slider"></span></label>
        </div>
        <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>

<div class="layout">
    <main class="main-content">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div class="hamburger-menu" id="hamburgerBtn"><i class="fas fa-bars"></i></div>
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
            <div class="user-info">
                <div class="notification-container">
                    <div class="notification-bell" id="notificationBell"><i class="fas fa-bell"></i><?php if($unreadCount>0): ?><span class="notification-badge"><?= $unreadCount ?></span><?php endif; ?></div>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4><i class="fas fa-bell"></i> Notifications</h4>
                            <a href="student_dashboard.php?view=notifications">View all</a>
                        </div>
                        <div class="notification-list">
                            <?php if(empty($recentNotifications)): ?>
                                <div class="notification-item"><div class="no-notifications"><i class="fas fa-inbox"></i> No notifications</div></div>
                            <?php else: foreach($recentNotifications as $notif): ?>
                                <div class="notification-item <?= $notif['is_read']==0 ? 'unread' : '' ?>" data-notification-id="<?= $notif['notification_id'] ?>" data-thesis-id="<?= $notif['thesis_id'] ?? 0 ?>">
                                    <div class="notif-message"><?= htmlspecialchars(substr($notif['message'] ?? '', 0, 70)) ?></div>
                                    <div class="notif-time"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
                <div class="avatar-dropdown">
                    <div class="avatar" id="avatarBtn"><?= htmlspecialchars($initials) ?></div>
                    <div class="dropdown-content" id="dropdownMenu">
                        <a href="profile.php" id="profileLink"><i class="fas fa-user-circle"></i> Profile</a>
                        <a href="settings.php" id="settingsLink"><i class="fas fa-cog"></i> Settings</a>
                        <hr>
                        <a href="/ArchivingThesis/authentication/logout.php" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <?php if($current_view == 'dashboard'): ?>
            <!-- DASHBOARD VIEW -->
            <div class="welcome-section"><h2>Welcome, <?= htmlspecialchars($first_name) ?>!</h2><p>Member since <?= $member_since ?></p></div>
            <div class="stats-grid">
                <div class="stat-card pending"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-value"><?= $pendingCount ?></div><div class="stat-label">Pending</div></div>
                <div class="stat-card approved"><div class="stat-icon"><i class="fas fa-check-circle"></i></div><div class="stat-value"><?= $approvedCount ?></div><div class="stat-label">Approved</div></div>
                <div class="stat-card rejected"><div class="stat-icon"><i class="fas fa-times-circle"></i></div><div class="stat-value"><?= $rejectedCount ?></div><div class="stat-label">Rejected</div></div>
                <div class="stat-card archived"><div class="stat-icon"><i class="fas fa-archive"></i></div><div class="stat-value"><?= $archivedCount ?></div><div class="stat-label">Archived</div></div>
                <div class="stat-card"><div class="stat-icon"><i class="fas fa-layer-group"></i></div><div class="stat-value"><?= $totalCount ?></div><div class="stat-label">Total</div></div>
            </div>
            
            <div class="charts-section">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-pie"></i> Project Status</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="projectStatusChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> Submissions Timeline</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="timelineChart"></canvas>
                    </div>
                </div>
            </div>
            
            <?php if(!empty($recentFeedback)): ?>
            <div class="recent-feedback"><h3><i class="fas fa-comments"></i> Recent Feedback</h3><div class="table-responsive"><table class="feedback-table"><thead><tr><th>TITLE</th><th>FROM</th><th>FEEDBACK</th><th>DATE</th></tr></thead><tbody><?php foreach($recentFeedback as $fb): ?><tr><td><?= htmlspecialchars($fb['thesis_title']) ?></td><td><?= htmlspecialchars($fb['faculty_first']) ?> <?= htmlspecialchars($fb['faculty_last']) ?></td><td><?= htmlspecialchars(substr($fb['feedback_text'],0,60)) ?>...</td><td><?= date('M d, Y', strtotime($fb['created_at'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div>
            <?php endif; ?>
        <?php else: ?>
            <div class="notifications-page">
                <div class="notif-header-page">
                    <h2><i class="fas fa-bell"></i> All Notifications</h2>
                    <?php if($unreadCount > 0): ?>
                        <a href="?mark_all_read=1&view=notifications" class="mark-all-btn" onclick="return confirm('Mark all as read?')"><i class="fas fa-check-double"></i> Mark all as read</a>
                    <?php endif; ?>
                </div>
                <div class="notif-list-page">
                    <?php if(empty($allNotifications)): ?>
                        <div class="empty-state-page"><i class="fas fa-bell-slash"></i><p>No notifications yet</p></div>
                    <?php else: ?>
                        <?php foreach($allNotifications as $notif): ?>
                            <div class="notif-card <?= $notif['is_read'] == 0 ? 'unread' : '' ?>">
                                <div class="notif-icon-page"><i class="fas fa-bell"></i></div>
                                <div class="notif-content-page">
                                    <div class="notif-message-page"><?= htmlspecialchars($notif['message']) ?></div>
                                    <?php if(!empty($notif['thesis_title'])): ?>
                                        <div class="notif-thesis-page"><i class="fas fa-book"></i> <?= htmlspecialchars($notif['thesis_title']) ?></div>
                                    <?php endif; ?>
                                    <div class="notif-time-page"><i class="far fa-clock"></i> <?= date('F d, Y h:i A', strtotime($notif['created_at'])) ?></div>
                                </div>
                                <div>
                                    <?php if($notif['is_read'] == 0): ?>
                                        <a href="?mark_read=<?= $notif['notification_id'] ?>&view=notifications" class="btn-mark-read"><i class="fas fa-check"></i> Mark read</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<script>
const chartData = { pending: <?= $pendingCount ?>, approved: <?= $approvedCount ?>, rejected: <?= $rejectedCount ?>, archived: <?= $archivedCount ?> };

// Sidebar functions
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const hamburgerBtn = document.getElementById('hamburgerBtn');
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const notificationBell = document.getElementById('notificationBell');
const notificationDropdown = document.getElementById('notificationDropdown');
const avatarBtn = document.getElementById('avatarBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
const darkToggle = document.getElementById('darkmode');

function openSidebar() { sidebar.classList.add('show'); overlay.classList.add('show'); document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('show'); overlay.classList.remove('show'); document.body.style.overflow = ''; }
function toggleSidebar(e) { e.stopPropagation(); sidebar.classList.contains('show') ? closeSidebar() : openSidebar(); }

if(hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);
if(mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleSidebar);
if(overlay) overlay.addEventListener('click', closeSidebar);

// Avatar dropdown - FIXED: Direct navigation using window.location
if(avatarBtn && dropdownMenu) {
    // Toggle dropdown on avatar click
    avatarBtn.addEventListener('click', (e) => { 
        e.stopPropagation(); 
        dropdownMenu.classList.toggle('show'); 
        if(notificationDropdown) notificationDropdown.classList.remove('show'); 
    });
    
    // Handle Profile link click - DIRECT NAVIGATION
    const profileLink = document.getElementById('profileLink');
    if(profileLink) {
        profileLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = 'profile.php';
        });
    }
    
    // Handle Settings link click
    const settingsLink = document.getElementById('settingsLink');
    if(settingsLink) {
        settingsLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = 'settings.php';
        });
    }
    
    // Handle Logout link click
    const logoutLink = document.getElementById('logoutLink');
    if(logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = '/ArchivingThesis/authentication/logout.php';
        });
    }
}

// Notification dropdown
if(notificationBell && notificationDropdown) {
    notificationBell.addEventListener('click', (e) => { 
        e.stopPropagation(); 
        notificationDropdown.classList.toggle('show'); 
        if(dropdownMenu) dropdownMenu.classList.remove('show'); 
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if(dropdownMenu && avatarBtn && !avatarBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
        dropdownMenu.classList.remove('show');
    }
    if(notificationDropdown && notificationBell && !notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
        notificationDropdown.classList.remove('show');
    }
});

// Dark mode
if(darkToggle) {
    darkToggle.addEventListener('change', () => { document.body.classList.toggle('dark-mode'); localStorage.setItem('darkMode', darkToggle.checked); });
    if(localStorage.getItem('darkMode') === 'true') { darkToggle.checked = true; document.body.classList.add('dark-mode'); }
}

// Mark as read AJAX for dropdown
function markAsRead(element, notifId) {
    fetch(window.location.href, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'mark_read=1&notif_id=' + notifId })
    .then(r => r.json()).then(data => {
        if(data.success) {
            element.classList.remove('unread');
            const badge = document.querySelector('.notification-badge');
            if(badge) { let c = parseInt(badge.textContent); if(c>0) { c--; if(c===0) badge.style.display='none'; else badge.textContent=c; } }
        }
    }).catch(err => console.error(err));
}

document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function(e) {
        const notifId = this.dataset.notificationId;
        const thesisId = this.dataset.thesisId;
        if(notifId && this.classList.contains('unread')) markAsRead(this, notifId);
        if(thesisId && parseInt(thesisId) > 0) {
            setTimeout(() => window.location.href = 'view_project.php?id=' + thesisId, 300);
        }
    });
});

// Charts
if(document.getElementById('projectStatusChart')) {
    let statusChart, timelineChart;
    const isDark = document.body.classList.contains('dark-mode');
    const textColor = isDark ? '#e0e0e0' : '#333';
    const gridColor = isDark ? '#4a5568' : '#e2e8f0';
    
    statusChart = new Chart(document.getElementById('projectStatusChart'), {
        type: 'doughnut',
        data: { 
            labels: ['Pending','Approved','Rejected','Archived'], 
            datasets: [{ 
                data: [chartData.pending, chartData.approved, chartData.rejected, chartData.archived], 
                backgroundColor: ['#f59e0b','#10b981','#ef4444','#6b7280'], 
                borderWidth: 0, 
                cutout: '60%' 
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            plugins: { 
                legend: { 
                    position: 'bottom', 
                    labels: { color: textColor, font: { size: 11 } } 
                }, 
                tooltip: { 
                    callbacks: { 
                        label: (ctx) => { 
                            const val = ctx.raw; 
                            const total = ctx.dataset.data.reduce((a,b)=>a+b,0); 
                            const pct = total>0 ? Math.round((val/total)*100) : 0; 
                            return `${ctx.label}: ${val} (${pct}%)`; 
                        } 
                    } 
                } 
            } 
        }
    });
    
    const monthlyData = [2,3,5,4,6,8,7,9,5,4,3,2];
    
    timelineChart = new Chart(document.getElementById('timelineChart'), {
        type: 'line',
        data: { 
            labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], 
            datasets: [{ 
                label: 'Submissions', 
                data: monthlyData, 
                borderColor: '#FE4853', 
                backgroundColor: 'rgba(254,72,83,0.1)', 
                borderWidth: 3, 
                fill: true, 
                tension: 0.4,
                pointBackgroundColor: '#FE4853',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            scales: { 
                y: { 
                    beginAtZero: true, 
                    grid: { color: gridColor }, 
                    ticks: { stepSize: 1, color: textColor } 
                }, 
                x: { 
                    ticks: { color: textColor } 
                } 
            }, 
            plugins: { 
                legend: { 
                    labels: { color: textColor } 
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `Submissions: ${ctx.raw}`
                    }
                }
            } 
        }
    });
    
    window.statusChart = statusChart;
    window.timelineChart = timelineChart;
}

if(darkToggle) {
    darkToggle.addEventListener('change', function() {
        setTimeout(() => {
            const isDarkNow = document.body.classList.contains('dark-mode');
            const textColorNew = isDarkNow ? '#e0e0e0' : '#333';
            const gridColorNew = isDarkNow ? '#4a5568' : '#e2e8f0';
            
            if(window.statusChart) {
                window.statusChart.options.plugins.legend.labels.color = textColorNew;
                window.statusChart.update();
            }
            if(window.timelineChart) {
                window.timelineChart.options.scales.y.grid.color = gridColorNew;
                window.timelineChart.options.scales.y.ticks.color = textColorNew;
                window.timelineChart.options.scales.x.ticks.color = textColorNew;
                window.timelineChart.options.plugins.legend.labels.color = textColorNew;
                window.timelineChart.update();
            }
        }, 50);
    });
}
</script>
</body>
</html>