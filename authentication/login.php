<?php
session_start();
include("../config/db.php");

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $message = "Username and password are required.";
        $message_type = "error";
    } else {

        $stmt = $conn->prepare("
            SELECT user_id, role_id, first_name, last_name, username, password, status
            FROM user_table
            WHERE username = ? OR email = ?
            LIMIT 1
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {

            $status = (string)($row['status'] ?? 'Pending');
            if ($status !== "Active") {
                $message = "Your account is inactive/pending. Contact admin.";
                $message_type = "error";
            } else {

                if (password_verify($password, $row['password'])) {

                    $_SESSION['user_id'] = (int)$row['user_id'];
                    $_SESSION['role_id'] = (int)$row['role_id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['first_name'] = $row['first_name'];
                    $_SESSION['last_name']  = $row['last_name'];
                    $_SESSION['login_time'] = date('Y-m-d H:i:s');
                    
                    // Set role in session for easy access
                    if ($row['role_id'] == 1) $_SESSION['role'] = 'admin';
                    elseif ($row['role_id'] == 2) $_SESSION['role'] = 'student';
                    elseif ($row['role_id'] == 3) $_SESSION['role'] = 'faculty';
                    elseif ($row['role_id'] == 4) $_SESSION['role'] = 'dean';

                    $message = "✓ Login successful! Redirecting...";
                    $message_type = "success";

                    if ((int)$row['role_id'] === 1) {
                        $redirect = "/ArchivingThesis/admin/admindashboard.php";
                    } elseif ((int)$row['role_id'] === 2) {
                        $redirect = "/ArchivingThesis/student/student_dashboard.php"; 
                    } elseif ((int)$row['role_id'] === 3) {
                        $redirect = "/ArchivingThesis/faculty/facultyDashboard.php"; 
                    } elseif ((int)$row['role_id'] === 4) {
                        $redirect = "/ArchivingThesis/dean/deanDashboard.php";
                    }

                    header("Location: $redirect");
                    exit;

                } else {
                    $message = "Invalid username/email or password.";
                    $message_type = "error";
                }
            }

        } else {
            $message = "Invalid username/email or password.";
            $message_type = "error";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Thesis Archiving System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        :root {
            --navy: #0f172a;
            --navy-dark: #020617;
            --gold: #fbbf24;
            --gold-dark: #d97706;
            --blue: #3b82f6;
            --blue-dark: #2563eb;
            --gray: #64748b;
            --light-gray: #94a3b8;
            --light: #f8fafc;
            --white: #ffffff;
            --card-bg: rgba(30, 41, 59, 0.92);
            --shadow: 0 10px 30px rgba(0,0,0,0.25);
            --radius: 16px;
            --transition: all 0.25s ease;
        }

        * { 
            margin:0; 
            padding:0; 
            box-sizing:border-box; 
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--navy) 0%, #1e293b 100%);
            color: var(--light-gray);
            line-height: 1.6;
            min-height: 100vh;
        }

        .navbar {
            background: var(--navy);
            padding: 1.2rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            color: var(--gold);
            font-size: 1.9rem;
            font-weight: 700;
            text-decoration: none;
        }

        .logo .material-symbols-outlined {
            font-size: 2.3rem;
        }

        .nav-links {
            display: flex;
            gap: 2.2rem;
            list-style: none;
        }

        .nav-links a {
            color: var(--light-gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: var(--gold);
        }

        .container {
            max-width: 460px;
            margin: 4rem auto 6rem;
            padding: 0 1.5rem;
        }

        .login-container {
            background: var(--card-bg);
            padding: 2.8rem 2.4rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(251,191,36,0.08);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.2rem;
        }

        .login-icon {
            font-size: 3.8rem;
            color: var(--gold);
            margin-bottom: 1.1rem;
        }

        .login-header h2 {
            color: var(--white);
            font-size: 1.95rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: var(--light-gray);
            font-size: 1.05rem;
        }

        .message {
            padding: 1rem 1.3rem;
            border-radius: 10px;
            margin-bottom: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            font-weight: 500;
        }

        .message.success {
            background: rgba(16,185,129,0.18);
            color: #10b981;
            border: 1px solid rgba(16,185,129,0.3);
        }

        .message.error {
            background: rgba(239,68,68,0.18);
            color: #ef4444;
            border: 1px solid rgba(239,68,68,0.3);
        }

        .form-group {
            margin-bottom: 1.7rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.7rem;
            color: #cbd5e1;
            font-weight: 500;
            font-size: 0.98rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 1.05rem 1.1rem 1.05rem 3rem;
            border: 1px solid #475569;
            border-radius: 10px;
            background: #1e293b;
            color: white;
            font-size: 1.02rem;
            transition: var(--transition);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(251,191,36,0.15);
        }

        .input-wrapper .material-symbols-outlined.input-icon {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-gray);
            font-size: 1.4rem;
        }

        .password-toggle {
            position: absolute;
            right: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--light-gray);
            transition: var(--transition);
        }

        .password-toggle:hover { 
            color: var(--gold); 
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 1.4rem 0 1.8rem;
            font-size: 0.96rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            color: var(--light-gray);
        }

        .forgot-link {
            color: var(--blue);
            text-decoration: none;
            transition: var(--transition);
        }

        .forgot-link:hover {
            color: var(--gold);
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 1.15rem;
            background: var(--gold);
            color: var(--navy);
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 14px rgba(251,191,36,0.25);
        }

        .btn-login:hover {
            background: var(--gold-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(251,191,36,0.35);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            color: var(--light-gray);
            margin: 2rem 0 1.6rem;
            font-size: 0.95rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #475569;
        }

        .role-selection {
            margin-bottom: 1.5rem;
        }

        .role-title {
            color: var(--light-gray);
            margin-bottom: 1rem;
            font-size: 0.95rem;
            text-align: center;
        }

        .role-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .role-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem;
            background: #1e293b;
            border: 1px solid #475569;
            border-radius: 8px;
            color: var(--light-gray);
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.95rem;
        }

        .role-btn:hover {
            border-color: var(--gold);
            color: var(--gold);
            transform: translateY(-2px);
        }

        .role-icon {
            font-size: 1.2rem;
        }

        .register-link {
            text-align: center;
            margin-top: 1.8rem;
            font-size: 1.02rem;
            color: var(--light-gray);
        }

        .register-link a {
            color: var(--gold);
            font-weight: 500;
            text-decoration: none;
            transition: var(--transition);
        }

        .register-link a:hover {
            color: var(--gold-dark);
            text-decoration: underline;
        }

        .footer {
            background: var(--navy-dark);
            text-align: center;
            padding: 3rem 1rem 4rem;
            color: var(--light-gray);
            font-size: 0.95rem;
        }

        @media (max-width: 480px) {
            .container { 
                margin: 3rem auto 5rem; 
                padding: 0 1rem; 
            }
            .login-container { 
                padding: 2.2rem 1.8rem; 
            }
            .login-header h2 { 
                font-size: 1.75rem; 
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="homepage.php" class="logo">
                <span class="material-symbols-outlined">book</span>
                   Web-Based Thesis Archiving System
            </a>
            <ul class="nav-links">
                <li><a href="homepage.php">Home</a></li>
                <li><a href="browse.php">Browse Thesis</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="login.php" class="active">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <span class="material-symbols-outlined login-icon">lock</span>
                <h2>Login to Your Account</h2>
                <p>Enter your credentials to access the system</p>
            </div>

            <?php if (!empty($message)) : ?>
                <div class="message <?php echo $message_type; ?>">
                    <span class="message-icon">
                        <?php echo ($message_type === 'success') ? '✓' : '✕'; ?>
                    </span>
                    <span class="message-text"><?php echo htmlspecialchars($message); ?></span>
                </div>
            <?php endif; ?>

            <form class="login-form" id="loginForm" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon">person</span>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon">lock</span>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <span class="material-symbols-outlined password-toggle" id="login-toggle">visibility_off</span>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login">Login</button>

                <div class="divider">
                    <span>OR</span>
                </div>

                <div class="role-selection">
                    <p class="role-title">Quick Login As:</p>
                    <div class="role-buttons">
                        <button type="button" class="role-btn" onclick="quickLogin('student')">
                            <span class="material-symbols-outlined role-icon">school</span>
                            <span>Student</span>
                        </button>
                        <button type="button" class="role-btn" onclick="quickLogin('faculty')">
                            <span class="material-symbols-outlined role-icon">person</span>
                            <span>Faculty</span>
                        </button>
                        <button type="button" class="role-btn" onclick="quickLogin('dean')">
                            <span class="material-symbols-outlined role-icon">account_balance</span>
                            <span>Dean</span>
                        </button>
                        <button type="button" class="role-btn" onclick="quickLogin('admin')">
                            <span class="material-symbols-outlined role-icon">settings</span>
                            <span>Admin</span>
                        </button>
                    </div>
                </div>

                <div class="register-link">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        function quickLogin(role) {
            
            switch(role) {
                case 'student':
                    window.location.href = '/ArchivingThesis/student/student_dashboard.php';
                    break;
                case 'faculty':
                    window.location.href = '/ArchivingThesis/faculty/facultyDashboard.php';
                    break;
                case 'dean':
                    window.location.href = '/ArchivingThesis/dean/deanDashboard.php';
                    break;
                case 'admin':
                    window.location.href = '/ArchivingThesis/admin/admindashboard.php';
                    break;
            }
        }

        const loginToggle = document.getElementById('login-toggle');
        const loginPass = document.getElementById('password');

        if (loginToggle && loginPass) {
            loginToggle.addEventListener('click', () => {
                if (loginPass.type === 'password') {
                    loginPass.type = 'text';
                    loginToggle.textContent = 'visibility';
                } else {
                    loginPass.type = 'password';
                    loginToggle.textContent = 'visibility_off';
                }
            });
        }
    </script>
</body>
</html>