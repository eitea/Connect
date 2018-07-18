<?php
require dirname(__DIR__).DIRECTORY_SEPARATOR.'connection.php';
require dirname(__DIR__).DIRECTORY_SEPARATOR.'utilities.php';
require dirname(__DIR__).DIRECTORY_SEPARATOR.'language.php';
require dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'fpdf'.DIRECTORY_SEPARATOR.'fpdf.php';

$companyID = intval($_POST['downloadVVs']) or die();

session_start();
if (empty($_SESSION['userid'])) {
	die('Please <a href="../login/auth">login</a> first.');
}
$userID = $_SESSION['userid'];
$privateKey = $_SESSION['privateKey'];

class PDF extends FPDF {
	public $glob = array();
	function Header(){
		$this->Image($this->glob['logo'], 10, 10, 0, 27); //Image(string file [, float x [, float y [, float w [, float h [, string type [, mixed link]]]]]])
		$this->SetFont('Helvetica','',8);
		// Address
		$this->Cell(91,5);
		$this->MultiCell(100, 4, $this->glob["headerAddress"], 0, 'R');
		//2cm Line break
		$this->Line(10, 38, 210-10, 38);
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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pdf = new PDF();
$pdf->AliasNbPages();
//narrow down page margins
$pdf->SetAutoPageBreak(1); //(true [margin-bot])
$pdf->SetLeftMargin(10);
$pdf->SetRightMargin(10);

//header
$result = $conn->query("SELECT name, cmpDescription, logo, address, companyPostal, companyCity, uid, phone, homepage, mail FROM companyData WHERE id = $companyID"); echo $conn->error;
$row = $result->fetch_assoc();
$logo_path = dirname(dirname(__DIR__))."/images/ups/".str_replace(' ', '-',$row['name']).'.jpg';
file_put_contents($logo_path, $row['logo']) or die('Unable to create file');
chmod ( $logo_path , 0777 );
$finfo = finfo_open(FILEINFO_MIME_TYPE);
if(finfo_file($finfo, $logo_path) == 'image/png'){
	unlink($logo_path);
	$logo_path = 'images/ups/'.$row['name'].'.png';
	file_put_contents($logo_path, $row['logo']);
}
$pdf->glob['logo'] = $logo_path;
$pdf->glob['headerAddress'] = iconv('UTF-8','windows-1252',$row['cmpDescription']."\n".$row['address']."\n".$row['companyPostal'].' '.$row['companyCity']."\n".$row['uid']."\n".$row['phone']."\n".$row['homepage']."\n".$row['mail']);


$matrixID = 0;
$matrix_result = $conn->query("SELECT id FROM dsgvo_vv_data_matrix WHERE companyID = $companyID");
if($matrix_result){
	$matrixID = $matrix_result->fetch_assoc()["id"];
}

$result = $conn->query("SELECT t.type, v.name, v.id, v.templateID FROM dsgvo_vv_templates t INNER JOIN dsgvo_vv v ON v.templateID = t.id WHERE t.companyID = $companyID ");
while($result && ($vv_row = $result->fetch_assoc())){
	$pdf->AddPage();
	$pdf->SetFont('Helvetica','B',16);
	$pdf->Cell(0, 10, $vv_row['name'], 0, 1);
	if($vv_row['type'] == 'app'){
		$settings = getSettings($vv_row['id'], $vv_row['templateID'], 'DESCRIPTION');
		if(isset($settings['DESCRIPTION'])){
			$pdf->SetFont('Helvetica','B', 10);
			$pdf->Cell(0, 5, 'Kurze Beschreibung der Applikation, bzw. den Zweck dieser Applikation', 0, 1);
			$pdf->SetFont('Helvetica','',8);
			$pdf->MultiCell(0,5, iconv('UTF-8', 'windows-1252', $settings['DESCRIPTION']['setting']));
			$pdf->Ln(5);
		}
	}
	$settings = getSettings($vv_row['id'], $vv_row['templateID'], 'GEN_%');
	foreach($settings as $val){
		if(!$val['setting']) continue;
		$pdf->SetFont('Helvetica','B', 10);
		$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $val['descr']), 0, 1);
		$pdf->SetFont('Helvetica','',8);
		$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $val['setting']));
	}
	$pdf->Ln(5);
	$pdf->SetFont('Helvetica','B', 12);
	$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', 'Generelle organisatorische und technische Maßnahmen zum Schutz der personenbezogenen Daten'), 0, 1);
	$settings = getSettings($vv_row['id'], $vv_row['templateID'], 'GRET_TEXTAREA');
	if(isset($settings['GRET_TEXTAREA'])){
		$pdf->Ln(5);
		$pdf->SetFont('Helvetica','B', 10);
		$pdf->MultiCell(0, 5, 'Notizen', 0, 1);
		$pdf->SetFont('Helvetica','',8);
		$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $settings['GRET_TEXTAREA']['setting']));
		$pdf->Ln(5);
	}
	$settings = getSettings($vv_row['id'], $vv_row['templateID'], 'MULT_OPT_%');
    foreach($settings as $val){
		$arr = explode("|",$val['setting'],2);
		$radioValue = isset($arr[0])?$arr[0]:"";
		$textFieldValue = isset($arr[1])?$arr[1]:"";
		if(!$radioValue && !$textFieldValue) continue;
		$pdf->SetFont('Helvetica','B', 10);
		$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $val['descr']), 0, 1);
		$pdf->SetFont('Helvetica','',8);
		if($radioValue == 1){
			$radioValue = 'Erfüllt';
		} elseif($radioValue == 2){
			$radioValue = 'Nicht erfüllt';
		} elseif($radioValue == 3){
			$radioValue = 'N/A';
		}
		if($radioValue) $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', $radioValue),0 ,1);
		$pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', $textFieldValue),0 ,1);
	}
	$settings = getSettings($vv_row['id'], $vv_row['templateID'], 'EXTRA_%');
	if(isset($settings['EXTRA_DVR'])){
		$pdf->SetFont('Helvetica','B', 10);
		$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $settings['EXTRA_DVR']['descr']), 0, 1);
		$pdf->SetFont('Helvetica','',8);
		$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', 'DVR-Nummer: '.$settings['EXTRA_DVR']['setting']));
		if(isset($settings['EXTRA_DAN'])){
			$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', "\n".'DAN-Nummer: '.$settings['EXTRA_DAN']['setting']));
		}
	}
	if(!empty($settings['EXTRA_FOLGE'])){
		$pdf->SetFont('Helvetica','B', 10);
		$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $settings['EXTRA_FOLGE']['descr']), 0, 1);
		$pdf->SetFont('Helvetica','',8);
		if(isset($settings['EXTRA_FOLGE_CHOICE']) && intval($settings['EXTRA_FOLGE_CHOICE']['setting']) === 1){
			$pdf->Cell(0, 5, 'Ja', 0, 1);
			if($settings['EXTRA_FOLGE_DATE']['setting']) $pdf->Cell(0, 5, $settings['EXTRA_FOLGE_DATE']['setting'], 0, 1);
		} elseif(isset($settings['EXTRA_FOLGE_CHOICE']) && intval($settings['EXTRA_FOLGE_CHOICE']['setting']) === 0){
			$pdf->Cell(0, 5, 'Nein', 0, 1);
			if($settings['EXTRA_FOLGE_REASON']['setting']) $pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', $settings['EXTRA_FOLGE_REASON']['setting']), 0, 1);
		}
	}
	if(!empty($settings['EXTRA_DOC']) && isset($settings['EXTRA_DOC_CHOICE']['setting'])){
		$pdf->SetFont('Helvetica','B', 10);
		$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $settings['EXTRA_DOC']['descr']), 0, 1);
		$pdf->SetFont('Helvetica','',8);
		if(intval($settings['EXTRA_DOC_CHOICE']['setting']) === 1){
			$pdf->Cell(0, 5, 'Ja', 0, 1);
		} elseif(intval($settings['EXTRA_DOC_CHOICE']['setting']) === 0){
			$pdf->Cell(0, 5, 'Nein', 0, 1);
		}
		if($settings['EXTRA_DOC']['setting']){
			$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $settings['EXTRA_DOC']['setting']), 0, 1);
		}
	}
	if($matrixID && $vv_row['type'] == 'app'){
		$pdf->Ln(5);
		$pdf->SetFont('Helvetica','B', 12);
		$pdf->MultiCell(0, 8, iconv('UTF-8', 'windows-1252', 'Auflistung der verarbeiteten Datenfelder und deren Übermittlung'), 0, 1);
		$headers = array();
		$headings = getSettings($vv_row['id'], $vv_row['templateID'], 'APP_HEAD_%', true);
		$settings = getSettings($vv_row['id'], $vv_row['templateID'], 'APP_GROUP_%', false, $matrixID);
		$i = 1;
		foreach($settings as $key => $val){
			$pdf->SetFont('Helvetica','B', 10);
			$pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', $val['descr']), 0, 1);
			$pdf->SetFont('Helvetica','',8);
			$cats = getSettings($vv_row['id'], $vv_row['templateID'], 'APP_CAT_'.util_strip_prefix($key, 'APP_GROUP_').'_%', false, $matrixID);
			foreach($cats as $catKey => $catVal){
				$pdf->Cell(0, 5, iconv('UTF-8', 'windows-1252', $i++ .'. '. $catVal['descr']),0 , 1);
				$dur = $catVal['duration'] ? $catVal['duration'] : 'D';
				$pdf->Cell(10, 5);
				$pdf->Cell(20, 5, iconv('UTF-8', 'windows-1252', 'Löschfrist: '.$dur.' '.$lang['TIME_UNIT_TOSTRING'][$catVal['duration_unit']]));
				$pdf->Cell(10, 5);
				$description_line = '';
				foreach($headings as $headVal){
					if($headVal['setting'][0]){
						$j = array_search($catKey, $headVal['category']); //$j = numeric index
						if($j && $headVal['setting'][$j]){
							$description_line .= $headVal['setting'][0].'; ';
						}
					}
				}
				$pdf->MultiCell(0, 5, iconv('UTF-8', 'windows-1252', $description_line));
				//$pdf->Ln();
			}
			$pdf->Ln();
		}

	}



}

