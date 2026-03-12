<?php
session_start();
include("../config/db.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$faculty_id = (int)$_SESSION["user_id"];
$thesis_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($thesis_id == 0) {
    header("Location: facultyDashboard.php");
    exit;
}

/* ================================
   FETCH FACULTY INFORMATION (for avatar)
================================ */
$stmt = $conn->prepare("SELECT first_name, last_name FROM user_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$stmt->close();

$first = trim($faculty["first_name"] ?? "");
$last  = trim($faculty["last_name"] ?? "");
$initials = $first && $last ? strtoupper(substr($first, 0, 1) . substr($last, 0, 1)) : "FA";

/* ================================
   GET THESIS DETAILS
================================ */
$thesis = null;

try {
    $query = "SELECT t.*, u.first_name, u.last_name, u.email, u.user_id 
              FROM thesis_table t
              JOIN user_table u ON t.student_id = u.user_id
              WHERE t.thesis_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $thesis = $result->fetch_assoc();
    $stmt->close();

    if (!$thesis) {
        header("Location: facultyDashboard.php");
        exit;
    }
} catch (Exception $e) {
    error_log("Error fetching thesis: " . $e->getMessage());
    header("Location: facultyDashboard.php");
    exit;
}

/* ================================
   GET FEEDBACK FOR THIS THESIS
================================ */
$feedbacks = [];

try {
    $query = "SELECT f.*, u.first_name, u.last_name 
              FROM feedback_table f
              JOIN user_table u ON f.faculty_id = u.user_id
              WHERE f.thesis_id = ?
              ORDER BY f.feedback_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $thesis_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching feedbacks: " . $e->getMessage());
}

/* ================================
   HANDLE FORM SUBMISSIONS
================================ */
$message = '';
$messageType = '';

// Handle Approve/Reject - FIXED: Single notification only
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    
    if ($action == 'approve' || $action == 'reject') {
        $status = ($action == 'approve') ? 'approved' : 'rejected';
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update thesis status
            $updateQuery = "UPDATE thesis_table SET status = ? WHERE thesis_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("si", $status, $thesis_id);
            $stmt->execute();
            $stmt->close();
            
            // Insert feedback if provided
            if (!empty($feedback)) {
                $insertQuery = "INSERT INTO feedback_table (thesis_id, faculty_id, comments, feedback_date) 
                               VALUES (?, ?, ?, NOW())";
                $stmt = $conn->prepare($insertQuery);
                $stmt->bind_param("iis", $thesis_id, $faculty_id, $feedback);
                $stmt->execute();
                $stmt->close();
            }
            
            // =============== GENERATE CERTIFICATE IF APPROVED ===============
            if ($action == 'approve') {
                // Create certificates directory if not exists
                $certDir = __DIR__ . "/../uploads/certificates/";
                if (!file_exists($certDir)) {
                    mkdir($certDir, 0777, true);
                }
                
                // Generate unique filename
                $certFileName = 'certificate_' . $thesis_id . '_' . time() . '.html';
                $certPath = $certDir . $certFileName;
                
                // Get student name and thesis title
                $studentName = $thesis['first_name'] . ' ' . $thesis['last_name'];
                $thesisTitle = $thesis['title'];
                $facultyName = $first . ' ' . $last;
                
                // Create HTML certificate
                $certificateHTML = '
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Thesis Certificate</title>
                    <style>
                        body {
                            font-family: "Times New Roman", Times, serif;
                            background: #f0f0f0;
                            margin: 0;
                            padding: 20px;
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            min-height: 100vh;
                        }
                        .certificate {
                            width: 800px;
                            background: white;
                            border: 20px solid #FE4853;
                            padding: 40px;
                            position: relative;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                        }
                        .certificate:before {
                            content: "";
                            position: absolute;
                            top: 10px;
                            left: 10px;
                            right: 10px;
                            bottom: 10px;
                            border: 2px solid #732529;
                            pointer-events: none;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 40px;
                        }
                        .header h1 {
                            color: #FE4853;
                            font-size: 48px;
                            margin: 0;
                            text-transform: uppercase;
                            letter-spacing: 5px;
                        }
                        .header h2 {
                            color: #732529;
                            font-size: 24px;
                            margin: 10px 0 0;
                            font-style: italic;
                        }
                        .content {
                            text-align: center;
                            margin: 50px 0;
                        }
                        .content p {
                            font-size: 18px;
                            color: #333;
                            line-height: 2;
                        }
                        .student-name {
                            font-size: 36px;
                            color: #FE4853;
                            font-weight: bold;
                            margin: 20px 0;
                            text-transform: uppercase;
                            border-bottom: 2px solid #732529;
                            display: inline-block;
                            padding-bottom: 10px;
                        }
                        .thesis-title {
                            font-size: 24px;
                            color: #732529;
                            font-style: italic;
                            margin: 20px 0;
                        }
                        .date {
                            font-size: 18px;
                            color: #666;
                            margin: 30px 0;
                        }
                        .signature {
                            margin-top: 60px;
                            display: flex;
                            justify-content: space-between;
                        }
                        .signature-line {
                            width: 200px;
                            border-top: 2px solid #333;
                            margin-top: 40px;
                        }
                        .signature-item {
                            text-align: center;
                        }
                        .signature-item p {
                            margin: 5px 0;
                            color: #666;
                        }
                        .seal {
                            position: absolute;
                            bottom: 50px;
                            right: 50px;
                            width: 100px;
                            height: 100px;
                            border: 3px solid #FE4853;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            transform: rotate(-15deg);
                        }
                        .seal p {
                            color: #FE4853;
                            font-size: 14px;
                            font-weight: bold;
                            text-align: center;
                            line-height: 1.4;
                        }
                        .footer {
                            text-align: center;
                            margin-top: 40px;
                            color: #999;
                            font-size: 12px;
                        }
                    </style>
                </head>
                <body>
                    <div class="certificate">
                        <div class="header">
                            <h1>Certificate of Approval</h1>
                            <h2>Thesis Archiving System</h2>
                        </div>
                        
                        <div class="content">
                            <p>This is to certify that</p>
                            <div class="student-name">' . $studentName . '</div>
                            <p>has successfully completed and defended the thesis entitled</p>
                            <div class="thesis-title">"' . $thesisTitle . '"</div>
                            <p>on this day, <strong>' . date('F d, Y') . '</strong></p>
                            <p>and is hereby granted the approval for thesis submission.</p>
                        </div>
                        
                        <div class="signature">
                            <div class="signature-item">
                                <div class="signature-line"></div>
                                <p><strong>' . $facultyName . '</strong></p>
                                <p>Thesis Adviser</p>
                            </div>
                            <div class="signature-item">
                                <div class="signature-line"></div>
                                <p><strong>Dean</strong></p>
                                <p>Graduate School</p>
                            </div>
                        </div>
                        
                        <div class="seal">
                            <p>OFFICIAL<br>SEAL</p>
                        </div>
                        
                        <div class="footer">
                            <p>This certificate is automatically generated by Theses Archiving System</p>
                            <p>Certificate ID: CERT-' . str_pad($thesis_id, 6, '0', STR_PAD_LEFT) . '</p>
                        </div>
                    </div>
                </body>
                </html>';
                
                // Save certificate file
                file_put_contents($certPath, $certificateHTML);
                
                // Check if certificates_table exists, if not, create it
                $checkTableQuery = "SHOW TABLES LIKE 'certificates_table'";
                $tableExists = $conn->query($checkTableQuery);
                
                if ($tableExists->num_rows == 0) {
                    // Create certificates table if it doesn't exist
                    $createTableQuery = "CREATE TABLE certificates_table (
                        certificate_id INT(11) NOT NULL AUTO_INCREMENT,
                        thesis_id INT(11) NOT NULL,
                        student_id INT(11) NOT NULL,
                        certificate_file VARCHAR(255) NOT NULL,
                        generated_date DATETIME NOT NULL,
                        downloaded_count INT(11) DEFAULT 0,
                        PRIMARY KEY (certificate_id),
                        KEY thesis_id (thesis_id),
                        KEY student_id (student_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $conn->query($createTableQuery);
                }
                
                // Insert record to certificates_table
                $certQuery = "INSERT INTO certificates_table (thesis_id, student_id, certificate_file, generated_date, downloaded_count) 
                              VALUES (?, ?, ?, NOW(), 0)";
                $certStmt = $conn->prepare($certQuery);
                $certStmt->bind_param("iis", $thesis_id, $thesis['user_id'], $certFileName);
                $certStmt->execute();
                $certStmt->close();
                
                // Add certificate notification to message
                $certMessage = " Certificate has been generated.";
            }
            
            // =============== NOTIFICATION SENDING (IMPROVED WITH ERROR CHECKING) ===============
            
            // Prepare notification message
            if (!empty($feedback)) {
                // Truncate feedback if too long
                $shortFeedback = strlen($feedback) > 50 ? substr($feedback, 0, 50) . "..." : $feedback;
                $notifMessage = "Your thesis '" . $thesis['title'] . "' has been " . $status . " with feedback: \"" . $shortFeedback . "\"";
            } else {
                $notifMessage = "Your thesis '" . $thesis['title'] . "' has been " . $status . " by faculty.";
            }
            
            // Add certificate mention if approved
            if ($action == 'approve') {
                $notifMessage .= " A certificate has been generated for you.";
            }
            
            // Log for debugging
            error_log("Sending notification to student ID: " . $thesis['user_id'] . " for thesis ID: " . $thesis_id);
            error_log("Notification message: " . $notifMessage);
            
            // =============== SIMPLIFIED NOTIFICATION INSERTION ===============
            // Diretso lang para sure na may ma-insert
            $notifQuery = "INSERT INTO notification_table (user_id, thesis_id, message, status, created_at) 
                          VALUES (" . $thesis['user_id'] . ", " . $thesis_id . ", '" . mysqli_real_escape_string($conn, $notifMessage) . "', 'unread', NOW())";
            
            if ($conn->query($notifQuery)) {
                error_log("Notification inserted successfully. ID: " . $conn->insert_id);
            } else {
                error_log("Failed to insert notification: " . $conn->error);
            }
            
            $conn->commit();
            
            $message = "Thesis successfully " . $status . "!";
            if ($action == 'approve') {
                $message .= " Certificate has been generated.";
            }
            $messageType = "success";
            
            // Refresh thesis data
            $thesis['status'] = $status;
            
            // Refresh feedbacks if feedback was added
            if (!empty($feedback)) {
                $feedbacks = [];
                $query = "SELECT f.*, u.first_name, u.last_name 
                          FROM feedback_table f
                          JOIN user_table u ON f.faculty_id = u.user_id
                          WHERE f.thesis_id = ?
                          ORDER BY f.feedback_date DESC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $thesis_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $feedbacks[] = $row;
                }
                $stmt->close();
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $messageType = "error";
            error_log("Error in approve/reject: " . $e->getMessage());
        }
    }
}

// Handle Add Feedback only - EACH FEEDBACK = ONE NOTIFICATION (CORRECT)
if (isset($_POST['add_feedback'])) {
    $feedback = trim($_POST['feedback']);
    
    if (!empty($feedback)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Insert feedback
            $insertQuery = "INSERT INTO feedback_table (thesis_id, faculty_id, comments, feedback_date) 
                           VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("iis", $thesis_id, $faculty_id, $feedback);
            $stmt->execute();
            $feedback_id = $stmt->insert_id;
            $stmt->close();
            
            // ✅ Create notification for student - ONE NOTIFICATION PER FEEDBACK
            $notifMessage = "New feedback on your thesis '" . $thesis['title'] . "'";
            
            // Log for debugging
            error_log("Sending feedback notification to student ID: " . $thesis['user_id'] . " for thesis ID: " . $thesis_id);
            
            // Simplified insert
            $notifQuery = "INSERT INTO notification_table (user_id, thesis_id, message, status, created_at) 
                          VALUES (" . $thesis['user_id'] . ", " . $thesis_id . ", '" . mysqli_real_escape_string($conn, $notifMessage) . "', 'unread', NOW())";
            
            if ($conn->query($notifQuery)) {
                error_log("Feedback notification inserted successfully. ID: " . $conn->insert_id);
            } else {
                error_log("Failed to insert feedback notification: " . $conn->error);
            }
            
            $conn->commit();
            
            $message = "Feedback added successfully! Student has been notified.";
            $messageType = "success";
            
            // Refresh feedbacks
            $feedbacks = [];
            $query = "SELECT f.*, u.first_name, u.last_name 
                      FROM feedback_table f
                      JOIN user_table u ON f.faculty_id = u.user_id
                      WHERE f.thesis_id = ?
                      ORDER BY f.feedback_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $thesis_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $feedbacks[] = $row;
            }
            $stmt->close();
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error adding feedback: " . $e->getMessage();
            $messageType = "error";
            error_log("Error adding feedback: " . $e->getMessage());
        }
    } else {
        $message = "Please enter feedback";
        $messageType = "error";
    }
}

