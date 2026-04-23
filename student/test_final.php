<?php
// student/test_final.php
require_once __DIR__ . '/../config/smtp_config.php';

echo "<h2>Final Test - Defense 2024</h2>";

$test = new EmailSender();
$result = $test->sendThesisSubmission(
    'mylenesellar13@gmail.com',  // Ang email nga gi-invite
    'Raganas Student',
    'Defense 2024 Thesis Submission',
    '999',
    'BSIT'
);

if ($result) {
    echo "<p style='color: green; font-size: 20px;'>✅ EMAIL WORKING NA!</p>";
    echo "<p>Check mylenesellar13@gmail.com - tan-awa ang SPAM folder kung wala sa Inbox.</p>";
} else {
    echo "<p style='color: red; font-size: 20px;'>❌ Still not working.</p>";
}
?>