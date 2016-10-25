<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}
require "connection.php";
require "createTimestamps.php";

$sql="SELECT userID, daysPerYear, beginningDate  FROM $userTable INNER JOIN $vacationTable ON $userTable.id = $vacationTable.userID";
$result = $conn->query($sql);
echo mysqli_error($conn);
while($row = $result->fetch_assoc()){
  $time = $row['daysPerYear'] / 365;

  $time *= timeDiff_Hours(substr($row['beginningDate'],0,11) .'05:00:00', substr(getCurrentTimestamp(),0,11) .'05:00:00')/24;

  $sql = "UPDATE $vacationTable SET vacationHoursCredit = '$time' WHERE userID = " . $row['userID'];
  $conn->query($sql);
  echo mysqli_error($conn);
}


?>