$pageTitle = "Review Thesis";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Theses Archiving System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Keep all your existing CSS exactly as is */
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

        /* Review Container */
        .review-container {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 3px 14px rgba(110, 110, 110, 0.1);
            margin-bottom: 2rem;
        }

        body.dark-mode .review-container {
            background: #3a3a3a;
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .thesis-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .thesis-header h2 {
            color: #732529;
            font-size: 1.8rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .thesis-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        body.dark-mode .thesis-details {
            background: #4a4a4a;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #6E6E6E;
            margin-bottom: 0.3rem;
        }

        .detail-value {
            font-size: 1rem;
            color: #333;
            font-weight: 500;
        }

        body.dark-mode .detail-value {
            color: #e0e0e0;
        }

        .thesis-abstract {
            margin-bottom: 2rem;
        }

        .thesis-abstract h3 {
            color: #732529;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .thesis-abstract p {
            line-height: 1.6;
            color: #333;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        body.dark-mode .thesis-abstract p {
            background: #4a4a4a;
            color: #e0e0e0;
        }

        /* =============== ENHANCED MANUSCRIPT SECTION =============== */
        .thesis-file {
            margin-bottom: 2rem;
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e0e0e0;
        }

        body.dark-mode .thesis-file {
            background: #4a4a4a;
            border-color: #6E6E6E;
        }

        .thesis-file h3 {
            color: #732529;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .thesis-file h3 i {
            color: #FE4853;
        }

        .file-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            transition: all 0.3s;
            font-weight: 500;
        }

        .file-link:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .file-link i {
            font-size: 1.1rem;
        }

        .file-link.download {
            background: #10b981;
        }

        .file-link.download:hover {
            background: #059669;
        }

        .pdf-viewer {
            margin-top: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }

        .pdf-viewer iframe {
            width: 100%;
            height: 600px;
            border: none;
        }

        .no-file-message {
            text-align: center;
            padding: 3rem;
            background: #fff3cd;
            border-radius: 8px;
            color: #856404;
            border: 2px dashed #ffc107;
        }

        .no-file-message i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ffc107;
        }

        .no-file-message p {
            font-size: 1.1rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #FE4853;
            color: white;
        }

        .btn-primary:hover {
            background: #732529;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Feedback Section */
        .feedback-section {
            margin-top: 3rem;
            border-top: 2px solid #f0f0f0;
            padding-top: 2rem;
        }

        .feedback-section h3 {
            color: #732529;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feedback-section h3 i {
            color: #FE4853;
        }

        .feedback-form {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        body.dark-mode .feedback-form {
            background: #4a4a4a;
        }

        .feedback-form textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            margin-bottom: 1rem;
        }

        body.dark-mode .feedback-form textarea {
            background: #2d2d2d;
            color: #e0e0e0;
            border-color: #6E6E6E;
        }

        .feedback-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .feedback-item {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #FE4853;
            transition: all 0.3s ease;
        }

        .feedback-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(254, 72, 83, 0.1);
        }

        body.dark-mode .feedback-item {
            background: #4a4a4a;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px dashed #e0e0e0;
        }

        body.dark-mode .feedback-header {
            border-bottom-color: #6E6E6E;
        }

        .feedback-author {
            font-weight: 600;
            color: #732529;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .feedback-author i {
            color: #FE4853;
            font-size: 1rem;
        }

        .feedback-date {
            color: #6E6E6E;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .feedback-date i {
            font-size: 0.8rem;
        }

        .feedback-content {
            color: #333;
            line-height: 1.7;
            font-size: 0.95rem;
        }

        body.dark-mode .feedback-content {
            color: #e0e0e0;
        }

        .no-feedback {
            text-align: center;
            color: #6E6E6E;
            padding: 3rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 2px dashed #e0e0e0;
        }

        .no-feedback i {
            font-size: 3rem;
            color: #FE4853;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-feedback h4 {
            color: #732529;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #FE4853;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1rem;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #732529;
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
            transition: all 0.3s;
        }

        .mobile-menu-btn:hover {
            background: #732529;
            transform: scale(1.05);
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

            .thesis-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .thesis-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .feedback-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .feedback-item {
                padding: 1rem;
            }

            .file-actions {
                flex-direction: column;
            }

            .file-link {
                width: 100%;
                justify-content: center;
            }

            .pdf-viewer iframe {
                height: 400px;
            }
        }

        @media (max-width: 480px) {
            .review-container {
                padding: 1rem;
            }

            .thesis-header h2 {
                font-size: 1.3rem;
            }

            .feedback-form {
                padding: 1rem;
            }

            .feedback-form textarea {
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .pdf-viewer iframe {
                height: 300px;
            }
        }
    </style>
</head>
<body>

<!-- OVERLAY -->
<div class="overlay" id="overlay"></div>

<!-- MOBILE MENU BUTTON -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Theses Archive</h2>
        <p>Faculty Portal</p>
    </div>

    <nav class="sidebar-nav">
        <a href="facultyDashboard.php" class="nav-link">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="reviewThesis.php" class="nav-link active">
            <i class="fas fa-book-reader"></i> Review Theses
        </a>
        <a href="facultyFeedback.php" class="nav-link">
            <i class="fas fa-comment-dots"></i> My Feedback
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
                <h1>Review Thesis</h1>
            </div>

            <div class="user-info">
                <div class="avatar" id="avatarBtn">
                    <?= htmlspecialchars($initials) ?>
                </div>
            </div>
        </header>

        <!-- Back Link -->
        <a href="facultyDashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- Review Container -->
        <div class="review-container">
            
            <?php if (!empty($message)): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Thesis Header -->
            <div class="thesis-header">
                <h2><?= htmlspecialchars($thesis['title']) ?></h2>
                <span class="status-badge <?= $thesis['status'] ?>">
                    <?= strtoupper($thesis['status']) ?>
                </span>
            </div>

            <!-- Thesis Details -->
            <div class="thesis-details">
                <div class="detail-item">
                    <span class="detail-label">Student Name</span>
                    <span class="detail-value"><?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email</span>
                    <span class="detail-value"><?= htmlspecialchars($thesis['email']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date Submitted</span>
                    <span class="detail-value"><?= date('F d, Y', strtotime($thesis['date_submitted'])) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Adviser</span>
                    <span class="detail-value"><?= htmlspecialchars($thesis['adviser'] ?? 'Not Assigned') ?></span>
                </div>
            </div>

            <!-- Abstract -->
            <div class="thesis-abstract">
                <h3>Abstract</h3>
                <p><?= nl2br(htmlspecialchars($thesis['abstract'])) ?></p>
            </div>

            <!-- =============== ENHANCED MANUSCRIPT SECTION =============== -->
            <div class="thesis-file">
                <h3><i class="fas fa-file-pdf"></i> Manuscript File</h3>
                
                <?php if (!empty($thesis['file_path'])): ?>
                    <?php 
                    // Construct full file path
                    $file_path = '../' . $thesis['file_path'];
                    $file_exists = file_exists($file_path);
                    ?>
                    
                    <?php if ($file_exists): ?>
                        <!-- File Actions -->
                        <div class="file-actions">
                            <a href="<?= htmlspecialchars($file_path) ?>" class="file-link" target="_blank">
                                <i class="fas fa-eye"></i> View in New Tab
                            </a>
                            <a href="<?= htmlspecialchars($file_path) ?>" class="file-link download" download>
                                <i class="fas fa-download"></i> Download Manuscript
                            </a>
                        </div>
                        
                        <!-- PDF Viewer -->
                        <div class="pdf-viewer">
                            <iframe src="<?= htmlspecialchars($file_path) ?>" 
                                    title="Manuscript PDF"
                                    allowfullscreen>
                            </iframe>
                        </div>
                        
                        <p style="margin-top: 0.5rem; color: #6E6E6E; font-size: 0.85rem;">
                            <i class="fas fa-info-circle"></i> 
                            File: <?= basename($file_path) ?>
                        </p>
                    <?php else: ?>
                        <div class="no-file-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>The manuscript file could not be found at: <?= htmlspecialchars($thesis['file_path']) ?></p>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Please check if the file exists in the uploads folder.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="no-file-message">
                        <i class="fas fa-file-pdf"></i>
                        <p>No manuscript file uploaded for this thesis.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons (Only if status is pending) -->
            <?php if ($thesis['status'] == 'pending'): ?>
            <div class="action-buttons">
                <!-- Approve Button -->
                <button type="button" class="btn btn-success" onclick="showConfirmModal('approve')">
                    <i class="fas fa-check-circle"></i> Approve Thesis
                </button>
                
                <!-- Reject Button -->
                <button type="button" class="btn btn-danger" onclick="showConfirmModal('reject')">
                    <i class="fas fa-times-circle"></i> Reject Thesis
                </button>
            </div>
            <?php endif; ?>

            <!-- FEEDBACK SECTION -->
            <div class="feedback-section">
                <h3><i class="fas fa-comments"></i> Feedback & Comments</h3>

                <!-- Add Feedback Form -->
                <div class="feedback-form">
                    <form method="POST" action="">
                        <textarea name="feedback" rows="4" placeholder="Enter your feedback or comments here..." required></textarea>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <small style="color: #10b981;">
                                <i class="fas fa-bell"></i> Student will be notified immediately
                            </small>
                        </div>
                        <button type="submit" name="add_feedback" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Add Feedback & Notify Student
                        </button>
                    </form>
                </div>

                <!-- Display Existing Feedback -->
                <div class="feedback-list">
                    <?php if (empty($feedbacks)): ?>
                        <div class="no-feedback">
                            <i class="fas fa-comment-dots"></i>
                            <h4>No feedback yet</h4>
                            <p>Be the first to provide feedback on this thesis.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $fb): ?>
                            <div class="feedback-item">
                                <div class="feedback-header">
                                    <span class="feedback-author">
                                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($fb['first_name'] . ' ' . $fb['last_name']) ?>
                                    </span>
                                    <span class="feedback-date">
                                        <i class="fas fa-clock"></i> <?= date('M d, Y h:i A', strtotime($fb['feedback_date'])) ?>
                                    </span>
                                </div>
                                <div class="feedback-content">
                                    <?= nl2br(htmlspecialchars($fb['comments'])) ?>
                                </div>
                                <div style="font-size: 0.7rem; color: #10b981; margin-top: 0.5rem; text-align: right;">
                                    <i class="fas fa-check-circle"></i> Student notified
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <!-- END FEEDBACK SECTION -->
            
        </div>
    </main>
</div>

<!-- Confirmation Modal -->
<div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 12px; max-width: 400px; width: 90%;">
        <h3 style="color: #732529; margin-bottom: 1rem;" id="modalTitle">Confirm Action</h3>
        <p style="margin-bottom: 1.5rem;" id="modalMessage">Are you sure you want to proceed?</p>
        
        <form method="POST" action="" id="actionForm">
            <input type="hidden" name="action" id="actionInput" value="">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: #333;">Feedback (Optional):</label>
                <textarea name="feedback" rows="3" style="width: 100%; padding: 0.5rem; border: 1px solid #e0e0e0; border-radius: 4px;" placeholder="Add your feedback here..."></textarea>
                <small style="color: #10b981;"><i class="fas fa-bell"></i> Student will be notified</small>
            </div>
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="hideConfirmModal()">Cancel</button>
                <button type="submit" class="btn btn-success" id="modalConfirmBtn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Dark mode toggle
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

    // Modal functions
    function showConfirmModal(action) {
        const modal = document.getElementById('confirmModal');
        const title = document.getElementById('modalTitle');
        const message = document.getElementById('modalMessage');
        const actionInput = document.getElementById('actionInput');
        const confirmBtn = document.getElementById('modalConfirmBtn');
        
        actionInput.value = action;
        
        if (action === 'approve') {
            title.textContent = 'Approve Thesis';
            message.textContent = 'Are you sure you want to approve this thesis?';
            confirmBtn.className = 'btn btn-success';
            confirmBtn.innerHTML = '<i class="fas fa-check"></i> Approve';
        } else {
            title.textContent = 'Reject Thesis';
            message.textContent = 'Are you sure you want to reject this thesis?';
            confirmBtn.className = 'btn btn-danger';
            confirmBtn.innerHTML = '<i class="fas fa-times"></i> Reject';
        }
        
        modal.style.display = 'flex';
    }

    function hideConfirmModal() {
        document.getElementById('confirmModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('confirmModal');
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Mobile menu
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

    // Close sidebar when clicking nav links on mobile
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
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
            }
        });
    });
</script>

</body>
</html>