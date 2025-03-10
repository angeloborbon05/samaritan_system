<?php
session_start();
require_once '../config/database.php'; // Include your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to check if the admin credentials are correct
    $query = "SELECT * FROM users WHERE email = ? AND user_type = 'admin'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($password == $user['password']) { // Directly compare plain text password
            $_SESSION['user'] = $user;
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../auth/style.css">
</head>
<body>
<div class="container">
    <h1 class="form-title">Admin Login</h1>
    <?php if (isset($error)): ?>
        <div class="error-main">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    <form method="POST" action="verify_admin.php">
        <div class="input-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="input-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
</div>
</body>
</html>
