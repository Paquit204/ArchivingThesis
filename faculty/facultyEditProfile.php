<?php
session_start();
include("../config/db.php"); // Your config

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$faculty_id = (int)$_SESSION["user_id"];

/* ================================
   FETCH FACULTY INFORMATION FROM DATABASE
================================ */
$stmt = $conn->prepare("SELECT first_name, last_name, email, contact_number, address, birth_date, profile_picture, role_id FROM user_table WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$faculty) {
    session_destroy();
    header("Location: ../authentication/login.php");
    exit;
}

$first = trim($faculty["first_name"] ?? "");
$last  = trim($faculty["last_name"] ?? "");
$fullName = trim($first . " " . $last);
$email = trim($faculty["email"] ?? "");
$contact = trim($faculty["contact_number"] ?? "");
$address = trim($faculty["address"] ?? "");
$birthDate = trim($faculty["birth_date"] ?? "");
$role_id = $faculty["role_id"] ?? 3;

// Set position based on role or default
$position = "Faculty Member";
if ($role_id == 1) $position = "Administrator";
elseif ($role_id == 2) $position = "Student";
elseif ($role_id == 3) $position = "Faculty Member";
elseif ($role_id == 4) $position = "Dean";

// Department (you can fetch from database if you have a department table)
$department = "College of Computer Studies";

$initials = $first && $last ? strtoupper(substr($first, 0, 1) . substr($last, 0, 1)) : "FA";
$profilePicUrl = $faculty["profile_picture"] 
    ? "../uploads/profile_pictures/" . $faculty["profile_picture"] 
    : "";

/* ================================
   GET UNREAD NOTIFICATIONS COUNT
================================ */
$unreadCount = 0;
$recentNotifications = [];

try {
    // Check notification table structure
    $notif_columns = $conn->query("SHOW COLUMNS FROM notification_table");
    $notif_user_column = 'user_id';
    $notif_read_column = 'is_read';
    $notif_message_column = 'message';
    $notif_date_column = 'created_at';
    
    while ($col = $notif_columns->fetch_assoc()) {
        $field = $col['Field'];
        if (strpos($field, 'user') !== false && strpos($field, 'sender') === false) {
            $notif_user_column = $field;
        }
        if (strpos($field, 'read') !== false || strpos($field, 'status') !== false) {
            $notif_read_column = $field;
        }
        if (strpos($field, 'message') !== false) {
            $notif_message_column = $field;
        }
        if (strpos($field, 'created_at') !== false || strpos($field, 'date') !== false) {
            $notif_date_column = $field;
        }
    }
    
    // Get unread count
    $countQuery = "SELECT COUNT(*) as total FROM notification_table 
                   WHERE $notif_user_column = ? AND $notif_read_column = 0";
    $stmt = $conn->prepare($countQuery);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $countResult = $stmt->get_result()->fetch_assoc();
    $unreadCount = $countResult['total'] ?? 0;
    $stmt->close();
    
    // Get recent notifications for dropdown
    $notifQuery = "SELECT $notif_message_column as message, $notif_read_column as is_read, 
                          $notif_date_column as created_at
                   FROM notification_table 
                   WHERE $notif_user_column = ? 
                   ORDER BY $notif_date_column DESC 
                   LIMIT 5";
    $stmt = $conn->prepare($notifQuery);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recentNotifications[] = $row;
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Notification error: " . $e->getMessage());
    $unreadCount = 0;
    $recentNotifications = [];
}

