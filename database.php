<?php
$servername = "localhost";
$username = "root";
$password = "";

try {
    // First create the database if it doesn't exist
    $temp_conn = new mysqli($servername, $username, $password);
    if ($temp_conn->connect_error) {
        throw new Exception("Connection failed: " . $temp_conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $temp_conn->query("CREATE DATABASE IF NOT EXISTS auth");
    $temp_conn->close();

    // Connect to the auth database
    $conn = new mysqli($servername, $username, $password, "auth");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set charset to utf8mb4
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
