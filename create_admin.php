<?php
require_once __DIR__ . '/../config/database.php';

// Default admin credentials
$admin_email = 'admin@gmail.com'; // Changed to a simpler email
$admin_password = 'admin123'; // Changed to a simpler password
$admin_name = 'System Administrator';
$user_type = 'admin';

try {
    // Check if admin already exists
    $check_stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ?");
    if (!$check_stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $check_stmt->bind_param("s", $admin_email);
    if (!$check_stmt->execute()) {
        die("Execute failed: " . $check_stmt->error);
    }

    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Admin account already exists!\n";
        echo "Email: " . $admin_email . "\n";
        
        // Update the admin password
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $hashed_password, $admin_email);
        
        if ($update_stmt->execute()) {
            echo "Admin password has been updated!\n";
            echo "New password: " . $admin_password . "\n";
        } else {
            echo "Failed to update admin password: " . $update_stmt->error . "\n";
        }
    } else {
        // Hash the password
        $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
        
        // Insert admin user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type, created_at) VALUES (?, ?, ?, ?, NOW())");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssss", $admin_name, $admin_email, $hashed_password, $user_type);
        
        if ($stmt->execute()) {
            echo "Default admin account created successfully!\n";
            echo "Email: " . $admin_email . "\n";
            echo "Password: " . $admin_password . "\n";
            echo "\nPlease make sure to change these credentials after first login.";
            
            // Verify the hash
            $verify = password_verify($admin_password, $hashed_password);
            echo "\n\nPassword hash verification: " . ($verify ? "SUCCESS" : "FAILED");
        } else {
            echo "Error creating admin account: " . $stmt->error;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Verify the admin account can be retrieved
try {
    $verify_stmt = $conn->prepare("SELECT id, email, password, user_type FROM users WHERE email = ?");
    $verify_stmt->bind_param("s", $admin_email);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "\n\nVerification of admin account in database:";
        echo "\nID: " . $user['id'];
        echo "\nEmail: " . $user['email'];
        echo "\nUser Type: " . $user['user_type'];
        echo "\nPassword verification: " . (password_verify($admin_password, $user['password']) ? "SUCCESS" : "FAILED");
    } else {
        echo "\n\nWARNING: Admin account not found in verification check!";
    }
} catch (Exception $e) {
    echo "\nVerification Error: " . $e->getMessage();
}
?> 