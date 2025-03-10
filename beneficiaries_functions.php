<?php
require_once '../config/database.php';

// Get beneficiary profile
function getBeneficiaryProfile($userId) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT b.*, u.email, u.username, u.is_active 
                               FROM beneficiaries b 
                               JOIN users u ON b.user_id = u.id 
                               WHERE b.user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getBeneficiaryProfile: " . $e->getMessage());
        return false;
    }
}

// Get beneficiary statistics
function getBeneficiaryStats($beneficiaryId) {
    global $conn;
    
    // Get help received count
    $stmt = $conn->prepare("SELECT COUNT(*) as help_count 
                           FROM help_received 
                           WHERE post_id IN (SELECT id FROM posts WHERE beneficiary_id = ?) 
                           AND status = 'completed'");
    $stmt->bind_param("i", $beneficiaryId);
    $stmt->execute();
    $helpCount = $stmt->get_result()->fetch_assoc()['help_count'];
    
    // Get active requests count
    $stmt = $conn->prepare("SELECT COUNT(*) as active_count 
                           FROM posts 
                           WHERE beneficiary_id = ? 
                           AND status IN ('pending', 'in_progress')");
    $stmt->bind_param("i", $beneficiaryId);
    $stmt->execute();
    $activeCount = $stmt->get_result()->fetch_assoc()['active_count'];
    
    // Get donors rated count
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT donor_id) as rated_count 
                           FROM ratings 
                           WHERE beneficiary_id = ?");
    $stmt->bind_param("i", $beneficiaryId);
    $stmt->execute();
    $ratedCount = $stmt->get_result()->fetch_assoc()['rated_count'];
    
    // Get total posts count
    $stmt = $conn->prepare("SELECT COUNT(*) as total_count 
                           FROM posts 
                           WHERE beneficiary_id = ?");
    $stmt->bind_param("i", $beneficiaryId);
    $stmt->execute();
    $totalCount = $stmt->get_result()->fetch_assoc()['total_count'];
    
    return [
        'help_received' => $helpCount,
        'active_requests' => $activeCount,
        'donors_rated' => $ratedCount,
        'total_posts' => $totalCount
    ];
}

// Get recent help received
function getRecentHelpReceived($beneficiaryId, $limit = 5) {
    global $conn;
    $stmt = $conn->prepare("SELECT hr.*, u.username as donor_name,
                           p.title as post_title
                           FROM help_received hr
                           JOIN posts p ON hr.post_id = p.id
                           JOIN users u ON hr.donor_id = u.id
                           WHERE p.beneficiary_id = ? AND hr.status = 'completed'
                           ORDER BY hr.created_at DESC
                           LIMIT ?");
    $stmt->bind_param("ii", $beneficiaryId, $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get active requests
function getActiveRequests($beneficiaryId) {
    global $conn;
    $stmt = $conn->prepare("SELECT p.*, 
                           (SELECT COUNT(*) FROM help_received WHERE post_id = p.id) as responses,
                           (SELECT COUNT(*) FROM post_images WHERE post_id = p.id) as image_count
                           FROM posts p
                           WHERE p.beneficiary_id = ? 
                           AND p.status IN ('pending', 'in_progress')
                           ORDER BY p.created_at DESC");
    $stmt->bind_param("i", $beneficiaryId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Create new post
function createPost($beneficiaryId, $data) {
    global $conn;
    
    // Validate required fields
    $required = ['title', 'description', 'category', 'urgency_level'];
    $errors = validateInput($data, $required);
    if (!empty($errors)) {
        return ['success' => false, 'message' => implode(', ', $errors)];
    }
    
    // Validate urgency level
    $validUrgencyLevels = ['low', 'medium', 'high'];
    if (!in_array($data['urgency_level'], $validUrgencyLevels)) {
        return ['success' => false, 'message' => 'Invalid urgency level'];
    }
    
    $conn->begin_transaction();
    
    try {
        // Insert post
        $stmt = $conn->prepare("INSERT INTO posts (beneficiary_id, title, description, category, urgency_level) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", 
            $beneficiaryId,
            $data['title'],
            $data['description'],
            $data['category'],
            $data['urgency_level']
        );
        $stmt->execute();
        $postId = $conn->insert_id;
        
        // Insert images if any
        if (!empty($data['images'])) {
            $stmt = $conn->prepare("INSERT INTO post_images (post_id, image_path) VALUES (?, ?)");
            foreach ($data['images'] as $image) {
                $stmt->bind_param("is", $postId, $image);
                $stmt->execute();
            }
        }
        
        // Insert verification documents
        if (!empty($data['valid_id'])) {
            $stmt = $conn->prepare("INSERT INTO verification_documents (post_id, document_type, document_path) 
                                  VALUES (?, 'valid_id', ?)");
            $stmt->bind_param("is", $postId, $data['valid_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        return ['success' => true, 'post_id' => $postId];
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in createPost: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to create post'];
    }
}

// Update post
function updatePost($postId, $data) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // Update post
        $stmt = $conn->prepare("UPDATE posts 
                               SET title = ?, 
                                   description = ?, 
                                   category = ?, 
                                   urgency_level = ?
                               WHERE id = ?");
        $stmt->bind_param("ssssi", 
            $data['title'],
            $data['description'],
            $data['category'],
            $data['urgency_level'],
            $postId
        );
        $stmt->execute();
        
        // Update images if any
        if (!empty($data['images'])) {
            // Delete existing images
            $stmt = $conn->prepare("DELETE FROM post_images WHERE post_id = ?");
            $stmt->bind_param("i", $postId);
            $stmt->execute();
            
            // Insert new images
            $stmt = $conn->prepare("INSERT INTO post_images (post_id, image_path) VALUES (?, ?)");
            foreach ($data['images'] as $image) {
                $stmt->bind_param("is", $postId, $image);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Rate a donor
function rateDonor($helpId, $beneficiaryId, $donorId, $rating, $comment = '') {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO ratings (help_id, beneficiary_id, donor_id, rating, comment) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiis", $helpId, $beneficiaryId, $donorId, $rating, $comment);
    return $stmt->execute();
}

// Add notification
function addNotification($userId, $title, $message, $type) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $title, $message, $type);
    return $stmt->execute();
}

// Get notifications with pagination
function getNotifications($userId, $limit = 10, $offset = 0) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notifications 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $userId, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get total notification count
function getTotalNotificationCount($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count 
                           FROM notifications 
                           WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

// Get unread notification count
function getUnreadNotificationCount($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as count 
                           FROM notifications 
                           WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

// Mark notification as read
function markNotificationAsRead($notificationId, $userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications 
                           SET is_read = TRUE 
                           WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

// Mark all notifications as read
function markAllNotificationsAsRead($userId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications 
                           SET is_read = TRUE 
                           WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

// Delete notification
function deleteNotification($notificationId, $userId) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM notifications 
                           WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

// Get notification settings
function getNotificationSettings($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT email_notifications, sms_notifications, feedback_notifications 
                           FROM users 
                           WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Update notification settings
function updateNotificationSettings($userId, $emailNotifications, $smsNotifications, $feedbackNotifications) {
    global $conn;
    $stmt = $conn->prepare("UPDATE users 
                           SET email_notifications = ?,
                               sms_notifications = ?,
                               feedback_notifications = ?
                           WHERE id = ?");
    $stmt->bind_param("iiii", $emailNotifications, $smsNotifications, $feedbackNotifications, $userId);
    return $stmt->execute();
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

// Helper function to get notification icon
function getNotificationIcon($type) {
    switch ($type) {
        case 'help':
            return 'hands-helping';
        case 'message':
            return 'envelope';
        case 'rating':
            return 'star';
        case 'system':
            return 'info-circle';
        default:
            return 'bell';
    }
}

// Profile Management Functions
function updateProfile($userId, $data) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE beneficiaries 
                           SET full_name = ?, 
                               birth_date = ?, 
                               gender = ?, 
                               address = ?, 
                               contact_number = ?, 
                               bio = ?
                           WHERE user_id = ?");
                           
    $stmt->bind_param("ssssssi", 
        $data['full_name'],
        $data['birth_date'],
        $data['gender'],
        $data['address'],
        $data['contact_number'],
        $data['bio'],
        $userId
    );
    
    return $stmt->execute();
}

function updateProfileImage($userId, $imagePath) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE beneficiaries 
                           SET profile_image = ? 
                           WHERE user_id = ?");
                           
    $stmt->bind_param("si", $imagePath, $userId);
    return $stmt->execute();
}

// Post Management Functions
function deletePost($postId, $beneficiaryId) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // Delete post images
        $stmt = $conn->prepare("DELETE FROM post_images WHERE post_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        
        // Delete verification documents
        $stmt = $conn->prepare("DELETE FROM verification_documents WHERE post_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        
        // Delete post
        $stmt = $conn->prepare("DELETE FROM posts WHERE id = ? AND beneficiary_id = ?");
        $stmt->bind_param("ii", $postId, $beneficiaryId);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Search and Filter Functions
function searchPosts($beneficiaryId, $search = '', $category = '', $status = '') {
    global $conn;
    
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM help_received WHERE post_id = p.id) as responses,
                     (SELECT COUNT(*) FROM post_images WHERE post_id = p.id) as image_count
              FROM posts p
              WHERE p.beneficiary_id = ?";
    
    $params = [$beneficiaryId];
    $types = "i";
    
    if (!empty($search)) {
        $query .= " AND (p.title LIKE ? OR p.description LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }
    
    if (!empty($category)) {
        $query .= " AND p.category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if (!empty($status)) {
        $query .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function searchDonors($filters = []) {
    global $conn;
    
    $query = "SELECT u.*, 
                     COUNT(DISTINCT r.id) as total_ratings,
                     AVG(r.rating) as average_rating,
                     COUNT(DISTINCT hr.id) as total_help
              FROM users u
              LEFT JOIN ratings r ON u.id = r.donor_id
              LEFT JOIN help_received hr ON u.id = hr.donor_id
              WHERE u.user_type = 'donor'";
    
    $params = [];
    $types = "";
    
    if (!empty($filters['location'])) {
        $query .= " AND u.location LIKE ?";
        $params[] = "%{$filters['location']}%";
        $types .= "s";
    }
    
    if (!empty($filters['help_type'])) {
        $query .= " AND EXISTS (
            SELECT 1 FROM help_received hr2 
            JOIN posts p ON hr2.post_id = p.id 
            WHERE hr2.donor_id = u.id AND p.category = ?
        )";
        $params[] = $filters['help_type'];
        $types .= "s";
    }
    
    if (!empty($filters['min_rating'])) {
        $query .= " HAVING average_rating >= ?";
        $params[] = $filters['min_rating'];
        $types .= "d";
    }
    
    $query .= " GROUP BY u.id ORDER BY average_rating DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// File Upload Functions
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }
    
    // Validate file type
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
    }
    
    // Generate unique filename
    $fileName = uniqid() . '_' . time() . '.' . $fileType;
    $targetPath = $targetDir . $fileName;
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'file_path' => $fileName];
    }
    
    return ['success' => false, 'message' => 'Failed to upload file'];
}

function validateFile($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit'];
    }
    
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    return ['success' => true];
}

// Account Management Functions
function deactivateAccount($userId) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

function deleteAccount($userId) {
    global $conn;
    
    $conn->begin_transaction();
    
    try {
        // Delete beneficiary profile
        $stmt = $conn->prepare("DELETE FROM beneficiaries WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Delete user's posts and related data
        $stmt = $conn->prepare("DELETE FROM posts WHERE beneficiary_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        // Delete user account
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

// Password Management Functions
function updatePassword($userId, $currentPassword, $newPassword) {
    global $conn;
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!password_verify($currentPassword, $result['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Failed to update password'];
}

// Add error handling function
function handleError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Add input validation function
function validateInput($data, $required = []) {
    $errors = [];
    foreach ($required as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = ucfirst($field) . " is required";
        }
    }
    return $errors;
}

// Add function to get post details with images and documents
function getPostDetails($postId, $beneficiaryId) {
    global $conn;
    try {
        // Get post details
        $stmt = $conn->prepare("SELECT p.*, 
                               (SELECT COUNT(*) FROM help_received WHERE post_id = p.id) as responses,
                               (SELECT COUNT(*) FROM post_images WHERE post_id = p.id) as image_count
                               FROM posts p
                               WHERE p.id = ? AND p.beneficiary_id = ?");
        $stmt->bind_param("ii", $postId, $beneficiaryId);
        $stmt->execute();
        $post = $stmt->get_result()->fetch_assoc();
        
        if (!$post) {
            return false;
        }
        
        // Get post images
        $stmt = $conn->prepare("SELECT image_path FROM post_images WHERE post_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $post['images'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get verification documents
        $stmt = $conn->prepare("SELECT * FROM verification_documents WHERE post_id = ?");
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $post['documents'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $post;
    } catch (Exception $e) {
        error_log("Error in getPostDetails: " . $e->getMessage());
        return false;
    }
}

// Add function to send thank you message
function sendThankYouMessage($beneficiaryId, $donorId, $helpId, $message) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO thank_you_messages (beneficiary_id, donor_id, help_id, message) 
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $beneficiaryId, $donorId, $helpId, $message);
        
        if ($stmt->execute()) {
            // Add notification for donor
            addNotification($donorId, 'New Thank You Message', 
                          'You have received a thank you message from a beneficiary', 'message');
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Failed to send thank you message'];
    } catch (Exception $e) {
        error_log("Error in sendThankYouMessage: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send thank you message'];
    }
}

// Add function to get donor statistics
function getDonorStats($donorId) {
    global $conn;
    try {
        $stats = [];
        
        // Get total help provided
        $stmt = $conn->prepare("SELECT COUNT(*) as total_help, 
                                      SUM(amount) as total_amount
                               FROM help_received 
                               WHERE donor_id = ? AND status = 'completed'");
        $stmt->bind_param("i", $donorId);
        $stmt->execute();
        $helpStats = $stmt->get_result()->fetch_assoc();
        $stats['total_help'] = $helpStats['total_help'];
        $stats['total_amount'] = $helpStats['total_amount'];
        
        // Get average rating
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, 
                                      COUNT(*) as total_ratings
                               FROM ratings 
                               WHERE donor_id = ?");
        $stmt->bind_param("i", $donorId);
        $stmt->execute();
        $ratingStats = $stmt->get_result()->fetch_assoc();
        $stats['avg_rating'] = round($ratingStats['avg_rating'], 1);
        $stats['total_ratings'] = $ratingStats['total_ratings'];
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error in getDonorStats: " . $e->getMessage());
        return false;
    }
} 