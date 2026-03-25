<?php
session_start();

// Temporary dummy data - replace with actual database query
$user_name = "Dr. Maria Santos";
$user_email = "maria.santos@dean.cas.edu";
$user_role = "Department Dean";
$department = "College of Arts and Sciences";
$user_phone = "+63 912 345 6789";
$user_address = "Manila, Philippines";
$user_bio = "Experienced academic leader with over 15 years in higher education. Specialized in curriculum development and research management.";
$user_join_date = "June 15, 2022";
$user_initials = "MS";

// Statistics
$stats = [
    'projects_reviewed' => 87,
    'theses_approved' => 23,
    'faculty_supervised' => 28,
    'years_experience' => 15
];

// Recent activities
$recent_activities = [
    ['icon' => 'check-circle', 'action' => 'Approved thesis proposal', 'title' => 'AI-Powered Thesis Recommendation System', 'date' => '2 hours ago'],
    ['icon' => 'calendar-check', 'action' => 'Scheduled defense', 'title' => 'Mobile App for Campus Navigation', 'date' => 'Yesterday'],
    ['icon' => 'comment', 'action' => 'Reviewed project', 'title' => 'IoT-Based Classroom Monitoring', 'date' => '3 days ago'],
    ['icon' => 'user-plus', 'action' => 'Added new faculty member', 'title' => 'Prof. Mark Santiago', 'date' => '1 week ago'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Thesis Management System</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <!-- Overlay for sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Top Navigation Bar -->
    <header class="top-nav">
        <div class="nav-left">
            <button class="hamburger" id="hamburgerBtn" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="search-area">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
            </div>
        </div>
        <div class="nav-right">
            <div class="notification-icon">
                <i class="far fa-bell"></i>
                <span class="notification-badge">4</span>
            </div>
            <div class="profile-wrapper" id="profileWrapper">
                <div class="profile-trigger">
                    <span class="profile-name"><?php echo $user_name; ?></span>
                    <div class="profile-avatar"><?php echo $user_initials; ?></div>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="#"><i class="fas fa-cog"></i> Settings</a>
                     <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn">
             <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="logo-container">
            <div class="logo">Thesis<span>Manager</span></div>
            <div class="logo-sub">DEPARTMENT DEAN</div>
        </div>
        
        <div class="nav-menu">
            <a href="dean.php" class="nav-item">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Department</span>
            </a>
        <a href="#" class="nav-item">
      <i class="fas fa-user-tie"></i>
             <span>Faculty</span>
          </a>

            <a href="#" class="nav-item">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-calendar-check"></i>
                <span>Defenses</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-archive"></i>
                <span>Archive</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>

    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>My Profile</h1>
            <p>View and manage your personal information</p>
        </div>

        <!-- Profile Content -->
        <div class="profile-container">
            <!-- Left Column - Profile Info -->
            <div class="profile-left">
                <div class="profile-card">
                    <div class="profile-avatar-large">
                        <?php echo $user_initials; ?>
                    </div>
                    <h2><?php echo $user_name; ?></h2>
                    <p class="user-role"><?php echo $user_role; ?></p>
                    <p class="user-department"><?php echo $department; ?></p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['projects_reviewed']; ?></div>
                            <div class="stat-label">Projects Reviewed</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['theses_approved']; ?></div>
                            <div class="stat-label">Theses Approved</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['faculty_supervised']; ?></div>
                            <div class="stat-label">Faculty</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['years_experience']; ?></div>
                            <div class="stat-label">Years Exp.</div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <button class="btn-edit" id="editProfileBtn">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                        <button class="btn-change-password" id="changePasswordBtn">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column - Details -->
            <div class="profile-right">
                <!-- Personal Information -->
                <div class="info-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-circle"></i> Personal Information</h3>
                        <button class="btn-icon" id="editPersonalBtn">
                            <i class="fas fa-pen"></i>
                        </button>
                    </div>
                    <div class="info-content">
                        <div class="info-row">
                            <span class="info-label">Full Name:</span>
                            <span class="info-value" id="displayName"><?php echo $user_name; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email Address:</span>
                            <span class="info-value" id="displayEmail"><?php echo $user_email; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone Number:</span>
                            <span class="info-value" id="displayPhone"><?php echo $user_phone; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Address:</span>
                            <span class="info-value" id="displayAddress"><?php echo $user_address; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Department:</span>
                            <span class="info-value"><?php echo $department; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Role:</span>
                            <span class="info-value"><?php echo $user_role; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Member Since:</span>
                            <span class="info-value"><?php echo $user_join_date; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Bio -->
                <div class="info-card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> About Me</h3>
                        <button class="btn-icon" id="editBioBtn">
                            <i class="fas fa-pen"></i>
                        </button>
                    </div>
                    <div class="info-content">
                        <p class="bio-text" id="displayBio"><?php echo $user_bio; ?></p>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="info-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($recent_activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-action"><?php echo $activity['action']; ?></div>
                                <div class="activity-title"><?php echo $activity['title']; ?></div>
                                <div class="activity-time"><?php echo $activity['date']; ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="editName" value="<?php echo $user_name; ?>">
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="editEmail" value="<?php echo $user_email; ?>">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" id="editPhone" value="<?php echo $user_phone; ?>">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" id="editAddress" value="<?php echo $user_address; ?>">
                    </div>
                    <div class="form-group">
                        <label>Bio</label>
                        <textarea id="editBio" rows="4"><?php echo $user_bio; ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel">Cancel</button>
                <button class="btn-save">Save Changes</button>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" id="currentPassword" placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" id="newPassword" placeholder="Enter new password">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="confirmPassword" placeholder="Confirm new password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel">Cancel</button>
                <button class="btn-save">Update Password</button>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="js/profile.js"></script>
</body>
</html>