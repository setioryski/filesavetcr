<?php
include 'auth.php'; // Include authentication check
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $role_id = $_POST['role_id'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $username, $password, $role_id);

    if ($stmt->execute()) {
        header("Location: users.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}

$roles = $conn->query("SELECT * FROM role");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link href="styleadmin.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1>Add User</h1>
    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <label for="role_id">Role:</label>
        <select id="role_id" name="role_id" required>
            <?php while($role = $roles->fetch_assoc()): ?>
            <option value="<?php echo $role['id']; ?>"><?php echo $role['role_name']; ?></option>
            <?php endwhile; ?>
        </select>
        <br>
        <button type="submit">Add</button>
    </form>
    <a href="users.php">Back to Users</a>
</body>
</html>
