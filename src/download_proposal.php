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

require dirname(__DIR__)."/plugins/fpdf/fpdf.php";

class PDF extends FPDF {
  public $glob = array();
  function Header(){
    $this->Image($this->glob["logo"], 10, 10, 0, 27); //Image(string file [, float x [, float y [, float w [, float h [, string type [, mixed link]]]]]])
    $this->SetFont('Helvetica','',8);
    // Address
    $this->Cell(91,5);
    $this->MultiCell(100, 4, $this->glob["headerAddress"], 0, 'R');
    //2cm Line break
    $this->Line(10, 38, 210-10, 38); //1cm from each edge
    $this->Ln(5);
  }
  function Footer(){
    $this->SetFont('Helvetica','', 8);
    $this->Line(10, 280, 210-10, 280);
    // Position at 1.6 cm from bottom
    $this->SetXY(9, -16);
    //$this->Cell(0,5,$this->PageNo().'/{nb}',0,2,'C');
    $this->MultiColCell(61, 3, iconv('UTF-8', 'windows-1252',$this->glob['footer_left']));
    $this->MultiColCell(70, 3, iconv('UTF-8', 'windows-1252',$this->glob['footer_middle']), 0 , 'C');
    $this->MultiColCell(61, 3, iconv('UTF-8', 'windows-1252',$this->glob['footer_right']), 0, 'R');
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
require "encryption_functions.php";

$result = $conn->query("SELECT proposals.*, proposals.id AS proposalID, companyData.*, clientData.*, clientData.name AS clientName, companyData.name AS companyName,
  clientInfoData.title, clientInfoData.firstname, clientInfoData.vatnumber, clientInfoData.name AS lastname, clientInfoData.nameAddition, clientInfoData.address_Street,
  clientInfoData.address_Country, clientInfoData.address_Country_Postal, clientInfoData.address_Country_City, erpNumbers.yourSign, erpNumbers.yourOrder, erpNumbers.ourSign, erpNumbers.ourMessage
  FROM proposals
  INNER JOIN clientData ON proposals.clientID = clientData.id
  INNER JOIN clientInfoData ON clientInfoData.clientID = clientData.id
  INNER JOIN companyData ON clientData.companyID = companyData.id
  INNER JOIN erpNumbers ON erpNumbers.companyID = companyData.id
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

//create the image and destroy it once we're done. For demacia.
$logo_path = dirname(__DIR__)."/images/ups/".str_replace(' ', '-',$row['companyName']).'.jpg';
file_put_contents($logo_path, $row['logo']) or die("Unable to create file");
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if(finfo_file($finfo, $logo_path) == 'image/png'){
  $logo_path = 'images/ups/'.$row['companyName'].'.png';
  file_put_contents($logo_path, $row['logo']);
}

$pdf->glob['logo'] = $logo_path;
$pdf->glob['headerAddress'] = iconv('UTF-8', 'windows-1252', $row['cmpDescription']."\n".$row['address']."\n".$row['companyPostal'].' '.$row['companyCity']."\n".$row['uid']."\n".$row['phone']."\n".$row['homepage']."\n".$row['mail']);
$pdf->glob['footer_left'] = $row['detailLeft'];
$pdf->glob['footer_middle'] = $row['detailMiddle'];
$pdf->glob['footer_right'] = $row['detailRight'];
$pdf->AliasNbPages();
$pdf->SetAutoPageBreak(1, 20); //(true [margin-bot])
$pdf->AddPage();
//narrow down page margins
$pdf->SetLeftMargin(10);
$pdf->SetRightMargin(10);

//abs
$pdf->SetFont('Helvetica','U',7);
$pdf->Cell(0,3, iconv('UTF-8', 'windows-1252', 'Abs.: '.$row['cmpDescription'].' · '.$row['address'].' · '.$row['companyPostal'].' '.$row['companyCity']), 0, 1);
$pdf->Ln(2);
$pdf->SetFont('Helvetica','',10);

//client general data
$pdf->Cell(0,5, iconv('UTF-8', 'windows-1252', $row['clientName']), 0, 2);
if($row['firstname'] || $row['lastname']){
  $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', trim($row['title'].' '.$row['firstname'].' '.$row['lastname'])), 0, 2 );
}
if($row['nameAddition']){
  $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', trim($row['nameAddition'])), 0, 2);
}
if($row['address_Street']){
  $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', $row['address_Street']), 0, 2 );
}
if($row['address_Country_Postal'] || $row['address_Country_City']){
  $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', trim($row['address_Country_Postal'].' '.$row['address_Country_City'])), 0, 2 );
}
if($row['address_Country']){
  $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', trim($row['address_Country'])), 0, 2 );
}

$pdf->Ln(5);
//client proposal data
$pdf->SetFontSize(14);
if($proposal_number == 'empty'){
  $proposal_number = $row['id_number'];
}
$proposal_mark = preg_replace('/\d/', '', $proposal_number);

$col = iconv('UTF-8', 'windows-1252',$lang['DATE'].": \n".$lang['EXPIRATION_DATE'].": \n".$lang['CLIENTS'].strtolower($lang['NUMBER']).": \n".$lang['VAT']." ID: ");
$col2 = iconv('UTF-8', 'windows-1252', date('d.m.Y', strtotime($row['curDate']))."\n". date('d.m.Y', strtotime($row['deliveryDate']))."\n".$row['clientNumber']."\n".$row['vatnumber']);

if($proposal_mark == 'RE') {
  $col = iconv('UTF-8', 'windows-1252',$lang['DATE'].": \n".$lang['CLIENTS'].strtolower($lang['NUMBER']).": \n".$lang['VAT']." ID: ");
  $col2 = iconv('UTF-8', 'windows-1252', date('d.m.Y', strtotime($row['curDate']))."\n".$row['clientNumber']."\n".$row['vatnumber']);
}

if($row['representative']){
  $col .= iconv('UTF-8', 'windows-1252',"\n".$lang['REPRESENTATIVE'].':');
  $col2 .= iconv('UTF-8', 'windows-1252',"\n".$row['representative']);
}

$pdf->MultiColCell(110, 7, iconv('UTF-8', 'windows-1252',$lang['PROPOSAL_TOSTRING'][$proposal_mark])."\n".$proposal_number);
$pdf->SetY($pdf->GetY() - 5, false); //SetY(float y [, boolean resetX = true])
$pdf->SetFontSize(8);
$pdf->MultiColCell(25, 4, $col, 0, 'L', 0, 30);
$pdf->MultiColCell(0, 4, $col2, 0, 'R');

$pdf->Ln(20);
if($row['header']) {
  $pdf->Ln(5);
  $pdf->SetFontSize(10);
  $pdf->MultiCell(0, 4, iconv('UTF-8', 'windows-1252', $row['header']));
}

if($row['referenceNumrow'] == 'checked'){
  $pdf->Ln(5);
  $pdf->SetFontSize(8);
  $pdf->MultiColCell(50, 4, $lang['PROP_YOUR_SIGN']."\n".$row['yourSign']);
  $pdf->MultiColCell(55, 4, $lang['PROP_YOUR_ORDER']."\n".$row['yourOrder']);
  $pdf->MultiColCell(50, 4, $lang['PROP_OUR_SIGN']."\n".$row['ourSign']);
  $pdf->MultiColCell(50, 4, $lang['PROP_OUR_MESSAGE']."\n".$row['ourMessage']);
  $pdf->Ln(5);
}

//PRODUCT TABLE
$i = 1;
$netto_value = $vat_value = $cash_value = $part_sum_netto = 0;
$pdf->SetFontSize(10);
$prod_res = $conn->query("SELECT *, (quantity * price) AS total FROM products WHERE proposalID = ".$row['proposalID'] .' ORDER BY position ASC');
if($prod_res && $prod_res->num_rows > 0){
  $pdf->Ln(5);
  $pdf->SetFillColor(200,200,200);
  // Column widths
  $w = array(15, 70, 25, 30, 20, 0);
  // Header
  $pdf->Cell($w[0],7,'Position',0,0,'L',1);
  $pdf->Cell($w[1],7,'Name',0,0,'L',1);
  $pdf->Cell($w[2],7,$lang['QUANTITY'], '', 0, 'R', 1);
  $pdf->Cell($w[3],7,$lang['PRICE_STK'], '', 0, 'R', 1);
  $pdf->Cell($w[4],7,$lang['TAXES'], '', 0, 'R', 1);
  $pdf->Cell($w[5],7,$lang['TOTAL_PRICE'], '', 1, 'R', 1);

  while($prod_row = $prod_res->fetch_assoc()){
    $mc = mc($prod_row["iv"],$prod_row["iv2"]);
    $prod_row["name"] = $mc->decrypt($prod_row["name"]);
    $prod_row["description"] = $mc->decrypt($prod_row["description"]);
    if($prod_row['name'] == 'NEW_PAGE'){
      $pdf->AddPage();
    } elseif($prod_row['name'] == 'PARTIAL_SUM'){
      $pdf->Cell($w[0],6);
      $pdf->SetFont('Helvetica','B');
      $pdf->Cell($w[1] + $w[2] + $w[3] + $w[4],6,$lang['PARTIAL_SUM']);
      $pdf->Cell($w[5],6,number_format($netto_value - $part_sum_netto,2,',','.'). ' EUR',0,1,'R');
      $pdf->SetFont('Helvetica');
      $pdf->Line(10, $pdf->GetY(), 210-10, $pdf->GetY());
      $part_sum_netto += $netto_value - $part_sum_netto;
    } elseif($prod_row['name'] == 'CLEAR_TEXT'){
      $pdf->Cell($w[0],6);
      //$pdf->SetFont('Arial','',8);
      $x = $pdf->GetX();
      $y = $pdf->GetY();
      $pdf->MultiCell($w[1]+$w[2]+$w[3]+$w[4],5,iconv('UTF-8', 'windows-1252',$prod_row['description']));
      //$pdf->SetFont('Helvetica','',10);
      if($prod_row['description']){
        $pdf->SetXY($x + $w[1], $pdf->GetY() - 6);
      } else {
        $pdf->SetXY($x + $w[1], $y - 1);
      }
      $pdf->Cell($w[5],6,'',0,1);
      $pdf->Line(10, $pdf->GetY(), 210-10, $pdf->GetY());
    } else { //Position
      $pdf->Cell($w[0],6,sprintf('#%03d', $i));
      //Name
      $pdf->Cell($w[1],6,iconv('UTF-8', 'windows-1252',$prod_row['name']),0,2);
      //Description
      $pdf->SetFont('Arial','',8);
      $x = $pdf->GetX();
      $y = $pdf->GetY();
      $pdf->MultiCell($w[1],4,iconv('UTF-8', 'windows-1252',$prod_row['description']));
      $pdf->SetFont('Helvetica','',10);
      if($prod_row['description']){
        $pdf->SetXY($x + $w[1], $pdf->GetY() - 6);
      } else {
        $pdf->SetXY($x + $w[1], $y - 1);
      }
      //Quantity
      $pdf->Cell($w[2],6,number_format($prod_row['quantity'],2,',','.').' '.iconv('UTF-8', 'windows-1252', $prod_row['unit']),0,0,'R');
      //Price
      $pdf->Cell($w[3],6,number_format($prod_row['price'],2,',','.'). ' EUR',0,0,'R');
      //Taxes
      if($prod_row['cash'] == 'TRUE'){
        $pdf->Cell($w[4],6,'BAR','',0,'R');
        $cash_value += $prod_row['total'];
      } else {
        $pdf->Cell($w[4],6,intval($prod_row['taxPercentage']). '%',0,0,'R');
        $vat_value += $prod_row['total'] * $prod_row['taxPercentage'] / 100;
        $netto_value += $prod_row['total'];
      }
      $pdf->Cell($w[5],6,number_format($prod_row['total'],2,',','.'). ' EUR',0,1,'R');
      $pdf->Line(10, $pdf->GetY(), 210-10, $pdf->GetY());
      $i++;
    }
  } //endwhile
}

$pdf->Line(10, $pdf->GetY() + 1, 200, $pdf->GetY() + 1);
$pdf->Ln(2);

if(280 - $pdf->GetY() < 30){ $pdf->AddPage(); } //if writable space is less than 3cm: new page
//Summary
$pdf->SetFontSize(8);
$col1 = "Warenwert";
$col2 = number_format($netto_value, 2, ',', '.')." EUR";
if($row['porto']){
  $col1 .= "\nPorto";
  $col2 .= "\n".number_format($row['porto'], 2, ',', '.')." EUR";
}
$pdf->MultiColCell(30, 4, $col1);
$pdf->MultiColCell(30, 4, $col2 , 0, 'R');
$pdf->SetFontSize(10);

//porto is basically just another product
$porto_vat = $row['porto'] * $row['portoRate'] / 100;

$netto_value += $row['porto'];
$vat_value += $porto_vat;
$col1 =  $lang['AMOUNT']." netto \n".$lang['AMOUNT'].' '.$lang['VAT'];
$col2 = number_format($netto_value, 2, ',', '.')." EUR\n".number_format($vat_value, 2, ',', '.').' EUR';
$dist = 10;
if($cash_value){
  $col1 .= "\n".$lang['CASH_EXPENSE'];
  $col2 .= "\n".number_format($cash_value, 2, ',', '.').' EUR';
  $dist = 15;
}
$pdf->MultiColCell(30, 5, $col1, 'B', 1, 0, 70);
$pdf->MultiColCell(30, 5, $col2, 'B', 'R');
$pdf->SetFont('Helvetica', 'B');
$pdf->Ln($dist);
$pdf->Cell(130);
$pdf->Cell(30, 6, $lang['SUM']);
$pdf->Cell(30, 6, number_format($netto_value + $vat_value + $cash_value, 2, ',', '.').' EUR', 0 , 1, 'R');
$pdf->SetFont('Helvetica');
$pdf->Ln(5);

//erp text
$pdf->SetFontSize(7);
$pdf->MultiCell(0, 3, iconv('UTF-8', 'windows-1252',$row['erpText']));

//payment conditions
$pdf->SetFont('Helvetica', 'UB', 10);
$pdf->Cell(0, 8, $lang['PAYMENT_CONDITIONS'].':', 0, 1);
$pdf->SetFont('Helvetica', '');

$payment_name = $payment_1 = $payment_2 = $payment_3 = '';

if($row['paymentMethod']){
  $result = $conn->query("SELECT * FROM paymentMethods WHERE name = '".$row['paymentMethod']."'");
  if($result && ($paymentRow = $result->fetch_assoc())){
    $payment_name = $paymentRow['name'];
    if($paymentRow['daysNetto'] > 0){
      $date = date("d.m.Y", strtotime("+".$paymentRow['daysNetto']." days", strtotime($row['curDate'])));
      $payment_1 = 'Netto '.$lang['WITHIN'].' '.$paymentRow['daysNetto'].' '.$lang['DAYS'].'('.$lang['TO'].' ('.$lang['TO'].' '.$date.'): '.$netto_value.' EUR';
      $payment_name = '';
    }
    if($paymentRow['skonto1'] > 0) {
      $date = date("d.m.Y", strtotime("+".$paymentRow['skonto1Days']." days", strtotime($row['curDate'])));
      $payment_2 = $paymentRow['skonto1'].'% '.$lang['WITHIN'].' '.$paymentRow['skonto1Days'].' '.$lang['DAYS'].' ('.$lang['TO'].' '.$date.'): '. number_format($netto_value * ((100 - $paymentRow['skonto1']) / 100), 2, ',', '.');
      $payment_name = '';
    }
    if($paymentRow['skonto2'] > 0){
      $date = date("d.m.Y", strtotime("+".$paymentRow['skonto2Days']." days", strtotime($row['curDate'])));
      $payment_3 =  $paymentRow['skonto2'].'% '.$lang['WITHIN'].' '.$paymentRow['skonto2Days'].' '.$lang['DAYS'].' ('.$lang['TO'].' '.$date.'): '. number_format($netto_value * ((100 - $paymentRow['skonto2']) / 100), 2, ',', '.');
      $payment_name = '';
    }
  }
  echo $conn->error;
}

if($payment_name) $pdf->Cell(0, 4, iconv('UTF-8', 'windows-1252', $payment_name), 0, 1);
if($payment_1) $pdf->Cell(0, 5, $payment_1.' EUR' , 0, 1);
if($payment_2) $pdf->Cell(0, 5, $payment_2.' EUR', 0, 1);
if($payment_3) $pdf->Cell(0, 5, $payment_3.' EUR', 0, 1);

/*
A4 = 210 x 297
Cell(wdith, height, text, border, ln(right, Ln, below), align, fill, link)
MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]])
Line(left margin, x, right margin, y)
*/
$pdf->Output(0, $proposal_number.'.pdf');

unlink($logo_path);
?>
