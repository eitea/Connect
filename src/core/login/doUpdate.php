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

<body>
  <div id="progressBar_grey">
    <div id="progress_text">0%</div>
    <div id="progress">.</div>
  </div>
<br>
<?php
require dirname(dirname(__DIR__)) . "/connection.php";
require dirname(dirname(__DIR__)) . "/utilities.php";
require_once dirname(dirname(__DIR__)) . '/validate.php';
require_once dirname(dirname(__DIR__)) . '/core/setup/setup_permissions.php';
set_time_limit(240);
$result = mysqli_query($conn, "SELECT version FROM configurationData;");
if(!$result){
    die($conn->error);
} else {
    $row = $result->fetch_assoc();
}

if ($row['version'] < 100) {
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

    $holidayFile = dirname(dirname(__DIR__)) . '/setup/Feiertage.txt';
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
    $conn->query("ALTER TABLE articles CHANGE name name VARCHAR(255)"); //50 -> 255
    $conn->query("ALTER TABLE articles CHANGE description description VARCHAR(1200)"); //600 -> 1200
    $conn->query("ALTER TABLE products CHANGE name name VARCHAR(255)"); //50 -> 255
    $conn->query("ALTER TABLE products CHANGE description description VARCHAR(600)"); //300 -> 600

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
    $file = fopen(dirname(dirname(__DIR__)) . '/setup/Kontoplan.csv', 'r');
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
    $file = fopen(dirname(dirname(__DIR__)) . '/setup/Steuerraten.csv', 'r');
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
    $stmt = $conn->prepare("INSERT INTO products (name, description, historyID, origin, price, quantity, unit, taxID, cash, purchase, position) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisddsiddiss", $name, $desc, $historyID, $origin, $price, $quantity, $unit, $taxID, $cash, $purchase, $pos);
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

    $conn->query("ALTER TABLE clientInfoData ADD COLUMN address_Addition VARCHAR(150)");
    $conn->query("ALTER TABLE documents MODIFY COLUMN name VARCHAR(100) NOT NULL");

    $conn->query("ALTER TABLE clientInfoData ADD COLUMN billingMailAddress VARCHAR(100)");
    if (!$conn->error) {
        echo '<br>Kundenstamm: Rechnungs Email Adresse';
    }
    $sql = "CREATE TABLE dsgvo_vv_templates(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		companyID INT(6) UNSIGNED,
		name VARCHAR(60) NOT NULL,
		type ENUM('base', 'app') NOT NULL,
		FOREIGN KEY (companyID) REFERENCES companyData(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnis: Templates';
    }
    $sql = "CREATE TABLE dsgvo_vv_template_settings(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		templateID INT(6) UNSIGNED,
		opt_name VARCHAR(30) NOT NULL,
		opt_descr VARCHAR(350) NOT NULL,
		opt_status VARCHAR(15) NOT NULL DEFAULT 'ACTIVE',
		FOREIGN KEY (templateID) REFERENCES dsgvo_vv_templates(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnis: Template Einstellungen';
    }
    $sql = "CREATE TABLE dsgvo_vv(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		templateID INT(6) UNSIGNED,
		name VARCHAR(60) NOT NULL,
		FOREIGN KEY (templateID) REFERENCES dsgvo_vv_templates(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)";
    if (!$conn->query($sql)) {
        echo '<br>' . $conn->error;
    } else {
        echo '<br>Verfahrensverzeichnisse erstellt.';
    }
    $sql = "CREATE TABLE dsgvo_vv_settings(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		vv_id INT(6) UNSIGNED,
		setting_id INT(10) UNSIGNED,
		setting VARCHAR(850) NOT NULL,
		category VARCHAR(50),
		FOREIGN KEY (vv_id) REFERENCES dsgvo_vv(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
		FOREIGN KEY (setting_id) REFERENCES dsgvo_vv_template_settings(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)";
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

    $sql = "CREATE TABLE dynamicprojects(
        projectid VARCHAR(100) NOT NULL,
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
        PRIMARY KEY (`projectid`)
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks';
    }

    $sql = "CREATE TABLE dynamicprojectsclients(
        projectid VARCHAR(100) NOT NULL,
        clientid INT(6),
        projectcompleted INT(6),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Kunden';
    }

    $sql = "CREATE TABLE dynamicprojectsemployees(
        projectid VARCHAR(100) NOT NULL,
        userid INT(6),
        PRIMARY KEY(projectid, userid),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Employees';
    }

    $sql = "CREATE TABLE dynamicprojectsoptionalemployees(
        projectid VARCHAR(100) NOT NULL,
        userid INT(6),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Optional Employees';
    }

    $sql = "CREATE TABLE dynamicprojectspictures(
        projectid VARCHAR(100) NOT NULL,
        picture MEDIUMBLOB,
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Pictures';
    }

    $sql = "CREATE TABLE dynamicprojectsseries(
        projectid VARCHAR(100) NOT NULL,
        projectnextdate VARCHAR(12),
        projectseries MEDIUMBLOB,
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Series';
    }

    $sql = "CREATE TABLE dynamicprojectsnotes(
        projectid VARCHAR(100) NOT NULL,
        noteid INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        notedate DATETIME DEFAULT CURRENT_TIMESTAMP,
        notetext VARCHAR(1000),
        notecreator INT(6),
        FOREIGN KEY (projectid) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Notizen';
    }

    $sql = "CREATE TABLE dynamicprojectsbookings(
        projectid VARCHAR(100) NOT NULL,
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        bookingstart DATETIME DEFAULT CURRENT_TIMESTAMP,
        bookingend DATETIME,
        bookingclient INT(6) UNSIGNED,
        userid INT(6) UNSIGNED,
        bookingtext VARCHAR(1000)
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }
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
	  name varchar(20) NOT NULL,
	  type varchar(10) NOT NULL,
	  owner int(11) NOT NULL,
	  sharegroup int(11) NOT NULL,
	  hashkey varchar(32) NOT NULL,
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
	  id int(11) NOT NULL AUTO_INCREMENT,
	  name varchar(50) NOT NULL,
	  dateOfBirth timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  ttl int(10) NOT NULL,
	  uri varchar(128) NOT NULL,
	  owner int(11) NOT NULL,
	  files varchar(200) DEFAULT NULL,
	  company int(11) NOT NULL,
	  PRIMARY KEY (id),
	  UNIQUE KEY url (uri),
	  KEY owner (owner)
	  )");
    if ($conn->error) {
        $conn->error;
    } else {
        echo '<br>s3Groups';
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

if ($row['version'] < 124) {
    $conn->query("CREATE TABLE archiveconfig(endpoint VARCHAR(50),awskey VARCHAR(50),secret VARCHAR(50));");
    $conn->query("INSERT INTO archiveconfig VALUE (null,null,null)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>S3 Modul';
    }
    $conn->query("DROP TABLE modules");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Module: Auflösen';
    }

    $sql = "ALTER TABLE userRequestsData MODIFY COLUMN requestType VARCHAR(3) DEFAULT 'vac' NOT NULL;";
    if ($conn->query($sql)) {
        echo '<br> Extended requests by splitted lunchbreaks';
    }
}


if($row['version'] < 125){
    $conn->query("ALTER TABLE projectBookingData ADD COLUMN dynamicID VARCHAR(100)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Projektbuchungen: Task Referenz';
    }

    $result = $conn->query("SELECT projectid FROM dynamicprojects");
    while($row = $result->fetch_assoc()){
        $id = uniqid();
        $conn->query("UPDATE dynamicprojects SET projectid = '$id' WHERE projectid = '".$row['projectid']."'");
    }

    $result = $conn->query("SELECT id, dynamicprojectid FROM projectData WHERE dynamicprojectid IS NOT NULL");
    while($row = $result->fetch_assoc()){
        $conn->query("UPDATE projectBookingData SET dynamicID = '".$row['dynamicprojectid']."' WHERE projectID = ".$row['id']);
        echo $conn->error;

        $conn->query("UPDATE projectBookingData SET projectID = 350 WHERE projectID = ".$row['id']);
        echo $conn->error;
    }

    $conn->query("DELETE FROM projectData WHERE dynamicprojectid IS NOT NULL");
    $conn->query("ALTER TABLE projectData DROP COLUMN dynamicprojectid");

    $conn->query("ALTER TABLE dynamicprojects MODIFY COLUMN projectdescription MEDIUMTEXT NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Beschreibung';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectseries MEDIUMBLOB");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Routine Tasks';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectnextdate VARCHAR(12)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Umbelegung';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN clientid INT(6) UNSIGNED");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Kunde';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN clientprojectid INT(6) UNSIGNED");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Projekt ID';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectpercentage INT(3) DEFAULT 0");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Projekt Prozentsatz';
    }

    $conn->query("ALTER TABLE dynamicprojectsemployees ADD COLUMN position VARCHAR(10) DEFAULT 'normal' NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Position';
    }
    $conn->query("DROP TABLE dynamicprojectsclients");
    $conn->query("DROP TABLE dynamicprojectsseries");
    $conn->query("DROP TABLE dynamicprojectsbookings");

    $conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid, position) SELECT (projectid, userid, 'optional') FROM dynamicprojectsoptionalemployees");
    $conn->query("DROP TABLE dynamicprojectsoptionalemployees");

    $conn->query("DELETE FROM dynamicprojectsemployees WHERE projectid IN (SELECT projectid FROM dynamicprojectsteams)");
}

if($row['version'] < 126){ //25.01.2018
    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectleader INT(6)");
    $conn->query("UPDATE dynamicprojects SET projectleader = projectowner");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Projektleiter';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD estimatedHours INT(4) DEFAULT 0 NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Geschätzte Zeit';
    }

    $conn->query("CREATE TABLE dynamicprojectslogs(
        projectid VARCHAR(100) NOT NULL,
        activity VARCHAR(20) NOT NULL,
        logTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        userID INT(6),
        extra1 VARCHAR(250),
        extra2 VARCHAR(450)
    )");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Aktivitäten Log';
    }
}

