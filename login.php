<?php
session_start();
include 'db.php';

if (isset($_SESSION['user_id'])) {
    // If user is already logged in, redirect based on role
    if ($_SESSION['role_id'] == 1) { // assuming role_id 1 is for admin
        header("Location: admin/index.php");
    } else if ($_SESSION['role_id'] == 2) { // assuming role_id 2 is for regular user
        header("Location: submit.php");
    }
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute the query to find the user
    $stmt = $conn->prepare("SELECT id, password, role_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role_id);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role_id'] = $role_id;

            // Redirect based on role
            if ($role_id == 1) { // assuming role_id 1 is for admin
                header("Location: admin/index.php");
            } else if ($role_id == 2) { // assuming role_id 2 is for regular user
                header("Location: submit.php");
            }
            exit();
        } else {
            $error_message = "Invalid username or password.";
        }
    } else {
        $error_message = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="stylelogin.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1>Login</h1>
    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <button type="submit">Login</button>
    </form>
</body>
</html>
