<?php
// db_config.php
// mysqli-style connection used by many files

$host = "localhost";
$user = "root"; // change if needed
$pass = "";     // change if needed
$dbname = "feedforward_db"; // matches your feedforward_db.sql

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}
?>
 