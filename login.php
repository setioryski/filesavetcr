<?php
session_start();
require 'db.php';  // Ensure your database connection file is correctly included

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username'], $_POST['password'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("SELECT id, password, role_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Directly compare the entered password with the database password
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id']; // Store the role ID in session
            $_SESSION['last_activity'] = time(); // Set the last activity time

            // Redirect based on the role_id
            if ($user['role_id'] == 1) { // Assuming 1 is the role_id for admin
                header("Location: /filesavetcr/index.php");
            } elseif ($user['role_id'] == 2) { // Assuming 2 is the role_id for regular user
                header("Location: /filesavetcr/submit.php");
            }
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
    <div class="login-container">
        <form method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <input type="submit" value="Login">
        </form>
        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
