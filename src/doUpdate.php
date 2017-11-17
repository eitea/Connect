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
require  "connection.php";
require  "utilities.php";
include 'validate.php';

$sql = "SELECT * FROM $adminLDAPTable;";
$result = mysqli_query($conn, $sql);
$row = $result->fetch_assoc();

if($row['version'] < 50){
  $sql = "CREATE TABLE $mailOptionsTable(
    host VARCHAR(50),
    username VARCHAR(50),
    password VARCHAR(50),
    port VARCHAR(50)
  )";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Created table for email options.";
  }
  $conn->query("INSERT INTO $mailOptionsTable (port) VALUES ('25')");
}

if($row['version'] < 51){
  $sql = "ALTER TABLE $clientDetailBankTable ADD COLUMN iv VARCHAR(100)";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Added random key encryption.";
  }
}

if($row['version'] < 52){
  $sql = "ALTER TABLE $clientDetailBankTable MODIFY COLUMN bic VARCHAR(200)";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Modified table for encryption.";
  }

  $conn->query("ALTER TABLE $clientDetailBankTable MODIFY COLUMN iban VARCHAR(200)");

  $sql = "ALTER TABLE $clientDetailBankTable ADD COLUMN iv2 VARCHAR(50)";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Added random key random key encryption.";
  }
  $conn->query("ALTER TABLE $clientDetailBankTable MODIFY COLUMN iv VARCHAR(150)");
}

if($row['version'] < 54){
  $sql = "ALTER TABLE $mailOptionsTable ADD COLUMN smtpSecure ENUM('', 'tls', 'ssl') DEFAULT 'tls'";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Added smtpSecure option.";
  }

  $sql = "ALTER TABLE $pdfTemplateTable ADD COLUMN repeatCount VARCHAR(50)";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Created table for list of e-mail recipients. Report specific.";
  }

  $sql = "ALTER TABLE $mailOptionsTable ADD COLUMN sender VARCHAR(50) DEFAULT 'noreplay@mail.com'";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Enabled Option for email logging.";
  }

  $sql = "CREATE TABLE $auditLogsTable(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    changeTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    changeStatement TEXT NOT NULL
  )";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Created table for email logging.";
  }
}

if($row['version'] < 57){
  $sql = "SET GLOBAL event_scheduler=ON";
  if (!$conn->query($sql)) {
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
  }

  $sql = "CREATE EVENT IF NOT EXISTS `daily_vacation_event`
  ON SCHEDULE EVERY 1 DAY STARTS '2016-09-01 23:30:00' ON COMPLETION PRESERVE ENABLE
  COMMENT 'Adding hours to vacationTable 23:00 daily!'
  DO
  UPDATE $vacationTable SET vacationHoursCredit = vacationHoursCredit + ((daysPerYear / 365) * 24)";
  if (!$conn->query($sql)) {
    echo '<br>'.$conn->error;
  }

  echo "<br>Recreated Events";

  $sql="SELECT userID, daysPerYear, beginningDate  FROM $userTable INNER JOIN $vacationTable ON $userTable.id = $vacationTable.userID";
  $result = $conn->query($sql);
  echo '<br>'.$conn->error;
  while($row = $result->fetch_assoc()){
    $time = $row['daysPerYear'] / 365;
    $time *= timeDiff_Hours(substr($row['beginningDate'],0,11) .'05:00:00', substr(getCurrentTimestamp(),0,11) .'05:00:00');
    $sql = "UPDATE $vacationTable SET vacationHoursCredit = '$time' WHERE userID = " . $row['userID'];
    $conn->query($sql);
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
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

    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Added table for adjustments.";
  }
}

if($row['version'] < 63){
  $sql = "ALTER TABLE $pdfTemplateTable ADD COLUMN userIDs VARCHAR(200)";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Added column for userIDs to be included in reports.";
  }
}

if($row['version'] < 64){
  $sql = "UPDATE $projectBookingTable SET bookingType = 'break' WHERE bookingType = '' OR bookingType IS NULL";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
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
      echo '<br>'.$conn->error;
    }
  }
  echo "<br>Inserted complete (!) lunchbreaks";

  //correct wrong expectedHours on all timestamps that are not 0s.
  $conn->query("UPDATE $logTable SET expectedHours = (TIMESTAMPDIFF(MINUTE, time, timeEnd) / 60) WHERE status != '0' AND TIMESTAMPDIFF(MINUTE, time, timeEnd) - expectedHours*60 != 0 ");
  echo "<br>Corrected wrong expected hours to all not-checkin timestamps ";
}

if($row['version'] < 65){
  $sql = "ALTER TABLE $correctionTable ADD COLUMN cType VARCHAR(10) NOT NULL DEFAULT 'log'";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Added column for correction vacation / log";
  }

  $sql = "ALTER TABLE $correctionTable ADD COLUMN createdOn DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Created interval table";
  }

  $sql = "INSERT INTO $intervalTable (startDate, mon, tue, wed, thu, fri, sat, sun, vacPerYear, overTimeLump, pauseAfterHours, hoursOfRest, userID)
  SELECT beginningDate, mon, tue, wed, thu, fri, sat, sun, daysPerYear, overTimeLump, pauseAfterHours, hoursOfRest, $userTable.id
  FROM $userTable INNER JOIN $vacationTable ON $vacationTable.userID = $userTable.id INNER JOIN $bookingTable ON $bookingTable.userID = $userTable.id";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Inserted $intervalTable";
  }

  $sql = "DROP TABLE $negative_logTable";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Dropped unlogs";
  }
  $sql = "DROP EVENT IF EXISTS daily_logs_event ";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Dropped unlog event";
  }
  $sql = "DROP EVENT IF EXISTS daily_vacation_event ";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Dropped vacation event";
  }
  $sql = "DROP TABLE $bookingTable";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Dropped bookingTable";
  }
  $sql = "DROP TABLE $vacationTable";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Dropped deactivated negative logs";
  }

  $conn->query("ALTER TABLE $deactivatedUserTable DROP COLUMN overTimeLump;");
  $conn->query("ALTER TABLE $deactivatedUserTable DROP COLUMN pauseAfterHours;");
  $conn->query("ALTER TABLE $deactivatedUserTable DROP COLUMN hoursOfrest;");

  echo "<br> Removed a few interval-columns from usertable";

  $sql = "ALTER TABLE $deactivatedUserLogs DROP COLUMN expectedHours";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Removed expected Hours from negative logs.";
  }
}

if($row['version'] < 69){
  $sql = "ALTER TABLE $projectBookingTable ADD UNIQUE KEY double_submit (timestampID, start, end)";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Added unique key for duplicate entries.";
  }

  $sql = "DELETE p1 FROM $projectBookingTable p1, $projectBookingTable p2 WHERE p1.id < p2.id AND p1.timestampID = p2.timestampID AND p1.start = p2.start AND p1.infoText = p2.infoText AND p1.end = p2.end";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Deleted all duplicates from projectbooking table.";
  }
}

if($row['version'] < 70){
  $sql = "ALTER TABLE $userTable ADD COLUMN real_email VARCHAR(50)";
  if (!$conn->query($sql)){
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
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
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Created relationship table for teams and users.";
  }
}

