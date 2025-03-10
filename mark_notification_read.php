<?php
session_start();
require_once '../config/database.php';
require_once 'beneficiaries_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'beneficiary') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$notificationId = $data['notification_id'] ?? null;

if (!$notificationId) {
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit();
}

$userId = $_SESSION['user']['id'];

// Mark notification as read
if (markNotificationAsRead($notificationId, $userId)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
} 