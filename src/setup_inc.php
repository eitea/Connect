<?php
require_once "connection_vars.php";

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
  exitDate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  preferredLang ENUM('ENG', 'GER', 'FRA', 'ITA') DEFAULT 'GER',
  coreTime TIME DEFAULT '8:00',
  kmMoney DECIMAL(4,2) DEFAULT 0.42
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}


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
if (!$conn->query($sql)) {
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
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $holidayTable(
  begin DATETIME,
  end DATETIME,
  name VARCHAR(60) NOT NULL
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
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
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $companyTable (
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  companyType ENUM('GmbH', 'AG', 'OG', 'KG', 'EU', '-') DEFAULT '-'
)";
if (!$conn->query($sql)) {
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
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
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
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}

$sql = "CREATE TABLE $projectBookingTable (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  start DATETIME NOT NULL,
  end DATETIME NOT NULL,
  chargedTimeStart DATETIME DEFAULT '0000-00-00 00:00:00',
  chargedTimeEnd DATETIME DEFAULT '0000-00-00 00:00:00',
  projectID INT(6) UNSIGNED,
  timestampID INT(10) UNSIGNED,
  infoText VARCHAR(500),
  internInfo VARCHAR(500),
  booked ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
  bookingType ENUM('project', 'break', 'drive'),
  FOREIGN KEY (projectID) REFERENCES $projectTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE,
  FOREIGN KEY (timestampID) REFERENCES $logTable(indexIM)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
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
if (!$conn->query($sql)) {
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
if (!$conn->query($sql)) {
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
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}


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


$sql = "CREATE TABLE $configTable(
  bookingTimeBuffer INT(3) DEFAULT '5',
  cooldownTimer INT(3) DEFAULT '2'
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
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
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $adminGitHubTable(
  sslVerify ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}

$sql = "INSERT INTO $adminGitHubTable (sslVerify) VALUES('FALSE')";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}


$sql = "CREATE TABLE $piConnTable(
  header VARCHAR(50)
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}

$sql = "INSERT INTO $piConnTable(header) VALUES (' ')";
if (!$conn->query($sql)) {
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
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}

$sql = "CREATE TABLE $travelCountryTable(
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(10) NOT NULL,
  countryName VARCHAR(50),
  dayPay DECIMAL(6,2) DEFAULT 0,
  nightPay DECIMAL(6,2) DEFAULT 0
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}

$sql = "CREATE TABLE $travelTable(
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  userID INT(6) UNSIGNED,
  countryID INT(6) UNSIGNED,
  travelDayStart DATETIME NOT NULL,
  travelDayEnd DATETIME NOT NULL,
  kmStart INT(8),
  kmEnd INT(8),
  infoText VARCHAR(500),
  hotelCosts DECIMAL(8,2) DEFAULT 0,
  hosting10 DECIMAL(6,2) DEFAULT 0,
  hosting20 DECIMAL(6,2) DEFAULT 0,
  expenses DECIMAL(8,2) DEFAULT 0,
  FOREIGN KEY (userID) REFERENCES $userTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE,
  FOREIGN KEY (countryID) REFERENCES $travelCountryTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}

$sql = "CREATE TABLE $deactivatedUserTable (
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
  coreTime TIME DEFAULT '8:00',
  kmMoney DECIMAL(4,2) DEFAULT 0.42
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
} else {
  echo "created deact usertab";
}

$sql = "CREATE TABLE $deactivatedUserLogs (
  indexIM INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  time DATETIME NOT NULL,
  timeEnd DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  status ENUM('-1', '0', '1', '2', '3', '4'),
  timeToUTC INT(2) DEFAULT '2',
  breakCredit	DECIMAL(4,2),
  userID INT(6) UNSIGNED,
  expectedHours DECIMAL(4,2),
  FOREIGN KEY (userID) REFERENCES $deactivatedUserTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
} else {
  echo "created deact userlogs";
}

$sql = "CREATE TABLE $deactivatedUserUnLogs(
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
  FOREIGN KEY (userID) REFERENCES $deactivatedUserTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
} else {
  echo "created deact unlogs";
}


