<?php
require_once '../config/database.php';

// Replace these values with the new admin's details
$new_admin_email = 'admin01@gmail.com';
$new_admin_password = 'admin12345';
$new_admin_name = 'Angelo';

// Hash the new admin's password
$hashed_password = password_hash($new_admin_password, PASSWORD_DEFAULT);

// Insert the new admin into the database
$query = "INSERT INTO users (email, password, name, user_type) VALUES (?, ?, ?, 'admin')";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $new_admin_email, $hashed_password, $new_admin_name);

if ($stmt->execute()) {
    echo "New admin has been added successfully.";
} else {
    echo "Error: " . $stmt->error;
}
?>
