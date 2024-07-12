<?php
session_start(); // Start the session

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Make sure GET param exists
if (isset($_GET['file'])) {
    $current_directory = dirname($_GET['file']);
    // If form submitted
    if (isset($_POST['filename'])) {
        // Make sure there are no special characters (excluding hyphens, dots, and whitespaces)
        if (preg_match('/^[\w\-. ]+$/', $_POST['filename'])) {
            // Rename the file
            $new_path = rtrim($current_directory, '/') . '/' . $_POST['filename'];
            if (rename($_GET['file'], $new_path)) {
                // Redirect to the index page with the current directory
                header('Location: index.php?file=' . urlencode($current_directory));
                exit;
            } else {
                $error = "Failed to rename the file.";
            }
        } else {
            $error = 'Please enter a valid name!';
        }
    }
} else {
    exit('Invalid file!');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,minimum-scale=1">
    <title>File Management System</title>
    <link href="style.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
    <div class="file-manager">
        <div class="file-manager-header">
            <h1>Rename</h1>
        </div>
        <form action="" method="post">
            <input type="hidden" name="current_directory" value="<?=htmlspecialchars($current_directory)?>">
            <label for="filename">Name</label>
            <input id="filename" name="filename" type="text" placeholder="Name" value="<?=htmlspecialchars(basename($_GET['file']))?>" required>
            <button type="submit">Save</button>
        </form>
        <?php if (isset($error)): ?>
            <p class="error"><?=htmlspecialchars($error)?></p>
        <?php endif; ?>
    </div>
</body>
</html>
