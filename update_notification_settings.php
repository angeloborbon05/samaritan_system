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
$userId = $_SESSION['user']['id'];

$emailNotifications = $data['email_notifications'] ?? 0;
$smsNotifications = $data['sms_notifications'] ?? 0;
$feedbackNotifications = $data['feedback_notifications'] ?? 0;

// Update notification settings
if (updateNotificationSettings($userId, $emailNotifications, $smsNotifications, $feedbackNotifications)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notification settings']);
} 