<?php
if($_SERVER['REQUEST_METHOD'] != 'POST'){
  header("Location: getProjects.php");
}
require "connection.php";

$filterQuery = $_POST['filterQuery'];
$templateID = $_POST['templateID'];

$result = $conn->query("SELECT htmlCode FROM $pdfTemplateTable WHERE id = $templateID");

if($result && ($row = $result->fetch_assoc())){
  $html = $row['htmlCode'];
  //grab positions
  $pos1 = strpos($html, "[REPEAT]");
  $pos2 = strpos($html, "[REPEAT END]");
  //explode my repeat pattern
  $html_head = substr($html, 0, $pos1);
  $html_foot = substr($html, $pos2 + 12);
  $repeat = substr($html, $pos1 + 12 , $pos2 - $pos1 - 12);
} else {
  die("Could not fetch template. Please make sure it exists. Contact support for further issues."); //We dont actually have a support.
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

//glue my html back together and prepend css
$html = '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><link href="../plugins/homeMenu/template.css" rel="stylesheet" /></head>' . $html_head . $html_foot;

//replace relative paths with absolute paths
$doc = new DOMDocument();
@$doc->loadHTML($html);

$tags = $doc->getElementsByTagName('img');

foreach ($tags as $tag) {
  $relPath = $tag->getAttribute('src');
  $absPath = str_replace('\\', '/', dirname(dirname(dirname(realpath("pdfDownload.php")))) . $relPath);

  $imgAbsElement = $doc->createElement("img");
  $imgAbsAttribute = $doc->createAttribute("src");
  $imgAbsAttribute->value = $absPath;

  $imgAbsElement->appendChild($imgAbsAttribute);
  $tag->parentNode->replaceChild($imgAbsElement, $tag);
}

$html = $doc->saveHTML();

//display the pdf

require_once "../plugins/dompdf/autoload.php";
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', TRUE);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
$dompdf->stream("sample.pdf", array("Attachment"=>0));


//echo $html;
function carryOverAdder_Hours($a, $b){
  if($a == '0000-00-00 00:00:00'){
    return $a;
  }
  $date = new DateTime($a);
  if($b < 0){
    $b *= -1;
    $date->sub(new DateInterval("PT".$b."H"));
  } else {
    $date->add(new DateInterval("PT".$b."H"));
  }
  return $date->format('Y-m-d H:i:s');
}
?>
