<?php
require  "connection.php";
require  "createTimestamps.php";
include 'validate.php';

session_start();
enableToCore($_SESSION['userid']);

ini_set('session.gc_max_lifetime', 0);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 1);

$sql = "SELECT * FROM $adminLDAPTable;";
$result = mysqli_query($conn, $sql);
  $row = $result->fetch_assoc();

if($row['version'] < 25){
  $sql="SELECT userID, daysPerYear, beginningDate  FROM $userTable INNER JOIN $vacationTable ON $userTable.id = $vacationTable.userID";
  $result = $conn->query($sql);
  echo mysqli_error($conn);
  while($row = $result->fetch_assoc()){
    $time = $row['daysPerYear'] / 365;

    $time *= timeDiff_Hours(substr($row['beginningDate'],0,11) .'05:00:00', substr(getCurrentTimestamp(),0,11) .'05:00:00');

    $sql = "UPDATE $vacationTable SET vacationHoursCredit = '$time' WHERE userID = " . $row['userID'];
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}

if($row['version'] < 26){
  $sql = "ALTER TABLE $clientTable MODIFY COLUMN name VARCHAR(60) NOT NULL";
  if($conn->query($sql)){
    echo "Bigger Name length for Clients. <br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql = "ALTER TABLE $projectTable MODIFY COLUMN name VARCHAR(60) NOT NULL";
  if($conn->query($sql)){
    echo "Bigger Name length for Projects. <br>";
  } else {
    echo mysqli_error($conn);
  }
  $sql = "ALTER TABLE $companyTable MODIFY COLUMN name VARCHAR(60) NOT NULL";
  if($conn->query($sql)){
    echo "Bigger Name length for Companies. <br>";
  } else {
    echo mysqli_error($conn);
  }
  $sql = "ALTER TABLE $holidayTable MODIFY COLUMN name VARCHAR(60) NOT NULL";
  if($conn->query($sql)){
    echo "Bigger Name length for Holidays. <br>";
  } else {
    echo mysqli_error($conn);
  }
  $sql = "ALTER TABLE $userTable MODIFY COLUMN email VARCHAR(50) UNIQUE NOT NULL";
  if($conn->query($sql)){
    echo "Bigger length for E-mails. <br>";
  } else {
    echo mysqli_error($conn);
  }
  $sql = "ALTER TABLE $companyDefaultProjectTable MODIFY COLUMN name VARCHAR(60) NOT NULL";
  if($conn->query($sql)){
    echo "Bigger name length for default projects. <br>";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 27){
  //add the lunchbreak bookings for each log of a normal user
  $sql = "SELECT time, timeEnd, pauseAfterHours, hoursOfRest, indexIM, id FROM $logTable INNER JOIN $userTable ON $logTable.userID = $userTable.id WHERE enableProjecting = 'FALSE' AND status = '0'"; //kek
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()){
    //for every single log if status = 0, and time lies over 6h and user cant book -> set the lunchbreak booking
    if(timeDiff_Hours($row['time'], $row['timeEnd']) > $row['pauseAfterHours']){
      //create the lunchbreak booking
      $start = $row['time'];
      $minutes = $row['hoursOfRest'] * 60;
      $indexIM = $row['indexIM'];
      $userID = $row['id'];

      $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText) VALUES('$start', DATE_ADD('$start', INTERVAL $minutes MINUTE), $indexIM, 'Lunchbreak for $userID')";
      $conn->query($sql);
      echo mysqli_error($conn);

      //update timestamp
      $sql = "UPDATE $logTable SET breakCredit = ".$row['hoursOfRest']." WHERE indexIM = $indexIM";
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  }
  //repair the unlogs
  $sql = "SELECT * FROM $userTable";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()){
    $userID = $row['id'];
    //fix1: remove all unlogs before entry date
    $entryDate = $row['beginningDate'];
    $sql = "DELETE FROM $negative_logTable WHERE userID = $userID AND time < '$entryDate'";
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}

if($row['version'] < 28){
  $sql = "CREATE TABLE $roleTable(
  userID INT(6) UNSIGNED,
  isCoreAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
  isTimeAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
  isProjectAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
  FOREIGN KEY (userID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo "Created Role Table. <br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql = "INSERT INTO $roleTable (userID) (SELECT id FROM $userTable)";
  if($conn->query($sql)){
    echo "Filled role Table. <br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql = "UPDATE $roleTable SET isCoreAdmin='TRUE' WHERE userID = 1";
  if($conn->query($sql)){
    echo "Updated role for the existing Admin. <br>";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 29){
  $sql = "ALTER TABLE $roleTable ADD COLUMN canStamp ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'";
  if($conn->query($sql)){
    echo "Updated roles - Add user role 1. <br>";
  } else {
    echo mysqli_error($conn);
  }
  $sql = "ALTER TABLE $roleTable ADD COLUMN canBook ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if($conn->query($sql)){
    echo "Updated roles - Add user role 2. <br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql = "UPDATE $roleTable INNER JOIN $userTable ON $roleTable.userID = $userTable.id SET canBook = 'TRUE' WHERE enableProjecting = 'TRUE'";
  if($conn->query($sql)){
    echo "Copy data to match user role.<br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql ="ALTER TABLE $userTable DROP COLUMN enableProjecting";
  if($conn->query($sql)){
    echo "Drop enableProjecting column <br>";
  } else {
    echo mysqli_error($conn);
  }
}



//------------------------------------------------------------------------------
require 'version_number.php';
$sql = "UPDATE $adminLDAPTable SET version=$VERSION_NUMBER";
$conn->query($sql);
header("refresh:6;url=home.php");
die ('<br>Update Finished. Click here if not redirected automatically: <a href="home.php">redirect</a>');
