<?php
session_start();
include("../config/db.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// Get user info for display
$stmt = $conn->prepare("SELECT first_name, last_name FROM user_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$first = trim($user["first_name"] ?? "");
$last  = trim($user["last_name"] ?? "");
$initials = $first && $last ? strtoupper(substr($first, 0, 1) . substr($last, 0, 1)) : "U";

// Mark single notification as read
if (isset($_GET['mark_read'])) {
    $notif_id = (int)$_GET['mark_read'];
    $updateQuery = "UPDATE notification_table SET status = 'read' WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: notification.php");
    exit;
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $updateQuery = "UPDATE notification_table SET status = 'read' WHERE user_id = ? AND status = 'unread'";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: notification.php");
    exit;
}

// Delete notification
if (isset($_GET['delete'])) {
    $notif_id = (int)$_GET['delete'];
    $deleteQuery = "DELETE FROM notification_table WHERE notification_id = ? AND user_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $notif_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: notification.php");
    exit;
}

// Get all notifications for this user
$notifications = [];
$unreadCount = 0;

try {
    // Get unread count
    $countQuery = "SELECT COUNT(*) as total FROM notification_table WHERE user_id = ? AND status = 'unread'";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $countResult = $stmt->get_result()->fetch_assoc();
    $unreadCount = $countResult['total'] ?? 0;
    $stmt->close();
    
    // Get all notifications with thesis details
    $query = "SELECT n.*, t.title as thesis_title 
              FROM notification_table n
              LEFT JOIN thesis_table t ON n.thesis_id = t.thesis_id
              WHERE n.user_id = ? 
              ORDER BY n.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
}

$pageTitle = "Notifications";
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

        /* Main Content */
        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Topbar */
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
            color: #732529;
        }

        body.dark-mode .topbar h1 {
            color: #FE4853;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        /* Avatar */
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
            border: 2px solid white;
        }

        .avatar:hover {
            transform: scale(1.05);
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #FE4853;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-link:hover {
            color: #732529;
            transform: translateX(-5px);
        }

        body.dark-mode .back-link {
            background: #3a3a3a;
            color: #FE4853;
        }

        /* Notifications Container */
        .notifications-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
        }

        body.dark-mode .notifications-container {
            background: #3a3a3a;
        }

        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        body.dark-mode .notifications-header {
            border-bottom-color: #6E6E6E;
        }

        .notifications-header h2 {
            color: #732529;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        body.dark-mode .notifications-header h2 {
            color: #FE4853;
        }

        .notifications-header h2 i {
            color: #FE4853;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-primary {
            background: #FE4853;
            color: white;
        }

        .btn-primary:hover {
            background: #732529;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #6E6E6E;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
            transform: translateY(-2px);
        }

        body.dark-mode .btn-secondary {
            background: #4a4a4a;
            color: #e0e0e0;
        }

        body.dark-mode .btn-secondary:hover {
            background: #5a5a5a;
        }

        /* Notification List */
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notification-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #FE4853;
            transition: all 0.3s;
        }

        .notification-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(254, 72, 83, 0.1);
        }

        .notification-item.unread {
            background: #fff3f3;
            border-left-width: 6px;
        }

        body.dark-mode .notification-item {
            background: #4a4a4a;
        }

        body.dark-mode .notification-item.unread {
            background: #3a1a1a;
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            background: #FE4853;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-message {
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        body.dark-mode .notification-message {
            color: #e0e0e0;
        }

        .notification-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.85rem;
            color: #6E6E6E;
        }

        .notification-meta i {
            margin-right: 0.3rem;
            color: #FE4853;
        }

        .notification-thesis {
            font-size: 0.85rem;
            color: #FE4853;
            margin-top: 0.3rem;
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .action-btn.mark-read {
            background: #10b981;
            color: white;
        }

        .action-btn.mark-read:hover {
            background: #059669;
        }

        .action-btn.delete {
            background: #ef4444;
            color: white;
        }

        .action-btn.delete:hover {
            background: #dc2626;
        }

        .no-notifications {
            text-align: center;
            color: #6E6E6E;
            padding: 4rem;
        }

        .no-notifications i {
            font-size: 4rem;
            color: #FE4853;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-notifications h3 {
            color: #732529;
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .notifications-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .header-actions {
                width: 100%;
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .notification-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .notification-actions {
                width: 100%;
                justify-content: flex-end;
            }

            .notification-meta {
                flex-direction: column;
                gap: 0.3rem;
            }
        }

        @media (max-width: 480px) {
            .topbar h1 {
                font-size: 1.3rem;
            }

            .notification-item {
                padding: 1rem;
            }

            .notification-icon {
                width: 30px;
                height: 30px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

<div class="layout">
    <main class="main-content">

        <header class="topbar">
            <h1>Notifications</h1>
            <div class="user-info">
                <div class="avatar"><?= htmlspecialchars($initials) ?></div>
            </div>
        </header>

        <a href="student_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="notifications-container">
            <div class="notifications-header">
                <h2>
                    <i class="fas fa-bell"></i>
                    All Notifications
                    <?php if ($unreadCount > 0): ?>
                        <span style="background: #FE4853; color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.8rem; margin-left: 0.5rem;">
                            <?= $unreadCount ?> unread
                        </span>
                    <?php endif; ?>
                </h2>
                <div class="header-actions">
                    <?php if ($unreadCount > 0): ?>
                        <a href="?mark_all_read=1" class="btn btn-primary">
                            <i class="fas fa-check-double"></i> Mark All as Read
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="notification-list">
                <?php if (empty($notifications)): ?>
                    <div class="no-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No Notifications</h3>
                        <p>You don't have any notifications at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?= $notif['status'] == 'unread' ? 'unread' : '' ?>">
                            <div class="notification-icon">
                                <i class="fas <?= $notif['status'] == 'unread' ? 'fa-bell' : 'fa-bell-slash' ?>"></i>
                            </div>
                            
                            <div class="notification-content">
                                <div class="notification-message">
                                    <?= htmlspecialchars($notif['message']) ?>
                                </div>
                                
                                <?php if (!empty($notif['thesis_title'])): ?>
                                    <div class="notification-thesis">
                                        <i class="fas fa-book"></i> <?= htmlspecialchars($notif['thesis_title']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="notification-meta">
                                    <span>
                                        <i class="fas fa-calendar"></i> <?= date('F d, Y', strtotime($notif['created_at'])) ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock"></i> <?= date('h:i A', strtotime($notif['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="notification-actions">
                                <?php if ($notif['status'] == 'unread'): ?>
                                    <a href="?mark_read=<?= $notif['notification_id'] ?>" class="action-btn mark-read">
                                        <i class="fas fa-check"></i> Mark Read
                                    </a>
                                <?php endif; ?>
                                <a href="?delete=<?= $notif['notification_id'] ?>" class="action-btn delete" onclick="return confirm('Delete this notification?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </main>
</div>

<script>
    // Dark mode toggle (optional)
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && prefersDark)) {
        document.body.classList.add('dark-mode');
    }
</script>

</body>
</html>