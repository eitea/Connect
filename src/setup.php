<?php
if(!isset($_SERVER['RDS_HOSTNAME'])){
  require 'connection_config.php';
  $conn = new mysqli($servername, $username, $password);
  if ($conn->connect_error) {
    echo "<br>Connection Error: Could not Connect.<a href='setup_getInput.php'>Click here to return to previous page.</a><br>";
    die();
  }
} else {
  require '';
}
// Create database
$sql = "CREATE DATABASE IF NOT EXISTS $dbName";
if ($conn->query($sql)) {
    echo "Database was created. <br>";
} else {
    echo "<br>Invalid Database name: Could not instantiate a database.<a href='setup_getInput.php'>Return</a><br>";
    die();
}
$conn->close();

require 'connection.php';

if(isset($_GET)){
  $psw = $_GET['psw'];
  $companyName = rawurldecode($_GET['companyName']);
  $firstname = rawurldecode($_GET['first']);
  $lastname = rawurldecode($_GET['last']);
  $loginname = rawurldecode($_GET['login']);
}

$sql = "CREATE TABLE $userTable (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  firstname VARCHAR(30) NOT NULL,
  lastname VARCHAR(30) NOT NULL,
  psw VARCHAR(60) NOT NULL,
  terminalPin INT(8) DEFAULT 4321,
  sid VARCHAR(50),
  email VARCHAR(50) UNIQUE NOT NULL,
  gender ENUM('female', 'male'),
  overTimeLump INT(3) DEFAULT 0,
  pauseAfterHours DECIMAL(4,2) DEFAULT 6,
  hoursOfRest DECIMAL(4,2) DEFAULT 0.5,
  beginningDate DATETIME DEFAULT CURRENT_TIMESTAMP,
  preferredLang ENUM('ENG', 'GER', 'FRA', 'ITA') DEFAULT 'GER',
  coreTime TIME DEFAULT '8:00'
)";
if ($conn->query($sql)) {
  echo "created user table. - ";
} else {
  echo mysqli_error($conn);
}

$sql = "INSERT INTO $userTable (firstname, lastname, email, psw) VALUES ('$firstname', '$lastname', '$loginname', '$psw');";
if ($conn->query($sql)) {
  echo "registered admin as first user. <br>";
} else {
  echo mysqli_error($conn);
}


