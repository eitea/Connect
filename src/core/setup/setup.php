<?php
if (file_exists(dirname(dirname(__DIR__)) . '/connection_config.php') || getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])) {
    header("Location: ../login/auth");
}
ignore_user_abort(1);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">

        <script src="plugins/jQuery/jquery.min.js"></script>

        <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
        <script src="plugins/bootstrap/js/bootstrap.min.js"></script>

        <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css">
        <script src='plugins/select2/js/select2.min.js'></script>

        <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
        <title>Setup Connect</title>
    </head>
    <body id="body_container" class="is-table-row">
        <div id="loader" style="display:none"></div>
        <!-- navbar -->
        <nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header hidden-xs"><a class="navbar-brand" >Connect</a></div>
                <div class="navbar-right">
                    <a class="btn navbar-btn navbar-link" data-toggle="collapse" href="#infoDiv_collapse"><strong>info</strong></a>
                </div>
            </div>
        </nav>
        <div class="collapse" id="infoDiv_collapse">
            <div class="well">
                <a href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include dirname(dirname(__DIR__)) . '/version_number.php';
echo $VERSION_TEXT; ?><br>
                The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
                the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
            </div>
        </div>
        <!-- /navbar -->
        <?php

        function test_input($data) {
            $data = preg_replace("~[^A-Za-z0-9\-.öäüÖÄÜ_ ]~", "", $data);
            $data = trim($data);
            return $data;
        }

        function clean($string) {
            return trim(preg_replace('/[^\.A-Za-z0-9\-]/', '', $string));
        }

        function randomPassword() {
            $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
            $psw = array();
            for ($i = 0; $i < 8; $i++) {
                $n = rand(0, strlen($alphabet) - 1);
                $psw[] = $alphabet[$n];
            }
            return implode($psw); //turn the array into a string
        }

        function match_passwordpolicy_setup($p, &$out = '') {
            if (strlen($p) < 6) {
                $out = "Password must be at least 6 Characters long.";
                return false;
            }
            if (!preg_match('/[A-Z]/', $p) || !preg_match('/[0-9]/', $p)) {
                $out = "Password must contain at least one captial letter and one number";
                return false;
            }
            return true;
        }
        ?>
        <div id="bodyContent">
            <div class="affix-content">
                <div class="container-fluid">

                    <?php
                    if (!function_exists('mysqli_init') && !extension_loaded('mysqli')) {
                        die('Mysqli not available.');
                    }
                    $firstname = $lastname = $companyName = $companyType = $localPart = $domainname = $out = "";

                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        $cmpDescription = $uid = $postal = $city = $address = $phone = $homepage = $email = '';
                        if (isset($_POST['cmpDescription'])) {
                            $cmpDescription = $_POST['cmpDescription'];
                            $uid = $_POST['uid'];
                            $postal = $_POST['postal'];
                            $city = $_POST['city'];
                            $address = $_POST['address'];
                            $phone = $_POST['phone'];
                            $homepage = $_POST['homepage'];
                            $email = $_POST['mail'];
                        }
                        if (!empty($_POST['companyName']) && !empty($_POST['adminPass']) && !empty($_POST['firstname']) && !empty($_POST['type']) && !empty($_POST['localPart']) && !empty($_POST['domainPart'])) {
                            $psw = $_POST['adminPass'];
                            $companyName = test_input($_POST['companyName']);
                            $companyType = test_input($_POST['type']);
                            $firstname = test_input($_POST['firstname']);
                            $lastname = test_input($_POST['lastname']);
                            $domainname = clean($_POST['domainPart']);
                            $loginname = clean($_POST['localPart']) . '@' . $domainname;

                            if (match_passwordpolicy_setup(test_input($_POST['adminPass']), $out)) {
                                $psw = password_hash($_POST['adminPass'], PASSWORD_BCRYPT);
                                //create connection file
                                $myfile = fopen(dirname(dirname(__DIR__)) . '/connection_config.php', 'w');
                                $txt = '<?php
              $servername = "' . test_input($_POST['serverName']) . '";
              $username = "' . test_input($_POST['mysqlUsername']) . '";
              $password = "' . test_input($_POST['pass']) . '";
              $dbName = "' . test_input($_POST['dbName']) . '";';
                                fwrite($myfile, $txt);
                                fclose($myfile);
                                if (!file_exists(dirname(dirname(__DIR__)) . '/connection_config.php')) {
                                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Fatal Error: Please grant PHP permission to create files first. Click Next to proceed. <a href="../login/auth">Next</a></div>';
                                }
                                require dirname(dirname(__DIR__)) . '/connection_config.php';
                                //establish connection
                                if (!($conn = new mysqli($servername, $username, $password))) {
                                    echo $conn->connect_error;
                                    unlink(dirname(dirname(__DIR__)) . '/connection_config.php');
                                    die("<br>Connection Error: Could not Connect.<br>");
                                }

                                if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbName")) {
                                    echo mysqli_error($conn);
                                    unlink(dirname(dirname(__DIR__)) . '/connection_config.php');
                                    die("<br>Invalid DB Connection: Could not instantiate a database.<a href='run'>Return</a><br>");
                                }

                                //reconnect to database
                                $conn->close();
                                $conn = new mysqli($servername, $username, $password, $dbName);

                                $conn->query("SET NAMES 'utf8';");
                                $conn->query("SET CHARACTER SET 'utf8';");

                                set_time_limit(0); //setup takes longer with a laptop in energy saving mode
                                //create all tables
                                require "setup_inc.php";
                                create_tables($conn);

                                require_once dirname(dirname(__DIR__)) . "/version_number.php";
                                //add lines to connection file
                                $identifier = uniqid('', true);
                                $myfile = fopen(dirname(dirname(__DIR__)) . '/connection_config.php', 'a');
                                $txt = '$identifier = \'' . $identifier . '\';
            $s3SharedFiles=$identifier.\'_sharedFiles\';
            $s3uploadedFiles=$identifier.\'_uploadedFiles\';
            $s3privateFiles=$identifier.\'_privateFiles\';';
                                fwrite($myfile, $txt);
                                fclose($myfile);


                                //------------------------------ INSERTS ---------------------------------------
                                //insert identification
                                $conn->query("INSERT INTO identification (id) VALUES ('$identifier')");
                                //insert main company
                                $stmt = $conn->prepare("INSERT INTO companyData (name, companyType, cmpDescription, companyPostal, companyCity, uid, address, phone, mail, homepage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                echo $conn->error;
                                $stmt->bind_param("ssssssssss", $companyName, $companyType, $cmpDescription, $postal, $city, $uid, $address, $phone, $email, $homepage);
                                $stmt->execute();
                                $stmt->close();
                                //insert password policy
                                $conn->query("INSERT INTO policyData (passwordLength) VALUES (6)");
                                //insert ADMIN bogus
                                $sql = "INSERT INTO UserData (firstname, lastname, email, psw) VALUES ('', 'Admin', 'Admin@$domainname', '$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK');";
                                $conn->query($sql);
                                //interval
                                $sql = "INSERT INTO intervalData (userID) VALUES (1);";
                                $conn->query($sql);
                                //role
                                $sql = "INSERT INTO roles (userID) VALUES(1);";
                                $conn->query($sql);
                                //insert company-client relationship
                                $sql = "INSERT INTO relationship_company_client(companyID, userID) VALUES(1, 1)";
                                $conn->query($sql);
                                //socialprofile
                                $sql = "INSERT INTO socialprofile (userID, isAvailable, status) VALUES(1, 'TRUE', '-');";
                                $conn->query($sql);

                                //insert core user
                                $sql = "INSERT INTO UserData (firstname, lastname, email, psw) VALUES ('$firstname', '$lastname', '$loginname', '$psw');";
                                $conn->query($sql);
                                //insert intervaltable
                                $sql = "INSERT INTO intervalData (userID) VALUES (2);";
                                $conn->query($sql);
                                //insert roletable
                                $sql = "INSERT INTO roles (userID, isCoreAdmin, isTimeAdmin, isProjectAdmin, isReportAdmin, isERPAdmin, isFinanceAdmin, isDSGVOAdmin, isDynamicProjectsAdmin, canStamp, canBook, canUseSocialMedia)
              VALUES(2, 'TRUE', 'TRUE', 'TRUE','TRUE', 'TRUE', 'TRUE','TRUE', 'TRUE', 'TRUE', 'TRUE', 'TRUE');";
                                $conn->query($sql);
                                //insert company-client relationship
                                $sql = "INSERT INTO relationship_company_client(companyID, userID) VALUES(1,2)";
                                $conn->query($sql);
                                //socialprofile
                                $sql = "INSERT INTO socialprofile (userID, isAvailable, status) VALUES(2, 'TRUE', '-');";
                                $conn->query($sql);

                                //insert configs
                                $sql = "INSERT INTO configurationData (bookingTimeBuffer, cooldownTimer, masterPassword) VALUES (5, 2,'')";
                                $conn->query($sql);
                                //insert ldap config
                                $sql = "INSERT INTO ldapConfigTab (adminID, version) VALUES (1, $VERSION_NUMBER)";
                                $conn->query($sql);
                                //insert ERP numbers
                                $conn->query("INSERT INTO erp_settings (erp_ang, erp_aub, erp_re, erp_lfs, erp_gut, erp_stn, companyID) VALUES (1, 1, 1, 1, 1, 1, 1)");
                                //insert mail options
                                $conn->query("INSERT INTO mailingOptions (host, port) VALUES('127.0.0.1', '80')");
                                //insert restic backup configuration
                                $conn->query("INSERT INTO resticconfiguration () VALUES ()");

                                //insert holidays
                                $icsFile = file_get_contents(__DIR__ . '/Feiertage.txt');
                                foreach (explode("BEGIN:", $icsFile) as $key => $value) {
                                    $icsDatesMeta[$key] = explode("\n", $value);
                                }
                                foreach ($icsDatesMeta as $key => $value) {
                                    foreach ($value as $subKey => $subValue) {
                                        if ($subValue != "") {
                                            if ($key != 0 && $subKey == 0) {
                                                $holidayFile[$key]["BEGIN"] = $subValue;
                                            } else {
                                                $subValueArr = explode(":", $subValue, 2);
                                                $holidayFile[$key][$subValueArr[0]] = $subValueArr[1];
                                            }
                                        }
                                    }
                                }
                                $stmt = $conn->prepare("INSERT INTO holidays(begin, end, name) VALUES(?, ?, ?)");
                                echo mysqli_error($conn);
                                $stmt->bind_param("sss", $start, $end, $n);
                                for ($i = 1; $i < count($holidayFile); $i++) {
                                    if (trim($holidayFile[$i]['BEGIN']) == 'VEVENT') {
                                        $start = substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 0, 4) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 6, 2) . " 00:00:00";
                                        $end = substr($holidayFile[$i]['DTEND;VALUE=DATE'], 0, 4) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 6, 2) . " 20:00:00";
                                        $n = $holidayFile[$i]['SUMMARY'];
                                        $stmt->execute();
                                    }
                                }
                                //insert github options
                                $conn->query("INSERT INTO gitHubConfigTab (sslVerify) VALUES('FALSE')");

                                //insert main report
                                $exampleTemplate = "<h1>Main Report</h1> \n [TIMESTAMPS] \n <br> [BOOKINGS] ";
                                $conn->query("INSERT INTO templateData(name, htmlCode, repeatCount) VALUES('Example_Report', '$exampleTemplate', 'TRUE')");

                                //insert taxRates
                                $i = 1;
                                $file = fopen(__DIR__ . '/Steuerraten.csv', 'r');
                                if ($file) {
                                    $stmt = $conn->prepare("INSERT INTO taxRates(id, description, percentage, account2, account3, code) VALUES (?, ?, ?, ?, ?, ?)");
                                    $stmt->bind_param("isiiii", $i, $name, $percentage, $account2, $account3, $code);
                                    while ($line = fgetcsv($file, 100, ';')) {
                                        $name = trim($line[0]);
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
                                    echo "<br>Error Steuerraten File";
                                }

                                //insert travelling expenses
                                $travellingFile = fopen(__DIR__ . "/Laender.txt", "r");
                                if ($travellingFile) {
                                    $stmt = $conn->prepare("INSERT INTO travelCountryData(identifier, countryName, dayPay, nightPay) VALUES(?, ?, ?, ?)");
                                    echo mysqli_error($conn);
                                    $stmt->bind_param("ssdd", $short, $name, $dayPay, $nightPay);
                                    while (($line = fgets($travellingFile)) !== false) {
                                        $line = iconv('UTF-8', 'windows-1252', $line);
                                        $thisLineIsNotOK = true;
                                        while ($thisLineIsNotOK) {
                                            $data = preg_split('/\s+/', $line);
                                            array_pop($data);
                                            if (count($data) == 4) {
                                                $short = test_input($data[0]);
                                                $name = test_input($data[1]);
                                                $dayPay = floatval($data[2]);
                                                $nightPay = floatval($data[3]);
                                                $stmt->execute();
                                                $thisLineIsNotOK = false;
                                            } elseif (count($data) > 4) {
                                                $line = substr_replace($line, '_', strlen($data[0] . ' ' . $data[1]), 1);
                                            } else {
                                                echo 'Error Inside Laender File <br>';
                                                print_r($data);
                                            }
                                        }
                                    }
                                    fclose($travellingFile);
                                } else {
                                    echo "Error Laender File <br>";
                                }

                                //insert sum units
                                $conn->query("INSERT INTO units (name, unit) VALUES('Stück', 'Stk')");
                                $conn->query("INSERT INTO units (name, unit) VALUES('Packungen', 'Pkg')");
                                $conn->query("INSERT INTO units (name, unit) VALUES('Stunden', 'h')");
                                $conn->query("INSERT INTO units (name, unit) VALUES('Gramm', 'g')");
                                $conn->query("INSERT INTO units (name, unit) VALUES('Kilogramm', 'kg')");
                                $conn->query("INSERT INTO units (name, unit) VALUES('Meter', 'm')");
                                $conn->query("INSERT INTO units (name, unit) VALUES('Kilometer', 'km')");
                                $conn->query("INSERT INTO units (name, unit) VALUES('Quadratmeter', 'm2')");
                                $conn->query("INSERT INTO units (name, unit) VALUES('Kubikmeter', 'm3')");

                                //insert payment method
                                $sql = "INSERT INTO paymentMethods (name) VALUES ('Überweisung')";
                                $conn->query($sql);
                                //insert shippign method
                                $sql = "INSERT INTO shippingMethods (name) VALUES ('Abholer')";
                                $conn->query($sql);
                                //insert accounts
                                $file = fopen(__DIR__ . '/Kontoplan.csv', 'r');
                                if ($file) {
                                    $stmt = $conn->prepare("INSERT INTO accounts (companyID, num, name, type) SELECT id, ?, ?, ? FROM companyData");
                                    $stmt->bind_param("iss", $num, $name, $type);
                                    while (($line = fgetcsv($file, 300, ';')) !== false) {
                                        $num = $line[0];
                                        $name = trim(iconv(mb_detect_encoding($line[1], mb_detect_order(), true), "UTF-8", $line[1]));
                                        if (!$name)
                                            $name = trim(iconv('MS-ANSI', "UTF-8", $line[1]));
                                        if (!$name)
                                            $name = $line[1];
                                        $type = trim($line[2]);
                                        $stmt->execute();
                                    }
                                    $stmt->close();
                                } else {
                                    echo "<br>Error Opening csv File";
                                }
                                $conn->query("UPDATE accounts SET manualBooking = 'TRUE' WHERE name = 'Bank' OR name = 'Kassa' ");

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

                                //-------------------------------- GIT -----------------------------------------

                                $repositoryPath = dirname(dirname(realpath("setup.php")));

                                //git init
                                $command = 'git -C ' . $repositoryPath . ' init 2>&1';
                                exec($command, $output, $returnValue);

                                //sslyverify false
                                $command = 'git -C ' . $repositoryPath . ' config http.sslVerify "false" 2>&1';
                                exec($command, $output, $returnValue);

                                //remote add
                                $command = "git -C $repositoryPath remote add -t master origin https://github.com/eitea/Connect.git 2>&1";
                                exec($command, $output, $returnValue);

                                $command = "git -C $repositoryPath fetch --force 2>&1";
                                exec($command, $output, $returnValue);

                                $command = "git -C $repositoryPath reset --hard origin/master 2>&1";
                                exec($command, $output, $returnValue);

                                //------------------------------------------------------------------------------
                                die('<br><br> Setup Finished. Click Next: <a href="../login/auth">Next</a>');
                            } else {
                                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $out . '</div>';
                            }
                        } else {
                            echo 'Missing Fields. <br><br>';
                        }
                    }
                    ?>

                    <form id="inputform" method='post'>
                        <h1>Login Data</h1><br><br>
                        <div class="row">
                            <div class="col-sm-8 col-lg-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon" style="min-width:150px">Firstname</span>
                                        <input type="text" class="form-control" name="firstname" placeholder="Firstname.." value="<?php echo $firstname; ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-8 col-lg-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon" style="min-width:150px">Lastname</span>
                                        <input type="text" class="form-control" name="lastname" placeholder="Lastname.." value="<?php echo $lastname ?>" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-8">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon text-warning" style=min-width:150px>Login Password</span>
                                        <input type='password' class="form-control" name='adminPass' value="" placeholder="****" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon text-warning" style="min-width:150px">Company Name</span>
                                        <input type='text' class="form-control" name='companyName' placeholder='Company Name' value="<?php echo $companyName ?>" />
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <div class="form-group">
                                    <select name="type" class="js-example-basic-single btn-block">
                                        <option selected>...</option>
                                        <option <?php if ($companyType == "GmbH") echo "selected"; ?> value="GmbH">GmbH</option>
                                        <option <?php if ($companyType == "AG") echo "selected"; ?> value="AG">AG</option>
                                        <option <?php if ($companyType == "OG") echo "selected"; ?> value="OG">OG</option>
                                        <option <?php if ($companyType == "KG") echo "selected"; ?> value="KG">KG</option>
                                        <option <?php if ($companyType == "EU") echo "selected"; ?> value="EU">EU</option>
                                        <option <?php if ($companyType == "-") echo "selected"; ?> value="-">Sonstiges</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <br><br>
                        <div class="row">
                            <div class="col-sm-8">
                                <label>Your Login E-Mail</label>
                                <div class="form-group">
                                    <div class="input-group">
                                        <input type='text' class="form-control" name='localPart' placeholder='name' value="<?php echo $localPart ?>" />
                                        <span class="input-group-addon text-warning"> @ </span>
                                        <input type='text' class="form-control" name='domainPart' placeholder="domain.com" value="<?php echo $domainname ?>" />
                                    </div>
                                </div>
                                <small> * The Domain will be used for every login adress that will be created. Cannot be changed afterwards.<br><b> May not contain any special characters! </b></small>
                            </div>
                        </div>
                        <br><hr><br>
                        <h1>MySQL Database Connection</h1><br><br>
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon" style="min-width:150px">
                                            Server Address
                                        </span>
                                        <input type="text" class="form-control" name="serverName" value = "127.0.0.1" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon" style="min-width:150px">
                                            Username
                                        </span>
                                        <input type="text" class="form-control" name='mysqlUsername' value = 'root' />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon" style="min-width:150px">
                                            Password
                                        </span>
                                        <input type="text" class="form-control" name='pass' value = '' />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-8">
                                <div class="form-group">
                                    <div class="input-group">
                                        <span class="input-group-addon" style="min-width:150px">
                                            DB Name
                                        </span>
                                        <input type="text" class="form-control" name='dbName' value = 'Zeit1' />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br><hr><br>

                        <div class="container-fluid text-right">
                            <button type='submit' name'submitInput' class="btn btn-warning">Continue</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <script>
            $(document).ready(function () {
                if ($(".js-example-basic-single")[0]) {
                    $(".js-example-basic-single").select2();
                }
            });
            $('#inputform').submit(function (ev) {
                document.getElementById("loader").style.display = "block";
                document.getElementById("bodyContent").style.display = "none";
            });
        </script>
    </body>
</html>
