<?php
if(isset($_POST['download_proposal'])){
  $proposalID = intval($_POST['download_proposal']);
} else {
die("Access denied.");
}

require "../plugins/fpdf/fpdf.php";

class PDF extends FPDF {
  public $glob = array(); //I know, I know..
  public $good_value = 0;
  function Header(){
    $this->Image($this->glob["logo"], 15, 10, 40);
    $this->SetFont('Helvetica','',8);
    // Move 8cm to the right
    $this->Cell(150);
    // 1cm down
    $this->Cell(0, 10, '', 0, 2);
    // Address
    $this->MultiCell(40, 4, $this->glob["headerAddress"], 0, 'R');
    //2cm Line break
    $this->Line(10, 45, 210-10, 45); //1cm from each edge
    $this->Ln(10);
  }
  function Footer(){
    // Position at 1.5 cm from bottom
    $this->SetY(-15);
    $this->SetFont('Times','',6);
    // Page number
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
  }
  function ImprovedTable($header, $data){
    $this->SetFillColor(200,200,200);
    // Column widths
    $w = array(70, 30, 35, 45);
    // Header
    for($i=0;$i<count($header);$i++){
      $this->Cell($w[$i],7,$header[$i],'B',0,'C',1);
    }
    $this->Ln();
    // Data
    foreach($data as $row){
      $x = $this->GetX();
      $y = $this->GetY();

      $this->Cell($w[0],6,iconv('UTF-8', 'windows-1252',$row[0]),'T',2);

      $this->SetFont('Arial','',8);
      $x = $this->GetX();
      $y = $this->GetY();
      $this->MultiCell($w[0],6,iconv('UTF-8', 'windows-1252',$row[1]),'');
      $this->SetFont('Helvetica','',10);
      if($row[1]){
        $this->SetXY($x + $w[0], $this->GetY());
      } else {
        $this->SetXY($x + $w[0], $y);
      }

      $this->Cell($w[1],6,$row[2],'B',0,'R');
      $this->Cell($w[2],6,sprintf('%.2f', $row[3]). ' EUR','B',0,'R');
      $this->Cell($w[3],6,sprintf('%.2f', $row[4]). ' EUR','B',0,'R');
      $this->Ln();

      $this->good_value += $row[4];
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
$result = $conn->query("SELECT *, companyData.name AS companyName, clientData.name AS clientName, clientInfoData.name AS dude, proposals.id AS proposalID
  FROM proposals
  INNER JOIN clientData ON proposals.clientID = clientData.id
  INNER JOIN clientInfoData ON clientInfoData.clientID = clientData.id
  INNER JOIN companyData ON clientData.companyID = companyData.id
  WHERE proposals.id = $proposalID");
$row = $result->fetch_assoc();
echo mysqli_error($conn);

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
$pdf->MultiCell(0, 5 , iconv('UTF-8', 'windows-1252', $row['clientName']."\n".$lang['GENDER_TOSTRING'][$row['gender']].' '.$row['title'].' '.$row['dude'].' '.$row['nameAddition']."\n".$row['address_Street']."\n".$row['address_Country']));
$pdf->Ln(10);
//client proposal data
$pdf->SetFontSize(14);
$pdf->MultiColCell(110, 7, $lang['OFFER']."\n".$row['id_number']);
$pdf->SetFontSize(8);
$pdf->MultiColCell(40, 5, $lang['CLIENTS'].' '.$lang['NUMBER'].': '."\n".$lang['REPRESENTATIVE'].': ');
$pdf->MultiColCell(30, 5, $row['clientNumber'] ."\n".$row['representative']);

$pdf->Ln(5);
//products
$pdf->SetFontSize(10);
$result = $conn->query("SELECT name, description, quantity, price, (quantity * price) AS total FROM products WHERE proposalID = ".$row['proposalID']);
$productResults = '';
if($result && $result->num_rows > 0){
  $pdf->Ln(10);
  $pdf->ImprovedTable(array('Name', $lang['QUANTITY'], $lang['PRICE_STK'], $lang['TOTAL_PRICE']), $productResults = $result->fetch_all(MYSQLI_NUM));
}
$pdf->Line(15, $pdf->GetY() + 10, 210-15, $pdf->GetY() + 10);
$pdf->Line(15, $pdf->GetY() + 11, 210-15, $pdf->GetY() + 11);
$pdf->Ln(12);
//Summary
$pdf->SetFontSize(8);
$pdf->MultiColCell(30, 4, "Rabatt \nRabattbetrag \nWarenwert \nPorto");
$pdf->MultiColCell(30, 4, "xx % \nxx EUR \n".$pdf->good_value." EUR\nxx EUR", 0, 'R');
$pdf->SetFontSize(10);
$pdf->MultiColCell(30, 6, $lang['AMOUNT']." netto \n".$lang['AMOUNT'].' '.$lang['VAT'], 0, 1, 0, 60);
$pdf->MultiColCell(30, 6, "xx EUR\n xx EUR", 0, 'R');
$pdf->Ln(25);

//end of document
$pdf->SetFontSize(7);
$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252',$row['erpText']));
$pdf->SetFontSize(10);

$pdf->SetFont('Helvetica', 'UB');
$pdf->Cell(0, 10, $lang['PAYMENT_CONDITIONS'].':', 0, 1);
$pdf->SetFont('Helvetica', '');
$pdf->Cell(0, 0, $lang['PAYMENT_NETTO_CONDITION'].$row['daysNetto'].$lang['DAYS'], 0, 1);

//Footer

/*
Cell(wdith, height, text, border, ln, align, fill, link)
MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]])
Line(left margin, x, right margin, y)
*/
$pdf->Output(); //header
?>
