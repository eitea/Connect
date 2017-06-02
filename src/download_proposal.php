<?php
if(isset($_POST['download_proposal'])){
  $proposalID = intval($_POST['download_proposal']);
} elseif(isset($_GET['propID'])){
  $proposalID = intval($_GET['propID']);
} else {
  $proposalID = 0;
}

if(isset($_POST['num'])){
  $proposal_number = preg_replace("~[^A-Za-z0-9\-?!=:.,/@€$%()+*öäüÖÄÜß\\n ]~", "", $_POST['num']);
} elseif(isset($_GET['num'])){
  $proposal_number = preg_replace("~[^A-Za-z0-9\-?!=:.,/@€$%()+*öäüÖÄÜß\\n ]~", "", $_GET['num']);
} else {
  $proposal_number = 'empty'; //false == 0 -> returns TRUE when mysql search for string LIKE %0%
}

if(!$proposalID && !$proposal_number){
  die("Access denied.");
}

require "../plugins/fpdf/fpdf.php";

class PDF extends FPDF {
  public $glob = array();
  public $netto_value= 0;
  public $vat_value= 0;
  function Header(){
    $this->Image($this->glob["logo"], 10, 10, 0, 27); //Image(string file [, float x [, float y [, float w [, float h [, string type [, mixed link]]]]]])
    $this->SetFont('Helvetica','',8);
    // Address
    $this->Cell(91,5);
    $this->MultiCell(100, 4, $this->glob["headerAddress"], 0, 'R');
    //2cm Line break
    $this->Line(10, 38, 210-10, 38); //1cm from each edge
    $this->Ln(3);
  }
  function Footer(){
    $this->SetFont('Helvetica','', 8);
    $this->Line(10, 280, 210-10, 280); //1cm from each edge
    // Position at 1.6 cm from bottom
    $this->SetXY(9, -16);
    //$this->Cell(0,5,$this->PageNo().'/{nb}',0,2,'C');
    $this->MultiColCell(61, 3, iconv('UTF-8', 'windows-1252',$this->glob['footer_left']));
    $this->MultiColCell(70, 3, iconv('UTF-8', 'windows-1252',$this->glob['footer_middle']), 0 , 'C');
    $this->MultiColCell(61, 3, iconv('UTF-8', 'windows-1252',$this->glob['footer_right']), 0, 'R');
  }
  function ImprovedTable($header, $data, $w){
    $this->SetFillColor(200,200,200);
    // Column widths
    $w = array(15, 70, 15, 30, 20, 30);
    // Header
    for($i=0;$i<count($header);$i++){
      if($i < 2) $this->Cell($w[$i],7,$header[$i],'',0,'L',1);
      if($i > 1) $this->Cell($w[$i],7,$header[$i],'',0,'R',1);
    }
    $this->Ln();
    $i = 1;
    foreach($data as $row){
      //Position
      $this->Cell($w[0],6,sprintf('#%03d', $i), '');
      //Name
      $this->Cell($w[1],6,iconv('UTF-8', 'windows-1252',$row['name']),'',2);
      //Description
      $this->SetFont('Arial','',8);
      $x = $this->GetX();
      $y = $this->GetY();
      $this->MultiCell($w[1],4,iconv('UTF-8', 'windows-1252',$row['description']),'');
      $this->SetFont('Helvetica','',10);
      if($row['description']){
        $this->SetXY($x + $w[1], $this->GetY() - 6);
      } else {
        $this->SetXY($x + $w[1], $y - 1);
      }

      $this->Cell($w[2],6,$row['quantity'].' '.iconv('UTF-8', 'windows-1252',$row['unit']),'',0,'R');
      $this->Cell($w[3],6,sprintf('%.2f', $row['price']). ' EUR','',0,'R');
      $this->Cell($w[4],6,$row['percentage']. '%','',0,'R');
      $this->Cell($w[5],6,sprintf('%.2f', $row['total']). ' EUR','',0,'R');
      $this->Ln();
      $this->Line(15, $this->GetY(), 210-15, $this->GetY());

      $this->netto_value += $row['total'];
      $this->vat_value += $row['total'] * $row['percentage'] / 100;
      $i++;
    }
  }
  function MultiColCell($w, $h, $txt, $border=0, $align='J', $fill=false, $offset=0){
    $x = $this->GetX();
    $y = $this->GetY();
    $this->SetXY($x + $offset, $y);
    $this->MultiCell($w, $h, $txt, $border, $align, $fill);
    $this->SetXY($x + $w + $offset, $y);
  }
}

