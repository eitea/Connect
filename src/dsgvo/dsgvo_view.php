<?php
include dirname(__DIR__) . '/header.php'; enableToDSGVO($userID);
require dirname(__DIR__) . "/misc/helpcenter.php";
if (empty($_GET['n']) || !in_array($_GET['n'], $available_companies)) { //eventually STRIKE
    $conn->query("UPDATE UserData SET strikeCount = strikeCount + 1 WHERE id = $userID");
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Invalid Access.</strong> '.$lang['ERROR_STRIKE'].'</div>';
    include dirname(__DIR__) . '/footer.php';
    die();
}

$cmpID = intval($_GET['n']);
$bucket = $identifier .'-uploads'; //no uppercase, no underscores, no ending dashes, no adjacent special chars
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['delete'])){
        $val = intval($_POST['delete']);
        $conn->query("DELETE FROM documents WHERE id = $val AND companyID = $cmpID;");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    } elseif (!empty($_POST['clone'])){
        $val = intval($_POST['clone']);
        $conn->query("INSERT INTO documents (companyID, docID, name, txt, version) SELECT companyID, docID, name, txt, version FROM documents WHERE id = $val AND companyID = $cmpID");
        //TODO: cloning a BASE has to result in merging the freetext INTO the document

        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_CREATE'].'</div>';
        }
    } elseif (isset($_POST['addDocument']) && !empty($_POST['add_docName'])) {
        $val = secure_data('DSGVO', test_input($_POST['add_docName']), 'encrypt', $userID, $privateKey, $err);
        $conn->query("INSERT INTO documents(name, txt, companyID, version) VALUES('$val', ' ', $cmpID, '1.0') ");
        if ($err || $conn->error) {
			showError($err . $conn->error);
        } else {
            redirect("edit?d=" . $conn->insert_id);
        }
    } elseif(!empty($_POST['save-meta'])){
		$metaID = intval($_POST['save-meta']);
		$parentID = !empty($_POST['meta-parent']) ? intval($_POST['meta-parent']) : 'NULL';
		$filename = secure_data('DSGVO', test_input($_POST['meta-name']), 'encrypt', $userID, $privateKey);
		$versionDescr = secure_data('DSGVO', test_input($_POST['meta-versionDescr']));
		$descr = secure_data('DSGVO', test_input($_POST['meta-description']));
		$note = secure_data('DSGVO', test_input($_POST['meta-note']));
		$cat = intval($_POST['meta-subcat']);
		$status = test_input($_POST['meta-status'], 1);
		$fromDate = test_Date($_POST['meta-fromDate'], 'Y-m-d') ? $_POST['meta-fromDate'] : '0000-00-00';
		$toDate = test_Date($_POST['meta-toDate'], 'Y-m-d') ? $_POST['meta-toDate'] : '0000-00-00';
		$validDate = test_Date($_POST['meta-validDate'], 'Y-m-d') ? $_POST['meta-validDate'] : '0000-00-00';
		$cPartner = test_input($_POST['meta-cPartner'], 1);
		if($cPartner == 'employee'){
			$cPartnerID = intval($_POST['meta-cPartner-employee']);
		} elseif($cPartner == 'contact' && !empty($_POST['filterClient'])){
			$cPartnerID = intval($_POST['filterClient']);
			if(!empty($_POST['filterContact'])) $cPartnerID .= ' '.intval($_POST['filterContact']);
		} elseif($cPartner == 'free' && !empty($_POST['meta-free-existing'])){
			$cPartnerID = intval($_POST['meta-free-existing']);
		} elseif($cPartner =='free' && !empty($_POST['meta-free-firstname']) && !empty($_POST['meta-free-lastname'])){
			$firstname = test_input($_POST['meta-free-firstname']);
			$lastname = test_input($_POST['meta-free-lastname']);
			$gender = test_input($_POST['meta-free-gender'], 1);
			$email = test_input($_POST['meta-free-email']);
			$conn->query("INSERT INTO contactPersons(firstname, lastname, email, gender, clientID) VALUES('$firstname', '$lastname', '$email', '$gender', NULL)");
			$cPartnerID = $conn->insert_id;
		}
		$conn->query("UPDATE archive_meta SET parentID = $parentID, name = '$filename', description = '$descr', versionDescr = '$versionDescr', category = '$cat', status = '$status',
			fromDate = '$fromDate', toDate = '$toDate', validDate = '$validDate', cPartner = '$cPartner', cPartnerID = '$cPartnerID', note = '$note' WHERE id = $metaID");
		if($conn->error){
			echo $conn->error;
		} else {
			showSuccess($lang['OK_SAVE']);
		}
	} elseif(!empty($_POST['edit-meta'])){
		$openUpload = intval($_POST['edit-meta']);
	} elseif(isset($_POST['editcat_save']) && !empty($_POST['editcat_name'])){
		$val = test_input($_POST['editcat_name']);
		if(!empty($_POST['editcat_save'])){
			$id = intval($_POST['editcat_save']);
			$conn->query("UPDATE dsgvo_categories SET name = '$val' WHERE id = $id");
		} else {
			$conn->query("INSERT INTO dsgvo_categories(name) VALUES('$val')");
		}
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess($lang['OK_SAVE']);
		}
	} elseif(isset($_POST['editcat_save'])){
		showError($lang['ERROR_MISSING_FIELDS']);
	} elseif(!empty($_POST['deletecat'])){
		$id = intval($_POST['deletecat']);
		$conn->query("DELETE FROM dsgvo_categories WHERE id = $id");
		//TODO: add a foreign key dependency
		$conn->query("UPDATE archive_meta SET category = NULL WHERE category = $id");
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess($lang['OK_DELETE']);
		}
	}

	if(!empty($_POST['delete-archive'])){
		$uniqID = test_input($_POST['delete-archive']);
		if($s3 = getS3Object()){
			$s3->deleteObject(['Bucket' => $bucket, 'Key' => $uniqID]);
		}
		$conn->query("DELETE FROM archive WHERE uniqID = '$uniqID' AND category = 'AGREEMENT'");
		if($conn->error){
			showError($conn->error);
		} else {
			showSuccess($lang['OK_DELETE']);
		}
	} elseif (isset($_FILES['uploadPDF']) && !empty($_FILES['uploadPDF']['name'])) {
		$file_info = pathinfo($_FILES['uploadPDF']['name']);
		$ext = strtolower($file_info['extension']);
		if($_FILES['uploadPDF']['size'] < 8000008 && $_FILES['uploadPDF']['type'] == 'application/pdf' && $ext == 'pdf') {
			if($s3 = getS3Object()){
				if(!$s3->doesBucketExist($bucket)){
					$result = $s3->createBucket(['Bucket' => $bucket]);
					if($result) showSuccess("Bucket $bucket Created");
				}

				$content = file_get_contents($_FILES['uploadPDF']['tmp_name']);
				$file_encrypt = secure_data('DSGVO', $content, 'encrypt', $userID, $privateKey, $err);
				if($file_encrypt != $content){
					$hashkey = uniqid('', true); //23 chars
					$s3->putObject(array(
						'Bucket' => $bucket,
						'Key' => $hashkey,
						'Body' => $file_encrypt
					));

					$filename = test_input($file_info['filename']);
					$conn->query("INSERT INTO archive (category, categoryID, name, parent_directory, type, uniqID, uploadUser)
					VALUES ('AGREEMENT', '$cmpID', '$filename', 'ROOT', '$ext', '$hashkey', $userID)");
					if($conn->error){
						echo $conn->error;
					} else {
						$conn->query("INSERT INTO archive_meta(archiveID, name, fromDate, validDate) VALUES(".$conn->insert_id.",
							'".secure_data('DSGVO', $filename, 'encrypt', $userID, $privateKey)."', CURDATE(), CURDATE())");
						if(!$conn->error){
							showSuccess($lang['OK_UPLOAD']);
						} else {
							echo $conn->error;
						}
					}
				} else {
					echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$err.'</div>';
				}
			} else {
				echo "Couldnt connect to s3";
			}
		} else {
			showError($lang['ERROR_INVALID_UPLOAD']);
		}
	} elseif (isset($_FILES['uploadZip']) && !empty($_FILES['uploadZip']['name'])) {
        $filename = $_FILES["uploadZip"]["name"];
        $accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
        if($_FILES['uploadZip']["size"] < 8000008 && in_array($_FILES["uploadZip"]["type"], $accepted_types) && substr_compare($filename, '.zip', -4) === 0) {
            $zip = new ZipArchive();
            if($zip->open($_FILES['uploadZip']["tmp_name"]) === true) {
                $stmt = $conn->prepare("INSERT INTO documents(name, txt, companyID, version, docID, isBase) VALUES(?, ?, $cmpID, ?, ?, 'TRUE')");
                $stmt->bind_param('ssss', $doc_name, $doc_txt, $doc_ver, $doc_id);
                $stmt_up = $conn->prepare("UPDATE documents SET txt = ?, name = ?, version = ? WHERE id = ?");
                $stmt_up->bind_param('sssi', $doc_name, $doc_txt, $doc_ver, $id);
                for($i = 0; $i < $zip->numFiles; $i++) {
                    $filename = explode('.', $zip->getNameIndex($i));
                    if(count($filename) == 2 && $filename[1] == 'txt' && ($meta = $zip->getFromName($filename[0] . '.xml'))) {
                        $meta = simplexml_load_string($meta);
                        if($meta->template_name && $meta->template_version && $meta->template_ID){
                            $doc_name = secure_data('DSGVO', test_input($meta->template_name), 'encrypt', $userID, $privateKey);
                            $doc_ver = $meta->template_version;
                            $doc_id = $meta->template_ID;
                            $doc_txt = secure_data('DSGVO', convToUTF8(nl2br($zip->getFromIndex($i))));
                            //upload exists: update
                            $result = $conn->query("SELECT id FROM documents WHERE companyID = $cmpID AND docID = '$doc_id'");
                            if($result && $result->num_rows > 0){
                                $row = $result->fetch_assoc();
                                $result->free();
                                $id = $row['id'];
                                $stmt_up->execute();
                                if($conn->error){
                                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                                }
                            } else { //insert as new document
                                $stmt->execute();
                                if($conn->error){
                                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                                }
                            }
                        } else {
                            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Fehler in '.$filename[0].'.xml: Fehlerhafte Tags.</div>';
                            continue;
                        }
                    }
                }
                $stmt->close();
                $stmt_up->close();
                $zip->close();
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>ZIP File konnte nicht geöffnet werden.</div>';
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Ungültig: File zu groß oder kein gültiges ZIP.</div>';
        }
    }
    if(isset($_POST['sendAccess']) && !empty($_POST['send_contact']) && !empty($_POST['send_document'])) {
        $accept = true;
        $processID = uniqid();
        $docID = intval($_POST['send_document']);
        $contactID = intval($_POST['send_contact']);
        $pass = '';
        if (!empty($_POST['send_andPassword'])) {
            $pass = password_hash($_POST['send_andPassword'], PASSWORD_BCRYPT);
        }
        //contactPerson
        $result = $conn->query("SELECT email, firstname, lastname FROM contactPersons WHERE id = $contactID");
        if(!$contact_row = $result->fetch_assoc()) $accept = false;

        //build link
        $link_id = '';
        if (getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])) {
            $link_id = '/' . substr($servername, 0, 8);
        }
        $link = "https://" . $_SERVER['HTTP_HOST'] . $link_id . $_SERVER['REQUEST_URI'];
        $link = explode('/', $link);
        array_pop($link);
        $link = implode('/', $link) . "/access?n=$processID";
        //prepare document
        $result = $conn->query("SELECT docID, name, txt, version FROM documents WHERE id = $docID AND companyID = $cmpID");
        if($row = $result->fetch_assoc()){
            $doc_cont = secure_data('DSGVO', test_input($row['txt']), 'decrypt', $userID, $privateKey);
            $doc_head = secure_data('DSGVO', $row['name'], 'decrypt');
            $doc_ver = $row['version'];
            $doc_ident = $row['docID'];
            $result->free();
        } else {
            echo $conn->error;
            $accept = false;
        }
        $result = $conn->query("SELECT p.firstname, p.lastname, e.name, c.address_Street, c.address_Country_Postal, c.address_Country_City FROM contactPersons p"
        ."INNER JOIN clientData e ON p.clientID = e.id INNER JOIN clientInfoData c ON e.id = c.clientID WHERE p.id = $contactID");
        if($accept && ($row = $result->fetch_assoc())){
            $doc_cont = str_replace('[LINK]', $link, $doc_cont);
            $doc_cont = str_replace('[FIRSTNAME]', $contact_row['firstname'], $doc_cont);
            $doc_cont = str_replace('[LASTNAME]', $contact_row['lastname'], $doc_cont);
            $doc_cont = str_replace('[Companyname]', $row['name'], $doc_cont);
            $doc_cont = str_replace('[Companystreet]', $row['address_Street'], $doc_cont);
            $doc_cont = str_replace('[Companyplace]', $row['address_Country_City'], $doc_cont);
            $doc_cont = str_replace('[Companypostcode]', $row['address_Country_Postal'], $doc_cont);
            $result->free();
            if(preg_match_all("/\[CUSTOMTEXT_\d+\]/", $doc_cont, $matches) && $matches){
                $result = $conn->query("SELECT id, identifier, content FROM document_customs WHERE doc_id = '$doc_ident' AND companyID = $cmpID ");
                $result = $result->fetch_all(MYSQLI_ASSOC);
                $result = array_combine(array_column($result, 'identifier'), array_column($result, 'content'));
                foreach($matches[0] as $match){
                    $doc_cont = str_replace($match, secure_data('DSGVO', $result[substr($match,1,-1)], 'decrypt'), $doc_cont);
                }
            }
        }
		//create process and history
        if($accept){
            $stmt = $conn->prepare("INSERT INTO documentProcess(id, docID, personID, password, document_text, document_headline, document_version) VALUES(?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('siissss', $processID, $docID, $contactID, $pass, $doc_cont, $doc_head, $doc_ver);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO documentProcessHistory(processID, activity) VALUES('$processID', ?)");
            $stmt->bind_param("s", $activity);
            if (isset($_POST['send_andRead'])) {
                $activity = 'ENABLE_READ';
                $stmt->execute();
            }
            if (isset($_POST['send_andSign'])) {
                $activity = 'ENABLE_SIGN';
                $stmt->execute();
            }
            if (isset($_POST['send_andAccept'])) {
                $activity = 'ENABLE_ACCEPT';
                $stmt->execute();
            }
            $stmt->close();
            //build email content
            if ($_POST['send_template']) {
                $val = intval($_POST['send_template']);
                $res = $conn->query("SELECT htmlCode FROM templateData WHERE id = $val AND type='document' AND userIDs = $cmpID ");
                $content = $res->fetch_assoc()['htmlCode'];
            } else {
                $content = "<p>Guten Tag,</p><p>&nbsp;</p><p>Soeben wurde&nbsp;folgendes Dokument an&nbsp;[FIRSTNAME]&nbsp;[LASTNAME] versendet. Es ist unter folgendem Link einsehbar:</p>" .
                "<p>[LINK]</p><p>&nbsp;</p><p>Zu beachten sind:</p><ul><li>Alle T&auml;tigkeiten auf dieser&nbsp;Seite werden mitprotokolliert und sind f&uuml;r den&nbsp;Absender dieses Dokuments einsehbar.&nbsp;</li>" .
                "<li>Jede Option kann nur einmal abgespeichert werden und ist im Nachhinein nicht mehr &auml;nderbar.</li><li>Falsch eingegebene Passw&ouml;rter werden gespeichert.&nbsp;</li></ul><p>&nbsp;</p><p>Danke.</p>";
            }

            $content = str_replace("[LINK]", $link, $content);
            $content = str_replace('[FIRSTNAME]', $contact_row['firstname'], $content);
            $content = str_replace('[LASTNAME]', $contact_row['lastname'], $content);

            //send mail
			$err = send_standard_email($contact_row['email'], $content);
            if($err){
                $conn->query("INSERT INTO $mailLogsTable(sentTo, messageLog) VALUES('".$contact_row['email']."', '$err')");
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $err . '</div>';
            } elseif ($conn->error) {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $conn->error . '</div>';
            } else {
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>' . $lang['OK_CREATE'] . '</div>';
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Dokument oder Kontakperson unzulässig.</div>';
        }
    }
}
?>