if($row['version'] < 127){ //29.01.2018
    $conn->query("ALTER TABLE relationship_team_user ADD COLUMN skill INT(3) DEFAULT 0 NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Teams: Skill-Level';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN level INT(3) DEFAULT 0 NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Skill-Level';
    }
}

if($row['version'] < 128){ //30.01.2018
    $conn->query("ALTER TABLE taskData CHANGE repeatPattern repeatPattern ENUM('-1','0','1','2','3','4','5') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT '-1'");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Email Projekte';
    }

    $conn->query("CREATE TABLE emailprojects (
    id INT(10) NOT NULL AUTO_INCREMENT,
    server VARCHAR(50) NOT NULL,
    port VARCHAR(50) NOT NULL,
    service ENUM('imap','pop3') NOT NULL,
    smtpSecure ENUM('','tls','ssl') NOT NULL,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL,
    logEnabled ENUM('TRUE','FALSE') NOT NULL,
    PRIMARY KEY (id))");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Projekte: Emails';
    }
    $conn->query("ALTER TABLE articles ADD companyID INT(6) AFTER id");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Artikel: Mandanten-Bezogen';
    }

    $conn->query("ALTER TABLE teamData ADD leader INT(6), ADD leaderreplacement INT(6)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Teams: Leader-Update';
    }
}

if($row['version'] < 129){ //31.01.2018
    $conn->query("ALTER TABLE mailingOptions ADD COLUMN feedbackRecipient VARCHAR(50) DEFAULT 'office@eitea.at'");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Mailing: Feedback recipient';
    }

    $conn->query("ALTER TABLE dynamicprojects ADD needsreview ENUM('TRUE','FALSE') DEFAULT 'TRUE' NOT NULL");
    $conn->query("ALTER TABLE dynamicprojects CHANGE projectstatus projectstatus ENUM('ACTIVE','DEACTIVATED','DRAFT','COMPLETED','REVIEW') CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT 'ACTIVE';");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Review-Update';
    }
}

if($row['version'] < 130){ //01.02.2018
    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN projecttags VARCHAR(250) DEFAULT '' NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Tags';
    }

    $conn->query("CREATE TABLE microtasks (
        projectid varchar(100) NOT NULL,
        microtaskid varchar(100) NOT NULL,
        title varchar(50) NOT NULL,
        ischecked enum('TRUE','FALSE') NOT NULL DEFAULT 'FALSE',
        finisher int(6) DEFAULT NULL COMMENT 'user who completes this microtask',
        completed timestamp NULL DEFAULT NULL,
        PRIMARY KEY (projectid,microtaskid))");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: MicroTask-Update';
    }

    $conn->query("CREATE TABLE taskemailrules (
        id int(6) NOT NULL AUTO_INCREMENT,
        identifier varchar(20) NOT NULL,
        company int(6) NOT NULL,
        client int(6) NOT NULL,
        clientproject int(6) DEFAULT NULL,
        color varchar(10) NOT NULL DEFAULT '#FFFFFF',
        status enum('ACTIVE','DEACTIVATED','DRAFT','COMPLETED') NOT NULL DEFAULT 'ACTIVE',
        priority int(6) NOT NULL DEFAULT '3',
        parent varchar(100) DEFAULT NULL,
        owner int(6) NOT NULL,
        employees varchar(100) NOT NULL,
        optionalemployees varchar(100) DEFAULT NULL,
        emailaccount int(6) NOT NULL,
        leader int(6) DEFAULT NULL,
        PRIMARY KEY (id)
       )");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Email-Projects: Rulesets';
    }
}

if($row['version'] < 131){ //14.02.2018
    $conn->query("CREATE TABLE archive_folders (
        folderid INT(6) NOT NULL,
        userid INT(6) NOT NULL,
        name VARCHAR(30) NOT NULL,
        parent_folder INT(6) NOT NULL,
        UNIQUE KEY user_folder (userid, folderid),
        INDEX (parent_folder))");
    $conn->query("CREATE TABLE archive_editfiles (
        hashid VARCHAR(32) NOT NULL,
        body TEXT NOT NULL,
        version INT(6) NOT NULL DEFAULT 1,
        PRIMARY KEY (hashid,version))");
    $conn->query("CREATE TABLE archive_savedfiles (
        id INT(12) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        type VARCHAR(10) NOT NULL,
        folderid INT(6) NOT NULL,
        userid INT(6) NOT NULL,
        hashkey VARCHAR(32) UNIQUE NOT NULL,
        filesize BIGINT(20) NOT NULL,
        uploaddate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        isS3 ENUM('TRUE','FALSE') DEFAULT 'TRUE' NOT NULL)");

    $conn->query("INSERT INTO archive_folders(folderid, userid, name, parent_folder) SELECT 0, id, 'ROOT', -1 FROM UserData");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Private Archive: Data Tables';
    }

    $conn->query("DELETE FROM taskData WHERE id = 4");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>BugFix: Email Tasks';
    }

    $conn->query("ALTER TABLE UserData ADD forcedPwdChange TINYINT(1) NULL DEFAULT NULL;");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Forced Change Password';
    }

    $conn->query("CREATE TABLE position (
    id INT(6) NOT NULL AUTO_INCREMENT,
    name VARCHAR(20) NOT NULL,
    PRIMARY KEY (id)
    )");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Contact Persons: More Details';
    }

    $conn->query("ALTER TABLE dynamicprojects MODIFY COLUMN estimatedHours VARCHAR(100) DEFAULT 0 NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Geschätzte Zeit';
    }

    $sql = "CREATE TABLE dsgvo_training (
        id int(6) NOT NULL AUTO_INCREMENT,
        name varchar(100),
        companyID INT(6) UNSIGNED,
        version INT(6) DEFAULT 0,
        onLogin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        PRIMARY KEY (id),
        FOREIGN KEY (companyID) REFERENCES companyData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: trainings';
    }

    $sql = "CREATE TABLE dsgvo_training_questions (
        id int(6) NOT NULL AUTO_INCREMENT,
        title varchar(100),
        text varchar(2000),
        trainingID INT(6),
        PRIMARY KEY (id),
        FOREIGN KEY (trainingID) REFERENCES dsgvo_training(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: questions';
    }

    $sql = "CREATE TABLE dsgvo_training_user_relations (
        trainingID int(6),
        userID INT(6) UNSIGNED,
        PRIMARY KEY (trainingID, userID),
        FOREIGN KEY (trainingID) REFERENCES dsgvo_training(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: user_relations';
    }

    $sql = "CREATE TABLE dsgvo_training_team_relations (
        trainingID int(6),
        teamID INT(6) UNSIGNED,
        PRIMARY KEY (trainingID, teamID),
        FOREIGN KEY (trainingID) REFERENCES dsgvo_training(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (teamID) REFERENCES teamData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: team_relations';
    }

    $sql = "CREATE TABLE dsgvo_training_completed_questions (
        questionID int(6),
        userID INT(6) UNSIGNED,
        correct ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        PRIMARY KEY (questionID, userID),
        FOREIGN KEY (questionID) REFERENCES dsgvo_training_questions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: completed_quesitons';
    }
}

if($row['version'] < 132){//14.02.2018
    $conn->query("INSERT INTO position (name) VALUES ('GF'),('Management'),('Leitung')");
    $result = $conn->query("SELECT position FROM contactPersons GROUP BY position");
    if($result){
        while($row = $result->fetch_assoc()){
            $conn->query("UPDATE contactPersons SET position = (SELECT id FROM position WHERE name = '".$row['position']."') WHERE position = '".$row['position']."'");
        }
    }
    $conn->query("ALTER TABLE contactPersons CHANGE position position INT(6) NOT NULL;");
}
if($row['version'] < 133){
    $sql = "CREATE TABLE emailprojectlogs (
        id int(11) PRIMARY KEY,
        timeofoccurence TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        body text
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Debugging: Email Projects';
    }
}
if($row['version'] < 134){
    $sql = "ALTER TABLE sharedfiles CHANGE name name varchar(60) NOT NULL COMMENT 'ursprünglicher Name der Datei'";
    $conn->query($sql);
    $sql = "ALTER TABLE sharedgroups CHANGE uri uri varchar(128) NOT NULL COMMENT 'URL zu den Objekten'";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Repairing: Archive';
    }
    $sql = "ALTER TABLE roles ADD canCreateTasks ENUM('TRUE','FALSE') DEFAULT 'TRUE' NOT NULL";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Role: Can Create Task';
    }
    $conn->query("ALTER TABLE dsgvo_training_completed_questions ADD COLUMN version INT(6) DEFAULT 0");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Training: version';
    }
    $conn->query("ALTER TABLE dsgvo_training ADD COLUMN allowOverwrite ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Training: overwrite';
    }
    $conn->query("ALTER TABLE dsgvo_training_completed_questions ADD COLUMN tries INT(6) DEFAULT 1");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Training: tries';
    }
    $conn->query("ALTER TABLE dsgvo_training ADD COLUMN random ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Training: random';
    }
    $conn->query("ALTER TABLE dsgvo_training_completed_questions ADD COLUMN duration INT(6) DEFAULT 0");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Training: random';
    }
}