require "connection.php";
require "language.php";

$result = $conn->query("SELECT proposals.*, proposals.id AS proposalID, companyData.*, clientData.*, clientData.name AS clientName,
  clientInfoData.title, clientInfoData.firstname, clientInfoData.vatnumber, clientInfoData.name AS lastname, clientInfoData.nameAddition, clientInfoData.address_Street,
  clientInfoData.address_Country, clientInfoData.address_Country_Postal, clientInfoData.address_Country_City
  FROM proposals
  INNER JOIN clientData ON proposals.clientID = clientData.id
  INNER JOIN clientInfoData ON clientInfoData.clientID = clientData.id
  INNER JOIN companyData ON clientData.companyID = companyData.id
  WHERE proposals.id = $proposalID OR proposals.id_number = '$proposal_number' OR proposals.history LIKE '%$proposal_number%'");
if(mysqli_error($conn)){
  echo mysqli_error($conn);
  die();
}
$row = $result->fetch_assoc();
if(empty($row['logo']) || empty($row['address']) || empty($row['cmpDescription'])){
  die($lang['ERROR_MISSING_DATA']. "(Name, Logo, Adr.)");
} elseif(empty($row['gender'])){
  $gender_exception = '';
} else {
  $gender_exception = $lang['GENDER_TOSTRING'][$row['gender']];
}

$pdf = new PDF();
$pdf->glob['logo'] = $row['logo'];
$pdf->glob['headerAddress'] = iconv('UTF-8', 'windows-1252', $row['cmpDescription']."\n".$row['address']."\n".$row['companyPostal'].' '.$row['companyCity']."\n".$row['uid']."\n".$row['phone']."\n".$row['homepage']."\n".$row['mail']);
$pdf->glob['footer_left'] = $row['detailLeft'];
$pdf->glob['footer_middle'] = $row['detailMiddle'];
$pdf->glob['footer_right'] = $row['detailRight'];
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1, 20); //(true [margin-bot])
$pdf->AddPage();
//narrow down page margins
$pdf->SetLeftMargin(15);
$pdf->SetRightMargin(15);

//abs
$pdf->SetFont('Helvetica','U',7);
$pdf->Cell(0,3, iconv('UTF-8', 'windows-1252', 'Abs.: '.$row['cmpDescription'].' · '.$row['address'].' · '.$row['companyPostal'].' '.$row['companyCity']), 0, 1);
$pdf->Ln(2);
$pdf->SetFont('Helvetica','',10);
//client general data
$pdf->Cell(0,5, iconv('UTF-8', 'windows-1252', $row['clientName']), 0, 2);
if($row['firstname'] || $row['lastname']){
  $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', trim($row['title'].' '.$row['firstname'].' '.$row['lastname'].' '.$row['nameAddition'])), 0, 2 );
}
if($row['address_Street']){
  $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', $row['address_Street']), 0, 2 );
}
if($row['address_Country'] || $row['address_Country_Postal'] || $row['address_Country_City']){
  $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', trim($row['address_Country'].' '.$row['address_Country_Postal'].' '.$row['address_Country_City'])), 0, 2 );
}

$pdf->Ln(5);
//client proposal data
$pdf->SetFontSize(14);
if($proposal_number == 'empty'){
  $proposal_number = $row['id_number'];
}
$pdf->MultiColCell(110, 7, iconv('UTF-8', 'windows-1252',$lang['PROPOSAL_TOSTRING'][preg_replace('/\d/', '', $proposal_number)])."\n".$proposal_number);
$pdf->SetFontSize(8);

