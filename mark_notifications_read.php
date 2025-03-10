<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user']['id'];

try {
    // Mark all notifications as read
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Error marking notifications as read: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error updating notifications']);
} 