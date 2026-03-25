// Profile Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar elements
    const hamburger = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Profile elements
    const profileWrapper = document.getElementById('profileWrapper');
    const profileDropdown = document.getElementById('profileDropdown');
    
    // Modal elements
    const editProfileModal = document.getElementById('editProfileModal');
    const changePasswordModal = document.getElementById('changePasswordModal');
    const editProfileBtn = document.getElementById('editProfileBtn');
    const changePasswordBtn = document.getElementById('changePasswordBtn');
    const editPersonalBtn = document.getElementById('editPersonalBtn');
    const editBioBtn = document.getElementById('editBioBtn');
    
    // Open sidebar function
    function openSidebar() {
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Close sidebar function
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    // Toggle sidebar function
    function toggleSidebar(e) {
        if (e) e.stopPropagation();
        if (sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }
    
    // Sidebar event listeners
    if (hamburger) {
        hamburger.addEventListener('click', toggleSidebar);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }
    
    // Profile dropdown toggle
    if (profileWrapper && profileDropdown) {
        profileWrapper.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (profileDropdown.classList.contains('show') && 
                !profileWrapper.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });
    }
    
    // Close sidebar with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
            if (profileDropdown && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
            }
            if (editProfileModal && editProfileModal.classList.contains('show')) {
                closeModal(editProfileModal);
            }
            if (changePasswordModal && changePasswordModal.classList.contains('show')) {
                closeModal(changePasswordModal);
            }
        }
    });
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        }, 250);
    });
    
    // Close sidebar when clicking sidebar links on mobile
    const sideNavLinks = document.querySelectorAll('.nav-item');
    sideNavLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
    
    // Modal Functions
    function openModal(modal) {
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    function closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    // Edit Profile Modal
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function() {
            openModal(editProfileModal);
        });
    }
    
    if (editPersonalBtn) {
        editPersonalBtn.addEventListener('click', function() {
            openModal(editProfileModal);
        });
    }
    
    // Change Password Modal
    if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', function() {
            openModal(changePasswordModal);
        });
    }
    
    // Edit Bio Modal (opens edit profile modal and scrolls to bio)
    if (editBioBtn) {
        editBioBtn.addEventListener('click', function() {
            openModal(editProfileModal);
            setTimeout(() => {
                const bioTextarea = document.getElementById('editBio');
                if (bioTextarea) {
                    bioTextarea.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    bioTextarea.focus();
                }
            }, 100);
        });
    }
    
    // Close modals
    const modalCloseBtns = document.querySelectorAll('.modal-close, .btn-cancel');
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal(editProfileModal);
            closeModal(changePasswordModal);
        });
    });
    
    // Save Profile Changes
    const saveProfileBtn = document.querySelector('#editProfileModal .btn-save');
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', function() {
            const newName = document.getElementById('editName').value;
            const newEmail = document.getElementById('editEmail').value;
            const newPhone = document.getElementById('editPhone').value;
            const newAddress = document.getElementById('editAddress').value;
            const newBio = document.getElementById('editBio').value;
            
            // Update display
            const displayName = document.getElementById('displayName');
            const displayEmail = document.getElementById('displayEmail');
            const displayPhone = document.getElementById('displayPhone');
            const displayAddress = document.getElementById('displayAddress');
            const displayBio = document.getElementById('displayBio');
            const profileName = document.querySelector('.profile-card h2');
            const profileAvatar = document.querySelector('.profile-avatar-large');
            const topNavName = document.querySelector('.profile-name');
            
            if (displayName) displayName.textContent = newName;
            if (displayEmail) displayEmail.textContent = newEmail;
            if (displayPhone) displayPhone.textContent = newPhone;
            if (displayAddress) displayAddress.textContent = newAddress;
            if (displayBio) displayBio.textContent = newBio;
            if (profileName) profileName.textContent = newName;
            if (topNavName) topNavName.textContent = newName;
            
            // Update avatar initials
            if (profileAvatar) {
                const initials = newName.split(' ').map(n => n[0]).join('').toUpperCase();
                profileAvatar.textContent = initials;
                const navAvatar = document.querySelector('.profile-avatar');
                if (navAvatar) navAvatar.textContent = initials;
            }
            
            alert('Profile updated successfully!');
            closeModal(editProfileModal);
        });
    }
    
    // Save Password Changes
    const savePasswordBtn = document.querySelector('#changePasswordModal .btn-save');
    if (savePasswordBtn) {
        savePasswordBtn.addEventListener('click', function() {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('Please fill in all fields');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Password must be at least 6 characters long');
                return;
            }
            
            alert('Password changed successfully!');
            closeModal(changePasswordModal);
            
            // Clear form
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        });
    }
    
    // Click outside modal to close
    window.addEventListener('click', function(e) {
        if (e.target === editProfileModal) {
            closeModal(editProfileModal);
        }
        if (e.target === changePasswordModal) {
            closeModal(changePasswordModal);
        }
    });
    
    // Notification click
    const notificationIcon = document.querySelector('.notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            alert('You have 4 new notifications');
        });
    }
    
    // View all activities
    const viewAllLink = document.querySelector('.view-all');
    if (viewAllLink) {
        viewAllLink.addEventListener('click', function(e) {
            e.preventDefault();
            alert('View all activities feature coming soon!');
        });
    }
});