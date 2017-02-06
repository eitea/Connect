<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<?php
/*
* To add a new Update: increase the version number in version_number.php. For more information see head of setup_inc.php
*/
require  "connection.php";
require  "createTimestamps.php";
include 'validate.php';
denyToCloud();

$sql = "SELECT * FROM $adminLDAPTable;";
$result = mysqli_query($conn, $sql);
$row = $result->fetch_assoc();

if($row['version'] < 42){
  if($conn->query("ALTER TABLE $userTable MODIFY COLUMN preferredLang ENUM('ENG', 'GER', 'FRA', 'ITA') DEFAULT 'GER'")){
    echo "<br> Changed preferred language to default GER";
  }

  //upsie.
  $sql = "DROP EVENT daily_logs_event";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "<br> Updated Daily log event.";
  }

  $sql = "CREATE EVENT IF NOT EXISTS `daily_logs_event`
  ON SCHEDULE EVERY 1 DAY STARTS '2016-09-01 23:00:00' ON COMPLETION PRESERVE ENABLE
  COMMENT 'Log absent sessions at 23:00 daily!'
  DO
  INSERT INTO $negative_logTable (time, userID, mon, tue, wed, thu, fri, sat, sun)
  SELECT UTC_TIMESTAMP, userID, mon, tue, wed, thu, fri, sat, sun
  FROM $userTable u
  INNER JOIN $bookingTable ON u.id = $bookingTable.userID
  WHERE !EXISTS (
    SELECT * FROM $logTable, $userTable u2
    WHERE DATE(time) = CURDATE()
    AND $logTable.userID = u2.id
    AND u.id = u2.id
  );";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  // remove all unlogs and logs before entry date
  $sql = "SELECT * FROM $userTable";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()){
    $user = $row['id'];
    $entryDate = $row['beginningDate'];

    $sql = "DELETE FROM $negative_logTable WHERE userID = $user AND time < '$entryDate'";
    $conn->query($sql);
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    }

    $sql = "DELETE FROM $logTable WHERE userID = $user AND time <= '$entryDate'";
    $conn->query($sql);
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    }
  }
  echo "<br> Removed all Absent logs before entrance date.";
  echo "<br> Removed all check ins before entrance date.";

  //fix unlogs for id = 1
  for($i = '2016-06-01 23:59:00'; substr($i,0, 10) != substr(carryOverAdder_Hours(getCurrentTimestamp(), 24),0, 10); $i = carryOverAdder_Hours($i, 24)){
    $conn->query("INSERT INTO $negative_logTable (time, userID, mon, tue, wed, thu, fri, sat, sun)
    SELECT '$i', userID, mon, tue, wed, thu, fri, sat, sun
    FROM $userTable u
    INNER JOIN $bookingTable ON u.id = $bookingTable.userID
    WHERE u.id = 1
    AND !EXISTS (
      SELECT * FROM $logTable, $userTable u2
      WHERE DATE(time) = DATE('$i')
      AND $logTable.userID = u2.id
      AND u.id = u2.id
    );");
    echo mysqli_error($conn);
  }
  echo "<br> Repaired absent log for admin.";
}

if($row['version'] < 43){
  $conn->query("ALTER TABLE $clientDetailTable MODIFY COLUMN name VARCHAR(45)");
  echo mysqli_error($conn);

  $conn->query("DELETE FROM $clientDetailTable");
  $conn->query("DELETE FROM $clientDetailBankTable");
  $conn->query("DELETE FROM $clientDetailNotesTable");

  echo mysqli_error($conn);
  echo "Cleared detail Table";

  if($conn->query("INSERT INTO $clientDetailTable (clientID) SELECT id FROM $clientTable")){
    echo "<br>Re-Added Customerdetails for every existing customer.";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 44){
  if($conn->query("ALTER TABLE $configTable ADD COLUMN enableReadyCheck ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'")){
    echo "<br>Added enable/disable Value for Ready Check.";
  } else {
    echo mysqli_error($conn);
  }
}

if($row['version'] < 45){
  $conn->query("DELETE FROM $clientDetailNotesTable");
  $conn->query("DELETE FROM $clientDetailBankTable");
  echo "<br>Starting Process for short table cleanup.......  Process completed.<br>";
}

if($row['version'] < 46){
  $sql="CREATE TABLE $moduleTable (
    enableTime ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    enableProject ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "Created module enable/disable function";
  }
  $conn->query("INSERT INTO $moduleTable (enableTime, enableProject) VALUES('TRUE', 'TRUE')");
  echo mysqli_error($conn);
}

