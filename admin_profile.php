<?php
session_start();
require_once '../config/database.php'; // Include your database connection file

// Check if the admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'admin') {
    header('Location: ../auth/index.php');
    exit();
}

// Fetch admin details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user']['id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link rel="stylesheet" href="../auth/style.css">
</head>
<body>
<div class="container">
    <h1 class="form-title">Admin Profile</h1>
    <p>Name: <?php echo $user['name']; ?></p>
    <p>Email: <?php echo $user['email']; ?></p>
    <a href="admin_dashboard.php" class="btn">Back to Dashboard</a>
</div>
</body>
</html>