if($row['version'] < 135){
    $conn->query("ALTER TABLE contactPersons ADD COLUMN gender ENUM('male','female') NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: Gender';
    }
    $conn->query("ALTER TABLE contactPersons ADD COLUMN title VARCHAR(20)");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: Titel';
    }
    $conn->query("ALTER TABLE contactPersons ADD COLUMN pgpKey TEXT");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Kontaktpersonen: PGP Key';
    }
}
if ($row['version'] < 136) {
    $sql = "CREATE OR REPLACE TABLE emailprojectlogs (
        id int(11),
        timeofoccurence TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        body text,
        PRIMARY KEY (id)
        )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Debugging: Email Projects';
    }
    $sql = "ALTER TABLE archiveconfig ADD isActive ENUM('TRUE','FALSE') NOT NULL DEFAULT 'FALSE';";
    $conn->query($sql);
    $sql = "ALTER TABLE archiveconfig ADD id INT(6) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (id);";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Archive: Multiple Configs';
    }
    $sql = "ALTER TABLE emailprojects CHANGE smtpSecure smtpSecure ENUM('tls','ssl','null') NOT NULL DEFAULT 'null';";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Bugfix: Email Tasks';
    }

    $conn->query("CREATE OR REPLACE position (
        id int(6) NOT NULL AUTO_INCREMENT,
        name varchar(20) NOT NULL,
        PRIMARY KEY (id)
        )");
    $conn->query("INSERT INTO position (name) VALUES ('GF'),('Management'),('Leitung')");
    $conn->query("INSERT INTO position (name) SELECT position FROM contactPersons GROUP BY position");
    $result = $conn->query("SELECT position FROM contactPersons GROUP BY position");
    if($result){
        while($row = $result->fetch_assoc()){
            $conn->query("UPDATE contactPersons SET position = (SELECT id FROM position WHERE name = '".$row['position']."') WHERE position = '".$row['position']."'");
        }
    }
    $sql = "ALTER TABLE contactPersons CHANGE position position INT(6) NOT NULL, ADD form_of_address ENUM('Herr','Frau') NOT NULL, ADD titel VARCHAR(20) DEFAULT null, ADD pgpKey TEXT DEFAULT null";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Position: Fixed List';
    }
}
if($row['version'] < 137){
    $sql = "ALTER TABLE archiveconfig ADD name VARCHAR(30) NOT NULL DEFAULT 'NO_NAME'";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Archive User Module';
    }
    $sql = "ALTER TABLE contactPersons DROP titel, DROP form_of_address";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Fixing Contact Persons';
    }
    $sql = "DELETE FROM position";
    $conn->query($sql);
    $conn->query("INSERT INTO position (name) VALUES ('GF'),('Management'),('Leitung')");
    $conn->query("UPDATE contactPersons SET position = '';");
    $conn->query("ALTER TABLE contactPersons CHANGE position position INT(6) NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Fixing Contact Persons v2';
    }

    $sql = "ALTER TABLE sharedgroups DROP INDEX url;";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        $sql = "SET GLOBAL event_scheduler = ON;";
        if (!$conn->query($sql)) {
            echo $conn->error;
        } else {
            echo '<br>Auto-Delete Dead Links';
        }
    }
    $sql = "ALTER TABLE emailprojectlogs CHANGE id id INT(11) NOT NULL AUTO_INCREMENT, CHANGE body body LONGTEXT DEFAULT null ";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Bug Fixes';
    }
    $sql = "ALTER TABLE dynamicprojects CHANGE projectdescription projectdescription MEDIUMTEXT;";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Bigger Task Description (Max. 15MB)';
    }

    $sql = "ALTER TABLE roles ADD COLUMN canUseClients ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Role: Can use clients';
    }
    $sql = "ALTER TABLE roles ADD COLUMN canUseSuppliers ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Role: Can use suppliers';
    }
    $sql = "ALTER TABLE roles ADD COLUMN canEditClients ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Role: Can edit clients';
    }
    $sql = "ALTER TABLE roles ADD COLUMN canEditSuppliers ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Role: Can edit suppliers';
    }
}
if($row['version'] < 138){
    $sql = "CREATE TABLE dsgvo_training_modules (
        id int(6) NOT NULL AUTO_INCREMENT,
        name varchar(100),
        PRIMARY KEY (id)
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: modules';
    }
    $sql = "ALTER TABLE dsgvo_training ADD COLUMN moduleID int(6)";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: module id';
    }
    $sql = "ALTER TABLE dsgvo_training ADD FOREIGN KEY (moduleID) REFERENCES dsgvo_training_modules(id) ON UPDATE CASCADE ON DELETE CASCADE";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: module constraint';
    }
    // add previous trainings to a module (can't be displayed otherwise)
    $sql = "INSERT INTO dsgvo_training_modules (name) VALUES ('before module update')";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: add new module';
    }
    $moduleID = mysqli_insert_id($conn);
    $sql = "UPDATE dsgvo_training SET moduleID = $moduleID";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: add to new module';
    }
}

if($row['version'] < 139){
    $sql = "ALTER TABLE dsgvo_training_completed_questions ADD COLUMN lastAnswered DATETIME DEFAULT CURRENT_TIMESTAMP";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: last answered';
    }
    $sql = "ALTER TABLE dsgvo_training ADD COLUMN answerEveryNDays int(6)";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Training: answer every n days';
    }

    $conn->query("ALTER TABLE configurationData ADD COLUMN firstTimeWizard ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Setup: Installation Wizard';
    }
    $conn->query("UPDATE configurationData SET firstTimeWizard = 'TRUE'");

    $conn->query("ALTER TABLE configurationData ADD COLUMN activeEncryption ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Security Update: Inactive';
    }

    $conn->query("ALTER TABLE configurationData DROP COLUMN masterPassword");
    $conn->query("ALTER TABLE configurationData DROP COLUMN checkSum");

    $sql = "CREATE TABLE security_modules(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        module VARCHAR(50) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        symmetricKey VARCHAR(150) NOT NULL,
        publicKey VARCHAR(150) NOT NULL,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Security Update: Module Keys';
    }

    $sql = "CREATE TABLE security_access(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        module VARCHAR(50) NOT NULL,
        privateKey VARCHAR(150) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Security Update: User Keys';
    }

    $sql = "CREATE TABLE security_company(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED,
        companyID INT(6) UNSIGNED NOT NULL,
        privateKey VARCHAR(150) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Security Update: Company Keys';
    }
}

if($row['version'] < 140){
    $sql = "ALTER TABLE dynamicprojects ADD isTemplate ENUM('TRUE','FALSE') NOT NULL DEFAULT 'FALSE';";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Task Update: Templates';
    }
    $sql = "ALTER TABLE taskemailrules ADD estimatedHours VARCHAR(100) NOT NULL DEFAULT '0';";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Email-Tasks Update: estimatedHours';
    }
}

if($row['version'] < 141){
    $conn->query("ALTER TABLE teamData ADD COLUMN isDepartment ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'FALSE'");
    if(!$conn->error){
        echo '<br>Team Update: Abteilungen';
    }

    $conn->query("ALTER TABLE dynamicprojects MODIFY COLUMN projectstart DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL");
    if(!$conn->error){
        echo '<br>Tasks Update: Aktiv - Geplant';
    }
    $conn->query("ALTER TABLE dynamicprojects MODIFY COLUMN projectend DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL");

    $conn->query("DELETE FROM dsgvo_training_questions WHERE id = 1 OR id = 2");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Training: remove test questions';
    }

    $conn->query("ALTER TABLE dynamicprojectsteams ADD CONSTRAINT fk_team_id FOREIGN KEY (teamid) REFERENCES teamData(id) ON UPDATE CASCADE ON DELETE CASCADE");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>FK: task - teams';
    }

    $conn->query("ALTER TABLE dynamicprojectsemployees MODIFY COLUMN userid INT(6) UNSIGNED");
    $conn->query("ALTER TABLE dynamicprojectsemployees ADD CONSTRAINT fk_user_id FOREIGN KEY (userid) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>FK: task - employees';
    }

    $conn->query("ALTER TABLE dynamicprojectslogs MODIFY COLUMN userid INT(6) UNSIGNED");
    $conn->query("ALTER TABLE dynamicprojectslogs ADD CONSTRAINT fk_user_id FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>FK: task - logs';
    }

    $conn->query("ALTER TABLE dynamicprojects MODIFY COLUMN projectowner INT(6) UNSIGNED");
    $conn->query("ALTER TABLE dynamicprojects ADD CONSTRAINT fk_owner_id FOREIGN KEY (projectowner) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>FK: task - owner';
    }

    $conn->query("ALTER TABLE dynamicprojects MODIFY COLUMN projectleader INT(6) UNSIGNED");
    $conn->query("ALTER TABLE dynamicprojects ADD CONSTRAINT fk_leader_id FOREIGN KEY (projectleader) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE SET NULL");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>FK: task - leader';
    }
}

if($row['version'] < 142){
    $conn->query("CREATE TABLE messages(
        messageID INT(6) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        userID INT(6) UNSIGNED NOT NULL,
        partnerID INT(6) UNSIGNED NOT NULL,
        subject varchar(60),
        message TEXT,
        picture MEDIUMBLOB,
        sent DATETIME DEFAULT CURRENT_TIMESTAMP,
        seen ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'
        )");
    if(!$conn->error){
        echo $conn->error;
    } else {
        echo '<br>Team Update: Messages';
    }

    if(!$conn->error){
        echo '<br>PGP: keypairs';
    }

    $sql = "CREATE TABLE external_users(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        contactID INT(6) UNSIGNED,
        login_mail VARCHAR(120) UNIQUE NOT NULL,
        login_pw VARCHAR(120) NOT NULL,
        firstname VARCHAR(50),
        lastname VARCHAR(50),
        publicKey VARCHAR(100) NOT NULL,
        privateKey VARCHAR(100) NOT NULL,
        FOREIGN KEY (contactID) REFERENCES contactPersons(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Extern: Benutzer';
    }
}

if($row['version'] < 143){
    $conn->query("ALTER TABLE external_users ADD COLUMN entryDate DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL");
    if(!$conn->error){
        echo '<br>Externe Benutzer: Registrierungsdatum';
    }

    $conn->query("ALTER TABLE external_users ADD COLUMN lastPswChange DATETIME DEFAULT NULL");
    if(!$conn->error){
        echo '<br>Externe Benutzer: Passwortänderungsdatum';
    }

    $conn->query("ALTER TABLE external_users DROP COLUMN firstname");
    $conn->query("ALTER TABLE external_users DROP COLUMN lastname");
}