if($row['version'] < 72){
  $sql = "ALTER TABLE $userRequests MODIFY COLUMN toDate DATETIME";
  if (!$conn->query($sql)) {
    echo '<br>'.$conn->error;
  } else {
    echo "<br>Alter request table to match expansion";
  }
  $sql = "ALTER TABLE $userRequests ADD COLUMN requestType ENUM('vac', 'log', 'acc') DEFAULT 'vac'";
  if (!$conn->query($sql)) {
    echo '<br>'.$conn->error;
  } else {
    echo "<br>Expanded request type table";
  }
  $sql="ALTER TABLE $userRequests ADD COLUMN requestID INT(10) DEFAULT 0";
  if (!$conn->query($sql)) {
    echo '<br>'.$conn->error;
  } else {
    echo "<br>Added volatile request ID to request Table";
  }

  $sql = "ALTER TABLE $configTable ADD COLUMN enableReg ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'";
  if (!$conn->query($sql)) {
    echo '<br>'.$conn->error;
  } else {
    echo "<br>Added DB check for enabling self registration";
  }
}

if($row['version'] < 73){
  $sql = "DELETE FROM $userRequests WHERE requestType = 'log'";
  if($conn->query($sql)){
    echo '<br> Removed possible wrong log-requests';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 74){
  $conn->query("ALTER TABLE $projectBookingTable ADD COLUMN extra_1 VARCHAR(200) NULL DEFAULT NULL");
  $conn->query("ALTER TABLE $projectBookingTable ADD COLUMN extra_2 VARCHAR(200) NULL DEFAULT NULL");
  $sql = "ALTER TABLE $projectBookingTable ADD COLUMN extra_3 VARCHAR(200) NULL DEFAULT NULL";
  if($conn->query($sql)){
    echo '<br> Added three optional booking fields to logs';
  } else {
    echo '<br>'.$conn->error;
  }

//through my mapping from companyID to extraField ID, this is possible
  $conn->query("ALTER TABLE $projectTable ADD COLUMN field_1 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
  $conn->query("ALTER TABLE $projectTable ADD COLUMN field_2 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
  $sql = "ALTER TABLE $projectTable ADD COLUMN field_3 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if($conn->query($sql)){
    echo '<br> Added three additional fields to projects';
  } else {
    echo '<br>'.$conn->error;
  }

  $conn->query("ALTER TABLE $companyDefaultProjectTable ADD COLUMN field_1 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
  $conn->query("ALTER TABLE $companyDefaultProjectTable ADD COLUMN field_2 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
  $sql = "ALTER TABLE $companyDefaultProjectTable ADD COLUMN field_3 ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if($conn->query($sql)){
    echo '<br> Added three additional fields to default projects';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "CREATE TABLE $companyExtraFieldsTable (
    id INT(6) UNSIGNED PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    name VARCHAR(25) NOT NULL,
    isActive ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isRequired ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    isForAllProjects ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
    description VARCHAR(50),
    FOREIGN KEY (companyID) REFERENCES $companyTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo '<br> Created table to save additional fields to companies';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "RENAME TABLE companyToClientRelationshipData TO $companyToUserRelationshipTable";
  if($conn->query($sql)){
    echo '<br> Quick table rename';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 75){
  $sql = "CREATE TABLE $taskTable(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    repeatPattern ENUM('-1', '0', '1', '2', '3', '4') DEFAULT '-1',
    runtime DATETIME DEFAULT CURRENT_TIMESTAMP,
    description VARCHAR(200),
    lastRuntime DATETIME DEFAULT CURRENT_TIMESTAMP,
    callee VARCHAR(50) NOT NULL
  )";
  if($conn->query($sql)){
    echo '<br> Added task schedules';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 76){
  $sql = "ALTER TABLE $companyDefaultProjectTable ADD UNIQUE KEY name_company (name, companyID)";
  if($conn->query($sql)){
    echo '<br> Added unqiue constraint';
  } else {
    echo '<br>'.$conn->error;
  }
  echo '<br> Table cleanup';
}

if($row['version'] < 77){
  $conn->query("DELETE p1 FROM projectData p1, projectData p2 WHERE p1.clientID = p2. clientID AND p1.name = p2.name AND p1.id > p2.id");
  echo '<br> Dropped possible duplicates in projects';
  $sql = "ALTER TABLE projectData ADD UNIQUE KEY name_client (name, clientID)";
  if($conn->query($sql)){
    echo '<br> Added unqiue constraint';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE $companyDefaultProjectTable MODIFY COLUMN hourlyPrice DECIMAL(6,2) DEFAULT 0";
  if($conn->query($sql)){
    echo '<br> Increased price per hours to 4 digit number (default projects)';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE projectData MODIFY COLUMN hourlyPrice DECIMAL(6,2) DEFAULT 0";
  if($conn->query($sql)){
    echo '<br> Increased price per hours to 4 digit number (projects)';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 78){
  $sql = "CREATE TABLE proposals (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(10) NOT NULL,
    clientID INT(6) UNSIGNED,
    status ENUM('0', '1', '2') DEFAULT '0',
    FOREIGN KEY (clientID) REFERENCES $clientTable(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo '<br> Created table for proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "CREATE TABLE products(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proposalID INT(6) UNSIGNED,
    name VARCHAR(50),
    description VARCHAR(600),
    price DECIMAL(10,2),
    quantity DECIMAL(8,2),
    FOREIGN KEY (proposalID) REFERENCES proposals(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo '<br> Created table for products';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE companyData ADD COLUMN logo VARCHAR(20)";
  if($conn->query($sql)){
    echo '<br> Added logo to companies';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE roles ADD COLUMN isERPAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if($conn->query($sql)){
    echo '<br> Added ERP Admin';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 79){
  $sql = "ALTER TABLE companyData ADD COLUMN address VARCHAR(100)";
  if($conn->query($sql)){
    echo '<br> Added address to companies';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE companyData ADD COLUMN phone VARCHAR(100)";
  if($conn->query($sql)){
    echo '<br> Added phone number to companies';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE companyData ADD COLUMN mail VARCHAR(100)";
  if($conn->query($sql)){
    echo '<br> Added email address to companies';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE companyData ADD COLUMN homepage VARCHAR(100)";
  if($conn->query($sql)){
    echo '<br> Added homepage to companies';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE companyData ADD COLUMN erpText TEXT";
  if($conn->query($sql)){
    echo '<br> Added ERP footer Text to companies';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE companyData MODIFY COLUMN logo VARCHAR(40)";
  if($conn->query($sql)){
    echo '<br> Larger logo names to companies';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 81){
  $sql = "ALTER TABLE products ADD COLUMN taxPercentage INT(3) UNSIGNED";
  if($conn->query($sql)){
    echo '<br> Added taxes to products';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE logs MODIFY COLUMN status INT(3)";
  if($conn->query($sql)){
    echo '<br> Log savetype changes...';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "UPDATE logs SET status = (status - 2)";
  if($conn->query($sql)){
    echo '<br> ... Recalculations';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE userRequestsData MODIFY COLUMN requestType ENUM('vac', 'log', 'acc', 'scl', 'spl', 'brk') DEFAULT 'vac'";
  if($conn->query($sql)){
    echo '<br> Extended request types';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 82){
  $sql = "ALTER TABLE projectBookingData MODIFY COLUMN bookingType ENUM('project', 'break', 'drive', 'mixed')";
  if($conn->query($sql)){
    echo '<br> Extended booking Types';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE projectBookingData ADD COLUMN mixedStatus INT(3) DEFAULT -1";
  if($conn->query($sql)){
    echo '<br> Added mixed status';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql =  "ALTER TABLE projectBookingData ADD COLUMN exp_info TEXT";
  if($conn->query($sql)){
    echo '<br> Added expenses: description';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql =  "ALTER TABLE projectBookingData ADD COLUMN exp_price DECIMAL(10,2)";
  if($conn->query($sql)){
    echo '<br> Added expenses: price';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql =  "ALTER TABLE projectBookingData ADD COLUMN exp_unit DECIMAL(10,2)";
  if($conn->query($sql)){
    echo '<br> Added expenses: quantity';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 83){
  $sql = "ALTER TABLE UserData ADD COLUMN coreTime TIME DEFAULT '08:00:00'";
  if($conn->query($sql)){
    echo '<br> Added user Core Time';
  } //no elso in here
}

if($row['version'] < 84){
  $sql = "ALTER TABLE proposals ADD COLUMN curDate DATETIME DEFAULT CURRENT_TIMESTAMP";
  if($conn->query($sql)){
    echo '<br> Added date to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN deliveryDate DATETIME";
  if($conn->query($sql)){
    echo '<br> Added date of delivery to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN yourSign VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Added sign 1 to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN yourOrder VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Added order to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN ourSign VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Added sign 2 to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN ourMessage VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Added mesasge to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE proposals ADD COLUMN daysNetto INT(4)";
  if($conn->query($sql)){
    echo '<br> Added days Netto to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN skonto1 DECIMAL(6,2)";
  if($conn->query($sql)){
    echo '<br> Added skonto 1 to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN skonto2 DECIMAL(6,2)";
  if($conn->query($sql)){
    echo '<br> Added skonto 2 to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN skonto1Days INT(4)";
  if($conn->query($sql)){
    echo '<br> Added days to skonto 1 to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN skonto2Days INT(4)";
  if($conn->query($sql)){
    echo '<br> Added days to skonto 2 to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN paymentMethod VARCHAR(100)";
  if($conn->query($sql)){
    echo '<br> Added payment method to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN shipmentType VARCHAR(100)";
  if($conn->query($sql)){
    echo '<br> Added shipment type to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN representative VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Added representative to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE proposals ADD COLUMN porto DECIMAL(8,2)";
  if($conn->query($sql)){
    echo '<br> Added porto to Proposals';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE clientInfoData ADD COLUMN firstname VARCHAR(45)";
  if($conn->query($sql)){
    echo '<br> Splitting name to first and lastname in client data';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE clientInfoData ADD COLUMN address_Country_Postal VARCHAR(20)";
  if($conn->query($sql)){
    echo '<br> Splitting postal code off country in client data';
  } else {
    echo '<br>'.$conn->error;
  }
  $sql = "ALTER TABLE clientInfoData ADD COLUMN address_Country_City VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Splitting city off country in client data';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE clientInfoData ADD COLUMN fax_number VARCHAR(30)";
  if($conn->query($sql)){
    echo '<br> Added fax number to client data';
  } else {
    echo '<br>'.$conn->error;
  }

  //fixing additional fields activity
  $result = $conn->query("SELECT * FROM companyDefaultProjects");
  while($result && ($row = $result->fetch_assoc())){
    $conn->query("UPDATE projectData p1 SET field_1 = '".$row['field_1']."', field_2 = '".$row['field_2']."', field_3 = '".$row['field_3']."'
    WHERE clientID IN (SELECT clientData.id FROM clientData, companyDefaultProjects
    WHERE clientData.companyID = companyDefaultProjects.companyID AND p1.name = companyDefaultProjects.name AND companyDefaultProjects.id = ".$row['id'].")");
  }
  if($conn->query($sql)){
    echo '<br> Fixed additional field assigning';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 85){
  $sql = "ALTER TABLE intervalData MODIFY COLUMN overTimeLump DECIMAL(5,2) DEFAULT 0.0";
  if($conn->query($sql)){
    echo '<br> Max overtime from 99.00 to 999.00';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE logs DROP COLUMN breakCredit";
  if($conn->query($sql)){
    echo '<br> Removed break field';
  } else {
    echo '<br>'.$conn->error;
  }

  $conn->query("DELETE FROM projectBookingData WHERE start = '0000-00-00 00:00:00'");

  $sql = "CREATE TABLE mixedInfoData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestampID INT(10) UNSIGNED,
    status INT(3),
    timeStart DATETIME,
    timeEnd DATETIME,
    isFillable ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    FOREIGN KEY (timestampID) REFERENCES $logTable(indexIM)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo '<br> Additional info storage for mixed timestamps';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE clientInfoData MODIFY COLUMN taxnumber VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Changed tax number to text in client details';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE clientInfoData ADD COLUMN vatnumber VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Added VAT number to client details';
  } else {
    echo '<br>'.$conn->error;
  }

  $result = $conn->query("SELECT * FROM logs WHERE status = '5'"); //select all mixed timestamps
  while($result && ($row = $result->fetch_assoc())){
    //select all mixed bookings
    $bookings_result = $conn->query("SELECT * FROM projectBookingData WHERE bookingType = 'mixed' AND timestampID = ".$row['indexIM']);
    if($bookings_result && ($booking_row = $bookings_result->fetch_assoc())){
      //correct starting time
      $conn->query("UPDATE logs SET time = '".$booking_row['end']."' WHERE indexIM = ".$row['indexIM']);
      //enter full hours
      $A = substr_replace($row['time'], '08:00', 11, 5);
      $B = carryOverAdder_Hours($A, 9);
      $conn->query("INSERT INTO mixedInfoData (timestampID, status, timeStart, timeEnd) VALUES(".$row['indexIM'].", '".$booking_row['mixedStatus']."', '$A', '$B')");
      //remove the mixed bookings
      $conn->query("DELETE FROM projectBookingData WHERE id = ". $booking_row['id']);
    }
  }

  $sql = "ALTER IGNORE TABLE projectbookingdata ADD UNIQUE double_submit (timestampID, start, end)";
  if($conn->query($sql)){
    echo '<br> Remove all the duplicate entries and create a key';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE $deactivatedUserLogs DROP COLUMN breakCredit";
  if($conn->query($sql)){
    echo '<br> Remove breaks from deactivated logs';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE DeactivatedUserLogData MODIFY COLUMN status INT(3)";
  if($conn->query($sql)){
    echo '<br> Changed status';
  } else {
    echo '<br>'.$conn->error;
  }

  $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN mixedStatus INT(3) DEFAULT -1");
  $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN extra_1 VARCHAR(200) NULL DEFAULT NULL");
  $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN extra_2 VARCHAR(200) NULL DEFAULT NULL");
  $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN extra_3 VARCHAR(200) NULL DEFAULT NULL");
  $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN exp_info TEXT");
  $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN exp_price DECIMAL(10,2)");
  $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN exp_unit DECIMAL(10,2)");

  $sql = "ALTER TABLE DeactivatedUserProjectData MODIFY COLUMN bookingType ENUM('project', 'break', 'drive', 'mixed')";
  if($conn->query($sql)){
    echo '<br> Expanded deactivated booking type';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 86){
  $conn->query("DROP TABLE IF EXISTS taxRates");
  $sql = "CREATE TABLE taxRates (
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(200),
    percentage INT(3)
  )";
  if($conn->query($sql)){
    echo '<br> Created table for tax rates';
  }

  $conn->query("DROP TABLE IF EXISTS articles");
  $sql = "CREATE TABLE articles (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    description VARCHAR(600),
    price DECIMAL(10,2),
    unit VARCHAR(20),
    taxPercentage INT(3)
  )";
  if($conn->query($sql)){
    echo '<br> Created article list for products';
  }

  $sql = "ALTER TABLE products ADD COLUMN unit VARCHAR(20)";
  if($conn->query($sql)){
    echo '<br> Added units to products';
  }

  $sql = "ALTER TABLE proposals ADD COLUMN history VARCHAR(100)";
  if($conn->query($sql)){
    echo '<br> Added transitions to proposals';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE proposals ADD COLUMN portoRate INT(3)";
  if($conn->query($sql)){
    echo '<br> Added percentage to porto in proposals';
  } else {
    echo '<br>'.$conn->error;
  }

  $conn->query("UPDATE logs SET timeToUTC = 2 WHERE timeToUTC = 0 AND status = 5 ");

  $conn->query("DELETE FROM projectBookingData WHERE bookingType = 'mixed'");

  if(mysqli_error($conn)){
    echo '<br>'.$conn->error;
  } else {
    echo "<br> Repaired wrong booking types";
  }

  $conn->query("ALTER TABLE proposals MODIFY COLUMN status INT(2)");

  $sql = "UPDATE proposals SET status = status - 1";
  if($conn->query($sql)){
    echo '<br> Extended status of proposals';
  } else {
    echo '<br>'.$conn->error;
  }
}


if($row['version'] < 87){
  $sql = "ALTER TABLE userRequestsData MODIFY COLUMN requestType ENUM('vac', 'log', 'acc', 'scl', 'spl', 'brk', 'cto') DEFAULT 'vac'";
  if($conn->query($sql)){
    echo '<br> Added compensatory time';
  }

  $sql = "ALTER TABLE companyData ADD COLUMN detailLeft VARCHAR(180)";
  if($conn->query($sql)){
    echo '<br> Added left-bound details';
  }

  $sql = "ALTER TABLE companyData ADD COLUMN detailMiddle VARCHAR(180)";
  if($conn->query($sql)){
    echo '<br> Added centered details';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE companyData ADD COLUMN detailRight VARCHAR(180)";
  if($conn->query($sql)){
    echo '<br> Added right-bound details';
  }

  $sql = "ALTER TABLE companyData ADD COLUMN uid VARCHAR(20)";
  if($conn->query($sql)){
    echo '<br> Added UID to company data';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE companyData ADD COLUMN cmpDescription VARCHAR(50)";
  if($conn->query($sql)){
    echo '<br> Added name to company data';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE companyData ADD COLUMN companyPostal VARCHAR(20)";
  if($conn->query($sql)){
    echo '<br> Added postal code to company data';
  } else {
    echo '<br>'.$conn->error;
  }
}
if($row['version'] < 88){
  $sql = "ALTER TABLE companyData ADD COLUMN companyCity VARCHAR(20)";
  if($conn->query($sql)){
    echo '<br> Added city field to company data';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 89){
  $sql = "ALTER TABLE products ADD COLUMN cash ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if($conn->query($sql)){
    echo '<br> Added expenses in cash checkbox';
  }

  $sql = "CREATE TABLE units (
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    unit VARCHAR(10) NOT NULL
  )";
  if($conn->query($sql)){
    echo '<br> Units created';
  }

  $conn->query("INSERT INTO units (name, unit) VALUES('Stück', 'Stk')");
  $conn->query("INSERT INTO units (name, unit) VALUES('Packungen', 'Pkg')");
  $conn->query("INSERT INTO units (name, unit) VALUES('Stunden', 'h')");
  $conn->query("INSERT INTO units (name, unit) VALUES('Gramm', 'g')");
  $conn->query("INSERT INTO units (name, unit) VALUES('Kilogramm', 'kg')");
  $conn->query("INSERT INTO units (name, unit) VALUES('Meter', 'm')");
  $conn->query("INSERT INTO units (name, unit) VALUES('Kilometer', 'km')");
  $conn->query("INSERT INTO units (name, unit) VALUES('Quadratmeter', 'm2')");
  $conn->query("INSERT INTO units (name, unit) VALUES('Kubikmeter', 'm3')");

  $sql = "ALTER TABLE userRequestsData MODIFY COLUMN requestType ENUM('vac', 'log', 'acc', 'scl', 'spl', 'brk', 'cto', 'div') DEFAULT 'vac'";
  if($conn->query($sql)){
    echo '<br> Extended requests by splitted lunchbreaks';
  }
}

if($row['version'] < 90){
  $sql = "ALTER TABLE products ADD COLUMN purchase DECIMAL(10,2)";
  if($conn->query($sql)){
    echo '<br> Added purchase price to products';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE articles ADD COLUMN cash ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
  if($conn->query($sql)){
    echo '<br> Update articles to Database';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE articles ADD COLUMN purchase DECIMAL(10,2)";
  if($conn->query($sql)){
    echo '<br> Added purchase price to articles';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 92){
  $sql = "ALTER TABLE $userTable ADD COLUMN color VARCHAR(10) DEFAULT 'default'";
  if($conn->query($sql)){
    echo '<br> Color Picker';
  } else {
    echo '<br>'.$conn->error;
  }
}

if($row['version'] < 93){
  $sql = "CREATE TABLE paymentMethods (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
  )";
  if($conn->query($sql)){
    echo '<br>Payment Methods';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "CREATE TABLE representatives (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50)
  )";
  if($conn->query($sql)){
    echo '<br>Representatives';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "CREATE TABLE shippingMethods (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
  )";
  if($conn->query($sql)){
    echo '<br>Shipping Methods';
  } else {
    echo '<br>'.$conn->error;
  }

  //insert payment method
  $sql = "INSERT INTO paymentMethods (name) VALUES ('Überweisung')";
  $conn->query($sql);
  //insert shippign method
  $sql = "INSERT INTO shippingMethods (name) VALUES ('Abholer')";
  $conn->query($sql);

}

if($row['version'] < 94){
  $result = $conn->query("SELECT logo, id FROM companyData");
  $conn->query("UPDATE companyData SET logo = NULL");

  $sql = "ALTER TABLE companyData MODIFY COLUMN logo MEDIUMBLOB";
  if($conn->query($sql)){
    echo '<br>Company Logo store replace reference by BLOB';
  } else {
    echo '<br>'.$conn->error;
  }

  $stmt = $conn->prepare("UPDATE companyData SET logo = ? WHERE id = ?");
  $null = NULL;
  $stmt->bind_param("bi", $null, $cmpID);
  while($row = $result->fetch_assoc()){
    if($row['logo'] && file_exists($row['logo'])){
      $cmpID = $row['id'];
      $fp = fopen($row['logo'], "r");
      while (!feof($fp)) {
        $stmt->send_long_data(0, fread($fp, 8192));
      }
      fclose($fp);
      $stmt->execute();
      if($stmt->errno){ echo $stmt->error;}
      unlink($row['logo']);
    }
  }
  $stmt->close();

  $sql = "ALTER TABLE products ADD COLUMN position INT(4)";
  if($conn->query($sql)){
    echo '<br>product position';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE products ADD UNIQUE INDEX(position, proposalID)";
  if($conn->query($sql)){
    echo '<br>Company Logo store replace reference by BLOB';
  } else {
    echo '<br>'.$conn->error;
  }

  $proposal = $i = 0;
  $result = $conn->query("SELECT id, proposalID FROM products ORDER BY proposalID ASC");
  while($row = $result->fetch_assoc()){
    if($proposal != $row['proposalID']){
      $i = 1;
      $proposal = $row['proposalID'];
    }
    $conn->query("UPDATE products SET position = $i WHERE id = ".$row['id']);
    $i++;
  }
}


if($row['version'] < 95){
  $sql = "ALTER TABLE UserData ADD COLUMN erpOption VARCHAR(10) DEFAULT 'TRUE'";
  if($conn->query($sql)){
    echo '<br>Added option to display overall balance in ERP';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "ALTER TABLE userRequestsData ADD COLUMN timeToUTC INT(2) DEFAULT 0";
  if($conn->query($sql)){
    echo '<br>Added UTC value to requests';
  } else {
    echo '<br>'.$conn->error;
  }

  $sql = "CREATE TABLE erpNumbers(
    companyID INT(6) UNSIGNED,
    erp_ang INT(5) DEFAULT 1,
    erp_aub INT(5) DEFAULT 1,
    erp_re INT(5) DEFAULT 1,
    erp_lfs INT(5) DEFAULT 1,
    erp_gut INT(5) DEFAULT 1,
    erp_stn INT(5) DEFAULT 1,
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo '<br>Create ERP numbers';
  } else {
    echo '<br>'.$conn->error;
  }
  $conn->query("INSERT INTO erpNumbers (erp_ang, erp_aub, erp_re, erp_lfs, erp_gut, erp_stn, companyID) SELECT 1, 1, 1, 1, 1, 1, id FROM companyData");
}

if ($row['version'] < 97) {
  if ($conn->query("ALTER TABLE roles ADD COLUMN canUseSocialMedia ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'")) {
    echo '<br>Added role "canUseSocialMedia" with default "FALSE"';
  } else {
    echo '<br>' . $conn->error;
  }
  if ($conn->query("CREATE TABLE socialprofile(
    userID INT(6) UNSIGNED,
    isAvailable ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
    status varchar(150) DEFAULT '-',
    picture MEDIUMBLOB,
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )")) {
    echo '<br>Added table socialprofile';
  } else {
    echo '<br>' . $conn->error;
  }
  if ($conn->query("CREATE TABLE socialmessages(
    userID INT(6) UNSIGNED,
    partner INT(6) UNSIGNED,
    message TEXT,
    picture MEDIUMBLOB,
    sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    seen ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
  )")) {
    echo '<br>Added table socialmessage';
  } else {
    echo '<br>' . $conn->error;
  }
  if ($conn->query("ALTER TABLE modules ADD COLUMN enableSocialMedia ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'")){
    echo '<br>Added role "enableSocialMedia" with default "TRUE"';
  } else {
    echo '<br>' . $conn->error;
  }

  if ($conn->query("CREATE TABLE socialgroups(
    groupID INT(6) UNSIGNED,
    userID INT(6) UNSIGNED,
    name VARCHAR(30),
    admin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
  )")) {
    echo '<br>Added table socialgroups';
  } else {
    echo '<br>' . $conn->error;
  }
  if ($conn->query("CREATE TABLE socialgroupmessages(
    userID INT(6) UNSIGNED,
    groupID INT(6) UNSIGNED,
    message TEXT,
    picture MEDIUMBLOB,
    sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    seen TEXT
  )")) {
    echo '<br>Added table socialgroupmessages';
  } else {
    echo '<br>' . $conn->error;
  }
  $result = $conn->query("SELECT * FROM UserData");
  while ($result && ($row = $result->fetch_assoc())) {
    $x = $row["id"];
    if(!$conn->query("INSERT INTO socialprofile (userID, isAvailable, status) VALUES($x, 'TRUE', '-')")){
      echo '<br>' . $conn->error;
    }
  }
}

if($row['version'] < 99){
  $conn->query("ALTER TABLE DeactivatedUserData ADD COLUMN vacPerYear INT(2)");
  $conn->query("UPDATE DeactivatedUserData SET vacPerYear = daysPerYear");
  $conn->query("ALTER TABLE DeactivatedUserData DROP COLUMN daysPerYear");


  //step1: delete all autocorrections
  $conn->query("DELETE FROM projectBookingData WHERE infoText = 'Admin Autocorrected Lunchbreak'");

  $sql = "SELECT l1.*, pauseAfterHours, hoursOfRest FROM logs l1
  INNER JOIN UserData ON l1.userID = UserData.id INNER JOIN intervalData ON UserData.id = intervalData.userID
  WHERE (status = '0' OR status ='5') AND endDate IS NULL AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, timeEnd) > (pauseAfterHours * 60)
  AND hoursOfRest * 60 > (SELECT IFNULL(SUM(TIMESTAMPDIFF(MINUTE, start, end)),0) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = l1.indexIM)";
  $result = $conn->query($sql);
  while($result && ($row = $result->fetch_assoc())){
    //step2: get the difference, the time of the last booking and simply append whats missing.
    $indexIM = $row['indexIM'];
    $result_book = $conn->query("SELECT end FROM projectBookingData WHERE timestampID = $indexIM ORDER BY start DESC");
    if($result_book && ($row_book = $result_book->fetch_assoc())){
      $row_break['breakCredit'] = 0;
      $result_break = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, start, end)) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = $indexIM");
      if($result_break && $result_break->num_rows > 0) $row_break = $result_break->fetch_assoc();
      $missingBreak = intval($row['hoursOfRest'] * 60 - $row_break['breakCredit']);
      if($missingBreak < 0 || $missingBreak > $row['hoursOfRest']*60) { echo $missingBreak .' '.$indexIM; }
      $break_begin = $row_book['end'];
      $break_end = carryOverAdder_Minutes($break_begin, $missingBreak);
      $conn->query("INSERT INTO projectBookingData (start, end, bookingType, infoText, timestampID) VALUES ('$break_begin', '$break_end', 'break', 'Admin Autocorrected Lunchbreak', $indexIM)");
      echo mysqli_error($conn);
    } else {
      $break_begin = carryOverAdder_Minutes($row['time'], $row['pauseAfterHours'] * 60);
      $break_end = carryOverAdder_Minutes($break_begin, $row['hoursOfRest'] * 60);
      $conn->query("INSERT INTO projectBookingData (start, end, bookingType, infoText, timestampID) VALUES ('$break_begin', '$break_end', 'break', 'Admin Autocorrected Lunchbreak', $indexIM)");
      echo mysqli_error($conn);
    }
  }
}

if($row['version'] < 100){
  //worst case scenario update: its not a wrong charset. its replaced special characters.
  //fix holidays
  $conn->query("DELETE FROM holidays");

  function icsToArray($paramUrl) {
    $icsFile = file_get_contents($paramUrl);
    $icsData = explode("BEGIN:", $icsFile);
    foreach ($icsData as $key => $value) {
      $icsDatesMeta[$key] = explode("\n", $value);
    }
    foreach ($icsDatesMeta as $key => $value) {
      foreach ($value as $subKey => $subValue) {
        if ($subValue != "") {
          if ($key != 0 && $subKey == 0) {
            $icsDates[$key]["BEGIN"] = $subValue;
          } else {
            $subValueArr = explode(":", $subValue, 2);
            $icsDates[$key][$subValueArr[0]] = $subValueArr[1];
          }
        }
      }
    }
    return $icsDates;
  }

  $holidayFile = __DIR__ . '/setup/Feiertage.txt';
  $holidayFile = icsToArray($holidayFile);
  for($i = 1; $i < count($holidayFile); $i++){
    if(trim($holidayFile[$i]['BEGIN']) == "VEVENT"){
      $start = substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 0, 4) ."-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 6, 2) . " 00:00:00";
      $end = substr($holidayFile[$i]['DTEND;VALUE=DATE'], 0, 4) ."-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 6, 2) . " 20:00:00";
      $n = $holidayFile[$i]['SUMMARY'];
      $conn->query("INSERT INTO holidays(begin, end, name) VALUES ('$start', '$end', '$n');");
      echo $conn->error;
    }
  }

  if($conn->error){
    echo $conn->error;
  } else {
    echo '<br>Repaired Wrong Charactersets';
  }
}

if($row['version'] < 101){
  $conn->query("ALTER TABLE articles ADD COLUMN iv VARCHAR(255)");
  $conn->query("ALTER TABLE articles ADD COLUMN iv2 VARCHAR(255)");
  $conn->query("ALTER TABLE articles CHANGE name name VARCHAR(255)"); //50 -> 255
  $conn->query("ALTER TABLE articles CHANGE description description VARCHAR(1200)"); //600 -> 1200
  $conn->query("ALTER TABLE products ADD COLUMN iv VARCHAR(255)");
  $conn->query("ALTER TABLE products ADD COLUMN iv2 VARCHAR(255)");
  $conn->query("ALTER TABLE products CHANGE name name VARCHAR(255)"); //50 -> 255
  $conn->query("ALTER TABLE products CHANGE description description VARCHAR(600)"); //300 -> 600
  $conn->query("UPDATE configurationData set masterPassword = ''");

  $conn->query("CREATE TABLE resticconfiguration(
    path VARCHAR(255),
    password VARCHAR(255),
    awskey VARCHAR(255),
    awssecret VARCHAR(255),
    location VARCHAR(255)
  )");
  $conn->query("INSERT INTO resticconfiguration () VALUES ()");
}

if($row['version'] < 102){
  $result = $conn->query("SELECT * FROM projectBookingData WHERE infoText LIKE '%_?_%'");
  $pool = array('ä', 'ö', 'ü');
  while($row = $result->fetch_assoc()){
    $letter = $pool[rand(0, 2)];
    $newText = str_ireplace('f?', 'fü', $row['infoText']);
    $newText = str_ireplace('l?r', 'lär', $newText);
    $newText = str_ireplace('s?tz', 'sätz', $newText);
    $newText = str_ireplace('tr?g', 'träg', $newText);
    $newText = str_ireplace('w?', 'wö', $newText);
    $newText = str_ireplace('k?', 'kö', $newText);
    $newText = str_ireplace('m?', 'mö', $newText);
    $newText = str_ireplace('l?', 'lö', $newText);
    $newText = str_ireplace('z?', 'zü', $newText);
    $newText = str_ireplace('sch?', 'schö', $newText);
    $newText = str_ireplace('?b', 'üb', $newText);
    $newText = str_ireplace('r?', 'rü', $newText);
    $newText = str_ireplace('?nd', 'änd', $newText);
    $newText = str_ireplace('?', $letter, $newText);
    $conn->query("UPDATE projectBookingData SET infoText = '$newText' WHERE id = ".$row['id'] );
  }
  if($conn->error){
    echo $conn->error;
  } else {
    echo '<br>Repaired Wrong Charactersets';
  }
}

if($row['version'] < 103){
  $sql = "CREATE TABLE identification(
    id VARCHAR(60) UNIQUE NOT NULL
  )";
  if($conn->query($sql)){
    echo '<br> Created identification table';
  }

  $identifier = str_replace('.', '0', randomPassword().uniqid('', true).randomPassword().uniqid('').randomPassword()); //60 characters;
  $conn->query("INSERT INTO identification (id) VALUES ('$identifier')");
  if($conn->query($sql)){
    echo '<br> Insert unique ID';
  }
}

if($row['version'] < 104){
  $conn->query("ALTER TABLE paymentMethods ADD COLUMN daysNetto INT(4)");
  $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto1 DECIMAL(6,2)");
  $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto2 DECIMAL(6,2)");
  $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto1Days INT(4)");
  $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto2Days INT(4)");

  if($conn->error){
    echo $conn->error;
  } else {
    echo '<br>Zahlungsmethoden Update';
  }
}

if($row['version'] < 105){
  $conn->query("ALTER TABLE proposals DROP COLUMN yourSign");
  $conn->query("ALTER TABLE proposals DROP COLUMN yourOrder");
  $conn->query("ALTER TABLE proposals DROP COLUMN ourMessage");
  $conn->query("ALTER TABLE proposals DROP COLUMN ourSign");
  if($conn->error){
    echo $conn->error;
  } else {
    echo '<br>ERP Bezugszeichenzeile aus Aufträge entfernt';
  }

  $conn->query("ALTER TABLE erpNumbers ADD COLUMN yourSign VARCHAR(30)");
  $conn->query("ALTER TABLE erpNumbers ADD COLUMN yourOrder VARCHAR(30)");
  $conn->query("ALTER TABLE erpNumbers ADD COLUMN ourMessage VARCHAR(30)");
  $conn->query("ALTER TABLE erpNumbers ADD COLUMN ourSign VARCHAR(30)");
  if($conn->error){
    echo $conn->error;
  } else {
    echo '<br>ERP Bezugszeichenzeile zu Mandant hinzugefügt';
  }

  $conn->query("ALTER TABLE proposals ADD COLUMN header VARCHAR(400)");
  $conn->query("ALTER TABLE proposals ADD COLUMN referenceNumrow VARCHAR(10)");
  if($conn->error){
    echo $conn->error;
  } else {
    echo '<br>ERP Kopftext und Bezugszeichenzeile an/aus Option';
  }

  $conn->query("ALTER TABLE clientInfoData DROP COLUMN daysNetto");
  $conn->query("ALTER TABLE clientInfoData DROP COLUMN skonto1");
  $conn->query("ALTER TABLE clientInfoData DROP COLUMN skonto2");
  $conn->query("ALTER TABLE clientInfoData DROP COLUMN skonto1Days");
  $conn->query("ALTER TABLE clientInfoData DROP COLUMN skonto2Days");

  $conn->query("ALTER TABLE proposals DROP COLUMN daysNetto");
  $conn->query("ALTER TABLE proposals DROP COLUMN skonto1");
  $conn->query("ALTER TABLE proposals DROP COLUMN skonto2");
  $conn->query("ALTER TABLE proposals DROP COLUMN skonto1Days");
  $conn->query("ALTER TABLE proposals DROP COLUMN skonto2Days");
  if($conn->error){
    echo $conn->error;
  } else {
    echo '<br>ERP: Zahlungsbedingung stark vereinfacht';
  }
}

if($row['version'] < 106){
  $conn->query("ALTER TABLE roles ADD COLUMN isFinanceAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' ");
  if($conn->error){
    echo $conn->error;
  } else {
    echo '<br>Finanzen: Admin Rolle hinzugefügt';
  }

  $sql = "CREATE TABLE accounts (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    num INT(4) UNSIGNED,
    name VARCHAR(50),
    type ENUM('1', '2', '3', '4') DEFAULT '1',
    UNIQUE KEY (companyID, num),
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo '<br>Finanzen: T-Konten hinzugefügt';
  } else {
    echo $conn->error;
  }

  $sql = "CREATE TABLE account_balance(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    docNum INT(6),
    payDate DATETIME,
    account INT(6) UNSIGNED,
    offAccount INT(6) UNSIGNED,
    info VARCHAR(70),
    tax INT(4) UNSIGNED,
    should DECIMAL(18,2),
    have DECIMAL(18,2),
    FOREIGN KEY (account) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (offAccount) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo '<br>Finanzen: Bank und Kassa hinzugefügt';
  } else {
    echo $conn->error;
  }

  $conn->query("ALTER TABLE taxRates ADD COLUMN account2 INT(4)");
  $conn->query("ALTER TABLE taxRates ADD COLUMN account3 INT(4)");
}

if($row['version'] < 107){  
  $sql = "CREATE TABLE projectBookingData_audit(
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    changedat DATETIME,
    bookingID INT(6) UNSIGNED,
    statement VARCHAR(100)
  )";
  if($conn->query($sql)){
    echo '<br>Audit: Projektbuchungen hinzugefügt';
  } else {
    echo $conn->error;
  }

  //DELIMITERS are client syntax
  $sql = "CREATE TRIGGER projectBookingData_update_trigger 
    BEFORE UPDATE ON projectBookingData
    FOR EACH ROW
  BEGIN
    SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
    IF @cnt >= 150 THEN 
      DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
    END IF;
    INSERT INTO projectBookingData_audit
    SET changedat = UTC_TIMESTAMP, bookingID = NEW.id, statement = CONCAT('UPDATE ', OLD.id);
  END";
  if($conn->query($sql)){
    echo '<br>Audit: 150 Zeilen Trigger für Projektbuchungen eingesetzt';
  } else {
    echo $conn->error;
  }

  $conn->query("CREATE TRIGGER projectBookingData_delete_trigger 
      BEFORE DELETE ON projectBookingData
      FOR EACH ROW
    BEGIN
      SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
      IF @cnt >= 150 THEN 
        DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
      END IF;
      INSERT INTO projectBookingData_audit
      SET changedat = UTC_TIMESTAMP, bookingID = OLD.id, statement = 'DELETE';  
    END");

  $conn->query("CREATE TRIGGER projectBookingData_insert_trigger 
      AFTER INSERT ON projectBookingData
      FOR EACH ROW
    BEGIN
      SELECT COUNT(*) INTO @cnt FROM projectBookingData_audit;
      IF @cnt >= 150 THEN 
        DELETE FROM projectBookingData_audit ORDER BY id LIMIT 1;
      END IF;
      INSERT INTO projectBookingData_audit
      SET changedat = UTC_TIMESTAMP, bookingID = NEW.id, statement = 'INSERT';  
    END");
}

if($row['version'] < 108){
  $sql = "CREATE TABLE account_journal(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6),
    taxID INT(4) UNSIGNED,
    docNum INT(6),
    payDate DATETIME,
    inDate DATETIME,
    account INT(6) UNSIGNED,
    offAccount INT(6) UNSIGNED,
    info VARCHAR(70),
    should DECIMAL(18,2),
    have DECIMAL(18,2),
    FOREIGN KEY (account) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (offAccount) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if($conn->query($sql)){
    echo '<br>Finanzen: Buchungsjournal hinzugefügt';
  } else {
    echo $conn->error;
  }

  $conn->query("ALTER TABLE accounts ADD COLUMN manualBooking VARCHAR(10) DEFAULT 'FALSE' ");
  if(!$conn->error){
    echo '<br>Finanzen: Steuern';
  } else {
    echo $conn->error;
  }

  $conn->query("DROP TABLE account_balance");
  $sql = "CREATE TABLE account_balance(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    journalID INT(10) UNSIGNED,
    accountID INT(4) UNSIGNED,
    should DECIMAL(18,2),
    have DECIMAL(18,2),  
    FOREIGN KEY (accountID) REFERENCES accounts(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,  
    FOREIGN KEY (journalID) REFERENCES account_journal(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo '<br>Buchhungsjournal Eintrag mit Buchungen verknüpft';
  }

  $conn->query("ALTER TABLE taxRates ADD COLUMN code INT(2)");
  echo $conn->error;
}

if($row['version'] < 109){
  $conn->query("ALTER TABLE companyData MODIFY column companyCity VARCHAR(60) ");
  if(!$conn->error){
    echo '<br>Mandant: 54 Zeichen Ort';
  } else {
    echo $conn->error;
  }
}

if($row['version'] < 110){
  //WEB
  $sql = "CREATE TABLE receiptBook(
    id INT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplierID INT(6) UNSIGNED,
    taxID INT(4) UNSIGNED,
    journalID INT(10) UNSIGNED,
    invoiceDate DATETIME,
    info VARCHAR(64),
    amount DECIMAL(10,2),
    FOREIGN KEY (supplierID) REFERENCES clientData(id)
    ON UPDATE CASCADE 
    ON DELETE CASCADE,
    FOREIGN KEY (journalID) REFERENCES account_journal(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo '<br>ERP: Wareneingangsbuch';
  }

  //suppliers
  $conn->query("ALTER TABLE clientData ADD COLUMN isSupplier VARCHAR(10) DEFAULT 'FALSE' ");
  if(!$conn->error){
    echo '<br>ERP: Lieferanten';
  } else {
    echo $conn->error;
  }

  //new accounts
  $conn->query("DELETE FROM accounts");
  $conn->query("ALTER TABLE accounts AUTO_INCREMENT = 1");
  $file = fopen(__DIR__.'/setup/Kontoplan.csv', 'r');
  if($file){
    $stmt = $conn->prepare("INSERT INTO accounts (companyID, num, name, type) SELECT id, ?, ?, ? FROM companyData");
    $stmt->bind_param("iss", $num, $name, $type);
    while(($line= fgetcsv($file, 300, ';')) !== false){
      $num = $line[0];
      $name = trim(iconv(mb_detect_encoding($line[1], mb_detect_order(), true), "UTF-8", $line[1]));
      if(!$name) $name = trim(iconv('MS-ANSI', "UTF-8", $line[1]));
      if(!$name) $name = $line[1];
      $type = trim($line[2]);
      $stmt->execute();
    }
    $stmt->close();
  } else {
    echo "<br>Error Opening csv File";
  }
  $conn->query("UPDATE accounts SET manualBooking = 'TRUE' WHERE name = 'Bank' OR name = 'Kassa' ");

  $sql = "CREATE TABLE closeUpData(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userID INT(6) UNSIGNED,
    lastDate DATETIME NOT NULL,
    saldo DECIMAL(6,2),
    FOREIGN KEY (userID) REFERENCES UserData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo '<br>Jahresabschlusstabelle hinzugefügt';
  }

  $conn->query("ALTER TABLE UserData ADD COLUMN strikeCount INT(3) DEFAULT 0");
  if(!$conn->error){
    echo '<br>Benutzer: Punktesystem';
  } else {
    echo $conn->error;
  }
}

if($row['version'] < 111){
  $i = 1;
  $conn->query("DELETE FROM taxRates");
  $file = fopen(__DIR__.'/setup/Steuerraten.csv', 'r');
  if($file){
    $stmt = $conn->prepare("INSERT INTO taxRates(id, description, percentage, account2, account3, code) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiii", $i, $name, $percentage, $account2, $account3, $code);
    while($line = fgetcsv($file, 100, ';')){
      $name = trim(iconv(mb_detect_encoding($line[0], mb_detect_order(), true), "UTF-8", $line[0]));
      if(!$name) $name = trim(iconv('MS-ANSI', "UTF-8", $line[0]));
      if(!$name) $name = $line[0];
      $percentage = $line[1];
      $account2 = $line[2] ? $line[2] : NULL;
      $account3 = $line[3] ? $line[3] : NULL;
      $code = $line[4] ? $line[4] : NULL;
      $stmt->execute();
      $i++;
    }
    $stmt->close();
    fclose($file);
  } else {
    echo "<br>Error Opening csv File";
  }
  if(!$conn->error){
    echo '<br>Finanzen: Neue Steuersätze';
  } else {
    echo $conn->error;
  }

  $sql = "CREATE TABLE accountingLocks(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    lockDate DATE NOT NULL,
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
  if (!$conn->query($sql)) {
    echo mysqli_error($conn);
  } else {
    echo '<br>Finanzen: Buchungsmonat-Sperre';
  }
}

if($row['version'] < 112){
  $conn->query("ALTER TABLE clientInfoData ADD COLUMN address_Addition VARCHAR(150)");
  if(!$conn->error){
    echo '<br>Kunden und Lieferanten: Adresszusatz';
  } else {
    echo $conn->error;
  }
}

if($row['version'] < 113){
  $conn->query("CREATE TABLE checkinLogs(
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestampID INT(10) UNSIGNED,
    remoteAddr VARCHAR(50),
    userAgent VARCHAR(150)
  )");
  if(!$conn->error){
    echo '<br>Checkin: IP und Header Logs';
  }

  $conn->query("ALTER TABLE logs ADD COLUMN emoji INT(2) DEFAULT 0 ");
  if(!$conn->error){
    echo '<br>Checkout: Emoji';
  }

  $conn->query("ALTER TABLE articles ADD COLUMN taxID INT(4) UNSIGNED");
  $conn->query("ALTER TABLE products ADD COLUMN taxID INT(4) UNSIGNED");
  $conn->query("UPDATE articles SET taxID = taxPercentage");
  $conn->query("ALTER TABLE articles DROP COLUMN taxPercentage");
  $conn->query("UPDATE products p1 SET taxID = (SELECT id FROM taxRates WHERE percentage = p1.taxPercentage LIMIT 1)");
  $conn->query("ALTER TABLE products DROP COLUMN taxPercentage");
}

if($row['version'] < 114){
  $conn->query("DELETE FROM account_balance");
  $result = $conn->query("SELECT account_journal.*, percentage, account2, account3, code FROM account_journal LEFT JOIN taxRates ON taxRates.id = taxID");
  echo $conn->error;

  $stmt = $conn->prepare("INSERT INTO account_balance (journalID, accountID, should, have) VALUES(?, ?, ?, ?)");
  echo $conn->error;
  $stmt->bind_param("iidd", $journalID, $account, $should, $have);

  while($row = $result->fetch_assoc()){    
    $addAccount = $row['account'];
    $offAccount = $row['offAccount'];
    $docNum = $row['docNum'];
    $date = $row['payDate'];
    $text = $row['info'];
    $should = $temp_should = $row['should'];
    $have = $temp_have = $row['have'];
    $tax = $row['taxID'];
    $journalID = $row['id'];

    $res = $conn->query("SELECT num FROM accounts WHERE id = $addAccount");
    if($res && ( $rowP = $res->fetch_assoc())) $accNum = $rowP['num'];

    //prepare balance
    $account2 = $account3 = '';
    if($row['account2']){
        $res = $conn->query("SELECT id FROM accounts WHERE num = ".$row['account2']." AND companyID IN (SELECT companyID FROM accounts WHERE id = $offAccount) "); echo $conn->error;
        if($res && $res->num_rows > 0) $account2 = $res->fetch_assoc()['id'];
    }
    if($row['account3']){
        $res = $conn->query("SELECT id FROM accounts WHERE num = ".$row['account3']." AND companyID IN (SELECT companyID FROM accounts WHERE id = $offAccount) "); echo $conn->error;
        if($res && $res->num_rows > 0) $account3 = $res->fetch_assoc()['id'];
    }

    //tax calculation
    if($account2 && $account3){
      $should_tax = $should * ($row['percentage'] / 100);
      $have_tax = $have * ($row['percentage'] / 100);
    } else {
      $should_tax = $should - ($should * 100) / (100 + $row['percentage']);
      $have_tax = $have - ($have * 100) / (100 + $row['percentage']);

    }
    
    $should = $temp_have;
    $have = $temp_should;
    //account balance
    if($account2){
      $should = $have_tax;
      $have = $should_tax;
      $account = $account2;
      $stmt->execute();
      if($account3){
        $should = $temp_have;
        $have = $temp_should;
      } else {
          $have = $temp_should - $should_tax;
          $should = $temp_have - $have_tax;
      }
    }
    $account = $offAccount;
    $stmt->execute();


    //offAccount balance
    $have = $temp_have;
    $should = $temp_should;
    if($account3){
      $have = $have_tax;
      $should = $should_tax;
      $account = $account3;
      $stmt->execute();
      if($account2){
          $should = $temp_should; 
          $have = $temp_have; 
      } else {
          $should = $temp_should - $should_tax;
          $have = $temp_have - $have_tax;
      }
    }
    $account = $addAccount;
    $stmt->execute();

  }
}

if($row['version'] < 115){
  $conn->query("DELETE a1 FROM account_journal a1, account_journal a2 WHERE a1.id > a2.id AND a1.userID = a2.userID AND a1.inDate = a2.inDate");

  $conn->query("ALTER TABLE account_journal ADD UNIQUE KEY double_submit (userID, inDate)");
  if(!$conn->error){
    echo '<br>Finanzen: Doppelte Buchungen Fix';
  }

  $conn->query("ALTER TABLE UserData ADD COLUMN keyCode VARCHAR(100)");
  if(!$conn->error){
    echo '<br>Verschlüsselung: Master Passwort aktualisiert';
  }
}

//------------------------------------------------------------------------------
require 'version_number.php';
$conn->query("UPDATE $adminLDAPTable SET version=$VERSION_NUMBER");
echo '<br><br>Update wurde beendet. Klicken sie auf "Weiter", wenn sie nicht automatisch weitergeleitet werden: <a href="../user/home">Weiter</a>';
?>
<script type="text/javascript">
  window.setInterval(function(){
    window.location.href="../user/home";
  }, 4000);
</script>

<noscript>
  <meta http-equiv="refresh" content="0;url='.$url.'" />';
</noscript>
</div>
</body>
</html>
