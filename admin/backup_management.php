 <?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// LOGIN VALIDATION - ONLY ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$fullName = $first_name . " " . $last_name;
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// AUDIT LOGS TABLE
$conn->query("CREATE TABLE IF NOT EXISTS audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(255),
    table_name VARCHAR(100),
    record_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

function logAdminAction($conn, $user_id, $action, $table, $record_id, $description) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    if ($ip == '::1') $ip = '127.0.0.1';
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action_type, table_name, record_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table, $record_id, $description, $ip);
    $stmt->execute();
    $stmt->close();
}

// ========== CONFIGURATION ==========
$base_path = dirname(__DIR__);

// Uploads path
if (!defined('UPLOADS_PATH')) {
    $uploads_candidates = [
        $base_path . '/uploads',
        __DIR__ . '/uploads'
    ];
    $found = false;
    foreach ($uploads_candidates as $candidate) {
        if (is_dir($candidate)) {
            define('UPLOADS_PATH', realpath($candidate));
            $found = true;
            break;
        }
    }
    if (!$found) define('UPLOADS_PATH', $base_path . '/uploads');
}

if (!is_dir(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0777, true);
}

// Local backup path
if (!defined('LOCAL_BACKUP_PATH')) {
    define('LOCAL_BACKUP_PATH', $base_path . '/backup_storage');
}

if (!is_dir(LOCAL_BACKUP_PATH)) {
    mkdir(LOCAL_BACKUP_PATH, 0777, true);
}

// Rclone path detection
$rclone_paths = [
    'C:\\xampp\\htdocs\\rclone-v1.73.5-windows-amd64\\rclone.exe',
    $base_path . '/rclone-v1.73.5-windows-amd64/rclone.exe',
    'C:\\xampp\\htdocs\\rclone\\rclone.exe',
    'rclone'
];

$rclone_available = false;
$rclone_path_used = 'rclone';
foreach ($rclone_paths as $path) {
    if (file_exists($path)) {
        define('RCLONE_PATH', $path);
        $rclone_available = true;
        $rclone_path_used = $path;
        break;
    }
}

if (!defined('RCLONE_PATH')) {
    define('RCLONE_PATH', 'rclone');
}

if (!defined('GDRIVE_REMOTE')) {
    define('GDRIVE_REMOTE', 'gdrive:ThesesFOLDER');
}

// Check Google Drive configuration
$gdrive_configured = false;
$gdrive_backup_files = [];

if ($rclone_available && file_exists(RCLONE_PATH)) {
    $ver = shell_exec(RCLONE_PATH . " version 2>&1");
    if (strpos($ver, 'rclone') !== false) {
        $remote_name = explode(':', GDRIVE_REMOTE)[0];
        $remotes = shell_exec(RCLONE_PATH . " listremotes 2>&1");
        if (strpos($remotes, $remote_name) !== false) {
            $gdrive_configured = true;
            $cmd = RCLONE_PATH . " ls " . escapeshellarg(GDRIVE_REMOTE) . " 2>&1";
            exec($cmd, $output, $status);
            if ($status === 0) {
                foreach ($output as $line) {
                    if (preg_match('/^\s*\d+\s+(.+)$/', $line, $matches)) {
                        $gdrive_backup_files[] = trim($matches[1]);
                    }
                }
            }
        }
    }
}

// HELPER FUNCTIONS
function copyFileToDest($src, $dest_folder, $dest_filename = null) {
    if (!file_exists($src)) {
        return ['status' => 'error', 'message' => '❌ File not found!'];
    }
    if (!is_dir($dest_folder)) {
        mkdir($dest_folder, 0777, true);
    }
    $filename = $dest_filename ?: basename($src);
    $dst = $dest_folder . DIRECTORY_SEPARATOR . $filename;
    if (copy($src, $dst)) {
        return ['status' => 'success', 'message' => '✅ File backed up successfully!'];
    } else {
        return ['status' => 'error', 'message' => '❌ Backup failed!'];
    }
}

function hasUploadFiles() {
    if (!is_dir(UPLOADS_PATH)) return false;
    $files = scandir(UPLOADS_PATH);
    if ($files === false) return false;
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_file(UPLOADS_PATH . DIRECTORY_SEPARATOR . $file)) {
            return true;
        }
    }
    return false;
}

