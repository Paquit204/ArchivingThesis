<?php
session_start();
include("../config/db.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is faculty
if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// Verify faculty role
$roleQuery = "SELECT role_id FROM user_table WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($roleQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$userData || $userData['role_id'] != 3) {
    header("Location: ../authentication/login.php?error=invalid_role");
    exit;
}

$faculty_id = $user_id;

// Get faculty info
$stmt = $conn->prepare("SELECT first_name, last_name FROM user_table WHERE user_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$stmt->close();

$first = $faculty['first_name'] ?? '';
$last = $faculty['last_name'] ?? '';
$fullName = trim($first . ' ' . $last);
$initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));

// Handle form submission - Add new feedback
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_feedback'])) {
    $thesis_id = (int)$_POST['thesis_id'];
    $comments = trim($_POST['comments']);
    
    if (!empty($thesis_id) && !empty($comments)) {
        $insertQuery = "INSERT INTO feedback_table (thesis_id, faculty_id, comments) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iis", $thesis_id, $faculty_id, $comments);
        
        if ($stmt->execute()) {
            $success = "Feedback added successfully!";
        } else {
            $error = "Error adding feedback: " . $conn->error;
        }
        $stmt->close();
    }
}

// Handle delete feedback
if (isset($_GET['delete'])) {
    $feedback_id = (int)$_GET['delete'];
    $deleteQuery = "DELETE FROM feedback_table WHERE feedback_id = ? AND faculty_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $feedback_id, $faculty_id);
    
    if ($stmt->execute()) {
        $success = "Feedback deleted successfully!";
    } else {
        $error = "Error deleting feedback";
    }
    $stmt->close();
}

// Get all feedback by this faculty
$feedbackQuery = "SELECT f.*, t.title as thesis_title, u.first_name as student_first, u.last_name as student_last
                  FROM feedback_table f
                  JOIN thesis_table t ON f.thesis_id = t.thesis_id
                  JOIN user_table u ON t.student_id = u.user_id
                  WHERE f.faculty_id = ?
                  ORDER BY f.feedback_date DESC";
$stmt = $conn->prepare($feedbackQuery);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$feedbackList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get pending theses for feedback dropdown
$pendingQuery = "SELECT t.thesis_id, t.title, u.first_name, u.last_name 
                 FROM thesis_table t
                 JOIN user_table u ON t.student_id = u.user_id
                 WHERE t.status = 'pending'";
$pendingResult = $conn->query($pendingQuery);
$pendingTheses = $pendingResult->fetch_all(MYSQLI_ASSOC);

$pageTitle = "My Feedback";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Theses Archiving System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
        }

        /* Sidebar styles (same as faculty dashboard) */
        .sidebar {
            position: fixed;
            top: 0;
            left: -300px;
            width: 280px;
            height: 100vh;
            background: linear-gradient(180deg, #FE4853 0%, #732529 100%);
            color: white;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: left 0.3s ease;
            box-shadow: 5px 0 20px rgba(0,0,0,0.3);
        }

        .sidebar.show { left: 0; }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            color: white;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 0.875rem 1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 0;
            min-height: 100vh;
            padding: 2rem;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .topbar h1 { color: #732529; }

        .user-info { display: flex; align-items: center; gap: 1.5rem; }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FE4853, #732529);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
        }

        /* Feedback Container */
        .feedback-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .btn-add {
            background: #FE4853;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-add:hover {
            background: #732529;
            transform: translateY(-2px);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.show { display: flex; }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6E6E6E;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #732529;
        }

        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .btn-submit {
            background: #FE4853;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
        }

        /* Feedback Cards */
        .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .feedback-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .feedback-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(254,72,83,0.2);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .thesis-info h3 {
            color: #732529;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .student-name {
            color: #6E6E6E;
            font-size: 0.9rem;
        }

        .feedback-date {
            color: #6E6E6E;
            font-size: 0.85rem;
        }

        .feedback-comments {
            color: #333;
            line-height: 1.6;
            margin: 1rem 0;
            padding: 1rem 0;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .feedback-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit, .btn-delete {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-delete {
            background: #fee2e2;
            color: #b91c1c;
        }

        .btn-edit:hover { background: #cbd5e1; }
        .btn-delete:hover { background: #fecaca; }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid #ef4444;
        }

        .no-feedback {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            color: #6E6E6E;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content { padding: 1rem; }
            .header-section { flex-direction: column; gap: 1rem; }
            .feedback-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Theses Archive</h2>
            <p>Faculty Portal</p>
        </div>
        <nav class="sidebar-nav">
            <a href="facultyDashboard.php" class="nav-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="reviewThesis.php" class="nav-link">
                <i class="fas fa-book-reader"></i> Review Theses
            </a>
            <a href="facultyFeedback.php" class="nav-link active">
                <i class="fas fa-comment-dots"></i> My Feedback
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="../authentication/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>My Feedback</h1>
            <div class="user-info">
                <div class="avatar"><?= $initials ?></div>
            </div>
        </header>

        <div class="feedback-container">
            <div class="header-section">
                <h2>Feedback History</h2>
                <button class="btn-add" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add New Feedback
                </button>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if (empty($feedbackList)): ?>
                <div class="no-feedback">
                    <i class="fas fa-comment-dots" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h3>No feedback yet</h3>
                    <p>Click "Add New Feedback" to start providing feedback on theses.</p>
                </div>
            <?php else: ?>
                <div class="feedback-grid">
                    <?php foreach ($feedbackList as $feedback): ?>
                        <div class="feedback-card">
                            <div class="feedback-header">
                                <div class="thesis-info">
                                    <h3><?= htmlspecialchars($feedback['thesis_title']) ?></h3>
                                    <div class="student-name">
                                        <i class="fas fa-user"></i> 
                                        <?= htmlspecialchars($feedback['student_first'] . ' ' . $feedback['student_last']) ?>
                                    </div>
                                </div>
                                <div class="feedback-date">
                                    <i class="fas fa-calendar"></i> 
                                    <?= date('M d, Y', strtotime($feedback['feedback_date'])) ?>
                                </div>
                            </div>
                            
                            <div class="feedback-comments">
                                <?= nl2br(htmlspecialchars($feedback['comments'])) ?>
                            </div>
                            
                            <div class="feedback-actions">
                                <button class="btn-edit" onclick="editFeedback(<?= $feedback['feedback_id'] ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete" onclick="deleteFeedback(<?= $feedback['feedback_id'] ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <div class="modal" id="feedbackModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Feedback</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="add_feedback" value="1">
                
                <div class="form-group">
                    <label>Select Thesis</label>
                    <select name="thesis_id" required>
                        <option value="">-- Choose a thesis --</option>
                        <?php foreach ($pendingTheses as $thesis): ?>
                            <option value="<?= $thesis['thesis_id'] ?>">
                                <?= htmlspecialchars($thesis['title'] . ' - ' . $thesis['first_name'] . ' ' . $thesis['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Feedback Comments</label>
                    <textarea name="comments" placeholder="Enter your feedback here..." required></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('feedbackModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('feedbackModal').classList.remove('show');
        }

        function deleteFeedback(id) {
            if (confirm('Are you sure you want to delete this feedback?')) {
                window.location.href = '?delete=' + id;
            }
        }

        function editFeedback(id) {
            alert('Edit feature coming soon!');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>