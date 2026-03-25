// Edit Profile Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar elements
    const hamburger = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Profile elements
    const profileWrapper = document.getElementById('profileWrapper');
    const profileDropdown = document.getElementById('profileDropdown');
    
    // Avatar upload elements
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    
    // Delete account elements
    const deleteAccountBtn = document.getElementById('deleteAccountBtn');
    const deleteModal = document.getElementById('deleteAccountModal');
    const confirmDeleteInput = document.getElementById('confirmDelete');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
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
            if (deleteModal && deleteModal.classList.contains('show')) {
                closeModal(deleteModal);
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
    
    // Avatar upload functionality
    if (changeAvatarBtn && avatarInput && avatarPreview) {
        changeAvatarBtn.addEventListener('click', function() {
            avatarInput.click();
        });
        
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    avatarPreview.style.background = 'none';
                    avatarPreview.style.backgroundImage = `url(${event.target.result})`;
                    avatarPreview.style.backgroundSize = 'cover';
                    avatarPreview.style.backgroundPosition = 'center';
                    avatarPreview.textContent = '';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Form validation
    const editForm = document.getElementById('editProfileForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            let hasError = false;
            
            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
            
            // Validate full name
            if (!fullName) {
                showError('full_name', 'Full name is required');
                hasError = true;
            }
            
            // Validate email
            if (!email) {
                showError('email', 'Email address is required');
                hasError = true;
            } else if (!isValidEmail(email)) {
                showError('email', 'Please enter a valid email address');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
            } else {
                // Show success message before actual submit
                const alertDiv = document.querySelector('.alert');
                if (alertDiv) {
                    alertDiv.style.display = 'flex';
                    setTimeout(() => {
                        alertDiv.style.opacity = '0';
                        setTimeout(() => {
                            alertDiv.style.display = 'none';
                            alertDiv.style.opacity = '1';
                        }, 500);
                    }, 3000);
                }
            }
        });
    }
    
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('error');
            const errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.textContent = message;
            field.parentNode.appendChild(errorSpan);
        }
    }
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
        return re.test(email);
    }
    
    // Modal functions
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
            if (confirmDeleteInput) confirmDeleteInput.value = '';
            if (confirmDeleteBtn) confirmDeleteBtn.disabled = true;
        }
    }
    
    // Delete account modal
    if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', function() {
            openModal(deleteModal);
        });
    }
    
    // Modal close buttons
    const modalCloseBtns = document.querySelectorAll('.modal-close, .modal .btn-cancel');
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            closeModal(deleteModal);
        });
    });
    
    // Confirm delete input validation
    if (confirmDeleteInput && confirmDeleteBtn) {
        confirmDeleteInput.addEventListener('input', function() {
            if (this.value === 'DELETE') {
                confirmDeleteBtn.disabled = false;
            } else {
                confirmDeleteBtn.disabled = true;
            }
        });
        
        confirmDeleteBtn.addEventListener('click', function() {
            if (confirmDeleteInput.value === 'DELETE') {
                alert('Account deletion feature coming soon. This would permanently delete your account.');
                closeModal(deleteModal);
            }
        });
    }
    
    // Click outside modal to close
    window.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            closeModal(deleteModal);
        }
    });
    
    // Notification click
    const notificationIcon = document.querySelector('.notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            alert('You have 4 new notifications');
        });
    }
    
    // Real-time character counter for bio
    const bioTextarea = document.getElementById('bio');
    if (bioTextarea) {
        const charCount = document.createElement('small');
        charCount.className = 'help-text';
        charCount.style.marginTop = '5px';
        charCount.style.display = 'block';
        charCount.textContent = `0 / 500 characters`;
        bioTextarea.parentNode.appendChild(charCount);
        
        function updateCharCount() {
            const length = bioTextarea.value.length;
            charCount.textContent = `${length} / 500 characters`;
            if (length > 500) {
                charCount.style.color = '#e74c3c';
            } else {
                charCount.style.color = '#999999';
            }
        }
        
        bioTextarea.addEventListener('input', updateCharCount);
        updateCharCount();
        
        // Limit to 500 characters
        bioTextarea.addEventListener('keydown', function(e) {
            if (this.value.length >= 500 && e.key !== 'Backspace' && e.key !== 'Delete') {
                e.preventDefault();
            }
        });
    }
});