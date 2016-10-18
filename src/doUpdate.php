<?php
require "connection.php";
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}

$sql = "SELECT * FROM $adminLDAPTable;";
$result = mysqli_query($conn, $sql);
  $row = $result->fetch_assoc();

if($row['version'] < 19){
  $sql = "ALTER TABLE $userRequests ADD COLUMN requestText VARCHAR(200)";
  if ($conn->query($sql)) {
    echo "Added reply text. <br>";
  } else {
    echo mysqli_error($conn) .'<br>';

  }

  $sql = "ALTER TABLE $userRequests ADD COLUMN answerText VARCHAR(200)";
  if ($conn->query($sql)) {
    echo "Added answer text <br>";
  } else {
    echo mysqli_error($conn) .'<br>';
  }
}

if($row['version'] < 20){

}

//------------------------------------------------------------------------------
header("refresh:8;url=adminHome.php");
die ('<br>Update Finished. Click here if not redirected automatically: <a href="adminHome.php">redirect</a>');
