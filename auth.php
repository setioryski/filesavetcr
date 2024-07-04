<?php
session_start(); // Start a session or resume the existing one

// If authenticated session is not set
if (!isset($_SESSION['authenticated'])) {
    // Check if the password has been submitted
    if (isset($_POST['password']) && $_POST['password'] == "12345") {
        $_SESSION['authenticated'] = true; // Set authenticated session
        header("Location: " . $_SERVER['PHP_SELF']); // Reload the current file
        exit;
    } else {
        // Display password form if password is wrong or not submitted yet
        echo '<form action="" method="post">Enter Password: <input type="password" name="password"><input type="submit" value="Submit"></form>';
        exit; // Stop further script execution until authenticated
    }
}
?>