<div class="page-header"><h3><?php echo $lang['DOCUMENTS']; ?>
    <div class="page-header-button-group">
        <button type="button" data-toggle="modal" data-target="#pdf-upload" class="btn btn-default" title="Upload PDF File"><i class="fa fa-upload"></i> PDF Upload</button>
		<a data-toggle="collapse" href="#show-categories" class="btn btn-default" title="Subkategorien Bearbeiten">Kategorien</a>
    </div>
</h3></div>

<div class="collapse" id="show-categories">
	<form method="POST">
	<h4>Subkategorien Verwalten
		<div class="page-header-button-group">
	        <button type="button" data-toggle="modal" data-target="#edit-categories" class="btn btn-default" title="Neue Subkategorie Hinzufügen"><i class="fa fa-plus"></i></button>
	    </div>
	</h4>
	<table class="table datatable">
		<thead>
			<tr>
				<th>Kategorie</th>
				<th>Name</th>
				<th></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$catresult = $conn->query("SELECT id, name FROM dsgvo_categories");
			while($cats = $catresult->fetch_assoc()){
				echo '<tr>';
				echo '<td>Vertrag</td>';
				echo '<td>'.$cats['name'].'</td>';
				echo '<td>';
				if($conn->query("SELECT id FROM archive_meta WHERE category = ".$cats['id'])->num_rows < 1){
					echo '<button type="submit" name="deletecat" value="'.$cats['id'].'" class="btn btn-default" title="Löschen"><i class="fa fa-trash-o"></i></button> ';
				}
				echo '<a data-toggle="modal" href="#edit-categories" data-name="'.$cats['name'].'" data-catid="'.$cats['id'].'" data- class="btn btn-default"><i class="fa fa-pencil"></i></a></td>';
				echo '</tr>';
			}
			?>
		</tbody>
	</table><br><br>
	</form>
