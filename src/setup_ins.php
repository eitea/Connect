<?php
/*
NECESSARY VARIABLES BEFORE INCLUDING THIS:
$firstname
$lastname
$loginname
$psw

include version_number.php

$companyName
$companyType
$holidayFile
$travellingFile

** SEE SETUP.PHP FOR MORE INFORMATION **
*/

//dev note: .. it would be a bit prettier if we put all of this (setup_ins and setup_inc into a  function... TODO for later.)
//insert main company
$sql = "INSERT INTO $companyTable (name, companyType) VALUES ('$companyName', '$companyType')";
$conn->query($sql);
//insert password policy
$conn->query("INSERT INTO $policyTable (passwordLength) VALUES (0)");
//insert module en/disable
$conn->query("INSERT INTO $moduleTable (enableTime, enableProject) VALUES('TRUE', 'TRUE')");

//insert ADMIN
$sql = "INSERT INTO $userTable (firstname, lastname, email, psw) VALUES ('', 'Admin', 'Admin@$domainname', '$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK');";
$conn->query($sql);
//interval
$sql = "INSERT INTO $intervalTable (userID) VALUES (1);";
$conn->query($sql);
//role
$sql = "INSERT INTO $roleTable (userID, isCoreAdmin, canStamp, canBook) VALUES(1, 'TRUE', 'TRUE', 'TRUE');";
$conn->query($sql);
//insert company-client relationship
$sql = "INSERT INTO $companyToUserRelationshipTable(companyID, userID) VALUES(1,1)";
$conn->query($sql);

//insert Core User
$sql = "INSERT INTO $userTable (firstname, lastname, email, psw) VALUES ('$firstname', '$lastname', '$loginname', '$psw');";
$conn->query($sql);
//insert intervaltable
$sql = "INSERT INTO $intervalTable (userID) VALUES (2);";
$conn->query($sql);
//insert roletable
$sql = "INSERT INTO $roleTable (userID, isCoreAdmin, isTimeAdmin, isProjectAdmin, isReportAdmin, isERPAdmin, canStamp, canBook) VALUES(2, 'TRUE', 'TRUE', 'TRUE','TRUE', 'TRUE', 'TRUE','TRUE');";
$conn->query($sql);
//insert company-client relationship
$sql = "INSERT INTO $companyToUserRelationshipTable(companyID, userID) VALUES(1,2)";
$conn->query($sql);

//insert ldap config
$sql = "INSERT INTO $adminLDAPTable (adminID, version) VALUES (1, $VERSION_NUMBER)";
$conn->query($sql);


//insert holidays
$holidayFile = icsToArray($holidayFile);
for($i = 1; $i < count($holidayFile); $i++){
  if($holidayFile[$i]['BEGIN'] == 'VEVENT'){
    $start = substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 0, 4) ."-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTSTART;VALUE=DATE'], 6, 2) . " 00:00:00";
    $end = substr($holidayFile[$i]['DTEND;VALUE=DATE'], 0, 4) ."-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 4, 2) . "-" . substr($holidayFile[$i]['DTEND;VALUE=DATE'], 6, 2) . " 20:00:00";
    $n = $holidayFile[$i]['SUMMARY'];
    $conn->query("INSERT INTO $holidayTable(begin, end, name) VALUES ('$start', '$end', '$n');");
  }
}
if (!$conn->query($sql)) {
  echo mysqli_error($conn);
}

//insert travelling expenses
if ($travellingFile) {
    while (($line = fgets($travellingFile)) !== false) {
      $line = iconv('UTF-8', 'windows-1252', $line);
      $thisLineIsNotOK = true;
      while($thisLineIsNotOK){
        $data = preg_split('/\s+/', $line);
        array_pop($data);
        if(count($data) == 4){
          $short = test_input($data[0]);
          $name = test_input($data[1]);
          $dayPay = floatval($data[2]);
          $nightPay = floatval($data[3]);
          $sql = "INSERT INTO $travelCountryTable(identifier, countryName, dayPay, nightPay) VALUES('$short', '$name', '$dayPay' , '$nightPay') ";
          if (!$conn->query($sql)) {
            echo mysqli_error($conn);
          }
          $thisLineIsNotOK = false;
        } elseif(count($data) > 4) {
          $line = substr_replace($line, '_', strlen($data[0].' '.$data[1]), 1);
        } else {
          echo 'Nope. <br>';
          print_r ($data);
          die();
        }
      }
    }
  fclose($travellingFile);
}

//insert main report
$exampleTemplate = "<h1>Main Report</h1>
[TIMESTAMPS] <br>
[BOOKINGS] ";
$conn->query("INSERT INTO $pdfTemplateTable(name, htmlCode, repeatCount) VALUES('Example_Report', '$exampleTemplate', 'TRUE')");

//insert taxRates
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Normalsatz', 20)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Ermäßigter Satz', 10)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschaftlicher Erwerb Normalsatz', 20)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschaftlicher Erwerb Ermäßigter Satz', 10)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschaftlicher Erwerb steuerfrei', NULL)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Reverse Charge Normalsatz', 20)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Reverse Charge Ermäßigter Satz', 10)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Bewirtung', 20)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Bewirtung', 10)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschaftliche Leistungen', NULL)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Innergemeinschatliche Lieferungen steuerfrei', NULL)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Ermäßigter Satz', 13)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Sonder Ermäßigter Satz', 12)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Zollausschulssgebiet', NULL)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Zusatzsteuer LuF', 10)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Zusatzsteuer LuF', 8)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('KFZ Normalsatz', 20)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('UStBBKV', 20)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Keine Steuer', NULL)");
$conn->query("INSERT INTO taxRates(description, percentage) VALUES('Steuerfrei', 0)");

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
?>
