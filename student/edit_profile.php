<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$error = "";

/* ================================
   GET USER DATA
================================ */
$stmt = $conn->prepare("SELECT first_name, last_name, email, contact_number, address, birth_date, profile_picture FROM user_table WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: ../authentication/login.php");
    exit;
}

$first = trim($user["first_name"] ?? "");
$last  = trim($user["last_name"] ?? "");
$fullName = trim($first . " " . $last);
$initials = $first && $last ? strtoupper(substr($first, 0, 1) . substr($last, 0, 1)) : "U";

$profilePicUrl = $user["profile_picture"] 
    ? "../uploads/profile_pictures/" . $user["profile_picture"] 
    : "";

/* ================================
   GET NOTIFICATIONS COUNT
================================ */
$notificationCount = 0;
try {
    $notif_query = "SELECT COUNT(*) as total FROM notification_table WHERE user_id = ? AND status != 'read'";
    $stmt = $conn->prepare($notif_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notifResult = $stmt->get_result()->fetch_assoc();
    $notificationCount = $notifResult['total'] ?? 0;
    $stmt->close();
} catch (Exception $e) {
    $notificationCount = 0;
}

/* ================================
   HANDLE FORM SUBMISSION
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name  = trim($_POST["first_name"] ?? "");
    $last_name   = trim($_POST["last_name"] ?? "");
    $email       = trim($_POST["email"] ?? "");
    $contact_num = trim($_POST["contact_number"] ?? "");
    $birth_date  = trim($_POST["birth_date"] ?? "");
    $address     = trim($_POST["address"] ?? "");

    if ($first_name === "" || $last_name === "" || $email === "") {
        $error = "First name, last name, and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $newFileName = null;

        if (!empty($_FILES["profile_picture"]["name"])) {
            $file = $_FILES["profile_picture"];
            $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

            if (!in_array($ext, ["jpg", "jpeg", "png"])) {
                $error = "Only JPG, JPEG or PNG files allowed.";
            } else {
                $uploadDir = __DIR__ . "/../uploads/profile_pictures/";
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                $newFileName = "user_" . $user_id . "_" . time() . "." . $ext;
                $dest = $uploadDir . $newFileName;

                if (!move_uploaded_file($file["tmp_name"], $dest)) {
                    $error = "Failed to upload picture.";
                    $newFileName = null;
                }
            }
        }

        if (!$error) {
            if ($newFileName) {
                $sql = "UPDATE user_table SET first_name=?, last_name=?, email=?, contact_number=?, address=?, birth_date=?, profile_picture=?, updated_at=NOW() WHERE user_id=?";
                $upd = $conn->prepare($sql);
                $upd->bind_param("sssssssi", $first_name, $last_name, $email, $contact_num, $address, $birth_date, $newFileName, $user_id);
            } else {
                $sql = "UPDATE user_table SET first_name=?, last_name=?, email=?, contact_number=?, address=?, birth_date=?, updated_at=NOW() WHERE user_id=?";
                $upd = $conn->prepare($sql);
                $upd->bind_param("ssssssi", $first_name, $last_name, $email, $contact_num, $address, $birth_date, $user_id);
            }

            if ($upd->execute()) {
                $upd->close();
                header("Location: profile.php");
                exit;
            } else {
                $error = "Update failed: " . $upd->error;
                $upd->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Theses Archive</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ====================================
           BASE STYLES
        ==================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --red-salsa: #FE4853;
            --persian-plum: #732529;
            --dim-gray: #6E6E6E;
            --card-bg: #ffffff;
            --card-bg-dark: #1e293b;
            --text-dark: #0f172a;
            --text-light: #e5e7eb;
            --muted: #6b7280;
            --muted-dark: #94a3b8;
            --radius: 16px;
            --shadow: 0 4px 20px rgba(0,0,0,0.08);
            --primary: #FE4853;
            --primary-hover: #732529;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f8fafc;
            color: var(--text-dark);
            min-height: 100vh;
            margin: 0;
        }

        body.dark-mode {
            background: #0f172a;
            color: var(--text-light);
        }

        .layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* ====================================
           SIDEBAR - RED BACKGROUND
        ==================================== */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #FE4853 0%, #732529 100%);
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease;
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

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
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

        /* Avatar Container */
        .avatar-container {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        /* Avatar Dropdown */
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

        /* Notification Bell */
        .notification-bell {
            position: relative;
            color: #6E6E6E;
            font-size: 1.25rem;
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

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Mobile menu button */
        .mobile-menu-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1001;
            border: none;
            background: var(--red-salsa);
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
            background: var(--persian-plum);
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

        /* ====================================
           EDIT PROFILE STYLES
        ==================================== */
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            padding: 1rem;
        }

        .profile-card.main {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 800px;
        }

        body.dark-mode .profile-card.main {
            background: var(--card-bg-dark);
        }

        .profile-title {
            color: var(--red-salsa);
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
        }

        body.dark-mode .profile-title {
            color: var(--red-salsa);
        }

        /* Alert */
        .alert {
            padding: 1rem;
            background: #fee2e2;
            color: #991b1b;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc2626;
        }

        body.dark-mode .alert {
            background: #3a1a1a;
            color: #fca5a5;
        }

        /* Form */
        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            font-size: 1rem;
            color: var(--persian-plum);
        }

        body.dark-mode .form-group label {
            color: var(--red-salsa);
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="date"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--red-salsa);
            box-shadow: 0 0 0 3px rgba(254, 72, 83, 0.1);
        }

        body.dark-mode .form-group input,
        body.dark-mode .form-group textarea {
            background: #1e293b;
            color: #e5e7eb;
            border-color: #475569;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Picture Group */
        .picture-group {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        body.dark-mode .picture-group {
            border-bottom-color: #475569;
        }

        .current-picture {
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.12);
            background: #f1f5f9;
        }

        body.dark-mode .current-picture {
            border-color: #334155;
            background: #1e293b;
        }

        .current-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .current-picture.placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dim-gray);
            font-size: 0.9rem;
        }

        .picture-group input[type="file"] {
            display: none;
        }

        .file-upload-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin: 10px 0 6px;
            flex-wrap: wrap;
        }

        .file-upload-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background: var(--red-salsa);
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .file-upload-btn:hover {
            background: var(--persian-plum);
            transform: translateY(-2px);
        }

        .file-name {
            font-size: 0.9rem;
            color: var(--dim-gray);
        }

        body.dark-mode .file-name {
            color: #94a3b8;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn.primary {
            background: var(--red-salsa);
            color: white;
        }

        .btn.primary:hover {
            background: var(--persian-plum);
            transform: translateY(-2px);
        }

        .btn.secondary {
            background: #e5e7eb;
            color: var(--dim-gray);
        }

        .btn.secondary:hover {
            background: #d1d5db;
            transform: translateY(-2px);
        }

        body.dark-mode .btn.secondary {
            background: #334155;
            color: #e0e0e0;
        }

        body.dark-mode .btn.secondary:hover {
            background: #475569;
        }

        /* Responsive */
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

            .profile-card.main {
                padding: 1.5rem;
            }

            .profile-title {
                font-size: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .file-upload-wrapper {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .profile-card.main {
                padding: 1rem;
            }

            .current-picture {
                width: 120px;
                height: 120px;
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

<div class="layout">

    <!-- SIDEBAR - RED BACKGROUND -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Theses Archive</h2>
            <p>Student Portal</p>
        </div>

        <nav class="sidebar-nav">
            <a href="student_dashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="projects.php" class="nav-link">
                <i class="fas fa-folder-open"></i> My Projects
            </a>
            <a href="submission.php" class="nav-link">
                <i class="fas fa-upload"></i> Submit Thesis
            </a>
            <a href="archived.php" class="nav-link">
                <i class="fas fa-archive"></i> Archived Theses
            </a>
            <a href="profile.php" class="nav-link active">
                <i class="fas fa-user-circle"></i> Profile
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

    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <!-- Three-line menu -->
                <div class="hamburger-menu" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>
                <h1>Edit Profile</h1>
            </div>

            <div class="user-info">
                <!-- Notification Bell -->
                <a href="notification.php" class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge"><?= $notificationCount ?></span>
                    <?php endif; ?>
                </a>
                
                <!-- Avatar Dropdown - MR Initials -->
                <div class="avatar-container">
                    <div class="avatar-dropdown">
                        <div class="avatar" id="avatarBtn">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <div class="dropdown-content" id="dropdownMenu">
                            <a href="profile.php">
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
            </div>
        </header>

        <!-- EDIT PROFILE CONTENT -->
        <div class="profile-container">
            <div class="profile-card main">
                <h2 class="profile-title">Edit Profile</h2>

                <?php if ($error): ?>
                    <div class="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="edit-form">

                    <div class="form-group picture-group">
                        <label>Profile Picture</label>

                        <?php if ($profilePicUrl && file_exists(__DIR__ . "/../uploads/profile_pictures/" . $user["profile_picture"])): ?>
                            <div class="current-picture">
                                <img src="<?= htmlspecialchars($profilePicUrl) ?>?v=<?= time() ?>" alt="Current profile picture">
                            </div>
                        <?php else: ?>
                            <div class="current-picture placeholder">
                                <span>No picture set</span>
                            </div>
                        <?php endif; ?>

                        <div class="file-upload-wrapper">
                            <input type="file" name="profile_picture" accept="image/jpeg,image/png" id="profile_picture">
                            <label for="profile_picture" class="file-upload-btn">
                                <i class="fas fa-upload"></i> Choose File
                            </label>
                            <span class="file-name">No file chosen</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($first) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($last) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user["email"]) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" value="<?= htmlspecialchars($user["contact_number"] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="birth_date">Birth Date</label>
                        <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars($user["birth_date"] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="4"><?= htmlspecialchars($user["address"] ?? '') ?></textarea>
                    </div>

                    <div class="form-actions">
                        <a href="profile.php" class="btn secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
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
        });
    }

    window.addEventListener('click', function() {
        if (dropdownMenu && dropdownMenu.classList.contains('show')) {
            dropdownMenu.classList.remove('show');
        }
    });

    if (dropdownMenu) {
        dropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

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
    document.getElementById('profile_picture')?.addEventListener('change', function(e) {
        const fileName = e.target.files.length > 0 ? e.target.files[0].name : 'No file chosen';
        document.querySelector('.file-name').textContent = fileName;
    });
</script>

</body>
</html>