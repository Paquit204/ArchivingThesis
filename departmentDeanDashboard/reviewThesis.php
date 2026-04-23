<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a Dean
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'dean') {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$first_name = $_SESSION['first_name'] ?? '';
$last_name = $_SESSION['last_name'] ?? '';
$fullName = $first_name . " " . $last_name;
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));

// Get thesis ID from URL
$thesis_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get thesis details from database
$thesis = null;
$thesis_title = 'Unknown Thesis';
$thesis_author = 'Unknown Author';
$thesis_abstract = 'No abstract available.';
$thesis_file = '';
$thesis_date = '';
$thesis_status = '';
$student_id = 0;
$adviser_name = '';

if ($thesis_id > 0) {
    $thesis_query = "SELECT * FROM thesis_table WHERE thesis_id = ?";
    $thesis_stmt = $conn->prepare($thesis_query);
    $thesis_stmt->bind_param("i", $thesis_id);
    $thesis_stmt->execute();
    $thesis_result = $thesis_stmt->get_result();
    if ($thesis_row = $thesis_result->fetch_assoc()) {
        $thesis = $thesis_row;
        $thesis_title = $thesis_row['title'];
        $thesis_abstract = $thesis_row['abstract'] ?? 'No abstract available.';
        $thesis_file = $thesis_row['file_path'] ?? '';
        $thesis_date = isset($thesis_row['date_submitted']) ? date('M d, Y', strtotime($thesis_row['date_submitted'])) : date('M d, Y');
        $thesis_status = $thesis_row['is_read'] ?? 'pending';  // GAMITIN ANG is_read
        $student_id = $thesis_row['student_id'] ?? 0;
        $adviser_name = $thesis_row['adviser'] ?? '';
        
        // Get student name
        $student_query = "SELECT first_name, last_name FROM user_table WHERE user_id = ?";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bind_param("i", $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        if ($student_row = $student_result->fetch_assoc()) {
            $thesis_author = $student_row['first_name'] . " " . $student_row['last_name'];
        }
        $student_stmt->close();
    }
    $thesis_stmt->close();
}

// Get coordinator name who forwarded this thesis
$coordinator_name = '';
$coordinator_query = "SELECT message FROM notifications WHERE thesis_id = ? AND type LIKE '%forward%' ORDER BY created_at DESC LIMIT 1";
$coordinator_stmt = $conn->prepare($coordinator_query);
$coordinator_stmt->bind_param("i", $thesis_id);
$coordinator_stmt->execute();
$coordinator_result = $coordinator_stmt->get_result();
if ($coordinator_row = $coordinator_result->fetch_assoc()) {
    $msg = $coordinator_row['message'];
    if (preg_match('/by (.+?)(\.|$)/', $msg, $matches)) {
        $coordinator_name = trim($matches[1]);
    }
}
$coordinator_stmt->close();

// ==================== FUNCTION TO NOTIFY LIBRARIAN ====================
function notifyLibrarian($conn, $thesis_id, $thesis_title, $student_name, $dean_name) {
    $lib_query = "SELECT user_id FROM user_table WHERE role_id = 5";
    $lib_result = $conn->query($lib_query);
    
    if (!$lib_result || $lib_result->num_rows == 0) {
        error_log("No librarian found with role_id = 5");
        return false;
    }
    
    $notified = false;
    while ($librarian = $lib_result->fetch_assoc()) {
        $librarian_id = $librarian['user_id'];
        $message = "📚 Thesis ready for archiving: \"" . $thesis_title . "\" from student " . $student_name . ". Approved by Dean: " . $dean_name;
        $link = "../librarian/archiveThesis.php?id=" . $thesis_id;
        
        $insert_sql = "INSERT INTO notifications (user_id, thesis_id, message, type, link, is_read, created_at) 
                       VALUES ($librarian_id, $thesis_id, '$message', 'dean_approved', '$link', 0, NOW())";
        
        if ($conn->query($insert_sql)) {
            $notified = true;
        }
    }
    return $notified;
}

// Process form submission - Forward to Librarian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forward_to_librarian'])) {
    $thesis_id_post = intval($_POST['thesis_id']);
    $dean_feedback = isset($_POST['dean_feedback']) ? trim($_POST['dean_feedback']) : '';
    
    // GAMITIN ANG is_read COLUMN
    $update_query = "UPDATE thesis_table SET is_read = 'approved' WHERE thesis_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $thesis_id_post);
    $update_stmt->execute();
    $update_stmt->close();
    
    if ($student_id > 0) {
        $student_msg = "✅ Good news! Your thesis \"" . $thesis_title . "\" has been APPROVED by Dean " . $fullName . " and forwarded to the Librarian for archiving.";
        if (!empty($dean_feedback)) {
            $student_msg .= " Feedback: " . $dean_feedback;
        }
        $notif_student = "INSERT INTO notifications (user_id, thesis_id, message, type, is_read, created_at) VALUES ($student_id, $thesis_id_post, '$student_msg', 'student_approved', 0, NOW())";
        $conn->query($notif_student);
    }
    
    if (!empty($adviser_name)) {
        $get_adviser = "SELECT user_id FROM user_table WHERE CONCAT(first_name, ' ', last_name) = ? AND role_id = 3";
        $adviser_stmt = $conn->prepare($get_adviser);
        $adviser_stmt->bind_param("s", $adviser_name);
        $adviser_stmt->execute();
        $adviser_result = $adviser_stmt->get_result();
        if ($adviser_row = $adviser_result->fetch_assoc()) {
            $adviser_id = $adviser_row['user_id'];
            $adviser_msg = "✅ Thesis \"" . $thesis_title . "\" has been APPROVED by Dean " . $fullName . " and forwarded to the Librarian.";
            if (!empty($dean_feedback)) {
                $adviser_msg .= " Feedback: " . $dean_feedback;
            }
            $notif_adviser = "INSERT INTO notifications (user_id, thesis_id, message, type, is_read, created_at) VALUES ($adviser_id, $thesis_id_post, '$adviser_msg', 'dean_approved', 0, NOW())";
            $conn->query($notif_adviser);
        }
        $adviser_stmt->close();
    }
    
    $student_name = $thesis_author;
    notifyLibrarian($conn, $thesis_id_post, $thesis_title, $student_name, $fullName);
    
    $get_coordinator = "SELECT user_id FROM user_table WHERE role_id = 4";
    $coord_result = $conn->query($get_coordinator);
    if ($coord_result && $coord_result->num_rows > 0) {
        while ($coord = $coord_result->fetch_assoc()) {
            $coord_msg = "✅ Thesis \"" . $thesis_title . "\" has been APPROVED by Dean " . $fullName . " and forwarded to Librarian.";
            $notif_coord = "INSERT INTO notifications (user_id, thesis_id, message, type, is_read, created_at) VALUES (" . $coord['user_id'] . ", $thesis_id_post, '$coord_msg', 'coordinator_info', 0, NOW())";
            $conn->query($notif_coord);
        }
    }
    
    $dean_msg = "✅ You approved thesis \"" . $thesis_title . "\" and forwarded to Librarian";
    $notif_dean = "INSERT INTO notifications (user_id, thesis_id, message, type, is_read, created_at) VALUES ($user_id, $thesis_id_post, '$dean_msg', 'dean_action', 0, NOW())";
    $conn->query($notif_dean);
    
    header("Location: dean.php?section=dashboard&msg=forwarded");
    exit;
}

