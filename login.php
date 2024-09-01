<?php
session_start();
include 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role_id'] == 1) {
        header("Location: admin/index.php");
    } elseif ($_SESSION['role_id'] == 2) {
        header("Location: submit.php");
    }
    exit();
}

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
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
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role_id'] = $role_id;

                // Set session cookie flags
                ini_set('session.cookie_httponly', 1);
                ini_set('session.cookie_secure', 1);
                ini_set('session.use_strict_mode', 1);

                // Redirect based on role
                if ($role_id == 1) {
                    header("Location: admin/index.php");
                } elseif ($role_id == 2) {
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
    } else {
        $error_message = "Please fill in both fields.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="stylelogin.css" rel="stylesheet" type="text/css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        <?php if (!empty($error_message)): ?>
            <p><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <form method="post" action="login.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
