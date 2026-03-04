<?php
session_start();
include("../config/db.php");

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$faculty_id = (int)$_SESSION["user_id"];

// Get the JSON input
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = $input['notification_id'] ?? 0;

if (!$notification_id) {
    echo json_encode(['success' => false, 'error' => 'Notification ID required']);
    exit;
}

try {
    // Mark the specific notification as read
    $updateQuery = "UPDATE notification_table SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $notification_id, $faculty_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Notification not found or already read']);
    }
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Mark read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>