<?php
if($_SERVER['REQUEST_METHOD'] != 'POST'){
  header("Location: getProjects.php");
}
require "connection.php";

$filterQuery = $_POST['filterQuery'];
$templateID = $_POST['templateID'];

$result = $conn->query("SELECT htmlCode FROM $pdfTemplateTable WHERE id = $templateID");
if($result && ($row = $result->fetch_assoc())){
  //explode my repeat pattern
  $html = $row['htmlCode'];

  $pos1 = strpos($html, "[REPEAT]");
  $pos2 = strpos($html, "[REPEAT END]") + 12; //strrchr($html, "[REPEAT END]") + 12;

  $html_head = substr($html, 0, $pos1);
  $html_foot = substr($html, $pos2);

  $repeat = substr($html, $pos1, $pos1 - $pos2);
} else {
  die("Could not fetch template. Please make sure it exists. Contact support for further issues.");
}

$sql="SELECT $projectTable.id AS projectID,
$clientTable.id AS clientID,
$clientTable.name AS clientName,
$projectTable.name AS projectName,
$projectBookingTable.*,
$projectBookingTable.id AS projectBookingID,
$logTable.timeToUTC,
$userTable.firstname, $userTable.lastname,
$projectTable.hours,
$projectTable.hourlyPrice,
$projectTable.status
FROM $projectBookingTable
INNER JOIN $logTable ON  $projectBookingTable.timeStampID = $logTable.indexIM
INNER JOIN $userTable ON $logTable.userID = $userTable.id
LEFT JOIN $projectTable ON $projectBookingTable.projectID = $projectTable.id
LEFT JOIN $clientTable ON $projectTable.clientID = $clientTable.id
LEFT JOIN $companyTable ON $clientTable.companyID = $companyTable.id
$filterQuery
ORDER BY $projectBookingTable.end ASC";

$result = $conn->query($sql);
if(!$result){
  echo $sql;
}
//replace all my findings
while($result && ($row = $result->fetch_assoc())){
  $start = carryOverAdder_Hours($row['start'], $row['timeToUTC']);
  $end = carryOverAdder_Hours($row['end'], $row['timeToUTC']);

  $appendPattern = str_replace("[NAME]", $row['firstname'] . ' ' . $row['lastname'], $repeat);
  $appendPattern = str_replace("[CLIENT]", $row['clientName'], $appendPattern);
  $appendPattern = str_replace("[PROJECT]", $row['projectName'], $appendPattern);
  $appendPattern = str_replace("[CLIENT]", $row['clientName'], $appendPattern);
  $appendPattern = str_replace("[INFOTEXT]", $row['infoText'], $appendPattern);
  $appendPattern = str_replace("[HOURLY RATE]", $row['hourlyPrice'], $appendPattern);
  $appendPattern = str_replace("[DATE]", substr($start,0,10), $appendPattern);
  $appendPattern = str_replace("[FROM]", substr($start,11,5), $appendPattern);
  $appendPattern = str_replace("[TO]", substr($end,11,5), $appendPattern);

  $html_head .= $appendPattern;
}

//glue my html back together
$html = $html_head . $html_foot;


//display the pdf
/*
require_once "../plugins/dompdf/autoload.php";
use Dompdf\Dompdf;
$dompdf = new DOMPDF();
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("sample.pdf", array("Attachment"=>0));
*/



function carryOverAdder_Hours($a, $b) {
  if($a == '0000-00-00 00:00:00'){
    return $a;
  }
  $date = new DateTime($a);
  if($b<0){
    $b *= -1;
    $date->sub(new DateInterval("PT".$b."H"));
  } else {
    $date->add(new DateInterval("PT".$b."H"));
}
  return $date->format('Y-m-d H:i:s');
}
?>
