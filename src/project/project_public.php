<?php
require dirname(__DIR__).DIRECTORY_SEPARATOR.'header.php';

if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
    $filterings['client'] = test_input($_GET['custID']);
}

if($isProjectAdmin == 'TRUE' && $_SERVER['REQUEST_METHOD'] == 'POST'){
	if(isset($_POST['add']) && !empty($_POST['name']) && !empty($_POST['filterClient'])){
	    $client_id = $filterings['client'] = intval($_POST['filterClient']);
	    $name = test_input($_POST['name']);
	    $status = "";
	    if(isset($_POST['status'])){
	        $status = "checked";
	    }
	    $hourlyPrice = floatval(test_input($_POST['hourlyPrice']));
	    $hours = floatval(test_input($_POST['hours']));

	    if(isset($_POST['createField_1'])){ $field_1 = 'TRUE'; } else { $field_1 = 'FALSE'; }
	    if(isset($_POST['createField_2'])){ $field_2 = 'TRUE'; } else { $field_2 = 'FALSE'; }
	    if(isset($_POST['createField_3'])){ $field_3 = 'TRUE'; } else { $field_3 = 'FALSE'; }
	    $conn->query("INSERT INTO projectData (clientID, name, status, hours, hourlyPrice, field_1, field_2, field_3, creator)
	    VALUES ($client_id, '$name', '$status', '$hours', '$hourlyPrice', '$field_1', '$field_2', '$field_3', $userID)");
	    if($conn->error){
	        showError($conn->error);
	    } else {
	        $projectID = $conn->insert_id;
	        $keyPair = sodium_crypto_box_keypair();
	        $private = sodium_crypto_box_secretkey($keyPair);
	        $public = sodium_crypto_box_publickey($keyPair);
	        $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
	        $nonce = random_bytes(24);
	        $symmetric_encrypted = base64_encode($nonce . sodium_crypto_box($symmetric, $nonce, $private.$public));
	        $conn->query("INSERT INTO security_projects (projectID, publicKey, symmetricKey) VALUES($projectID, '".base64_encode($public)."', '$symmetric_encrypted')");
	        echo $conn->error;

	        $nonce = random_bytes(24);
	        $private_encrypt = base64_encode($nonce . sodium_crypto_box($private, $nonce, $private.base64_decode($publicKey)));
	        $conn->query("INSERT INTO security_access (userID, module, privateKey, optionalID) VALUES($userID, 'PRIVATE_PROJECT', '$private_encrypt', $projectID)");
	        if($conn->error){
	            showError($conn->error);
	        } else {
				$conn->query("INSERT INTO relationship_project_user(projectID, userID, access) VALUES($projectID, $userID, 'WRITE') "); echo $conn->error;
	            showSuccess($lang['OK_ADD']);
	        }

	    }
	}
	if(isset($_POST['delete']) && isset($_POST['index'])) {
	    $index = $_POST["index"];
	    foreach ($index as $x) {
	        $x = intval($x);
	        if (!$conn->query("DELETE FROM projectData WHERE id = $x;")) {
	            echo mysqli_error($conn);
	        } else {
				$conn->query("DELETE FROM security_projects WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$x'");
			}
	    }
	    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
	}
}

require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'aws'.DIRECTORY_SEPARATOR.'autoload.php';

$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0); //set_filter requirement
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
}

