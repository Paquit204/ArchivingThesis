// Department Dean Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar elements
    const hamburger = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Profile elements - FIXED: Dropdown works when clicking name or avatar
    const profileWrapper = document.getElementById('profileWrapper');
    const profileDropdown = document.getElementById('profileDropdown');
    
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
    
    // Profile dropdown toggle - FIXED: Works when clicking on profile-trigger (name or avatar)
    if (profileWrapper && profileDropdown) {
        // Click on profile wrapper (the whole area with name and avatar)
        profileWrapper.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
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
    
    // Handle window resize - close sidebar if open on large screens
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
    
    // Project Status Chart
    const statusCtx = document.getElementById('projectStatusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'In Progress', 'Completed', 'Archived'],
                datasets: [{
                    data: [11, 34, 42, 15],
                    backgroundColor: ['#d32f2f', '#ef9a9a', '#81c784', '#b71c1c'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            color: '#333333'
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    // Workload Chart
    const workloadCtx = document.getElementById('workloadChart');
    if (workloadCtx) {
        new Chart(workloadCtx, {
            type: 'bar',
            data: {
                labels: ['Prof. Dela Cruz', 'Dr. Lopez', 'Prof. Reyes', 'Dr. Garcia', 'Prof. Santiago', 'Dr. Villanueva'],
                datasets: [{
                    label: 'Projects Supervised',
                    data: [8, 6, 4, 5, 7, 3],
                    backgroundColor: '#d32f2f',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        grid: {
                            color: 'rgba(183, 28, 28, 0.05)'
                        },
                        ticks: {
                            color: '#333333'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#333333'
                        }
                    }
                }
            }
        });
    }

    // Search functionality
    const searchInput = document.querySelector('.search-area input');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const searchTerm = this.value.toLowerCase();
            const facultyCards = document.querySelectorAll('.faculty-card');
            const projectRows = document.querySelectorAll('.projects-section tbody tr');
            
            // Search faculty
            facultyCards.forEach(card => {
                const name = card.querySelector('.faculty-name')?.textContent.toLowerCase() || '';
                const spec = card.querySelector('.faculty-spec')?.textContent.toLowerCase() || '';
                
                if (name.includes(searchTerm) || spec.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Search projects
            projectRows.forEach(row => {
                const title = row.cells[0]?.textContent.toLowerCase() || '';
                const student = row.cells[1]?.textContent.toLowerCase() || '';
                const adviser = row.cells[2]?.textContent.toLowerCase() || '';
                
                if (title.includes(searchTerm) || student.includes(searchTerm) || adviser.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Notification click
    const notificationIcon = document.querySelector('.notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            alert('You have 4 new notifications');
        });
    }

    // View all links
    const viewAllLinks = document.querySelectorAll('.view-all');
    viewAllLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.closest('.faculty-section, .projects-section, .defenses-section, .activities-section, .workload-section');
            if (section) {
                const title = section.querySelector('.section-title')?.textContent || 'items';
                alert(`View all ${title}`);
            }
        });
    });

    // Quick action buttons
    const quickActions = document.querySelectorAll('.quick-action-btn');
    quickActions.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const action = this.querySelector('span')?.textContent || 'action';
            alert(`${action} feature coming soon!`);
        });
    });

    // Defense details buttons
    const defenseDetailBtns = document.querySelectorAll('.defense-item .btn-view');
    defenseDetailBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const defenseItem = this.closest('.defense-item');
            const title = defenseItem.querySelector('.defense-title')?.textContent || '';
            alert(`Viewing details for: ${title}`);
        });
    });

    // Project view buttons
    const projectViewBtns = document.querySelectorAll('.projects-section .btn-view');
    projectViewBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const row = this.closest('tr');
            const title = row.cells[0]?.textContent || '';
            alert(`Viewing project: ${title}`);
        });
    });
});