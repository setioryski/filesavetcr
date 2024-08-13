<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    // User not logged in, redirect to login page
    header("Location: ../login.php");
    exit();
}

// Get user role
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT role_id FROM users WHERE id = $user_id");
$user = $result->fetch_assoc();

if ($user['role_id'] != 1) {
    // If not an admin, redirect to home page or show access denied
    echo "Access denied. Only admins are allowed.";
    exit();
}
?>
