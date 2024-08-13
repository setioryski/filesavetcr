<?php
include 'auth.php'; // Include authentication check
include '../db.php';

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM tenant WHERE id=$id");
$tenant = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_tenant = $_POST['name_tenant'];

    $stmt = $conn->prepare("UPDATE tenant SET name_tenant=? WHERE id=?");
    $stmt->bind_param("si", $name_tenant, $id);

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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tenant</title>
    <link href="styleadmin.css" rel="stylesheet" type="text/css">
</head>
<body>
    <h1>Edit Tenant</h1>
    <form method="post">
        <label for="name_tenant">Tenant Name:</label>
        <input type="text" id="name_tenant" name="name_tenant" value="<?php echo $tenant['name_tenant']; ?>" required>
        <button type="submit">Update</button>
    </form>
    <a href="tenants.php">Back to Tenants</a>
</body>
</html>
