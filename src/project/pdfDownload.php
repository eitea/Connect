<?php
if($_SERVER['REQUEST_METHOD'] != 'POST'){
  header("Location: bookings");
}
require dirname(__DIR__) . "/connection.php";
require_once dirname(__DIR__) . '/utilities.php';
require dirname(__DIR__) . "/Calculators/IntervalCalculator.php";

$filterQuery = $_POST['filterQuery'];
$templateID = $_POST['templateID'];

if($templateID < 0) include __DIR__ . "/download_overview.php";

$html = getFilledOutTemplate($templateID, $filterQuery); //query must contain WHERE clause

//prepend css
$html = '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><link href="plugins/homeMenu/template.css" rel="stylesheet" /></head>' .$html;

//replace all occuring relative paths with absolute paths in the html
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
require_once dirname(dirname(__DIR__)) ."/plugins/dompdf/autoload.php";
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', TRUE);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->render();
$dompdf->stream("sample.pdf", array("Attachment"=>0));
?>
