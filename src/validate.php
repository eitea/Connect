<?php
/*
isCoreAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',      |
isTimeAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',      | - Define accessable Pages
isProjectAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',   |

canStamp ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',          | - Define Menu Items.
canBook ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',          |
*/

function enableToCore($userID){
  require 'connection.php';
  $sql = "SELECT isCoreAdmin FROM $roleTable WHERE userID = $userID AND isCoreAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToTime($userID){
  require 'connection.php';
  $sql = "SELECT isTimeAdmin FROM $roleTable WHERE userID = $userID AND isTimeAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToProject($userID){
  require 'connection.php';
  $sql = "SELECT isProjectAdmin FROM $roleTable WHERE userID = $userID AND isProjectAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToStamps($userID){
  require 'connection.php';
  $sql = "SELECT canStamp FROM $roleTable WHERE userID = $userID AND canStamp = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToBookings($userID){
  require 'connection.php';
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND canBook = 'TRUE' AND canStamp = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToTemplate($userID){
  require 'connection.php';
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND (isCoreAdmin = 'TRUE' OR canEditTemplates = 'TRUE')";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToReport($userID){
  require 'connection.php';
  $sql = "SELECT isReportAdmin FROM $roleTable WHERE userID = $userID AND isReportAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function denyToCloud(){
  if(isset($_SESSION['dbConnect']) || isset($_SERVER['RDS_HOSTNAME']) || isset($_SERVER['RDS_PORT'])){
    die("Restricted Access. <a href='logout.php'> Exit</a>");
  }
}
?>
