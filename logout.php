<?php
session_start(); // Start the session
session_destroy(); // Destroy the session

// Clear cookies
setcookie("myapp_session", "", time() - 86400, "/");
setcookie("myapp_remember_me", "", time() - 86400, "/");

// Redirect to login or homepage
header("Location: login.php");
exit();
?>
