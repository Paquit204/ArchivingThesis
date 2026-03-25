// Edit Profile Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar elements
    const hamburger = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Profile elements
    const profileWrapper = document.getElementById('profileWrapper');
    const profileDropdown = document.getElementById('profileDropdown');
    
    // Theme toggle
    const darkmodeToggle = document.getElementById('darkmode');
    
    // Avatar upload
    const changeAvatarBtn = document.getElementById('changeAvatarBtn');
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    
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
    
    // Dark mode toggle
    if (darkmodeToggle) {
        // Load saved preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
            darkmodeToggle.checked = true;
        }
        
        darkmodeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'true');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'false');
            }
        });
    }
    
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
                    avatarPreview.style.background = `url(${event.target.result})`;
                    avatarPreview.style.backgroundSize = 'cover';
                    avatarPreview.style.backgroundPosition = 'center';
                    avatarPreview.textContent = '';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Notification click
    const notificationIcon = document.querySelector('.notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            window.location.href = 'notifications.php';
        });
    }
    
    // Character counter for bio
    const bioTextarea = document.querySelector('textarea[name="bio"]');
    if (bioTextarea) {
        const charCount = document.createElement('small');
        charCount.className = 'help-text';
        charCount.style.marginTop = '5px';
        charCount.style.display = 'block';
        charCount.textContent = `${bioTextarea.value.length} / 500 characters`;
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
        
        // Limit to 500 characters
        bioTextarea.addEventListener('keydown', function(e) {
            if (this.value.length >= 500 && e.key !== 'Backspace' && e.key !== 'Delete') {
                e.preventDefault();
            }
        });
    }
    
    // Form validation
    const editForm = document.querySelector('form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            const firstName = this.querySelector('input[name="first_name"]').value.trim();
            const lastName = this.querySelector('input[name="last_name"]').value.trim();
            const email = this.querySelector('input[name="email"]').value.trim();
            const username = this.querySelector('input[name="username"]').value.trim();
            
            if (!firstName || !lastName || !email || !username) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
            
            const emailRegex = /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long');
                return false;
            }
        });
    }
});