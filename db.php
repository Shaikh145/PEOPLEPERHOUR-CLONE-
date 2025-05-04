<?php
// Database connection parameters
$host = "localhost";
$username = "uklz9ew3hrop3";
$password = "zyrbspyjlzjb";
$database = "dbc21ohmmrzuni";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Function to hash passwords
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Function to verify passwords
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to redirect using JavaScript
function jsRedirect($url) {
    echo "<script>window.location.href = '$url';</script>";
    exit;
}

// Function to display error message
function showError($message) {
    return "<div class='error-message'>$message</div>";
}

// Function to display success message
function showSuccess($message) {
    return "<div class='success-message'>$message</div>";
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
