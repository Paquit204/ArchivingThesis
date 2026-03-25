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
$user_initials = "MS";

// Error messages array
$errors = [];
$success_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = trim($_POST['full_name'] ?? '');
    $user_email = trim($_POST['email'] ?? '');
    $user_phone = trim($_POST['phone'] ?? '');
    $user_address = trim($_POST['address'] ?? '');
    $user_bio = trim($_POST['bio'] ?? '');
    
    // Validation
    if (empty($user_name)) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($user_email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($errors)) {
        // Here you would save to database
        $success_message = "Profile updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Thesis Management System</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/editProfile.css">
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
                    <a href="editProfile.php"><i class="fas fa-edit"></i> Edit Profile</a>
                    <a href="#"><i class="fas fa-cog"></i> Settings</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
            <a href="deanDashboard.php" class="nav-item">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-users"></i>
                <span>Faculty</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
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
            <a href="#" class="nav-item active">
                <i class="fas fa-user-edit"></i>
                <span>Edit Profile</span>
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </div>
        
        <div class="nav-footer">
      <a href="/ArchivingThesis/authentication/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
               </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1>Edit Profile</h1>
            <p>Update your personal information and preferences</p>
        </div>

        <!-- Success Message -->
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <!-- Edit Profile Form -->
        <div class="edit-profile-container">
            <form method="POST" action="" class="edit-profile-form" id="editProfileForm">
                <!-- Avatar Section -->
                <div class="avatar-section">
                    <div class="avatar-preview">
                        <div class="avatar-large" id="avatarPreview">
                            <?php echo $user_initials; ?>
                        </div>
                        <button type="button" class="btn-change-avatar" id="changeAvatarBtn">
                            <i class="fas fa-camera"></i> Change Avatar
                        </button>
                    </div>
                    <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                </div>

                <!-- Form Fields -->
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">
                            <i class="fas fa-user"></i> Full Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               value="<?php echo htmlspecialchars($user_name); ?>"
                               class="<?php echo isset($errors['full_name']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="error-message"><?php echo $errors['full_name']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email Address <span class="required">*</span>
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($user_email); ?>"
                               class="<?php echo isset($errors['email']) ? 'error' : ''; ?>">
                        <?php if (isset($errors['email'])): ?>
                            <span class="error-message"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone"></i> Phone Number
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="<?php echo htmlspecialchars($user_phone); ?>">
                    </div>

                    <div class="form-group">
                        <label for="department">
                            <i class="fas fa-building"></i> Department
                        </label>
                        <input type="text" 
                               id="department" 
                               name="department" 
                               value="<?php echo htmlspecialchars($department); ?>"
                               readonly
                               disabled>
                        <small class="help-text">Department cannot be changed</small>
                    </div>

                    <div class="form-group full-width">
                        <label for="address">
                            <i class="fas fa-map-marker-alt"></i> Address
                        </label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               value="<?php echo htmlspecialchars($user_address); ?>">
                    </div>

                    <div class="form-group full-width">
                        <label for="bio">
                            <i class="fas fa-info-circle"></i> Bio / About Me
                        </label>
                        <textarea id="bio" 
                                  name="bio" 
                                  rows="5"><?php echo htmlspecialchars($user_bio); ?></textarea>
                        <small class="help-text">Tell us a little about yourself</small>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="profile.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>

            <!-- Danger Zone -->
            <div class="danger-zone">
                <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                <p>Once you delete your account, there is no going back. Please be certain.</p>
                <button type="button" class="btn-delete" id="deleteAccountBtn">
                    <i class="fas fa-trash-alt"></i> Delete Account
                </button>
            </div>
        </div>
    </main>

    <!-- Delete Account Modal -->
    <div class="modal" id="deleteAccountModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Delete Account</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                <p class="warning-text">All your data, including projects, feedback, and activities, will be permanently removed.</p>
                <div class="form-group">
                    <label>Type <strong>DELETE</strong> to confirm:</label>
                    <input type="text" id="confirmDelete" placeholder="DELETE">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel">Cancel</button>
                <button class="btn-delete-confirm" id="confirmDeleteBtn" disabled>Delete Account</button>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="js/editProfile.js"></script>
</body>
</html>