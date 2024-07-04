<?php
// Make sure GET param exists
if (isset($_GET['directory'])) {
    // If form submitted
    if (isset($_POST['filename'], $_POST['type'])) {
        // Make sure there are no special characters (excluding hyphens, dots, and whitespaces)
        if (preg_match('/^[\w\-. ]+$/', $_POST['filename'])) {
            // Create directory or else create a file
            if ($_POST['type'] == 'directory') {
                mkdir($_GET['directory'] . $_POST['filename']);
            } else {
                file_put_contents($_GET['directory'] . $_POST['filename'], '');
            }
            // Redirect to the index page
            if ($_GET['directory']) {
                header('Location: index.php?file=' . urlencode($_GET['directory']));
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            exit('Please enter a valid name!');
        }
    }
} else {
    exit('Invalid directory!');
}
?>

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
                <h1>Create</h1>
            </div>

            <form action="" method="post">

                <label for="type">Type</label>
                <select id="type" name="type">
                    <option value="directory">Directory</option>
                    <option value="file">File</option>
                </select>

                <label for="filename">Name</label>
                <input id="filename" name="filename" type="text" placeholder="Name" required> 

                <button type="submit">Save</button>

            </form>

        </div>
    </body>
</html>