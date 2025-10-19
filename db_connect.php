<?php
// db_connect.php
// object-oriented mysqli connection (some files include this)

$servername = "localhost";
$username = "root";   // change if needed
$password = "";       // change if needed
$dbname = "feedforward_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
 