// Process form submission - Return to Coordinator
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return_to_coordinator'])) {
    $thesis_id_post = intval($_POST['thesis_id']);
    $dean_feedback = isset($_POST['dean_feedback']) ? trim($_POST['dean_feedback']) : '';
    
    // GAMITIN ANG is_read COLUMN
    $update_query = "UPDATE thesis_table SET is_read = 'revision_needed' WHERE thesis_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $thesis_id_post);
    $update_stmt->execute();
    $update_stmt->close();
    
    if ($student_id > 0) {
        $student_msg = "❌ Your thesis \"" . $thesis_title . "\" needs revision as per Dean's review. Please work with your adviser.";
        if (!empty($dean_feedback)) {
            $student_msg .= " Feedback: " . $dean_feedback;
        }
        $notif_student = "INSERT INTO notifications (user_id, thesis_id, message, type, is_read, created_at) VALUES ($student_id, $thesis_id_post, '$student_msg', 'student_revision', 0, NOW())";
        $conn->query($notif_student);
    }
    
    $get_coordinator = "SELECT user_id FROM user_table WHERE role_id = 4";
    $coord_result = $conn->query($get_coordinator);
    if ($coord_result && $coord_result->num_rows > 0) {
        while ($coord = $coord_result->fetch_assoc()) {
            $coord_msg = "⚠️ Thesis \"" . $thesis_title . "\" was returned by Dean " . $fullName . " for revision.";
            if (!empty($dean_feedback)) {
                $coord_msg .= " Feedback: " . $dean_feedback;
            }
            $notif_coord = "INSERT INTO notifications (user_id, thesis_id, message, type, is_read, created_at) VALUES (" . $coord['user_id'] . ", $thesis_id_post, '$coord_msg', 'coordinator_revision', 0, NOW())";
            $conn->query($notif_coord);
        }
    }
    
    $dean_msg = "❌ You returned thesis \"" . $thesis_title . "\" to Coordinator for revision";
    $notif_dean = "INSERT INTO notifications (user_id, thesis_id, message, type, is_read, created_at) VALUES ($user_id, $thesis_id_post, '$dean_msg', 'dean_action', 0, NOW())";
    $conn->query($notif_dean);
    
    header("Location: dean.php?section=dashboard&msg=returned");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Thesis | Dean Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #fef2f2; color: #1f2937; }
        
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
        .logo { font-size: 1.3rem; font-weight: 700; color: #991b1b; }
        .logo span { color: #dc2626; }
        .profile-wrapper { position: relative; }
        .profile-trigger { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .profile-name { font-weight: 500; color: #1f2937; }
        .profile-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #dc2626, #991b1b);
            border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .profile-dropdown { position: absolute; top: 55px; right: 0; background: white; border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); min-width: 200px; display: none; z-index: 100; }
        .profile-dropdown.show { display: block; }
        .profile-dropdown a { display: flex; align-items: center; gap: 12px; padding: 12px 18px;
            text-decoration: none; color: #1f2937; }
        .profile-dropdown a:hover { background: #fef2f2; color: #dc2626; }
        
        .sidebar { position: fixed; top: 0; left: -280px; width: 280px; height: 100%;
            background: linear-gradient(180deg, #991b1b 0%, #dc2626 100%);
            z-index: 1000; transition: left 0.3s ease; }
        .sidebar.open { left: 0; }
        .logo-container { padding: 28px 24px; border-bottom: 1px solid rgba(255,255,255,0.15); }
        .logo-container .logo { color: white; }
        .nav-menu { padding: 24px 16px; display: flex; flex-direction: column; gap: 4px; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 12px 16px;
            border-radius: 12px; text-decoration: none; color: #fecaca; font-weight: 500; }
        .nav-item:hover { background: rgba(255,255,255,0.15); color: white; }
        .nav-item.active { background: rgba(255,255,255,0.2); color: white; }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4); z-index: 999; display: none; }
        .sidebar-overlay.show { display: block; }
        
        .main-content { margin-left: 0; margin-top: 70px; padding: 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .page-header h2 { font-size: 1.75rem; font-weight: 700; color: #991b1b; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; color: #dc2626;
            text-decoration: none; padding: 8px 16px; background: #fef2f2; border-radius: 30px; }
        
        .thesis-detail-card { background: white; border-radius: 24px; padding: 32px;
            margin-bottom: 32px; border: 1px solid #fee2e2; }
        .thesis-title { font-size: 1.5rem; font-weight: 700; color: #1f2937; margin-bottom: 16px;
            padding-bottom: 16px; border-bottom: 1px solid #fee2e2; }
        .thesis-meta { display: flex; gap: 24px; margin-bottom: 24px; flex-wrap: wrap; }
        .meta-item { display: flex; align-items: center; gap: 8px; color: #6b7280; font-size: 0.85rem; }
        .meta-item i { color: #dc2626; }
        .abstract-section h4 { font-size: 1rem; font-weight: 600; color: #991b1b; margin-bottom: 12px; }
        .abstract-section p { color: #4b5563; line-height: 1.6; }
        
        .file-section { background: #fef2f2; border-radius: 16px; padding: 16px 20px;
            margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; }
        .file-info { display: flex; align-items: center; gap: 12px; }
        .file-info i { font-size: 1.5rem; color: #dc2626; }
        .download-btn { padding: 8px 16px; background: white; color: #dc2626;
            text-decoration: none; border-radius: 30px; border: 1px solid #fee2e2; }
        
        .pdf-viewer { border-radius: 12px; overflow: hidden; border: 1px solid #fee2e2; }
        .pdf-viewer iframe { width: 100%; height: 600px; border: none; }
        
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 30px;
            font-size: 0.75rem; font-weight: 600; margin-bottom: 20px; }
        .status-pending { background: #fef3c7; color: #d97706; }
        
        .action-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-top: 24px; }
        .action-card { background: white; border-radius: 24px; padding: 28px; border: 1px solid #fee2e2; }
        .action-icon { width: 50px; height: 50px; background: #fef2f2; border-radius: 16px;
            display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .action-icon i { font-size: 1.5rem; color: #dc2626; }
        .action-card h3 { font-size: 1.2rem; font-weight: 600; color: #991b1b; margin-bottom: 12px; }
        .action-card p { color: #6b7280; margin-bottom: 24px; }
        
        .btn-forward { width: 100%; padding: 14px; background: #10b981; color: white;
            border: none; border-radius: 14px; font-weight: 600; cursor: pointer; }
        .btn-forward:hover { background: #059669; }
        .btn-return { width: 100%; padding: 14px; background: #f59e0b; color: white;
            border: none; border-radius: 14px; font-weight: 600; cursor: pointer; }
        .btn-return:hover { background: #d97706; }
        
        .info-card { background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 24px;
            padding: 28px; text-align: center; }
        .info-card i { font-size: 3rem; color: #0284c7; margin-bottom: 16px; }
        .info-card h3 { color: #0369a1; margin-bottom: 12px; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1100; align-items: center; justify-content: center; }
        .modal.show { display: flex; }
        .modal-content { background: white; border-radius: 24px; width: 500px; max-width: 90%; }
        .modal-header { padding: 20px 24px; border-bottom: 1px solid #fee2e2;
            display: flex; justify-content: space-between; align-items: center; }
        .close-modal { font-size: 1.5rem; cursor: pointer; }
        .modal-body { padding: 24px; }
        .modal-footer { padding: 20px 24px; border-top: 1px solid #fee2e2;
            display: flex; justify-content: flex-end; gap: 12px; }
        .btn-cancel { padding: 10px 20px; background: #fef2f2; border: none; border-radius: 10px; cursor: pointer; }
        
        body.dark-mode { background: #1a1a1a; }
        body.dark-mode .top-nav { background: #2d2d2d; }
        body.dark-mode .thesis-detail-card { background: #2d2d2d; }
        body.dark-mode .action-card { background: #2d2d2d; }
        body.dark-mode .modal-content { background: #2d2d2d; }
        body.dark-mode .profile-dropdown { background: #2d2d2d; }
        body.dark-mode .profile-dropdown a { color: #e5e7eb; }
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
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="editProfile.php"><i class="fas fa-edit"></i> Edit Profile</a>
                    <hr>
                    <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="logo-container"><div class="logo">Thesis<span>Manager</span></div></div>
        <div class="nav-menu">
            <a href="dean.php?section=dashboard" class="nav-item"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="reviewThesis.php" class="nav-item active"><i class="fas fa-file-alt"></i><span>Review Theses</span></a>
            <a href="dean.php?section=department" class="nav-item"><i class="fas fa-building"></i><span>Department</span></a>
            <a href="dean.php?section=reports" class="nav-item"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
        </div>
        <div class="nav-footer">
            <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h2>Review Thesis</h2>
            <a href="dean.php?section=dashboard" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if (!$thesis): ?>
        <div class="thesis-detail-card">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-file-alt" style="font-size: 3rem; color: #dc2626; margin-bottom: 16px;"></i>
                <h3 style="color: #991b1b;">Thesis Not Found</h3>
                <a href="dean.php?section=dashboard" class="back-link" style="margin-top: 20px;">Go back to Dashboard</a>
            </div>
        </div>
        <?php else: ?>
        
        <div class="thesis-detail-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                <h1 class="thesis-title" style="margin-bottom: 0; padding-bottom: 0; border-bottom: none;"><?= htmlspecialchars($thesis_title) ?></h1>
                <span class="status-badge status-pending">
                    <?= htmlspecialchars(ucfirst($thesis_status)) ?>
                </span>
            </div>
            
            <div class="thesis-meta">
                <div class="meta-item"><i class="fas fa-user"></i><span>Student: <?= htmlspecialchars($thesis_author) ?></span></div>
                <div class="meta-item"><i class="fas fa-chalkboard-user"></i><span>Adviser: <?= htmlspecialchars($adviser_name) ?></span></div>
                <div class="meta-item"><i class="fas fa-calendar-alt"></i><span>Submitted: <?= $thesis_date ?></span></div>
            </div>
            
            <div class="abstract-section">
                <h4><i class="fas fa-align-left"></i> Abstract</h4>
                <p><?= nl2br(htmlspecialchars($thesis_abstract)) ?></p>
            </div>
            
            <div class="file-section">
                <div class="file-info">
                    <i class="fas fa-file-pdf"></i>
                    <div class="file-name"><?= !empty($thesis_file) ? basename($thesis_file) : 'No file uploaded' ?></div>
                </div>
                <?php if (!empty($thesis_file)): ?>
                <a href="<?= htmlspecialchars('../' . $thesis_file) ?>" class="download-btn" download><i class="fas fa-download"></i> Download</a>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($thesis_file) && file_exists('../' . $thesis_file)): ?>
            <div class="pdf-viewer">
                <iframe src="<?= htmlspecialchars('../' . $thesis_file) ?>"></iframe>
            </div>
            <?php endif; ?>
        </div>

        <!-- ACTION CARDS -->
        <div class="action-cards">
            <div class="action-card">
                <div class="action-icon"><i class="fas fa-paper-plane"></i></div>
                <h3>Forward to Librarian</h3>
                <p>Approve this thesis and forward to the Librarian for final archiving.</p>
                <button class="btn-forward" onclick="openForwardModal()"><i class="fas fa-paper-plane"></i> Forward to Librarian</button>
            </div>
            <div class="action-card">
                <div class="action-icon"><i class="fas fa-undo-alt"></i></div>
                <h3>Return to Coordinator</h3>
                <p>Return this thesis to the Coordinator for revision. Provide feedback below.</p>
                <button class="btn-return" onclick="openReturnModal()"><i class="fas fa-undo"></i> Return to Coordinator</button>
            </div>
        </div>
        
        <?php endif; ?>
    </main>

    <!-- Forward Modal -->
    <div id="forwardModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="color:#10b981;"><i class="fas fa-paper-plane"></i> Forward to Librarian</h3>
                <span class="close-modal" onclick="closeForwardModal()">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="thesis_id" value="<?= $thesis_id ?>">
                    <input type="hidden" name="forward_to_librarian" value="1">
                    <div class="form-group">
                        <label>Feedback (Optional)</label>
                        <textarea name="dean_feedback" rows="3" placeholder="Optional feedback..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeForwardModal()">Cancel</button>
                    <button type="submit" class="btn-forward">Confirm Forward</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="color:#f59e0b;"><i class="fas fa-undo-alt"></i> Return to Coordinator</h3>
                <span class="close-modal" onclick="closeReturnModal()">&times;</span>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="thesis_id" value="<?= $thesis_id ?>">
                    <input type="hidden" name="return_to_coordinator" value="1">
                    <div class="form-group">
                        <label>Reason for Return <span style="color:#f59e0b;">*</span></label>
                        <textarea name="dean_feedback" rows="3" placeholder="Please provide reason..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeReturnModal()">Cancel</button>
                    <button type="submit" class="btn-return">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const profileWrapper = document.getElementById('profileWrapper');
        const profileDropdown = document.getElementById('profileDropdown');
        const forwardModal = document.getElementById('forwardModal');
        const returnModal = document.getElementById('returnModal');

        function toggleSidebar() { 
            sidebar.classList.toggle('open'); 
            sidebarOverlay.classList.toggle('show');
        }
        
        hamburgerBtn.addEventListener('click', toggleSidebar);
        sidebarOverlay.addEventListener('click', toggleSidebar);
        
        profileWrapper.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function() {
            profileDropdown.classList.remove('show');
        });
        
        function openForwardModal() { forwardModal.classList.add('show'); }
        function closeForwardModal() { forwardModal.classList.remove('show'); }
        function openReturnModal() { returnModal.classList.add('show'); }
        function closeReturnModal() { returnModal.classList.remove('show'); }
        
        window.onclick = function(event) {
            if (event.target === forwardModal) closeForwardModal();
            if (event.target === returnModal) closeReturnModal();
        }
        
        // Dark mode
        const isDark = localStorage.getItem('darkMode') === 'true';
        if (isDark) document.body.classList.add('dark-mode');
    </script>
</body>
</html>