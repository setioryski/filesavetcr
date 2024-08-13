<?php
include 'auth.php'; // Include authentication check
include '../db.php';

$result = $conn->query("SELECT users.id, users.username, role.role_name FROM users LEFT JOIN role ON users.role_id = role.id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link href="styleadmin.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1>Users</h1>
    <a href="add_user.php">Add User</a>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['username']; ?></td>
            <td><?php echo $row['role_name']; ?></td>
            <td>
                <a href="edit_user.php?id=<?php echo $row['id']; ?>">Edit</a>
                <a href="change_password.php?id=<?php echo $row['id']; ?>">Change Password</a>
                <a href="delete_user.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
<?php
$conn->close();
?>
