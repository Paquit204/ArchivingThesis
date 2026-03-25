// Librarian Profile Page JavaScript

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
    
    // Notification click
    const notificationIcon = document.querySelector('.notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            window.location.href = 'notifications.php';
        });
    }
});