</div>

<table class="table datatable">
	<thead>
		<tr>
			<th><?php echo $lang['CATEGORY']; //5b055b4696156 ?></th>
			<th>Name</th>
			<th>Vertragspartner</th>
			<th><?php echo $lang['VALID_PERIOD']; ?></th>
			<th>Status</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php
		$stmt = $conn->prepare('SELECT firstname, lastname FROM contactPersons WHERE id = ? LIMIT 1');
		$stmt->bind_param('i', $val);
		$stmt_client = $conn->prepare('SELECT name FROM clientData WHERE id = ? LIMIT 1');
		$stmt_client->bind_param('i', $val);
		$result = $conn->query("SELECT am.*, uniqID, c.name AS catName FROM archive INNER JOIN archive_meta am ON archive.id = am.archiveID
			LEFT JOIN dsgvo_categories c ON c.id = am.category WHERE archive.category = 'AGREEMENT' AND archive.categoryID = '$cmpID' ");
		echo $conn->error;
		while($result && ($row = $result->fetch_assoc())){
			echo $row['status'] == 'PENDING' ? '<tr style="color:red">' : '<tr>';
			echo '<td>'.$row['catName'].'</td>';
			echo '<td>'.mc_status('DSGVO').secure_data('DSGVO',$row['name'], 'decrypt', $userID, $privateKey).' - '.secure_data('DSGVO', $row['versionDescr'], 'decrypt').'</td>';
			echo '<td>';
			if($row['cPartner'] == 'employee') echo $userID_toName[$row['cPartnerID']];
			if($row['cPartner'] == 'contact'){
				$arr = explode(' ', $row['cPartnerID']);
				$val = $arr[0];
				$stmt_client->execute();
				$conRow = $stmt_client->get_result()->fetch_assoc();
				echo $conRow['name'];
				if(isset($arr[1])){
					$val = $arr[1];
					$stmt->execute();
					$conRow = $stmt->get_result()->fetch_assoc();
					echo ' - '.$conRow['firstname'].' '.$conRow['lastname'];
				}
			}
			if($row['cPartner'] == 'free'){
				$val = $row['cPartnerID'];
				$stmt->execute();
				$conRow = $stmt->get_result()->fetch_assoc();
				echo $conRow['firstname'].' '.$conRow['lastname'];
			}
			echo '</td>';
			echo '<td>'.$row['validDate'].'</td>';
			echo '<td>'.$row['status'].'</td>';
			echo '<td><form method="POST" style="display:inline"><button type="submit" name="edit-meta" value="'.$row['id'].'" class="btn btn-default"><i class="fa fa-pencil"></i></button>
			<button type="submit" name="delete-archive" value="'.$row['uniqID'].'" class="btn btn-default"><i class="fa fa-trash-o"></i></button></form>
			<form method="POST" style="display:inline" action="../project/detailDownload" target="_blank"><input type="hidden" name="keyReference" value="DSGVO" />
			<button type="submit" class="btn btn-default" name="download-file" value="'.$row['uniqID'].'"><i class="fa fa-download"></i></button></form>';
			echo '</td>';
			echo '</tr>';

			if($row['status'] == 'PENDING') $openUpload = $row['id'];
		}
		$stmt->close();
		$stmt_client->close();
		?>
	</tbody>
</table>

<br><hr><br> <!-- TEMPLATE SECTION -->

<div class="page-header"><h3><?php echo $lang['TEMPLATES'].' '.$lang['FOR'].' '.$lang['DOCUMENTS']; ?>
    <div class="page-header-button-group">
        <button type="button" data-toggle="modal" data-target="#new-document" class="btn btn-default" title="New..."><i class="fa fa-plus"></i></button>
        <button type="button" data-toggle="modal" data-target="#zip-upload" class="btn btn-default" title="Upload Zip File"><i class="fa fa-upload"></i> ZIP Upload</button>
    </div>
    <span style="float:right" ><a href="https://consulio.at/dokumente" class="btn btn-sm btn-warning" target="_blank"><?php echo $lang['NEWEST_AGREEMENTS_FROM_CONSULIO']; ?></a></span>
</h3></div>
<table class="table datatable">
    <thead><tr>
        <th>Name</th>
        <th>Version</th>
        <th></th>
    </tr></thead>
    <tbody>
        <?php
        $doc_selects = '';
        $result = $conn->query("SELECT * FROM documents WHERE companyID = $cmpID");
        while ($row = $result->fetch_assoc()) {
            $style = '';
            if($row['isBase'] == 'TRUE'){
                $style = 'style="background-color:#efefef"';
                $row['version'] .= ' <small>(Basis)</small>';
            }
            echo "<tr $style>";
            echo '<td>' .mc_status('DSGVO'). secure_data('DSGVO', $row['name'], 'decrypt', $userID, $privateKey) . '</td>';
            echo '<td>' . $row['version'] . '</td>';
            echo '<td><form method="POST">';
            echo '<a href="edit?d=' . $row['id'] . '" title="'.$lang['EDIT'].'" class="btn btn-default"><i class="fa fa-pencil"></i></a> ';
            echo '<button type="submit" name="clone" value="' . $row['id'] . '" title="Klonen" class="btn btn-default" ><i class="fa fa-files-o"></i></button> ';
            echo '<button type="submit" name="delete" value="' . $row['id'] . '" title="Löschen" class="btn btn-default" ><i class="fa fa-trash-o"></i></button> ';
            echo '<button type="button" name="setSelect" value="' . $row['id'] . '" data-toggle="modal" data-target="#send-as-mail" class="btn btn-default" title="Senden.."><i class="fa fa-envelope-o"></i></button>';
            echo '</form></td>';
            echo '</tr>';
			//5ae9c3361c57c
            $doc_selects .= '<option value="' . $row['id'] . '" >' . secure_data('DSGVO', $row['name'], 'decrypt')  .' - '. $row['version']. '</option>';
        }
        ?>
    </tbody>
</table>

<form method="POST">
    <div id="send-as-mail" class="modal fade">
        <div class="modal-dialog modal-content modal-md"><div class="modal-header h4">Dokument Senden</div>
	        <div class="modal-body">
	            <div class="container-fluid">
	                <label><?php echo $lang['DOCUMENTS']; ?></label>
	                <select id="send-select-doc" class="js-example-basic-single" name="send_document"><?php echo $doc_selects; ?></select>
	                <br><br>
	                <?php include dirname(__DIR__).'/misc/select_contact.php'; ?>
	                <div class="row form-group checkbox">
	                    <div class="col-sm-4"><label><input type="checkbox" name="send_andRead" /> + Lesen</label></div>
	                    <div class="col-sm-4"><label><input type="checkbox" name="send_andAccept" /> + Akzeptieren</label></div>
	                    <div class="col-sm-4"><label><input type="checkbox" name="send_andSign" /> + Unterschreiben</label></div>
	                </div>
	                <br>
	                <div class="row">
	                    <div class="col-sm-6"><label>Zugang mit Passwort schützen</label><input type="text" name="send_andPassword" placeholder="Password" class="form-control" /></div>
	                    <div class="col-sm-6">
	                        <label>E-Mail Vorlage</label>
	                        <select class="js-example-basic-single" name="send_template">
	                            <option value="0"><?php echo $lang['DEFAULT']; ?></option>
	                            <?php
	                            $res = $conn->query("SELECT * FROM templateData WHERE type='document' AND userIDs = $cmpID");
	                            while ($res && ($row_fc = $res->fetch_assoc())) {
	                                echo "<option value='" . $row_fc['id'] . "' >" . $row_fc['name'] . "</option>";
	                            }
	                            ?>
	                        </select>
	                    </div>
	                </div>
	            </div>
	        </div>
	        <div class="modal-footer">
	            <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	            <button type="submit" class="btn btn-warning" name="sendAccess">Dokument Senden</button>
	        </div>
	    </div>
	</div>
</form>

<form method="POST">
    <div class="modal fade" id="new-document">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header h4"><?php echo $lang['ADD']; ?></div>
            <div class="modal-body">
                <label>Name <?php echo mc_status('DSGVO'); ?> </label>
                <input type="text" class="form-control" name="add_docName" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="addDocument"><?php echo $lang['ADD']; ?></button>
            </div>
        </div>
    </div>
</form>

<form method="POST" enctype="multipart/form-data">
    <div class="modal fade" id="zip-upload">
        <div class="modal-dialog modal-content modal-sm">
            <div class="modal-header h4">ZIP <?php echo $lang['UPLOAD']; ?></div>
            <div class="modal-body">
                <label class="btn btn-default">
                    .zip File <?php echo $lang['UPLOAD']; ?>
                    <input type="file" name="uploadZip" accept="application/zip" style="display:none">
                </label>
                <small>Max. 8MB</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning"><?php echo $lang['UPLOAD']; ?></button>
            </div>
        </div>
    </div>
</form>

<form method="POST" enctype="multipart/form-data">
    <div class="modal fade" id="pdf-upload">
        <div class="modal-dialog modal-content modal-sm">
            <div class="modal-header h4">PDF <?php echo $lang['UPLOAD']; ?></div>
            <div class="modal-body">
                <label class="btn btn-default">
                    .pdf File <?php echo $lang['UPLOAD']; ?>
                    <input type="file" name="uploadPDF" accept="application/pdf" style="display:none">
                </label>
                <small>Max. 8MB</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning"><?php echo $lang['UPLOAD']; ?></button>
            </div>
        </div>
    </div>
</form>

<form method="POST" enctype="multipart/form-data">
    <div class="modal fade" id="edit-categories">
        <div class="modal-dialog modal-content modal-sm">
            <div class="modal-header h4">Subkategorie</div>
            <div class="modal-body">
				<label>Kategorie</label>
				<select class="form-control">
					<option>Vertrag</option>
				</select>
				<br>
                <label for="editcat_name">Name</label>
				<input type="text" class="form-control" name="editcat_name" id="editcat_name" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="editcat_save" id="editcat_save"><?php echo $lang['SAVE']; ?></button>
            </div>
        </div>
    </div>
</form>

<?php
$result = false;
if(!empty($openUpload) && is_numeric($openUpload)){
	$result = $conn->query("SELECT m.*, uniqID FROM archive_meta m, archive WHERE archive.id = m.archiveID AND m.id = $openUpload LIMIT 1");
	echo $conn->error;
}
if($result && ($row = $result->fetch_assoc())):
 ?>
<div class="modal fade" id="edit-meta-modal">
	<div class="modal-dialog modal-content modal-lg">
		<div class="modal-header h4"><?php echo $lang['DOCUMENTS'].' - Detail '.$lang['DATA']; ?>
			<form method="POST" style="display:inline;float:right" action="../project/detailDownload" target="_blank">
				<input type="hidden" name="keyReference" value="DSGVO" />
				<button type="submit" class="btn btn-default" name="download-file" value="<?php echo $row['uniqID']; ?>">Vorschau <i class="fa fa-download"></i></button>
			</form>
		 </div>
			<form method="POST">
			<div class="modal-body">
				<div class="row">
					<div class="col-md-6">
						<label for="meta-name"><?php echo mc_status('DSGVO'); ?>Name</label>
						<input id="meta-name" maxlenght="150" type="text" name="meta-name" class="form-control required-field" value="<?php echo secure_data('DSGVO', $row['name'], 'decrypt'); ?>" />
					</div>
					<div class="col-md-2">
						<label for="meta-versionDescr"><?php echo mc_status('DSGVO'); ?>Version</label>
						<input id="meta-versionDescr" maxlength="75" type="text" name="meta-versionDescr" class="form-control" value="<?php echo secure_data('DSGVO', $row['versionDescr'], 'decrypt'); ?>" />
					</div>
					<div class="col-md-2">
						<label for="meta-status">Status</label>
						<select id="meta-status" name="meta-status" class="js-example-basic-single">
							<option value="ACTIVE">Aktiv</option>
							<option value="WAITING" <?php if($row['status'] == 'WAITING') echo'selected'; ?> >Wartend</option>
							<option value="INACTIVE" <?php if($row['status'] == 'INACTIVE') echo'selected'; ?>>Deaktiv</option>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<label><?php echo $lang['CATEGORY']; ?></label>
						<select class="form-control">
							<option><?php echo $lang['CONTRACT']; ?></option>
						</select>
					</div>
					<div class="col-md-6">
						<label>Subkategorie</label>
						<select class="js-example-basic-single" name="meta-subcat">
							<?php
							$catresult = $conn->query("SELECT id, name FROM dsgvo_categories")->fetch_all();
							$cats = array_combine(array_column($catresult, 0),array_column($catresult, 1));
							foreach($cats as $key => $val){
								$checked = $key == $row['category'] ? 'selected' : '';
								echo "<option $checked value=\"$key\">$val</option>";
							}
							?>
						</select>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<label for="meta-validDate">Vertragsdatum</label>
						<input id="meta-validDate" type="text" class="form-control datepicker" name="meta-validDate" value="<?php echo $row['validDate']; ?>" />
					</div>
					<div class="col-md-4">
						<label for="meta-fromDate">Gültig Ab</label>
						<input id="meta-fromDate" type="text" class="form-control datepicker" name="meta-fromDate" value="<?php echo $row['fromDate']; ?>" />
					</div>
					<div class="col-md-4">
						<label for="meta-toDate">Gültig Bis</label>
						<input id="meta-toDate" type="text" class="form-control datepicker" name="meta-toDate" value="<?php echo $row['toDate']; ?>" />
					</div>
				</div>
				<div class="row">
					<div class="col-md-8">
						<label for="meta-parent"><?php echo mc_status('DSGVO'); ?>Ersetzt alten Vertrag</label>
						<select id="meta-parent" class="js-example-basic-single" name="meta-parent">
							<option value=""> ... </option> <!--5b055b09c88df -->
							<?php
							$result = $conn->query("SELECT am.id, am.name, am.category, versionDescr FROM archive INNER JOIN archive_meta am ON archive.id = am.archiveID AND am.status != 'PENDING'
								WHERE archive.category = 'AGREEMENT' AND archive.categoryID = '$cmpID' ");
							while($subRow = $result->fetch_assoc()){
								$selected = $row['parentID'] == $subRow['id'] ? 'selected' : '';
								echo '<option value="'.$subRow['id'].'" '.$selected.'>'.$cats[$subRow['category']].': '.secure_data('DSGVO',$subRow['name'],'decrypt').' - '.secure_data('DSGVO', $subRow['versionDescr'], 'decrypt').'</option>';
							}
							?>
						</select>
					</div>
				</div>
				<div class="row">
					<?php $part = $row['cPartner'] ? $row['cPartner'] : 'employee'; //just for default view ?>
					<div class="col-md-3">
						<label>Vertragspartner</label>
						<br>
						<label><input type="radio" name="meta-cPartner" value="free" <?php if($part == 'free') echo 'checked'; ?>>Frei</label><br>
						<label><input type="radio" name="meta-cPartner" value="employee"<?php if($part == 'employee') echo 'checked'; ?>><?php echo $lang['EMPLOYEE']; ?></label><br>
						<label><input type="radio" name="meta-cPartner" value="contact"<?php if($part == 'contact') echo 'checked'; ?>><?php echo $lang['ADDRESS_BOOK']; ?></label>
					</div>
					<div class="col-md-9">
						<div id="meta-cPartner-employee" <?php if($part != 'employee') echo 'style="display:none"'; ?> >
							<label><?php echo $lang['EMPLOYEE']; ?></label>
							<select class="js-example-basic-single" name="meta-cPartner-employee">
								<?php
								foreach($userID_toName as $key => $val){
									if(in_array($key, $available_users)){
										$selected = $row['cPartner'] == 'employee' ? $row['cPartnerID'] == $key ? 'selected' : '' : '';
										echo '<option '.$selected.' value="'.$key.'">'.$val.'</option>';
									}
								}
								?>
							</select>
						</div>
						<div id="meta-cPartner-free" <?php if($part != 'free') echo 'style="display:none"'; ?>>
							<div class="row">
								<div class="col-sm-12">
									<label>Bestehender Kontakt</label>
									<select id="meta-free-existing" class="js-example-basic-single" name="meta-free-existing">
										<option value="">Neuer Kontakt ... </option>
										<?php
										$result = $conn->query("SELECT id, firstname, lastname FROM contactPersons WHERE clientID IS NULL");
										while($subRow = $result->fetch_assoc()){
											$selected = $row['cPartner'] == 'free' ? $row['cPartnerID'] == $subRow['id'] ? 'selected' : '' : '';
											echo '<option '.$selected.' value="'.$subRow['id'].'">'.$subRow['firstname'].' '.$subRow['lastname'].'</option>';
										}
										?>
									</select>
								</div>
							</div>
							<div id="meta-cPartner-newfree" <?php if($row['cPartner'] == 'free') echo 'style="display:none;"'; ?>>
								<div class="row">
									<div class="col-sm-6">
										<label><?php echo $lang['FIRSTNAME']; ?></label>
										<input type="text" class="form-control" name="meta-free-firstname" value="" />
									</div>
									<div class="col-sm-6">
										<label><?php echo $lang['LASTNAME']; ?></label>
										<input type="text" class="form-control" name="meta-free-lastname" value="" />
									</div>
								</div>
								<div class="row">
									<div class="col-sm-6">
										<label><?php echo $lang['ADDRESS_FORM']; ?></label><br>
		                                <label><input type="radio" value="male" name="meta-free-gender" checked /> <?php echo $lang['GENDER_TOSTRING']['male']; ?></label>
		                                <label style="padding-left:25px;"><input type="radio" value="female" name="meta-free-gender" /> <?php echo $lang['GENDER_TOSTRING']['female']; ?></label>
		                            </div>
									<div class="col-sm-6">
										<label>E-Mail</label>
		                                <input type="email" class="form-control" name="meta-free-email" value="" />
		                            </div>
								</div>
							</div>
						</div>
						<div id="meta-cPartner-contact" <?php if($part != 'contact') echo 'style="display:none"'; ?>>
							<?php
							if($part == 'contact'){ //5b1a6c79d7b3b
								$arr = explode(' ', $row['cPartnerID']);
								$filterings['client'] = $arr[0];
								if(!empty($arr[1])) $filterings['contact'] = $arr[1];
							}
							include dirname(__DIR__).'/misc/select_contact.php';
							?>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-8">
						<label for="meta-description"><?php echo mc_status('DSGVO').' '.$lang['DESCRIPTION']; ?></label>
						<textarea id="meta-description" name="meta-description" rows="3" class="form-control"><?php echo secure_data('DSGVO', $row['description'], 'decrypt'); ?></textarea>
					</div>
					<div class="col-md-4">
						<label><?php echo mc_status('DSGVO').' '.$lang['NOTES']; ?></label>
						<textarea maxlength="250" name="meta-note" rows="3" class="form-control"><?php echo secure_data('DSGVO', $row['note'], 'decrypt'); ?></textarea>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
				<button type="submit" class="btn btn-warning" name="save-meta" value="<?php echo $openUpload; ?>"><?php echo $lang['SAVE']; ?></button>
			</div>
		</form>
	</div>
</div>
<?php endif; ?>

<script>
$('#meta-free-existing').on('change', function(){
	if(!$(this).val()){
		$('#meta-cPartner-newfree').show();
	} else {
		$('#meta-cPartner-newfree').hide();
	}
});
$('button[name=setSelect]').click(function(){
  $("#send-select-doc").val($(this).val()).trigger('change');
});
$('input[name=meta-cPartner]').click(function(){
	$('#meta-cPartner-employee').hide();
	$('#meta-cPartner-free').hide();
	$('#meta-cPartner-contact').hide();
	$('#meta-cPartner-'+$(this).val()).show();
});
$(document).ready(function(){
	$('.datatable').DataTable({
		language: {
			<?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
		},
		autoWidth: false,
		pageLength: 5,
		dom: 'ftp'
	});
	$('#edit-categories').on('show.bs.modal', function (event) {
		var button = $(event.relatedTarget);
		$('#editcat_name').val(button.data('name'));
		$('#editcat_save').val(button.data('catid'));
	});
});
<?php if(!empty($openUpload)){ echo "setTimeout(function(){ $('#edit-meta-modal').modal('show'); }, 500)"; } ?>
</script>
<?php include dirname(__DIR__) . '/footer.php';?>