function backupAllLocal() {
    if (!hasUploadFiles()) {
        return ['status' => 'error', 'message' => '❌ No files found in uploads folder!'];
    }
    if (!is_dir(UPLOADS_PATH)) {
        return ['status' => 'error', 'message' => '❌ Uploads folder not found!'];
    }
    
    $remote = LOCAL_BACKUP_PATH;
    if (!is_dir($remote)) mkdir($remote, 0777, true);
    
    $count = 0;
    $files = scandir(UPLOADS_PATH);
    if ($files === false) {
        return ['status' => 'error', 'message' => '❌ Cannot read uploads directory!'];
    }
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') continue;
        $src = UPLOADS_PATH . DIRECTORY_SEPARATOR . $file;
        $dst = $remote . DIRECTORY_SEPARATOR . $file;
        if (is_file($src)) {
            if (copy($src, $dst)) {
                $count++;
            }
        }
    }
    
    if ($count > 0) {
        return ['status' => 'success', 'message' => "✅ Full backup completed! $count file(s) backed up."];
    } else {
        return ['status' => 'error', 'message' => '❌ No files were backed up!'];
    }
}

function backupFileLocal($source_path) {
    global $base_path;
    $possible_paths = [
        $base_path . '/' . $source_path,
        UPLOADS_PATH . '/' . basename($source_path),
        $source_path
    ];
    
    $src = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $src = $path;
            break;
        }
    }
    
    if (!$src) {
        return ['status' => 'error', 'message' => '❌ File not found!'];
    }
    return copyFileToDest($src, LOCAL_BACKUP_PATH);
}

function backupToGoogleDrive($source_path = null, $is_full_backup = false) {
    global $base_path;
    
    if ($is_full_backup && !hasUploadFiles()) {
        return ['status' => 'error', 'message' => '❌ No files found in uploads folder!'];
    }
    
    if (!RCLONE_PATH || !file_exists(RCLONE_PATH)) {
        return ['status' => 'error', 'message' => '❌ rclone not found! Please install rclone.'];
    }
    
    $remote_name = explode(':', GDRIVE_REMOTE)[0];
    $remotes = shell_exec(RCLONE_PATH . " listremotes 2>&1");
    if (strpos($remotes, $remote_name) === false) {
        return ['status' => 'error', 'message' => "❌ Google Drive remote not configured!"];
    }
    
    if ($is_full_backup) {
        $cmd = RCLONE_PATH . " sync " . escapeshellarg(UPLOADS_PATH) . " " . escapeshellarg(GDRIVE_REMOTE) . " 2>&1";
        exec($cmd, $output, $status);
        
        if ($status === 0) {
            return ['status' => 'success', 'message' => "✅ Full backup to Google Drive completed successfully!"];
        } else {
            return ['status' => 'error', 'message' => "❌ Backup to Google Drive failed!"];
        }
    } else {
        $possible_paths = [
            $base_path . '/' . $source_path,
            UPLOADS_PATH . '/' . basename($source_path)
        ];
        $src = null;
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $src = $path;
                break;
            }
        }
        if (!$src) {
            return ['status' => 'error', 'message' => '❌ File not found!'];
        }
        
        $cmd = RCLONE_PATH . " copy " . escapeshellarg($src) . " " . escapeshellarg(GDRIVE_REMOTE) . " 2>&1";
        exec($cmd, $output, $status);
        
        if ($status === 0) {
            return ['status' => 'success', 'message' => "✅ File uploaded to Google Drive successfully!"];
        } else {
            return ['status' => 'error', 'message' => "❌ Upload to Google Drive failed!"];
        }
    }
}

function restoreFromGoogleDrive($filename = null, $is_full_restore = false) {
    if (!RCLONE_PATH || !file_exists(RCLONE_PATH)) {
        return ['status' => 'error', 'message' => '❌ rclone not found!'];
    }
    
    if (!is_dir(UPLOADS_PATH)) {
        mkdir(UPLOADS_PATH, 0777, true);
    }
    
    if ($is_full_restore) {
        $cmd = RCLONE_PATH . " copy " . escapeshellarg(GDRIVE_REMOTE) . " " . escapeshellarg(UPLOADS_PATH) . " 2>&1";
        exec($cmd, $output, $status);
        
        if ($status === 0) {
            return ['status' => 'success', 'message' => "✅ Full restore from Google Drive completed successfully!"];
        } else {
            return ['status' => 'error', 'message' => "❌ Full restore failed!"];
        }
    } else {
        if (!$filename) return ['status' => 'error', 'message' => "❌ No file selected!"];
        
        $cmd = RCLONE_PATH . " copy " . escapeshellarg(GDRIVE_REMOTE . "/" . $filename) . " " . escapeshellarg(UPLOADS_PATH) . " 2>&1";
        exec($cmd, $output, $status);
        
        if ($status === 0) {
            return ['status' => 'success', 'message' => "✅ File restored from Google Drive successfully!"];
        } else {
            return ['status' => 'error', 'message' => "❌ Restore failed!"];
        }
    }
}

