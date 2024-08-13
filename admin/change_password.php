<?php
include 'auth.php'; // Include authentication check
include '../db.php';

$id = $_GET['id'];
$result = $conn->query("SELECT username FROM users WHERE id=$id");
$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT); // Hash the new password

    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $new_password, $id);

    if ($stmt->execute()) {
        echo "Password changed successfully.";
        header("Location: users.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
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
    <title>Change Password</title>
    <link href="styleadmin.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1>Change Password for <?php echo $user['username']; ?></h1>
    <form method="post">
        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required>
        <br>
        <button type="submit">Change Password</button>
    </form>
    <a href="users.php">Back to Users</a>
</body>
</html>