if($row['version'] < 144){
    $conn->query("ALTER TABLE projectData ADD COLUMN creator INT(6)");
    if(!$conn->error){
        echo '<br>Projekte: Ersteller';
    } else {
        echo '<br>'.$conn->error;
    }

    $conn->query("ALTER TABLE projectData ADD COLUMN publicKey VARCHAR(150)");
    if(!$conn->error){
        echo '<br>Projekte: Public Key';
    } else {
        echo '<br>'.$conn->error;
    }

    $conn->query("ALTER TABLE projectData ADD COLUMN symmetricKey VARCHAR(150)");
    if(!$conn->error){
        echo '<br>Projekte: Symmetric Key';
    } else {
        echo '<br>'.$conn->error;
    }

    $sql = "CREATE TABLE security_projects(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        projectID INT(6) UNSIGNED,
        privateKey VARCHAR(150) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (projectID) REFERENCES projectData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Security: Projekte';
    }

    $sql = "CREATE TABLE relationship_project_user(
        projectID INT(6) UNSIGNED,
        userID INT(6) UNSIGNED,
        access VARCHAR(10) NOT NULL DEFAULT 'READ',
        expirationDate DATE NOT NULL DEFAULT '0000-00-00',
        FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (projectID) REFERENCES projectData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Project: Relation Users';
    }

    $sql = "CREATE TABLE relationship_project_extern(
        projectID INT(6) UNSIGNED,
        userID INT(6) UNSIGNED,
        access VARCHAR(10) NOT NULL DEFAULT 'READ',
        expirationDate DATE NOT NULL DEFAULT '0000-00-00',
        FOREIGN KEY (userID) REFERENCES external_users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
        FOREIGN KEY (projectID) REFERENCES projectData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Project: Relation Extern';
    }
}

if($row['version'] < 145){
    $sql = "CREATE TABLE dsgvo_vv_data_matrix (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        companyID INT(6) UNSIGNED,
        FOREIGN KEY (companyID) REFERENCES companyData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }else{
        echo '<br>DSGVO: Data Matrix';
    }

    $sql = "CREATE TABLE dsgvo_vv_data_matrix_settings (
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        matrixID INT(6) UNSIGNED,
        opt_name VARCHAR(30) NOT NULL,
        opt_descr VARCHAR(350) NOT NULL,
        opt_status VARCHAR(15) NOT NULL DEFAULT 'ACTIVE',
        UNIQUE KEY (matrixID,opt_name),
        FOREIGN KEY (matrixID) REFERENCES dsgvo_vv_data_matrix(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }else{
        echo '<br>DSGVO: Data Matrix Settings';
    }

    $conn->query("ALTER TABLE dsgvo_vv_settings ADD COLUMN matrix_setting_id INT(10) UNSIGNED");
    if($conn->error){
        echo $conn->error;
    }else{
        echo '<br>DSGVO: Data Matrix Settings Foreign Key';
    }

    $conn->query("ALTER TABLE dsgvo_vv_settings ADD FOREIGN KEY (matrix_setting_id) REFERENCES dsgvo_vv_data_matrix_settings(id) ON UPDATE CASCADE ON DELETE SET NULL");
    if($conn->error){
        echo $conn->error;
    }else{
        echo '<br>DSGVO: Data Matrix Settings Foreign Key';
    }

    $conn->query("ALTER TABLE dsgvo_vv_settings ADD COLUMN clientID INT(6) UNSIGNED");
    if($conn->error){
        echo $conn->error;
    }else{
        echo '<br>DSGVO: VV Settings Client';
    }

    $conn->query("ALTER TABLE dsgvo_vv_settings ADD FOREIGN KEY (clientID) REFERENCES clientData(id) ON UPDATE CASCADE ON DELETE SET NULL");
    if($conn->error){
        echo $conn->error;
    }else{
        echo '<br>DSGVO: VV Settings Client Foreign Key';
    }

    $conn->query("CREATE TABLE dsgvo_vv_logs (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(6) UNSIGNED,
        log_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        short_description VARCHAR(100) NOT NULL,
        scope VARCHAR(100),
        long_description VARCHAR(500),
        FOREIGN KEY (user_id) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>DSGVO: Logs';
    }
}

if($row['version'] < 147){
    //5ab7ae7596e5c
    $conn->query("ALTER TABLE roles ADD COLUMN canUseWorkflow ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Rollen: Workflow';
    }

    //5ab7bd3310438
    $conn->query("ALTER TABLE UserData ADD COLUMN birthday DATE");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Benutzer: Geburtstag';
    }
    $conn->query("ALTER TABLE UserData ADD COLUMN displayBirthday ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Benutzer: Geburtstag Kalendereintrag';
    }

    //5abcfa8f314ae
    $conn->query("ALTER TABLE UserData ADD COLUMN companyID INT(6) UNSIGNED");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Benutzer: Hauptmandant';
    }
}

if($row['version'] < 148){
    $sql = "CREATE TABLE dsgvo_training_company_relations (
        trainingID int(6),
        companyID INT(6) UNSIGNED NOT NULL,
        PRIMARY KEY (trainingID, companyID),
        FOREIGN KEY (trainingID) REFERENCES dsgvo_training(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (companyID) REFERENCES companyData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    }else{
        echo '<br> DSGVO Training Company Relations';
    }
    $conn->query("ALTER TABLE security_projects ADD COLUMN publicKey VARCHAR(150) NOT NULL"); echo $conn->error;
    $conn->query("ALTER TABLE security_projects ADD COLUMN symmetricKey VARCHAR(150) NOT NULL"); echo $conn->error;

    $conn->query("UPDATE security_projects s, projectData p SET s.publicKey = p.publicKey, s.symmetricKey = p.symmetricKey WHERE s.projectID = p.id ");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Projects: better security storage';
    }

    $conn->query("ALTER TABLE projectData DROP COLUMN publicKey");
    $conn->query("ALTER TABLE projectData DROP COLUMN publicPGPKey");
    $conn->query("ALTER TABLE projectData DROP COLUMN symmetricKey");

    $conn->query("ALTER TABLE security_access ADD COLUMN optionalID VARCHAR(32)");
    $conn->query("INSERT INTO security_access (userID, module, privateKey, outDated, optionalID) SELECT userID, 'PRIVATE_PROJECT', privateKey, outDated, projectID FROM security_projects");

    $conn->query("ALTER TABLE security_projects DROP FOREIGN KEY security_projects_ibfk_1");
    $conn->query("ALTER TABLE security_projects DROP COLUMN userID");
    $conn->query("ALTER TABLE security_projects DROP COLUMN privateKey");

    //5acc47de619a8
    $conn->query("ALTER TABLE messages MODIFY COLUMN subject VARCHAR(250)");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>Nachrichten: Betrefflänge geändert';
    }
}

// 5ac63505c0ecd
if($row['version'] < 149){
    $sql = "CREATE TABLE taskmessages(
        userID INT(6) UNSIGNED,
        taskID varchar(100),
        taskName varchar(100),
        message TEXT,
        picture MEDIUMBLOB,
        sent DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    if (!$conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Task Messages hinzugefügt';
    }
}

if($row['version'] < 150) {
    $conn->query("ALTER TABLE dsgvo_training_questions ADD COLUMN version INT(6) DEFAULT 1");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>DSGVO Training: Question Version';
    }

    //5ad4376e05226
    $conn->query("ALTER TABLE mailingOptions MODIFY COLUMN feedbackRecipient VARCHAR(50) DEFAULT 'connect@eitea.at'");
    $conn->query("UPDATE mailingOptions SET feedbackRecipient = 'connect@eitea.at'");
    $conn->query("ALTER TABLE mailingOptions ADD COLUMN senderName VARCHAR(50) DEFAULT NULL");

    $sql = "CREATE TABLE security_external_access(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        externalID INT(6) UNSIGNED,
        module VARCHAR(50) NOT NULL,
        optionalID VARCHAR(32),
        privateKey VARCHAR(150) NOT NULL,
        recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
        FOREIGN KEY (externalID) REFERENCES external_users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>Extern: Security Access';
    }

    $sql = "CREATE TABLE dsgvo_training_user_suspension (
        userID INT(6) UNSIGNED,
        last_suspension DATETIME DEFAULT CURRENT_TIMESTAMP,
        suspension_count INT(6) DEFAULT 0,
        PRIMARY KEY (userID),
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
        echo '<br>DSGVO Training: User Suspension';
    }
}

if($row['version'] < 151){
    $conn->query("ALTER TABLE UserData ADD COLUMN lastLogin DATETIME DEFAULT NULL"); //5ac7126421a8b
}

if($row['version'] < 152){
	$conn->query("ALTER TABLE security_projects DROP COLUMN privateKey");

	//same with User Access
	$sql = "ALTER TABLE roles ADD canUseArchive ENUM('TRUE','FALSE') DEFAULT 'FALSE' NOT NULL";
	if (!$conn->query($sql)) {
		echo $conn->error;
	} else {
		echo '<br>Archive User Module';
	}

    $sql = "ALTER TABLE dsgvo_training_questions CHANGE `text` `text` MEDIUMTEXT";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Bigger Training Questions (16MiB)';
    }

    $result = $conn->query("SELECT id FROM dsgvo_vv_templates WHERE type = 'app'");
    $stmt = $conn->prepare("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr) VALUES(?, ?, ?)");
    $stmt->bind_param("iss", $templateID, $opt, $descr);

    if($result && ($row = $result->fetch_assoc())){
        $templateID = $row["id"];

        $descr = '';
        $opt = 'EXTRA_DAN';
        $stmt->execute();

        $descr = '';
        $opt = 'EXTRA_FOLGE_CHOICE';
        $stmt->execute();

        $descr = '';
        $opt = 'EXTRA_FOLGE_DATE';
        $stmt->execute();

        $descr = '';
        $opt = 'EXTRA_FOLGE_REASON';
        $stmt->execute();

        $descr = '';
        $opt = 'EXTRA_DOC_CHOICE';
        $stmt->execute();

    }
    if($conn->error){
        echo $conn->error;
    }else{
        echo 'DSGVO: Extra VV fields';
    }

    $conn->query("ALTER TABLE dsgvo_vv_data_matrix_settings ADD COLUMN opt_duration INT(6) DEFAULT 0");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>DSGVO VV: Data matrix duration';
    }
    $conn->query("ALTER TABLE dsgvo_vv_data_matrix_settings ADD COLUMN opt_unit INT(6) DEFAULT 0");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>DSGVO VV: Data matrix unit';
    }

    // erp_settings in doUpdate.php:1060 is different than in setup_inc.php:807
    // Values are taken from doUpdate.php:1085
    //   clientNum, clientStep, supplierNum, supplierStep
    //   '1000'   , '1'       , '1000'     , '1'
    $conn->query("SELECT clientNum FROM erp_settings");
    if($conn->error){ // add missing field
        $conn->query("ALTER TABLE erp_settings ADD COLUMN clientNum VARCHAR(12)");
        echo $conn->error;
        $conn->query("UPDATE erp_settings SET clientNum = '1000'");
        echo $conn->error;
    }
    $conn->query("SELECT clientStep FROM erp_settings");
    if($conn->error){ // add missing field
        $conn->query("ALTER TABLE erp_settings ADD COLUMN clientStep INT(2)");
        echo $conn->error;
        $conn->query("UPDATE erp_settings SET clientStep = '1'");
        echo $conn->error;
    }
    $conn->query("SELECT supplierNum FROM erp_settings");
    if($conn->error){ // add missing field
        $conn->query("ALTER TABLE erp_settings ADD COLUMN supplierNum VARCHAR(12)");
        echo $conn->error;
        $conn->query("UPDATE erp_settings SET supplierNum = '1000'");
        echo $conn->error;
    }
    $conn->query("SELECT supplierStep FROM erp_settings");
    if($conn->error){ // add missing field
        $conn->query("ALTER TABLE erp_settings ADD COLUMN supplierStep INT(2)");
        echo $conn->error;
        $conn->query("UPDATE erp_settings SET supplierStep = '1'");
        echo $conn->error;
    }
}

