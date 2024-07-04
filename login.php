<?php
session_start();
require 'db.php';  // Make sure your database connection file is correctly included

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Directly compare the entered password with the database password
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: /filesave"); // Redirect to a logged-in page
            exit;
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
    }
    $stmt->close();
}
?>
<link rel="stylesheet" type="text/css" href="stylelogin.css">
<html>
<head>
    <title>Login</title>
</head>
<body>
    <form method="post">
        Username: <input type="text" name="username"><br>
        Password: <input type="password" name="password"><br>
        <input type="submit" value="Login">
    </form>
    <?php if ($error): ?>
        <p><?php echo $error; ?></p>
    <?php endif; ?>
</body>
</html>
