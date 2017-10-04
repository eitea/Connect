<?php
require dirname(__DIR__)."/plugins/fpdf/fpdf.php";

class PDF extends FPDF {
  public $glob = array();
  function Header(){
    $this->Image($this->glob['logo'], 10, 10, 0, 27); //Image(string file [, float x [, float y [, float w [, float h [, string type [, mixed link]]]]]])
    $this->SetFont('Helvetica','',8);
    // Address
    $this->Cell(91,5);
    $this->MultiCell(100, 4, $this->glob["headerAddress"], 0, 'R');
    //2cm Line break
    $this->Line(10, 38, 210-10, 38); //1cm from each edge
    $this->Ln(5);
  }
  function MultiColCell($w, $h, $txt, $border=0, $align='J', $fill=false, $offset=0){
    $x = $this->GetX();
    $y = $this->GetY();
    $this->SetX($x + $offset);
    $this->MultiCell($w, $h, $txt, $border, $align, $fill);
    $maxY = $this->GetY();
    $this->SetXY($x + $w + $offset, $y);
        return $maxY;

  }
}

require "connection.php";
require "language.php";

//projectbookings
$sql="SELECT $projectTable.id AS projectID,
$companyTable.id AS companyID, $companyTable.name AS companyName,
$companyTable.logo, $companyTable.cmpDescription, $companyTable.uid, $companyTable.phone, $companyTable.mail, 
$companyTable.homepage, $companyTable.address, $companyTable.companyPostal, $companyTable.companyCity,
$clientTable.id AS clientID,
$clientTable.name AS clientName,
$projectTable.name AS projectName,
$projectBookingTable.*,
$projectBookingTable.id AS projectBookingID,
$logTable.timeToUTC,
$logTable.userID,
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
$filterQuery ORDER BY companyID ASC, $projectBookingTable.start ASC";
$result = $conn->query($sql);

$pdf = new PDF();
$pdf->AliasNbPages();
//narrow down page margins
$pdf->SetAutoPageBreak(1); //(true [margin-bot])
$pdf->SetLeftMargin(10);
$pdf->SetRightMargin(10);

$companyID = $sum = 0;
echo $conn->error;
while($result && ($row = $result->fetch_assoc())){
    if($companyID != $row['companyID']){        
        if(empty($row['logo']) || empty($row['address']) || empty($row['cmpDescription'])){
            die($lang['ERROR_MISSING_DATA']. "(Name, Logo, Adr.)");
        }
        $logo_path = dirname(__DIR__)."/images/ups/".str_replace(' ', '-',$row['companyName']).'.jpg';
        file_put_contents($logo_path, $row['logo']) or die("Unable to create file");
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if(finfo_file($finfo, $logo_path) == 'image/png'){
            $logo_path = 'images/ups/'.$row['companyName'].'.png';
            file_put_contents($logo_path, $row['logo']);
        }
        $created_logos[] = $logo_path;
        $pdf->glob['logo'] = $logo_path;
        $pdf->glob['headerAddress'] = iconv('UTF-8', 'windows-1252', $row['cmpDescription']."\n".$row['address']."\n".$row['companyPostal'].' '.$row['companyCity']."\n".$row['uid']."\n".$row['phone']."\n".$row['homepage']."\n".$row['mail']);
        
        $w = array(40, 20, 25, 10, 65, 30); //190

        if($companyID){ //copy below
            $pdf->Line(10, $pdf->GetY()+1, 200, $pdf->GetY()+1);
            $pdf->SetFont('Helvetica','B',10);
            $pdf->Cell($w[0]+$w[1]+$w[2],10,$lang['SUM']);
            $pdf->Cell(30,10, $sum .' '.$lang['MINUTES'] );
            $pdf->Cell(30,10, round($sum / 60, 2) .' '.$lang['HOURS'] );
        }

        $pdf->AddPage();
        $sum = 0;
        $companyID = $row['companyID'];
        $pdf->SetFont('Helvetica','B',16);
        $pdf->Cell(0, 10, 'Projektaufstellung', 0, 1, 'R');
        $pdf->SetFont('Helvetica','',10);

        $pdf->Ln(5);
        $pdf->SetFillColor(200,200,200);

        $pdf->Cell($w[0],7,$lang['CLIENT'].'/'.$lang['PROJECT'],0,0,'L',1);
        $pdf->Cell($w[1],7,$lang['DATE'], '', 0, 'C', 1);
        $pdf->Cell($w[2],7,$lang['TIME'], 0, 0, 'L', 1);
        $pdf->Cell($w[3],7, "Min." , '', 0, 'C', 1);
        $pdf->Cell($w[4],7,'Infotext', '', 0, 'C', 1);
        $pdf->Cell($w[5],7,$lang['EMPLOYEE'], '', 1, 'C', 1);
    }

    $A = strtotime($row['start']) + $row['timeToUTC']*3600;
    $B = strtotime($row['end']) + $row['timeToUTC']*3600;
    $y = array(0);
    $y[] = $pdf->MultiColCell($w[0],5,$row['clientName']."\n".$row['projectName']);
    $pdf->Cell($w[1],7,date('d.m.Y', $A), '', 0, 'C');
    $pdf->Cell($w[2],7,date('H:i', $A).' - '.date('H:i', $B) , 0, 0, 'L');
    $pdf->Cell($w[3],7,(($B - $A)/60));

    $sum += ($B - $A) / 60;
    $y[] = $pdf->MultiColCell($w[4],5,iconv('UTF-8', 'windows-1252', $row['infoText']));
    $pdf->MultiColCell($w[5], 5 ,iconv('UTF-8', 'windows-1252', $row['firstname'].' '.$row['lastname']), '', 'R');
    $pdf->Ln();
    $pdf->SetY(max($y));
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());

    if(280 - $pdf->GetY() < 10){ $pdf->AddPage(); } 
}

$pdf->Line(10, $pdf->GetY()+1, 200, $pdf->GetY()+1);
$pdf->SetFont('Helvetica','B',10);
$pdf->Cell($w[0]+$w[1]+$w[2],10,$lang['SUM']);
$pdf->Cell(30,10, $sum .' '.$lang['MINUTES'] );
$pdf->Cell(30,10, round($sum / 60, 2) .' '.$lang['HOURS'] );

/*
A4 = 210 x 297
Cell(wdith, height, text, border, ln(right, Ln, below), align, fill, link)
MultiCell(width, height, txt [, mixed border [, string align [, boolean fill]]])
MultiColCell(width, height, $txt, $border=0, $align='J', $fill=false, $offset=0, maintainYAxis){
Line(left margin, x, right margin, y)
*/

$pdf->Output(0, 'Overview.pdf');

array_map(unlink, $created_logos);

exit;
?>
