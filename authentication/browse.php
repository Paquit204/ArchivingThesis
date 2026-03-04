<?php
session_start();
include("../config/db.php");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$is_logged_in = isset($_SESSION['user_id']);

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8; // 8 thesis per page
$offset = ($page - 1) * $limit;

// Search and filter variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$year = isset($_GET['year']) ? trim($_GET['year']) : '';

// Build the query
$sql = "SELECT t.*, u.first_name, u.last_name 
        FROM thesis_table t
        JOIN user_table u ON t.student_id = u.user_id
        WHERE t.status IN ('approved', 'archived')"; // Only show approved or archived thesis

$countSql = "SELECT COUNT(*) as total 
             FROM thesis_table t
             JOIN user_table u ON t.student_id = u.user_id
             WHERE t.status IN ('approved', 'archived')";

$params = [];
$types = "";

// Add search condition
if (!empty($search)) {
    $sql .= " AND (t.title LIKE ? OR t.abstract LIKE ? OR t.keywords LIKE ?)";
    $countSql .= " AND (t.title LIKE ? OR t.abstract LIKE ? OR t.keywords LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Add year filter
if (!empty($year)) {
    $sql .= " AND YEAR(t.date_submitted) = ?";
    $countSql .= " AND YEAR(t.date_submitted) = ?";
    $params[] = $year;
    $types .= "s";
}

// Add pagination
$sql .= " ORDER BY t.date_submitted DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Get total count for pagination
$stmt = $conn->prepare($countSql);
if (!empty($params)) {
    // Remove limit and offset from params for count query
    $countParams = array_slice($params, 0, -2);
    $countTypes = substr($types, 0, -2);
    if (!empty($countParams)) {
        $stmt->bind_param($countTypes, ...$countParams);
    }
}
$stmt->execute();
$totalResult = $stmt->get_result()->fetch_assoc();
$totalTheses = $totalResult['total'];
$totalPages = ceil($totalTheses / $limit);
$stmt->close();

// Get thesis for current page
$theses = [];
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $theses[] = $row;
}
$stmt->close();

