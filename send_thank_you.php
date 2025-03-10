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
$donorName = $data['donor_name'] ?? null;
$message = $data['message'] ?? '';

if (!$donorName || !$message) {
    echo json_encode(['success' => false, 'message' => 'Donor name and message are required']);
    exit();
}

$userId = $_SESSION['user']['id'];

// Get donor ID from username
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND user_type = 'donor'");
$stmt->bind_param("s", $donorName);
$stmt->execute();
$result = $stmt->get_result();
$donor = $result->fetch_assoc();

if (!$donor) {
    echo json_encode(['success' => false, 'message' => 'Donor not found']);
    exit();
}

$donorId = $donor['id'];

// Create notification for donor
$title = "Thank You Message";
$type = "message";

$stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $donorId, $title, $message, $type);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send thank you message']);
} 