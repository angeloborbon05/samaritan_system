<?php
require_once '../config/database.php';

$query = "SELECT id, password FROM users";
$result = $conn->query($query);

while ($user = $result->fetch_assoc()) {
    // Check if the password is already hashed
    if (password_get_info($user['password'])['algo'] == 0) {
        $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed_password, $user['id']);
        $stmt->execute();
    }
}

echo "Passwords have been hashed successfully.";
?>