$pdf->SetY($pdf->GetY() - 5, false); //SetY(float y [, boolean resetX = true])
$pdf->MultiColCell(40, 4, $lang['DATE'].": \n".iconv('UTF-8', 'windows-1252',$lang['EXPIRATION_DATE']).": \n".$lang['CLIENTS'].' '.$lang['NUMBER'].": \n". $lang['VAT'] ." ID: \n". $lang['REPRESENTATIVE'].': ');
$pdf->MultiColCell(30, 4, date('d.m.Y', strtotime($row['curDate']))."\n". date('d.m.Y', strtotime($row['deliveryDate']))."\n".$row['clientNumber']."\n".$row['vatnumber']."\n".$row['representative']);
$pdf->SetY($pdf->GetY() + 5, false);
$pdf->Ln(25);
$pdf->MultiColCell(45, 4, $lang['PROP_YOUR_SIGN']."\n".$row['yourSign']);
$pdf->MultiColCell(55, 4, $lang['PROP_YOUR_ORDER']."\n".$row['yourOrder']);
$pdf->MultiColCell(45, 4, $lang['PROP_OUR_SIGN']."\n".$row['ourSign']);
$pdf->MultiColCell(50, 4, $lang['PROP_OUR_MESSAGE']."\n".$row['ourMessage']);

//products
$pdf->SetFontSize(10);
$result = $conn->query("SELECT products.*,taxRates.percentage, taxRates.description AS taxName, (quantity * price) AS total FROM products, taxRates WHERE proposalID = ".$row['proposalID'].' AND taxID = taxRates.id');
if($result && $result->num_rows > 0){
  $productResults = $result->fetch_all(MYSQLI_ASSOC);
  $pdf->Ln(10);
  $pdf->ImprovedTable(array('Position', 'Name', $lang['QUANTITY'], $lang['PRICE_STK'], $lang['TAXES'], $lang['TOTAL_PRICE']), $productResults , array());
}

$pdf->Line(15, $pdf->GetY() + 1, 210-15, $pdf->GetY() + 1);
$pdf->Ln(5);

if(280 - $pdf->GetY() < 30){ $pdf->AddPage(); } //if writable space is less than 3cm: new page
//Summary
$pdf->SetFontSize(8);
$pdf->MultiColCell(30, 4, "Warenwert \nPorto");
$pdf->MultiColCell(30, 4, $pdf->netto_value." EUR\n  ".$row['porto']." EUR", 0, 'R');
$pdf->SetFontSize(10);

//porto is just another product
$result = $conn->query("SELECT percentage from taxRates WHERE id = ".$row['portoRate']);
if($result && $result->num_rows > 0){
  $porto_row = $result->fetch_assoc();
  $porto_vat = $row['porto'] * $porto_row['percentage'] / 100;
}
$amount_netto = $pdf->netto_value + $row['porto'];
$amount_vat = $pdf->vat_value + $porto_vat;
$pdf->MultiColCell(30, 6, $lang['AMOUNT']." netto \n".$lang['AMOUNT'].' '.$lang['VAT'], 'B', 1, 0, 60);
$pdf->MultiColCell(30, 6, sprintf('%.2f', $amount_netto)." EUR\n".sprintf('%.2f', $amount_vat)." EUR", 'B', 'R');
$pdf->SetFont('Helvetica', 'B');
$pdf->Cell(0, 13, '', 0 , 1);
$pdf->Cell(120);
$pdf->Cell(30, 6, $lang['SUM']);
$pdf->Cell(30, 6, sprintf('%.2f', $amount_netto + $amount_vat).' EUR', 0 , 1, 'R');
$pdf->SetFont('Helvetica');
$pdf->Ln(5);

//erp text
$pdf->SetFontSize(7);
$pdf->MultiCell(0, 3, iconv('UTF-8', 'windows-1252',$row['erpText']));
$pdf->SetFontSize(10);

//payment conditions
$pdf->SetFont('Helvetica', 'UB');
$pdf->Cell(0, 10, $lang['PAYMENT_CONDITIONS'].':', 0, 1);
$pdf->SetFont('Helvetica', '');
if($row['skonto1']){
  $pdf->Cell(0, 0, $row['skonto1'].'% Skonto '.$lang['WITHIN'].' '.$row['skonto1Days'].' '.$lang['DAYS'], 0, 1);
}
if($row['daysNetto']){
  $pdf->Cell(0, 8, 'Netto '.$lang['WITHIN'].' '.$row['daysNetto'].' '.$lang['DAYS'], 0, 1);
}

/*
A4 = 210 x 297
Cell(wdith, height, text, border, ln, align, fill, link)
MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]])
Line(left margin, x, right margin, y)
*/
$pdf->Output(0, $proposal_number.'.pdf');
?>
