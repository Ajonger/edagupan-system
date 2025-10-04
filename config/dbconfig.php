<?php
$host = "localhost";
$user = "root";   // default XAMPP user
$pass = "";       // default is empty in XAMPP
$dbname = "edagupan_db";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