// Get unique years for filter dropdown
$years = [];
$yearQuery = "SELECT DISTINCT YEAR(date_submitted) as year FROM thesis_table ORDER BY year DESC";
$yearResult = $conn->query($yearQuery);
if ($yearResult) {
    while ($row = $yearResult->fetch_assoc()) {
        $years[] = $row['year'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Thesis - Thesis Archiving System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Your existing CSS here - unchanged */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #9b59b6;
            --light-bg: #ecf0f1;
            --dark-text: #2c3e50;
            --light-text: #7f8c8d;
            --white: #ffffff;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark-text);
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            background: var(--white);
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.2rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--dark-text);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--secondary-color);
            background: var(--light-bg);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Browse Header */
        .browse-header {
            text-align: center;
            background: var(--white);
            padding: 3rem 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .browse-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .browse-header p {
            color: var(--light-text);
            font-size: 1.1rem;
        }

        /* Search Section */
        .search-section {
            background: var(--white);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .search-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-input {
            flex: 1;
            padding: 14px 20px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .search-btn {
            padding: 14px 30px;
            background: linear-gradient(135deg, var(--secondary-color), #2980b9);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: var(--shadow);
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .filter-bar {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--white);
            min-width: 200px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--secondary-color);
        }

        .clear-btn {
            padding: 12px 24px;
            background: var(--white);
            color: var(--secondary-color);
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .clear-btn:hover {
            background: var(--secondary-color);
            color: var(--white);
        }

        /* Results Info */
        .results-info {
            background: var(--white);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-info p {
            color: var(--dark-text);
            font-size: 1rem;
        }

        .results-info strong {
            color: var(--secondary-color);
        }

        /* Thesis Grid */
        .thesis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .thesis-card {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .thesis-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .thesis-header {
            margin-bottom: 1rem;
        }

        .thesis-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            line-height: 1.4;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .thesis-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
            color: var(--light-text);
            font-size: 0.9rem;
        }

        .thesis-abstract {
            color: var(--dark-text);
            line-height: 1.7;
            margin-bottom: 1rem;
            text-align: justify;
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }

        .thesis-abstract::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: linear-gradient(transparent, white);
        }

        .thesis-keywords {
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--light-bg);
        }

        .thesis-keywords strong {
            color: var(--light-text);
            font-size: 0.9rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .keyword {
            display: inline-block;
            padding: 0.3rem 0.7rem;
            background: var(--light-bg);
            color: var(--dark-text);
            border-radius: 15px;
            font-size: 0.85rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .thesis-actions {
            display: flex;
            gap: 1rem;
            margin-top: auto;
        }

        .btn-view,
        .btn-download {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-view {
            background: var(--secondary-color);
            color: var(--white);
        }

        .btn-view:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        .btn-download {
            background: var(--light-bg);
            color: var(--dark-text);
        }

        .btn-download:hover {
            background: var(--success-color);
            color: var(--white);
            transform: translateY(-1px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .page-btn {
            padding: 10px 16px;
            background: var(--white);
            color: var(--dark-text);
            border: 2px solid #ddd;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .page-btn:hover:not(:disabled) {
            background: var(--secondary-color);
            color: var(--white);
            border-color: var(--secondary-color);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .page-active {
            background: var(--secondary-color);
            color: var(--white);
            border-color: var(--secondary-color);
        }

        /* No Results */
        .no-results {
            background: var(--white);
            padding: 4rem;
            border-radius: 12px;
            text-align: center;
            grid-column: 1 / -1;
        }

        .no-results i {
            font-size: 4rem;
            color: var(--light-text);
            margin-bottom: 1rem;
        }

        .no-results h3 {
            color: var(--dark-text);
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
        }

        .no-results p {
            color: var(--light-text);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
                width: 100%;
            }

            .browse-header h1 {
                font-size: 2rem;
            }

            .search-section {
                padding: 1.5rem;
            }

            .search-bar {
                flex-direction: column;
            }

            .filter-bar {
                flex-direction: column;
            }

            .filter-select {
                width: 100%;
            }

            .results-info {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .thesis-grid {
                grid-template-columns: 1fr;
            }

            .thesis-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .thesis-actions {
                flex-direction: column;
            }

            .pagination {
                gap: 0.25rem;
            }

            .page-btn {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 1rem;
            }

            .browse-header {
                padding: 2rem 1rem;
            }

            .browse-header h1 {
                font-size: 1.5rem;
            }

            .thesis-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../homepage.php" class="logo">
                <div class="logo-icon">📚</div>
                <span>Thesis Archive</span>
            </a>
            <ul class="nav-links">
                <li><a href="../authentication/homepage.php">Home</a></li>
                <li><a href="browse.php" class="active">Browse Thesis</a></li>
                <li><a href="../about.php">About</a></li>
                <?php if ($is_logged_in): ?>
                    <?php
                    // Get user role to determine dashboard link
                    $roleQuery = "SELECT role_id FROM user_table WHERE user_id = ? LIMIT 1";
                    $stmt = $conn->prepare($roleQuery);
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $userRole = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    $dashboardLink = '#';
                    if ($userRole) {
                        if ($userRole['role_id'] == 3) { // Faculty
                            $dashboardLink = '../faculty/facultyDashboard.php';
                        } elseif ($userRole['role_id'] == 2) { // Student
                            $dashboardLink = '../student/student_dashboard.php';
                        } elseif ($userRole['role_id'] == 1) { // Admin
                            $dashboardLink = '../admin/admin_dashboard.php';
                        }
                    }
                    ?>
                    <li><a href="<?= $dashboardLink ?>">Dashboard</a></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="browse-header">
            <h1>Browse Archived Thesis</h1>
            <p>Explore our collection of approved and archived academic thesis</p>
        </div>

        <!-- Search and Filter Section -->
        <form method="GET" action="browse.php" class="search-section">
            <div class="search-bar">
                <input type="text" class="search-input" name="search" placeholder="Search by title, keywords, or abstract..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
            </div>
            
            <div class="filter-bar">
                <select name="year" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <?php if (!empty($search) || !empty($year)): ?>
                    <a href="browse.php" class="clear-btn"><i class="fas fa-times"></i> Clear Filters</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Results Info -->
        <div class="results-info">
            <p>Found <strong><?= $totalTheses ?></strong> thesis</p>
            <?php if ($totalPages > 1): ?>
                <p>Page <strong><?= $page ?></strong> of <strong><?= $totalPages ?></strong></p>
            <?php endif; ?>
        </div>

        <!-- Thesis Cards Grid -->
        <div class="thesis-grid">
            <?php if (empty($theses)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No thesis found</h3>
                    <p>Try adjusting your search or filter criteria</p>
                </div>
            <?php else: ?>
                <?php foreach ($theses as $thesis): ?>
                    <div class="thesis-card">
                        <div class="thesis-header">
                            <h3 class="thesis-title"><?= htmlspecialchars($thesis['title']) ?></h3>
                        </div>
                        
                        <div class="thesis-meta">
                            <span class="author"><i class="fas fa-user"></i> <?= htmlspecialchars($thesis['first_name'] . ' ' . $thesis['last_name']) ?></span>
                            <span class="date"><i class="fas fa-calendar"></i> <?= date('F Y', strtotime($thesis['date_submitted'])) ?></span>
                        </div>
                        
                        <p class="thesis-abstract">
                            <?= htmlspecialchars(substr($thesis['abstract'], 0, 200)) ?>...
                        </p>
                        
                        <?php if (!empty($thesis['keywords'])): ?>
                            <div class="thesis-keywords">
                                <strong>Keywords:</strong>
                                <?php 
                                $keywords = explode(',', $thesis['keywords']);
                                foreach ($keywords as $kw): 
                                    $kw = trim($kw);
                                    if (!empty($kw)):
                                ?>
                                    <span class="keyword"><?= htmlspecialchars($kw) ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="thesis-actions">
                            <a href="view-thesis.php?id=<?= $thesis['thesis_id'] ?>" class="btn-view"><i class="fas fa-eye"></i> View Details</a>
                            <a href="../<?= htmlspecialchars($thesis['file_path']) ?>" class="btn-download" download><i class="fas fa-download"></i> Download PDF</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&year=<?= urlencode($year) ?>" class="page-btn"><i class="fas fa-chevron-left"></i> Previous</a>
                <?php else: ?>
                    <button class="page-btn" disabled><i class="fas fa-chevron-left"></i> Previous</button>
                <?php endif; ?>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&year=<?= urlencode($year) ?>" class="page-btn <?= $i == $page ? 'page-active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&year=<?= urlencode($year) ?>" class="page-btn">Next <i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                    <button class="page-btn" disabled>Next <i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>