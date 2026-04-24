<?php
session_start();
include("../config/db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION["user_id"])) {
    header("Location: /ArchivingThesis/authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// Handle restore request
if(isset($_POST['restore_thesis'])) {
    $restore_thesis_id = (int)$_POST['thesis_id'];
    
    $update = $conn->prepare("UPDATE thesis_table SET is_archived = 0, archived_date = NULL WHERE thesis_id = ? AND student_id = ?");
    $update->bind_param("ii", $restore_thesis_id, $user_id);
    
    if($update->execute()) {
        $_SESSION['success'] = "Thesis restored successfully!";
    } else {
        $_SESSION['error'] = "Failed to restore thesis.";
    }
    $update->close();
    
    header("Location: archived.php");
    exit();
}

// Get user data
$user_query = "SELECT first_name, last_name FROM user_table WHERE user_id = ? LIMIT 1";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

$first_name = $user_data['first_name'] ?? '';
$last_name = $user_data['last_name'] ?? '';
$initials = !empty($first_name) && !empty($last_name) ? strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)) : "U";

// Get notification count
$notificationCount = 0;
$notif_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
if ($notif_row = $notif_result->fetch_assoc()) {
    $notificationCount = $notif_row['count'];
}
$notif_stmt->close();

// Get archived theses - GAMIT ANG ACTUAL COLUMNS SA DATABASE
$archived = [];
$query = "SELECT thesis_id, title, abstract, keywords, adviser, file_path, date_submitted, is_archived, archived_date, year 
          FROM thesis_table 
          WHERE student_id = ? AND is_archived = 1 
          ORDER BY archived_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $archived[] = $row;
}
$stmt->close();

