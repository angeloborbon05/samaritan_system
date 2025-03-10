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
$helpId = $data['help_id'] ?? null;
$rating = $data['rating'] ?? null;
$comment = $data['comment'] ?? '';

if (!$helpId || !$rating) {
    echo json_encode(['success' => false, 'message' => 'Help ID and rating are required']);
    exit();
}

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit();
}

$userId = $_SESSION['user']['id'];

// Get beneficiary ID
$stmt = $conn->prepare("SELECT beneficiary_id FROM help_received WHERE id = ?");
$stmt->bind_param("i", $helpId);
$stmt->execute();
$result = $stmt->get_result();
$help = $result->fetch_assoc();

if (!$help) {
    echo json_encode(['success' => false, 'message' => 'Help record not found']);
    exit();
}

$beneficiaryId = $help['beneficiary_id'];

// Get donor ID
$stmt = $conn->prepare("SELECT donor_id FROM help_received WHERE id = ?");
$stmt->bind_param("i", $helpId);
$stmt->execute();
$result = $stmt->get_result();
$help = $result->fetch_assoc();
$donorId = $help['donor_id'];

// Submit rating
if (rateDonor($helpId, $beneficiaryId, $donorId, $rating, $comment)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit rating']);
} 