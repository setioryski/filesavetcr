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

<?php
$directory = "uploads/";

// Check if the directory exists, if not try to create it
if (!file_exists($directory)) {
    if (!mkdir($directory, 0777, true)) {
        die('Failed to create folders...');
    }
}

// Check if a file has been uploaded
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['fileToUpload'])) {
    $targetFile = $directory . basename($_FILES['fileToUpload']['name']);

    // Validate file size, type, or other rules here
    if (move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $targetFile)) {
        echo "The file " . htmlspecialchars(basename($_FILES['fileToUpload']['name'])) . " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        form { background: #f7f7f7; padding: 20px; border-radius: 8px; }
        input[type="file"] { margin-top: 10px; }
        input[type="submit"] { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>Upload a File</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <label for="fileToUpload">Select file to upload:</label>
        <input type="file" name="fileToUpload" id="fileToUpload">
        <input type="submit" value="Upload File" name="submit">
    </form>
</body>
</html>