// ========== HANDLE POST REQUESTS ==========
$success_message = $error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['backup_all_local'])) {
        $res = backupAllLocal();
        if ($res['status'] === 'success') {
            $success_message = $res['message'];
            logAdminAction($conn, $user_id, "Local full backup", "backup", 0, "Admin performed local full backup");
        } else {
            $error_message = $res['message'];
        }
    }
    
    if (isset($_POST['backup_single_local']) && !empty($_POST['file_path_local'])) {
        $res = backupFileLocal($_POST['file_path_local']);
        if ($res['status'] === 'success') {
            $success_message = $res['message'];
            logAdminAction($conn, $user_id, "Local single backup", "backup", 0, "Admin backed up file locally");
        } else {
            $error_message = $res['message'];
        }
    }
    
    if (isset($_POST['backup_all_gdrive'])) {
        $res = backupToGoogleDrive(null, true);
        if ($res['status'] === 'success') {
            $success_message = $res['message'];
            logAdminAction($conn, $user_id, "Google Drive full backup", "backup", 0, "Admin performed full backup to Google Drive");
        } else {
            $error_message = $res['message'];
        }
    }
    
    if (isset($_POST['backup_single_gdrive']) && !empty($_POST['file_path_gdrive'])) {
        $res = backupToGoogleDrive($_POST['file_path_gdrive'], false);
        if ($res['status'] === 'success') {
            $success_message = $res['message'];
            logAdminAction($conn, $user_id, "Google Drive single backup", "backup", 0, "Admin uploaded file to Google Drive");
        } else {
            $error_message = $res['message'];
        }
    }
    
    if (isset($_POST['restore_all_gdrive'])) {
        $res = restoreFromGoogleDrive(null, true);
        if ($res['status'] === 'success') {
            $success_message = $res['message'];
            logAdminAction($conn, $user_id, "Full restore", "backup", 0, "Admin performed full restore from Google Drive");
        } else {
            $error_message = $res['message'];
        }
    }
    
    if (isset($_POST['restore_single_gdrive']) && !empty($_POST['restore_filename'])) {
        $res = restoreFromGoogleDrive(basename($_POST['restore_filename']), false);
        if ($res['status'] === 'success') {
            $success_message = $res['message'];
            logAdminAction($conn, $user_id, "Single restore", "backup", 0, "Admin restored file from Google Drive");
        } else {
            $error_message = $res['message'];
        }
    }
}

// GET ARCHIVED THESES
$thesis_files = [];
$thesis_query = $conn->query("SELECT thesis_id, title, file_path FROM thesis_table WHERE file_path IS NOT NULL AND file_path != '' AND is_archived = 1");
if ($thesis_query && $thesis_query->num_rows > 0) {
    while ($row = $thesis_query->fetch_assoc()) {
        $possible_paths = [
            $base_path . '/' . $row['file_path'],
            $base_path . '/uploads/' . basename($row['file_path']),
            UPLOADS_PATH . '/' . basename($row['file_path'])
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $thesis_files[] = $row;
                break;
            }
        }
    }
}

// Get notification count
$notificationCount = 0;
$notif_check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($notif_check && $notif_check->num_rows > 0) {
    $col_check = $conn->query("SHOW COLUMNS FROM notifications LIKE 'is_read'");
    if ($col_check && $col_check->num_rows > 0) {
        $n = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
        $n->bind_param("i", $user_id);
        $n->execute();
        $res = $n->get_result();
        if ($row = $res->fetch_assoc()) $notificationCount = $row['c'];
        $n->close();
    }
}

