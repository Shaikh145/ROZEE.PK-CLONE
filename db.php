<?php
// Database connection
$host = "localhost";
$username = "uklz9ew3hrop3";
$password = "zyrbspyjlzjb";
$database = "db5jfrgcm2r8ng";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
