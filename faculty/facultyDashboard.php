<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

$roleQuery = "SELECT user_id, first_name, last_name, role_id FROM user_table WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($roleQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userData) {
    session_destroy();
    header("Location: ../authentication/login.php?error=user_not_found");
    exit;
}

$required_role_id = 3;

if ($userData['role_id'] != $required_role_id) {
    if ($userData['role_id'] == 2) {
        header("Location: /ArchivingThesis/student/student_dashboard.php");
        exit;
    } elseif ($userData['role_id'] == 1) {
        header("Location: /ArchivingThesis/admin/admin_dashboard.php");
        exit;
    } else {
        header("Location: ../authentication/login.php?error=invalid_role");
        exit;
    }
}

$faculty_id = $user_id;

$stmt = $conn->prepare("SELECT user_id, first_name, last_name, email, role_id FROM user_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$faculty) {
    session_destroy();
    header("Location: ../authentication/login.php?error=invalid_session");
    exit;
}

$first = trim($faculty["first_name"] ?? "");
$last  = trim($faculty["last_name"] ?? "");
$fullName = trim($first . " " . $last);
$initials = $first && $last ? strtoupper(substr($first, 0, 1) . substr($last, 0, 1)) : "FA";

// =============== HANDLE FILE DOWNLOAD ===============
if (isset($_GET['download_manuscript']) && isset($_GET['thesis_id'])) {
    $download_id = (int)$_GET['thesis_id'];
    
    $fileQuery = "SELECT file_path, title FROM thesis_table WHERE thesis_id = ?";
    $stmt = $conn->prepare($fileQuery);
    $stmt->bind_param("i", $download_id);
    $stmt->execute();
    $fileResult = $stmt->get_result();
    $fileData = $fileResult->fetch_assoc();
    $stmt->close();
    
    if ($fileData && file_exists('../' . $fileData['file_path'])) {
        $file = '../' . $fileData['file_path'];
        $filename = basename($file);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        readfile($file);
        exit;
    }
}

