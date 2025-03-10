<?php
require_once '../config/database.php';

// Replace 'admin@gmail.com' with the actual admin email if different
$admin_email = 'admin@gmail.com';

$query = "SELECT id, password FROM users WHERE email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    // Check if the password is already hashed
    if (password_get_info($admin['password'])['algo'] == 0) {
        $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed_password, $admin['id']);
        $stmt->execute();
        echo "Admin password has been hashed successfully.";
    } else {
        echo "Admin password is already hashed.";
    }
} else {
    echo "Admin user not found.";
}
?>