if($row['version'] < 153){
	$conn->query("ALTER TABLE dynamicprojectslogs ADD COLUMN id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY");

	$conn->query("CREATE TABLE security_users(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		userID INT(6) UNSIGNED,
		publicKey VARCHAR(150) NOT NULL,
		privateKey VARCHAR(150) NOT NULL,
		recentDate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        outDated ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL,
		FOREIGN KEY (userID) REFERENCES UserData(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
	)");
	if($conn->error){
        echo $conn->error;
    } else {
		$conn->query("INSERT INTO security_users (userID, publicKey, privateKey) SELECT id, publicPGPKey, privatePGPKey FROM UserData WHERE publicPGPKey IS NOT NULL"); echo $conn->error;
		$conn->query("ALTER TABLE UserData DROP COLUMN publicPGPKey");
		$conn->query("ALTER TABLE UserData DROP COLUMN privatePGPKey");
        echo '<br>Security: Users';
    }

	$conn->query("INSERT INTO security_access(userID, module, optionalID, privateKey) SELECT userID, 'COMPANY', companyID, privateKey FROM security_company");
	$conn->query("DELETE FROM security_company");
	$conn->query("ALTER TABLE security_company ADD COLUMN publicKey VARCHAR(150) NOT NULL");
	$conn->query("ALTER TABLE security_company ADD COLUMN symmetricKey VARCHAR(150) NOT NULL");
	$conn->query("ALTER TABLE security_company DROP COLUMN privateKey");
	$conn->query("ALTER TABLE security_company DROP FOREIGN KEY security_company_ibfk_1");
	$conn->query("ALTER TABLE security_company DROP COLUMN userID");

	$conn->query("ALTER TABLE companyData DROP COLUMN publicPGPKey");
}

if($row['version'] < 154){
	$sql = "CREATE TABLE folder_default_sturctures(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		category VARCHAR(20) NOT NULL,
		categoryID VARCHAR(20) NOT NULL,
		name VARCHAR(155) NOT NULL
	)";
	if(!$conn->query($sql)){
		echo $conn->error;
	} else {
		echo '<br>Archive: Default folder structures';
		$conn->query("INSERT INTO folder_default_sturctures(category, categoryID, name) SELECT 'COMPANY', companyID, name FROM company_folders");
		if(!$conn->error) $conn->query("DROP TABLE company_folders");
		echo $conn->error;
	}

	$conn->query("INSERT INTO folder_default_sturctures(category, categoryID, name) SELECT 'DSGVO', id, 'Dokumentation' FROM companyData");

	$sql = "CREATE TABLE archive(
        id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		uniqID VARCHAR(30) UNIQUE,
		category VARCHAR(20) NOT NULL,
		categoryID VARCHAR(20) NOT NULL,
        name VARCHAR(120) NOT NULL,
        parent_directory VARCHAR(120) NOT NULL DEFAULT 'ROOT',
        type VARCHAR(10) NOT NULL,
        uploadDate DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
	if(!$conn->query($sql)){
		echo $conn->error;
	} else {
		echo '<br>Archive: upload structure';
		$conn->query("INSERT INTO archive(id, uniqID, category, categoryID, name, parent_directory, type, uploadDate)
		SELECT id, uniqID, 'PROJECT', projectID, name, parent_directory, type, uploadDate FROM project_archive");
		if(!$conn->error) $conn->query("DROP TABLE project_archive");
		echo $conn->error;
	}

	$conn->query("ALTER TABLE security_modules CHANGE `publicPGPKey` `publicKey` VARCHAR(150) NOT NULL");

    // this might exist already
    $sql = "CREATE TABLE taskmessages(
        userID INT(6) UNSIGNED,
        taskID varchar(100),
        taskName varchar(100),
        message TEXT,
        picture MEDIUMBLOB,
        sent DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($sql);
    if (!$conn->error) {
        echo '<br>Task Messages';
    }
    $conn->query("ALTER TABLE messages ADD COLUMN user_deleted ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
    echo $conn->error;
    $conn->query("ALTER TABLE messages ADD COLUMN partner_deleted ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
    echo $conn->error;

    $conn->query("ALTER TABLE socialprofile ADD COLUMN new_message_email ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
    echo $conn->error;

	$conn->query("INSERT INTO dsgvo_vv_template_settings (templateID, opt_name, opt_descr) SELECT id, 'GEN_TEXTAREA', 'Notizen' FROM dsgvo_vv_templates WHERE type='base'");
}

if($row['version'] < 155){
	$conn->query("ALTER TABLE archive ADD COLUMN uploadUser INT(6)");

	if(getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])){
		$conn->query("UPDATE mailingOptions SET host = 'adminmail', port = 25, username = '', password = '', smtpSecure = '',
		sender = 'noreply@eitea.at', sendername = 'Connect', isDefault = 1");
	}

	$sql = "CREATE TABLE archive_meta(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		archiveID INT(10) UNSIGNED,
		parentID INT(10),
		name VARCHAR(400) NOT NULL,
		description MEDIUMTEXT NOT NULL DEFAULT '',
		category VARCHAR(120),
		status VARCHAR(100) NOT NULL DEFAULT 'PENDING',
		fromDate DATE NOT NULL DEFAULT '0000-00-00',
		toDate DATE NOT NULL DEFAULT '0000-00-00',
		validDate DATE NOT NULL DEFAULT '0000-00-00',
		version INT(4) NOT NULL DEFAULT 1,
		versionDescr VARCHAR(250),
		cPartner VARCHAR(50),
		cPartnerID INT(6),
		note VARCHAR(500) NOT NULL DEFAULT '',
		FOREIGN KEY (archiveID) REFERENCES archive(id)
		ON UPDATE CASCADE ON DELETE CASCADE
	)";
	if(!$conn->query($sql)){
		echo $conn->error;
	} else {
		echo '<br>Archiv: Metadaten';
	}
}

if($row['version'] < 156){
	if(empty($identifier) || preg_match('/[^0-9]/', $identifier)){
		$identifier = uniqid('');
		echo '<br> New Identification';
	}
	$conn->query("UPDATE identification SET id = '$identifier'");

	$myfile = fopen(dirname(dirname(__DIR__)) .'/connection_config.php', 'w');
	$txt = '<?php
	$servername = "'.$servername.'";
	$username = "'.$username.'";
	$password = "'.$password.'";
	$dbName = "'.$dbName.'";';
	fwrite($myfile, $txt);
	fclose($myfile);

	//5af32c18c9ab7
	$conn->query("DELETE FROM emailprojectlogs WHERE id NOT IN (SELECT id FROM emailprojectslogs ORDER BY id DESC LIMIT 200)");
	$result = $conn->query("SELECT id FROM emailprojectlogs");
	$i = 1;
	while($row = $result->fetch_assoc()){
		$conn->query("UPDATE emailprojectlogs SET id = $i WHERE id = ".$row['id']);
		$i++;
	}
}

if($row['version'] < 157){
	$conn->query("ALTER TABLE UserData ADD COLUMN supervisor INT(6) DEFAULT NULL ");
	$conn->query("CREATE TABLE dsgvo_categories(
		id INT(4) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(250) NOT NULL
	)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>DSGVO: Subkategorien';
	}

	$conn->query("INSERT INTO dsgvo_categories (name) VALUES ('Geheimhaltungsvereinbarung'),('Auftragsverarbeitung Art. 28'),('IT-Richtlinien'),('Allgemeine-Richtlinien'),('Datenschutzerklärung'),('Vereinbarung nach Art. 26')");
}

if($row['version'] < 158){
    $sql = "CREATE TABLE messagegroups (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        subject VARCHAR(60)
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE messagegroups_user (
        userID INT(6) UNSIGNED,
        groupID INT(6) UNSIGNED,
        admin ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL,
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (groupID) REFERENCES messagegroups(id) ON UPDATE CASCADE ON DELETE CASCADE,
        PRIMARY KEY (userID, groupID)
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE groupmessages(
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        groupID INT(6) UNSIGNED,
        sender INT(6) UNSIGNED,
        message TEXT,
        picture MEDIUMBLOB,
        sent DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (groupID) REFERENCES messagegroups(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

    $sql = "CREATE TABLE groupmessages_user (
        userID INT(6) UNSIGNED,
        messageID INT(6) UNSIGNED,
        deleted ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL,
        seen DATETIME DEFAULT NULL,
        PRIMARY KEY (userID, messageID),
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (messageID) REFERENCES groupmessages(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo $conn->error;
    }

	$conn->query("ALTER TABLE security_users ADD COLUMN checkSum VARCHAR(100)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Security: Userkey Checksum';
	}
	$conn->query("ALTER TABLE security_modules ADD COLUMN checkSum VARCHAR(100)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Security: Module Checksum';
	}
}

if($row['version'] < 159){
	$conn->query("UPDATE dsgvo_vv_template_settings SET opt_descr = REPLACE(opt_descr, 'Applikation', 'Vorgang') WHERE opt_descr LIKE '%Applikation%'");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>DSGVO: Vorgang rename';
	}

	$conn->query("ALTER TABLE dsgvo_vv_settings MODIFY COLUMN setting MEDIUMTEXT NOT NULL");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>DSGVO: Max. Zeichenlänge';
	}
	$conn->query("ALTER TABLE dsgvo_vv_logs MODIFY COLUMN long_description TEXT");
	if($conn->error){
		echo $conn->error;
	}

	$conn->query("RENAME TABLE teamRelationshipData TO relationship_team_user");

	$conn->query("ALTER TABLE processHistory ADD COLUMN status INT(2)");
	$conn->query("UPDATE processHistory p SET status = (SELECT status FROM proposals WHERE id = p.processID)");
	$conn->query("ALTER TABLE proposals DROP COLUMN status");

	//5b16313246c45
	$conn->query("UPDATE dsgvo_vv_template_settings SET opt_descr = REPLACE(opt_descr, 'automatischer', 'automaischer') WHERE opt_descr LIKE '% automaischer %'");

	$conn->query("ALTER TABLE dynamicprojects ADD COLUMN v2 VARCHAR(150) DEFAULT NULL");

	$keypair = sodium_crypto_box_keypair();
	$private = sodium_crypto_box_secretkey($keypair);
	$public = sodium_crypto_box_publickey($keypair);
	$nonce = random_bytes(24);
	$conn->query("INSERT INTO security_modules (module, symmetricKey, publicKey, outDated, checkSum) VALUES('TASK', '', '".base64_encode($public)."', 'FALSE', '')");

	echo $conn->error;
	$result = $conn->query("SELECT userID, publicKey FROM security_users WHERE outDated = 'FALSE'");
	while($row = $result->fetch_assoc()){
		$user_public = base64_decode($row['publicKey']);
		$nonce = random_bytes(24);
		$encrypted = base64_encode($nonce . sodium_crypto_box($private, $nonce, $private.$user_public));
		$conn->query("INSERT INTO security_access(userID, module, privateKey, outDated) VALUES(".$row['userID'].", 'TASK', '$encrypted', 'FALSE')");
	}
	echo $conn->error;
}


if($row['version'] < 160){
	$conn->query("ALTER TABLE dynamicprojects MODIFY COLUMN projectname VARCHAR(250) NOT NULL");
	$conn->query("ALTER TABLE archive_meta MODIFY COLUMN cPartnerID VARCHAR(20)");

	//5afc141e4a3c7
	$conn->query("ALTER TABLE companyData ADD COLUMN companyRegister VARCHAR(80)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Mandant: Firmenbuchnummer';
	}
	$conn->query("ALTER TABLE companyData ADD COLUMN companyCommercialCourt VARCHAR(80)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Mandant: Firmenbuchgericht';
	}
	$conn->query("ALTER TABLE companyData ADD COLUMN companyWKOLink VARCHAR(150)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Mandant: Link zur WKO';
	}
	$conn->query("ALTER TABLE companyData ADD COLUMN fax VARCHAR(60)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Mandant: Fax Nr.';
	}

	//5b17cd451c685
	$conn->query("UPDATE travelCountryData SET countryName = 'Österreich', identifier = 'AT' WHERE id = 1"); //just in case

	$conn->query("UPDATE clientInfoData c SET address_Country = (SELECT id FROM travelCountryData t WHERE c.address_country = t.countryName )");
	$conn->query("ALTER TABLE clientInfoData MODIFY COLUMN address_Country INT(6) UNSIGNED");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Adressbuch: Länderauswahl';
	}

	//5b17d3a5b9341
	$conn->query("ALTER TABLE erp_settings ADD COLUMN euDelivery VARCHAR(150)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>ERP: EU Lieferung';
	}
	$conn->query("ALTER TABLE erp_settings ADD COLUMN euService VARCHAR(150)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>ERP: EU Leistung';
	}

	//5b050794ee954
	$conn->query("DELETE FROM UserData WHERE id = 1");
	$conn->query("UPDATE UserData SET id = 1 WHERE id = 2");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Benutzer: Admin entfernt';
	}
	$conn->query("UPDATE dynamicprojects SET projectleader = 1 WHERE projectleader = 2");
	$conn->query("UPDATE dynamicprojects SET projectowner = 1 WHERE projectowner = 2");
	$conn->query("UPDATE archive SET uploadUser = 1 WHERE uploadUser = 2");
	$conn->query("UPDATE account_journal SET userID = 1 WHERE userID = 2");

	//5b1f67f86c983
	$sql = "CREATE TABLE workflowRules (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		workflowID INT(10),
		templateID VARCHAR(100),
		position INT(4),
		subject VARCHAR(100),
		fromAddress VARCHAR(100),
		toAddress VARCHAR(100),
		FOREIGN KEY (templateID) REFERENCES dynamicprojects(projectid)
		ON UPDATE CASCADE
		ON DELETE SET NULL,
		FOREIGN KEY (workflowID) REFERENCES emailprojects(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
    )";
    if(!$conn->query($sql)){
        echo $conn->error;
    } else {
		echo '<br>Workflow: Update';
	}

	$result = $conn->query("SELECT * FROM taskemailrules");
	$i = 1;
	while($row = $result->fetch_assoc()){
		$id = uniqid();
		$conn->query("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor,
			projectstatus, projectpriority, projectparent, projectowner, projectleader, estimatedHours, isTemplate)
		    VALUES ('$id', '{$row['identifier']}', '', '{$row['company']}', '{$row['client']}', '{$row['clientproject']}', '{$row['color']}',
			'{$row['status']}', '{$row['priority']}', '{$row['parent']}', '{$row['owner']}', '{$row['leader']}', '{$row['estimatedHours']}', 'TRUE')");
			echo $conn->error;
	   $conn->query("INSERT INTO workflowRules (workflowID, templateID, position, subject) VALUES ('{$row['emailaccount']}', '$id', $i, '{$row['identifier']}') ");
	   echo $conn->error;
   }

	$conn->query("DROP TABLE taskemailrules");
	$conn->query("DROP TABLE dynamicprojectsnotes");
	$conn->query("DROP TABLE dynamicprojectspictures");

	$conn->query("ALTER TABLE dynamicprojects ADD COLUMN projectmailheader TEXT NOT NULL DEFAULT ''");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Tasks: Verschlüsselter email Header';
	}
}

