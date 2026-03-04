<?php
session_start();
include("../config/db.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

/* ================================
   GET USER PROFILE DATA
================================ */
$stmt = $conn->prepare("
    SELECT first_name, last_name, email, contact_number, address, birth_date, profile_picture
    FROM user_table
    WHERE user_id = ?
");
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
$full  = trim($first . " " . $last);

$initials = "";
if ($first && $last) $initials = strtoupper(substr($first,0,1) . substr($last,0,1));
elseif ($first) $initials = strtoupper(substr($first,0,1));
else $initials = "U";

$profilePicUrl = $user["profile_picture"]
    ? "../uploads/profile_pictures/" . $user["profile_picture"]
    : "";

$email   = trim($user["email"] ?? "");
$contact = trim($user["contact_number"] ?? "");
$address = trim($user["address"] ?? "");
$birth   = trim($user["birth_date"] ?? "");

/* ================================
   GET UNREAD NOTIFICATIONS COUNT
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Theses Archive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Base styles */
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
           SIDEBAR - RED BACKGROUND (SAME AS DASHBOARD)
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

        /* Theme Toggle - adjusted for red sidebar */
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

        /* Profile Container */
        .profile-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 2rem;
        }

        /* Profile Card */
        .profile-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        body.dark-mode .profile-card {
            background: var(--card-bg-dark);
            box-shadow: 0 6px 24px rgba(0,0,0,0.35);
        }

        .card-title {
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.10);
        }

        body.dark-mode .card-title {
            border-bottom-color: rgba(229, 231, 235, 0.10);
        }

        .card-title h1 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            letter-spacing: 0.2px;
            color: var(--red-salsa);
        }

        /* Avatar Section */
        .profile-top {
            text-align: center;
            margin-bottom: 2rem;
        }

        .big-avatar-wrapper,
        .big-avatar {
            width: 140px;
            height: 140px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            overflow: hidden;
        }

        .big-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 5px solid white;
        }

        body.dark-mode .big-avatar-img {
            border-color: #334155;
        }

        .big-avatar {
            background: linear-gradient(135deg, var(--red-salsa), var(--persian-plum));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.2rem;
            font-weight: bold;
            color: white;
        }

        .profile-info h1 {
            margin: 0.5rem 0 0.25rem;
            font-size: 2.1rem;
            color: var(--persian-plum);
        }

        body.dark-mode .profile-info h1 {
            color: var(--red-salsa);
        }

        .student-id {
            color: var(--dim-gray);
            font-size: 0.95rem;
        }

        /* Details */
        .profile-details {
            margin: 1.5rem 0 2rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.9rem 0;
            border-bottom: 1px solid #e5e7eb;
            gap: 1rem;
        }

        body.dark-mode .detail-row {
            border-bottom-color: #475569;
        }

        .detail-row strong {
            min-width: 120px;
            font-weight: 600;
            color: var(--persian-plum);
        }

        body.dark-mode .detail-row strong {
            color: var(--red-salsa);
        }

        .detail-row span {
            color: var(--dim-gray);
            text-align: right;
            flex: 1;
        }

        body.dark-mode .detail-row span {
            color: #e0e0e0;
        }

        /* Edit button */
        .edit-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--red-salsa);
            color: white;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .edit-btn:hover {
            background: var(--persian-plum);
            transform: translateY(-2px);
        }

        .edit-btn i {
            font-size: 1rem;
        }

        /* Progress card */
        .profile-card.stats {
            padding: 1.75rem;
        }

        .profile-card.stats h3 {
            color: var(--persian-plum);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        body.dark-mode .profile-card.stats h3 {
            color: var(--red-salsa);
        }

        .progress-item {
            margin-bottom: 1.25rem;
        }

        .progress-item span {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dim-gray);
        }

        body.dark-mode .progress-item span {
            color: #e0e0e0;
        }

        .progress-bar {
            height: 10px;
            background: #e5e7eb;
            border-radius: 999px;
            overflow: hidden;
        }

        body.dark-mode .progress-bar {
            background: #334155;
        }

        .progress-bar .fill {
            height: 100%;
            background: linear-gradient(to right, var(--red-salsa), var(--persian-plum));
            width: 0%;
            transition: width 0.8s ease;
        }

        /* Overlay for mobile */
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

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .profile-container {
                grid-template-columns: 1fr;
                max-width: 800px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1.25rem 1rem;
            }

            .mobile-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .profile-top {
                margin-bottom: 1.5rem;
            }

            .big-avatar-wrapper,
            .big-avatar {
                width: 120px;
                height: 120px;
            }

            .profile-info h1 {
                font-size: 1.9rem;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }

            .detail-row span {
                text-align: left;
            }

            .edit-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .profile-card {
                padding: 1.5rem;
            }

            .big-avatar-wrapper,
            .big-avatar {
                width: 110px;
                height: 110px;
            }

            .profile-info h1 {
                font-size: 1.7rem;
            }

            .card-title h1 {
                font-size: 28px;
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

        <!-- TOPBAR with three-line and avatar -->
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <!-- Three-line menu -->
                <div class="hamburger-menu" id="hamburgerBtn">
                    <i class="fas fa-bars"></i>
                </div>
                <h1>My Profile</h1>
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

        <!-- Profile Content -->
        <div class="profile-container">

            <!-- Main Profile Card -->
            <div class="profile-card main">
                <div class="profile-top">
                    <?php if ($profilePicUrl && file_exists(__DIR__ . "/../uploads/profile_pictures/" . $user["profile_picture"])): ?>
                        <div class="big-avatar-wrapper">
                            <img class="big-avatar-img" src="<?= htmlspecialchars($profilePicUrl) ?>?v=<?= time() ?>" alt="Profile Picture">
                        </div>
                    <?php else: ?>
                        <div class="big-avatar"><?= htmlspecialchars($initials) ?></div>
                    <?php endif; ?>

                    <div class="profile-info">
                        <h1><?= htmlspecialchars($full ?: "User") ?></h1>
                    </div>
                </div>

                <div class="profile-details">
                    <div class="detail-row">
                        <strong>Email</strong>
                        <span><?= htmlspecialchars($email ?: "Not provided") ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Contact</strong>
                        <span><?= htmlspecialchars($contact ?: "Not provided") ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Address</strong>
                        <span><?= htmlspecialchars($address ?: "Not provided") ?></span>
                    </div>
                    <div class="detail-row">
                        <strong>Birth Date</strong>
                        <span><?= htmlspecialchars($birth ?: "Not provided") ?></span>
                    </div>
                </div>

                <a href="edit_profile.php" class="edit-btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>

            <!-- Progress Card -->
            <div class="profile-card stats">
                <h3>Thesis Progress</h3>

                <div class="progress-item">
                    <span>Overall Progress</span>
                    <div class="progress-bar"><div class="fill" style="width:0%"></div></div>
                </div>

                <div class="progress-item">
                    <span>Proposal</span>
                    <div class="progress-bar"><div class="fill" style="width:0%"></div></div>
                </div>

                <div class="progress-item">
                    <span>Final Manuscript</span>
                    <div class="progress-bar"><div class="fill" style="width:0%"></div></div>
                </div>
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
        if (dropdownMenu.classList.contains('show')) {
            dropdownMenu.classList.remove('show');
        }
    });

    dropdownMenu.addEventListener('click', function(e) {
        e.stopPropagation();
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
</script>

</body>
</html>