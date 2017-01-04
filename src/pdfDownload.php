<?php
require_once('../plugins/html2pdf-4.5.1/html2pdf.class.php');

$doc="<page>Testdokument</page>";

$html2pdf = new HTML2PDF('P','A4','de', false, 'UTF-8');
//$html2pdf->setModeDebug();
$html2pdf->setDefaultFont('Arial');
$html2pdf->writeHTML($doc, false);
$datei="_DeliveryNote.pdf";
$html2pdf->Output($datei,"F");

 ?>
