<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'coordinator') {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// GET USER DATA FROM DATABASE
$user_query = "SELECT user_id, username, email, first_name, last_name, role_id, department FROM user_table WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();

$first_name = '';
$last_name = '';
$username = '';
$user_email = '';
$coordinator_department = '';

if ($user_data) {
    $first_name = $user_data['first_name'] ?? '';
    $last_name = $user_data['last_name'] ?? '';
    $username = $user_data['username'] ?? '';
    $user_email = $user_data['email'] ?? '';
    $coordinator_department = $user_data['department'] ?? '';
}

$fullName = trim($first_name . " " . $last_name);
if (empty($fullName)) $fullName = !empty($username) ? $username : "Coordinator";

$initials = !empty($first_name) && !empty($last_name) ? strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) : 
            (!empty($first_name) ? strtoupper(substr($first_name, 0, 1)) : "CO");

// Department names mapping
$department_names = [
    'BSIT' => 'BS Information Technology',
    'BSCRIM' => 'BS Criminology',
    'BSHTM' => 'BS Hospitality Management',
    'BSED' => 'BS Education',
    'BSBA' => 'BS Business Administration'
];
$coordinator_dept_display = $department_names[$coordinator_department] ?? ($coordinator_department ?: 'All Departments');

$dept_colors = [
    'BSIT' => '#3b82f6',
    'BSCRIM' => '#10b981',
    'BSHTM' => '#f59e0b',
    'BSED' => '#8b5cf6',
    'BSBA' => '#ef4444'
];

$dept_icons = [
    'BSIT' => 'fa-laptop-code',
    'BSCRIM' => 'fa-gavel',
    'BSHTM' => 'fa-utensils',
    'BSED' => 'fa-chalkboard-user',
    'BSBA' => 'fa-chart-line'
];

// GET COORDINATOR DATA
$position = "Research Coordinator";
$assigned_date = date('F Y');

// ==================== CHECK IF THESIS_TABLE EXISTS ====================
$thesis_table_exists = false;
$check_thesis = $conn->query("SHOW TABLES LIKE 'thesis_table'");
if ($check_thesis && $check_thesis->num_rows > 0) {
    $thesis_table_exists = true;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// GET NOTIFICATION COUNT
$notificationCount = 0;
$notif_query = "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
if ($notif_row = $notif_result->fetch_assoc()) {
    $notificationCount = $notif_row['cnt'];
}
$notif_stmt->close();

// GET RECENT NOTIFICATIONS
$recentNotifications = [];
$notif_list_query = "SELECT notification_id, user_id, thesis_id, message, type, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
$notif_list_stmt = $conn->prepare($notif_list_query);
$notif_list_stmt->bind_param("i", $user_id);
$notif_list_stmt->execute();
$notif_list_result = $notif_list_stmt->get_result();
while ($row = $notif_list_result->fetch_assoc()) {
    $recentNotifications[] = $row;
}
$notif_list_stmt->close();

// ==================== FUNCTION TO NOTIFY DEAN ====================
function notifyDean($conn, $thesis_id, $thesis_title, $student_name, $coordinator_name) {
    $dean_query = "SELECT user_id FROM user_table WHERE role_id = 4";
    $dean_result = $conn->query($dean_query);
    
    if ($dean_result && $dean_result->num_rows > 0) {
        while ($dean = $dean_result->fetch_assoc()) {
            $message = "📋 Thesis ready for Dean approval: \"" . $thesis_title . "\" from student " . $student_name . ". Forwarded by Coordinator: " . $coordinator_name;
            $link = "../departmentDeanDashboard/reviewThesis.php?id=" . $thesis_id;
            $insert = "INSERT INTO notifications (user_id, thesis_id, message, type, link, is_read, created_at) VALUES (?, ?, ?, 'dean_forward', ?, 0, NOW())";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("iisss", $dean['user_id'], $thesis_id, $message, $link);
            $stmt->execute();
            $stmt->close();
        }
    }
    return true;
}

// ==================== GET DEPARTMENT COUNTS ====================
$dept_counts = [];
$dept_query = "SELECT department, COUNT(*) as count FROM thesis_table GROUP BY department ORDER BY count DESC";
$dept_result = $conn->query($dept_query);
if ($dept_result && $dept_result->num_rows > 0) {
    while ($row = $dept_result->fetch_assoc()) {
        $dept_counts[$row['department']] = $row['count'];
    }
}
$total_theses = array_sum($dept_counts);
$max_dept_count = max(array_values($dept_counts) ?: [1]);

// ==================== GET PENDING THESES GROUPED BY DEPARTMENT ====================
$pending_theses_by_dept = [];
foreach ($department_names as $code => $name) {
    $pending_theses_by_dept[$code] = [];
}

if ($thesis_table_exists) {
    $pending_query = "SELECT t.*, u.first_name, u.last_name, u.email 
                      FROM thesis_table t
                      JOIN user_table u ON t.student_id = u.user_id
                      WHERE (t.is_archived = 0 OR t.is_archived IS NULL)
                      ORDER BY t.department, t.date_submitted DESC";
    $pending_result = $conn->query($pending_query);
    
    if ($pending_result && $pending_result->num_rows > 0) {
        while ($row = $pending_result->fetch_assoc()) {
            $dept_code = $row['department'];
            if (isset($pending_theses_by_dept[$dept_code])) {
                $pending_theses_by_dept[$dept_code][] = $row;
            }
        }
    }
}

// Calculate total pending count
$total_pending = 0;
foreach ($pending_theses_by_dept as $theses) {
    $total_pending += count($theses);
}

// MARK NOTIFICATION AS READ
if (isset($_POST['mark_read']) && isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    $update_query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $notif_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// MARK ALL NOTIFICATIONS AS READ
if (isset($_POST['mark_all_read'])) {
    $update_query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true]);
    exit;
}