/* ================================
   HANDLE FORM SUBMISSION
================================ */
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name  = trim($_POST["first_name"] ?? "");
    $last_name   = trim($_POST["last_name"] ?? "");
    $email       = trim($_POST["email"] ?? "");
    $contact_num = trim($_POST["contact_number"] ?? "");
    $address     = trim($_POST["address"] ?? "");
    $birth_date  = trim($_POST["birth_date"] ?? "");

    if ($first_name === "" || $last_name === "" || $email === "") {
        $errorMessage = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Invalid email format.";
    } else {
        $newFileName = null;

        // Handle profile picture upload
        if (!empty($_FILES["profile_picture"]["name"])) {
            $file = $_FILES["profile_picture"];
            $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

            if (!in_array($ext, ["jpg", "jpeg", "png"])) {
                $errorMessage = "Only JPG, JPEG or PNG files allowed.";
            } else {
                $uploadDir = __DIR__ . "/../uploads/profile_pictures/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $newFileName = "faculty_" . $faculty_id . "_" . time() . "." . $ext;
                $dest = $uploadDir . $newFileName;

                if (!move_uploaded_file($file["tmp_name"], $dest)) {
                    $errorMessage = "Failed to upload picture.";
                    $newFileName = null;
                }
            }
        }

        if (!$errorMessage) {
            if ($newFileName) {
                $sql = "UPDATE user_table SET first_name=?, last_name=?, email=?, contact_number=?, address=?, birth_date=?, profile_picture=?, updated_at=NOW() WHERE user_id=?";
                $upd = $conn->prepare($sql);
                $upd->bind_param("sssssssi", $first_name, $last_name, $email, $contact_num, $address, $birth_date, $newFileName, $faculty_id);
            } else {
                $sql = "UPDATE user_table SET first_name=?, last_name=?, email=?, contact_number=?, address=?, birth_date=?, updated_at=NOW() WHERE user_id=?";
                $upd = $conn->prepare($sql);
                $upd->bind_param("ssssssi", $first_name, $last_name, $email, $contact_num, $address, $birth_date, $faculty_id);
            }

            if ($upd->execute()) {
                $upd->close();
                header("Location: facultyProfile.php");
                exit;
            } else {
                $errorMessage = "Update failed: " . $upd->error;
                $upd->close();
            }
        }
    }
}

