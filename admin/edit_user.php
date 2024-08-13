<?php
include 'auth.php'; // Include authentication check
include '../db.php';

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM users WHERE id=$id");
$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $role_id = $_POST['role_id'];

    $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role_id=? WHERE id=?");
    $stmt->bind_param("ssii", $username, $password, $role_id, $id);

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
    <title>Edit User</title>
    <link href="styleadmin.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1>Edit User</h1>
    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" value="<?php echo $user['username']; ?>" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" value="" required>
        <br>
        <label for="role_id">Role:</label>
        <select id="role_id" name="role_id" required>
            <?php while($role = $roles->fetch_assoc()): ?>
            <option value="<?php echo $role['id']; ?>" <?php if($role['id'] == $user['role_id']) echo 'selected'; ?>><?php echo $role['role_name']; ?></option>
            <?php endwhile; ?>
        </select>
        <br>
        <button type="submit">Update</button>
    </form>
    <a href="users.php">Back to Users</a>
</body>
</html>
