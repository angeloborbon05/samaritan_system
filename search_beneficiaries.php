<?php
session_start();
require_once '../config/database.php';
require_once 'donors_functions.php';

// Check if user is logged in and is a donor
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'donor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$searchTerm = $_GET['search'] ?? '';

try {
    $results = searchBeneficiaries($searchTerm);
    echo json_encode(['success' => true, 'results' => $results]);
} catch (Exception $e) {
    error_log("Error in search_beneficiaries.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error searching beneficiaries']);
} 