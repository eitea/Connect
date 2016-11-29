<?php
require "connection_config.php";
require "connection_vars.php";

if(isset($_SESSION['dbConnect'])){
  $conn = new mysqli("localhost", "root", "", $dbName);
  //$conn = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $dbName, $_SERVER['RDS_PORT']);
} else {
  $conn = new mysqli($servername, $username, $password, $dbName);
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("SET NAMES 'utf8';");
$conn->query("SET CHARACTER SET 'utf8';");
