<?php
// config/smtp_config.php
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->setupSMTP();
    }
    
    private function setupSMTP() {
        try {
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF; // I-set sa DEBUG_SERVER kung gusto makita error
            $this->mail->isSMTP();
            $this->mail->Host       = 'smtp.gmail.com';
            $this->mail->SMTPAuth   = true;
            
            // !!! UPDATED WITH NEW APP PASSWORD !!!
            $this->mail->Username   = 'mylenesellar13@gmail.com';
            $this->mail->Password   = 'nxsr rpkd rvzj tgzi';  // Bag-ong password for Defense 2024
            
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = 587;
            
            $this->mail->setFrom('mylenesellar13@gmail.com', 'Defense 2024 ');
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("SMTP Setup Error: " . $e->getMessage());
        }
    }
    
    public function sendThesisSubmission($to, $studentName, $thesisTitle, $thesisId, $department) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            
            $this->mail->Subject = "New Thesis Submission: " . $thesisTitle;
            $this->mail->Body = $this->getSubmissionHTML($studentName, $thesisTitle, $thesisId, $department);
            $this->mail->AltBody = "New thesis submission: {$thesisTitle} from {$studentName}";
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Email failed to {$to}: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    public function sendInvitation($to, $inviterName, $thesisTitle, $thesisId) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            
            $this->mail->Subject = "Thesis Collaboration Invitation from " . $inviterName;
            $this->mail->Body = $this->getInvitationHTML($inviterName, $thesisTitle, $thesisId);
            $this->mail->AltBody = "{$inviterName} invited you to collaborate on: {$thesisTitle}";
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Invitation failed to {$to}: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    private function getSubmissionHTML($studentName, $thesisTitle, $thesisId, $department) {
        $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/ArchivingThesis";
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FE4853; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { background: #FE4853; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Thesis Archiving System</h2>
                </div>
                <div class='content'>
                    <p>Dear Faculty Member,</p>
                    <p>A new thesis has been submitted:</p>
                    <h3>{$thesisTitle}</h3>
                    <p><strong>Student:</strong> {$studentName}</p>
                    <p><strong>Department:</strong> {$department}</p>
                    <p><strong>Date:</strong> " . date('F d, Y h:i A') . "</p>
                    <p><a href='{$baseUrl}/faculty/reviewThesis.php?id={$thesisId}' class='button'>Review Thesis</a></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getInvitationHTML($inviterName, $thesisTitle, $thesisId) {
        $baseUrl = "http://" . $_SERVER['HTTP_HOST'] . "/ArchivingThesis";
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FE4853; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { background: #FE4853; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Thesis Collaboration Invitation</h2>
                </div>
                <div class='content'>
                    <p><strong>{$inviterName}</strong> invited you to collaborate on:</p>
                    <h3>{$thesisTitle}</h3>
                    <p><a href='{$baseUrl}/student/invitations.php?thesis_id={$thesisId}' class='button'>View Invitation</a></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

// Initialize global email sender
$emailSender = new EmailSender();
?>