if($row['version'] < 47){
  $sql = "CREATE TABLE $policyTable (
    passwordLength INT(2) DEFAULT 0,
    complexity ENUM('0', '1', '2') DEFAULT '0',
    expiration ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    expirationDuration INT(3),
    expirationType ENUM('ALERT', 'FORCE') DEFAULT 'ALERT'
    )";
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    } else {
      echo "<br> Created Passwordpolicy table";
    }

    $conn->query("INSERT INTO $policyTable (passwordLength) VALUES (0)");
    echo mysqli_error($conn);

    $sql = "ALTER TABLE $userTable ADD COLUMN lastPswChange DATETIME DEFAULT CURRENT_TIMESTAMP";
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    } else {
      echo "<br> Added expiration Date to PSW";
    }
}

if($row['version'] < 48){
  $sql = "ALTER TABLE $roleTable ADD COLUMN canEditTemplates ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "<br> Added new Role for editing PDF Templates";
  }
}

if($row['version'] < 49){
  $sql = "ALTER TABLE $configTable ADD COLUMN masterPassword VARCHAR(100)";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "<br> Added master Password for encrypting banking information.";
  }
  $sql = "ALTER TABLE $roleTable ADD COLUMN isReportAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added Report-Admin role.";
  }
}

if($row['version'] < 50){
  $sql = "CREATE TABLE $mailOptionsTable(
    host VARCHAR(50),
    username VARCHAR(50),
    password VARCHAR(50),
    port VARCHAR(50)
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Created table for email options.";
  }
  $conn->query("INSERT INTO $mailOptionsTable (port) VALUES ('25')");
}

if($row['version'] < 51){
  $sql = "ALTER TABLE $clientDetailBankTable ADD COLUMN iv VARCHAR(100)";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added random key encryption.";
  }
}

if($row['version'] < 52){
  $sql = "ALTER TABLE $clientDetailBankTable MODIFY COLUMN bic VARCHAR(200)";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Modified table for encryption.";
  }

  $conn->query("ALTER TABLE $clientDetailBankTable MODIFY COLUMN iban VARCHAR(200)");

  $sql = "ALTER TABLE $clientDetailBankTable ADD COLUMN iv2 VARCHAR(50)";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added random key random key encryption.";
  }
  $conn->query("ALTER TABLE $clientDetailBankTable MODIFY COLUMN iv VARCHAR(150)");
}

if($row['version'] < 53){
  $sql = "CREATE TABLE $mailReportsTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    htmlMail TEXT,
    repeatCount VARCHAR(50)
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added table for saving e-mail reports.";
  }

  $sql = "CREATE TABLE $mailReportsRecipientsTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reportID INT(6) UNSIGNED,
    email VARCHAR(50) NOT NULL,
    name VARCHAR(50),
    FOREIGN KEY (reportID) REFERENCES $mailReportsTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Created table for list of e-mail recipients. Report specific.";
  }
}

//------------------------------------------------------------------------------
require 'version_number.php';
$sql = "UPDATE $adminLDAPTable SET version=$VERSION_NUMBER";
$conn->query($sql);
header("refresh:6;url=home.php");
die ('<br>Update Finished. Click here if not redirected automatically: <a href="home.php">redirect</a>');
