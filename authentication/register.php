<?php
session_start();
include("../config/db.php");

$message = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $role_id     = (int)($_POST["role_id"] ?? 2);

    $first_name  = trim($_POST["first_name"] ?? "");
    $last_name   = trim($_POST["last_name"] ?? "");
    $email       = trim($_POST["email"] ?? "");
    $username    = trim($_POST["username"] ?? "");
    $password    = $_POST["password"] ?? "";
    $cpassword   = $_POST["cpassword"] ?? "";
    $department  = trim($_POST["department"] ?? "");
    $birth_date  = trim($_POST["birth_date"] ?? "");
    $address     = trim($_POST["address"] ?? "");

    $contact_number = trim($_POST["contact_number"] ?? "");

    $status      = "1";

    if ($first_name === "" || $last_name === "" || $email === "" || $username === "" || $password === "" || $cpassword === "" || $contact_number === "") {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif ($password !== $cpassword) {
        $message = "Password and Confirm Password do not match.";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
    } elseif (!ctype_digit($contact_number) || strlen($contact_number) < 10) {
        $message = "Contact number must be numeric and valid.";
    } elseif (!in_array($role_id, [1,2,3,4], true)) {
        $message = "Invalid role selected.";
    } else {

        $check = $conn->prepare("SELECT user_id FROM user_table WHERE username = ? OR email = ? LIMIT 1");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $exists = $check->get_result();

        if ($exists && $exists->num_rows > 0) {
            $message = "Username or Email already exists.";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $profile_picture = "default.png";

            $stmt = $conn->prepare("
                INSERT INTO user_table
                (role_id, first_name, last_name, email, username, password, department, birth_date, address, contact_number, status, profile_picture)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "isssssssssss",
                $role_id,
                $first_name,
                $last_name,
                $email,
                $username,
                $hashed,
                $department,
                $birth_date,
                $address,
                $contact_number,
                $status,
                $profile_picture
            );

            if ($stmt->execute()) {
                $success = "Registered successfully! You can now login.";
            } else {
                $message = "Register failed: " . $conn->error;
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Thesis Archiving</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}

    :root{
      --bg1:#071a33;
      --bg2:#050e1e;

      --card1:#0a1f3a;
      --card2:#071a33;

      --stroke: rgba(255,255,255,.10);
      --stroke2: rgba(255,255,255,.14);

      --text:#eaf2ff;
      --muted: rgba(234,242,255,.70);

      --input-bg: rgba(0,0,0,.18);
      --input-bg2: rgba(0,0,0,.26);

      --yellow1:#ffcc33;
      --yellow2:#f6b51a;

      --shadow: 0 22px 60px rgba(0,0,0,.55);
    }

    body{
      min-height:100vh;
      color:var(--text);
      background:
        radial-gradient(1100px 700px at 20% 10%, rgba(255,255,255,.06), transparent 55%),
        radial-gradient(900px 700px at 80% 35%, rgba(255,190,50,.08), transparent 55%),
        linear-gradient(180deg,var(--bg1),var(--bg2));
      display:flex;
      justify-content:center;
      align-items:center;
      padding:24px 14px;
    }

    .auth-wrap{width:100%;max-width:520px;}

    .card{
      background:
        linear-gradient(180deg, rgba(255,255,255,.05), transparent 55%),
        linear-gradient(180deg, var(--card1), var(--card2));
      border:1px solid var(--stroke);
      border-radius:22px;
      padding:22px 20px 18px;
      box-shadow:var(--shadow);
      text-align:left;
    }

    .top-icon{
      width:58px;height:58px;
      margin:0 auto 16px;
      display:grid;place-items:center;
      border-radius:16px;
      background:rgba(255,190,50,.14);
      border:1px solid rgba(255,190,50,.22);
      color:var(--yellow1);
    }

    h1{
      text-align:left;
      font-size:32px;
      font-weight:800;
      margin:2px 0 6px;
    }

    .sub{
      text-align:left;
      color:var(--muted);
      font-size:14px;
      margin-bottom:18px;
    }

    .alert,.success{
      padding:11px 12px;
      border-radius:14px;
      margin:12px 0 14px;
      border:1px solid var(--stroke2);
      font-size:14px;
    }
    .alert{background:rgba(255,80,80,.12);border-color:rgba(255,80,80,.22);color:#ffd5d5}
    .success{background:rgba(65,211,138,.12);border-color:rgba(65,211,138,.22);color:#d9ffe9}

    .form{display:block}

    .lbl{
      display:block;
      color:rgba(234,242,255,.80);
      font-size:13px;
      margin:12px 0 8px;
    }

    .input,.select,.textarea{
      width:100%;
      border-radius:14px;
      border:1px solid var(--stroke);
      background:linear-gradient(180deg,var(--input-bg),var(--input-bg2));
      color:var(--text);
      padding:14px 14px;
      outline:none;
      transition:.15s ease;
    }

    .input::placeholder,.textarea::placeholder{color:rgba(234,242,255,.35)}

    .input:focus,.select:focus,.textarea:focus{
      border-color:rgba(255,190,50,.45);
      box-shadow:0 0 0 4px rgba(255,190,50,.12);
    }

    .select{
      appearance:none;
      padding-right:46px;
      background-image:
        linear-gradient(45deg, transparent 50%, rgba(234,242,255,.75) 50%),
        linear-gradient(135deg, rgba(234,242,255,.75) 50%, transparent 50%);
      background-position: calc(100% - 18px) 50%, calc(100% - 12px) 50%;
      background-size:6px 6px, 6px 6px;
      background-repeat:no-repeat;
    }
    .select option{background:#0b1f3a;color:var(--text)}

    .textarea{min-height:92px;resize:none}

    .grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:14px;
    }
    @media (max-width:380px){
      .grid{grid-template-columns:1fr}
    }

    .btn{
      margin-top:18px;
      width:100%;
      border:none;
      border-radius:16px;
      padding:16px 14px;
      font-weight:800;
      letter-spacing:.3px;
      cursor:pointer;
      color:#141414;
      background:linear-gradient(180deg,var(--yellow1),var(--yellow2));
      box-shadow:0 16px 34px rgba(246,181,26,.22);
      transition:transform .08s ease, filter .15s ease;
    }
    .btn:hover{filter:brightness(1.03)}
    .btn:active{transform:translateY(1px)}

    .or{
      text-align:center;
      margin:14px 0 8px;
      color:rgba(234,242,255,.45);
      font-size:12px;
      letter-spacing:1px;
    }
    .foot{
      text-align:center;
      color:rgba(234,242,255,.70);
      font-size:14px;
      padding-bottom:6px;
    }
    .link{
      color:var(--yellow1);
      text-decoration:none;
      font-weight:800;
    }
    .link:hover{text-decoration:underline}
  </style>
</head>
<body>

<div class="auth-wrap">
  <div class="card">
    <div class="top-icon" aria-hidden="true">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none">
        <path d="M15 19a6 6 0 0 0-12 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/>
        <path d="M19 8v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M22 11h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    </div>

    <h1>Create Account</h1>
    <p class="sub"></p>

    <?php if ($message): ?>
      <div class="alert"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="form" autocomplete="off">

      <label class="lbl">Role *</label>

      <div class="select-wrap">
        <select class="select" name="role_id" required>
          <option value="" disabled selected>Select role</option>
          <option value="1">Admin</option>
          <option value="2">Student</option>
          <option value="3">Faculty</option>
          <option value="4">Dean</option>
        </select>
      </div>

      <div class="grid">
        <div>
          <label class="lbl">First Name *</label>
          <input class="input" type="text" name="first_name" placeholder="Enter first name" required>
        </div>
        <div>
          <label class="lbl">Last Name *</label>
          <input class="input" type="text" name="last_name" placeholder="Enter last name" required>
        </div>
      </div>

      <label class="lbl">Email *</label>
      <input class="input" type="email" name="email" placeholder="Enter email" required>

      <label class="lbl">Username *</label>
      <input class="input" type="text" name="username" placeholder="Enter username" required>

      <div class="grid">
        <div>
          <label class="lbl">Password *</label>
          <input class="input" type="password" name="password" placeholder="Enter password" required>
        </div>
        <div>
          <label class="lbl">Confirm Password *</label>
          <input class="input" type="password" name="cpassword" placeholder="Confirm password" required>
        </div>
      </div>

      <label class="lbl">Department</label>
      <input class="input" type="text" name="department" placeholder="">

      <div class="grid">
        <div>
          <label class="lbl">Birth Date</label>
          <input class="input" type="date" name="birth_date">
        </div>
        <div>
          <label class="lbl">Contact Number *</label>
          <input class="input" type="text" name="contact_number" placeholder="09xxxxxxxxx" required>
        </div>
      </div>

      <label class="lbl">Address</label>
      <textarea class="textarea" name="address" placeholder="Enter address"></textarea>

      <button class="btn" type="submit">Register</button>

      <div class="or">OR</div>
      <div class="foot">
        Already have an account? <a class="link" href="login.php">Login</a>
      </div>
    </form>
  </div>
</div>

</body>
</html>