<?php
// test_email.php - Ibutang ni sa student folder
require_once __DIR__ . '/../config/smtp_config.php';

echo "<h2>Testing Email Configuration</h2>";
echo "<p>Using Gmail: raganas13@gmail.com</p>";
echo "<p>App Password: [HIDDEN]</p>";
echo "<hr>";

$email = new EmailSender();

// I-send sa imong kaugalingon nga email
$result = $email->sendThesisSubmission(
    'mylenesellar13@gmail.com',  // Ang email nga imong gi-invite
    'Test Student Raganas',
    'Test Thesis Title - Email Test',
    '999',
    'BSIT'
);

if ($result) {
    echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS! Email sent successfully!</p>";
    echo "<p>Check your Gmail inbox or spam folder.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ FAILED! Could not send email.</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Wrong Gmail username</li>";
    echo "<li>Wrong App Password</li>";
    echo "<li>2-Step Verification not enabled</li>";
    echo "<li>No internet connection</li>";
    echo "</ul>";
}
?>