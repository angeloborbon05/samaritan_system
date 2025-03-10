<?php
session_start();
require_once '../config/database.php'; // Include your database connection file

// Check if the admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] != 'admin') {
    header('Location: ../auth/index.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];

    $sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $email, $_SESSION['user']['id']);

    if ($stmt->execute()) {
        echo "Settings updated successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }
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
    <title>Admin Settings</title>
    <link rel="stylesheet" href="../auth/style.css">
</head>
<body>
<div class="container">
    <h1 class="form-title">Admin Settings</h1>
    <form method="POST" action="admin_settings.php">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
        <br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
        <br>
        <button type="submit" class="btn">Update Settings</button>
    </form>
    <a href="admin_dashboard.php" class="btn">Back to Dashboard</a>
</div>
</body>
</html>
