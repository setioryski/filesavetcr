<?php
session_start(); // Start the session

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Assuming the user is logged in if this point is reached
if (isset($_GET['file'])) {
    $file = urldecode($_GET['file']); // Decode the file path
    if (unlink($file)) { // Try to delete the file
        echo "File deleted successfully.";
    } else {
        echo "Error deleting the file.";
    }
}
?>

?php
// Ensure captured GET param exists
if (isset($_GET['file'])) {
    // Check if file is a directory
    if (is_dir($_GET['file'])) {
        // Attempt to delete the directory
        if (rmdir($_GET['file'])) {
            // Delete success! Redirect to file manager page
            header('Location: index.php');
            exit;
        } else {
            // Delete failed - directory is empty or insufficient permissions
            exit('Directory must be empty!');
        }
    } else {
        // Delete the file
        unlink($_GET['file']);
        // Delete success! Redirect to file manager page
        header('Location: index.php');
        exit;
    }
} else {
    exit('Invalid Request!');
}
?>