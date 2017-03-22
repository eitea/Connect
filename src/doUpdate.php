<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<style>/*  HEADER AND CONTENT  */
html,body{
  padding-top: 25px;
  font: 13px/1.4 Geneva, 'Lucida Sans', 'Lucida Grande', 'Lucida Sans Unicode', Verdana, sans-serif;
  text-align: center;
  line-height:200%;
}
#progressBar_grey{
  margin-left:10%;
  width:80%;
  background-color:#ddd;
  border-radius:10px;
}
#progress{
  width: 0%;
  background-color: #ff9900;
  color: #ff9900;
  border-radius:10px;
}
#progress_text{
  z-index:10;
  background-color : transparent;
  position: absolute;
  left:50%;
  color:white;
}
</style>

<script>
document.onreadystatechange = function () {
  var state = document.readyState
  if (state == 'complete') {
    document.getElementById("content").style.display = "block";
  } else {
    move();
  }
}

function move() {
  var elem = document.getElementById("progress");
  var elem_text = document.getElementById("progress_text");
  var width = 10;
  var id = setInterval(frame, 20); //calling frame every Xms, 10ms = 1 second
  function frame() {
    if (width >= 100) {
      clearInterval(id);
    } else {
      width++;
      elem.style.width = width + '%';
      elem_text.innerHTML = width * 1  + '%';
    }
  }
}
</script>

<body>
  <div id="progressBar_grey">
    <div id="progress_text">0%</div>
    <div id="progress">.</div>
  </div>

  <div id="content" style="display:none;">
<br>
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

if($row['version'] < 54){
  $sql = "ALTER TABLE $mailOptionsTable ADD COLUMN smtpSecure ENUM('', 'tls', 'ssl') DEFAULT 'tls'";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added smtpSecure option.";
  }

  $sql = "ALTER TABLE $pdfTemplateTable ADD COLUMN repeatCount VARCHAR(50)";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added smtpSecure option.";
  }

  $conn->query("DROP TABLE $mailReportsRecipientsTable");
  $conn->query("DROP TABLE $mailReportsTable");

  $sql = "CREATE TABLE $mailReportsRecipientsTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reportID INT(6) UNSIGNED,
    email VARCHAR(50) NOT NULL,
    name VARCHAR(50),
    FOREIGN KEY (reportID) REFERENCES $pdfTemplateTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Created table for list of e-mail recipients. Report specific.";
  }

  $sql = "ALTER TABLE $mailOptionsTable ADD COLUMN sender VARCHAR(50) DEFAULT 'noreplay@mail.com'";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added sending address.";
  }
}

if($row['version'] < 55){
  $exampleTemplate = "<h1>Main Report</h1>
  <p>[REPEAT]</p>
  <p>[NAME]: [DATE] &nbsp;FROM &nbsp;[FROM] TO &nbsp;[TO]</p>
  <p>[INFOTEXT]</p>
  <p>[REPEAT END]</p>";
  $conn->query("INSERT INTO $pdfTemplateTable(name, htmlCode, repeatCount) VALUES('Main_Report', '$exampleTemplate', 'TRUE')");
  echo "<br> Added Example Report";

  $sql = "ALTER TABLE $mailOptionsTable ADD COLUMN enableEmailLog ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Enabled Option for email logging.";
  }

  $sql = "CREATE TABLE $auditLogsTable(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    changeTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    changeStatement TEXT NOT NULL
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Created table for audit logging.";
  }

  $sql = "CREATE TABLE $mailLogsTable(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timeSent DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sentTo VARCHAR(100),
    messageLog TEXT
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Created table for email logging.";
  }
}