$pageTitle = "Archived Theses";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#fef2f2;color:#1f2937;overflow-x:hidden}
        body.dark-mode{background:#1a1a1a}
        .layout{margin-left:0}
        .main-content{padding:1.5rem;max-width:1400px;margin:0 auto}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;padding-bottom:1rem;border-bottom:1px solid #fee2e2}
        body.dark-mode .topbar{border-bottom-color:#991b1b}
        .hamburger-menu{cursor:pointer;font-size:1.2rem;color:#dc2626;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:8px;transition:background .2s}
        .hamburger-menu:hover{background:#fee2e2}
        body.dark-mode .hamburger-menu:hover{background:#3d3d3d}
        .topbar h1{font-size:1.5rem;color:#991b1b}
        body.dark-mode .topbar h1{color:#fecaca}
        .user-info{display:flex;align-items:center;gap:1rem}
        .notification-container{position:relative}
        .notification-bell{position:relative;color:#dc2626;font-size:1.2rem;width:40px;height:40px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background .2s;text-decoration:none}
        .notification-bell:hover{background:#fee2e2}
        .notification-badge{position:absolute;top:-5px;right:-5px;background:#ef4444;color:#fff;font-size:.6rem;font-weight:600;border-radius:10px;padding:0 5px;min-width:18px;height:18px;display:flex;align-items:center;justify-content:center}
        .avatar-dropdown{position:relative}
        .avatar{width:40px;height:40px;background:linear-gradient(135deg,#dc2626,#991b1b);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;cursor:pointer;transition:transform .2s}
        .avatar:hover{transform:scale(1.05)}
        .dropdown-content{position:absolute;top:50px;right:0;background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);min-width:180px;display:none;z-index:100;border:1px solid #fee2e2;overflow:hidden}
        .dropdown-content.show{display:block;animation:fadeIn .2s ease}
        @keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
        .dropdown-content a{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;text-decoration:none;color:#1f2937;font-size:.85rem;transition:background .2s}
        .dropdown-content a:hover{background:#fef2f2}
        .dropdown-content hr{margin:.25rem 0;border-color:#fee2e2}
        body.dark-mode .dropdown-content{background:#2d2d2d;border-color:#991b1b}
        body.dark-mode .dropdown-content a{color:#e5e7eb}
        body.dark-mode .dropdown-content a:hover{background:#3d3d3d}
        .sidebar{position:fixed;top:0;left:-280px;width:280px;height:100%;background:linear-gradient(180deg,#991b1b 0%,#dc2626 100%);z-index:1000;transition:left .3s ease;display:flex;flex-direction:column;box-shadow:2px 0 10px rgba(0,0,0,.1)}
        .sidebar.open{left:0}
        .sidebar-header{padding:1.5rem;border-bottom:1px solid rgba(255,255,255,.2)}
        .sidebar-header h2{color:#fff;margin:0;font-size:1.3rem}
        .sidebar-header p{color:#fecaca;font-size:.8rem;margin-top:.25rem}
        .sidebar-nav{flex:1;padding:1rem;display:flex;flex-direction:column;gap:.25rem}
        .sidebar-nav .nav-link{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:12px;text-decoration:none;color:#fecaca;transition:all .2s;font-weight:500}
        .sidebar-nav .nav-link i{width:22px}
        .sidebar-nav .nav-link:hover{background:rgba(255,255,255,.15);color:#fff;transform:translateX(5px)}
        .sidebar-nav .nav-link.active{background:rgba(255,255,255,.2);color:#fff}
        .sidebar-footer{padding:1rem;border-top:1px solid rgba(255,255,255,.15)}
        .logout-btn{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:12px;text-decoration:none;color:#fecaca;transition:all .2s}
        .logout-btn:hover{background:rgba(255,255,255,.15);color:#fff}
        .overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);z-index:999;display:none}
        .overlay.show{display:block}
        .alert{padding:1rem;border-radius:12px;margin-bottom:1.5rem;display:flex;align-items:center;gap:.75rem}
        .alert-success{background:#d1fae5;color:#059669;border:1px solid #a7f3d0}
        .alert-error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
        body.dark-mode .alert-success{background:#064e3b;color:#6ee7b7}
        body.dark-mode .alert-error{background:#7f1d1d;color:#fca5a5}
        .archived-container{max-width:1200px;margin:0 auto}
        .archive-card{background:#fff;border-radius:16px;padding:1.5rem;margin-bottom:1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #fee2e2;transition:all .3s ease}
        body.dark-mode .archive-card{background:#2d2d2d;border-color:#991b1b}
        .archive-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(220,38,38,.1)}
        .archive-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;margin-bottom:1rem;gap:1rem}
        .archive-header h2{font-size:1.25rem;font-weight:600;color:#1f2937;margin:0;flex:1}
        body.dark-mode .archive-header h2{color:#fecaca}
        .status{padding:.25rem .75rem;border-radius:20px;font-size:.75rem;font-weight:500;white-space:nowrap}
        .status-archived{background:#e5e7eb;color:#6b7280}
        body.dark-mode .status-archived{background:#374151;color:#9ca3af}
        .archive-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem;margin:1rem 0;font-size:.85rem;color:#4b5563}
        .archive-meta i{width:20px;color:#dc2626}
        body.dark-mode .archive-meta{color:#cbd5e1}
        .archive-actions{display:flex;gap:1rem;margin-top:1rem;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;border-radius:8px;font-size:.85rem;font-weight:500;text-decoration:none;transition:all .2s;cursor:pointer;border:none}
        .btn-primary{background:#dc2626;color:#fff}
        .btn-primary:hover{background:#991b1b;transform:translateY(-1px)}
        .btn-secondary{background:#fef2f2;color:#dc2626;border:1px solid #fee2e2}
        .btn-secondary:hover{background:#fee2e2}
        .btn-restore{background:#10b981;color:#fff}
        .btn-restore:hover{background:#059669;transform:translateY(-1px)}
        body.dark-mode .btn-secondary{background:#3d3d3d;color:#fecaca;border-color:#991b1b}
        .archive-empty{text-align:center;padding:3rem;background:#fff;border-radius:16px;border:1px solid #fee2e2}
        body.dark-mode .archive-empty{background:#2d2d2d}
        .archive-empty i{font-size:3rem;color:#dc2626;margin-bottom:1rem}
        .archive-empty h3{margin-bottom:.5rem;color:#1f2937}
        body.dark-mode .archive-empty h3{color:#fecaca}
        .archive-empty p{color:#6b7280}
        @media(max-width:768px){.main-content{padding:1rem}.archive-header{flex-direction:column}.archive-meta{grid-template-columns:1fr}.archive-actions{flex-direction:column}.btn{justify-content:center;width:100%}.topbar h1{font-size:1.2rem}}
        @media(max-width:480px){.archive-card{padding:1rem}.archive-header h2{font-size:1rem}}
    </style>
</head>
<body>

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Theses Archive</h2>
        <p>Student Portal</p>
    </div>
    <nav class="sidebar-nav">
        <a href="student_dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
        <a href="projects.php" class="nav-link"><i class="fas fa-folder-open"></i> My Projects</a>
        <a href="submission.php" class="nav-link"><i class="fas fa-upload"></i> Submit Thesis</a>
        <a href="archived.php" class="nav-link active"><i class="fas fa-archive"></i> Archived Theses</a>
    </nav>
    <div class="sidebar-footer">
        <a href="profile.php" class="nav-link" style="margin-bottom:0.5rem"><i class="fas fa-user-circle"></i> Profile</a>
        <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</aside>

<div class="layout">
    <main class="main-content">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:1rem">
                <div class="hamburger-menu" id="hamburgerBtn"><i class="fas fa-bars"></i></div>
                <h1>Archived Theses</h1>
            </div>
            <div class="user-info">
                <div class="notification-container">
                    <a href="notification.php" class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                            <span class="notification-badge"><?= $notificationCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <div class="avatar-dropdown">
                    <div class="avatar" id="avatarBtn"><?= htmlspecialchars($initials) ?></div>
                    <div class="dropdown-content" id="dropdownMenu">
                        <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <hr>
                        <a href="/ArchivingThesis/authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="archived-container">
            <?php if (count($archived) === 0): ?>
                <div class="archive-empty">
                    <i class="fas fa-archive"></i>
                    <h3>No Archived Theses</h3>
                    <p>Your archived theses will appear here.</p>
                    <a href="projects.php" class="btn btn-primary" style="margin-top:1rem"><i class="fas fa-folder-open"></i> View My Projects</a>
                </div>
            <?php else: ?>
                <?php foreach ($archived as $a): ?>
                    <div class="archive-card">
                        <div class="archive-header">
                            <h2><?= htmlspecialchars($a["title"] ?? "Untitled") ?></h2>
                            <span class="status status-archived">Archived</span>
                        </div>
                        <div class="archive-meta">
                            <div><i class="fas fa-user-tie"></i> <strong>Adviser:</strong> <?= htmlspecialchars($a["adviser"] ?? 'Not Assigned') ?></div>
                            <div><i class="fas fa-tags"></i> <strong>Keywords:</strong> <?= htmlspecialchars($a["keywords"] ?? 'None') ?></div>
                            <div><i class="fas fa-calendar-alt"></i> <strong>Submitted:</strong> <?= date("F d, Y", strtotime($a["date_submitted"])) ?></div>
                            <?php if (!empty($a["archived_date"])): ?>
                                <div><i class="fas fa-archive"></i> <strong>Archived:</strong> <?= date("F d, Y", strtotime($a["archived_date"])) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($a["year"])): ?>
                                <div><i class="fas fa-calendar"></i> <strong>Year:</strong> <?= htmlspecialchars($a["year"]) ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($a["abstract"])): ?>
                            <div style="margin: 10px 0; padding: 10px; background: #fef2f2; border-radius: 8px;">
                                <strong><i class="fas fa-align-left"></i> Abstract:</strong>
                                <p style="margin-top: 5px; font-size: 0.85rem;"><?= htmlspecialchars(substr($a["abstract"], 0, 200)) ?>...</p>
                            </div>
                        <?php endif; ?>
                        <div class="archive-actions">
                            <?php if (!empty($a["file_path"])): ?>
                                <a href="../<?= htmlspecialchars($a["file_path"]) ?>" class="btn btn-secondary" target="_blank"><i class="fas fa-file-pdf"></i> View Manuscript</a>
                            <?php endif; ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="thesis_id" value="<?= $a['thesis_id'] ?>">
                                <button type="submit" name="restore_thesis" class="btn btn-restore" onclick="return confirm('Restore this thesis?')"><i class="fas fa-undo"></i> Restore</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
const hamburgerBtn=document.getElementById('hamburgerBtn');
const sidebar=document.getElementById('sidebar');
const overlay=document.getElementById('overlay');
const avatarBtn=document.getElementById('avatarBtn');
const dropdownMenu=document.getElementById('dropdownMenu');

function openSidebar(){sidebar.classList.add('open');overlay.classList.add('show');document.body.style.overflow='hidden';}
function closeSidebar(){sidebar.classList.remove('open');overlay.classList.remove('show');document.body.style.overflow='';}
function toggleSidebar(e){e.stopPropagation();if(sidebar.classList.contains('open')){closeSidebar();}else{openSidebar();}}

if(hamburgerBtn) hamburgerBtn.addEventListener('click',toggleSidebar);
if(overlay) overlay.addEventListener('click',closeSidebar);

document.addEventListener('keydown',function(e){if(e.key==='Escape'){if(sidebar.classList.contains('open')) closeSidebar();if(dropdownMenu && dropdownMenu.classList.contains('show')) dropdownMenu.classList.remove('show');}});
window.addEventListener('resize',function(){if(window.innerWidth>768 && sidebar.classList.contains('open')) closeSidebar();});

function toggleProfileDropdown(e){e.stopPropagation();dropdownMenu.classList.toggle('show');}
function closeProfileDropdown(e){const avatarDropdown=document.querySelector('.avatar-dropdown');if(avatarDropdown && !avatarDropdown.contains(e.target)){if(dropdownMenu) dropdownMenu.classList.remove('show');}}
if(avatarBtn){avatarBtn.addEventListener('click',toggleProfileDropdown);document.addEventListener('click',closeProfileDropdown);}

function initDarkMode(){const isDark=localStorage.getItem('darkMode')==='true';if(isDark) document.body.classList.add('dark-mode');}
initDarkMode();
</script>
</body>
</html>