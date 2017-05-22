<?php
if(isset($_POST['download_proposal'])){
  $proposalID = intval($_POST['download_proposal']);
} elseif(isset($_GET['propID'])){
  $proposalID = intval($_GET['propID']);
} else {
  die("Access denied.");
}

require "../plugins/fpdf/fpdf.php";

class PDF extends FPDF {
  public $glob = array();
  public $good_value = 0;
  function Header(){
    $this->Image($this->glob["logo"], 10, 10, 40); //Image(string file [, float x [, float y [, float w [, float h [, string type [, mixed link]]]]]])
    $this->SetFont('Helvetica','',8);
    //move 5cm away from right, 1.2cm down
    // Address
    $this->MultiCell(40, 4, $this->glob["headerAddress"], 0, 'R');
    //2cm Line break
    $this->Line(10, 42, 210-10, 42); //1cm from each edge
    $this->Ln(5);
  }
  function Footer(){
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    $this->SetFont('Times','',6);
    // Page number
    $this->Cell(0,10,$this->PageNo().'/{nb}',0,1,'C');
  }
  function ImprovedTable($header, $data, $w){
    $this->SetFillColor(200,200,200);
    // Column widths
    $w = array(20, 70, 20, 30, 40);
    // Header
    for($i=0;$i<count($header);$i++){
      if($i < 3) $this->Cell($w[$i],7,$header[$i],'B',0,'L',1);
      if($i > 2) $this->Cell($w[$i],7,$header[$i],'B',0,'R',1);
    }
    $this->Ln();
    $i = 1;
    foreach($data as $row){
      //Position
      $this->Cell($w[0],6,sprintf('#%03d', $i), 'T');
      //Name
      $this->Cell($w[1],6,iconv('UTF-8', 'windows-1252',$row['name']),'T',2);
      //Description
      $this->SetFont('Arial','',8);
      $x = $this->GetX();
      $y = $this->GetY();
      $this->MultiCell($w[1],6,iconv('UTF-8', 'windows-1252',$row['description']),'');
      $this->SetFont('Helvetica','',10);
      if($row['description']){
        $this->SetXY($x + $w[1], $this->GetY());
      } else {
        $this->SetXY($x + $w[1], $y);
      }
      $this->Cell($w[2],6,$row['quantity'].' '.iconv('UTF-8', 'windows-1252',$row['unit']),'B',0,'L');
      $this->Cell($w[3],6,sprintf('%.2f', $row['price']). ' EUR','B',0,'R');
      $this->Cell($w[4],6,sprintf('%.2f', $row['total']). ' EUR','B',0,'R');
      $this->Ln();

      $this->good_value += $row['total'];
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

$result = $conn->query("SELECT proposals.*, proposals.id AS proposalID, companyData.*, companyData.name AS companyName, clientData.*, clientData.name AS clientName,
  clientInfoData.title, clientInfoData.firstname, clientInfoData.vatnumber, clientInfoData.name AS lastname, clientInfoData.nameAddition, clientInfoData.address_Street,
  clientInfoData.address_Country, clientInfoData.address_Country_Postal, clientInfoData.address_Country_City
  FROM proposals
  INNER JOIN clientData ON proposals.clientID = clientData.id
  INNER JOIN clientInfoData ON clientInfoData.clientID = clientData.id
  INNER JOIN companyData ON clientData.companyID = companyData.id
  WHERE proposals.id = $proposalID");
if(mysqli_error($conn)){
  echo mysqli_error($conn);
  die();
}
$row = $result->fetch_assoc();
if(empty($row['logo']) || empty($row['address'])){
  die($lang['ERROR_MISSING_DATA']. "(Logo, Adr.)");
} elseif(empty($row['gender'])){
  $gender_exception = '';
} else {
  $gender_exception = $lang['GENDER_TOSTRING'][$row['gender']].' ';
}

$pdf = new PDF();
$pdf->glob['logo'] = $row['logo'];
$pdf->glob['headerAddress'] = iconv('UTF-8', 'windows-1252',$row['companyName']).' '.$row['companyType']. "\n" .$row['address']."\n".$row['phone']."\n".$row['homepage']."\n".$row['mail'];
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1, 30);
$pdf->AddPage();
$pdf->SetFont('Helvetica','',10);
//narrow down page margins
$pdf->SetLeftMargin(15);
$pdf->SetRightMargin(15);
//client general data
$row['title'] .= ' ';
$row['firstname'] .= ' ';
$pdf->MultiCell(0, 5 , iconv('UTF-8', 'windows-1252', $row['clientName']."\n".$gender_exception.$row['title'].
$row['firstname'].$row['lastname'].' '.$row['nameAddition']."\n".$row['address_Street']."\n".$row['address_Country'].' '.$row['address_Country_Postal'].' '.$row['address_Country_City']));
$pdf->Ln(5);
//client proposal data
$pdf->SetFontSize(14);
$pdf->MultiColCell(110, 7, $lang['OFFER']."\n".$row['id_number']);
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
  $pdf->Ln(10);
  $pdf->ImprovedTable(array('Position', 'Name', $lang['QUANTITY'], $lang['PRICE_STK'], $lang['TOTAL_PRICE']), $productResults = $result->fetch_all(MYSQLI_ASSOC), array());
}
$pdf->Line(15, $pdf->GetY() + 10, 210-15, $pdf->GetY() + 10);
$pdf->Line(15, $pdf->GetY() + 11, 210-15, $pdf->GetY() + 11);
$pdf->Ln(12);

if(260 - $pdf->GetY() < 20){ $pdf->AddPage(); } //if writable space is less than 2cm: new page
//Summary
$pdf->SetFontSize(8);
$pdf->MultiColCell(30, 4, "Rabatt \nRabattbetrag \nWarenwert \nPorto");
$pdf->MultiColCell(30, 4, "xx %\n xx EUR\n ".$pdf->good_value." EUR\n  ".$row['porto']." EUR", 0, 'R');
$pdf->SetFontSize(10);
$amount_netto = $pdf->good_value + $row['porto'];
$amount_vat = $amount_netto * 0.2;
$pdf->MultiColCell(30, 6, $lang['AMOUNT']." netto \n".$lang['AMOUNT'].' '.$lang['VAT'], 'B', 1, 0, 60);
$pdf->MultiColCell(30, 6, $amount_netto." EUR\n".$amount_vat." EUR", 'B', 'R');
$pdf->SetFont('Helvetica', 'B');
$pdf->Cell(0, 13, '', 0 , 1);
$pdf->Cell(120);
$pdf->Cell(30, 6, $lang['SUM']);
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
$pdf->Output();
?>
