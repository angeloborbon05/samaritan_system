<?php
session_start();
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Gamitin ang password_verify() para icheck ang hashed password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;

            //  Redirect based on user type
            if ($user['user_type'] == 'donor') {
                header('Location: ../donors/donors_dashboard.php');
            } else if ($user['user_type'] == 'beneficiary') {
                header('Location: ../beneficiaries/beneficiaries_dashboard.php');
            } else if ($user['user_type'] == 'admin') {
                header('Location: ../admin/admin_dashboard.php');
            } else {
                $_SESSION['error'] = 'Invalid user type. Please contact support.';
                header('Location: index.php');
            }
            exit();
        } else {
            $_SESSION['error'] = 'Invalid email or password';
        }
    } else {
        $_SESSION['error'] = 'Invalid email or password';
    }

    header('Location: index.php');
    exit();
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
