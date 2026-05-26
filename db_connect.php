<?php
// db_connect.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mcc";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8mb4");
?>