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

// Get request data
$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $data['action'] ?? '';

switch ($action) {
    case 'filter_requests':
        $filters = $data['filters'] ?? [];
        $location = $filters['location'] ?? '';
        $urgency = $filters['urgency'] ?? '';
        $type = $filters['type'] ?? '';
        
        try {
            $query = "SELECT p.*, b.full_name as beneficiary_name, b.address as location, 
                     u.profile_image 
                     FROM posts p 
                     JOIN beneficiaries b ON p.beneficiary_id = b.id 
                     JOIN users u ON b.user_id = u.id 
                     WHERE p.status = 'active'";
            
            $params = [];
            $types = "";
            
            if (!empty($location)) {
                $query .= " AND b.address LIKE ?";
                $params[] = "%$location%";
                $types .= "s";
            }
            if (!empty($urgency)) {
                $query .= " AND p.urgency_level = ?";
                $params[] = $urgency;
                $types .= "s";
            }
            if (!empty($type)) {
                $query .= " AND p.help_type = ?";
                $params[] = $type;
                $types .= "s";
            }
            
            $query .= " ORDER BY p.created_at DESC";
            
            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $requests = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'requests' => $requests]);
        } catch (Exception $e) {
            error_log("Error in filter_requests: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error filtering requests']);
        }
        break;

    case 'submit_donation':
        $requestId = $data['request_id'] ?? '';
        $amount = $data['amount'] ?? '';
        $message = $data['message'] ?? '';
        $donorId = $_SESSION['user']['id'];
        
        if (empty($requestId) || empty($amount)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Get post details
            $stmt = $conn->prepare("SELECT p.*, b.user_id as beneficiary_user_id 
                                  FROM posts p 
                                  JOIN beneficiaries b ON p.beneficiary_id = b.id 
                                  WHERE p.id = ?");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            $post = $result->fetch_assoc();
            
            if (!$post) {
                throw new Exception('Request not found');
            }
            
            // Create help_received record
            $stmt = $conn->prepare("INSERT INTO help_received (post_id, donor_id, amount, description, status, created_at) 
                                  VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("iids", $requestId, $donorId, $amount, $message);
            $stmt->execute();
            $helpId = $stmt->insert_id;
            
            // Create notification for beneficiary
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id, created_at) 
                                  VALUES (?, 'New Donation Offer', ?, 'donation', ?, NOW())");
            $notifMessage = "A donor has offered to help with your request. Amount: " . formatCurrency($amount);
            if (!empty($message)) {
                $notifMessage .= "\nMessage: " . $message;
            }
            $stmt->bind_param("isi", $post['beneficiary_user_id'], $notifMessage, $helpId);
            $stmt->execute();
            
            // Update post status if needed
            if ($amount >= $post['amount_needed']) {
                $stmt = $conn->prepare("UPDATE posts SET status = 'in_progress' WHERE id = ?");
                $stmt->bind_param("i", $requestId);
                $stmt->execute();
            }
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Donation offer sent successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error in submit_donation: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error submitting donation']);
        }
        break;

    case 'contact_beneficiary':
        $beneficiaryId = $data['beneficiary_id'] ?? '';
        $message = $data['message'] ?? '';
        $donorId = $_SESSION['user']['id'];
        
        if (empty($beneficiaryId) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Get beneficiary user_id
            $stmt = $conn->prepare("SELECT user_id, full_name FROM beneficiaries WHERE id = ?");
            $stmt->bind_param("i", $beneficiaryId);
            $stmt->execute();
            $result = $stmt->get_result();
            $beneficiary = $result->fetch_assoc();
            
            if (!$beneficiary) {
                throw new Exception('Beneficiary not found');
            }
            
            // Create message record
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, created_at) 
                                  VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $donorId, $beneficiary['user_id'], $message);
            $stmt->execute();
            $messageId = $stmt->insert_id;
            
            // Create notification for beneficiary
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id, created_at) 
                                  VALUES (?, 'New Message from Donor', ?, 'message', ?, NOW())");
            $notifMessage = "You have received a new message from a donor.";
            $stmt->bind_param("isi", $beneficiary['user_id'], $notifMessage, $messageId);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error in contact_beneficiary: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error sending message']);
        }
        break;

    case 'help_beneficiary':
        $beneficiaryId = $data['beneficiary_id'] ?? '';
        $amount = $data['amount'] ?? '';
        $message = $data['message'] ?? '';
        $donorId = $_SESSION['user']['id'];
        
        if (empty($beneficiaryId) || empty($amount)) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Create help_received record
            $stmt = $conn->prepare("INSERT INTO help_received (beneficiary_id, donor_id, amount, description, status, created_at) 
                                  VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("iids", $beneficiaryId, $donorId, $amount, $message);
            $stmt->execute();
            
            // Create notification for beneficiary
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) 
                                  SELECT user_id, 'New Help Offer', ?, 'help', NOW()
                                  FROM beneficiaries 
                                  WHERE id = ?");
            $notifMessage = "A donor wants to help you. Amount offered: " . formatCurrency($amount);
            $stmt->bind_param("si", $notifMessage, $beneficiaryId);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Help offer sent successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error in help_beneficiary: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error processing help offer']);
        }
        break;

    case 'update_profile':
        $userId = $_SESSION['user']['id'];
        $fullName = $data['full_name'] ?? '';
        $email = $data['email'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $birthDate = $data['birth_date'] ?? '';
        $gender = $data['gender'] ?? '';
        $bio = $data['bio'] ?? '';
        
        if (empty($fullName) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Name and email are required']);
            exit();
        }
        
        try {
            $conn->begin_transaction();
            
            // Update users table
            $stmt = $conn->prepare("UPDATE users SET 
                                  username = ?,
                                  email = ?,
                                  phone = ?,
                                  address = ?,
                                  birth_date = ?,
                                  gender = ?,
                                  bio = ?,
                                  updated_at = NOW()
                                  WHERE id = ?");
            $stmt->bind_param("sssssssi", $fullName, $email, $phone, $address, $birthDate, $gender, $bio, $userId);
            $stmt->execute();
            
            // Update session data
            $_SESSION['user']['username'] = $fullName;
            $_SESSION['user']['email'] = $email;
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error updating profile: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating profile']);
        }
        break;

    case 'update_password':
        $userId = $_SESSION['user']['id'];
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'All password fields are required']);
            exit();
        }
        
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit();
        }
        
        try {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (!password_verify($currentPassword, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
                exit();
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        } catch (Exception $e) {
            error_log("Error updating password: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating password']);
        }
        break;

    case 'update_notification_settings':
        $userId = $_SESSION['user']['id'];
        $emailNotifications = $data['email_notifications'] ?? false;
        $smsNotifications = $data['sms_notifications'] ?? false;
        $feedbackNotifications = $data['feedback_notifications'] ?? false;
        
        try {
            $stmt = $conn->prepare("UPDATE users SET 
                                  email_notifications = ?,
                                  sms_notifications = ?,
                                  feedback_notifications = ?
                                  WHERE id = ?");
            $stmt->bind_param("iiii", $emailNotifications, $smsNotifications, $feedbackNotifications, $userId);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Notification settings updated']);
        } catch (Exception $e) {
            error_log("Error updating notification settings: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating notification settings']);
        }
        break;

    case 'get_request_details':
        $requestId = $_GET['request_id'] ?? '';
        
        if (empty($requestId)) {
            echo json_encode(['success' => false, 'message' => 'Request ID is required']);
            exit();
        }
        
        try {
            $stmt = $conn->prepare("
                SELECT p.*, b.user_id as beneficiary_user_id, b.id as beneficiary_id,
                       u.username as beneficiary_name, u.profile_image, u.address as location,
                       (SELECT AVG(rating) FROM ratings WHERE beneficiary_id = b.id) as rating
                FROM posts p
                JOIN beneficiaries b ON p.beneficiary_id = b.id
                JOIN users u ON b.user_id = u.id
                WHERE p.id = ?
            ");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();
            
            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                exit();
            }
            
            // Get request images
            $stmt = $conn->prepare("
                SELECT pi.*, DATE_FORMAT(pi.created_at, '%M %d, %Y') as date
                FROM post_images pi
                WHERE pi.post_id = ?
            ");
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $imagesResult = $stmt->get_result();
            $request['images'] = [];
            
            while ($image = $imagesResult->fetch_assoc()) {
                $request['images'][] = [
                    'url' => $image['image_url'],
                    'title' => $image['title'] ?? 'Supporting Document',
                    'description' => $image['description'] ?? '',
                    'date' => $image['date']
                ];
            }
            
            echo json_encode(['success' => true, 'request' => $request]);
        } catch (Exception $e) {
            error_log("Error getting request details: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading request details']);
        }
        break;

    case 'update_profile_picture':
        $userId = $_SESSION['user']['id'];
        
        if (!isset($_FILES['profile_picture'])) {
            echo json_encode(['success' => false, 'message' => 'No file uploaded']);
            exit();
        }
        
        $file = $_FILES['profile_picture'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileError = $file['error'];
        
        if ($fileError !== 0) {
            echo json_encode(['success' => false, 'message' => 'Error uploading file']);
            exit();
        }
        
        // Get file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($fileExt, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG & PNG files are allowed']);
            exit();
        }
        
        // Generate unique filename
        $newFileName = uniqid('profile_', true) . '.' . $fileExt;
        $uploadPath = '../uploads/profile_pictures/' . $newFileName;
        
        // Create directory if it doesn't exist
        if (!file_exists('../uploads/profile_pictures')) {
            mkdir('../uploads/profile_pictures', 0777, true);
        }
        
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Move uploaded file
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Update database
                $imageUrl = '/uploads/profile_pictures/' . $newFileName;
                $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->bind_param("si", $imageUrl, $userId);
                $stmt->execute();
                
                // Delete old profile picture if exists
                if (!empty($_SESSION['user']['profile_image'])) {
                    $oldFile = $_SERVER['DOCUMENT_ROOT'] . $_SESSION['user']['profile_image'];
                    if (file_exists($oldFile) && is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
                
                // Update session
                $_SESSION['user']['profile_image'] = $imageUrl;
                
                $conn->commit();
                echo json_encode(['success' => true, 'image_url' => $imageUrl]);
            } else {
                throw new Exception('Failed to move uploaded file');
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error updating profile picture: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating profile picture']);
        }
        break;

    case 'get_messages':
        $beneficiaryId = $data['beneficiary_id'] ?? '';
        $donorId = $_SESSION['user']['id'];
        
        if (empty($beneficiaryId)) {
            echo json_encode(['success' => false, 'message' => 'Beneficiary ID is required']);
            exit();
        }
        
        try {
            // Get beneficiary user_id
            $stmt = $conn->prepare("SELECT user_id FROM beneficiaries WHERE id = ?");
            $stmt->bind_param("i", $beneficiaryId);
            $stmt->execute();
            $result = $stmt->get_result();
            $beneficiary = $result->fetch_assoc();
            
            if (!$beneficiary) {
                throw new Exception('Beneficiary not found');
            }
            
            // Get messages
            $stmt = $conn->prepare("SELECT m.*, 
                                  CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as direction,
                                  u.username as sender_name, u.profile_image as sender_image
                                  FROM messages m
                                  JOIN users u ON m.sender_id = u.id
                                  WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                                  OR (m.sender_id = ? AND m.receiver_id = ?)
                                  ORDER BY m.created_at ASC");
            $stmt->bind_param("iiiii", $donorId, $donorId, $beneficiary['user_id'], $beneficiary['user_id'], $donorId);
            $stmt->execute();
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
        } catch (Exception $e) {
            error_log("Error getting messages: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error loading messages']);
        }
        break;

    case 'mark_help_completed':
        $helpId = $data['help_id'] ?? '';
        $donorId = $_SESSION['user']['id'];
        
        if (empty($helpId)) {
            echo json_encode(['success' => false, 'message' => 'Help ID is required']);
            exit();
        }
        
        try {
            $conn->begin_transaction();
            
            // Update help_received status
            $stmt = $conn->prepare("UPDATE help_received SET status = 'completed', completed_at = NOW() 
                                  WHERE id = ? AND donor_id = ?");
            $stmt->bind_param("ii", $helpId, $donorId);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
                throw new Exception('Help record not found or unauthorized');
            }
            
            // Get beneficiary details
            $stmt = $conn->prepare("SELECT b.user_id, b.id as beneficiary_id, p.id as post_id
                                  FROM help_received h
                                  JOIN posts p ON h.post_id = p.id
                                  JOIN beneficiaries b ON p.beneficiary_id = b.id
                                  WHERE h.id = ?");
            $stmt->bind_param("i", $helpId);
            $stmt->execute();
            $result = $stmt->get_result();
            $help = $result->fetch_assoc();
            
            // Create notification for beneficiary
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, reference_id, created_at)
                                  VALUES (?, 'Help Completed', 'A donor has marked their help as completed. Please confirm and provide feedback.', 'help_completed', ?, NOW())");
            $stmt->bind_param("ii", $help['user_id'], $helpId);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Help marked as completed']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error marking help completed: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error updating help status']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
} 