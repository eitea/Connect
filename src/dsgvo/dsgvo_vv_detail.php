<?php include dirname(__DIR__) . '/header.php'; enableToDSGVO($userID);?>
<?php
if(empty($_GET['v'])){
    echo "Invalid Access.";
    include dirname(__DIR__) . '/footer.php';
    die();
}
$vvID = intval($_GET['v']);

$result = $conn->query("SELECT dsgvo_vv.name, companyID, dsgvo_vv_templates.type, dsgvo_vv_templates.id AS templateID
FROM dsgvo_vv, dsgvo_vv_templates WHERE dsgvo_vv.id = $vvID AND dsgvo_vv_templates.id = templateID");
if(!$result || $result->num_rows < 1 || !($vv_row = $result->fetch_assoc()) || !in_array($vv_row['companyID'], $available_companies)){
    echo $conn->error;
    echo "<br>Invalid Access.";
    include dirname(__DIR__) . '/footer.php';
    die();
}
$templateID = $vv_row['templateID'];
$doc_type = 'BASE';
if($vv_row['type'] == 'app'){ $doc_type = 'APP'; }
$company = $vv_row['companyID'];
$matrix_result = $conn->query("SELECT id FROM dsgvo_vv_data_matrix WHERE companyID = $company");
if($matrix_result){
    if($matrix_result->num_rows === 0){
        showError("Diese Firma hat keine Matrix in den Einstellungen. Zum Erstellen <a href='data-matrix'>hier klicken</a>.");
    }
    $matrixID = $matrix_result->fetch_assoc()["id"];
} else {
    showError($conn->error);
}

$stmt_insert_vv_log = $conn->prepare("INSERT INTO dsgvo_vv_logs (user_id,short_description,long_description,scope) VALUES ($userID,?,?,?)");
showError($conn->error);
$last_encryption_error = "";
$stmt_insert_vv_log->bind_param("sss", $stmt_insert_vv_log_short_description, $stmt_insert_vv_log_long_description, $stmt_insert_vv_log_scope);
function insertVVLog($short,$long){
    global $stmt_insert_vv_log;
    global $stmt_insert_vv_log_short_description;
    global $stmt_insert_vv_log_long_description;
    global $stmt_insert_vv_log_scope;
    global $userID;
    global $privateKey;
    global $last_encryption_error;
    $stmt_insert_vv_log_short_description = secure_data('DSGVO', $short, 'encrypt', $userID, $privateKey);
    $stmt_insert_vv_log_long_description = secure_data('DSGVO', $long, 'encrypt', $userID, $privateKey, $encryptionError);
    $stmt_insert_vv_log_scope = secure_data('DSGVO', "VV", 'encrypt', $userID, $privateKey, $encryptionError);
    if($encryptionError){
        $last_encryption_error = showError($encryptionError, true); // only show last error because consecutive errors are usually the same
    }
    $stmt_insert_vv_log->execute();
    showError($stmt_insert_vv_log->error);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $stmt_update_setting = $conn->prepare("UPDATE dsgvo_vv_settings SET setting = ? WHERE id = ?");
    $stmt_update_setting->bind_param("si", $setting_encrypt, $valID);
    $stmt_insert_setting = $conn->prepare("INSERT INTO dsgvo_vv_settings(vv_id, setting_id, setting, category) VALUES($vvID, ?, ?, ?)");
    $stmt_insert_setting->bind_param("iss", $setID, $setting_encrypt, $cat);

    $stmt_insert_setting_client = $conn->prepare("INSERT INTO dsgvo_vv_settings(vv_id, setting_id, setting, category, clientID) VALUES($vvID, ?, ?, ?, ?)");
    $stmt_insert_setting_client->bind_param("issi", $setID, $setting_encrypt, $cat, $clientID);

    $stmt_insert_setting_matrix = $conn->prepare("INSERT INTO dsgvo_vv_settings(vv_id, matrix_setting_id, setting, category) VALUES($vvID, ?, ?, ?)");
    $stmt_insert_setting_matrix->bind_param("iss", $setID, $setting_encrypt, $cat);

	if(!empty($_POST['add-new-file']) && isset($_FILES['new-file-upload'])){
		require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'aws'.DIRECTORY_SEPARATOR.'autoload.php';
		$bucket = $identifier .'-uploads'; //no uppercase, no underscores, no ending dashes, no adjacent special chars

		$result = $conn->query("SELECT endpoint, awskey, secret FROM archiveconfig WHERE isActive = 'TRUE' LIMIT 1");
		if($result && ($row = $result->fetch_assoc())){
			try{
				$s3 = new Aws\S3\S3Client(array(
					'version' => 'latest',
					'region' => '',
					'endpoint' => $row['endpoint'],
					'use_path_style_endpoint' => true,
					'credentials' => array('key' => $row['awskey'], 'secret' => $row['secret'])
				));
			} catch(Exception $e){
				echo $e->getMessage();
			}
		} else {
			showError("Keine S3 Schnittstelle gefunden ".$conn->error);
		}

		//decrypt the symmetric key
		$result = $conn->query("SELECT privateKey, s.publicKey AS publicKey, s.symmetricKey FROM security_access a
			LEFT JOIN security_modules s ON (s.module = a.module AND s.outDated = 'FALSE') WHERE a.module = 'DSGVO' AND a.userID = $userID AND a.outDated = 'FALSE' LIMIT 1");
		if($result && ($row = $result->fetch_assoc()) && $row['publicKey'] && $row['privateKey']){
			$keypair = base64_decode($privateKey).base64_decode($row['publicKey']);
			$cipher = base64_decode($row['privateKey']);
			$nonce = mb_substr($cipher, 0, 24, '8bit');
			$encrypted = mb_substr($cipher, 24, null, '8bit');
			try {
				$project_private = sodium_crypto_box_open($encrypted, $nonce, $keypair);
				$cipher_symmetric = base64_decode($row['symmetricKey']);
				$nonce = mb_substr($cipher_symmetric, 0, 24, '8bit');
				$dsgvo_symmetric = sodium_crypto_box_open(mb_substr($cipher_symmetric, 24, null, '8bit'), $nonce, $project_private.base64_decode($row['publicKey']));
			} catch(Exception $e){
				echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
			}
		} else {
			showError($conn->error." - No Access");
		}

		if($dsgvo_symmetric){
			$file_info = pathinfo($_FILES['new-file-upload']['name']);
			$ext = strtolower($file_info['extension']);
			$filetype = $_FILES['new-file-upload']['type'];
			$accepted_types = ['application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'text/plain', 'application/pdf', 'application/zip',
			'application/x-zip-compressed', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'multipart/x-zip',
			'application/x-compressed', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
			if (!in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'txt', 'zip'])){
				showError('Ungültige Dateiendung: '.$ext);
			} elseif(!in_array($filetype, $accepted_types)) {
				showError('Ungültiger Dateityp: '.$filetype);
			} elseif ($_FILES['new-file-upload']['size'] > 15000000) { //15mb max
				showError('Die maximale Dateigröße wurde überschritten (15 MB)');
			} elseif(empty($s3)) {
				showError("Es konnte keine S3 Verbindung hergestellt werden. Stellen Sie sicher, dass unter den Archiv Optionen eine gültige Verbindung gespeichert wurde.");
			} else {
				$parent = test_input($_POST['add-new-file']);
				try{
					$hashkey = uniqid('', true); //23 chars
					if(!$s3->doesBucketExist($bucket)){
						$result = $s3->createBucket(['Bucket' => $bucket]);
						if($result) showSuccess("Bucket $bucket Created");
					}
					$file_encrypt = simple_encryption(file_get_contents($_FILES['new-file-upload']['tmp_name']), $dsgvo_symmetric);
					//$_FILES['file']['name']
					$s3->putObject(array(
						'Bucket' => $bucket,
						'Key' => $hashkey,
						'Body' => $file_encrypt
					));

					$filename = test_input($file_info['filename']);
					$conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uniqID, uploadUser)
					VALUES ('DSGVO', '$vvID', '$filename', '$parent', '$ext', '$hashkey', $userID)");
					if($conn->error){ showError($conn->error); } else { showSuccess($lang['OK_UPLOAD']); }
				} catch(Exception $e){
					echo $e->getTraceAsString();
					echo '<br><hr><br>';
					echo $e->getMessage();
				}
			}
		} else {
			showError("could not decrypt symmetric key: Access denied");
		}
	} elseif(!empty($_POST['add-new-file'])){
		showError('No File Selected '.$_FILES['new-file-upload']['error']);
	} elseif(!empty($_POST['add-new-folder'])){
        $parent = test_input($_POST['add-new-folder']);
        if(!empty($_POST['new-folder-name'])){
            $name = test_input($_POST['new-folder-name']);
            $conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uploadUser) VALUES ('DSGVO', '$vvID', '$name', '$parent', 'folder', $userID)");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_ADD']);
            }
        } else {
            showError($lang['ERROR_MISSING_FIELDS']);
        }
    }elseif(!empty($_POST['delete-folder'])){
		$x = test_input($_POST['delete-folder']);
		$conn->query("DELETE FROM archive WHERE id = '$x' AND type = 'folder'");
		if($conn->error){ showError($conn->error); } else { showSuccess($lang['OK_DELETE']); }
	}
}
function getSettings($like, $mults = false, $from_matrix = false){
    global $conn;
    global $vvID;
    global $userID;
    global $privateKey;
    if($from_matrix){ // from matrix, returned id references a tuple in dsgvo_vv_data_matrix_settings
        global $matrixID;
        $result = $conn->query("SELECT setting, opt_name, opt_descr, category, opt_status, ms.opt_duration, ms.opt_unit, ms.id, vs.id AS valID, vs.clientID AS client
        FROM dsgvo_vv_data_matrix_settings ms LEFT JOIN dsgvo_vv_settings vs ON vs.matrix_setting_id = ms.id AND vv_ID = $vvID
		WHERE opt_name LIKE '$like' AND ms.matrixID = $matrixID ORDER BY vs.setting_id, ms.id");
    }else{ // from template
		global $templateID;
        $result = $conn->query("SELECT setting, opt_name, opt_descr, opt_status, category, ts.id, vs.id AS valID, vs.clientID AS client, vs.setting_id
        FROM dsgvo_vv_template_settings ts LEFT JOIN dsgvo_vv_settings vs ON setting_id = ts.id AND vv_ID = $vvID
		WHERE opt_name LIKE '$like' AND templateID = $templateID ORDER BY vs.setting_id, vs.id");
    }
    showError($conn->error);
    $settings = array();
    while($row = $result->fetch_assoc()){
        $settings[$row['opt_name']]['descr'] = $row['opt_descr'];
        $settings[$row['opt_name']]['id'] = $row['id'];
        if($mults){
            $settings[$row['opt_name']]['setting'][] = secure_data('DSGVO', $row['setting'], 'decrypt', $userID, $privateKey);
            $settings[$row['opt_name']]['valID'][] = $row['valID'];
            $settings[$row['opt_name']]['category'][] = $row['category'];
            $settings[$row['opt_name']]['client'][] = $row['client'];
            $settings[$row['opt_name']]['setting_id'][] = $row['setting_id'];
			$settings[$row['opt_name']]['status'][] = $row['opt_status'];
			if($from_matrix){
				$settings[$row['opt_name']]['duration'][] = $row['opt_duration'];
				$settings[$row['opt_name']]['duration_unit'][] = $row['opt_unit'];
			}
        } else {
            $settings[$row['opt_name']]['setting'] = secure_data('DSGVO', $row['setting'], 'decrypt', $userID, $privateKey);
            $settings[$row['opt_name']]['valID'] = $row['valID'];
			$settings[$row['opt_name']]['status'] = $row['opt_status'];
			if($from_matrix){
				$settings[$row['opt_name']]['duration'] = $row['opt_duration'];
				$settings[$row['opt_name']]['duration_unit'] = $row['opt_unit'];
			}
        }
    }
    return $settings;
}
?>
<div class="page-header-fixed">
	<div class="page-header"><h3><?php echo $vv_row['name'].' '.$lang['PROCEDURE_DIRECTORY']; ?>
		<div class="page-header-button-group"><button type="submit" form="vv-mainForm" class="btn btn-default blinking"><i class="fa fa-floppy-o"></i></button></div>
	</h3></div>
</div>
<form id="vv-mainForm" method="POST">
	<div class="page-content-fixed-100">
	<?php
	$settings = getSettings('DESCRIPTION');
	if(isset($settings['DESCRIPTION'])):
	    if(isset($_POST['DESCRIPTION'])){
	        $setting = strip_tags($_POST['DESCRIPTION']);
	        $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	        $valID = $settings['DESCRIPTION']['valID'];
	        if($valID){
	            $stmt_update_setting->execute();
	            if($stmt_update_setting->affected_rows > 0){
	                $escaped_setting = test_input($setting);
	                insertVVLog("UPDATE","Update description for Procedure Directory $vvID to '$escaped_setting'");
	            }
	        } else {
	            $setID = $settings['DESCRIPTION']['id'];
	            $stmt_insert_setting->execute();
	            $escaped_setting = test_input($setting);
	            insertVVLog("INSERT","Insert description for Procedure Directory $vvID as '$escaped_setting'");
	        }
	        if($conn->error){
	            showError($conn->error);
	        } else {
	            $settings['DESCRIPTION']['setting'] = $setting;
	            showSuccess($lang['OK_SAVE']);
	        }
	    }
	?>
	    <div class="col-md-6">
	        <div class="panel panel-default">
	            <?php
	            if($doc_type == 'BASE'):
	            $result = $conn->query("SELECT name, address, phone, mail, companyPostal, companyCity FROM companyData WHERE id = ".$vv_row['companyID']);
	            $row = $result->fetch_assoc();
	            ?>
	            <div class="panel-heading">Firmendaten</div>
	            <div class="panel-body">
	                <div class="col-sm-6 bold">Name der Firma</div><div class="col-sm-6 grey"><?php echo $row['name']; ?><br></div>
	                <div class="col-sm-6 bold">Straße</div><div class="col-sm-6 grey"><?php echo $row['address']; ?><br></div>
	                <div class="col-sm-6 bold">Ort</div><div class="col-sm-6 grey"><?php echo $row['companyCity']; ?><br></div>
	                <div class="col-sm-6 bold">PLZ</div><div class="col-sm-6 grey"><?php echo $row['companyPostal']; ?><br></div>
	                <div class="col-sm-6 bold">Telefon</div><div class="col-sm-6 grey"><?php echo $row['phone']; ?><br></div>
	                <div class="col-sm-6 bold">E-Mail</div><div class="col-sm-6 grey"><?php echo $row['mail']; ?><br></div>
	            </div>
	            <?php else: ?>
	            <div class="panel-heading"><?php echo mc_status('DSGVO'); ?>Kurze Beschreibung des Vorgangs, bzw. den Zweck dieses Vorgangs</div>
	            <div class="panel-body">
	                <textarea name="DESCRIPTION" style='resize:none' class="form-control" rows="5"><?php echo $settings['DESCRIPTION']['setting']; ?></textarea>
	            </div>
	            <?php endif; ?>
	        </div>
	    </div>
	<?php endif; ?>

	<div class="col-md-12">
	    <div class="panel panel-default">
	        <div class="panel-body">
	        <?php
	        $settings = getSettings('GEN_%');
	        foreach($settings as $key => $val){
	            if(isset($_POST[$key])){
	                $val['setting'] = $setting = strip_tags($_POST[$key]);
	                $valID = $val['valID'];
	                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	                if($valID){
	                    $stmt_update_setting->execute();
	                    if($stmt_update_setting->affected_rows > 0){
	                        $escaped_setting = test_input($setting);
	                        insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
	                    }
	                } else {
	                    $setID = $val['id'];
	                    $stmt_insert_setting->execute();
	                    $escaped_setting = test_input($setting);
	                    insertVVLog("INSERT","Insert '$key' for Procedure Directory $vvID as '$escaped_setting'");
	                }
	            }
	            echo '<div class="row">';
	            echo '<div class="col-sm-6 bold">'.mc_status('DSGVO').$val['descr'].'</div>';
	            echo '<div class="col-sm-6 grey"><input type="text" class="form-control" maxlength="700" name="'.$key.'" value="'.$val['setting'].'"/></div>';
	            echo '</div>';
	        }
	        ?>
	        </div>
	    </div>
	</div>
	<div class="col-md-12">
	    <div class="panel panel-default">
	        <div class="panel-heading">Generelle organisatorische und technische Maßnahmen zum Schutz der personenbezogenen Daten</div>
			<?php
			// $stmt_update_setting = $conn->prepare("UPDATE dsgvo_vv_settings SET setting = ? WHERE id = ?");
		    // $stmt_update_setting->bind_param("si", $setting_encrypt, $valID);
		    // $stmt_insert_setting = $conn->prepare("INSERT INTO dsgvo_vv_settings(vv_id, setting_id, setting, category) VALUES($vvID, ?, ?, ?)");
		    // $stmt_insert_setting->bind_param("iss", $setID, $setting_encrypt, $cat);

			$key = 'GRET_TEXTAREA';
	        $settings = getSettings($key);
			//var_dump($settings);
			if(isset($settings[$key])){
				echo '<br><p class="text-center"><label for="#matrice-area">'.mc_status('DSGVO').'Notizen</label></p>';
				if(isset($_POST[$key])){
					$settings[$key]['setting'] = $setting = strip_tags($_POST[$key]);
					$setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
					$valID = $settings[$key]['valID'];
					if($valID){
						$stmt_update_setting->execute();
						if($stmt_update_setting->affected_rows > 0){
							$escaped_setting = test_input($setting);
							insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
						}
					} else {
						$setID = $settings[$key]['id'];
						$stmt_insert_setting->execute();
						$escaped_setting = test_input($setting);
						insertVVLog("INSERT","Insert '$key' for Procedure Directory $vvID as '$escaped_setting'");
					}
				}

				echo '<textarea id="matrice-area" name="'.$key.'" >'.$settings[$key]['setting'].'</textarea>';
			}

			?>

			<br>
	        <div class="panel-body">
	            <table class="table table-borderless">
	                <thead>
	                    <tr>
	                        <th></th><!-- number -->
	                        <th></th><!-- name -->
	                        <th style="width:38%"></th>
	                    </tr>
	                </thead>
	                <tbody>
	                    <?php
	                    $settings = getSettings('MULT_OPT_%');
	                    foreach($settings as $key => $val){
	                        // numbers for checked radio and text field are saved together, separated by |
	                        $textFieldKey = "${key}_TEXTFIELD";
	                        if(isset($_POST[$key]) || isset($_POST[$textFieldKey])){
	                            $val['setting'] = $setting = (isset($_POST[$key])?intval($_POST[$key]):"")."|".(isset($_POST[$textFieldKey])?test_input($_POST[$textFieldKey]):"");
	                            $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	                            $valID = $val['valID'];
	                            if($valID){
	                                $stmt_update_setting->execute();
	                                if($stmt_update_setting->affected_rows > 0){
	                                    $escaped_setting = test_input($setting);
	                                    insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
	                                }
	                            } else {
	                                $setID = $val['id'];
	                                $stmt_insert_setting->execute();
	                                $escaped_setting = test_input($setting);
	                                insertVVLog("INSERT","Insert '$key' for Procedure Directory $vvID as '$escaped_setting'");
	                            }
	                        }
	                        $arr = explode("|",$val['setting'],2);
	                        $radioValue = isset($arr[0])?$arr[0]:"";
	                        $textFieldValue = isset($arr[1])?$arr[1]:"";
	                        $number = sprintf("A%03d",intval(util_strip_prefix($key,"MULT_OPT_")));
	                        echo '<tr>';
	                        echo '<td class="text-muted">'.$number.'</td>';
	                        echo '<td class="bold">'.$val['descr'].'</td>';
							echo '<td>';
							echo '';
							$checked = $radioValue == 1 ? 'checked' : '';
							echo '<div class="col-sm-4"><input type="radio" '.$checked.' name="'.$key.'" value="1" /> Erfüllt</div>';
							$checked = $radioValue == 2 ? 'checked' : '';
							echo '<div class="col-sm-4 text-center"><input type="radio" '.$checked.' name="'.$key.'" value="2" /> Nicht erfüllt</div>';
							$checked = $radioValue == 3 ? 'checked' : '';
							echo '<div class="col-sm-4 text-right"><input type="radio" '.$checked.' name="'.$key.'" value="3" /> N/A</div>';
							echo '<div class="row"><textarea style="font-size:12px;resize:none" rows="3" type="text" class="form-control" name="'.$textFieldKey.'"placeholder="Notizen...">'.$textFieldValue.'</textarea></div>';
							echo '</td>';
	                        echo '</tr>';
	                    }
	                    ?>
	                </tbody>
	            </table>
	        </div>
	    </div>
	</div>
	<?php
	$settings = getSettings('EXTRA_%');

	function update_or_insert_extra (&$settings, $name){
	    global $userID;
	    global $privateKey;
	    global $stmt_update_setting;
	    global $stmt_insert_setting;
	    global $valID;
	    global $setting_encrypt;
	    global $setID;
	    global $vvID;
	    if(isset($_POST[$name])){
	        $settings[$name]['setting'] = $setting = strip_tags($_POST[$name]);
	        $valID = $settings[$name]['valID'];
	        $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	        if($valID){
	            $stmt_update_setting->execute();
	            if($stmt_update_setting->affected_rows > 0){
	                $escaped_setting = test_input($setting);
	                insertVVLog("UPDATE","Update '$name' for Procedure Directory $vvID to '$escaped_setting'");
	            }
	        }else{
	            $setID = $settings[$name]['id'];
	            $stmt_insert_setting->execute();
	            $escaped_setting = test_input($setting);
	            insertVVLog("INSERT","Insert '$name' for Procedure Directory $vvID as '$escaped_setting'");
	        }
	    }
	}

	if(isset($settings['EXTRA_DVR'])){
	    update_or_insert_extra($settings, "EXTRA_DVR");
	    update_or_insert_extra($settings, "EXTRA_DAN");
	    echo '<div class="col-md-7">';
	    echo '<div class="panel panel-default">';
	    echo '<div class="panel-heading">'.mc_status('DSGVO').$settings['EXTRA_DVR']['descr'].'</div>';
	    echo '<div class="row"><div class="col-sm-12"><label>DVR-Nummer</label><input type="text" name="EXTRA_DVR" value="'.$settings['EXTRA_DVR']['setting'].'" class="form-control"></div></div>';
	    echo '<div class="row"><div class="col-sm-12"><label>DAN-Nummer</label><input type="text" name="EXTRA_DAN" value="'.$settings['EXTRA_DAN']['setting'].'" class="form-control"></div></div>';
	    echo '</div></div>';
	}
	if(isset($settings['EXTRA_FOLGE'])){
	    update_or_insert_extra($settings, "EXTRA_FOLGE_CHOICE");
	    update_or_insert_extra($settings, "EXTRA_FOLGE_DATE");
	    update_or_insert_extra($settings, "EXTRA_FOLGE_REASON");
	    echo '<div class="col-md-7">';
	    echo '<div class="panel panel-default">';
	    echo '<div class="panel-heading">'.mc_status('DSGVO').$settings['EXTRA_FOLGE']['descr'].'</div>';
	    echo '<div class="row"><div class="col-sm-2"><input type="radio" name="EXTRA_FOLGE_CHOICE" value="1" '.(intval($settings['EXTRA_FOLGE_CHOICE']['setting']) === 1?"checked":"").'>Ja</div><div class="col-sm-2"><input type="radio" name="EXTRA_FOLGE_CHOICE" value="0" '.(intval($settings['EXTRA_FOLGE_CHOICE']['setting']) === 0?"checked":"").'>Nein</div></div>';
	    echo '<div class="row"><div class="col-sm-12 bold">Wenn Ja, wann?<input type="text" name="EXTRA_FOLGE_DATE" class="form-control datepicker" value="'.$settings['EXTRA_FOLGE_DATE']['setting'].'"></div></div>';
	    echo '<div class="row"><div class="col-sm-12 bold">Wenn Nein, warum?<input type="text" name="EXTRA_FOLGE_REASON" class="form-control" value="'.$settings['EXTRA_FOLGE_REASON']['setting'].'"></div></div>';
	    echo '</div></div>';
	}
	if(isset($settings['EXTRA_DOC'])){
	    update_or_insert_extra($settings, "EXTRA_DOC_CHOICE");
	    update_or_insert_extra($settings, "EXTRA_DOC");
	    echo '<div class="col-md-7"><div class="panel panel-default">';
	    echo '<div class="panel-heading">'.mc_status('DSGVO').$settings['EXTRA_DOC']['descr'].'</div>';
	    echo '<div class="row"><div class="col-sm-2"><input type="radio" name="EXTRA_DOC_CHOICE" value="1" '.(intval($settings['EXTRA_DOC_CHOICE']['setting']) === 1?"checked":"").'>Ja</div><div class="col-sm-2"><input type="radio" name="EXTRA_DOC_CHOICE" value="0" '.(intval($settings['EXTRA_DOC_CHOICE']['setting']) === 0?"checked":"").'>Nein</div></div>';
	    echo '<div class="row"><div class="col-sm-6 bold">Wo befindet sich diese?</div><div class="col-sm-6"><input type="text" name="EXTRA_DOC" class="form-control" value="'.$settings['EXTRA_DOC']['setting'].'"></div></div>';
	    echo '</div></div><br>';
	}

	$upload_viewer = [
	'accessKey' => 'DSGVO',
	'category' => 'DSGVO',
	'categoryID' => $vvID
	];
	include dirname(__DIR__).DIRECTORY_SEPARATOR.'misc'.DIRECTORY_SEPARATOR.'upload_viewer.php';
	?>

	<?php if($doc_type == 'APP'): ?>
	<div class="col-md-12">
	    <div class="panel panel-default">
	        <div class="panel-heading">Auflistung der verarbeiteten Datenfelder und deren Übermittlung</div>
	        <div class="panel-body" style="overflow-x: auto;">
	            <?php
	            $str_heads = $space = $space_key = '';
	            $heading = getSettings('APP_HEAD_%', true);
	            foreach($heading as $key => $val){
	                if(!empty($_POST['delete_cat']) && $val['valID'][0] == $_POST['delete_cat']){
	                    $id = $val['setting_id'][0];
	                    $conn->query("DELETE FROM dsgvo_vv_settings WHERE vv_id = $vvID AND setting_id = $id");
	                    if($conn->error){
	                        showError($conn->error);
	                    }else{
	                        unset($heading[$key]);
	                        showSuccess($lang["OK_DELETE"]);
	                        continue;
	                    }
	                }
	                if($val['setting'][0]){
						$tooltip = isset($lang['DSGVO_CATEGORY_TOSTRING'][$val['category'][0]]) ? $lang['DSGVO_CATEGORY_TOSTRING'][$val['category'][0]] : '';

	                    $client = "";
	                    if($val['client'] && $val['client'][0]){
	                        $clientID = $val['client'][0];
	                        $result = $conn->query("SELECT name FROM clientData WHERE id = $clientID");
	                        if($result && $result->num_rows > 0){
	                            $client = ' (an '.$result->fetch_assoc()["name"].') ';
	                        }
	                    }

	                    $str_heads .= '<th data-toggle="tooltip" data-container="body" data-placement="left" title="'.$tooltip.$client.'"><div class="btn-group">
						<button style="white-space: normal;" type="button" class="btn btn-link" data-toggle="dropdown">'.$val['setting'][0].'</button> <ul class="dropdown-menu">
						<li><button type="button" class="btn btn-link" data-toggle="modal" data-target="#add-cate"
						data-valid="'.$val['valID'][0].'" data-setting="'.$val['setting'][0].'" data-client="'.$val['client'][0].'" data-cat="'.$val['category'][0].'">Bearbeiten</button></li>
						<li><button type="submit" class="btn btn-link" name="delete_cat" value="'.$val['valID'][0].'">Löschen</button></li>
						</ul></div></th>';
	                } else {
	                    $space_key = !$space ? $key : $space_key;
	                    $space = !$space ? $val['id'] : $space;
	                    unset($heading[$key]);
	                }
	            }
	            // no other sane choice for the backend to be but here
	            if(isset($_POST['add_category']) && !empty($_POST['add_category_name'])){
	                $setting = test_input($_POST['add_category_name']);
	                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	                $cat = test_input($_POST['add_category_mittlung']);
	                $clientID = (isset($_POST['add_category_client']) && intval($_POST['add_category_client'])) ? intval($_POST['add_category_client']) : false;
	                if($clientID){
	                    $stmt = $stmt_insert_setting_client;
	                }else{
	                    $stmt = $stmt_insert_setting;
	                }
					if(!empty($_POST['add_category'])){
						$valID = test_input($_POST['add_category']);
						if($clientID){
							$conn->query("UPDATE dsgvo_vv_settings SET setting = '$setting_encrypt', category = '$cat', clientID = $clientID WHERE id = $valID ");
						} else {
							$conn->query("UPDATE dsgvo_vv_settings SET setting = '$setting_encrypt', category = '$cat' WHERE id = $valID ");
						}
						if($conn->error){
		                    showError($conn->error);
		                } else {
							redirect('');
						}
					} elseif($space) {
						$setID = $space;
						$stmt->execute();
		                $escaped_setting = test_input($setting);
		                insertVVLog("INSERT","Add new category '$escaped_setting' for Procedure Directory $vvID");
						if($stmt->error){
		                    showError($stmt->error);
		                } else {
							redirect('');
						}
					} else {
						showWarning("Kein Platz mehr");
					}
	            }
	            ?>

	            <?php  if($matrixID): ?>
	            <table class="table table-condensed">
	            <thead><tr>
	            <th style="width:15%">Gruppierung</th>
	            <th>Nr.</th>
	            <th style="width:15%">Datenkategorien der gesammelten personenbezogenen Daten</th>
				<th>Löschfrist</th>
	            <?php echo $str_heads; ?>
	            <th><a data-toggle="modal" data-target="#add-cate" title="Neue Kategorie..." class="btn btn-warning" ><i class="fa fa-plus"></i></a></th>
	            </tr></thead>
	            <?php
					$cat_colors = array( 'heading1' =>  '#84b4d6', 'heading2' => '#99c34c', 'heading3' => '#ffd400', 'heading4' => '#ff86b3'); //blue green yellow pink
	                $settings = getSettings('APP_GROUP_%', false, true); // settings from matrix
	                $fieldID = 0;
	                foreach($settings as $key => $val){
	                    $i = 1;
	                    $cats = getSettings('APP_CAT_'.util_strip_prefix($key, 'APP_GROUP_').'_%', false, true);
	                    echo '<tr><td rowspan="'.(count($cats) +1).'" >'.$val['descr'].'</td></tr>';
	                    foreach($cats as $catKey => $catVal){
	                        if($_SERVER['REQUEST_METHOD'] == 'POST'){
	                            $valID = $catVal['valID'];
	                            if(!empty($_POST[$catKey])){
	                                $catVal['setting'] = $setting = '1';
	                                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	                                if($valID){ //update to true if checked and exists
	                                    $stmt_update_setting->execute();
	                                    if($stmt_update_setting->affected_rows > 0){
	                                        $escaped_setting = test_input($setting);
	                                        insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
	                                    }
	                                } else { //insert with true if checked and not exists
	                                    $cat = '';
	                                    $setID = $catVal['id'];
	                                    $stmt_insert_setting_matrix->execute();
	                                    $escaped_setting = test_input($setting);
	                                    insertVVLog("INSERT","Insert '$key' for Procedure Directory $vvID as '$escaped_setting'");
	                                }
	                            } elseif($valID && $catVal['setting']) { //set to false only if not checked, exists and saved as true (anything else is false anyways)
	                                $catVal['setting'] = $setting = '0';
	                                $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	                                $stmt_update_setting->execute();
	                                if($stmt_update_setting->affected_rows > 0){
	                                    $escaped_setting = test_input($setting);
	                                    insertVVLog("UPDATE","Update '$key' for Procedure Directory $vvID to '$escaped_setting'");
	                                }
	                            }
	                        }
	                        $fieldID ++;
							$isArt9 = $catVal['status'] == 'ART9' ? 'style="color:red" title="Besondere Datenkategorie iSd Art 9 DSGVO"' : '';

	                        echo '<tr>';
	                        echo '<td>'.$fieldID.'</td>';
	                        echo "<td $isArt9 >".$catVal['descr'].'</td>';
							$dur = $catVal['duration'] ? $catVal['duration'] : '<label title="Default">D</label>';
							echo '<td>'.$dur.' '.$lang['TIME_UNIT_TOSTRING'][$catVal['duration_unit']].'</td>';

	                        foreach($heading as $headKey => $headVal){
	                            $j = array_search($catKey, $headVal['category']); //$j = numeric index
	                            $checked = ($j && $headVal['setting'][$j]) ? 'checked' : '';
	                            if($_SERVER['REQUEST_METHOD'] == 'POST'){
	                                if(!empty($_POST[$headKey.'_'.$catKey])){
	                                    $setting = '1';
	                                    $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	                                    $checked = 'checked';
	                                    if($j){
	                                        $valID = $headVal['valID'][$j];
	                                        $stmt_update_setting->execute();
	                                        if($stmt_update_setting->affected_rows > 0){
	                                            $escaped_setting = test_input($setting);
	                                            insertVVLog("UPDATE","Update Value '$valID' for Procedure Directory $vvID to '$escaped_setting'");
	                                        }
	                                    } else {
	                                        $cat = $catKey;
	                                        $setID = $headVal['id'];
	                                        $stmt_insert_setting->execute();
	                                        $escaped_setting = test_input($setting);
	                                        insertVVLog("INSERT","Insert Value '$valID' for Procedure Directory $vvID as '$escaped_setting'");
	                                    }
	                                } elseif($j && $headVal['setting'][$j]){
	                                    $valID = $headVal['valID'][$j];
	                                    $setting = '0';
	                                    $setting_encrypt = secure_data('DSGVO', $setting, 'encrypt', $userID, $privateKey);
	                                    $checked = '';
	                                    $stmt_update_setting->execute();
	                                    if($stmt_update_setting->affected_rows > 0){
	                                        $escaped_setting = test_input($setting);
	                                        insertVVLog("UPDATE","Update Value '$valID' for Procedure Directory $vvID to '$escaped_setting'");
	                                    }
	                                    showError($stmt_update_setting->error);
	                                }
	                            }
	                            echo '<td class="text-center" style="border-bottom:3px dashed '.$cat_colors[$headVal['category'][0]].'"><input type="checkbox" '.$checked.' name="'.$headKey.'_'.$catKey.'" value="1" ></td>';
	                        }
	                        echo '<td></td>';
	                        echo '</tr>';
	                    }
	                }
	                ?>
	                 </table>
	            <?php else: ?>
	                Diese Firma hat keine Matrix in den Einstellungen. Zum Erstellen <a href='../dsgvo/data-matrix'>hier klicken</a>.
				<?php endif; ?>
	        </div>
	    </div>
	</div>

	<div id="add-cate" class="modal fade">
	  <div class="modal-dialog modal-content modal-md">
		<div class="modal-header h4">Neue Kategorie Option</div>
		<div class="modal-body">
	        <div class="row">
	            <div class="col-sm-12">
	                <label>Name</label>
	                <input type="text" name="add_category_name" class="form-control" maxlength="60" />
	            </div>
	        </div>
	        <div class="row">
	            <div class="col-sm-12">
	                <div class="radio">
	                    <label><input type="radio" name="add_category_mittlung" value="heading1" checked /><?php echo $lang['DSGVO_CATEGORY_TOSTRING']['heading1']; ?></label>
	                </div>
	                <div class="radio">
	                    <label><input type="radio" name="add_category_mittlung" value="heading3" /><?php echo $lang['DSGVO_CATEGORY_TOSTRING']['heading3']; ?></label>
	                </div>
	                <div class="radio">
	                    <label><input type="radio" name="add_category_mittlung" value="heading2" /><?php echo $lang['DSGVO_CATEGORY_TOSTRING']['heading2']; ?></label>
	                </div>
					<div class="radio">
	                    <label><input type="radio" name="add_category_mittlung" value="heading4" /><?php echo $lang['DSGVO_CATEGORY_TOSTRING']['heading4']; ?></label>
	                </div>
	            </div>
	        </div>
	        <div class="row" id="mittlung_customer_chooser">
	            <div class="col-sm-12">
	                <div class="input-group">
	                    <select class="form-control" name="add_category_client">
	                        <option value='0'><?php echo $lang['CLIENT'] ?> ...</option>
	                        <?php
							$result = mysqli_query($conn, "SELECT id, name FROM clientData WHERE isSupplier = 'FALSE' AND companyID=$company");
	                            if ($result && $result->num_rows > 0) {
	                                while ($row = $result->fetch_assoc()) {
	                                    $cmpnyID = $row['id'];
	                                    $cmpnyName = $row['name'];
	                                    echo "<option value='$cmpnyID'>$cmpnyName</option>";
	                                }
	                            }
	                        ?>
	                    </select>
	                    <div class="input-group-btn">
	                        <a class="btn btn-default" href="../system/clients?t=1"><i class="fa fa-external-link text-muted"></i>Neu</a>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
		<div class="modal-footer">
	        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	        <button type="submit" class="btn btn-warning" name="add_category"><?php echo $lang['SAVE']; ?></button>
		</div>
	  </div>
	</div>
	<?php endif;?>
	</div>
</form>
<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
	$('#add-cate').on('show.bs.modal', function (event) {
	  var button = $(event.relatedTarget);
	  //data-valid="'.$val['valID'][0].'" data-setting="'.$val['setting'][0].'" data-client="'.$val['client'][0].'" data-cat="'.$val['category'][0].'"
	  $(this).find('button[name="add_category"]').val(button.data('valid'));
	  $(this).find('input[name="add_category_name"]').val(button.data('setting'));
	  $(this).find('input:radio[name="add_category_mittlung"][value="'+button.data('cat')+'"]').click();
	  $(this).find('[name="add_category_client"]').val(button.data('client')).trigger('change');
	});

    $('[data-toggle="tooltip"]').tooltip();

    function toggleCustomerChooser(visible){
        if(visible){
            $("#mittlung_customer_chooser").fadeIn();
        } else {
            $("select[name='add_category_client']").val("0");
            $("#mittlung_customer_chooser").fadeOut();
        }
    }

    $("input[name='add_category_mittlung'][value='heading1']").change(function(event){
        toggleCustomerChooser(true);
    });

    $("input[name='add_category_mittlung'][value='heading2']").change(function(event){
        toggleCustomerChooser(false);
    });

    $("input[name='add_category_mittlung'][value='heading3']").change(function(event){
        toggleCustomerChooser(true);
    });

	tinymce.init({
	  selector: '#matrice-area',
	  height: 150,
	  menubar: false,
	  plugins: [
	    'advlist autolink lists link image charmap print preview anchor',
	    'searchreplace visualblocks code fullscreen',
	    'insertdatetime media table contextmenu paste code'
	  ],
	  toolbar: 'undo redo | styleselect | outdent indent | bullist table',
	  relative_urls: false,
	  content_css: '../plugins/homeMenu/template.css',
	  init_instance_callback: function (editor) {
	    editor.on('keyup', function (e) {
	        var blink = $('.blinking');
	        blink.attr('class', 'btn btn-warning blinking');
	        setInterval(function() {
	        blink.fadeOut(500, function() {
	            blink.fadeIn(500);
	        });
	        }, 1000);
	    });
	  }
	});
</script>
<?php echo $last_encryption_error; ?>
<?php include dirname(__DIR__) . '/footer.php'; ?>