// GET RECENT NOTIFICATIONS
$recentNotifications = [];
$notif_list = $conn->prepare("SELECT notification_id, message, type, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notif_list->bind_param("i", $user_id);
$notif_list->execute();
$notif_result = $notif_list->get_result();
while ($row = $notif_result->fetch_assoc()) {
    $recentNotifications[] = $row;
}
$notif_list->close();

// DASHBOARDS
$dashboards = [
    1 => ['name' => 'Admin', 'icon' => 'fa-user-shield', 'color' => '#d32f2f', 'folder' => 'admin', 'file' => 'admindashboard.php'],
    2 => ['name' => 'Student', 'icon' => 'fa-user-graduate', 'color' => '#1976d2', 'folder' => 'student', 'file' => 'student_dashboard.php'],
    3 => ['name' => 'Research Adviser', 'icon' => 'fa-chalkboard-user', 'color' => '#388e3c', 'folder' => 'faculty', 'file' => 'facultyDashboard.php'],
    4 => ['name' => 'Dean', 'icon' => 'fa-user-tie', 'color' => '#f57c00', 'folder' => 'departmentDeanDashboard', 'file' => 'dean.php'],
    5 => ['name' => 'Librarian', 'icon' => 'fa-book-reader', 'color' => '#7b1fa2', 'folder' => 'librarian', 'file' => 'librarian_dashboard.php'],
    6 => ['name' => 'Coordinator', 'icon' => 'fa-clipboard-list', 'color' => '#e67e22', 'folder' => 'coordinator', 'file' => 'coordinatorDashboard.php']
];

// MARK NOTIFICATION AS READ (AJAX)
if (isset($_POST['mark_read']) && isset($_POST['notif_id'])) {
    $notif_id = intval($_POST['notif_id']);
    $update_query = "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("ii", $notif_id, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    $count_stmt = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $new_count = 0;
    if ($count_row = $count_result->fetch_assoc()) {
        $new_count = $count_row['c'];
    }
    $count_stmt->close();
    
    echo json_encode(['success' => true, 'new_count' => $new_count]);
    exit;
}

// MARK ALL NOTIFICATIONS AS READ
if (isset($_POST['mark_all_read'])) {
    $update_query = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
    echo json_encode(['success' => true, 'new_count' => 0]);
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Management | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/backup_management.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <header class="top-nav">
        <div class="nav-left">
            <button class="hamburger" id="hamburgerBtn">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="search-area">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search...">
            </div>
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
                        <h3><i class="fas fa-bell"></i> Notifications</h3>
                        <?php if ($notificationCount > 0): ?>
                            <button class="mark-all-read" id="markAllReadBtn">Mark all as read</button>
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
                                <a href="#" class="notification-item <?= $notif['is_read'] == 0 ? 'unread' : '' ?>" data-id="<?= $notif['notification_id'] ?>">
                                    <div class="notif-icon"><i class="fas fa-bell"></i></div>
                                    <div class="notif-content">
                                        <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="notif-time"><i class="far fa-clock"></i> <?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></div>
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
                    <a href="admin_profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="admin_settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <hr>
                    <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="logo-container">
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="admin-label">ADMINISTRATOR</div>
        </div>
        <div class="nav-menu">
            <a href="admindashboard.php" class="nav-item"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="users.php" class="nav-item"><i class="fas fa-users"></i><span>Users</span></a>
            <a href="audit_logs.php" class="nav-item"><i class="fas fa-history"></i><span>Audit Logs</span></a>
            <a href="theses.php" class="nav-item"><i class="fas fa-file-alt"></i><span>Theses</span></a>
            <a href="backup_management.php" class="nav-item active"><i class="fas fa-database"></i><span>Backup</span></a>
        </div>
        <div class="dashboard-links">
            <div class="dashboard-links-header"><i class="fas fa-chalkboard-user"></i><span>Quick Access</span></div>
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

    <main class="main-content">
        <div class="welcome-banner">
            <div class="welcome-info">
                <h1>Backup Management</h1>
                <p>Secure your thesis files – local & Google Drive</p>
            </div>
            <div class="admin-info">
                <div class="admin-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="admin-since">Admin since <?= date('F Y') ?></div>
            </div>
        </div>

        <!-- Toast Message -->
        <?php if ($success_message): ?>
        <div class="toast-message toast-success" id="toastMessage">
            <i class="fas fa-check-circle"></i>
            <span><?= htmlspecialchars($success_message) ?></span>
            <button class="toast-close" onclick="closeToast()">&times;</button>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="toast-message toast-error" id="toastMessage">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error_message) ?></span>
            <button class="toast-close" onclick="closeToast()">&times;</button>
        </div>
        <?php endif; ?>

        <div class="backup-section">
            <h3><i class="fas fa-database"></i> Backup & Restore</h3>

            <!-- LOCAL BACKUP -->
            <div style="margin-bottom: 30px;">
                <h4 style="color: #d32f2f; margin-bottom: 15px;"><i class="fas fa-hdd"></i> Local Backup (PC)</h4>
                <div class="backup-layout">
                    <div class="backup-col">
                        <div class="backup-card-item">
                            <h4><i class="fas fa-cloud-upload-alt"></i> Full Backup</h4>
                            <p>Backup all thesis files to: <strong><?= htmlspecialchars(basename(LOCAL_BACKUP_PATH)) ?></strong></p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to run full local backup?')">
                                <button type="submit" name="backup_all_local" class="btn-backup"><i class="fas fa-play"></i> Run Full Backup</button>
                            </form>
                            <button onclick="copyLocalPath()" class="btn-backup btn-secondary"><i class="fas fa-copy"></i> Copy Folder Path</button>
                            <input type="hidden" id="localPathValue" value="<?= addslashes(LOCAL_BACKUP_PATH) ?>">
                        </div>
                    </div>
                    <div class="backup-col">
                        <div class="backup-card-item">
                            <h4><i class="fas fa-file"></i> Single File Backup</h4>
                            <p>Select an ARCHIVED thesis to backup locally.</p>
                            <form method="POST" onsubmit="return confirmBackup()">
                                <select name="file_path_local" class="file-select" required>
                                    <option value="">-- Choose an archived thesis --</option>
                                    <?php foreach ($thesis_files as $thesis): ?>
                                        <option value="<?= htmlspecialchars($thesis['file_path']) ?>"><?= htmlspecialchars($thesis['title']) ?> (<?= htmlspecialchars(basename($thesis['file_path'])) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="backup_single_local" class="btn-backup"><i class="fas fa-file-export"></i> Backup Selected</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <hr style="margin: 20px 0; border-color: #ffcdd2;">

            <!-- GOOGLE DRIVE BACKUP & RESTORE -->
            <div>
                <h4 style="color: #d32f2f; margin-bottom: 15px;"><i class="fab fa-google-drive"></i> Google Drive Backup & Restore</h4>

                <?php if (!$rclone_available): ?>
                    <div class="alert-info"><i class="fas fa-exclamation-triangle"></i> rclone not found. Please install rclone first.</div>
                <?php elseif (!$gdrive_configured): ?>
                    <div class="alert-info"><i class="fas fa-exclamation-triangle"></i> Google Drive remote not configured. Run <code>rclone config</code>.</div>
                <?php endif; ?>

                <div class="backup-layout">
                    <div class="backup-col">
                        <div class="backup-card-item">
                            <h4><i class="fas fa-cloud-upload-alt"></i> Backup to Google Drive</h4>
                            <p>Sync entire uploads folder to: <strong><?= htmlspecialchars(GDRIVE_REMOTE) ?></strong></p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to sync to Google Drive?')">
                                <button type="submit" name="backup_all_gdrive" class="btn-backup" <?= (!$rclone_available || !$gdrive_configured) ? 'disabled' : '' ?>><i class="fab fa-google-drive"></i> Sync to Google Drive</button>
                            </form>
                            <div style="margin-top: 15px;"></div>
                            <p>Select an ARCHIVED thesis to upload individually.</p>
                            <form method="POST" onsubmit="return confirmUpload()">
                                <select name="file_path_gdrive" class="file-select" required>
                                    <option value="">-- Choose an archived thesis --</option>
                                    <?php foreach ($thesis_files as $thesis): ?>
                                        <option value="<?= htmlspecialchars($thesis['file_path']) ?>"><?= htmlspecialchars($thesis['title']) ?> (<?= htmlspecialchars(basename($thesis['file_path'])) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="backup_single_gdrive" class="btn-backup" <?= (!$rclone_available || !$gdrive_configured) ? 'disabled' : '' ?>><i class="fab fa-google-drive"></i> Upload Single File</button>
                            </form>
                            <a href="https://drive.google.com/drive/my-drive" target="_blank" class="folder-link"><i class="fab fa-google-drive"></i> Open Google Drive</a>
                        </div>
                    </div>

                    <div class="backup-col">
                        <div class="backup-card-item">
                            <h4><i class="fas fa-download"></i> Restore from Google Drive</h4>
                            <p>Restore all backup files back to the uploads folder.</p>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to restore all files from Google Drive?')">
                                <button type="submit" name="restore_all_gdrive" class="btn-backup" <?= (!$rclone_available || !$gdrive_configured) ? 'disabled' : '' ?>><i class="fas fa-sync-alt"></i> Full Restore</button>
                            </form>
                            <div style="margin-top: 15px;"></div>
                            <p>Select a backup file to restore individually.</p>
                            <form method="POST" onsubmit="return confirmRestore()">
                                <select name="restore_filename" class="file-select" required>
                                    <option value="">-- Choose a backup file --</option>
                                    <?php foreach ($gdrive_backup_files as $file): ?>
                                        <option value="<?= htmlspecialchars($file) ?>"><?= htmlspecialchars($file) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="restore_single_gdrive" class="btn-backup" <?= (!$rclone_available || !$gdrive_configured) ? 'disabled' : '' ?>><i class="fas fa-download"></i> Restore Selected</button>
                            </form>
                            <?php if ($rclone_available && $gdrive_configured && empty($gdrive_backup_files)): ?>
                                <p class="alert-info" style="margin-top:10px;"><i class="fas fa-info-circle"></i> No backup files found in Google Drive.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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
            if (sidebar.classList.contains('open')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        if (hamburgerBtn) {
            hamburgerBtn.addEventListener('click', toggleSidebar);
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }
        
        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (sidebar.classList.contains('open')) toggleSidebar();
                if (notificationDropdown) notificationDropdown.classList.remove('show');
            }
        });
        
        // Copy local path
        function copyLocalPath() {
            const pathInput = document.getElementById('localPathValue');
            if (pathInput) {
                const path = pathInput.value;
                navigator.clipboard.writeText(path).then(function() {
                    alert('Path copied: ' + path);
                }).catch(function() {
                    alert('Failed to copy path');
                });
            }
        }
        
        // Confirm functions
        function confirmBackup() {
            return confirm('Are you sure you want to backup this file locally?');
        }
        
        function confirmUpload() {
            return confirm('Are you sure you want to upload this file to Google Drive?');
        }
        
        function confirmRestore() {
            return confirm('Are you sure you want to restore this file from Google Drive?');
        }
        
        // Toast message auto close
        function closeToast() {
            const toast = document.getElementById('toastMessage');
            if (toast) {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }
        }
        
        // Auto close toast after 5 seconds
        setTimeout(function() {
            const toast = document.getElementById('toastMessage');
            if (toast) {
                closeToast();
            }
        }, 5000);
        
        // Notification dropdown
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationDropdown = document.getElementById('notificationDropdown');
        
        if (notificationIcon) {
            notificationIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('show');
            });
        }
        
        document.addEventListener('click', function() {
            if (notificationDropdown) {
                notificationDropdown.classList.remove('show');
            }
        });
        
        if (notificationDropdown) {
            notificationDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        // Mark notification as read
        function markNotificationAsRead(notifId, element) {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_read=1&notif_id=' + notifId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    element.classList.remove('unread');
                    const badge = document.getElementById('notificationBadge');
                    if (badge) {
                        let count = parseInt(badge.textContent);
                        if (count > 0) {
                            count--;
                            if (count === 0) {
                                badge.style.display = 'none';
                            } else {
                                badge.textContent = count;
                            }
                        }
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Mark all as read
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function() {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'mark_all_read=1'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        }
        
        // Handle notification item clicks
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const notifId = this.dataset.id;
                if (notifId && this.classList.contains('unread')) {
                    markNotificationAsRead(notifId, this);
                }
            });
        });
        
        // Dark mode
        const darkModeToggle = document.getElementById('darkmode');
        if (darkModeToggle) {
            if (localStorage.getItem('darkMode') === 'true') {
                document.body.classList.add('dark-mode');
                darkModeToggle.checked = true;
            }
            
            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.body.classList.add('dark-mode');
                    localStorage.setItem('darkMode', 'true');
                } else {
                    document.body.classList.remove('dark-mode');
                    localStorage.setItem('darkMode', 'false');
                }
            });
        }
        
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const backupCards = document.querySelectorAll('.backup-card-item');
                backupCards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
        
        console.log('Backup Management Page Loaded');
    </script>
</body>
</html>