//doesnt need a projectID, since we are unique anyways
if(!empty($_POST['delete-file'])){
	$x = test_input($_POST['delete-file']);
	try{
		$s3->deleteObject(['Bucket' => $bucket, 'Key' => $x]);
		$conn->query("DELETE FROM project_archive WHERE uniqID = '$x'");
		if($conn->error){ showError($conn->error); } else { showSuccess($lang['OK_DELETE']); }
	} catch(Exception $e){
		echo $e->getMessage();
	}
} elseif(!empty($_POST['delete-folder'])){
	$x = test_input($_POST['delete-folder']);
	$conn->query("DELETE FROM project_archive WHERE id = '$x' AND type = 'folder'");
	if($conn->error){ showError($conn->error); } else { showSuccess($lang['OK_DELETE']); }
} elseif(!empty($_POST['saveThisProject'])){
	$active_modal = $x = intval($_POST['saveThisProject']);

	//decrypt the projects private key
	$result = $conn->query("SELECT privateKey, s.publicKey, s.symmetricKey FROM security_access LEFT JOIN security_projects s ON (s.projectID = optionalID AND s.outDated = 'FALSE')
	WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$x' AND userID = $userID AND security_access.outDated = 'FALSE' LIMIT 1");
	if($result && ($row = $result->fetch_assoc()) && $row['publicKey'] && $row['privateKey']){
		$keypair = base64_decode($privateKey).base64_decode($row['publicKey']);
		$cipher = base64_decode($row['privateKey']);
		$nonce = mb_substr($cipher, 0, 24, '8bit');
		$encrypted = mb_substr($cipher, 24, null, '8bit');
		try {
			$project_private = sodium_crypto_box_open($encrypted, $nonce, $keypair);
			$cipher_symmetric = base64_decode($row['symmetricKey']);
			$nonce = mb_substr($cipher_symmetric, 0, 24, '8bit');
			$project_symmetric = sodium_crypto_box_open(mb_substr($cipher_symmetric, 24, null, '8bit'), $nonce, $project_private.base64_decode($row['publicKey']));
		} catch(Exception $e){
			echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
		}
	} else {
		showError($conn->error." - No Access");
	}

	if(isset($_POST['saveGeneral'])){
		$hours = floatval(test_input($_POST['project_hours']));
		$hourlyPrice = floatval(test_input($_POST['project_hourlyPrice']));
		$status = isset($_POST['project_productive']) ? 'checked' : '';
		$field_1 = $field_2 = $field_3 = 'FALSE';
		if(isset($_POST['project_field_1'])){ $field_1 = 'TRUE'; }
		if(isset($_POST['project_field_2'])){ $field_2 = 'TRUE'; }
		if(isset($_POST['project_field_3'])){ $field_3 = 'TRUE'; }

		$conn->query("UPDATE projectData SET hours = '$hours', hourlyPrice = '$hourlyPrice', status='$status', field_1 = '$field_1', field_2 = '$field_2', field_3 = '$field_3' WHERE id = $x");
		if($conn->error){
			echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
		} else {
			echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
		}
	} elseif(!empty($_POST['saveReadWrite'])){
		$result = $conn->query("SELECT userID, access FROM relationship_project_user WHERE projectID = $x"); echo $conn->error;
		while($result && ($row = $result->fetch_assoc())){
			$newAccess = test_input($_POST['user_access_'.$row['userID']], 1);
			if($newAccess != $row['access']){
				$conn->query("UPDATE relationship_project_user SET access = '$newAccess' WHERE projectID = $x and userID = ".$row['userID']);
				if($conn->error){ showError($conn->error); } else { showSuccess($lang['SAVE']); }
			}
		}
		$result = $conn->query("SELECT userID, access FROM relationship_project_extern WHERE projectID = $x"); echo $conn->error;
		while($result && ($row = $result->fetch_assoc())){
			$newAccess = test_input($_POST['extern_access_'.$row['userID']], 1);
			if($newAccess != $row['access']){
				$conn->query("UPDATE relationship_project_extern SET access = '$newAccess' WHERE projectID = $x and userID = ".$row['userID']);
				if($conn->error){ showError($conn->error); } else { showSuccess($lang['SAVE']); }
			}
		}
	} elseif(isset($_POST['hire']) && $project_private){
		if(!empty($_POST['userID'])){
			$stmt = $conn->prepare("INSERT INTO relationship_project_user (projectID, userID, access, expirationDate) VALUES($x, ?, 'READ', '0000-00-00')"); echo $conn->error;
			$stmt->bind_param('i', $val);
			for($i = 0; $i < count($_POST['userID']); $i++){
				$val = intval($_POST['userID'][$i]);
				insert_access_user($x, $val, $project_private);
				$stmt->execute();
				echo $stmt->error;
			}
			$stmt->close();
		}
		if(!empty($_POST['externID'])){
			$stmt = $conn->prepare("INSERT INTO relationship_project_extern (projectID, userID, access, expirationDate) VALUES($x, ?, 'READ', '0000-00-00')"); echo $conn->error;
			$stmt->bind_param('i', $val);
			for($i = 0; $i < count($_POST['externID']); $i++){
				$val = intval($_POST['externID'][$i]);
				insert_access_user($x, $val, $project_private, 1);
				$stmt->execute();
				echo $stmt->error;
			}
			$stmt->close();
		}
    } elseif(!empty($_POST['removeUser'])){
		$val = intval($_POST['removeUser']);
		$conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$x' AND userID = $val");
		$conn->query("DELETE FROM relationship_project_user WHERE userID = $val AND projectID = $x");
		if($conn->error){
			echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
		} else {
			echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
		}
	} elseif(!empty($_POST['removeExtern'])){
		$val = intval($_POST['removeExtern']);
		$conn->query("DELETE FROM relationship_project_extern WHERE userID = $val AND projectID = $x");
		if($conn->error){
			echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
		} else {
			echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
		}
	} elseif($project_symmetric && !empty($_POST['add-new-file']) && isset($_FILES['new-file-upload'])){
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
				$file_encrypt = simple_encryption(file_get_contents($_FILES['new-file-upload']['tmp_name']), $project_symmetric);
				//$_FILES['file']['name']
				$s3->putObject(array(
					'Bucket' => $bucket,
					'Key' => $hashkey,
					'Body' => $file_encrypt
				));

				$filename = test_input($file_info['filename']);
				$conn->query("INSERT INTO project_archive (projectID, name, parent_directory, type, uniqID) VALUES ($x, '$filename', '$parent', '$ext', '$hashkey')");
				if($conn->error){ showError($conn->error); } else { showSuccess($lang['OK_UPLOAD']); }
			} catch(Exception $e){
				echo $e->getTraceAsString();
				echo '<br><hr><br>';
				echo $e->getMessage();
			}
		}
	} elseif(!empty($_POST['add-new-file'])){
		showError('No File Selected '.$_FILES['new-file-upload']['error']);
	} elseif(!empty($_POST['add-new-folder'])){
        $parent = test_input($_POST['add-new-folder']);
        if(!empty($_POST['new-folder-name'])){
            $name = test_input($_POST['new-folder-name']);
            $conn->query("INSERT INTO project_archive(projectID, name, parent_directory, type) VALUES ($x, '$name', '$parent', 'folder')");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_ADD']);
            }
        } else {
            showError($lang['ERROR_MISSING_FIELDS']);
        }
    }
} //endif post

