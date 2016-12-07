<?php
require "connection_config.php";
require "connection_vars.php";


if(!isset($_SESSION)){ //my gosh, PLEASE
  session_start();
}

if(isset($_SESSION['dbConnect'])){
  if(isset($_SERVER['RDS_HOSTNAME'])){
    $conn = mysqli_connect($_SERVER['RDS_HOSTNAME'], $_SERVER['RDS_USERNAME'], $_SERVER['RDS_PASSWORD'], $_SESSION['dbConnect'], $_SERVER['RDS_PORT']);
  } else {
    $conn = new mysqli($servername, $username, $password, $_SESSION['dbConnect']);
  }
} else {
  $conn = new mysqli($servername, $username, $password, $dbName);
}
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("SET NAMES 'utf8';");
$conn->query("SET CHARACTER SET 'utf8';");
