 <?php
session_start();
include("../config/db.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// Get user data
$stmt = $conn->prepare("SELECT first_name, last_name, email, contact_number, address, birth_date, profile_picture FROM user_table WHERE user_id = ?");
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
if ($first && $last) {
    $initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
} elseif ($first) {
    $initials = strtoupper(substr($first, 0, 1));
} else {
    $initials = "U";
}

$profilePicUrl = $user["profile_picture"] ? "../uploads/profile_pictures/" . $user["profile_picture"] : "";
$email   = trim($user["email"] ?? "");
$contact = trim($user["contact_number"] ?? "");
$address = trim($user["address"] ?? "");
$birth   = trim($user["birth_date"] ?? "");

// Get notification count
$notificationCount = 0;
$notif_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0";
$notif_stmt = $conn->prepare($notif_query);
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifResult = $notif_stmt->get_result()->fetch_assoc();
$notificationCount = $notifResult['total'] ?? 0;
$notif_stmt->close();

// Fetch thesis progress from thesis_table based on status
// Status values: 'pending', 'approved', 'rejected', 'archived', etc.
$proposal_progress = 0;
$final_progress = 0;
$overall_progress = 0;

// Query to get all thesis submissions by this user
$thesis_stmt = $conn->prepare("SELECT thesis_id, title, is_read, date_submitted FROM thesis_table WHERE student_id = ? ORDER BY date_submitted DESC LIMIT 5");
if ($thesis_stmt) {
    $thesis_stmt->bind_param("i", $user_id);
    $thesis_stmt->execute();
    $theses = $thesis_stmt->get_result();
    
    $thesis_count = 0;
    $approved_count = 0;
    
    while ($thesis = $theses->fetch_assoc()) {
        $thesis_count++;
        // Check if thesis is approved (is_read = 'approved')
        if ($thesis['is_read'] == 'approved') {
            $approved_count++;
        }
    }
    $thesis_stmt->close();
    
    // Calculate progress based on approved theses
    if ($thesis_count > 0) {
        $overall_progress = round(($approved_count / $thesis_count) * 100);
    }
    
    // For demo purposes, set some sample progress values
    // You can customize this logic based on your requirements
    $proposal_progress = min(100, $overall_progress + 10);
    $final_progress = $overall_progress;
}

$pageTitle = "My Profile";
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Theses Archive</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <header class="top-nav">
        <div class="nav-left">
            <button class="hamburger" id="hamburgerBtn"><span></span><span></span><span></span></button>
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="search-area"><i class="fas fa-search"></i><input type="text" id="searchInput" placeholder="Search projects..."></div>
        </div>
        <div class="nav-right">
            <div class="notification-icon" id="notificationIcon"><i class="far fa-bell"></i><?php if($notificationCount > 0): ?><span class="notification-badge"><?= $notificationCount ?></span><?php endif; ?></div>
            <div class="profile-wrapper" id="profileWrapper">
                <div class="profile-trigger"><span class="profile-name"><?= htmlspecialchars($full) ?></span><div class="profile-avatar"><?= htmlspecialchars($initials) ?></div></div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="edit_profile.php"><i class="fas fa-edit"></i> Edit Profile</a>
                    <a href="change_password.php"><i class="fas fa-key"></i> Change Password</a>
                    <hr style="margin: 4px 0; opacity:0.3">
                    <a href="../authentication/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <div class="logo-container"><div class="logo">Thesis<span>Manager</span></div><div class="logo-sub">STUDENT PORTAL</div></div>
        <div class="nav-menu">
            <a href="student_dashboard.php" class="nav-item"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <a href="projects.php" class="nav-item"><i class="fas fa-folder-open"></i><span>My Projects</span></a>
            <a href="submission.php" class="nav-item"><i class="fas fa-upload"></i><span>Submit Thesis</span></a>
            <a href="archived.php" class="nav-item"><i class="fas fa-archive"></i><span>Archived Theses</span></a>
            <a href="profile.php" class="nav-item active"><i class="fas fa-user-circle"></i><span>Profile</span></a>
        </div>
        <div class="nav-footer">
            <div class="theme-toggle"><input type="checkbox" id="darkmode"><label for="darkmode" class="toggle-label"><i class="fas fa-sun"></i><i class="fas fa-moon"></i></label></div>
            <a href="../authentication/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="dashboard-grid">
            <!-- Profile Information Card (Redesigned) -->
            <div class="glass-card">
                <div class="profile-header">
                    <?php if ($profilePicUrl && file_exists(__DIR__ . "/../uploads/profile_pictures/" . $user["profile_picture"])): ?>
                        <div class="avatar-large"><img src="<?= htmlspecialchars($profilePicUrl) ?>?v=<?= time() ?>" alt="Profile"></div>
                    <?php else: ?>
                        <div class="avatar-large"><?= htmlspecialchars($initials) ?></div>
                    <?php endif; ?>
                    <div class="profile-title">
                        <h2><?= htmlspecialchars($full ?: "Student") ?></h2>
                        <span class="profile-badge"><i class="fas fa-graduation-cap"></i> Thesis Candidate</span>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info-item"><div class="info-label"><i class="fas fa-envelope"></i> Email</div><div class="info-value"><?= htmlspecialchars($email ?: "not set") ?></div></div>
                    <div class="info-item"><div class="info-label"><i class="fas fa-phone-alt"></i> Contact</div><div class="info-value"><?= htmlspecialchars($contact ?: "not provided") ?></div></div>
                    <div class="info-item"><div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div><div class="info-value"><?= htmlspecialchars($address ?: "—") ?></div></div>
                    <div class="info-item"><div class="info-label"><i class="fas fa-calendar-alt"></i> Birth Date</div><div class="info-value"><?= htmlspecialchars($birth ?: "—") ?></div></div>
                </div>
                <div class="action-buttons">
                    <a href="edit_profile.php" class="btn-primary"><i class="fas fa-user-edit"></i> Edit Profile</a>
                    <a href="change_password.php" class="btn-outline"><i class="fas fa-lock"></i> Change Password</a>
                </div>
            </div>

            <!-- Thesis Progress Card (dynamic bars) -->
            <div class="glass-card">
                <h3 style="display: flex; align-items: center; gap: 8px; margin-bottom: 1.5rem;"><i class="fas fa-chart-line" style="color:#dc2626;"></i> Thesis Progress</h3>
                <div class="progress-stats">
                    <div class="progress-item-modern">
                        <div class="progress-header"><span>📄 Overall Completion</span><span class="percentage" id="overallPercent">0%</span></div>
                        <div class="progress-track"><div class="progress-fill" id="overallFill" style="width:0%"></div></div>
                    </div>
                    <div class="progress-item-modern">
                        <div class="progress-header"><span>📑 Proposal</span><span class="percentage" id="propPercent">0%</span></div>
                        <div class="progress-track"><div class="progress-fill" id="propFill" style="width:0%"></div></div>
                    </div>
                    <div class="progress-item-modern">
                        <div class="progress-header"><span>📖 Final Manuscript</span><span class="percentage" id="finalPercent">0%</span></div>
                        <div class="progress-track"><div class="progress-fill" id="finalFill" style="width:0%"></div></div>
                    </div>
                </div>
                <div style="margin-top: 20px; font-size:0.75rem; background:#fff2f2; padding:10px; border-radius:18px; text-align:center;">
                    <i class="fas fa-info-circle"></i> Keep progressing! Submit your thesis for review.
                </div>
            </div>
        </div>
    </main>

    <script>
        // Pass PHP progress values to JS
        const proposalVal = <?= (int)$proposal_progress ?>;
        const finalVal = <?= (int)$final_progress ?>;
        const overallVal = <?= (int)$overall_progress ?>;

        // DOM Elements
        const hamburger = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const profileWrap = document.getElementById('profileWrapper');
        const profileDropdown = document.getElementById('profileDropdown');
        const darkToggle = document.getElementById('darkmode');
        const notifIcon = document.getElementById('notificationIcon');

        // Sidebar functions
        function openSidebar() { sidebar.classList.add('open'); overlay.classList.add('show'); document.body.style.overflow = 'hidden'; }
        function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); document.body.style.overflow = ''; }
        if(hamburger) hamburger.addEventListener('click', (e) => { e.stopPropagation(); sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
        if(overlay) overlay.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', (e) => { if(e.key === 'Escape') { closeSidebar(); if(profileDropdown) profileDropdown.classList.remove('show'); } });

        // Profile dropdown
        if(profileWrap) {
            profileWrap.addEventListener('click', (e) => { e.stopPropagation(); profileDropdown.classList.toggle('show'); });
            document.addEventListener('click', (e) => { if(!profileWrap.contains(e.target)) profileDropdown.classList.remove('show'); });
        }
        if(notifIcon) notifIcon.addEventListener('click', () => window.location.href = 'notification.php');
        
        // Search dummy
        const searchInput = document.getElementById('searchInput');
        if(searchInput) searchInput.addEventListener('input', (e) => console.log('Search:', e.target.value));

        // Dark Mode
        function initDarkMode() {
            const isDark = localStorage.getItem('darkMode') === 'true';
            if(isDark) { document.body.classList.add('dark-mode'); if(darkToggle) darkToggle.checked = true; }
            if(darkToggle) darkToggle.addEventListener('change', function() {
                if(this.checked) { document.body.classList.add('dark-mode'); localStorage.setItem('darkMode', 'true'); }
                else { document.body.classList.remove('dark-mode'); localStorage.setItem('darkMode', 'false'); }
            });
        }

        // Animate progress bars with delay
        function animateProgress() {
            const propFill = document.getElementById('propFill');
            const finalFill = document.getElementById('finalFill');
            const overallFill = document.getElementById('overallFill');
            const propPerc = document.getElementById('propPercent');
            const finalPerc = document.getElementById('finalPercent');
            const overallPerc = document.getElementById('overallPercent');
            
            setTimeout(() => {
                if(propFill) { propFill.style.width = proposalVal + '%'; propPerc.innerText = proposalVal + '%'; }
                if(finalFill) { finalFill.style.width = finalVal + '%'; finalPerc.innerText = finalVal + '%'; }
                if(overallFill) { overallFill.style.width = overallVal + '%'; overallPerc.innerText = overallVal + '%'; }
            }, 150);
        }

        initDarkMode();
        animateProgress();
    </script>
</body>
</html>