/*
-1 .... absent (should not occur!)
0 ..... arrival
1 ..... vacation
2 ..... special leave
3 .... sickness
4 ..... time balancing
*/
$sql = "CREATE TABLE $logTable (
  indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  time DATETIME NOT NULL,
  timeEnd DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  status ENUM('-1', '0', '1', '2', '3', '4'),
  timeToUTC INT(2) DEFAULT '2',
  breakCredit	DECIMAL(4,2),
  userID INT(6) UNSIGNED,
  expectedHours DECIMAL(4,2),
  FOREIGN KEY (userID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "created logtable. <br>";
} else {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE  $adminLDAPTable (
  ldapConnect VARCHAR(30),
  ldapPassword VARCHAR(30),
  ldapUsername VARCHAR(30),
  adminID INT(6) UNSIGNED,
  version INT(5) DEFAULT 0,
  FOREIGN KEY (adminID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Ldap config table created. - ";
} else {
  echo mysqli_error($conn);
}

require 'version_number.php';
$sql = "INSERT INTO $adminLDAPTable (adminID, version) VALUES (1, $VERSION_NUMBER)";
if ($conn->query($sql)) {
  echo "Insert into ldap table. <br>";
} else {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $holidayTable(
  begin DATETIME,
  end DATETIME,
  name VARCHAR(60) NOT NULL
)";
if ($conn->query($sql)) {
  echo "Holiday Table Created. <br>";
}
$holi = icsToArray('../Feiertage.txt');
for($i = 1; $i < count($holi); $i++){
  if($holi[$i]['BEGIN'] == 'VEVENT'){
    $start = substr($holi[$i]['DTSTART;VALUE=DATE'], 0, 4) ."-" . substr($holi[$i]['DTSTART;VALUE=DATE'], 4, 2) . "-" . substr($holi[$i]['DTSTART;VALUE=DATE'], 6, 2) . " 00:00:00";
    $end = substr($holi[$i]['DTEND;VALUE=DATE'], 0, 4) ."-" . substr($holi[$i]['DTEND;VALUE=DATE'], 4, 2) . "-" . substr($holi[$i]['DTEND;VALUE=DATE'], 6, 2) . " 23:59:59";
    $n = $holi[$i]['SUMMARY'];

    $sql = "INSERT INTO $holidayTable(begin, end, name) VALUES ('$start', '$end', '$n');";
    $conn->query($sql);
  }
}

$sql = "CREATE TABLE $bookingTable(
  mon DECIMAL(4,2) DEFAULT 8.5,
  tue DECIMAL(4,2) DEFAULT 8.5,
  wed DECIMAL(4,2) DEFAULT 8.5,
  thu DECIMAL(4,2) DEFAULT 8.5,
  fri DECIMAL(4,2) DEFAULT 4.5,
  sat DECIMAL(4,2) DEFAULT 0,
  sun DECIMAL(4,2) DEFAULT 0,
  userID INT(6) UNSIGNED,
  FOREIGN KEY (userID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Added user booking table <br>";
}

$sql = "CREATE TABLE $companyTable (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL
)";
if ($conn->query($sql)) {
  echo "CompanyTable created. - ";
} else {
  echo mysqli_error($conn);
}


$sql = "INSERT INTO $companyTable (name) VALUES ('$companyName')";
if ($conn->query($sql)) {
  echo "Insert default Administration company. <br>";
} else {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $clientTable(
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(60) NOT NULL,
companyID INT(6) UNSIGNED,
clientNumber VARCHAR(12),
FOREIGN KEY (companyID) REFERENCES $companyTable(id)
ON UPDATE CASCADE
ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Added client table <br>";
}


$sql = "CREATE TABLE $projectTable(
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
clientID INT(6) UNSIGNED,
name VARCHAR(60) NOT NULL,
hours DECIMAL(5,2),
status VARCHAR(30),
hourlyPrice DECIMAL(4,2) DEFAULT 0,
FOREIGN KEY (clientID) REFERENCES $clientTable(id)
ON UPDATE CASCADE
ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Added project table <br>";
}


$sql = "CREATE TABLE $projectBookingTable (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  start DATETIME NOT NULL,
  end DATETIME DEFAULT '0000-00-00 00:00:00',
  projectID INT(6) UNSIGNED,
  timestampID INT(10) UNSIGNED,
  infoText VARCHAR(500),
  booked ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
  internInfo VARCHAR(500),
  FOREIGN KEY (projectID) REFERENCES $projectTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE,
  FOREIGN KEY (timestampID) REFERENCES $logTable(indexIM)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)){
  echo "ProjectBookingTable created! <br>";
} else {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $companyToUserRelationshipTable (
  companyID INT(6) UNSIGNED,
  userID INT(6) UNSIGNED,
  FOREIGN KEY (companyID) REFERENCES $companyTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE,
  FOREIGN KEY (userID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Company - User n:n Relationship created. <br>";
} else {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $companyDefaultProjectTable (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  companyID INT(6) UNSIGNED,
  hours INT(3),
  status VARCHAR(30),
  hourlyPrice DECIMAL(4,2) DEFAULT 0,
  FOREIGN KEY (companyID) REFERENCES $companyTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Default project table created. <br>";
} else {
  echo mysqli_error($conn);
}

$sql = "CREATE TABLE $negative_logTable(
  negative_indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  time DATETIME NOT NULL,
  userID INT(6) UNSIGNED,
  mon DECIMAL(4,2) DEFAULT 8.5,
  tue DECIMAL(4,2) DEFAULT 8.5,
  wed DECIMAL(4,2) DEFAULT 8.5,
  thu DECIMAL(4,2) DEFAULT 8.5,
  fri DECIMAL(4,2) DEFAULT 4.5,
  sat DECIMAL(4,2) DEFAULT 0,
  sun DECIMAL(4,2) DEFAULT 0,
  FOREIGN KEY (userID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Created absent-log table. <br>";
} else {
  echo mysqli_error($conn) .'<br>';
}


$sql = "SET GLOBAL event_scheduler=ON";
if ($conn->query($sql)) {
  echo "Activate global event scheduler. <br>";
} else {
  echo mysqli_error($conn) .'<br>';
}

$sql = "CREATE EVENT IF NOT EXISTS `daily_logs_event`
ON SCHEDULE EVERY 1 DAY STARTS '2016-09-01 23:00:00' ON COMPLETION PRESERVE ENABLE
COMMENT 'Log absent sessions at 23:00 daily!'
DO
INSERT INTO $negative_logTable (time, userID, mon, tue, wed, thu, fri, sat, sun)
SELECT UTC_TIMESTAMP, userID, mon, tue, wed, thu, fri, sat, sun
FROM $userTable u
INNER JOIN $bookingTable ON u.id = $bookingTable.userID
WHERE u.id != 1
AND !EXISTS (
  SELECT * FROM $logTable, $userTable u2
  WHERE DATE(time) = CURDATE()
  AND $logTable.userID = u2.id
  AND u.id = u2.id
);";

if ($conn->query($sql)) {
  echo "Procedure created. <br>";
} else {
  echo mysqli_error($conn) .'<br>';
}

$sql = "CREATE TABLE $configTable(
  bookingTimeBuffer INT(3) DEFAULT '5',
  cooldownTimer INT(3) DEFAULT '2'
)";
if ($conn->query($sql)) {
  echo "Created configuration Data. <br>";
} else {
  echo mysqli_error($conn) .'<br>';
}
$sql = "INSERT INTO $configTable (bookingTimeBuffer, cooldownTimer) VALUES (5, 2)";
$conn->query($sql);


$sql = "CREATE TABLE $vacationTable(
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  userID INT(6) UNSIGNED,
  vacationHoursCredit DECIMAL(6,2) DEFAULT 0,
  daysPerYear INT(2) DEFAULT 25,
  FOREIGN KEY (userID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Created Vacation Table. <br>";
} else {
  echo mysqli_error($conn) .'<br>';
}


$sql = "CREATE EVENT IF NOT EXISTS `daily_vacation_event`
ON SCHEDULE EVERY 1 DAY STARTS '2016-09-01 23:30:00' ON COMPLETION PRESERVE ENABLE
COMMENT 'Adding hours to vacationTable 23:00 daily!'
DO
UPDATE $vacationTable SET vacationHoursCredit = vacationHoursCredit + ((daysPerYear / 365) * 24)";
if ($conn->query($sql)) {
  echo "Event for Vacationtable created. <br>";
} else {
  echo mysqli_error($conn) .'<br>';
}


$sql = "CREATE TABLE $userRequests(
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  userID INT(6) UNSIGNED,
  fromDate DATETIME NOT NULL,
  toDate DATETIME NOT NULL,
  status ENUM('0', '1', '2') DEFAULT '0',
  requestText VARCHAR(150),
  answerText VARCHAR(150),
  FOREIGN KEY (userID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if ($conn->query($sql)) {
  echo "Created request table. <br>";
} else {
  echo mysqli_error($conn) .'<br>';
}


$sql = "CREATE TABLE $adminGitHubTable(
  sslVerify ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
)";
if ($conn->query($sql)) {
  echo "Added gitConfigTable - ";
} else {
  echo mysqli_error($conn) .'<br>';
}

$sql = "INSERT INTO $adminGitHubTable (sslVerify) VALUES('FALSE')";
if ($conn->query($sql)) {
  echo "Insert into gitConfigTable <br>";
} else {
  echo mysqli_error($conn) .'<br>';
}


$sql = "CREATE TABLE $piConnTable(
  header VARCHAR(50)
)";
if($conn->query($sql)){
  echo "Created config table for  terminals. - ";
} else {
  echo mysqli_error($conn);
}

$sql = "INSERT INTO $piConnTable(header) VALUES (' ')";
if($conn->query($sql)){
  echo "Insert statement for config table. <br>";
} else {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $roleTable(
userID INT(6) UNSIGNED,
isCoreAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
isTimeAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
isProjectAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
canStamp ENUM('TRUE', 'FALSE') DEFAULT 'TRUE',
canBook ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
FOREIGN KEY (userID) REFERENCES $userTable(id)
ON UPDATE CASCADE
ON DELETE CASCADE
)";
if($conn->query($sql)){
  echo "Created Role Table. - ";
} else {
  echo mysqli_error($conn);
}

$sql = "INSERT INTO $roleTable (userID, isCoreAdmin) VALUES (1, 'TRUE')";
if($conn->query($sql)){
  echo "Insert Admin for CORE. <br>";
} else {
  echo mysqli_error($conn);
}

$repositoryPath = dirname(dirname(realpath("setup.php")));

//git init
$command = 'git -C ' .$repositoryPath. ' init 2>&1';
exec($command, $output, $returnValue);

//sslyverify false
$command = 'git -C ' .$repositoryPath. ' config http.sslVerify "false" 2>&1';
exec($command, $output, $returnValue);

//remote add
$command = "git -C $repositoryPath remote add -t master origin https://github.com/eitea/T-Time.git 2>&1";
exec($command, $output, $returnValue);

$command = "git -C $repositoryPath fetch --force 2>&1";
exec($command, $output, $returnValue);

$command = "git -C $repositoryPath reset --hard origin/master 2>&1";
exec($command, $output, $returnValue);

echo implode('<br>', $output);


//------------------------------------------------------------------------------

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

//------------------------------------------------------------------------------
header("refresh:10;url=home.php");
die ('<br>Setup Finished. Click here if not redirected automatically: <a href="login.php">redirect</a>');
