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
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND isCoreAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToTime($userID){
  require 'connection.php';
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND isTimeAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToProject($userID){
  require 'connection.php';
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND isProjectAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="logout.php"> return</a>');
  }
}

function enableToStamps($userID){
  require 'connection.php';
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND canStamp = 'TRUE'";
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
?>