if($row['version'] < 57){
  $sql = "SET GLOBAL event_scheduler=ON";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
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

  $sql = "CREATE EVENT IF NOT EXISTS `daily_vacation_event`
  ON SCHEDULE EVERY 1 DAY STARTS '2016-09-01 23:30:00' ON COMPLETION PRESERVE ENABLE
  COMMENT 'Adding hours to vacationTable 23:00 daily!'
  DO
  UPDATE $vacationTable SET vacationHoursCredit = vacationHoursCredit + ((daysPerYear / 365) * 24)";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  }

  echo "<br>Recreated Events";

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

if($row['version'] < 59){
  $conn->query("DELETE FROM $pdfTemplateTable WHERE name = 'Example_Report' OR name = 'Main_Report'");
  $exampleTemplate = "<h1>Main Report</h1>
  [TIMESTAMPS] <br>
  [BOOKINGS]
  ";
  $conn->query("INSERT INTO $pdfTemplateTable(name, htmlCode, repeatCount) VALUES('Main_Report', '$exampleTemplate', 'TRUE')");
  echo "<br> Changed Main Report";
}

if($row['version'] < 60){
  $sql = "ALTER TABLE $userTable ADD COLUMN emUndo DATETIME DEFAULT CURRENT_TIMESTAMP";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added column for emergency undos.";
  }
}

if($row['version'] < 61){
  $result = $conn->query("SELECT * FROM $projectBookingTable WHERE infoText LIKE 'Lunchbreak for %'");
  while($result && ($row = $result->fetch_assoc())){
    $id = $row['id'];
    $indexIM = $row['timestampID'];
    $minutes = timeDiff_Hours($row['start'], $row['end']) * 60;

    $res2 = $conn->query("SELECT time FROM $logTable WHERE indexIM = $indexIM");
    $rowTime = $res2->fetch_assoc();
    $start = $rowTime['time'];
    $conn->query("UPDATE $projectBookingTable SET start = '$start', end = DATE_ADD('$start', INTERVAL $minutes MINUTE) WHERE id = $id");

    echo mysqli_error($conn);
  }
}

if($row['version'] < 62){
  $sql = "CREATE TABLE $correctionTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    hours DECIMAL(6,2),
    infoText VARCHAR(350),
    addOrSub ENUM('1', '-1') NOT NULL,
    cOnDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES $userTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added table for adjustments.";
  }
}

if($row['version'] < 63){
  $sql = "ALTER TABLE $pdfTemplateTable ADD COLUMN userIDs VARCHAR(200)";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added column for userIDs to be included in reports.";
  }
}

if($row['version'] < 64){
  $sql = "UPDATE $projectBookingTable SET bookingType = 'break' WHERE bookingType = '' OR bookingType IS NULL";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Repaired missing Bookingtypes.";
  }
  if($conn->query("DELETE FROM $projectBookingTable WHERE bookingType = 'break' AND infoText LIKE 'Lunchbreak for %' ")){
    echo "<br>Deleted all old automatic lunchbreaks.";
  }
  $resultParent = $conn->query("SELECT * FROM $logTable l1 INNER JOIN $userTable ON l1.userID = $userTable.id
  WHERE status = '0' AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, timeEND) > (pauseAfterHours * 60)
  AND !EXISTS(SELECT id FROM $projectBookingTable WHERE timestampID = l1.indexIM AND bookingType = 'break' AND (hoursOfRest * 60 DIV 1) <= TIMESTAMPDIFF(MINUTE, start, end) )");
  while($resultParent && ($rowP = $resultParent->fetch_assoc())){
    $indexIM = $rowP['indexIM'];
    $result = $conn->query("SELECT time, pauseAfterHours, hoursOfRest FROM $userTable, $logTable WHERE indexIM = $indexIM AND userID = $userTable.id");
    if($result && ($row = $result->fetch_assoc())){
      $start = carryOverAdder_Minutes($row['time'], $row['pauseAfterHours'] * 60);
      $end = carryOverAdder_Minutes($start, $row['hoursOfRest'] * 60);
      $conn->query("INSERT INTO $projectBookingTable (timestampID, bookingType, start, end, infoText) VALUES($indexIM, 'break', '$start', '$end', 'Admin added missing lunchbreak')");
      echo mysqli_error($conn);
    }
  }
  echo "<br>Inserted complete (!) lunchbreaks";
  //recalculate all break values that need recalculating.
  $result = $conn->query("SELECT indexIM FROM $logTable WHERE status = '0'");
  while($result && ($row = $result->fetch_assoc())){
    $indexIM = $row['indexIM'];
    $resultBreak = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, start, end)) AS mySum FROM $projectBookingTable WHERE timestampID = $indexIM AND bookingType = 'break'");
    if($resultBreak && ($rowBreak = $resultBreak->fetch_assoc())){
      $hours = sprintf("%.2f", $rowBreak['mySum'] / 60);
      $conn->query("UPDATE $logTable SET breakCredit = '$hours' WHERE indexIM = $indexIM");
    }
  }
  echo "<br>Recalculated all breaks.";

  //correct wrong expectedHours on all timestamps that are not 0s.
  $conn->query("UPDATE $logTable SET expectedHours = (TIMESTAMPDIFF(MINUTE, time, timeEnd) / 60) WHERE status != '0' AND TIMESTAMPDIFF(MINUTE, time, timeEnd) - expectedHours*60 != 0 ");
  echo "<br>Corrected wrong expected hours to all not-checkin timestamps ";
}

