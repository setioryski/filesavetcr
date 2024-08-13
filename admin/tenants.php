<?php
include 'auth.php'; // Include authentication check
include '../db.php';

$result = $conn->query("SELECT * FROM tenant");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tenants</title>
    <link href="styleadmin.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1>Tenants</h1>
    <a href="add_tenant.php">Add Tenant</a>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Actions</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo $row['name_tenant']; ?></td>
            <td>
                <a href="edit_tenant.php?id=<?php echo $row['id']; ?>">Edit</a>
                <a href="delete_tenant.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
<?php
$conn->close();
?>