// =============== ORIGINAL COUNTS ===============
$pendingCount = 0;
try {
    $pendingQuery = "SELECT COUNT(*) as total FROM thesis_table WHERE status = 'pending'";
    $pendingResult = $conn->query($pendingQuery);
    if ($pendingResult) {
        $pendingCount = $pendingResult->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Pending count error: " . $e->getMessage());
}

$approvedCount = 0;
try {
    $approvedQuery = "SELECT COUNT(*) as total FROM thesis_table WHERE status = 'approved'";
    $approvedResult = $conn->query($approvedQuery);
    if ($approvedResult) {
        $approvedCount = $approvedResult->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Approved count error: " . $e->getMessage());
}

$rejectedCount = 0;
try {
    $rejectedQuery = "SELECT COUNT(*) as total FROM feedback_table WHERE comments LIKE '%reject%' OR comments LIKE '%wrong%'";
    $rejectedResult = $conn->query($rejectedQuery);
    if ($rejectedResult) {
        $rejectedCount = $rejectedResult->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Rejected count error: " . $e->getMessage());
}

// =============== GET ALL SUBMISSIONS WITH MANUSCRIPT INFO ===============
$allSubmissions = [];
try {
    $submissionQuery = "SELECT 
                        t.*, 
                        u.first_name, 
                        u.last_name, 
                        u.email,
                        s.student_id,
                        (SELECT COUNT(*) FROM feedback_table f WHERE f.thesis_id = t.thesis_id) as feedback_count,
                        (SELECT MAX(feedback_date) FROM feedback_table f WHERE f.thesis_id = t.thesis_id) as last_feedback_date
                        FROM thesis_table t
                        JOIN user_table u ON t.student_id = u.user_id
                        JOIN student_table s ON u.user_id = s.user_id
                        ORDER BY t.date_submitted DESC";
    
    $result = $conn->query($submissionQuery);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $allSubmissions[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Faculty Dashboard - Submissions query error: " . $e->getMessage());
}

// =============== PENDING THESES FOR REVIEW ===============
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

// =============== NOTIFICATION QUERIES ===============
$unreadCount = 0;
$recentNotifications = [];

try {
    // Get unread count
    $countQuery = "SELECT COUNT(*) as total FROM notification_table 
                   WHERE user_id = ? AND status = 'unread'";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $countResult = $stmt->get_result()->fetch_assoc();
    $unreadCount = $countResult['total'] ?? 0;
    $stmt->close();
    
    // Get recent notifications
    $notifQuery = "SELECT 
                    n.notification_id as id, 
                    n.message,
                    n.status,
                    n.created_at,
                    n.thesis_id,
                    t.title as thesis_title,
                    u.first_name as student_first,
                    u.last_name as student_last
                  FROM notification_table n
                  LEFT JOIN thesis_table t ON n.thesis_id = t.thesis_id
                  LEFT JOIN user_table u ON t.student_id = u.user_id
                  WHERE n.user_id = ? 
                  ORDER BY n.created_at DESC 
                  LIMIT 10";
    
    $stmt = $conn->prepare($notifQuery);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $notifResult = $stmt->get_result();
    
    while ($row = $notifResult->fetch_assoc()) {
        $row['is_read'] = ($row['status'] == 'unread') ? 0 : 1;
        $recentNotifications[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Faculty Dashboard - Notification error: " . $e->getMessage());
}

$pageTitle = "Faculty Dashboard";
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
        }

        body.dark-mode {
            background: #2d2d2d;
            color: #e0e0e0;
        }

        .layout {
            min-height: 100vh;
            position: relative;
        }

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

        .sidebar.show {
            left: 0;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            color: white;
            font-weight: 700;
        }

        .sidebar-header p {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 0.25rem;
            transition: all 0.2s;
            font-weight: 500;
        }

        .nav-link i {
            width: 20px;
            color: white;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 600;
        }

        .nav-link.active i {
            color: white;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .logout-btn i {
            color: white;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .theme-toggle {
            margin-bottom: 1rem;
        }

        .theme-toggle input {
            display: none;
        }

        .toggle-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            cursor: pointer;
            position: relative;
        }

        .toggle-label i {
            font-size: 1rem;
            z-index: 1;
            padding: 0.25rem;
            color: white;
        }

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

        #darkmode:checked ~ .toggle-label .slider {
            transform: translateX(100%);
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .overlay.show {
            display: block;
        }

        .main-content {
            flex: 1;
            margin-left: 0;
            min-height: 100vh;
            padding: 2rem;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(110, 110, 110, 0.1);
        }

        body.dark-mode .topbar {
            background: #3a3a3a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .topbar h1 {
            font-size: 1.875rem;
            color: #000000;
        }

        body.dark-mode .topbar h1 {
            color: #FE4853;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

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
            transition: all 0.3s ease;
        }

        .hamburger-menu:hover {
            background: rgba(254, 72, 83, 0.1);
            color: #732529;
        }

        .avatar-dropdown {
            position: relative;
            display: inline-block;
        }

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
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 55px;
            background-color: white;
            min-width: 180px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            border-radius: 8px;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .dropdown-content.show {
            display: block;
            animation: fadeIn 0.2s ease;
        }

        .dropdown-content a {
            color: #475569;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: background-color 0.2s;
            font-size: 0.95rem;
        }

        .dropdown-content a i {
            width: 18px;
            color: #FE4853;
        }

        .dropdown-content a:hover {
            background-color: #f8fafc;
        }

        .dropdown-content hr {
            margin: 0;
            border: none;
            border-top: 1px solid #e9ecef;
        }

        .notification-container {
            position: relative;
            display: inline-block;
        }

        .notification-bell {
            position: relative;
            cursor: pointer;
            font-size: 1.25rem;
            color: #6E6E6E;
            transition: color 0.2s;
        }

        .notification-bell:hover {
            color: #FE4853;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #FE4853;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            animation: pulse 2s infinite;
        }

        .notification-dropdown {
            display: none;
            position: absolute;
            right: -10px;
            top: 45px;
            width: 350px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            border: 1px solid #e0e0e0;
            z-index: 1000;
        }

        body.dark-mode .notification-dropdown {
            background: #2d2d2d;
            border-color: #6E6E6E;
        }

        .notification-dropdown.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        .notification-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h4 {
            margin: 0;
            color: #000000;
            font-size: 1rem;
            font-weight: 600;
        }

        .notification-header a {
            color: #000000;
            text-decoration: none;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .notification-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
            cursor: pointer;
        }

        .notification-item:hover {
            background: #f5f5f5;
        }

        .notification-item.unread {
            background: #fff3f3;
            border-left: 3px solid #FE4853;
        }

        .notif-message {
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .notif-thesis {
            font-size: 0.8rem;
            color: #FE4853;
            margin-top: 3px;
            font-style: italic;
        }

        .notif-time {
            font-size: 0.75rem;
            color: #6E6E6E;
        }

        .no-notifications {
            text-align: center;
            color: #6E6E6E;
            padding: 20px 0;
        }

        .notification-footer {
            padding: 15px 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }

        .notification-footer a {
            color: #FE4853;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .welcome-banner {
            background: linear-gradient(135deg, #FE4853 0%, #732529 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(254, 72, 83, 0.3);
        }

        .welcome-banner h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .welcome-banner p {
            margin: 0;
            opacity: 0.9;
            font-size: 1rem;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
            text-align: center;
            transition: transform 0.18s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(254, 72, 83, 0.15);
        }

        body.dark-mode .stat-card {
            background: #3a3a3a;
        }

        .stat-icon {
            font-size: 2.2rem;
            color: #FE4853;
            margin-bottom: 0.8rem;
        }

        .stat-value {
            font-size: 2.4rem;
            font-weight: 700;
            color: #000000;
            line-height: 1.2;
            margin-bottom: 0.3rem;
        }

        .stat-label {
            color: #6E6E6E;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .pending-theses {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
            margin-bottom: 2rem;
        }

        .pending-theses h3 {
            color: #000000;
            margin: 0 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.3rem;
        }

        .theses-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .thesis-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            transition: transform 0.2s, background 0.2s;
        }

        .thesis-item:hover {
            transform: translateX(5px);
            background: #f1f5f9;
        }

        .thesis-info h4 {
            margin: 0 0 0.3rem 0;
            color: #333;
            font-size: 1rem;
        }

        .thesis-info p {
            margin: 0;
            font-size: 0.85rem;
            color: #6E6E6E;
        }

        .thesis-info p i {
            margin-right: 0.3rem;
        }

        .btn-review {
            padding: 0.5rem 1rem;
            background: #FE4853;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-review:hover {
            background: #000000;
        }

        .badge {
            background: #FE4853;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 8px;
        }

        .mobile-menu-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1001;
            border: none;
            background: #FE4853;
            color: #fff;
            padding: 12px 15px;
            border-radius: 10px;
            cursor: pointer;
            display: none;
            font-size: 1.2rem;
            box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
            border: 1px solid white;
        }

        /* =============== SUBMISSIONS SECTION =============== */
        .submissions-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
        }

        .submissions-section h3 {
            color: #000000;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        body.dark-mode .submissions-section h3 {
            color: #FE4853;
        }

        .submission-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 0.5rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 0.5rem 1rem;
            background: none;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            color: #6E6E6E;
            transition: all 0.2s;
        }

        .tab-btn:hover {
            background: #f0f0f0;
            color: #FE4853;
        }

        .tab-btn.active {
            background: #FE4853;
            color: white;
        }

        .submissions-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .submission-item {
            padding: 1.2rem;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #6E6E6E;
            transition: transform 0.2s;
        }

        .submission-item:hover {
            transform: translateX(5px);
        }

        body.dark-mode .submission-item {
            background: #4a4a4a;
        }

        .submission-item.status-pending {
            border-left-color: #f59e0b;
        }

        .submission-item.status-approved {
            border-left-color: #10b981;
        }

        .submission-item.status-rejected {
            border-left-color: #ef4444;
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .submission-header h4 {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
        }

        body.dark-mode .submission-header h4 {
            color: #e0e0e0;
        }

        .submission-header h4 i {
            color: #FE4853;
            margin-right: 0.5rem;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-badge.status-pending {
            background: #fef3c7;
            color: #f59e0b;
        }

        .status-badge.status-approved {
            background: #d1fae5;
            color: #10b981;
        }

        .status-badge.status-rejected {
            background: #fee2e2;
            color: #ef4444;
        }

        .submission-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .submission-details p {
            margin: 0;
            font-size: 0.9rem;
            color: #6E6E6E;
        }

        body.dark-mode .submission-details p {
            color: #b0b0b0;
        }

        .submission-details p i {
            color: #FE4853;
            margin-right: 0.3rem;
            width: 16px;
        }

        /* =============== MINIMALIST MANUSCRIPT ACTIONS =============== */
        .submission-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid #e0e0e0;
        }

        body.dark-mode .submission-actions {
            border-top-color: #6E6E6E;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #333;
            font-size: 0.9rem;
        }

        body.dark-mode .file-info {
            color: #e0e0e0;
        }

        .file-info i {
            color: #FE4853;
        }

        .file-actions {
            display: flex;
            gap: 0.75rem;
            margin-left: auto;
        }

        .file-actions a {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: color 0.2s;
        }

        .file-actions a:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .file-actions a.download {
            color: #10b981;
        }

        .file-actions a.download:hover {
            color: #059669;
        }

        .file-missing {
            color: #ef4444;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .no-file {
            color: #6E6E6E;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-review-small {
            padding: 0.25rem 0.75rem;
            background: #FE4853;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-left: auto;
        }

        .btn-review-small:hover {
            background: #d13b45;
        }

        .no-submissions {
            text-align: center;
            padding: 3rem;
            color: #6E6E6E;
        }

        .no-submissions i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #FE4853;
        }

        /* Modal for PDF viewer */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            position: relative;
            margin: 2% auto;
            width: 90%;
            height: 90%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e0e0e0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.2rem;
            color: #333;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6E6E6E;
        }

        .close-modal:hover {
            color: #FE4853;
        }

        .modal-body {
            height: calc(100% - 70px);
            padding: 1rem;
        }

        .pdf-frame {
            width: 100%;
            height: 100%;
            border: none;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .user-info {
                width: 100%;
                justify-content: flex-start;
                gap: 1rem;
            }
            .stats-overview {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .thesis-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .thesis-actions {
                width: 100%;
            }
            .btn-review {
                width: 100%;
                justify-content: center;
            }
            .notification-dropdown {
                width: 300px;
                right: -50px;
            }
            .submission-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .submission-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .file-actions {
                margin-left: 0;
            }
            .btn-review-small {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<div class="overlay" id="overlay"></div>
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Theses Archive</h2>
        <p>Faculty Portal</p>
    </div>

    <nav class="sidebar-nav">
        <a href="facultyDashboard.php" class="nav-link active">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="reviewThesis.php" class="nav-link">
            <i class="fas fa-book-reader"></i> Review Theses
            <?php if ($pendingCount > 0): ?>
                <span class="badge"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        <a href="facultyFeedback.php" class="nav-link">
            <i class="fas fa-comment-dots"></i> My Feedback
        </a>
        <a href="notification.php" class="nav-link">
            <i class="fas fa-bell"></i> Notifications
            <?php if ($unreadCount > 0): ?>
                <span class="badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </a>
        <a href="#" class="nav-link">
            <i class="fas fa-calendar-check"></i> Schedule
        </a>
        <a href="#" class="nav-link">
            <i class="fas fa-chart-line"></i> Statistics
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="theme-toggle">
            <input type="checkbox" id="darkmode" />
            <label for="darkmode" class="toggle-label">
                <i class="fas fa-sun"></i>
                <i class="fas fa-moon"></i>
                <span class="slider"></span>
            </label>
        </div>
        <a href="../authentication/logout.php" class="logout-btn">
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
                <h1>Faculty Dashboard</h1>
            </div>

            <div class="user-info">
                <div class="notification-container">
                    <div class="notification-bell" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <a href="#" id="markAllRead">Mark all as read</a>
                        </div>
                        <div class="notification-list">
                            <?php if (empty($recentNotifications)): ?>
                                <div class="notification-item">
                                    <div class="no-notifications">No new notifications</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentNotifications as $notif): ?>
                                    <div class="notification-item <?= $notif['status'] == 'unread' ? 'unread' : '' ?>"
                                         data-notification-id="<?= $notif['id'] ?? '' ?>"
                                         data-thesis-id="<?= $notif['thesis_id'] ?? 0 ?>"
                                         onclick="markAsReadAndRedirect(this)">
                                        <div class="notif-message">
                                            <?= htmlspecialchars($notif['message'] ?? '') ?>
                                        </div>
                                        <?php if (!empty($notif['thesis_title'])): ?>
                                            <div class="notif-thesis">
                                                <i class="fas fa-book"></i> <?= htmlspecialchars($notif['thesis_title']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="notif-time"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="notification-footer">
                            <a href="notification.php">View all notifications</a>
                        </div>
                    </div>
                </div>
                <div class="avatar-dropdown">
                    <div class="avatar" id="avatarBtn">
                        <?= htmlspecialchars($initials) ?>
                    </div>
                    <div class="dropdown-content" id="dropdownMenu">
                        <a href="facultyProfile.php">
                            <i class="fas fa-user-circle"></i> Profile
                        </a>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <hr>
                        <a href="../authentication/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <?php if (isset($_GET['debug'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 8px;">
            <strong>Debug Info:</strong><br>
            User ID: <?= $user_id ?><br>
            Role ID from DB: <?= $userData['role_id'] ?? 'N/A' ?><br>
            Required Role: <?= $required_role_id ?><br>
            Access: <?= ($userData['role_id'] == $required_role_id) ? 'GRANTED' : 'DENIED' ?><br>
            Full Name: <?= htmlspecialchars($fullName) ?>
        </div>
        <?php endif; ?>

        <div class="welcome-banner">
            <h2>Welcome, <?= htmlspecialchars($fullName) ?>!</h2>
            <p>Here's an overview of your advising and review activities.</p>
        </div>

        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?= $pendingCount ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?= $approvedCount ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value"><?= $rejectedCount ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <?php if (!empty($pendingTheses)): ?>
        <div class="pending-theses">
            <h3><i class="fas fa-clock"></i> Theses Waiting for Review</h3>
            <div class="theses-list">
                <?php foreach ($pendingTheses as $thesis): ?>
                <div class="thesis-item">
                    <div class="thesis-info">
                        <h4><?= htmlspecialchars($thesis['title']) ?></h4>
                        <p>
                            <i class="fas fa-user"></i> <?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?> | 
                            <i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($thesis['date_submitted'])) ?>
                        </p>
                    </div>
                    <div class="thesis-actions">
                        <a href="reviewThesis.php?id=<?= $thesis['thesis_id'] ?>" class="btn-review">
                            <i class="fas fa-search"></i> Review
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- =============== ALL THESIS SUBMISSIONS =============== -->
        <div class="submissions-section">
            <h3><i class="fas fa-file-alt"></i> All Thesis Submissions</h3>
            
            <div class="submission-tabs">
                <button class="tab-btn active" onclick="filterSubmissions('all')">All (<?= count($allSubmissions) ?>)</button>
                <button class="tab-btn" onclick="filterSubmissions('pending')">Pending (<?= count(array_filter($allSubmissions, function($s) { return $s['status'] == 'pending'; })) ?>)</button>
                <button class="tab-btn" onclick="filterSubmissions('approved')">Approved (<?= count(array_filter($allSubmissions, function($s) { return $s['status'] == 'approved'; })) ?>)</button>
                <button class="tab-btn" onclick="filterSubmissions('rejected')">Rejected (<?= count(array_filter($allSubmissions, function($s) { return $s['status'] == 'rejected'; })) ?>)</button>
            </div>
            
            <div class="submissions-list" id="submissionsList">
                <?php if (empty($allSubmissions)): ?>
                    <div class="no-submissions">
                        <i class="fas fa-folder-open"></i>
                        <p>No thesis submissions yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($allSubmissions as $submission): ?>
                        <div class="submission-item status-<?= $submission['status'] ?>" data-status="<?= $submission['status'] ?>">
                            <div class="submission-header">
                                <h4>
                                    <i class="fas fa-file-pdf"></i> 
                                    <?= htmlspecialchars($submission['title']) ?>
                                </h4>
                                <span class="status-badge status-<?= $submission['status'] ?>">
                                    <?= ucfirst($submission['status']) ?>
                                </span>
                            </div>
                            
                            <div class="submission-details">
                                <p>
                                    <i class="fas fa-user"></i> 
                                    <strong>Student:</strong> <?= htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']) ?>
                                </p>
                                <p>
                                    <i class="fas fa-calendar"></i> 
                                    <strong>Submitted:</strong> <?= date('F d, Y', strtotime($submission['date_submitted'])) ?>
                                </p>
                                <p>
                                    <i class="fas fa-comments"></i> 
                                    <strong>Feedback:</strong> <?= $submission['feedback_count'] ?> feedback(s)
                                </p>
                                <?php if ($submission['last_feedback_date']): ?>
                                <p>
                                    <i class="fas fa-clock"></i> 
                                    <strong>Last Feedback:</strong> <?= date('M d, Y', strtotime($submission['last_feedback_date'])) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="submission-actions">
                                <?php if (!empty($submission['file_path'])): ?>
                                    <?php 
                                    $full_path = '../' . $submission['file_path'];
                                    $file_exists = file_exists($full_path);
                                    $file_name = basename($submission['file_path']);
                                    ?>
                                    
                                    <?php if ($file_exists): ?>
                                        <div class="file-info">
                                            <i class="fas fa-file-pdf"></i>
                                            <span><?= htmlspecialchars($file_name) ?></span>
                                        </div>
                                        <div class="file-actions">
                                            <a href="<?= htmlspecialchars($full_path) ?>" target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="?download_manuscript=1&thesis_id=<?= $submission['thesis_id'] ?>" class="download">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="file-missing">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <span>File "<?= htmlspecialchars($file_name) ?>" not found</span>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="no-file">
                                        <i class="fas fa-file-pdf"></i>
                                        <span>No manuscript uploaded</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($submission['status'] == 'pending'): ?>
                                    <a href="reviewThesis.php?id=<?= $submission['thesis_id'] ?>" class="btn-review-small">
                                        <i class="fas fa-check-circle"></i> Review
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Modal for PDF viewer -->
<div id="pdfModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Viewing Manuscript</h2>
            <button class="close-modal" onclick="closePDFModal()">&times;</button>
        </div>
        <div class="modal-body">
            <iframe id="pdfFrame" class="pdf-frame" src=""></iframe>
        </div>
    </div>
</div>

<script>
    // =============== DARK MODE TOGGLE ===============
    const toggle = document.getElementById('darkmode');
    if (toggle) {
        toggle.addEventListener('change', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', toggle.checked);
        });
        if (localStorage.getItem('darkMode') === 'true') {
            toggle.checked = true;
            document.body.classList.add('dark-mode');
        }
    }

    // =============== AVATAR DROPDOWN ===============
    const avatarBtn = document.getElementById('avatarBtn');
    const dropdownMenu = document.getElementById('dropdownMenu');
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');

    if (avatarBtn) {
        avatarBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
            notificationDropdown.classList.remove('show');
        });
    }

    // =============== NOTIFICATION DROPDOWN ===============
    if (notificationBell) {
        notificationBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
            dropdownMenu.classList.remove('show');
        });
    }

    // =============== CLOSE DROPDOWNS ===============
    window.addEventListener('click', function() {
        if (notificationDropdown) notificationDropdown.classList.remove('show');
        if (dropdownMenu) dropdownMenu.classList.remove('show');
    });

    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    if (dropdownMenu) {
        dropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // =============== UPDATED NOTIFICATION FUNCTION WITH AJAX ===============
    function markAsReadAndRedirect(element) {
        var notificationId = element.getAttribute('data-notification-id');
        var thesisId = element.getAttribute('data-thesis-id');
        
        console.log('Clicked - ID:', notificationId, 'Thesis:', thesisId);
        
        if (!notificationId) {
            console.error('No notification ID');
            return;
        }
        
        // Show loading state
        element.style.opacity = '0.5';
        element.style.pointerEvents = 'none';
        
        // AJAX request to mark as read
        fetch('notification_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                action: 'mark_read',
                notification_id: notificationId 
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.success) {
                // Remove unread class
                element.classList.remove('unread');
                
                // Update badge count
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    let currentCount = parseInt(badge.textContent);
                    if (currentCount > 1) {
                        badge.textContent = currentCount - 1;
                        console.log('New badge count:', currentCount - 1);
                    } else {
                        badge.remove();
                        console.log('Badge removed');
                    }
                }
                
                // Redirect to review page
                if (thesisId && thesisId > 0 && thesisId != '0') {
                    window.location.href = 'reviewThesis.php?id=' + thesisId;
                } else {
                    // If no thesis ID, just reload to show updated notification list
                    location.reload();
                }
            } else {
                console.error('Server error:', data.error);
                element.style.opacity = '1';
                element.style.pointerEvents = 'auto';
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
            alert('Network error: ' + error.message);
        });
    }

    // =============== MARK ALL AS READ ===============
    document.getElementById('markAllRead')?.addEventListener('click', function(e) {
        e.preventDefault();
        
        console.log('Mark all as read clicked');
        
        fetch('notification_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'mark_all_read' })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Mark all response:', data);
            
            if (data.success) {
                // Remove unread class from all notifications
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                });
                
                // Remove badge
                const badge = document.querySelector('.notification-badge');
                if (badge) {
                    badge.remove();
                }
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // =============== MOBILE MENU ===============
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const hamburgerBtn = document.getElementById('hamburgerBtn');

    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
        const mobileIcon = mobileBtn?.querySelector('i');
        const hamburgerIcon = hamburgerBtn?.querySelector('i');
        
        if (sidebar.classList.contains('show')) {
            if (mobileIcon) {
                mobileIcon.classList.remove('fa-bars');
                mobileIcon.classList.add('fa-times');
            }
            if (hamburgerIcon) {
                hamburgerIcon.classList.remove('fa-bars');
                hamburgerIcon.classList.add('fa-times');
            }
        } else {
            if (mobileIcon) {
                mobileIcon.classList.remove('fa-times');
                mobileIcon.classList.add('fa-bars');
            }
            if (hamburgerIcon) {
                hamburgerIcon.classList.remove('fa-times');
                hamburgerIcon.classList.add('fa-bars');
            }
        }
    }

    if (mobileBtn) mobileBtn.addEventListener('click', toggleSidebar);
    if (hamburgerBtn) hamburgerBtn.addEventListener('click', toggleSidebar);

    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            
            const mobileIcon = mobileBtn?.querySelector('i');
            const hamburgerIcon = hamburgerBtn?.querySelector('i');
            
            if (mobileIcon) {
                mobileIcon.classList.remove('fa-times');
                mobileIcon.classList.add('fa-bars');
            }
            if (hamburgerIcon) {
                hamburgerIcon.classList.remove('fa-times');
                hamburgerIcon.classList.add('fa-bars');
            }
        });
    }

    // =============== FILTER SUBMISSIONS ===============
    function filterSubmissions(status) {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');
        
        const submissions = document.querySelectorAll('.submission-item');
        submissions.forEach(item => {
            if (status === 'all' || item.dataset.status === status) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // =============== PDF VIEWER ===============
    function viewManuscript(filePath, title) {
        document.getElementById('modalTitle').textContent = 'Viewing: ' + title;
        document.getElementById('pdfFrame').src = filePath;
        document.getElementById('pdfModal').style.display = 'block';
    }

    function closePDFModal() {
        document.getElementById('pdfModal').style.display = 'none';
        document.getElementById('pdfFrame').src = '';
    }

    window.onclick = function(event) {
        const modal = document.getElementById('pdfModal');
        if (event.target == modal) {
            closePDFModal();
        }
    }
</script>

</body>
</html>