if($row['version'] < 65){
  $sql = "ALTER TABLE $correctionTable ADD COLUMN cType VARCHAR(10) NOT NULL DEFAULT 'log'";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added column for correction vacation / log";
  }

  $sql = "ALTER TABLE $correctionTable ADD COLUMN createdOn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added column for correction date / log";
  }
}

if($row['version'] < 66){
  $sql = "CREATE TABLE $intervalTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    startDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    endDate DATETIME DEFAULT NULL,
    mon DECIMAL(4,2) DEFAULT 8.5,
    tue DECIMAL(4,2) DEFAULT 8.5,
    wed DECIMAL(4,2) DEFAULT 8.5,
    thu DECIMAL(4,2) DEFAULT 8.5,
    fri DECIMAL(4,2) DEFAULT 4.5,
    sat DECIMAL(4,2) DEFAULT 0,
    sun DECIMAL(4,2) DEFAULT 0,
    vacPerYear INT(2) DEFAULT 25,
    overTimeLump DECIMAL(4,2) DEFAULT 0.0,
    pauseAfterHours DECIMAL(4,2) DEFAULT 6,
    hoursOfRest DECIMAL(4,2) DEFAULT 0.5,
    userID INT(6) UNSIGNED,
    FOREIGN KEY (userID) REFERENCES $userTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Created interval table";
  }

  $sql = "INSERT INTO $intervalTable (startDate, mon, tue, wed, thu, fri, sat, sun, vacPerYear, overTimeLump, pauseAfterHours, hoursOfRest, userID)
  SELECT beginningDate, mon, tue, wed, thu, fri, sat, sun, daysPerYear, overTimeLump, pauseAfterHours, hoursOfRest, $userTable.id
  FROM $userTable INNER JOIN $vacationTable ON $vacationTable.userID = $userTable.id INNER JOIN $bookingTable ON $bookingTable.userID = $userTable.id";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Inserted $intervalTable";
  }

  $sql = "DROP TABLE $negative_logTable";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Dropped unlogs";
  }
  $sql = "DROP EVENT IF EXISTS daily_logs_event ";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Dropped unlog event";
  }
  $sql = "DROP EVENT IF EXISTS daily_vacation_event ";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Dropped vacation event";
  }
  $sql = "DROP TABLE $bookingTable";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Dropped bookingTable";
  }
  $sql = "DROP TABLE $vacationTable";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Dropped vacationTable";
  }

  $conn->query("ALTER TABLE $deactivatedUserDataTable DROP COLUMN vacationHoursCredit");
  $conn->query("ALTER TABLE $deactivatedUserDataTable ADD COLUMN overTimeLump DECIMAL(4,2) DEFAULT 0.0;");
  $conn->query("ALTER TABLE $deactivatedUserDataTable ADD COLUMN pauseAfterHours DECIMAL(4,2) DEFAULT 6;");
  $conn->query("ALTER TABLE $deactivatedUserDataTable ADD COLUMN hoursOfRest DECIMAL(4,2) DEFAULT 0.5;");
  $conn->query("ALTER TABLE $deactivatedUserDataTable ADD COLUMN startDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;");
  $conn->query("ALTER TABLE $deactivatedUserDataTable ADD COLUMN endDate DATETIME;");
  echo "<br> Modified table for deactivated intervaldata";
}

