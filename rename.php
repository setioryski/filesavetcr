<?php
session_start(); // Start the session

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<?php
// Make sure GET param exists
if (isset($_GET['file'])) {
    // If form submitted
    if (isset($_POST['filename'])) {
        // Make sure there are no special characters (exluding hyphens, dots, and whitespaces)
        if (preg_match('/^[\w\-. ]+$/', $_POST['filename'])) {
            // Rename the file
            rename($_GET['file'], rtrim(pathinfo($_GET['file'], PATHINFO_DIRNAME), '/') . '/' . $_POST['filename']);
            // Redirect to the index page
            header('Location: index.php');
            exit;
        } else {
            exit('Please enter a valid name!');
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

                <label for="filename">Name</label>
                <input id="filename" name="filename" type="text" placeholder="Name" value="<?=basename($_GET['file'])?>" required> 

                <button type="submit">Save</button>

            </form>

        </div>
    </body>
</html>