$pdf->Output(0, 'Verfahrensverzeichnisse.pdf');

unlink($logo_path);

/*
A4 = 210 x 297
Cell(wdith, height, text, border('LTRB'), ln=0(right[0], Ln[1], below[2]), align=L, fill, link)
MultiCell(width, height, txt [, mixed border [, string align [, boolean fill]]])
MultiColCell(width, height, $txt, $border=0, $align='J', $fill=false, $offset=0, maintainYAxis){
Line(left margin, x, right margin, y)
*/

function getSettings($vvID, $templateID, $like, $mults = false, $matrixID = false){
	global $conn;
	global $userID;
	global $privateKey;
	if($matrixID){ // from matrix, returned id references a tuple in dsgvo_vv_data_matrix_settings
		$result = $conn->query("SELECT setting, opt_name, opt_descr, category, opt_status, ms.opt_duration, ms.opt_unit, ms.id, vs.id AS valID, vs.clientID AS client
		FROM dsgvo_vv_data_matrix_settings ms LEFT JOIN dsgvo_vv_settings vs ON vs.matrix_setting_id = ms.id AND vv_ID = $vvID
		WHERE opt_name LIKE '$like' AND ms.matrixID = $matrixID ORDER BY vs.setting_id, ms.id");
	}else{ // from template
		$result = $conn->query("SELECT setting, opt_name, opt_descr, opt_status, category, ts.id, vs.id AS valID, vs.clientID AS client, vs.setting_id
		FROM dsgvo_vv_template_settings ts LEFT JOIN dsgvo_vv_settings vs ON setting_id = ts.id AND vv_ID = $vvID
		WHERE opt_name LIKE '$like' AND templateID = $templateID ORDER BY vs.setting_id, vs.id");
	}
	echo $conn->error;
	$settings = array();
	while($row = $result->fetch_assoc()){
		$settings[$row['opt_name']]['descr'] = $row['opt_descr'];
		if($mults){
			$settings[$row['opt_name']]['setting'][] = secure_data('DSGVO', $row['setting'], 'decrypt', $userID, $privateKey);
			$settings[$row['opt_name']]['category'][] = $row['category'];
			$settings[$row['opt_name']]['client'][] = $row['client'];
			$settings[$row['opt_name']]['status'][] = $row['opt_status'];
			if($matrixID){
				$settings[$row['opt_name']]['duration'][] = $row['opt_duration'];
				$settings[$row['opt_name']]['duration_unit'][] = $row['opt_unit'];
			}
		} else {
			$settings[$row['opt_name']]['setting'] = secure_data('DSGVO', $row['setting'], 'decrypt', $userID, $privateKey);
			$settings[$row['opt_name']]['status'] = $row['opt_status'];
			if($matrixID){
				$settings[$row['opt_name']]['duration'] = $row['opt_duration'];
				$settings[$row['opt_name']]['duration_unit'] = $row['opt_unit'];
			}
		}
	}
	return $settings;
}

exit;