if($row['version'] < 161){
	//5b20ad39615f9
	$conn->query("ALTER TABLE workflowRules ADD COLUMN autoResponse TEXT");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Workflow: Auto Response';
	}
	//5b28952ad8a9a
	$conn->query("ALTER TABLE teamData ADD COLUMN email VARCHAR(100)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Teams: E-Mail Adresse';
	}
}

if($row['version'] < 162){
	//5b2931a15ad87
	$conn->query("ALTER TABLE UserData ADD COLUMN canLogin ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'TRUE'");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Benutzer: Login-Sperre';
	}

	$conn->query("ALTER TABLE configurationData ADD COLUMN sessionTime DECIMAL(4,2) DEFAULT 4.0 NOT NULL");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Security: Session Timer';
	}

	$conn->query("DROP TABLE archive_savedfiles");
	$conn->query("DROP TABLE archive_folders");
	$conn->query("DROP TABLE archive_editfiles");
	$conn->query("DROP TABLE uploadedfiles");
	$conn->query("DROP TABLE sharedfiles");
	$conn->query("DROP TABLE share");

	//5af9b976aa8a6
	$conn->query("ALTER TABLE dsgvo_vv_logs ADD COLUMN vvID VARCHAR(10)");
	$conn->query("ALTER TABLE proposals ADD COLUMN foreignOpt INT(2) NOT NULL DEFAULT 0");

	$conn->query("ALTER TABLE taskmessages ADD COLUMN id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Taskmessages: ID';
	}

	$conn->query("CREATE TABLE taskmessages_user (
		userID INT(6) UNSIGNED,
		messageID INT(6) UNSIGNED,
		deleted ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL,
		seen DATETIME DEFAULT NULL,
		PRIMARY KEY (userID, messageID),
		FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE,
		FOREIGN KEY (messageID) REFERENCES taskmessages(id) ON UPDATE CASCADE ON DELETE CASCADE
	)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Taskmessages: User seen';
	}
}

