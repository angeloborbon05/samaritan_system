<?php
session_start();
require_once '../config/database.php'; // Include your database connection file

// Check if the admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'admin') {
    session_destroy(); // Destroy session to remove any stored data
    header('Location: ../auth/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../auth/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
<div class="navbar">
    <ul>
        <li><a href="admin_profile.php">Profile</a></li>
        <li><a href="admin_settings.php">Settings</a></li>
        <li><a href="../auth/logout.php" class="btn">Logout</a></li>
    </ul>
</div>
<div class="container">
    <h1 class="form-title">Admin Dashboard</h1>
    <p>Hello, <?php echo htmlspecialchars($_SESSION['user']['email']); ?>!</p>
</div>
<script src="admin_script.js"></script>
</body>
</html>