$sql = "CREATE TABLE $deactivatedUserDataTable(
  userID INT(6) UNSIGNED,
  mon DECIMAL(4,2) DEFAULT 8.5,
  tue DECIMAL(4,2) DEFAULT 8.5,
  wed DECIMAL(4,2) DEFAULT 8.5,
  thu DECIMAL(4,2) DEFAULT 8.5,
  fri DECIMAL(4,2) DEFAULT 4.5,
  sat DECIMAL(4,2) DEFAULT 0,
  sun DECIMAL(4,2) DEFAULT 0,
  vacationHoursCredit DECIMAL(6,2) DEFAULT 0,
  daysPerYear INT(2) DEFAULT 25,
  FOREIGN KEY (userID) REFERENCES $deactivatedUserTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
} else {
  echo "created deact datatable";
}


$sql = "CREATE TABLE $deactivatedUserProjects (
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  start DATETIME NOT NULL,
  end DATETIME NOT NULL,
  chargedTimeStart DATETIME DEFAULT '0000-00-00 00:00:00',
  chargedTimeEnd DATETIME DEFAULT '0000-00-00 00:00:00',
  projectID INT(6) UNSIGNED,
  timestampID INT(10) UNSIGNED,
  infoText VARCHAR(500),
  internInfo VARCHAR(500),
  booked ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
  bookingType ENUM('project', 'break', 'drive'),
  FOREIGN KEY (projectID) REFERENCES $projectTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE,
  FOREIGN KEY (timestampID) REFERENCES $deactivatedUserLogs(indexIM)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
} else {
  echo "created deact projectbookings";
}

$sql = "CREATE TABLE $deactivatedUserTravels(
  id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  userID INT(6) UNSIGNED,
  countryID INT(6) UNSIGNED,
  travelDayStart DATETIME NOT NULL,
  travelDayEnd DATETIME NOT NULL,
  kmStart INT(8),
  kmEnd INT(8),
  infoText VARCHAR(500),
  hotelCosts DECIMAL(8,2) DEFAULT 0,
  hosting10 DECIMAL(6,2) DEFAULT 0,
  hosting20 DECIMAL(6,2) DEFAULT 0,
  expenses DECIMAL(8,2) DEFAULT 0,
  FOREIGN KEY (userID) REFERENCES $deactivatedUserTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE,
  FOREIGN KEY (countryID) REFERENCES $travelCountryTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
} else {
  echo "created deact travellogs";
}

$sql = "CREATE TABLE $clientDetailTable(
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  contactType ENUM('person', 'company'),
  gender ENUM('female', 'male'),
  title VARCHAR(30),
  name VARCHAR(45),
  nameAddition VARCHAR(45),
  address_Street VARCHAR(100),
  address_Country VARCHAR(100),
  phone VARCHAR(20),
  debitNumber INT(10),
  datev INT(10),
  accountName VARCHAR(100),
  taxnumber INT(50),
  taxArea VARCHAR(50),
  customerGroup VARCHAR(50),
  representative VARCHAR(50),
  blockDelivery ENUM('true', 'false') DEFAULT 'false',
  paymentMethod VARCHAR(100),
  shipmentType VARCHAR(100),
  creditLimit DECIMAL(10,2),
  eBill ENUM('true', 'false') DEFAULT 'false',
  lastFaktura DATETIME,
  daysNetto INT(4),
  skonto1 DECIMAL(6,2),
  skonto2 DECIMAL(6,2),
  skonto1Days INT(4),
  skonto2Days INT(4),
  warningEnabled ENUM('true', 'false') DEFAULT 'true',
  karenztage INT(4),
  lastWarning DATETIME,
  warning1 DECIMAL(10,2),
  warning2 DECIMAL(10,2),
  warning3 DECIMAL(10,2),
  calculateInterest ENUM('true', 'false'),
  clientID INT(6) UNSIGNED,
  FOREIGN KEY (clientID) REFERENCES $clientTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}

$sql = "CREATE TABLE $clientDetailNotesTable(
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  infoText VARCHAR(800),
  createDate DATETIME,
  parentID INT(6) UNSIGNED,
  FOREIGN KEY (parentID) REFERENCES $clientDetailTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}
$sql = "CREATE TABLE $clientDetailBankTable(
  id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bic VARCHAR(20),
  iban VARCHAR(50),
  bankName VARCHAR(100),
  parentID  INT(6) UNSIGNED,
  FOREIGN KEY (parentID) REFERENCES $clientDetailTable(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}
?>