if($row['version'] < 163){
	//messenger update. ONE performant messenger, throghout the entire system
	$conn->query("DROP TABLE messages"); echo $conn->error;
	$conn->query("DROP TABLE taskmessages");
	$conn->query("DROP TABLE taskmessages_user");
	$conn->query("DROP TABLE messagegroups");
	$conn->query("DROP TABLE messagegroups_user"); echo $conn->error;
	$conn->query("DROP TABLE groupmessages");
	$conn->query("DROP TABLE groupmessages_user");
	$conn->query("DROP TABLE socialgroupmessages");
	$conn->query("DROP TABLE socialmessages"); echo $conn->error;
	$conn->query("DROP TABLE socialgroups"); echo $conn->error;

	$conn->query("CREATE TABLE messenger_conversations(
		id INT(6) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		subject VARCHAR(550) NOT NULL,
		category VARCHAR(25),
		categoryID VARCHAR(10)
	)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Messenger: Conversations';
	}
	$conn->query("CREATE TABLE relationship_conversation_participant(
		id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		conversationID INT(6) UNSIGNED,
		partType VARCHAR(25) NOT NULL,
		partID VARCHAR(50) NOT NULL,
		status VARCHAR(25),
		lastCheck DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		FOREIGN KEY (conversationID) REFERENCES messenger_conversations(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE,
		UNIQUE KEY relationship (conversationID, partType, partID)
	)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Messenger: Participants';
	}

	//try not to flood this
	$conn->query("CREATE TABLE messenger_messages(
		id INT(15) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
		message MEDIUMTEXT,
		participantID INT(10) UNSIGNED,
		sentTime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		type VARCHAR(15) NOT NULL DEFAULT 'text',
		vKey VARCHAR(150),
		FOREIGN KEY (participantID) REFERENCES relationship_conversation_participant(id)
		ON UPDATE CASCADE
		ON DELETE CASCADE
	)");
	if($conn->error){
		echo $conn->error;
	} else {
		echo '<br>Messenger: Messages';
	}

	$keypair = sodium_crypto_box_keypair();
	$private = sodium_crypto_box_secretkey($keypair);
	$public = sodium_crypto_box_publickey($keypair);
	$nonce = random_bytes(24);
	$conn->query("INSERT INTO security_modules (module, symmetricKey, publicKey, outDated, checkSum) VALUES('CHAT', '', '".base64_encode($public)."', 'FALSE', '')");

	echo $conn->error;
	$result = $conn->query("SELECT userID, publicKey FROM security_users WHERE outDated = 'FALSE'");
	while($row = $result->fetch_assoc()){
		$user_public = base64_decode($row['publicKey']);
		$nonce = random_bytes(24);
		$encrypted = base64_encode($nonce . sodium_crypto_box($private, $nonce, $private.$user_public));
		$conn->query("INSERT INTO security_access(userID, module, privateKey, outDated) VALUES(".$row['userID'].", 'CHAT', '$encrypted', 'FALSE')");
	}
	echo $conn->error;
}

if($row['version'] < 164){
	//these were missing. dc if fail
	$conn->query("ALTER TABLE clientInfoData ADD COLUMN homepage VARCHAR(100)");
	if (!$conn->error) {
		echo '<br>Datenstamm: Homepage';
	}
	$conn->query("ALTER TABLE clientInfoData ADD COLUMN mail VARCHAR(100)");
	if (!$conn->error) {
		echo '<br>Datenstamm: Allgemeine E-Mails';
	}
	$conn->query("ALTER TABLE clientInfoData ADD COLUMN billDelivery VARCHAR(60)");
	if (!$conn->error) {
		echo '<br>Kundendetails: Rechnungsversand';
	}
	$conn->query("ALTER TABLE messenger_conversations MODIFY COLUMN categoryID VARCHAR(20); ");

	//5b34d0a15ec53 - start
	$conn->query("ALTER TABLE workflowRules ADD COLUMN isActive ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'TRUE'");
	if (!$conn->error) {
		echo '<br>Workflow: Deaktivierung';
	} else {
		echo '<br>', $conn->error;
	}
	$conn->query("INSERT INTO workflowRules (workflowID) SELECT id FROM emailprojects");
	if (!$conn->error) {
		echo '<br>Workflow: Messenger Rule';
	} else {
		echo '<br>',$conn->error;
	}
	$conn->query("ALTER TABLE messenger_conversations ADD COLUMN identifier VARCHAR(13)");
	if (!$conn->error) {
		echo '<br>Messenger: identification step 1/2';
	} else {
		echo '<br>', $conn->error;
	}
	$stmt = $conn->prepare("UPDATE messenger_conversations SET identifier = ? WHERE id = ? ");
	$stmt->bind_param("si", $uniqID, $id);
	$result = $conn->query("SELECT id FROM messenger_conversations");
	while($row = $result->fetch_assoc()){
		$uniqID = uniqID();
		$id = $row['id'];
		$stmt->execute();
	}
	$stmt->close();
	$conn->query("ALTER TABLE messenger_conversations MODIFY COLUMN identifier VARCHAR(13) UNIQUE NOT NULL");
	if (!$conn->error) {
		echo '<br>Messenger: identification step 2/2';
	} else {
		echo '<br>', $conn->error;
	}
	//5b34d0a15ec53 - end

	//5b34d75a75691
	$conn->query("ALTER TABLE teamData ADD COLUMN emailName VARCHAR(50)");
	if (!$conn->error) {
		echo '<br>Team: Email Anzeigename';
	} else {
		echo '<br>', $conn->error;
	}

	//5b34fa15e7a23
	$conn->query("CREATE TABLE tags(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		value VARCHAR(50) NOT NULL
	)");

	$conn->query("INSERT INTO tags (value) VALUES('Anruf'), ('Wichtig'), ('Vertraulich'), ('Frage'), ('Information')");
	if (!$conn->error) {
		echo '<br>Tags';
	} else {
		echo '<br>', $conn->error;
	}
}

if($row['version'] < 165){
	$conn->query("ALTER TABLE companyData ADD COLUMN emailSignature TEXT");
	if (!$conn->error) {
		echo '<br>Mandant: E-Mail Signatur';
	} else {
		echo '<br>', $conn->error;
	}

	$conn->query("ALTER TABLE teamData ADD COLUMN emailSignature TEXT");
	if (!$conn->error) {
		echo '<br>Team: E-Mail Signatur';
	} else {
		echo '<br>', $conn->error;
	}

	$conn->query("ALTER TABLE socialprofile ADD COLUMN emailSignature TEXT");
	if (!$conn->error) {
		echo '<br>Benutzer: E-Mail Signatur';
	} else {
		echo '<br>', $conn->error;
	}

	$conn->query("ALTER TABLE roles ADD COLUMN canSendToExtern ENUM('TRUE', 'FALSE') DEFAULT 'FALSE' NOT NULL");
	if (!$conn->error) {
		echo '<br>Benutzer: Senden an Extern';
	} else {
		echo '<br>', $conn->error;
	}
}


if($row['version'] < 166){
    $conn->query("ALTER TABLE socialprofile ADD COLUMN new_message_notification ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
    echo $conn->error;

    $conn->query("ALTER TABLE dsgvo_training_questions ADD COLUMN survey ENUM('TRUE', 'FALSE') DEFAULT 'FALSE'");
    echo $conn->error;

    $sql = "CREATE TABLE dsgvo_training_completed_questions_survey_answers (
        questionID int(6),
        userID INT(6) UNSIGNED,
        identifier VARCHAR(30) NOT NULL,
        PRIMARY KEY (questionID, userID, identifier),
        FOREIGN KEY (questionID) REFERENCES dsgvo_training_completed_questions(questionID) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
	if(!$conn->query($sql)){
        echo $conn->error;
    }

    $sql = "CREATE TABLE access_permission_groups (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(20) NOT NULL UNIQUE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE access_permissions (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        groupID INT(10) UNSIGNED NOT NULL,
        name VARCHAR(30) NOT NULL,
        FOREIGN KEY (groupID) REFERENCES access_permission_groups(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $sql = "CREATE TABLE relationship_access_permissions (
        userID INT(6) UNSIGNED NOT NULL,
        permissionID INT(10) UNSIGNED NOT NULL,
        type ENUM('READ', 'WRITE') NOT NULL,
        PRIMARY KEY (userID, permissionID),
        FOREIGN KEY (permissionID) REFERENCES access_permissions(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $stmt_insert_groups = $conn->prepare("INSERT INTO access_permission_groups (name) VALUES (?)");
    echo $conn->error;
    $stmt_insert_groups->bind_param("s", $group);
    $stmt_insert_permission = $conn->prepare("INSERT INTO access_permissions (groupID, name) VALUES (?, ?)");
    echo $conn->error;
    $stmt_insert_permission->bind_param("is", $groupID, $permission);
    $stmt_insert_permission_relationship = $conn->prepare("INSERT INTO relationship_access_permissions (userID, permissionID, type) VALUES (?, ?, ?)");
    echo $conn->error;
    $stmt_insert_permission_relationship->bind_param("iis", $uid, $permissionID, $type);
    $user_result = $conn->query("SELECT userID, isERPAdmin, isDSGVOAdmin, isCoreAdmin FROM roles WHERE userID != 1");
    echo $conn->error;
    $user_roles = $user_result->fetch_all(MYSQLI_ASSOC);
    $permission_groups = [
      'CORE' => [
        'SECURITY',
        'USERS',
        'COMPANIES',
        'TEAMS',
        'SETTINGS'
      ],
      'TIMES' => [
        'OVERVIEW',
        'CORRECTION_HOURS',
        'TRAVELING_EXPENSES',
        'VACATION',
        'CHECKLIST'
      ],
      'PROJECTS' => [
        'PROJECTS',
        'WORKFLOW',
        'LOGS'
      ],
      'ERP' => [
        'PROCESS',
        'CLIENTS',
        'SUPPLIERS',
        'ARTICLE',
        'RECEIPT_BOOK',
        'VACANT_POSITIONS',
        'SETTINGS'
      ],
      'FINANCES' => [
        'ACCOUNTING_PLAN',
        'ACCOUNTING_JOURNAL',
        'TAX_RATES'
      ],
      'DSGVO' => [
        'AGREEMENTS',
        'PROCEDURE_DIRECTORY',
        'EMAIL_TEMPLATES',
        'TRAINING',
        'LOGS'
      ],
      'ARCHIVE' => [
        'SHARE',
        'PRIVATE'
      ]
    ];
    foreach ($permission_groups as $group => $permissions) {
      $stmt_insert_groups->execute();
      echo $stmt_insert_groups->error;
      $groupID = $stmt_insert_groups->insert_id;
      foreach ($permissions as $permission) {
        $stmt_insert_permission->execute();
        echo $stmt_insert_permission->error;
        $uid = 1; // admin has all permissions
        $type = "WRITE";
        $permissionID = $stmt_insert_permission->insert_id;
        $stmt_insert_permission_relationship->execute();
        echo $stmt_insert_permission_relationship->error;
        foreach($user_roles as $row){
            $uid = $row["userID"]; // permissionID and type stay the same
            if($group == "DSGVO" && $row["isDSGVOAdmin"] == "TRUE"){
                $stmt_insert_permission_relationship->execute();
                echo $stmt_insert_permission_relationship->error;
            }else if ($group == "ERP" && $row["isERPAdmin"] == "TRUE"){
                $stmt_insert_permission_relationship->execute();
                echo $stmt_insert_permission_relationship->error;
            }else if ($group == "CORE" && $row["isCoreAdmin"] == "TRUE"){
                $stmt_insert_permission_relationship->execute();
                echo $stmt_insert_permission_relationship->error;
            }
        }
      }
    }
    $stmt_insert_permission_relationship->close();
    $stmt_insert_groups->close();
    $stmt_insert_permission->close();
	//5b45d4ae6b4cc
	$sql = "CREATE TABLE dynamicprojectsnotes(
		id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        taskID VARCHAR(100) NOT NULL,
		userID INT(6) UNSIGNED,
        notedate DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        notetext VARCHAR(1000) NOT NULL,
        FOREIGN KEY (taskID) REFERENCES dynamicprojects(projectid)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
		FOREIGN KEY (userID) REFERENCES UserData(id)
		ON UPDATE CASCADE
		ON DELETE SET NULL
      );";
    if (!$conn->query($sql)) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Notizen';
    }

	$conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid, position) SELECT projectid, projectleader, 'leader'
	FROM dynamicprojects WHERE projectleader <> 0 AND projectleader IS NOT NULL ON DUPLICATE KEY UPDATE position = 'leader'"); echo $conn->error;

	$conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid, position) SELECT projectid, projectowner, 'owner'
	FROM dynamicprojects WHERE projectowner IS NOT NULL AND projectowner <> 0 ON DUPLICATE KEY UPDATE position = 'owner'");

	$conn->query("ALTER TABLE dynamicprojects DROP FOREIGN KEY dynamicprojects_ibfk_2 ");

	$conn->query("ALTER TABLE dynamicprojects DROP COLUMN projectowner");
	$conn->query("ALTER TABLE dynamicprojects DROP COLUMN projectleader");
}

