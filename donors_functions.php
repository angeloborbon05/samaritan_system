<?php
require_once '../config/database.php';

// Get donor profile
function getDonorProfile($userId) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT u.* FROM users u WHERE u.id = ? AND u.user_type = 'donor'");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getDonorProfile: " . $e->getMessage());
        return false;
    }
}

// Get donor statistics
function getDonorStats($donorId) {
    global $conn;
    try {
        // Get families helped count (unique beneficiaries)
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT p.beneficiary_id) as families_helped,
                               COUNT(*) as total_donations,
                               SUM(hr.amount) as total_amount,
                               (SELECT AVG(rating) FROM ratings WHERE donor_id = ?) as avg_rating,
                               COUNT(DISTINCT CASE WHEN hr.status = 'completed' THEN hr.id END) as completed_donations
                               FROM help_received hr
                               JOIN posts p ON hr.post_id = p.id
                               WHERE hr.donor_id = ?");
        $stmt->bind_param("ii", $donorId, $donorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getDonorStats: " . $e->getMessage());
        return false;
    }
}

// Get recent activities
function getRecentActivities($donorId, $limit = 5) {
    global $conn;
    try {
        $stmt = $conn->prepare("(SELECT 'donation' as type, hr.created_at, hr.status,
                                hr.amount, hr.description, b.full_name as beneficiary_name,
                                p.title as post_title, NULL as rating
                                FROM help_received hr
                                JOIN posts p ON hr.post_id = p.id
                                JOIN beneficiaries b ON p.beneficiary_id = b.id
                                WHERE hr.donor_id = ?)
                               UNION ALL
                               (SELECT 'feedback' as type, r.created_at, 'completed' as status,
                                NULL as amount, r.comment as description, b.full_name as beneficiary_name,
                                NULL as post_title, r.rating
                                FROM ratings r
                                JOIN beneficiaries b ON r.beneficiary_id = b.id
                                WHERE r.donor_id = ?)
                               ORDER BY created_at DESC
                               LIMIT ?");
        $stmt->bind_param("iii", $donorId, $donorId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getRecentActivities: " . $e->getMessage());
        return [];
    }
}

// Get help proofs
function getHelpProofs($donorId, $limit = 6) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT hi.*, hr.description, p.title as post_title
                               FROM help_images hi
                               JOIN help_received hr ON hi.help_id = hr.id
                               JOIN posts p ON hr.post_id = p.id
                               WHERE hr.donor_id = ? AND hr.status = 'completed'
                               ORDER BY hi.created_at DESC
                               LIMIT ?");
        $stmt->bind_param("ii", $donorId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getHelpProofs: " . $e->getMessage());
        return [];
    }
}

// Get ratings and feedback
function getRatingsAndFeedback($donorId) {
    global $conn;
    try {
        // Get overall stats
        $stmt = $conn->prepare("SELECT 
                               COUNT(*) as total_ratings,
                               AVG(rating) as avg_rating,
                               SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                               SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                               SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                               SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                               SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                               FROM ratings
                               WHERE donor_id = ?");
        $stmt->bind_param("i", $donorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getRatingsAndFeedback: " . $e->getMessage());
        return false;
    }
}

// Search beneficiaries
function searchBeneficiaries($searchTerm) {
    global $conn;
    try {
        $searchTerm = "%$searchTerm%";
        $stmt = $conn->prepare("SELECT b.*, u.email, p.title as latest_post_title,
                               p.description as latest_post_description,
                               p.created_at as post_date
                               FROM beneficiaries b
                               JOIN users u ON b.user_id = u.id
                               LEFT JOIN posts p ON b.id = p.beneficiary_id
                               WHERE b.full_name LIKE ? OR b.address LIKE ?
                               ORDER BY p.created_at DESC");
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in searchBeneficiaries: " . $e->getMessage());
        return [];
    }
}

// Get notifications
function getNotifications($userId, $limit = 10) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM notifications 
                               WHERE user_id = ? 
                               ORDER BY created_at DESC 
                               LIMIT ?");
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Error in getNotifications: " . $e->getMessage());
        return [];
    }
}

// Get unread notification count
function getUnreadNotificationCount($userId) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count 
                               FROM notifications 
                               WHERE user_id = ? AND is_read = FALSE");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    } catch (Exception $e) {
        error_log("Error in getUnreadNotificationCount: " . $e->getMessage());
        return 0;
    }
}

// Helper function to format time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return "Just now";
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($difference < 2592000) {
        $days = floor($difference / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date('M j, Y', $timestamp);
    }
}

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
} 