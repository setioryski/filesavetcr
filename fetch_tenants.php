<?php
require 'db.php';  // Ensure your database connection file is correctly included

$query = isset($_GET['query']) ? $_GET['query'] : '';

if ($query !== '') {
    $stmt = $conn->prepare("SELECT id, name_tenant FROM tenant WHERE name_tenant LIKE ?");
    $search = "%$query%";
    $stmt->bind_param("s", $search);
} else {
    // If the query is empty, fetch all tenants
    $stmt = $conn->prepare("SELECT id, name_tenant FROM tenant");
}

$stmt->execute();
$result = $stmt->get_result();

$tenants = array();
while ($row = $result->fetch_assoc()) {
    $tenants[] = array("id" => $row['id'], "name" => $row['name_tenant']);
}

echo json_encode($tenants);
?>
