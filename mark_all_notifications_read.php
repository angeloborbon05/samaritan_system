<?php
session_start();
require_once '../config/database.php';
require_once 'beneficiaries_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'beneficiary') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user']['id'];

// Mark all notifications as read
$stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
} 