$pageTitle = "Edit Profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> - Theses Archiving System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* ====================================
       EDIT PROFILE PAGE STYLES - RED THEME
    ==================================== */

    /* Base styles */
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

    /* ====================================
       SIDEBAR - RED BACKGROUND
    ==================================== */
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

    .logout-btn:hover i {
      color: white;
    }

    /* Theme Toggle */
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

    .toggle-label .fa-sun {
      color: white;
    }

    .toggle-label .fa-moon {
      color: rgba(255, 255, 255, 0.8);
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

    /* Overlay */
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

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: 0;
      min-height: 100vh;
      padding: 2rem;
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

    /* Three-line menu */
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

    body.dark-mode .hamburger-menu {
      color: #FE4853;
    }

    body.dark-mode .hamburger-menu:hover {
      background: rgba(254, 72, 83, 0.2);
      color: #FE4853;
    }

    /* ====================================
       NOTIFICATION STYLES
    ==================================== */
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
      text-decoration: none;
    }

    .notification-bell:hover {
      color: #FE4853;
    }

    body.dark-mode .notification-bell {
      color: #e0e0e0;
    }

    body.dark-mode .notification-bell:hover {
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

    body.dark-mode .notification-header {
      border-bottom-color: #6E6E6E;
    }

    .notification-header h4 {
      margin: 0;
      color: #732529;
      font-size: 1rem;
      font-weight: 600;
    }

    body.dark-mode .notification-header h4 {
      color: #FE4853;
    }

    .notification-header a {
      color: #FE4853;
      text-decoration: none;
      font-size: 0.85rem;
      cursor: pointer;
    }

    .notification-header a:hover {
      text-decoration: underline;
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

    body.dark-mode .notification-item {
      border-bottom-color: #3a3a3a;
    }

    .notification-item:hover {
      background: #f5f5f5;
    }

    body.dark-mode .notification-item:hover {
      background: #3a3a3a;
    }

    .notification-item.unread {
      background: #fff3f3;
    }

    body.dark-mode .notification-item.unread {
      background: #3a1a1a;
    }

    .notif-message {
      font-size: 0.9rem;
      color: #333;
      margin-bottom: 5px;
    }

    body.dark-mode .notif-message {
      color: #e0e0e0;
    }

    .notif-time {
      font-size: 0.75rem;
      color: #6E6E6E;
    }

    body.dark-mode .notif-time {
      color: #94a3b8;
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

    body.dark-mode .notification-footer {
      border-top-color: #6E6E6E;
    }

    .notification-footer a {
      color: #FE4853;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 500;
    }

    .notification-footer a:hover {
      text-decoration: underline;
    }

    /* ====================================
       AVATAR DROPDOWN
    ==================================== */
    .avatar-dropdown {
      position: relative;
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
    }

    .avatar:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
    }

    body.dark-mode .avatar {
      background: linear-gradient(135deg, #FE4853 0%, #732529 100%);
    }

    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 55px;
      background: white;
      min-width: 200px;
      box-shadow: 0 8px 16px rgba(110, 110, 110, 0.15);
      border-radius: 8px;
      z-index: 1000;
      overflow: hidden;
      border: 1px solid #e0e0e0;
    }

    body.dark-mode .dropdown-content {
      background: #3a3a3a;
      border-color: #6E6E6E;
      box-shadow: 0 8px 16px rgba(0,0,0,0.3);
    }

    .dropdown-content.show {
      display: block;
      animation: fadeIn 0.2s;
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
      color: #6E6E6E;
      padding: 12px 16px;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: background 0.2s;
    }

    body.dark-mode .dropdown-content a {
      color: #e0e0e0;
    }

    .dropdown-content a i {
      width: 18px;
      color: #FE4853;
    }

    .dropdown-content hr {
      border: none;
      border-top: 1px solid #e0e0e0;
      margin: 4px 0;
    }

    body.dark-mode .dropdown-content hr {
      border-top-color: #6E6E6E;
    }

    .dropdown-content a:hover {
      background: #f5f5f5;
    }

    body.dark-mode .dropdown-content a:hover {
      background: #4a4a4a;
    }

    /* ====================================
       EDIT PROFILE FORM STYLES
    ==================================== */
    .profile-card.edit-card {
      background: white;
      border-radius: 16px;
      padding: 2.5rem;
      box-shadow: 0 6px 24px rgba(110, 110, 110, 0.1);
      max-width: 680px;
      margin: 2rem auto;
    }

    body.dark-mode .profile-card.edit-card {
      background: #3a3a3a;
      box-shadow: 0 6px 24px rgba(0,0,0,0.45);
    }

    .form-title {
      text-align: center;
      margin-bottom: 2.4rem;
      font-size: 1.95rem;
      font-weight: 700;
      color: #732529;
    }

    body.dark-mode .form-title {
      color: #FE4853;
    }

    /* Alert Messages */
    .alert-success {
      background: #dcfce7;
      color: #166534;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      border-left: 4px solid #22c55e;
    }

    .alert-error {
      background: #fee2e2;
      color: #b91c1c;
      padding: 1rem 1.5rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      border-left: 4px solid #ef4444;
    }

    body.dark-mode .alert-success {
      background: #1a3a2a;
      color: #86efac;
    }

    body.dark-mode .alert-error {
      background: #3a1a1a;
      color: #fca5a5;
    }

    .avatar-upload-section {
      text-align: center;
      margin-bottom: 3rem;
    }

    .current-avatar {
      width: 140px;
      height: 140px;
      margin: 0 auto 1.2rem;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid #e2e8f0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    body.dark-mode .current-avatar {
      border-color: #6E6E6E;
    }

    .avatar-placeholder {
      background: #FE4853;
      color: white;
      font-size: 3.8rem;
      font-weight: bold;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .current-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .help-text {
      font-size: 0.92rem;
      color: #6E6E6E;
      margin-top: 0.7rem;
    }

    body.dark-mode .help-text {
      color: #94a3b8;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.6rem 2rem;
    }

    .field {
      display: flex;
      flex-direction: column;
    }

    .field.full-width {
      grid-column: span 2;
    }

    .field label {
      margin-bottom: 0.65rem;
      font-weight: 500;
      color: #732529;
    }

    body.dark-mode .field label {
      color: #FE4853;
    }

    .field input,
    .field textarea {
      padding: 0.9rem 1.15rem;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      font-size: 1rem;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: white;
    }

    .field input:focus,
    .field textarea:focus {
      border-color: #FE4853;
      outline: none;
      box-shadow: 0 0 0 3px rgba(254, 72, 83, 0.12);
    }

    body.dark-mode .field input,
    body.dark-mode .field textarea {
      background: #4a4a4a;
      border-color: #6E6E6E;
      color: #e0e0e0;
    }

    .field textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-actions {
      display: flex;
      gap: 1.2rem;
      justify-content: flex-end;
      margin-top: 3rem;
    }

    .form-actions .btn {
      min-width: 160px;
      padding: 0.9rem 1.6rem;
    }

    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      text-decoration: none;
    }

    .btn.primary {
      background: #FE4853;
      color: white;
    }

    .btn.primary:hover {
      background: #732529;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(254, 72, 83, 0.3);
    }

    .btn.secondary {
      background: #e2e8f0;
      color: #6E6E6E;
    }

    .btn.secondary:hover {
      background: #cbd5e1;
      transform: translateY(-2px);
    }

    body.dark-mode .btn.secondary {
      background: #4a4a4a;
      color: #e0e0e0;
    }

    body.dark-mode .btn.secondary:hover {
      background: #5a5a5a;
    }

    /* Mobile menu button */
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

    body.dark-mode .mobile-menu-btn {
      background: #732529;
    }

    /* Animations */
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

    /* Dropdown Enhancements */
    .dropdown-content {
      z-index: 9999 !important;
    }

    .dropdown-content::before {
      content: '';
      position: absolute;
      top: -8px;
      right: 20px;
      width: 0;
      height: 0;
      border-left: 8px solid transparent;
      border-right: 8px solid transparent;
      border-bottom: 8px solid white;
    }

    body.dark-mode .dropdown-content::before {
      border-bottom-color: #3a3a3a;
    }

    .dropdown-content a {
      transition: all 0.2s ease;
      border-left: 3px solid transparent;
    }

    .dropdown-content a:hover {
      border-left-color: #FE4853;
      background-color: #f5f5f5;
      padding-left: 19px;
    }

    body.dark-mode .dropdown-content a:hover {
      border-left-color: #FE4853;
      background-color: #4a4a4a;
    }

    .avatar {
      cursor: pointer;
      user-select: none;
      position: relative;
    }

    .avatar::after {
      content: '▼';
      position: absolute;
      bottom: -5px;
      right: -5px;
      font-size: 8px;
      color: white;
      background: #FE4853;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 2px solid white;
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .avatar:hover::after {
      opacity: 1;
    }

    body.dark-mode .avatar::after {
      background: #732529;
      border-color: #1e293b;
    }

    /* ====================================
       RESPONSIVE DESIGN
    ==================================== */
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

      .profile-card.edit-card {
        padding: 2rem 1.5rem;
        margin: 1.5rem 1rem;
      }

      .form-grid {
        grid-template-columns: 1fr;
        gap: 1.4rem;
      }

      .field.full-width {
        grid-column: span 1;
      }

      .form-title {
        font-size: 1.75rem;
      }

      .form-actions {
        flex-direction: column;
        gap: 1rem;
      }

      .form-actions .btn {
        width: 100%;
      }

      .current-avatar,
      .avatar-placeholder {
        width: 120px;
        height: 120px;
        font-size: 3.2rem;
      }

      .avatar {
        width: 38px;
        height: 38px;
        font-size: 1rem;
      }

      .dropdown-content {
        min-width: 160px;
        right: -10px;
      }

      .notification-dropdown {
        width: 300px;
        right: -50px;
      }
    }

    @media (max-width: 480px) {
      .avatar-upload-section {
        margin-bottom: 2.2rem;
      }

      .topbar h1 {
        font-size: 1.3rem;
      }

      .notification-bell {
        font-size: 1.1rem;
      }

      .avatar {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
      }

      .notification-dropdown {
        width: 280px;
        right: -70px;
      }

      .dropdown-content {
        min-width: 150px;
      }

      .dropdown-content a {
        padding: 10px 14px;
        font-size: 0.9rem;
      }
    }

    /* Print Styles */
    @media print {
      .sidebar,
      .topbar .user-info,
      .notification-bell,
      .avatar-dropdown,
      .theme-toggle,
      .logout-btn,
      .mobile-menu-btn,
      .form-actions {
        display: none !important;
      }

      .main-content {
        margin-left: 0 !important;
        padding: 20px !important;
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

<!-- SIDEBAR - RED BACKGROUND -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h2>Theses Archive</h2>
    <p>Faculty Portal</p>
  </div>

  <nav class="sidebar-nav">
    <a href="facultyDashboard.php" class="nav-link">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="facultyProfile.php" class="nav-link active">
      <i class="fas fa-user-circle"></i> Profile
    </a>
    <a href="reviewThesis.php" class="nav-link">
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
        <!-- Three-line menu -->
        <div class="hamburger-menu" id="hamburgerBtn">
          <i class="fas fa-bars"></i>
        </div>
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
      </div>

      <div class="user-info">
        <!-- Notification Container with Dropdown -->
        <div class="notification-container">
          <div class="notification-bell" id="notificationBell">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
              <span class="notification-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
          </div>
          
          <!-- Notification Dropdown -->
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
                  <div class="notification-item <?= isset($notif['is_read']) && !$notif['is_read'] ? 'unread' : '' ?>">
                    <div class="notif-message"><?= htmlspecialchars($notif['message']) ?></div>
                    <div class="notif-time"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="notification-footer">
              <a href="notifications.php">View all notifications</a>
            </div>
          </div>
        </div>

        <!-- Avatar Dropdown -->
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

    <!-- Edit Profile Form -->
    <div class="profile-card edit-card">
      <h2 class="form-title">Update Your Information</h2>

      <?php if ($errorMessage): ?>
        <div class="alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <?= htmlspecialchars($errorMessage) ?>
        </div>
      <?php endif; ?>

      <?php if ($successMessage): ?>
        <div class="alert-success">
          <i class="fas fa-check-circle"></i>
          <?= htmlspecialchars($successMessage) ?>
        </div>
      <?php endif; ?>

      <form action="" method="post" enctype="multipart/form-data">
        <div class="avatar-upload-section">
          <label>Profile Picture</label>
          <div class="current-avatar" id="preview-container">
            <?php if ($profilePicUrl && file_exists(__DIR__ . "/../uploads/profile_pictures/" . $faculty["profile_picture"])): ?>
              <img src="<?= htmlspecialchars($profilePicUrl) ?>?v=<?= time() ?>" alt="Profile Picture">
            <?php else: ?>
              <div class="avatar-placeholder"><?= htmlspecialchars($initials) ?></div>
            <?php endif; ?>
          </div>
          <input type="file" id="avatar" name="profile_picture" accept="image/jpeg,image/png" hidden>
          <button type="button" class="btn secondary" onclick="document.getElementById('avatar').click()">
            <i class="fas fa-upload"></i> Choose New Photo
          </button>
          <p class="help-text">JPG or PNG • max 2 MB • recommended 200×200 px</p>
        </div>

        <div class="form-grid">
          <div class="field">
            <label>First Name</label>
            <input type="text" name="first_name" value="<?= htmlspecialchars($first) ?>" required>
          </div>
          <div class="field">
            <label>Last Name</label>
            <input type="text" name="last_name" value="<?= htmlspecialchars($last) ?>" required>
          </div>
          <div class="field">
            <label>Email Address</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
          </div>
          <div class="field">
            <label>Phone Number</label>
            <input type="tel" name="contact_number" value="<?= htmlspecialchars($contact) ?>">
          </div>
          <div class="field">
            <label>Birth Date</label>
            <input type="date" name="birth_date" value="<?= htmlspecialchars($birthDate) ?>">
          </div>
          <div class="field">
            <label>Position / Title</label>
            <input type="text" value="<?= htmlspecialchars($position) ?>" disabled>
            <small style="color:#6E6E6E;">Position cannot be changed</small>
          </div>
          <div class="field full-width">
            <label>Address</label>
            <textarea name="address" rows="3"><?= htmlspecialchars($address) ?></textarea>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn primary">
            <i class="fas fa-save"></i> Save Changes
          </button>
          <a href="facultyProfile.php" class="btn secondary">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>

<script>
  // Dark mode toggle
  const toggle = document.getElementById('darkmode');
  if (toggle) {
    toggle.addEventListener('change', () => {
      document.body.classList.toggle('dark-mode');
      localStorage.setItem('darkMode', toggle.checked);
    });

    const savedMode = localStorage.getItem('darkMode');
    if (savedMode === 'true') {
      toggle.checked = true;
      document.body.classList.add('dark-mode');
    }
  }

  // Avatar dropdown
  const avatarBtn = document.getElementById('avatarBtn');
  const dropdownMenu = document.getElementById('dropdownMenu');

  if (avatarBtn) {
    avatarBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      dropdownMenu.classList.toggle('show');
      
      // Close notification dropdown if open
      notificationDropdown.classList.remove('show');
    });
  }

  // Notification dropdown
  const notificationBell = document.getElementById('notificationBell');
  const notificationDropdown = document.getElementById('notificationDropdown');

  if (notificationBell) {
    notificationBell.addEventListener('click', function(e) {
      e.stopPropagation();
      notificationDropdown.classList.toggle('show');
      
      // Close avatar dropdown if open
      dropdownMenu.classList.remove('show');
    });
  }

  // Close dropdowns when clicking outside
  window.addEventListener('click', function() {
    if (notificationDropdown) notificationDropdown.classList.remove('show');
    if (dropdownMenu) dropdownMenu.classList.remove('show');
  });

  // Prevent closing when clicking inside dropdowns
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

  // Mark all as read
  document.getElementById('markAllRead')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    // AJAX request to mark all as read
    fetch('mark_all_read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      }
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Remove unread class from all notifications
        document.querySelectorAll('.notification-item').forEach(item => {
          item.classList.remove('unread');
        });
        
        // Remove notification badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
          badge.remove();
        }
      }
    })
    .catch(error => console.error('Error:', error));
  });

  // Mobile menu toggle
  const mobileBtn = document.getElementById('mobileMenuBtn');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');

  if (mobileBtn) {
    mobileBtn.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
      
      // Change icon
      const icon = mobileBtn.querySelector('i');
      if (sidebar.classList.contains('show')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
      } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    });
  }

  // Three-line menu for desktop
  const hamburgerBtn = document.getElementById('hamburgerBtn');
  if (hamburgerBtn) {
    hamburgerBtn.addEventListener('click', function() {
      sidebar.classList.toggle('show');
      overlay.classList.toggle('show');
      
      // Change icon between bars and times
      const icon = hamburgerBtn.querySelector('i');
      if (sidebar.classList.contains('show')) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
      } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
      }
    });
  }

  // Close sidebar when clicking on overlay
  if (overlay) {
    overlay.addEventListener('click', function() {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
      
      // Reset both buttons' icons
      const mobileIcon = mobileBtn?.querySelector('i');
      if (mobileIcon) {
        mobileIcon.classList.remove('fa-times');
        mobileIcon.classList.add('fa-bars');
      }
      
      const hamburgerIcon = hamburgerBtn?.querySelector('i');
      if (hamburgerIcon) {
        hamburgerIcon.classList.remove('fa-times');
        hamburgerIcon.classList.add('fa-bars');
      }
    });
  }

  // Close sidebar when clicking a link (for mobile)
  const navLinks = document.querySelectorAll('.nav-link');
  navLinks.forEach(link => {
    link.addEventListener('click', function() {
      if (window.innerWidth <= 768) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        
        const mobileIcon = mobileBtn?.querySelector('i');
        if (mobileIcon) {
          mobileIcon.classList.remove('fa-times');
          mobileIcon.classList.add('fa-bars');
        }
        
        const hamburgerIcon = hamburgerBtn?.querySelector('i');
        if (hamburgerIcon) {
          hamburgerIcon.classList.remove('fa-times');
          hamburgerIcon.classList.add('fa-bars');
        }
      }
    });
  });

  // Live avatar preview
  document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(ev) {
        document.getElementById('preview-container').innerHTML = 
          `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
      };
      reader.readAsDataURL(file);
    }
  });
</script>

</body>
</html>