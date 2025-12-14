<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Maintenance";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    // Try without password if first attempt fails (common local setup)
    $conn = mysqli_connect($servername, $username, "", $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
}
?>