if(!empty($_SESSION['external_id'])){
	$tableName = 'relationship_project_extern';
} else {
	$tableName = 'relationship_project_user';
}

if($isProjectAdmin == 'TRUE'){
	$result = $conn->query("SELECT id FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")");
	if(!$result || $result->num_rows <= 0){
	    echo '<div class="alert alert-info">'.$lang['WARNING_NO_CLIENTS'].'<br><br>';
	    echo '<a class="btn btn-warning" data-toggle="modal" data-target="#create_client">'.$lang['NEW_CLIENT_CREATE'].'</a>';
	    echo '</div>';
	    include dirname(__DIR__) . "/misc/new_client_buttonless.php";
	}
	$result_outer = $conn->query("SELECT p.name, p.id, p.status, clientData.companyID, clientData.name AS clientName, companyData.name AS companyName
        FROM projectData p INNER JOIN clientData ON clientData.id = p.clientID INNER JOIN companyData ON companyData.id = clientData.companyID WHERE 1 $query");
} else {
	$result_outer = $conn->query("SELECT p.name, p.id, p.status, companyData.name AS companyName, clientData.name AS clientName FROM $tableName t LEFT JOIN projectData p ON p.id = t.projectID
		INNER JOIN clientData ON clientData.id = p.clientID INNER JOIN companyData ON companyData.id = clientData.companyID WHERE t.userID = $userID");
}
echo $conn->error
?>
<div class="page-header h3"><?php echo $lang['PROJECTS']; ?>
	<div class="page-header-button-group">
		<?php include dirname(__DIR__) . '/misc/set_filter.php'; ?>
		<?php if($isProjectAdmin == 'TRUE'): ?>
			<button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-project" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
		<?php endif; ?>
	</div>
</div>

<form id="mainForm" method="POST">
    <table class="table table-hover">
        <thead>
            <th><?php echo $lang['COMPANY']; ?></th>
            <th><?php echo $lang['CLIENT']; ?></th>
			<th></th>
            <th><?php echo $lang['PROJECT']; ?></th>
            <th></th>
        </thead>
        <tbody>
			<?php
			 while($result_outer && ($row = $result_outer->fetch_assoc())){
				 $productive = $row['status'] == 'checked' ? '<i class="fa fa-tags"></i>' : '';
				 echo '<tr class="clicker" value="'.$row['id'].'">';
				 echo '<td>'.$row['companyName'] .'</td>';
				 echo '<td>'. $row['clientName'] .'</td>';
				 echo '<td>'.$productive.'</td>';
				 echo '<td>'. $row['name'] .'</td>';
				 echo '<td><button type="button" class="btn btn-default" name="editModal" value="'.$row['id'].'" ><i class="fa fa-pencil"></i></button></td>';
				 echo '</tr>';
			 }
			?>
		</tbody>
	</table>
</form>

<div id="editingModalDiv"></div>


<!-- ADD PROJECT -->
<form method="POST">
    <div class="modal fade add-project">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header"><h4><?php echo $lang['ADD']; ?></h4></div>
            <div class="modal-body">
                <?php include dirname(__DIR__) . '/misc/select_client.php'; ?>
                <br>
                <div class="col-sm-12">
                    <label>Name</label>
                    <input type=text class="form-control required-field" name='name' placeholder='Name'><br>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <label><?php echo $lang['HOURS']; ?></label>
                        <input type=number class="form-control" name='hours' step="any"><br>
                    </div>
                    <div class="col-md-6">
                        <label><?php echo $lang['HOURLY_RATE']; ?></label>
                        <input type=number class="form-control" name='hourlyPrice' step="any"><br>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <input type="checkbox" name="status" value="checked" checked> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?>
                    </div>
                    <div class="col-md-6">
                            <?php
                            $resF = $conn->query("SELECT * FROM $companyExtraFieldsTable WHERE companyID = $filterCompany ORDER BY id ASC");
                            if($resF->num_rows > 0){
                                $rowF = $resF->fetch_assoc();
                                if($rowF['isActive'] == 'TRUE'){
                                    $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                                    echo '<input type="checkbox" '.$checked.' name="createField_1"/>'. $rowF['name'];
                                }
                            }
                            if($resF->num_rows > 1){
                                $rowF = $resF->fetch_assoc();
                                if($rowF['isActive'] == 'TRUE'){
                                    $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                                    echo '<br><input type="checkbox" '.$checked.' name="createField_2" />'. $rowF['name'];
                                }
                            }
                            if($resF->num_rows > 2){
                                $rowF = $resF->fetch_assoc();
                                if($rowF['isActive'] == 'TRUE'){
                                    $checked = $rowF['isForAllProjects'] == 'TRUE' ? 'checked': '';
                                    echo '<br><input type="checkbox" '.$checked.' name="createField_3" />'. $rowF['name'];
                                }
                            }
                            ?>
                        </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name='add'> <?php echo $lang['ADD']; ?> </button>
            </div>
        </div>
    </div>
</form>

<script>
	var grandParent = [];
    var existingModals = new Array();
    function checkAppendModal(index){
        if(existingModals.indexOf(index) == -1){
            $.ajax({
                url:'ajaxQuery/AJAX_projectEditModal.php',
                data:{projectID: index},
                type: 'GET',
                success : function(resp){
                    $("#editingModalDiv").append(resp);
                    existingModals.push(index);
                    onPageLoad();
                },
                error : function(resp){},
                complete: function(resp){
                    if(index){
                        $('#editingModal-'+index).modal('show');
                    }
					grandParent[index] = ['ROOT'];
					$('.tree-node-back-'+index).click(function(){
						var grandPa = grandParent[index].pop();
						//alert(grandPa);
						$('#folder-'+index+'-'+ $(this).data('parent')).hide();
						$('#folder-'+index+'-'+ grandPa).fadeIn();
						$('.modal-new-'+index).val(grandPa);
					});
					$('.folder-structure-'+index).click(function(event){
						$('#folder-'+index+'-'+ $(this).data('parent')).hide();
						$('#folder-'+index+'-'+ $(this).data('child')).fadeIn();
						//alert('#folder-'+index+'-'+ $(this).data('child'));
						grandParent[index].push($(this).data('parent'));
						$('.modal-new-'+index).val($(this).data('child'));
					});
                }
            });
        } else {
            $('#editingModal-'+index).modal('show');
        }
    }

    $('.clicker').click(function(){
        checkAppendModal($(this).find('button[name=editModal]:first').val());
        event.stopPropagation();
    });

    $('.table').DataTable({
        autoWidth: false,
        order: [[ 2, "asc" ]],
        columns: [null, null, null, null, {orderable: false}],
        responsive: true,
        colReorder: true,
        language: {
            <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
        }
    });

	<?php
	if(isset($active_modal)){
        echo '$("button[name=\'editModal\'][value=\''.$active_modal.'\']").click();';
    }
	?>
</script>
<?php require dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; ?>