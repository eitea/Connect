<?php
require "connection.php";
require "createTimestamps.php";
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
  $sql = "CREATE TABLE $adminGitHubTable(
    sslVerify ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'
  )";
  if ($conn->query($sql)) {
    echo "Added gitConfigTable <br>";
  } else {
    echo mysqli_error($conn) .'<br>';
  }

  $sql = "INSERT INTO $adminGitHubTable (sslVerify) VALUES('TRUE')";
  if ($conn->query($sql)) {
    echo "Added gitConfigTable <br>";
  } else {
    echo mysqli_error($conn) .'<br>';
  }
}

if($row['version'] < 21){
  $sql = "ALTER TABLE $userTable ADD COLUMN coreTime TIME DEFAULT '8:00'";
  if ($conn->query($sql)) {
    echo "Added coreTime <br>";
  } else {
    echo mysqli_error($conn) .'<br>';
  }
}

if($row['version'] < 22){
  $sql = "ALTER TABLE $vacationTable MODIFY COLUMN vacationHoursCredit DECIMAL(6,2) DEFAULT 0";
  if ($conn->query($sql)) {
    echo "Adjust vacation credit<br>";
  } else {
    echo mysqli_error($conn) .'<br>';
  }
}

if($row['version'] < 23){
  $sql = "UPDATE $logTable SET breakCredit = 0 WHERE status != '0'";
  $conn->query($sql);

  $sql = "SELECT * FROM $logTable WHERE status = '1'";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()){
    $absolvedHours = timeDiff_Hours($row['time'], $row['timeEnd']);
    if($absolvedHours != $row['expectedHours']){
      $adjustedTime = carryOverAdder_Hours($row['time'], floor($row['expectedHours']));
      $adjustedTime = carryOverAdder_Minutes($adjustedTime, (($row['expectedHours'] * 60) % 60));
      $sql = "UPDATE $logTable SET timeEnd = '$adjustedTime' WHERE indexIM =" .$row['indexIM'];
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  }

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
}

if($row['version'] < 24){
  $sql = "CREATE TABLE $piConnTable(
    header VARCHAR(50)
  )";
  if($conn->query($sql)){
    echo "Created config table for  terminals. <br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql = "INSERT INTO $piConnTable (header) VALUES ('EI-TEA Zeiterfassung v13 Code3A5B')";
  if($conn->query($sql)){
    echo "Insert user-agent for config table. <br>";
  } else {
    echo mysqli_error($conn);
  }

  $sql = "ALTER TABLE $userTable ADD COLUMN terminalPin INT(8) DEFAULT 4321";
  if($conn->query($sql)){
    echo "Add PIN for Users. <br>";
  } else {
    echo mysqli_error($conn);
  }
}

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

//------------------------------------------------------------------------------
require 'version_number.php';
$sql = "UPDATE $adminLDAPTable SET version=$VERSION_NUMBER";
$conn->query($sql);
header("refresh:8;url=adminHome.php");
die ('<br>Update Finished. Click here if not redirected automatically: <a href="adminHome.php">redirect</a>');