if($row['version'] < 167){
	$conn->query("DELETE a1 FROM messenger_messages a1, messenger_messages a2 WHERE a1.id > a2.id AND a1.participantID = a2.participantID AND a1.sentTime = a2.sentTime");
	$conn->query("ALTER TABLE messenger_messages ADD UNIQUE KEY uq_participant_time (participantID, sentTime)");
    if (!$conn->error) {
        echo '<br>Messenger: Doppelte PNs Fix';
    } else {
		echo '<br>',$conn->error;
	}
	//5b642eece3110
	$conn->query("ALTER TABLE companyData ADD COLUMN ecoYear DATE");
	if (!$conn->error) {
        echo '<br>Mandant: Wirtschaftsjahr';
    } else {
		echo '<br>',$conn->error;
	}

	//5b6800f1881fb
	$conn->query("ALTER TABLE tags ADD COLUMN type VARCHAR(20) NOT NULL DEFAULT 'text'");
	if (!$conn->error) {
        echo '<br>Tags: typ';
    } else {
		echo '<br>',$conn->error;
	}
	$conn->query("ALTER TABLE tags ADD COLUMN extra VARCHAR(80)");
	if (!$conn->error) {
        echo '<br>Tags: Benutzerdef. Status';
    } else {
		echo '<br>',$conn->error;
	}
}


$conn->query("DELETE a1 FROM messenger_messages a1, messenger_messages a2 WHERE a1.id > a2.id AND a1.participantID = a2.participantID AND a1.sentTime = a2.sentTime");
$conn->query("ALTER TABLE messenger_messages ADD UNIQUE KEY uq_participant_time (participantID, sentTime)");
if (!$conn->error) {
	echo '<br>Messenger: Doppelte PNs Fix';
}

if($row['version'] < 168){
    // drop access_permission_groups.name unique key
    $result = $conn->query("SHOW INDEX FROM access_permission_groups WHERE column_name = 'name'");
    while($row = $result->fetch_assoc()){
        $conn->query("ALTER TABLE access_permission_groups DROP INDEX ${row['Key_name']}");
        echo $conn->error;
    }
    $conn->query("ALTER TABLE access_permission_groups ADD COLUMN parent INT(10) UNSIGNED");
    $conn->query("ALTER TABLE access_permission_groups ADD FOREIGN KEY (parent) REFERENCES access_permission_groups(id) ON UPDATE CASCADE ON DELETE CASCADE");
    $conn->query("ALTER TABLE relationship_access_permissions DROP COLUMN type");

    $sql = "CREATE TABLE relationship_team_access_permissions (
        teamID INT(6) UNSIGNED NOT NULL,
        permissionID INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (teamID, permissionID),
        FOREIGN KEY (permissionID) REFERENCES access_permissions(id) ON UPDATE CASCADE ON DELETE CASCADE,
        FOREIGN KEY (teamID) REFERENCES teamData(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }

    $conn->query("ALTER TABLE UserData ADD COLUMN inherit_team_permissions ENUM('TRUE', 'FALSE') NOT NULL DEFAULT 'TRUE'");
    if($conn->error){
        echo $conn->error;
    } else {
        echo '<br>UserData: Inherit team permissions';
    }
    $conn->query("DROP TABLE roles");
    echo $conn->error;

    setup_permissions();

	$conn->query("CREATE TABLE log_encryption(
		id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		field VARCHAR(25),
		type VARCHAR(25),
		extra VARCHAR(25),
		version VARCHAR(5) NOT NULL DEFAULT '1'
	)");
}

if($row['version'] < 169){
	$date = getCurrentTimestamp();
	$conn->query("ALTER TABLE relationship_conversation_participant ADD COLUMN archive VARCHAR(45)");
	$conn->query("UPDATE relationship_conversation_participant SET archive = '$date'
		WHERE conversationID IN (SELECT id FROM messenger_conversations WHERE category LIKE 'archive_%')");
	$conn->query("UPDATE messenger_conversations SET category = SUBSTRING(category, 9) WHERE category LIKE 'archive_%'");

    $conn->query("ALTER TABLE socialprofile MODIFY COLUMN new_message_notification ENUM('TRUE', 'FALSE') DEFAULT 'TRUE'");
    if($conn->error){
        echo '<br>', $conn->error;
    }else{
        echo "<br>new_message_notification default true";
    }
    $conn->query("UPDATE socialprofile SET new_message_notification = 'TRUE'");
    if($conn->error){
        echo '<br>', $conn->error;
    }else{
        echo "<br>new_message_notification existing true";
    }
}

if($row['version'] < 170){
	setup_permissions();
}
if($row['version'] < 171){
	//5b6d28165191c
	$conn->query("INSERT INTO tags (value, type, extra) VALUES('Angebot', 'date_years', '7'), ('Auftragsbestätigung', 'date_years', '7'),
	('Lieferschein', 'date_years', '7'), ('Nachrichten', 'date_years', '3'), ('Kunde', 'date_years', '7'), ('Lieferant', 'date_years', '7'),
	('Bewerbung', 'date_months', '6'), ('Interessent', 'date_months', '6'), ('Rechnung', 'date_years', '7')");
	if($conn->error){
		echo '<br>', $conn->error;
	} else {
		echo "<br>Tags: Weitere default Tags hinzugefügt";
	}

	$conn->query("ALTER TABLE dynamicprojectsnotes ADD COLUMN notesubject VARCHAR(75)");
	if($conn->error){
		echo '<br>', $conn->error;
	} else {
		echo "<br>Notizen: Betreff";
	}

	$conn->query("ALTER TABLE clientData ADD COLUMN status VARCHAR(25)");
	if($conn->error){
		echo '<br>', $conn->error;
	} else {
		echo "<br>Kunden: Status";
	}
}

if($row['version'] < 172){
	$conn->query("ALTER TABLE account_journal ADD COLUMN status VARCHAR(10) DEFAULT 'account' NOT NULL");
	if($conn->error){
		echo '<br>', $conn->error;
	} else {
		echo "<br>Finanzen: Umbuchung";
	}

// if($row['version'] < 173){}
    $sql = "CREATE TABLE default_access_permissions (
        permissionID INT(10) UNSIGNED NOT NULL,
        PRIMARY KEY (permissionID),
        FOREIGN KEY (permissionID) REFERENCES access_permissions(id) ON UPDATE CASCADE ON DELETE CASCADE
    )";
    if (!$conn->query($sql)) {
        echo mysqli_error($conn);
    }else{
        echo "Permissions: defaults";
    }

    $conn->query("ALTER TABLE dynamicprojects ADD COLUMN levelmax INT(3) DEFAULT 100 NOT NULL");
    if ($conn->error) {
        echo $conn->error;
    } else {
        echo '<br>Tasks: Skill-Level max';
    }
}

// if($row['version'] < 174){}
// if($row['version'] < 175){}
// if($row['version'] < 176){}
// if($row['version'] < 177){}
// if($row['version'] < 178){}
// if($row['version'] < 179){}

//cleanups for maintainable db sizes
$conn->query("DELETE FROM `checkinLogs` WHERE id <= ( SELECT id FROM ( SELECT id FROM `checkinLogs` ORDER BY id DESC LIMIT 1 OFFSET 100 ) foo )");echo $conn->error;
$conn->query("DELETE FROM `dsgvo_vv_logs` WHERE id <= ( SELECT id FROM ( SELECT id FROM `dsgvo_vv_logs` ORDER BY id DESC LIMIT 1 OFFSET 200 ) foo )");echo $conn->error;
$conn->query("DELETE FROM `emailprojectlogs` WHERE id <= ( SELECT id FROM ( SELECT id FROM `emailprojectlogs` ORDER BY id DESC LIMIT 1 OFFSET 100 ) foo )");echo $conn->error; //5b331ae15c641
// ------------------------------------------------------------------------------
require dirname(dirname(__DIR__)) . '/version_number.php';
$conn->query("UPDATE configurationData SET version=$VERSION_NUMBER");
echo '<br><br>Update wurde beendet. Klicken sie auf "Weiter", wenn sie nicht automatisch weitergeleitet werden: <a href="../user/home">Weiter</a>';
?>
<script type="text/javascript">
window.setInterval(function(){
	window.location.href="../user/home";
}, 4000);

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
</script>

<noscript>
  <meta http-equiv="refresh" content="0;url=../user/home" />';
</noscript>
</body>
</html>
