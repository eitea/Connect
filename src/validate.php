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
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function enableToTime($userID){
  require 'connection.php';
  $sql = "SELECT isTimeAdmin FROM $roleTable WHERE userID = $userID AND isTimeAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function enableToProject($userID){
  require 'connection.php';
  $sql = "SELECT isProjectAdmin FROM $roleTable WHERE userID = $userID AND isProjectAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function enableToStamps($userID){
  require 'connection.php';
  $sql = "SELECT canStamp FROM $roleTable WHERE userID = $userID AND canStamp = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function enableToBookings($userID){
  require 'connection.php';
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND canBook = 'TRUE' AND canStamp = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function enableToTemplate($userID){
  require 'connection.php';
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND (isCoreAdmin = 'TRUE' OR canEditTemplates = 'TRUE')";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function enableToReport($userID){
  require 'connection.php';
  $sql = "SELECT isReportAdmin FROM $roleTable WHERE userID = $userID AND isReportAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function enableToERP($userID){
  require 'connection.php';
  $sql = "SELECT isERPAdmin FROM $roleTable WHERE userID = $userID AND isERPAdmin = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function enableToClients($userID){
  require 'connection.php';
  $sql = "SELECT isERPAdmin, isCoreAdmin FROM $roleTable WHERE userID = $userID AND (isERPAdmin = 'TRUE' OR isCoreAdmin = 'TRUE')";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> return</a>');
  }
}

function denyToContainer(){
  if(getenv('IS_Container') || isset($_SERVER['IS_Container'])){
    die("Feature not accessible in current environment.");
  }
}

function enableToSocialMedia($userID){
  require 'connection.php';
  if ($conn->query("SELECT enableSocialMedia FROM modules")->fetch_assoc()['enableSocialMedia'] === 'FALSE'){
    die('Module not enabled. <a href="../system/advanced">Enable</a>');
  }
  $sql = "SELECT * FROM $roleTable WHERE userID = $userID AND canUseSocialMedia = 'TRUE'";
  $result = $conn->query($sql);
  if($userID != 1 && (!$result || $result->num_rows <= 0)){
    die('Access denied. <a href="../user/logout"> log out</a> or <a href="../user/logout"> log out</a>');
  }
}

?>
