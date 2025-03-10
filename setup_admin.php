<?php
require_once __DIR__ . '/../config/database.php';

// Admin credentials - change these to your preferred values
$admin = [
    'name' => 'Administrator',
    'email' => 'admin01@gmail.com',
    'password' => 'admin12345',
    'user_type' => 'admin'
];

try {
    // Create users table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            user_type VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Check if admin exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_stmt->bind_param("s", $admin['email']);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing admin
        $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, password = ? WHERE email = ?");
        $update_stmt->bind_param("sss", $admin['name'], $hashed_password, $admin['email']);
        
        if ($update_stmt->execute()) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px;'>";
            echo "✅ Admin account updated successfully!<br>";
            echo "Email: " . $admin['email'] . "<br>";
            echo "Password: " . $admin['password'] . "<br>";
            echo "</div>";
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
            echo "❌ Failed to update admin account: " . $update_stmt->error;
            echo "</div>";
        }
    } else {
        // Create new admin
        $hashed_password = password_hash($admin['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $admin['name'], $admin['email'], $hashed_password, $admin['user_type']);
        
        if ($stmt->execute()) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px;'>";
            echo "✅ New admin account created successfully!<br>";
            echo "Email: " . $admin['email'] . "<br>";
            echo "Password: " . $admin['password'] . "<br>";
            echo "</div>";
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
            echo "❌ Failed to create admin account: " . $stmt->error;
            echo "</div>";
        }
    }

    // Verify the admin account
    $verify_stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $verify_stmt->bind_param("s", $admin['email']);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<div style='background-color: #f0f0f0; padding: 10px; margin: 10px;'>";
        echo "✅ Admin account verification successful:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Name: " . $user['name'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "User Type: " . $user['user_type'] . "<br>";
        echo "</div>";
        
        echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px;'>";
        echo "⚠️ Important: Please save these credentials and delete this file after use!<br>";
        echo "You can now log in at: <a href='../auth/index.php'>Login Page</a>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
}
</style> 