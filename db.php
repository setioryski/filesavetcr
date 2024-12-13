<?php
$servername = "localhost";
$username = "root";
$password = "aejot1234";  // NULL password, typically used for localhost without a password
$dbname = "crudimg";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
