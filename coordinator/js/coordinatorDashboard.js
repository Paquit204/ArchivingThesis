// Coordinator Dashboard JavaScript

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
            updateChartTheme();
        });
    }
    
    // Notification click
    const notificationIcon = document.querySelector('.notification-icon');
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            window.location.href = 'notification.php';
        });
    }
    
    // Search functionality for theses table
    const thesisSearchInput = document.getElementById('thesisSearchInput');
    if (thesisSearchInput) {
        thesisSearchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.theses-table tbody tr');
            
            tableRows.forEach(row => {
                const title = row.cells[0]?.textContent.toLowerCase() || '';
                const author = row.cells[1]?.textContent.toLowerCase() || '';
                
                if (title.includes(searchTerm) || author.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Get chart data from PHP
    const chartData = window.chartData || {
        status: { pending: 0, forwarded: 0, rejected: 0 },
        monthly: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
        months: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    };
    
    let statusChart = null;
    let monthlyChart = null;
    
    function getTextColor() {
        return document.body.classList.contains('dark-mode') ? '#e5e7eb' : '#0f172a';
    }
    
    function getGridColor() {
        return document.body.classList.contains('dark-mode') ? '#334155' : '#e2e8f0';
    }
    
    // Status Chart
    function createStatusChart() {
        const canvas = document.getElementById('statusChart');
        if (!canvas) return;
        if (statusChart) statusChart.destroy();
        
        statusChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['Pending Review', 'Forwarded to Dean', 'Rejected'],
                datasets: [{
                    data: [chartData.status.pending, chartData.status.forwarded, chartData.status.rejected],
                    backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                                return `${ctx.label}: ${ctx.raw} (${pct}%)`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
    
    // Monthly Chart
    function createMonthlyChart() {
        const canvas = document.getElementById('monthlyChart');
        if (!canvas) return;
        if (monthlyChart) monthlyChart.destroy();
        
        monthlyChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: chartData.months,
                datasets: [{
                    label: 'Number of Theses',
                    data: chartData.monthly,
                    borderColor: '#d32f2f',
                    backgroundColor: 'rgba(211, 47, 47, 0.05)',
                    borderWidth: 2,
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
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return `Theses: ${ctx.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: getTextColor(), font: { size: 9 } },
                        grid: { color: getGridColor() }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: getTextColor(), stepSize: 5, font: { size: 9 } },
                        grid: { color: getGridColor() },
                        title: {
                            display: true,
                            text: 'Number of Theses',
                            color: getTextColor(),
                            font: { size: 10 }
                        }
                    }
                }
            }
        });
    }
    
    function updateChartTheme() {
        if (monthlyChart) {
            monthlyChart.options.scales.x.ticks.color = getTextColor();
            monthlyChart.options.scales.x.grid.color = getGridColor();
            monthlyChart.options.scales.y.ticks.color = getTextColor();
            monthlyChart.options.scales.y.grid.color = getGridColor();
            monthlyChart.options.scales.y.title.color = getTextColor();
            monthlyChart.update();
        }
    }
    
    // Initialize charts
    setTimeout(function() {
        createStatusChart();
        createMonthlyChart();
    }, 100);
    
    // Animation for cards
    const cards = document.querySelectorAll('.stat-card, .chart-card, .theses-card, .submissions-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        observer.observe(card);
    });
});