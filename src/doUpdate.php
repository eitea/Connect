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
require "connection.php";
require "utilities.php";
include 'validate.php';

$sql = "SELECT * FROM $adminLDAPTable;";
$result = mysqli_query($conn, $sql);
$row = $result->fetch_assoc();

if ($row['version'] < 81) {
    $sql = "ALTER TABLE products ADD COLUMN taxPercentage INT(3) UNSIGNED";
    if ($conn->query($sql)) {
        echo '<br> Added taxes to products';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE logs MODIFY COLUMN status INT(3)";
    if ($conn->query($sql)) {
        echo '<br> Log savetype changes...';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "UPDATE logs SET status = (status - 2)";
    if ($conn->query($sql)) {
        echo '<br> ... Recalculations';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE userRequestsData MODIFY COLUMN requestType ENUM('vac', 'log', 'acc', 'scl', 'spl', 'brk') DEFAULT 'vac'";
    if ($conn->query($sql)) {
        echo '<br> Extended request types';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 82) {
    $sql = "ALTER TABLE projectBookingData MODIFY COLUMN bookingType ENUM('project', 'break', 'drive', 'mixed')";
    if ($conn->query($sql)) {
        echo '<br> Extended booking Types';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE projectBookingData ADD COLUMN mixedStatus INT(3) DEFAULT -1";
    if ($conn->query($sql)) {
        echo '<br> Added mixed status';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE projectBookingData ADD COLUMN exp_info TEXT";
    if ($conn->query($sql)) {
        echo '<br> Added expenses: description';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE projectBookingData ADD COLUMN exp_price DECIMAL(10,2)";
    if ($conn->query($sql)) {
        echo '<br> Added expenses: price';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE projectBookingData ADD COLUMN exp_unit DECIMAL(10,2)";
    if ($conn->query($sql)) {
        echo '<br> Added expenses: quantity';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 83) {
    $sql = "ALTER TABLE UserData ADD COLUMN coreTime TIME DEFAULT '08:00:00'";
    if ($conn->query($sql)) {
        echo '<br> Added user Core Time';
    } //no elso in here
}

if ($row['version'] < 84) {
    $sql = "ALTER TABLE proposals ADD COLUMN curDate DATETIME DEFAULT CURRENT_TIMESTAMP";
    if ($conn->query($sql)) {
        echo '<br> Added date to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN deliveryDate DATETIME";
    if ($conn->query($sql)) {
        echo '<br> Added date of delivery to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN yourSign VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Added sign 1 to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN yourOrder VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Added order to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN ourSign VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Added sign 2 to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN ourMessage VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Added mesasge to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE proposals ADD COLUMN daysNetto INT(4)";
    if ($conn->query($sql)) {
        echo '<br> Added days Netto to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN skonto1 DECIMAL(6,2)";
    if ($conn->query($sql)) {
        echo '<br> Added skonto 1 to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN skonto2 DECIMAL(6,2)";
    if ($conn->query($sql)) {
        echo '<br> Added skonto 2 to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN skonto1Days INT(4)";
    if ($conn->query($sql)) {
        echo '<br> Added days to skonto 1 to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN skonto2Days INT(4)";
    if ($conn->query($sql)) {
        echo '<br> Added days to skonto 2 to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN paymentMethod VARCHAR(100)";
    if ($conn->query($sql)) {
        echo '<br> Added payment method to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN shipmentType VARCHAR(100)";
    if ($conn->query($sql)) {
        echo '<br> Added shipment type to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN representative VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Added representative to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE proposals ADD COLUMN porto DECIMAL(8,2)";
    if ($conn->query($sql)) {
        echo '<br> Added porto to Proposals';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE clientInfoData ADD COLUMN firstname VARCHAR(45)";
    if ($conn->query($sql)) {
        echo '<br> Splitting name to first and lastname in client data';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE clientInfoData ADD COLUMN address_Country_Postal VARCHAR(20)";
    if ($conn->query($sql)) {
        echo '<br> Splitting postal code off country in client data';
    } else {
        echo '<br>' . $conn->error;
    }
    $sql = "ALTER TABLE clientInfoData ADD COLUMN address_Country_City VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Splitting city off country in client data';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE clientInfoData ADD COLUMN fax_number VARCHAR(30)";
    if ($conn->query($sql)) {
        echo '<br> Added fax number to client data';
    } else {
        echo '<br>' . $conn->error;
    }

    //fixing additional fields activity
    $result = $conn->query("SELECT * FROM companyDefaultProjects");
    while ($result && ($row = $result->fetch_assoc())) {
        $conn->query("UPDATE projectData p1 SET field_1 = '" . $row['field_1'] . "', field_2 = '" . $row['field_2'] . "', field_3 = '" . $row['field_3'] . "'
    WHERE clientID IN (SELECT clientData.id FROM clientData, companyDefaultProjects
    WHERE clientData.companyID = companyDefaultProjects.companyID AND p1.name = companyDefaultProjects.name AND companyDefaultProjects.id = " . $row['id'] . ")");
    }
    if ($conn->query($sql)) {
        echo '<br> Fixed additional field assigning';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 85) {
    $sql = "ALTER TABLE intervalData MODIFY COLUMN overTimeLump DECIMAL(5,2) DEFAULT 0.0";
    if ($conn->query($sql)) {
        echo '<br> Max overtime from 99.00 to 999.00';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE logs DROP COLUMN breakCredit";
    if ($conn->query($sql)) {
        echo '<br> Removed break field';
    } else {
        echo '<br>' . $conn->error;
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
    if ($conn->query($sql)) {
        echo '<br> Additional info storage for mixed timestamps';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE clientInfoData MODIFY COLUMN taxnumber VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Changed tax number to text in client details';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE clientInfoData ADD COLUMN vatnumber VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Added VAT number to client details';
    } else {
        echo '<br>' . $conn->error;
    }

    $result = $conn->query("SELECT * FROM logs WHERE status = '5'"); //select all mixed timestamps
    while ($result && ($row = $result->fetch_assoc())) {
        //select all mixed bookings
        $bookings_result = $conn->query("SELECT * FROM projectBookingData WHERE bookingType = 'mixed' AND timestampID = " . $row['indexIM']);
        if ($bookings_result && ($booking_row = $bookings_result->fetch_assoc())) {
            //correct starting time
            $conn->query("UPDATE logs SET time = '" . $booking_row['end'] . "' WHERE indexIM = " . $row['indexIM']);
            //enter full hours
            $A = substr_replace($row['time'], '08:00', 11, 5);
            $B = carryOverAdder_Hours($A, 9);
            $conn->query("INSERT INTO mixedInfoData (timestampID, status, timeStart, timeEnd) VALUES(" . $row['indexIM'] . ", '" . $booking_row['mixedStatus'] . "', '$A', '$B')");
            //remove the mixed bookings
            $conn->query("DELETE FROM projectBookingData WHERE id = " . $booking_row['id']);
        }
    }

    $sql = "ALTER IGNORE TABLE projectbookingdata ADD UNIQUE double_submit (timestampID, start, end)";
    if ($conn->query($sql)) {
        echo '<br> Remove all the duplicate entries and create a key';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE $deactivatedUserLogs DROP COLUMN breakCredit";
    if ($conn->query($sql)) {
        echo '<br> Remove breaks from deactivated logs';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE DeactivatedUserLogData MODIFY COLUMN status INT(3)";
    if ($conn->query($sql)) {
        echo '<br> Changed status';
    } else {
        echo '<br>' . $conn->error;
    }

    $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN mixedStatus INT(3) DEFAULT -1");
    $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN extra_1 VARCHAR(200) NULL DEFAULT NULL");
    $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN extra_2 VARCHAR(200) NULL DEFAULT NULL");
    $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN extra_3 VARCHAR(200) NULL DEFAULT NULL");
    $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN exp_info TEXT");
    $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN exp_price DECIMAL(10,2)");
    $conn->query("ALTER TABLE DeactivatedUserProjectData ADD COLUMN exp_unit DECIMAL(10,2)");

    $sql = "ALTER TABLE DeactivatedUserProjectData MODIFY COLUMN bookingType ENUM('project', 'break', 'drive', 'mixed')";
    if ($conn->query($sql)) {
        echo '<br> Expanded deactivated booking type';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 86) {
    $conn->query("DROP TABLE IF EXISTS taxRates");
    $sql = "CREATE TABLE taxRates (
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(200),
    percentage INT(3)
  )";
    if ($conn->query($sql)) {
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
    if ($conn->query($sql)) {
        echo '<br> Created article list for products';
    }

    $sql = "ALTER TABLE products ADD COLUMN unit VARCHAR(20)";
    if ($conn->query($sql)) {
        echo '<br> Added units to products';
    }

    $sql = "ALTER TABLE proposals ADD COLUMN history VARCHAR(100)";
    if ($conn->query($sql)) {
        echo '<br> Added transitions to proposals';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE proposals ADD COLUMN portoRate INT(3)";
    if ($conn->query($sql)) {
        echo '<br> Added percentage to porto in proposals';
    } else {
        echo '<br>' . $conn->error;
    }

    $conn->query("UPDATE logs SET timeToUTC = 2 WHERE timeToUTC = 0 AND status = 5 ");

    $conn->query("DELETE FROM projectBookingData WHERE bookingType = 'mixed'");

    if (mysqli_error($conn)) {
        echo '<br>' . $conn->error;
    } else {
        echo "<br> Repaired wrong booking types";
    }

    $conn->query("ALTER TABLE proposals MODIFY COLUMN status INT(2)");

    $sql = "UPDATE proposals SET status = status - 1";
    if ($conn->query($sql)) {
        echo '<br> Extended status of proposals';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 87) {
    $sql = "ALTER TABLE userRequestsData MODIFY COLUMN requestType ENUM('vac', 'log', 'acc', 'scl', 'spl', 'brk', 'cto') DEFAULT 'vac'";
    if ($conn->query($sql)) {
        echo '<br> Added compensatory time';
    }

    $sql = "ALTER TABLE companyData ADD COLUMN detailLeft VARCHAR(180)";
    if ($conn->query($sql)) {
        echo '<br> Added left-bound details';
    }

    $sql = "ALTER TABLE companyData ADD COLUMN detailMiddle VARCHAR(180)";
    if ($conn->query($sql)) {
        echo '<br> Added centered details';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE companyData ADD COLUMN detailRight VARCHAR(180)";
    if ($conn->query($sql)) {
        echo '<br> Added right-bound details';
    }

    $sql = "ALTER TABLE companyData ADD COLUMN uid VARCHAR(20)";
    if ($conn->query($sql)) {
        echo '<br> Added UID to company data';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE companyData ADD COLUMN cmpDescription VARCHAR(50)";
    if ($conn->query($sql)) {
        echo '<br> Added name to company data';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE companyData ADD COLUMN companyPostal VARCHAR(20)";
    if ($conn->query($sql)) {
        echo '<br> Added postal code to company data';
    } else {
        echo '<br>' . $conn->error;
    }
}
if ($row['version'] < 88) {
    $sql = "ALTER TABLE companyData ADD COLUMN companyCity VARCHAR(20)";
    if ($conn->query($sql)) {
        echo '<br> Added city field to company data';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 89) {
    $sql = "ALTER TABLE products ADD COLUMN cash ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
    if ($conn->query($sql)) {
        echo '<br> Added expenses in cash checkbox';
    }

    $sql = "CREATE TABLE units (
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL,
    unit VARCHAR(10) NOT NULL
  )";
    if ($conn->query($sql)) {
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
    if ($conn->query($sql)) {
        echo '<br> Extended requests by splitted lunchbreaks';
    }
}

if ($row['version'] < 90) {
    $sql = "ALTER TABLE products ADD COLUMN purchase DECIMAL(10,2)";
    if ($conn->query($sql)) {
        echo '<br> Added purchase price to products';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE articles ADD COLUMN cash ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
    if ($conn->query($sql)) {
        echo '<br> Update articles to Database';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE articles ADD COLUMN purchase DECIMAL(10,2)";
    if ($conn->query($sql)) {
        echo '<br> Added purchase price to articles';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 92) {
    $sql = "ALTER TABLE $userTable ADD COLUMN color VARCHAR(10) DEFAULT 'default'";
    if ($conn->query($sql)) {
        echo '<br> Color Picker';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 93) {
    $sql = "CREATE TABLE paymentMethods (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
  )";
    if ($conn->query($sql)) {
        echo '<br>Payment Methods';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "CREATE TABLE representatives (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50)
  )";
    if ($conn->query($sql)) {
        echo '<br>Representatives';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "CREATE TABLE shippingMethods (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100)
  )";
    if ($conn->query($sql)) {
        echo '<br>Shipping Methods';
    } else {
        echo '<br>' . $conn->error;
    }

    //insert payment method
    $sql = "INSERT INTO paymentMethods (name) VALUES ('Überweisung')";
    $conn->query($sql);
    //insert shippign method
    $sql = "INSERT INTO shippingMethods (name) VALUES ('Abholer')";
    $conn->query($sql);

}

if ($row['version'] < 94) {
    $result = $conn->query("SELECT logo, id FROM companyData");
    $conn->query("UPDATE companyData SET logo = NULL");

    $sql = "ALTER TABLE companyData MODIFY COLUMN logo MEDIUMBLOB";
    if ($conn->query($sql)) {
        echo '<br>Company Logo store replace reference by BLOB';
    } else {
        echo '<br>' . $conn->error;
    }

    $stmt = $conn->prepare("UPDATE companyData SET logo = ? WHERE id = ?");
    $null = NULL;
    $stmt->bind_param("bi", $null, $cmpID);
    while ($row = $result->fetch_assoc()) {
        if ($row['logo'] && file_exists($row['logo'])) {
            $cmpID = $row['id'];
            $fp = fopen($row['logo'], "r");
            while (!feof($fp)) {
                $stmt->send_long_data(0, fread($fp, 8192));
            }
            fclose($fp);
            $stmt->execute();
            if ($stmt->errno) {echo $stmt->error;}
            unlink($row['logo']);
        }
    }
    $stmt->close();

    $sql = "ALTER TABLE products ADD COLUMN position INT(4)";
    if ($conn->query($sql)) {
        echo '<br>product position';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE products ADD UNIQUE INDEX(position, proposalID)";
    if ($conn->query($sql)) {
        echo '<br>Company Logo store replace reference by BLOB';
    } else {
        echo '<br>' . $conn->error;
    }

    $proposal = $i = 0;
    $result = $conn->query("SELECT id, proposalID FROM products ORDER BY proposalID ASC");
    while ($row = $result->fetch_assoc()) {
        if ($proposal != $row['proposalID']) {
            $i = 1;
            $proposal = $row['proposalID'];
        }
        $conn->query("UPDATE products SET position = $i WHERE id = " . $row['id']);
        $i++;
    }
}

if ($row['version'] < 95) {
    $sql = "ALTER TABLE UserData ADD COLUMN erpOption VARCHAR(10) DEFAULT 'TRUE'";
    if ($conn->query($sql)) {
        echo '<br>Added option to display overall balance in ERP';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "ALTER TABLE userRequestsData ADD COLUMN timeToUTC INT(2) DEFAULT 0";
    if ($conn->query($sql)) {
        echo '<br>Added UTC value to requests';
    } else {
        echo '<br>' . $conn->error;
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
    if ($conn->query($sql)) {
        echo '<br>Create ERP numbers';
    } else {
        echo '<br>' . $conn->error;
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
    if ($conn->query("ALTER TABLE modules ADD COLUMN enableSocialMedia ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'")) {
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
        if (!$conn->query("INSERT INTO socialprofile (userID, isAvailable, status) VALUES($x, 'TRUE', '-')")) {
            echo '<br>' . $conn->error;
        }
    }
}

if ($row['version'] < 99) {
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
    while ($result && ($row = $result->fetch_assoc())) {
        //step2: get the difference, the time of the last booking and simply append whats missing.
        $indexIM = $row['indexIM'];
        $result_book = $conn->query("SELECT end FROM projectBookingData WHERE timestampID = $indexIM ORDER BY start DESC");
        if ($result_book && ($row_book = $result_book->fetch_assoc())) {
            $row_break['breakCredit'] = 0;
            $result_break = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, start, end)) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = $indexIM");
            if ($result_break && $result_break->num_rows > 0) {
                $row_break = $result_break->fetch_assoc();
            }

            $missingBreak = intval($row['hoursOfRest'] * 60 - $row_break['breakCredit']);
            if ($missingBreak < 0 || $missingBreak > $row['hoursOfRest'] * 60) {echo $missingBreak . ' ' . $indexIM;}
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

if ($row['version'] < 100) {
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
    for ($i = 1; $i < count($holidayFile); $i++) {
        if (trim($holidayFile[$i]['BEGIN']) == "VEVENT") {
            $start = substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 0, 4) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 6, 2) . " 00:00:00";
            $end = substr($holidayFile[$i]['DTEND;VALUE=DATE'], 0, 4) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 6, 2) . " 20:00:00";
            $n = $holidayFile[$i]['SUMMARY'];
            $conn->query("INSERT INTO holidays(begin, end, name) VALUES ('$start', '$end', '$n');");
            echo $conn->error;
        }
    }

    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Repaired Wrong Charactersets';
    }
}

if ($row['version'] < 101) {
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

if ($row['version'] < 102) {
    $result = $conn->query("SELECT * FROM projectBookingData WHERE infoText LIKE '%_?_%'");
    $pool = array('ä', 'ö', 'ü');
    while ($row = $result->fetch_assoc()) {
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
        $conn->query("UPDATE projectBookingData SET infoText = '$newText' WHERE id = " . $row['id']);
    }
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Repaired Wrong Charactersets';
    }
}

if ($row['version'] < 103) {
    $sql = "CREATE TABLE identification(
    id VARCHAR(60) UNIQUE NOT NULL
  )";
    if ($conn->query($sql)) {
        echo '<br> Created identification table';
    }

    $identifier = str_replace('.', '0', randomPassword() . uniqid('', true) . randomPassword() . uniqid('') . randomPassword()); //60 characters;
    $conn->query("INSERT INTO identification (id) VALUES ('$identifier')");
    if ($conn->query($sql)) {
        echo '<br> Insert unique ID';
    }
}

if ($row['version'] < 104) {
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN daysNetto INT(4)");
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto1 DECIMAL(6,2)");
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto2 DECIMAL(6,2)");
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto1Days INT(4)");
    $conn->query("ALTER TABLE paymentMethods ADD COLUMN skonto2Days INT(4)");

    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Zahlungsmethoden Update';
    }
}

if ($row['version'] < 105) {
    $conn->query("ALTER TABLE proposals DROP COLUMN yourSign");
    $conn->query("ALTER TABLE proposals DROP COLUMN yourOrder");
    $conn->query("ALTER TABLE proposals DROP COLUMN ourMessage");
    $conn->query("ALTER TABLE proposals DROP COLUMN ourSign");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>ERP Bezugszeichenzeile aus Aufträge entfernt';
    }

    $conn->query("ALTER TABLE erpNumbers ADD COLUMN yourSign VARCHAR(30)");
    $conn->query("ALTER TABLE erpNumbers ADD COLUMN yourOrder VARCHAR(30)");
    $conn->query("ALTER TABLE erpNumbers ADD COLUMN ourMessage VARCHAR(30)");
    $conn->query("ALTER TABLE erpNumbers ADD COLUMN ourSign VARCHAR(30)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>ERP Bezugszeichenzeile zu Mandant hinzugefügt';
    }

    $conn->query("ALTER TABLE proposals ADD COLUMN header VARCHAR(400)");
    $conn->query("ALTER TABLE proposals ADD COLUMN referenceNumrow VARCHAR(10)");
    if ($conn->error) {
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
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>ERP: Zahlungsbedingung stark vereinfacht';
    }
}

if ($row['version'] < 106) {
    $conn->query("ALTER TABLE roles ADD COLUMN isFinanceAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' ");
    if ($conn->error) {
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
    if ($conn->query($sql)) {
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
    if ($conn->query($sql)) {
        echo '<br>Finanzen: Bank und Kassa hinzugefügt';
    } else {
        echo $conn->error;
    }

    $conn->query("ALTER TABLE taxRates ADD COLUMN account2 INT(4)");
    $conn->query("ALTER TABLE taxRates ADD COLUMN account3 INT(4)");
}

if ($row['version'] < 107) {
    $sql = "CREATE TABLE projectBookingData_audit(
    id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    changedat DATETIME,
    bookingID INT(6) UNSIGNED,
    statement VARCHAR(100)
  )";
    if ($conn->query($sql)) {
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
    if ($conn->query($sql)) {
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

if ($row['version'] < 108) {
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
    if ($conn->query($sql)) {
        echo '<br>Finanzen: Buchungsjournal hinzugefügt';
    } else {
        echo $conn->error;
    }

    $conn->query("ALTER TABLE accounts ADD COLUMN manualBooking VARCHAR(10) DEFAULT 'FALSE' ");
    if (!$conn->error) {
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

if ($row['version'] < 109) {
    $conn->query("ALTER TABLE companyData MODIFY column companyCity VARCHAR(60) ");
    if (!$conn->error) {
        echo '<br>Mandant: 54 Zeichen Ort';
    } else {
        echo $conn->error;
    }
}

if ($row['version'] < 110) {
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
    if (!$conn->error) {
        echo '<br>ERP: Lieferanten';
    } else {
        echo $conn->error;
    }

    //new accounts
    $conn->query("DELETE FROM accounts");
    $conn->query("ALTER TABLE accounts AUTO_INCREMENT = 1");
    $file = fopen(__DIR__ . '/setup/Kontoplan.csv', 'r');
    if ($file) {
        $stmt = $conn->prepare("INSERT INTO accounts (companyID, num, name, type) SELECT id, ?, ?, ? FROM companyData");
        $stmt->bind_param("iss", $num, $name, $type);
        while (($line = fgetcsv($file, 300, ';')) !== false) {
            $num = $line[0];
            $name = trim(iconv(mb_detect_encoding($line[1], mb_detect_order(), true), "UTF-8", $line[1]));
            if (!$name) {
                $name = trim(iconv('MS-ANSI', "UTF-8", $line[1]));
            }

            if (!$name) {
                $name = $line[1];
            }

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
    if (!$conn->error) {
        echo '<br>Benutzer: Punktesystem';
    } else {
        echo $conn->error;
    }
}

if ($row['version'] < 111) {
    $i = 1;
    $conn->query("DELETE FROM taxRates");
    $file = fopen(__DIR__ . '/setup/Steuerraten.csv', 'r');
    if ($file) {
        $stmt = $conn->prepare("INSERT INTO taxRates(id, description, percentage, account2, account3, code) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiiii", $i, $name, $percentage, $account2, $account3, $code);
        while ($line = fgetcsv($file, 100, ';')) {
            $name = trim(iconv(mb_detect_encoding($line[0], mb_detect_order(), true), "UTF-8", $line[0]));
            if (!$name) {
                $name = trim(iconv('MS-ANSI', "UTF-8", $line[0]));
            }

            if (!$name) {
                $name = $line[0];
            }

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
    if (!$conn->error) {
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

if ($row['version'] < 113) {
    $conn->query("CREATE TABLE checkinLogs(
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    timestampID INT(10) UNSIGNED,
    remoteAddr VARCHAR(50),
    userAgent VARCHAR(150)
  )");
    if (!$conn->error) {
        echo '<br>Checkin: IP und Header Logs';
    }

    $conn->query("ALTER TABLE logs ADD COLUMN emoji INT(2) DEFAULT 0 ");
    if (!$conn->error) {
        echo '<br>Checkout: Emoji';
    }

    $conn->query("ALTER TABLE articles ADD COLUMN taxID INT(4) UNSIGNED");
    $conn->query("ALTER TABLE products ADD COLUMN taxID INT(4) UNSIGNED");
    $conn->query("UPDATE articles SET taxID = taxPercentage");
    $conn->query("ALTER TABLE articles DROP COLUMN taxPercentage");
    $conn->query("UPDATE products p1 SET taxID = (SELECT id FROM taxRates WHERE percentage = p1.taxPercentage LIMIT 1)");
    $conn->query("ALTER TABLE products DROP COLUMN taxPercentage");
}

if ($row['version'] < 114) {
    $conn->query("DELETE FROM account_balance");
    $result = $conn->query("SELECT account_journal.*, percentage, account2, account3, code FROM account_journal LEFT JOIN taxRates ON taxRates.id = taxID");
    echo $conn->error;

    $stmt = $conn->prepare("INSERT INTO account_balance (journalID, accountID, should, have) VALUES(?, ?, ?, ?)");
    echo $conn->error;
    $stmt->bind_param("iidd", $journalID, $account, $should, $have);

    while ($row = $result->fetch_assoc()) {
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
        if ($res && ($rowP = $res->fetch_assoc())) {
            $accNum = $rowP['num'];
        }

        //prepare balance
        $account2 = $account3 = '';
        if ($row['account2']) {
            $res = $conn->query("SELECT id FROM accounts WHERE num = " . $row['account2'] . " AND companyID IN (SELECT companyID FROM accounts WHERE id = $offAccount) ");
            echo $conn->error;
            if ($res && $res->num_rows > 0) {
                $account2 = $res->fetch_assoc()['id'];
            }

        }
        if ($row['account3']) {
            $res = $conn->query("SELECT id FROM accounts WHERE num = " . $row['account3'] . " AND companyID IN (SELECT companyID FROM accounts WHERE id = $offAccount) ");
            echo $conn->error;
            if ($res && $res->num_rows > 0) {
                $account3 = $res->fetch_assoc()['id'];
            }

        }

        //tax calculation
        if ($account2 && $account3) {
            $should_tax = $should * ($row['percentage'] / 100);
            $have_tax = $have * ($row['percentage'] / 100);
        } else {
            $should_tax = $should - ($should * 100) / (100 + $row['percentage']);
            $have_tax = $have - ($have * 100) / (100 + $row['percentage']);

        }

        $should = $temp_have;
        $have = $temp_should;
        //account balance
        if ($account2) {
            $should = $have_tax;
            $have = $should_tax;
            $account = $account2;
            $stmt->execute();
            if ($account3) {
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
        if ($account3) {
            $have = $have_tax;
            $should = $should_tax;
            $account = $account3;
            $stmt->execute();
            if ($account2) {
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

if ($row['version'] < 115) {
    $conn->query("DELETE a1 FROM account_journal a1, account_journal a2 WHERE a1.id > a2.id AND a1.userID = a2.userID AND a1.inDate = a2.inDate");

    $conn->query("ALTER TABLE account_journal ADD UNIQUE KEY double_submit (userID, inDate)");
    if (!$conn->error) {
        echo '<br>Finanzen: Doppelte Buchungen Fix';
    }

    $conn->query("ALTER TABLE UserData ADD COLUMN keyCode VARCHAR(100)");
    if (!$conn->error) {
        echo '<br>Verschlüsselung: Master Passwort aktualisiert';
    }

    $conn->query("ALTER TABLE configurationData ADD COLUMN checkSum VARCHAR(20)");
}

if ($row['version'] < 116) {
    //encrypted values need sooo much more SPACE
    $conn->query("ALTER TABLE configurationData MODIFY COLUMN checkSum VARCHAR(40)");

    $conn->query("ALTER TABLE clientInfoBank MODIFY COLUMN bankName VARCHAR(400)");

    $conn->query("ALTER TABLE clientInfoBank MODIFY COLUMN iban VARCHAR(400)");

    $conn->query("ALTER TABLE accounts ADD COLUMN options VARCHAR(10) DEFAULT 'STAT' ");

    $conn->query("CREATE TABLE processHistory(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_number VARCHAR(12) NOT NULL,
    processID INT(6) UNSIGNED,
    FOREIGN KEY (processID) REFERENCES proposals(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )");
    echo $conn->error;

    $stmt = $conn->prepare("INSERT INTO processHistory (id_number, processID) VALUES(?, ?) ");
    $stmt->bind_param("si", $id_number, $processID);

    $result = $conn->query("SELECT id, id_number, history FROM proposals");
    while ($row = $result->fetch_assoc()) {
        $processID = $row['id'];
        $id_number = $row['id_number'];
        $stmt->execute();
        if ($row['history']) {
            $arr = explode(' ', $row['history']);
            foreach ($arr as $a) {
                $id_number = $a;
                $stmt->execute();
            }
        }
    }
    $stmt->close();
    echo $conn->error;

    $conn->query("ALTER TABLE proposals DROP COLUMN id_number");
    $conn->query("ALTER TABLE proposals DROP COLUMN history");
    echo $conn->error;

    $conn->query("ALTER TABLE products DROP FOREIGN KEY products_ibfk_1");
    echo $conn->error;
    $conn->query("ALTER TABLE products DROP COLUMN proposalID");
    $conn->query("ALTER TABLE products DROP INDEX position");

    $conn->query("ALTER TABLE products ADD COLUMN historyID INT(6) UNSIGNED");
    $conn->query("ALTER TABLE products ADD COLUMN origin VARCHAR(16)");
    $conn->query("ALTER TABLE products ADD FOREIGN KEY (historyID) REFERENCES processHistory(id) ON UPDATE CASCADE ON DELETE CASCADE");
    echo $conn->error;

    $result = $conn->query("SELECT * FROM products");
    $conn->query("DELETE FROM products");
    $stmt = $conn->prepare("INSERT INTO products (name, description, historyID, origin, price, quantity, unit, taxID, cash, purchase, position, iv, iv2) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisddsiddiss", $name, $desc, $historyID, $origin, $price, $quantity, $unit, $taxID, $cash, $purchase, $pos, $iv, $iv2);
    echo $stmt->error;
    while ($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $desc = $row['description'];
        $price = $row['price'];
        $quantity = $row['quantity'];
        $unit = $row['unit'];
        $taxID = $row['taxID'];
        $cash = $row['cash'];
        $purchase = $row['purchase'];
        $pos = $row['position'];
        $iv = $row['iv'];
        $iv2 = $row['iv2'];
        $origin = randomPassword(16);

        $processRes = $conn->query("SELECT id FROM processHistory WHERE processID = " . $row['proposalID']);
        echo $conn->error;
        while ($processRes && ($processRow = $processRes->fetch_assoc())) {
            $historyID = $processRow['id'];
            $stmt->execute();
        }
    }
    echo $conn->error;
    $conn->query("ALTER TABLE products DROP COLUMN proposalID");
    echo $conn->error;
}

if ($row['version'] < 117) {
    $sql = "CREATE TABLE documents(
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    companyID INT(6) UNSIGNED,
    name VARCHAR(50) NOT NULL,
    txt MEDIUMTEXT NOT NULL,
    version VARCHAR(15) NOT NULL DEFAULT 'latest',
    FOREIGN KEY (companyID) REFERENCES companyData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>DSGVO: Dokumente erstellt';
    }

    $conn->query("ALTER TABLE templateData ADD COLUMN type VARCHAR(10) NOT NULL DEFAULT 'report' ");
    if (!$conn->error) {
        echo '<br>Vorlagen: E-Mail Templates erweitert';
    } else {
        echo '<br>' . $conn->error;
    }

    $sql = "CREATE TABLE contactPersons (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clientID INT(6) UNSIGNED,
    firstname VARCHAR(150),
    lastname VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    position VARCHAR(250),
    responsibility VARCHAR(250),
    FOREIGN KEY (clientID) REFERENCES clientData(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>Kundenstamm: Ansprechpartner';
    }

    $sql = "CREATE TABLE documentProcess(
    id VARCHAR(16) NOT NULL PRIMARY KEY,
    docID INT(6) UNSIGNED,
    personID INT(6) UNSIGNED,
    password VARCHAR(60) NOT NULL,
    FOREIGN KEY (docID) REFERENCES documents(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
    FOREIGN KEY (personID) REFERENCES contactPersons(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>DSGVO: Dokument Sendevorgänge';
    }

    $sql = "CREATE TABLE documentProcessHistory(
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    processID VARCHAR(16),
    logDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activity VARCHAR(20) NOT NULL,
    info VARCHAR(450),
    userAgent VARCHAR(150)
  )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    } else {
        echo '<br>DSGVO: Prozess Logs erstellt';
    }

    $sql = "ALTER TABLE roles ADD COLUMN isDSGVOAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
    if ($conn->query($sql)) {
        echo '<br>DSGVO Admin Rolle';
    } else {
        echo '<br>' . $conn->error;
    }
}

if ($row['version'] < 118) {
    $sql = "ALTER TABLE documents MODIFY COLUMN name VARCHAR(100) NOT NULL";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnis: Einstellungen';
    }

    //INSERT DEFAULT TEMPLATES
    $base_opts = array('', 'Awarness: Regelmäßige Mitarbeiter Schulung in Bezug auf Datenschutzmanagement', 'Awarness: Risikoanalyse', 'Awarness: Datenschutz-Folgeabschätzung',
        'Zutrittskontrolle: Schutz vor unbefugten Zutritt zu Server, Netzwerk und Storage', 'Zutrittskontrolle: Protokollierung der Zutritte in sensible Bereiche (z.B. Serverraum)',
        'Zugangskontrolle: regelmäßige Passwortänderung der Benutzerpasswörter per Policy (mind. Alle 180 Tage)', 'Zugangskontrolle: regelmäßige Passwortänderung der administrativen Zugänge,
	Systembenutzer (mind. Alle 180 Tage)', 'Zugangskontrolle: automaischer Sperrmechanismus der Zugänge um Brut Force Attacken abzuwehren', 'Zugangskontrolle: Zwei-Faktor-Authentifizierung für externe Zugänge (VPN)',
        'Wechseldatenträger: Sperre oder zumindest Einschränkung von Wechseldatenträger (USB-Stick, SD Karte, USB Geräte mit Speichermöglichkeiten…)',
        'Infrastruktur: Verschlüsselung der gesamten Festplatte in PC und Notebooks', 'Infrastruktur: Network Access Control (NAC) im Netzwerk aktiv',
        'Infrastruktur: Protokollierung der Verbindungen über die Firewall (mind. 180 Tage)', 'Infrastruktur: Einsatz einer Applikationsbasierter Firewall (next Generation Firewall)', 'Infrastruktur: Backup-Strategie, die mind. Alle 180 Tage getestet wird', 'Infrastruktur: Virenschutz (advanced Endpoint Protection)',
        'Infrastruktur: Regelmäßige Failover Tests, falls ein zweites Rechenzentrum vorhanden ist', 'Infrastruktur: Protokollierung von Zugriffen und Alarmierung bei unbefugten Lesen oder Schreiben',
        'Weitergabekontrolle: Kein unbefugtes Lesen, Kopieren, Verändern oder Entfernen bei elektronischer Übertragung oder Transport, zB: Verschlüsselung, Virtual Private Networks (VPN), elektronische Signatur',
        'Drucker und MFP Geräte: Verschlüsselung der eingebauten Datenträger.', 'Drucker und MFP Geräte: Secure Printing bei personenbezogenen Daten. Unter "secure printing" versteht man die zusätzliche Authentifizierung direkt am Drucker, um den Ausdruck zu erhalten.',
        'Drucker und MFP Geräte: Bei Leasinggeräten, oder pay per Page Verträgen muss der Datenschutz zwischen den Vertragspartner genau geregelt werden (Vertrag).',
        'Eingabekontrolle: Feststellung, ob und von wem personenbezogene Daten in Datenverarbeitungssysteme eingegeben, verändert oder entfernt worden sind, zB: Protokollierung, Dokumentenmanagement');
    $app_opt_1 = array('', 'Name der verantwortlichen Stelle für diese Applikation', 'Beschreibung der betroffenen Personengruppen und der diesbezüglichen Daten oder Datenkategorien',
        'Zweckbestimmung der Datenerhebung, Datenverarbeitung und Datennutzung', 'Regelfristen für die Löschung der Daten', 'Datenübermittlung in Drittländer', 'Einführungsdatum der Applikation', 'Liste der zugriffsberechtigten Personen');
    $app_opt_2 = array('', 'Pseudonymisierung: Falls die jeweilige Datenanwendung eine Pseudonymisierung unterstützt, wird diese aktiviert. Bei einer Pseudonymisierung werden personenbezogene Daten in der Anwendung entfernt und gesondert aufbewahrt.',
        'Verschlüsselung der Daten: Sofern von der jeweiligen Datenverarbeitung möglich, werden die personenbezogenen Daten verschlüsselt und nicht als Plain-Text Daten gespeichert',
        'Applikation: Backup-Strategie, die mind. Alle 180 Tage getestet wird', 'Applikation: Protokollierung von Zugriffen und Alarmierung bei unbefugten Lesen oder Schreiben',
        'Weitergabekontrolle: Kein unbefugtes Lesen, Kopieren, Verändern oder Entfernen bei elektronischer Übertragung oder Transport, zB: Verschlüsselung, Virtual Private Networks (VPN), elektronische Signatur',
        'Vertraglich (bei externer Betreuung): Gib eine schriftliche Übereinkunft der Leistung und Verpflichtung mit dem entsprechenden Dienstleister der Software?',
        'Eingabekontrolle: Feststellung, ob und von wem personenbezogene Daten in Datenverarbeitungssysteme eingegeben, verändert oder entfernt worden sind, zB: Protokollierung, Dokumentenmanagement');

    $stmt_vv = $conn->prepare("INSERT INTO dsgvo_vv(templateID, name) VALUES(?, 'Basis')");
    $stmt_vv->bind_param("i", $templateID);
    $stmt = $conn->prepare("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr) VALUES(?, ?, ?)");
    $stmt->bind_param("iss", $templateID, $opt, $descr);
    $result = $conn->query("SELECT id FROM companyData");
    while ($row = $result->fetch_assoc()) {
        $cmpID = $row['id'];
        $conn->query("INSERT INTO dsgvo_vv_templates(companyID, name, type) VALUES ($cmpID, 'Default', 'base')");
        $templateID = $conn->insert_id;
        $stmt_vv->execute();
        //BASE
        $descr = '';
        $opt = 'DESCRIPTION';
        $stmt->execute();
        $descr = 'Leiter der Datenverarbeitung (IT Leitung)';
        $opt = 'GEN_1';
        $stmt->execute();
        $descr = 'Inhaber, Vorstände, Geschäftsführer oder sonstige gesetzliche oder nach der Verfassung des Unternehmens berufene Leiter';
        $opt = 'GEN_2';
        $stmt->execute();
        $descr = 'Rechtsgrundlage(n) für die Verwendung von Daten';
        $opt = 'GEN_3';
        $stmt->execute();
        $i = 1;
        while ($i < 24) {
            $opt = 'MULT_OPT_' . $i;
            $descr = $base_opts[$i];
            $stmt->execute();
            $i++;
        }

        $conn->query("INSERT INTO dsgvo_vv_templates(companyID, name, type) VALUES ($cmpID, 'Default', 'app')");
        $templateID = $conn->insert_id;
        //APPS
        $descr = '';
        $opt = 'DESCRIPTION';
        $stmt->execute();
        $i = 1;
        while ($i < 8) {
            $opt = 'GEN_' . $i;
            $descr = $app_opt_1[$i];
            $stmt->execute();
            $i++;
        }
        $i = 1;
        while ($i < 8) {
            $opt = 'MULT_OPT_' . $i;
            $descr = $app_opt_2[$i];
            $stmt->execute();
            $i++;
        }
        $descr = 'Angaben zum Datenverarbeitungsregister (DVR)';
        $opt = 'EXTRA_DVR';
        $stmt->execute();
        $descr = 'Wurde eine Datenschutz-Folgeabschätzung durchgeführt?';
        $opt = 'EXTRA_FOLGE';
        $stmt->execute();
        $descr = 'Gibt es eine aktuelle Dokumentation dieser Applikation?';
        $opt = 'EXTRA_DOC';
        $stmt->execute();

        $opt = 'APP_MATR_DESCR';
        $stmt->execute();
        $opt = 'APP_GROUP_1';
        $descr = 'Kunde';
        $stmt->execute();
        $opt = 'APP_GROUP_2';
        $descr = 'Lieferanten und Partner';
        $stmt->execute();
        $opt = 'APP_GROUP_3';
        $descr = 'Mitarbeiter';
        $stmt->execute();
        $i = 1;
        $cat_descr = array('', 'Firmenname', 'Ansprechpartner, E-Mail, Telefon', 'Straße', 'Ort', 'Bankverbindung', 'Zahlungsdaten', 'UID', 'Firmenbuchnummer');
        while ($i < 9) { //Kunde
            $opt = 'APP_CAT_1_' . $i;
            $descr = $cat_descr[$i];
            $stmt->execute();
            $i++;
        }
        $i = 1;
        while ($i < 9) { //Lieferanten und Partner
            $opt = 'APP_CAT_2_' . $i;
            $descr = $cat_descr[$i];
            $stmt->execute();
            $i++;
        }
        $cat_descr = array('', 'Nachname', 'Vorname', 'PLZ', 'Ort', 'Telefon', 'Geb. Datum', 'Lohn und Gehaltsdaten', 'Religion', 'Gewerkschaftszugehörigkeit', 'Familienstand',
            'Anwesenheitsdaten', 'Bankverbindung', 'Sozialversicherungsnummer', 'Beschäftigt als', 'Staatsbürgerschaft', 'Geschlecht', 'Name, Geb. Datum und Sozialversicherungsnummer des Ehegatten',
            'Name, Geb. Datum und Sozialversicherungsnummer der Kinder', 'Personalausweis, Führerschein', 'Abwesenheitsdaten', 'Kennung');
        $i = 1;
        while ($i < 22) { //Mitarbeiter
            $opt = 'APP_CAT_3_' . $i;
            $descr = $cat_descr[$i];
            $stmt->execute();
            $i++;
        }
        $descr = '';
        $i = 1;
        while ($i < 21) { //20 App Spaces
            $opt = 'APP_HEAD_' . $i;
            $descr = $cat_descr[$i];
            $stmt->execute();
            $i++;
        }
    }
    $stmt->close();
    $stmt_vv->close();
}

if ($row['version'] < 119) {
    $conn->query("CREATE TABLE erp_settings(
	companyID INT(6) UNSIGNED,
	erp_ang INT(5) DEFAULT 1,
	erp_aub INT(5) DEFAULT 1,
	erp_re INT(5) DEFAULT 1,
	erp_lfs INT(5) DEFAULT 1,
	erp_gut INT(5) DEFAULT 1,
	erp_stn INT(5) DEFAULT 1,
	yourSign VARCHAR(30),
	yourOrder VARCHAR(30),
	ourSign VARCHAR(30),
	ourMessage VARCHAR(30),
	clientNum VARCHAR(12),
	clientStep INT(2),
	supplierNum VARCHAR(12),
	supplierStep INT(2),
	FOREIGN KEY (companyID) REFERENCES companyData(id)
	ON UPDATE CASCADE
	ON DELETE CASCADE
	)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Zähleinstellungen: Lieferanten und Kunden';
    }
    $conn->query("INSERT INTO erp_settings (companyID, erp_ang, erp_aub, erp_re, erp_lfs, erp_gut, erp_stn, yourSign, yourOrder, ourSign, ourMessage, clientNum, clientStep, supplierNum, supplierStep)
	SELECT companyID, erp_ang, erp_aub, erp_re, erp_lfs, erp_gut, erp_stn, yourSign, yourOrder, ourSign, ourMessage, '1000', '1', '1000', '1' FROM erpNumbers");
    echo $conn->error;
    $conn->query("DROP TABLE erpNumbers");
    echo $conn->error;

    $conn->query("ALTER TABLE UserData ADD COLUMN supervisor INT(6) DEFAULT NULL ");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Benutzer: Vorgesetzter';
    }

    $conn->query("ALTER TABLE clientInfoData ADD COLUMN homepage VARCHAR(100)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Datenstamm: Homepage';
    }
    $conn->query("ALTER TABLE clientInfoData ADD COLUMN mail VARCHAR(100)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Datenstamm: Allgemeine E-Mails';
    }

    $conn->query("ALTER TABLE clientInfoData ADD COLUMN billDelivery VARCHAR(60)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kundendetails: Rechnungsversand';
    }

    $conn->query("ALTER TABLE roles ADD COLUMN isDynamicProjectsAdmin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
    $conn->query("ALTER TABLE modules ADD COLUMN enableDynamicProjects ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");

    $conn->multi_query("CREATE TABLE dynamicprojects(
    projectid VARCHAR(100) NOT NULL,
    projectdataid INT(6) UNSIGNED,
    projectname VARCHAR(60) NOT NULL,
    projectdescription VARCHAR(500) NOT NULL,
    companyid INT(6),
    projectcolor VARCHAR(10),
    projectstart VARCHAR(12),
    projectend VARCHAR(12),
    projectstatus ENUM('ACTIVE', 'DEACTIVATED', 'DRAFT', 'COMPLETED') DEFAULT 'ACTIVE',
    projectpriority INT(6),
    projectparent VARCHAR(100),
    projectowner INT(6),
    projectcompleted INT(6),
    PRIMARY KEY (`projectid`)
  );
  CREATE TABLE dynamicprojectsclients(
    projectid VARCHAR(100) NOT NULL,
    clientid INT(6),
    FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  );
  CREATE TABLE dynamicprojectsemployees(
    projectid VARCHAR(100) NOT NULL,
    userid INT(6),
    FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  );
  CREATE TABLE dynamicprojectsoptionalemployees(
    projectid VARCHAR(100) NOT NULL,
    userid INT(6),
    FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  );
  CREATE TABLE dynamicprojectspictures(
    projectid VARCHAR(100) NOT NULL,
    picture MEDIUMBLOB,
    FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  );
  CREATE TABLE dynamicprojectsseries(
    projectid VARCHAR(100) NOT NULL,
    projectnextdate VARCHAR(12),
    projectseries MEDIUMBLOB,
    FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  );
  CREATE TABLE dynamicprojectsnotes(
    projectid VARCHAR(100) NOT NULL,
    noteid INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notedate DATETIME DEFAULT CURRENT_TIMESTAMP,
    notetext VARCHAR(1000),
    notecreator INT(6),
    FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
  );
  CREATE TABLE dynamicprojectsbookings(
    projectid VARCHAR(100) NOT NULL,
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bookingstart DATETIME DEFAULT CURRENT_TIMESTAMP,
    bookingend DATETIME,
    userid INT(6) UNSIGNED,
    bookingtext VARCHAR(1000)
  );
  ");
}

if ($row['version'] < 120) {
    $conn->query("ALTER TABLE projectData ADD COLUMN dynamicprojectid VARCHAR(100)");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>projectData: +dynamicprojectid';
    }
    $conn->query("ALTER TABLE dynamicprojects DROP COLUMN projectdataid");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojects: -dynamicprojectid';
    }
}

if ($row['version'] < 121) {
    $conn->query("ALTER TABLE dynamicprojectsclients ADD COLUMN projectcompleted INT(6)");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojectsclients: +projectcompleted';
    }
    $conn->query("ALTER TABLE dynamicprojects DROP COLUMN projectcompleted");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojects: -projectcompleted';
    }
}

if ($row['version'] < 122) {
    $conn->query("ALTER TABLE dynamicprojectsbookings ADD COLUMN bookingclient INT(6) UNSIGNED");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojectsbookings: +bookingclient';
    }
    $conn->query("ALTER TABLE modules MODIFY COLUMN enableDynamicProjects ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>Dynamic Projects by default';
    }
    $conn->query("UPDATE modules SET enableDynamicProjects = 'TRUE'");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>Dynamic Projects enabled';
    }
    $conn->query("CREATE TABLE dynamicprojectsteams(
    projectid VARCHAR(100) NOT NULL,
    teamid INT(6) UNSIGNED,
    FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
    ON UPDATE CASCADE
    ON DELETE CASCADE
    );");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojectsteams';
    }
    $conn->query("ALTER TABLE dynamicprojectsemployees ADD PRIMARY KEY(projectid, userid);");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>dynamicprojectsteams';
    }
}

if ($row['version'] < 123) {
    $conn->query("CREATE TABLE sharedfiles (
	  id int(11) NOT NULL AUTO_INCREMENT,
	  name varchar(20) NOT NULL COMMENT 'ursprünglicher Name der Datei',
	  type varchar(10) NOT NULL COMMENT 'Dateiendung',
	  owner int(11) NOT NULL COMMENT 'User der die Datei hochgeladen hat',
	  sharegroup int(11) NOT NULL COMMENT 'in welcher Gruppe sie hinterlegt ist (groupID)',
	  hashkey varchar(32) NOT NULL COMMENT 'der eindeutige, sichere Key für den Link',
	  filesize bigint(20) NOT NULL,
	  uploaddate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  PRIMARY KEY (id),
	  UNIQUE KEY hashkey (hashkey),
	  KEY owner (owner)
	 )");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>s3Files';
    }

    $conn->query("CREATE TABLE sharedgroups (
	  id int(11) NOT NULL AUTO_INCREMENT COMMENT 'PK',
	  name varchar(50) NOT NULL COMMENT 'Name der SharedGruppe',
	  dateOfBirth timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Tag der Erstellung',
	  ttl int(10) NOT NULL COMMENT 'Tage bis der Link ungültig ist',
	  uri varchar(100) NOT NULL COMMENT 'URL zu den Objekten',
	  owner int(11) NOT NULL COMMENT 'Besitzer der Gruppe',
	  files varchar(200) DEFAULT NULL,
	  company int(11) NOT NULL COMMENT 'Mandant',
	  PRIMARY KEY (id),
	  UNIQUE KEY url (uri),
	  KEY owner (owner)
	  )");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>s3Groups';
    }

    $conn->query("CREATE TABLE uploadedfiles (
	  id INT NOT NULL AUTO_INCREMENT ,
	  uploadername VARCHAR NOT NULL ,
	  filename VARCHAR(20) NOT NULL ,
	  filetype VARCHAR(10) NOT NULL ,
	  hashkey VARCHAR(32) NOT NULL ,
	  filesize BIGINT(20) NOT NULL ,
	  uploaddate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
	  notes TEXT NULL ,
	  PRIMARY KEY (id),
	  UNIQUE hashkey (hashkey)
	  )");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>s3Upload';
    }

    $conn->query("ALTER TABLE mailingoptions ADD COLUMN senderName VARCHAR(50) DEFAULT NULL COMMENT 'Absendername'");

    $conn->query("ALTER TABLE mailingoptions ADD COLUMN isDefault TINYINT(1) NOT NULL DEFAULT 1");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>mailingoptions';
    }

    $conn->query("ALTER TABLE userdata ADD COLUMN publicPGPKey TEXT DEFAULT NULL");

    $conn->query("ALTER TABLE userdata ADD COLUMN privatePGPKey TEXT DEFAULT NULL");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>PGPKeys';
    }

    $conn->query("ALTER TABLE contactPersons ADD COLUMN dial VARCHAR(20)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: Durchwahl';
    }

    $conn->query("ALTER TABLE contactPersons ADD COLUMN faxDial VARCHAR(20)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: Faxfurchwahl';
    }

    $conn->query("ALTER TABLE contactPersons ADD COLUMN phone VARCHAR(25)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: Mobiltelefon';
    }

    //ALTER TABLE `documents` DROP INDEX `docID`;
    $conn->query("ALTER TABLE documents ADD COLUMN docID VARCHAR(40)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Vereinbarungen: Template ID';
    }

    $conn->query("ALTER TABLE documents ADD COLUMN isBase ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'FALSE' ");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Vereinbarungen: Basis Templates';
    }

    $conn->query("CREATE TABLE document_customs(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		companyID INT(6) UNSIGNED,
		doc_id VARCHAR(40) NOT NULL,
		identifier VARCHAR(30),
		content VARCHAR(450),
		status VARCHAR(10),
		FOREIGN KEY (companyID) REFERENCES companyData(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Vereinbarungen: Freitext';
    }

    $conn->query("ALTER TABLE documentProcess ADD COLUMN document_text MEDIUMTEXT NOT NULL");

    $conn->query("ALTER TABLE documentProcess ADD COLUMN document_headline VARCHAR(120) NOT NULL");

    $conn->query("ALTER TABLE documentProcess ADD COLUMN document_version VARCHAR(15) NOT NULL DEFAULT '1.0' ");
}

// ------------------------------------------------------------------------------

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