if($row['version'] < 67){
  $sql = "ALTER TABLE $logTable DROP COLUMN expectedHours";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Removed expected Hours from logs.";
  }

  $conn->query("ALTER TABLE $userTable DROP COLUMN overTimeLump");
  $conn->query("ALTER TABLE $userTable DROP COLUMN amVacDays;");
  $conn->query("ALTER TABLE $userTable DROP COLUMN pauseAfterHours;");
  $conn->query("ALTER TABLE $userTable DROP COLUMN hoursOfrest;");
  echo "<br> Removed a few interval-columns from usertable";

  $sql = "DROP TABLE $deactivatedUserUnLogs";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Dropped deactivated negative logs";
  }

  $conn->query("ALTER TABLE $deactivatedUserTable DROP COLUMN overTimeLump;");
  $conn->query("ALTER TABLE $deactivatedUserTable DROP COLUMN pauseAfterHours;");
  $conn->query("ALTER TABLE $deactivatedUserTable DROP COLUMN hoursOfrest;");

  echo "<br> Removed a few interval-columns from usertable";

  $sql = "ALTER TABLE $deactivatedUserLogs DROP COLUMN expectedHours";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Removed expected Hours from negative logs.";
  }
}

if($row['version'] < 69){
  $sql = "ALTER TABLE $projectBookingTable ADD UNIQUE KEY double_submit (timestampID, start, end)";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added unique key for duplicate entries.";
  }

  $sql = "DELETE p1 FROM $projectBookingTable p1, $projectBookingTable p2 WHERE p1.id < p2.id AND p1.timestampID = p2.timestampID AND p1.start = p2.start AND p1.infoText = p2.infoText AND p1.end = p2.end";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Deleted all duplicates from projectbooking table.";
  }
}

if($row['version'] < 70){
  $sql = "ALTER TABLE $userTable ADD COLUMN real_email VARCHAR(50)";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Added real email Adress to user.";
  }
}

if($row['version'] < 71){
  $sql = "CREATE TABLE $teamTable (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60),
    companyID INT(6) UNSIGNED,
    FOREIGN KEY (companyID) REFERENCES $companyTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)){
    echo mysqli_error($conn);
  } else {
    echo "<br> Created table for teams.";
  }

  $sql = "CREATE TABLE $teamRelationshipTable (
    teamID INT(6) UNSIGNED,
    userID INT(6) UNSIGNED,
    FOREIGN KEY (teamID) REFERENCES $teamTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (userID) REFERENCES $userTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "<br> Created relationship table for teams and users.";
  }
}

if($row['version'] < 72){
  $sql = "ALTER TABLE $userRequests MODIFY COLUMN toDate DATETIME";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "<br>Alter request table to match expansion";
  }
  $sql = "ALTER TABLE $userRequests ADD COLUMN requestType ENUM('vac', 'log', 'acc') DEFAULT 'vac'";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "<br>Expanded request type table";
  }
  $sql="ALTER TABLE $userRequests ADD COLUMN requestID INT(10) DEFAULT 0";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "<br>Added volatile request ID to request Table";
  }

  $sql = "ALTER TABLE $configTable ADD COLUMN enableReg ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo "<br>Added DB check for enabling self registration";
  }
}


//------------------------------------------------------------------------------
require 'version_number.php';
$sql = "UPDATE $adminLDAPTable SET version=$VERSION_NUMBER";
$conn->query($sql);
header("refresh:6;url=home.php");
die ('<br><br>Update Finished. Click here if not redirected automatically: <a href="home.php">redirect</a>');
?>
</div>
</body>
</html>
