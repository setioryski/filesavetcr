<?php
session_start(); // Start the session

header('Content-Type: application/json'); // Ensure JSON response

$response = array('success' => false, 'message' => '');

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'You must be logged in to delete a file.';
    echo json_encode($response);
    exit;
}

// Assuming the user is logged in if this point is reached
if (isset($_POST['file'])) {
    $file = urldecode($_POST['file']); // Decode the file path
    if (unlink($file)) { // Try to delete the file
        $response['success'] = true;
        $response['message'] = 'File deleted successfully.';
    } else {
        $response['message'] = 'Error deleting the file.';
    }
} else {
    $response['message'] = 'No file specified.';
}

echo json_encode($response);
?>