// FORWARD THESIS TO DEAN
if (isset($_POST['forward_to_dean']) && isset($_POST['thesis_id']) && $thesis_table_exists) {
    header('Content-Type: application/json');
    $thesis_id = intval($_POST['thesis_id']);
    $thesis_title = $_POST['thesis_title'] ?? '';
    $student_name = $_POST['student_name'] ?? '';
    $coordinator_name = $fullName;
    
    notifyDean($conn, $thesis_id, $thesis_title, $student_name, $coordinator_name);
    
    echo json_encode(['success' => true, 'message' => 'Thesis forwarded to Dean successfully']);
    exit;
}

// REJECT THESIS
if (isset($_POST['reject_thesis']) && isset($_POST['thesis_id']) && $thesis_table_exists) {
    header('Content-Type: application/json');
    $thesis_id = intval($_POST['thesis_id']);
    $reason = $_POST['reason'] ?? 'No reason provided';
    
    echo json_encode(['success' => true, 'message' => 'Thesis rejected successfully']);
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard | Thesis Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fef2f2; color: #1f2937; overflow-x: hidden; }
        body.dark-mode { background: #1a1a1a; color: #e0e0e0; }
        
        .top-nav { position: fixed; top: 0; right: 0; left: 0; height: 70px; background: white; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); z-index: 99; border-bottom: 1px solid #fee2e2; }
        body.dark-mode .top-nav { background: #2d2d2d; border-bottom-color: #991b1b; }
        .nav-left { display: flex; align-items: center; gap: 24px; }
        .hamburger { display: flex; flex-direction: column; gap: 5px; width: 40px; height: 40px; background: #fef2f2; border: none; border-radius: 8px; cursor: pointer; align-items: center; justify-content: center; }
        .hamburger span { display: block; width: 22px; height: 2px; background: #dc2626; border-radius: 2px; }
        .hamburger:hover { background: #fee2e2; }
        .logo { font-size: 1.3rem; font-weight: 700; color: #991b1b; }
        .logo span { color: #dc2626; }
        body.dark-mode .logo { color: #fecaca; }
        .search-area { display: flex; align-items: center; background: #fef2f2; padding: 8px 16px; border-radius: 40px; gap: 10px; }
        .search-area i { color: #dc2626; }
        .search-area input { border: none; background: none; outline: none; font-size: 0.85rem; width: 200px; }
        .nav-right { display: flex; align-items: center; gap: 20px; position: relative; }
        
        .notification-container { position: relative; }
        .notification-icon { position: relative; cursor: pointer; width: 40px; height: 40px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .notification-icon:hover { background: #fee2e2; }
        .notification-icon i { font-size: 1.2rem; color: #dc2626; }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 0.6rem; font-weight: 600; min-width: 18px; height: 18px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 5px; }
        .notification-dropdown { position: absolute; top: 55px; right: 0; width: 380px; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); display: none; overflow: hidden; z-index: 100; border: 1px solid #fee2e2; }
        .notification-dropdown.show { display: block; animation: fadeSlideDown 0.2s ease; }
        .notification-header { padding: 16px 20px; border-bottom: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center; }
        .notification-header h3 { font-size: 1rem; font-weight: 600; color: #991b1b; }
        .mark-all-read { font-size: 0.7rem; color: #dc2626; cursor: pointer; background: none; border: none; }
        .notification-list { max-height: 400px; overflow-y: auto; }
        .notification-item { display: flex; gap: 12px; padding: 12px 20px; border-bottom: 1px solid #fef2f2; cursor: pointer; transition: background 0.2s; text-decoration: none; color: inherit; }
        .notification-item:hover { background: #fef2f2; }
        .notification-item.unread { background: #fff5f5; border-left: 3px solid #dc2626; }
        .notification-item.empty { justify-content: center; color: #9ca3af; cursor: default; }
        .notif-icon { width: 36px; height: 36px; background: #fef2f2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #dc2626; }
        .notif-content { flex: 1; }
        .notif-message { font-size: 0.8rem; color: #1f2937; margin-bottom: 4px; line-height: 1.4; }
        .notif-time { font-size: 0.65rem; color: #9ca3af; }
        .notification-footer { padding: 12px 20px; border-top: 1px solid #fee2e2; text-align: center; }
        .notification-footer a { color: #dc2626; text-decoration: none; font-size: 0.8rem; }
        
        .profile-wrapper { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .profile-name { font-weight: 500; color: #1f2937; font-size: 0.9rem; }
        .profile-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #dc2626, #5b3b3b); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .profile-dropdown { position: absolute; top: 55px; right: 0; background: white; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); min-width: 200px; display: none; overflow: hidden; z-index: 100; border: 1px solid #fee2e2; }
        .profile-dropdown.show { display: block; }
        .profile-dropdown a { display: flex; align-items: center; gap: 12px; padding: 12px 18px; text-decoration: none; color: #1f2937; font-size: 0.85rem; }
        .profile-dropdown a:hover { background: #fef2f2; color: #dc2626; }
        
        .sidebar { position: fixed; top: 0; left: -300px; width: 280px; height: 100%; background: linear-gradient(180deg, #991b1b 0%, #dc2626 100%); display: flex; flex-direction: column; z-index: 1000; transition: left 0.3s ease; box-shadow: 2px 0 10px rgba(0,0,0,0.05); }
        .sidebar.open { left: 0; }
        .logo-container { padding: 28px 24px; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .logo-container .logo { color: white; }
        .logo-sub { font-size: 0.7rem; color: #fecaca; margin-top: 6px; }
        .nav-menu { flex: 1; padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 12px 16px; border-radius: 12px; text-decoration: none; color: #fecaca; font-weight: 500; transition: all 0.2s; }
        .nav-item:hover { background: rgba(255,255,255,0.15); color: white; transform: translateX(5px); }
        .nav-item.active { background: rgba(255,255,255,0.2); color: white; }
        .nav-footer { padding: 20px 16px; border-top: 1px solid rgba(255,255,255,0.15); }
        .theme-toggle { margin-bottom: 12px; }
        .theme-toggle input { display: none; }
        .toggle-label { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .toggle-label i { font-size: 1rem; color: #fecaca; }
        .logout-btn { display: flex; align-items: center; gap: 12px; padding: 10px 12px; text-decoration: none; color: #fecaca; border-radius: 10px; }
        .logout-btn:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); z-index: 999; display: none; }
        .sidebar-overlay.show { display: block; }
        
        .main-content { margin-left: 0; margin-top: 70px; padding: 32px; transition: margin-left 0.3s ease; }
        
        .welcome-banner { background: linear-gradient(135deg, #851313, #900c0c); border-radius: 28px; padding: 32px 36px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; color: white; }
        .welcome-info h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 8px; }
        .welcome-info p { opacity: 0.8; font-size: 0.85rem; }
        .coordinator-info { text-align: right; }
        .coordinator-name { font-size: 1rem; font-weight: 600; margin-bottom: 4px; }
        .coordinator-position { font-size: 0.8rem; opacity: 0.9; }
        .coordinator-since { font-size: 0.7rem; opacity: 0.7; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
        .stat-card { background: white; border-radius: 20px; padding: 24px; display: flex; align-items: center; gap: 20px; border: 1px solid #fee2e2; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        body.dark-mode .stat-card { background: #2d2d2d; border-color: #991b1b; }
        .stat-icon { width: 60px; height: 60px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; background: #fef2f2; color: #dc2626; }
        .stat-content h3 { font-size: 2rem; font-weight: 700; color: #ba0202; }
        .stat-content p { font-size: 0.85rem; color: #6b7280; margin-top: 4px; }
        body.dark-mode .stat-content h3 { color: #fecaca; }
        
        /* Department Cards Grid */
        .chart-card { background: white; border-radius: 24px; padding: 24px; margin-bottom: 32px; border: 1px solid #fee2e2; transition: all 0.2s; }
        body.dark-mode .chart-card { background: #2d2d2d; border-color: #991b1b; }
        .chart-card h3 { font-size: 1rem; font-weight: 600; color: #991b1b; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        body.dark-mode .chart-card h3 { color: #fecaca; }
        
        .dept-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .dept-card {
            background: #fef2f2;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid #fee2e2;
            transition: all 0.3s;
            cursor: pointer;
        }
        body.dark-mode .dept-card {
            background: #3d3d3d;
            border-color: #991b1b;
        }
        .dept-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        
        .dept-card-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .dept-card-content {
            flex: 1;
        }
        .dept-card-content h4 {
            font-size: 0.85rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 6px;
        }
        body.dark-mode .dept-card-content h4 {
            color: #e5e7eb;
        }
        
        .dept-card-stats {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 8px;
        }
        .dept-card-count {
            font-size: 1.6rem;
            font-weight: 700;
            color: #dc2626;
        }
        body.dark-mode .dept-card-count {
            color: #fecaca;
        }
        .dept-card-label {
            font-size: 0.7rem;
            color: #6b7280;
        }
        
        .progress-bar-small {
            width: 100%;
            height: 5px;
            background: #fee2e2;
            border-radius: 10px;
            overflow: hidden;
        }
        body.dark-mode .progress-bar-small {
            background: #4a4a4a;
        }
        .progress-fill-small {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .dept-total-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #fee2e2;
            text-align: right;
            font-size: 0.9rem;
            color: #6b7280;
        }
        body.dark-mode .dept-total-footer {
            border-top-color: #991b1b;
            color: #94a3b8;
        }
        .dept-total-footer strong {
            color: #dc2626;
            font-size: 1.1rem;
        }
        body.dark-mode .dept-total-footer strong {
            color: #fecaca;
        }
        
        /* Department Sections */
        .dept-section {
            margin-bottom: 32px;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid #fee2e2;
        }
        body.dark-mode .dept-section {
            background: #2d2d2d;
            border-color: #991b1b;
        }
        
        .dept-section-header {
            padding: 18px 24px;
            background: #fef2f2;
            border-bottom: 1px solid #fee2e2;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        body.dark-mode .dept-section-header {
            background: #3d3d3d;
            border-bottom-color: #991b1b;
        }
        
        .dept-section-header .dept-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }
        
        .dept-section-header h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        body.dark-mode .dept-section-header h4 {
            color: #e5e7eb;
        }
        
        .dept-section-header .badge {
            background: #dc2626;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .dept-section-content {
            padding: 20px;
        }
        
        .dept-empty {
            text-align: center;
            padding: 30px;
            color: #9ca3af;
        }
        
        .thesis-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            background: #fef2f2;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: all 0.2s;
        }
        body.dark-mode .thesis-item {
            background: #3d3d3d;
        }
        .thesis-item:hover {
            background: #fee2e2;
            transform: translateX(5px);
        }
        body.dark-mode .thesis-item:hover {
            background: #4a4a4a;
        }
        
        .thesis-info {
            flex: 1;
        }
        .thesis-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        body.dark-mode .thesis-title {
            color: #e5e7eb;
        }
        .thesis-meta {
            display: flex;
            gap: 15px;
            font-size: 0.7rem;
            color: #6b7280;
            flex-wrap: wrap;
        }
        
        .review-btn, .btn-forward {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .review-btn {
            background: #dc2626;
            color: white;
        }
        .review-btn:hover {
            background: #991b1b;
            transform: scale(1.02);
        }
        .btn-forward {
            background: #10b981;
            color: white;
        }
        .btn-forward:hover {
            background: #059669;
            transform: scale(1.02);
        }
        
        .department-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #dc2626;
            color: white;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 500;
        }
        
        @keyframes fadeSlideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .toast-message { position: fixed; bottom: 30px; right: 30px; background: #10b981; color: white; padding: 12px 20px; border-radius: 12px; font-size: 0.85rem; z-index: 1001; animation: slideIn 0.3s ease; }
        .toast-message.error { background: #ef4444; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        
        @media (max-width: 1024px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } .dept-cards-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .dept-cards-grid { grid-template-columns: 1fr; }
            .search-area { display: none; }
            .profile-name { display: none; }
            .welcome-banner { flex-direction: column; text-align: center; gap: 15px; }
            .coordinator-info { text-align: center; }
            .thesis-item { flex-direction: column; align-items: flex-start; gap: 12px; }
        }
        @media (max-width: 480px) { .main-content { padding: 20px; } .stat-card { padding: 16px; } .stat-icon { width: 45px; height: 45px; font-size: 1.3rem; } .stat-content h3 { font-size: 1.5rem; } }
        
        body.dark-mode .stat-card, body.dark-mode .chart-card { background: #2d2d2d; border-color: #991b1b; }
        body.dark-mode .stat-content h3 { color: #fecaca; }
        body.dark-mode .dept-section { background: #2d2d2d; border-color: #991b1b; }
        body.dark-mode .dept-section-header { background: #3d3d3d; border-bottom-color: #991b1b; }
        body.dark-mode .dept-section-header h4 { color: #fecaca; }
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
                    <span class="notification-badge" id="notificationBadge" style="display: <?= $notificationCount > 0 ? 'flex' : 'none' ?>;">
                        <?= $notificationCount ?>
                    </span>
                </div>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <?php if ($notificationCount > 0): ?>
                            <button class="mark-all-read" id="markAllRead">Mark all as read</button>
                        <?php endif; ?>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <?php if (empty($recentNotifications)): ?>
                            <div class="notification-item empty">
                                <div class="notif-icon"><i class="far fa-bell-slash"></i></div>
                                <div class="notif-content">
                                    <div class="notif-message">No notifications yet</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentNotifications as $notif): ?>
                                <a href="reviewThesis.php?id=<?= $notif['thesis_id'] ?>" class="notification-item <?= $notif['is_read'] == 0 ? 'unread' : '' ?>" data-id="<?= $notif['notification_id'] ?>">
                                    <div class="notif-icon"><i class="fas fa-bell"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="notif-time"><i class="far fa-clock"></i> <?= date('M d, Y', strtotime($notif['created_at'])) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="notification-footer">
                        <a href="notifications.php">View all notifications <i class="fas fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="profile-wrapper" id="profileWrapper">
                <div class="profile-trigger">
                    <span class="profile-name"><?= htmlspecialchars($fullName) ?></span>
                    <div class="profile-avatar"><?= htmlspecialchars($initials) ?></div>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="editProfile.php"><i class="fas fa-edit"></i> Edit Profile</a>
                    <hr>
                    <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="logo-container"><div class="logo">Thesis<span>Manager</span></div><div class="logo-sub">RESEARCH COORDINATOR</div></div>
        <div class="nav-menu">
            <a href="coordinatorDashboard.php" class="nav-item active"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="reviewThesis.php" class="nav-item"><i class="fas fa-file-alt"></i><span>Review Theses</span>
                <?php if ($total_pending > 0): ?><span style="margin-left: auto; background: #ff6b6b; padding: 2px 8px; border-radius: 20px; font-size: 0.7rem;"><?= $total_pending ?></span><?php endif; ?>
            </a>
            <a href="forwardedTheses.php" class="nav-item"><i class="fas fa-arrow-right"></i><span>Forwarded to Dean</span></a>
            <a href="myFeedback.php" class="nav-item"><i class="fas fa-comment"></i><span>My Feedback</span></a>
        </div>
        <div class="nav-footer">
            <div class="theme-toggle"><input type="checkbox" id="darkmode"><label for="darkmode" class="toggle-label"><i class="fas fa-sun"></i><i class="fas fa-moon"></i></label></div>
            <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="welcome-banner">
            <div class="welcome-info">
                <h1>Coordinator Dashboard</h1>
                <p>Welcome back, <?= htmlspecialchars($first_name) ?>!</p>
            </div>
            <div class="coordinator-info">
                <div class="coordinator-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="coordinator-position"><?= htmlspecialchars($position) ?></div>
                <div class="coordinator-since">Since <?= $assigned_date ?></div>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-file-alt"></i></div><div class="stat-content"><h3><?= number_format($total_theses) ?></h3><p>Total Theses</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-clock"></i></div><div class="stat-content"><h3><?= number_format($total_pending) ?></h3><p>Pending Review</p></div></div>
            <div class="stat-card"><div class="stat-icon"><i class="fas fa-archive"></i></div><div class="stat-content"><h3><?= number_format($total_theses - $total_pending) ?></h3><p>Archived</p></div></div>
        </div>

        <!-- DEPARTMENT WISE STATISTICS - SEPARATE CARDS -->
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar"></i> Thesis Submissions by Department</h3>
            <div class="dept-cards-grid">
                <?php foreach ($department_names as $code => $name): 
                    $count = isset($dept_counts[$code]) ? $dept_counts[$code] : 0;
                    $percentage = $max_dept_count > 0 ? ($count / $max_dept_count) * 100 : 0;
                    $icon = $dept_icons[$code] ?? 'fa-graduation-cap';
                ?>
                <div class="dept-card">
                    <div class="dept-card-icon" style="background: <?= $dept_colors[$code] ?>20;">
                        <i class="fas <?= $icon ?>" style="color: <?= $dept_colors[$code] ?>;"></i>
                    </div>
                    <div class="dept-card-content">
                        <h4><?= htmlspecialchars($name) ?></h4>
                        <div class="dept-card-stats">
                            <span class="dept-card-count"><?= $count ?></span>
                            <span class="dept-card-label">Theses</span>
                        </div>
                        <div class="progress-bar-small">
                            <div class="progress-fill-small" style="width: <?= $percentage ?>%; background: <?= $dept_colors[$code] ?>;"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="dept-total-footer">
                <strong>Total Theses: <?= $total_theses ?></strong>
            </div>
        </div>

        <!-- THESES READY FOR DEAN FORWARDING - BY DEPARTMENT -->
        <div class="chart-card">
            <h3><i class="fas fa-paper-plane"></i> Theses Ready for Dean Forwarding</h3>
            <?php 
            $has_pending = false;
            foreach ($department_names as $code => $name):
                $dept_theses = $pending_theses_by_dept[$code] ?? [];
                if (empty($dept_theses)) continue;
                $has_pending = true;
            ?>
            <div class="dept-section">
                <div class="dept-section-header">
                    <span class="dept-dot" style="background: <?= $dept_colors[$code] ?>;"></span>
                    <h4><?= htmlspecialchars($name) ?></h4>
                    <span class="badge"><?= count($dept_theses) ?> pending</span>
                </div>
                <div class="dept-section-content">
                    <?php foreach ($dept_theses as $thesis): ?>
                    <div class="thesis-item">
                        <div class="thesis-info">
                            <div class="thesis-title"><?= htmlspecialchars($thesis['title']) ?></div>
                            <div class="thesis-meta">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?></span>
                                <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($thesis['date_submitted'])) ?></span>
                            </div>
                        </div>
                        <button class="btn-forward" onclick="alert('Forward thesis ID: <?= $thesis['thesis_id'] ?>')"><i class="fas fa-arrow-right"></i> Forward to Dean</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!$has_pending): ?>
                <div class="empty-state" style="padding: 40px;"><i class="fas fa-check-circle"></i><p>No pending theses to forward to Dean</p></div>
            <?php endif; ?>
        </div>

        <!-- THESES WAITING FOR REVIEW - BY DEPARTMENT -->
        <div class="chart-card">
            <h3><i class="fas fa-clock"></i> Theses Waiting for Review</h3>
            <?php 
            $has_waiting = false;
            foreach ($department_names as $code => $name):
                $dept_theses = $pending_theses_by_dept[$code] ?? [];
                if (empty($dept_theses)) continue;
                $has_waiting = true;
            ?>
            <div class="dept-section">
                <div class="dept-section-header">
                    <span class="dept-dot" style="background: <?= $dept_colors[$code] ?>;"></span>
                    <h4><?= htmlspecialchars($name) ?></h4>
                    <span class="badge"><?= count($dept_theses) ?> waiting</span>
                </div>
                <div class="dept-section-content">
                    <?php foreach ($dept_theses as $thesis): ?>
                    <div class="thesis-item">
                        <div class="thesis-info">
                            <div class="thesis-title"><?= htmlspecialchars($thesis['title']) ?></div>
                            <div class="thesis-meta">
                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?></span>
                                <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($thesis['date_submitted'])) ?></span>
                            </div>
                        </div>
                        <a href="reviewThesis.php?id=<?= $thesis['thesis_id'] ?>" class="review-btn"><i class="fas fa-chevron-right"></i> Review Thesis</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!$has_waiting): ?>
                <div class="empty-state" style="padding: 40px;"><i class="fas fa-check-circle"></i><p>No pending theses to review</p></div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Sidebar toggle
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
            document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
        }
        
        if (hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                toggleSidebar();
            }
        });
        
        // Profile dropdown
        const profileWrapper = document.getElementById('profileWrapper');
        const profileDropdown = document.getElementById('profileDropdown');
        
        if (profileWrapper) {
            profileWrapper.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });
            document.addEventListener('click', function(e) {
                if (!profileWrapper.contains(e.target) && profileDropdown.classList.contains('show')) {
                    profileDropdown.classList.remove('show');
                }
            });
        }
        
        // Notification dropdown
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationIcon) {
            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
            });
            document.addEventListener('click', function(e) {
                if (!notificationIcon.contains(e.target) && notificationDropdown && !notificationDropdown.contains(e.target)) {
                    notificationDropdown.classList.remove('show');
                }
            });
        }
        
        // Mark notifications as read
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
                        if (c > 0) {
                            c--;
                            if (c === 0) badge.style.display = 'none';
                            else badge.textContent = c;
                        }
                    }
                }
            });
        }
        
        document.querySelectorAll('.notification-item').forEach(item => {
            if (!item.classList.contains('empty')) {
                item.addEventListener('click', function() {
                    const id = this.dataset.id;
                    if (id) markNotificationAsRead(id, this);
                });
            }
        });
        
        // Dark mode
        const darkToggle = document.getElementById('darkmode');
        if (darkToggle) {
            darkToggle.addEventListener('change', function() {
                document.body.classList.toggle('dark-mode');
                localStorage.setItem('darkMode', darkToggle.checked);
            });
            if (localStorage.getItem('darkMode') === 'true') {
                darkToggle.checked = true;
                document.body.classList.add('dark-mode');
            }
        }
        
        // Search filter
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase();
                document.querySelectorAll('.thesis-item').forEach(item => {
                    const title = item.querySelector('.thesis-title')?.textContent.toLowerCase() || '';
                    const author = item.querySelector('.thesis-meta')?.textContent.toLowerCase() || '';
                    item.style.display = (title.includes(term) || author.includes(term)) ? 'flex' : 'none';
                });
            });
        }
        
        console.log('Coordinator Dashboard Loaded - Grouped by Department');
    </script>
</body>
</html>