<?php //may NOT contain any functions or classes
require "connection_config.php";
require "connection_vars.php";

$conn = new mysqli($servername, $username, $password, $dbName);

if ($conn->connect_error){
  die("Connection failed: " . $conn->connect_error);
}

$conn->query("SET NAMES 'utf8';");
$conn->query("SET CHARACTER SET 'utf8';");
