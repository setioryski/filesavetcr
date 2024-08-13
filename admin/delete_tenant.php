<?php
include 'auth.php'; // Include authentication check
include '../db.php';

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM tenant WHERE id=?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: tenants.php");
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
