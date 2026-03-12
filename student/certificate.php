<?php
session_start();
include("../config/db.php");

if (!isset($_SESSION["user_id"])) {
    header("Location: ../authentication/login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];
$cert_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get certificate details and verify ownership
$query = "SELECT c.*, t.title, u.first_name, u.last_name, 
                 t.date_submitted, t.status
          FROM certificates_table c
          JOIN thesis_table t ON c.thesis_id = t.thesis_id
          JOIN user_table u ON c.student_id = u.user_id
          WHERE c.certificate_id = ? AND c.student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $cert_id, $user_id);
$stmt->execute();
$cert = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cert) {
    header("Location: projects.php?error=invalid_certificate");
    exit;
}

// Update download count
$updateQuery = "UPDATE certificates_table SET downloaded_count = downloaded_count + 1 WHERE certificate_id = ?";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("i", $cert_id);
$stmt->execute();
$stmt->close();

$pageTitle = "Thesis Certificate";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Theses Archiving System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .certificate-wrapper {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .certificate {
            background: white;
            border: 20px solid #FE4853;
            padding: 40px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .certificate:before {
            content: "";
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            bottom: 10px;
            border: 2px solid #732529;
            pointer-events: none;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            color: #FE4853;
            font-size: 48px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 5px;
        }
        
        .header h2 {
            color: #732529;
            font-size: 24px;
            margin: 10px 0 0;
            font-style: italic;
        }
        
        .content {
            text-align: center;
            margin: 50px 0;
        }
        
        .content p {
            font-size: 18px;
            color: #333;
            line-height: 2;
        }
        
        .student-name {
            font-size: 36px;
            color: #FE4853;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            border-bottom: 2px solid #732529;
            display: inline-block;
            padding-bottom: 10px;
        }
        
        .thesis-title {
            font-size: 24px;
            color: #732529;
            font-style: italic;
            margin: 20px 0;
        }
        
        .date {
            font-size: 18px;
            color: #666;
            margin: 30px 0;
        }
        
        .signature {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-line {
            width: 200px;
            border-top: 2px solid #333;
            margin-top: 40px;
        }
        
        .signature-item {
            text-align: center;
        }
        
        .signature-item p {
            margin: 5px 0;
            color: #666;
        }
        
        .seal {
            position: absolute;
            bottom: 50px;
            right: 50px;
            width: 100px;
            height: 100px;
            border: 3px solid #FE4853;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transform: rotate(-15deg);
        }
        
        .seal p {
            color: #FE4853;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            line-height: 1.4;
        }
        
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #999;
            font-size: 12px;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn-print {
            background: #FE4853;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .btn-print:hover {
            background: #732529;
            transform: translateY(-2px);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #FE4853;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .actions, .back-link {
                display: none;
            }
            .certificate {
                border: 20px solid #FE4853;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-wrapper">
        <div class="certificate">
            <div class="header">
                <h1>Certificate of Approval</h1>
                <h2>Thesis Archiving System</h2>
            </div>
            
            <div class="content">
                <p>This is to certify that</p>
                <div class="student-name"><?= htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']) ?></div>
                <p>has successfully completed and defended the thesis entitled</p>
                <div class="thesis-title">"<?= htmlspecialchars($cert['title']) ?>"</div>
                <p>on this day, <strong><?= date('F d, Y', strtotime($cert['generated_date'])) ?></strong></p>
                <p>and is hereby granted the approval for thesis submission.</p>
            </div>
            
            <div class="signature">
                <div class="signature-item">
                    <div class="signature-line"></div>
                    <p><strong>Thesis Adviser</strong></p>
                    <p>Faculty, Graduate School</p>
                </div>
                <div class="signature-item">
                    <div class="signature-line"></div>
                    <p><strong>Dean</strong></p>
                    <p>Graduate School</p>
                </div>
            </div>
            
            <div class="seal">
                <p>OFFICIAL<br>SEAL</p>
            </div>
            
            <div class="footer">
                <p>This certificate is automatically generated by Theses Archiving System</p>
                <p>Certificate ID: CERT-<?= str_pad($cert['certificate_id'], 6, '0', STR_PAD_LEFT) ?></p>
                <p>Downloaded: <?= $cert['downloaded_count'] ?> times</p>
            </div>
        </div>
        
        <div class="actions">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Print Certificate
            </button>
        </div>
        
        <div style="text-align: center;">
            <a href="projects.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Projects
            </a>
        </div>
    </div>
</body>
</html>