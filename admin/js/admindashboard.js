// Admin Dashboard JavaScript

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
    
    // Section elements
    const usersSection = document.getElementById('usersSection');
    const logsSection = document.getElementById('logsSection');
    const usersMenuBtn = document.getElementById('usersMenuBtn');
    const logsMenuBtn = document.getElementById('logsMenuBtn');
    
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
    
    // Show Users Section (default)
    if (usersSection) usersSection.style.display = 'block';
    if (logsSection) logsSection.style.display = 'none';
    
    // Users Menu Click
    if (usersMenuBtn) {
        usersMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (usersSection) usersSection.style.display = 'block';
            if (logsSection) logsSection.style.display = 'none';
            
            // Update active state
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    }
    
    // Logs Menu Click
    if (logsMenuBtn) {
        logsMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (usersSection) usersSection.style.display = 'none';
            if (logsSection) logsSection.style.display = 'block';
            
            // Update active state
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Search users table
            const userRows = document.querySelectorAll('.users-table tbody tr');
            userRows.forEach(row => {
                const name = row.cells[1]?.textContent.toLowerCase() || '';
                const email = row.cells[2]?.textContent.toLowerCase() || '';
                const role = row.cells[3]?.textContent.toLowerCase() || '';
                if (name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Search logs table
            const logRows = document.querySelectorAll('.logs-table tbody tr');
            logRows.forEach(row => {
                const user = row.cells[0]?.textContent.toLowerCase() || '';
                const action = row.cells[1]?.textContent.toLowerCase() || '';
                const details = row.cells[4]?.textContent.toLowerCase() || '';
                if (user.includes(searchTerm) || action.includes(searchTerm) || details.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Refresh logs button
    const refreshLogsBtn = document.getElementById('refreshLogsBtn');
    if (refreshLogsBtn) {
        refreshLogsBtn.addEventListener('click', function() {
            location.reload();
        });
    }
    
    // Add User button
    const addUserBtn = document.querySelector('.add-user-btn');
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            alert('Add New User feature coming soon!');
        });
    }
    
    // Edit and Delete buttons
    const editBtns = document.querySelectorAll('.action-btn.edit');
    const deleteBtns = document.querySelectorAll('.action-btn.delete');
    
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const userName = row.cells[1]?.querySelector('span')?.textContent.trim() || 'User';
            alert(`Edit user: ${userName}`);
        });
    });
    
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const userName = row.cells[1]?.querySelector('span')?.textContent.trim() || 'User';
            if (confirm(`Are you sure you want to delete ${userName}?`)) {
                alert(`Delete user: ${userName}`);
            }
        });
    });
    
    // Notification click
    const notificationIcon = document.querySelector('.notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            alert('You have new notifications');
        });
    }
    
    // =============== CHARTS ===============
    // User Distribution Chart
    const userCtx = document.getElementById('userDistributionChart');
    if (userCtx && window.userData) {
        const userStats = window.userData.stats;
        new Chart(userCtx, {
            type: 'doughnut',
            data: {
                labels: ['Students', 'Faculty', 'Deans', 'Librarians', 'Coordinators', 'Admins'],
                datasets: [{
                    data: [userStats.students, userStats.faculty, userStats.deans, userStats.librarians, userStats.coordinators, userStats.admins],
                    backgroundColor: ['#1976d2', '#388e3c', '#f57c00', '#7b1fa2', '#e67e22', '#d32f2f'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 10 }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
    
    // Registration Trend Chart
    const regCtx = document.getElementById('registrationChart');
    if (regCtx) {
        new Chart(regCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'New Users',
                    data: [12, 19, 15, 17, 14, 22, 25, 20, 28, 32, 30, 35],
                    borderColor: '#d32f2f',
                    backgroundColor: 'rgba(211, 47, 47, 0.05)',
                    borderWidth: 3,
                    pointBackgroundColor: '#d32f2f',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return `New Users: ${ctx.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e2e8f0'
                        },
                        title: {
                            display: true,
                            text: 'Number of Users',
                            font: { size: 11 }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Month',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }
});