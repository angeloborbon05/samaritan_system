<?php

session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];

    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Query to insert the new user
        $query = "INSERT INTO users (username, email, password, user_type, full_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $user_type, $name);

        if ($stmt->execute()) {
            header('Location: index.php');
            exit();
        } else {
            $error = 'Error registering user';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>

<div class="container">
    <h1 class="form-title">Register</h1>

    <?php if (isset($error)): ?>
        <div class="error-main">
            <p><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="name" id="name" placeholder="Name" required>
        </div>

        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" id="email" placeholder="Email" required>
        </div>

        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required>
            <i class="fa fa-eye-slash" id="eye" onclick="togglePasswordVisibility('password', 'eye')"></i>
        </div>

        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
        </div>

        <div class="input-group">
            <i class="fas fa-user-tag"></i>
            <select name="user_type" id="user_type" required>
                <option value="" disabled selected>Select your role</option>
                <option value="donor">Donor</option>
                <option value="beneficiary">Beneficiary</option>
            </select>
        </div>

        <input type="submit" value="Sign Up" class="btn">
    </form>

    <div class="links">
        <p>Already have an account? <a href="index.php">Sign In</a></p>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>