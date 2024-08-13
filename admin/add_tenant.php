<?php
include 'auth.php'; // Include authentication check
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_tenant = $_POST['name_tenant'];

    $stmt = $conn->prepare("INSERT INTO tenant (name_tenant) VALUES (?)");
    $stmt->bind_param("s", $name_tenant);

    if ($stmt->execute()) {
        header("Location: tenants.php");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<link href="styleadmin.css" rel="stylesheet" type="text/css">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Tenant</title>
    <link href="styleadmin.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1>Add Tenant</h1>
    <form method="post">
        <label for="name_tenant">Tenant Name:</label>
        <input type="text" id="name_tenant" name="name_tenant" required>
        <button type="submit">Add</button>
    </form>
    <a href="tenants.php">Back to Tenants</a>
</body>
</html>
