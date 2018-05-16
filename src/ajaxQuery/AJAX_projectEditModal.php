<?php
require dirname(__DIR__) . DIRECTORY_SEPARATOR.'connection.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR.'utilities.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR.'/language.php';
require dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'aws'.DIRECTORY_SEPARATOR.'autoload.php';
$projectID = intval($_GET['projectID']);

session_start();
if(!empty($_SESSION['external_id'])){
	$userID = $_SESSION['external_id'];
    $tableName = 'security_external_access';
} else {
	$userID = $_SESSION["userid"] or die("0");
    $tableName = 'security_access';
	$privateKey = $_SESSION['privateKey'];
}

$result = $conn->query("SELECT isProjectAdmin FROM roles WHERE userID = $userID");
if ($result && ($row = $result->fetch_assoc())) {
	$isProjectAdmin = $row['isProjectAdmin'];
}

$result = $conn->query("SELECT p.*, c.companyID, s.publicKey, s.symmetricKey, c.name AS clientName FROM projectData p INNER JOIN clientData c ON p.clientID = c.id
INNER JOIN security_projects s ON s.projectID = p.id AND s.outDated = 'FALSE' WHERE p.id = $projectID LIMIT 1"); echo $conn->error;
$projectRow = $result->fetch_assoc();
if(!$projectRow) { include dirname(__DIR__).DIRECTORY_SEPARATOR.'footer.php'; die($lang['ERROR_UNEXPECTED']); }

if($projectRow['publicKey']){
    $result = $conn->query("SELECT privateKey FROM $tableName WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$projectID' AND userID = $userID AND outDated = 'FALSE' LIMIT 1");
    if($result && ($row = $result->fetch_assoc())){
        $keypair = base64_decode($privateKey).base64_decode($projectRow['publicKey']);
        $cipher = base64_decode($row['privateKey']);
        $nonce = mb_substr($cipher, 0, 24, '8bit');
        $encrypted = mb_substr($cipher, 24, null, '8bit');
        try {
            $project_private = sodium_crypto_box_open($encrypted, $nonce, $keypair);
            $cipher_symmetric = base64_decode($projectRow['symmetricKey']);
            $nonce = mb_substr($cipher_symmetric, 0, 24, '8bit');
            $project_symmetric = sodium_crypto_box_open(mb_substr($cipher_symmetric, 24, null, '8bit'), $nonce, $project_private.base64_decode($projectRow['publicKey']));
        } catch(Exception $e){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
        }
    } else {
		if($conn->error){
			echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Access Err: '.$conn->error.__LINE__.'</div>';
		} else {
			$result = $conn->query("SELECT privateKey FROM security_access WHERE module = 'PRIVATE_PROJECT' AND optionalID = '$projectID'");
			if($result->num_rows > 0){
				echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Sie besitzen keinen Zugriff auf dieses Projekt.Nur der Projektersteller kann Ihnen diesen Zugriff gewähren.</div><hr>';
			} else {
				//no one has access to this, but a keypair exists. re-key it.
				$keyPair = sodium_crypto_box_keypair();
				$new_private = sodium_crypto_box_secretkey($keyPair);
				$new_public = sodium_crypto_box_publickey($keyPair);
				$symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
				$projectRow['publicKey'] = base64_encode($new_public);
				$project_private = $new_private;
				$project_symmetric = $symmetric;
				$nonce = random_bytes(24);
				$symmetric_encrypted = base64_encode($nonce . sodium_crypto_box($symmetric, $nonce, $new_private.$new_public));
				$conn->query("UPDATE security_projects SET outDated = 'TRUE' WHERE projectID = $projectID"); echo $conn->error;
				$conn->query("INSERT INTO security_projects (projectID, publicKey, symmetricKey) VALUES ('$projectID', '".base64_encode($new_public)."', '$symmetric_encrypted')"); echo $conn->error;
				insert_access_user($projectID, $userID, $new_private);
				$conn->query("INSERT INTO relationship_project_user(projectID, userID, access) VALUES($projectID, $userID, 'WRITE')");
				showSuccess("Fehlender access wurde hinzugefügt.");
			}
		}
	}
	//if there is a public key, there is an access, there is an upload:
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
}
?>

<div id="editingModal-<?php echo $projectID; ?>" class="modal fade">
  <div class="modal-dialog modal-content modal-lg">
	<div class="modal-header h3"><?php echo $projectRow['clientName'].' - '.$projectRow['name']; ?></div>
	<div class="modal-body">
		<?php if($isProjectAdmin == 'TRUE'): ?>
			<form method="POST">
				<input type="hidden" name="saveGeneral" value="1" />
			    <?php if(!$projectRow['publicKey']) echo '<div class="alert alert-warning"><a href="#" data-dismiss="alert" class="close">&times;</a>
			    Dieses Projekt besitzt noch kein Schlüsselpaar. Zugriff wurde eingeschränkt. Um das Projekt absichern zu lassen, drücken Sie auf den Schloss-Button</div><hr>'; ?>
			    <h4>Allgemein
					<div class="page-header-button-group">
						<button type="submit" name="saveThisProject" value="<?php echo $projectID; ?>" class="btn btn-default"><i class="fa fa-floppy-o"></i></button>
					</div>
				</h4>
			    <br>
			    <div class="row form-group">
			        <div class="col-sm-2">Produktiv</div>
			        <div class="col-sm-10"><label><input type="checkbox" name="project_productive" <?php echo $projectRow['status']; ?> value="1" /> <i class="fa fa-tags"></i></label></div>
			    </div>
			    <div class="row form-group">
			        <div class="col-sm-2">Stunden</div>
			        <div class="col-sm-4"><label><input type="number" step="any" class="form-control" name="project_hours" value="<?php echo $projectRow['hours']; ?>" /></div>
			        <div class="col-sm-2">Stundenrate</div>
			        <div class="col-sm-4"><label><input type="number" step="any" class="form-control" name="project_hourlyPrice" value="<?php echo $projectRow['hourlyPrice']; ?>" /></div>
			    </div>
			    <div class="row form-group">
			        <div class="col-sm-2">Optionale Projektfelder</div>
			        <?php
			        $resF = $conn->query("SELECT isActive, name FROM $companyExtraFieldsTable WHERE companyID = ".$projectRow['companyID']." ORDER BY id ASC"); echo $conn->error;
			        if($resF->num_rows > 0){
			            $rowF = $resF->fetch_assoc();
			            if($rowF['isActive'] == 'TRUE'){
			                $checked = $projectRow['field_1'] == 'TRUE' ? 'checked': '';
			                echo '<div class="col-sm-3"><label><input type="checkbox" '.$checked.' name="project_field_1" /> '.$rowF['name'].'</label></div>';
			            }
			        }
			        if($resF->num_rows > 1){
			            $rowF = $resF->fetch_assoc();
			            if($rowF['isActive'] == 'TRUE'){
			                $checked = $projectRow['field_2'] == 'TRUE' ? 'checked': '';
			                echo '<div class="col-sm-3"><label><input type="checkbox" '.$checked.' name="project_field_2" /> '.$rowF['name'].'</label></div>';
			            }
			        }
			        if($resF->num_rows > 2){
			            $rowF = $resF->fetch_assoc();
			            if($rowF['isActive'] == 'TRUE'){
			                $checked = $projectRow['field_3'] == 'TRUE' ? 'checked': '';
			                echo '<div class="col-sm-3"><label><input type="checkbox" '.$checked.' name="project_field_3" /> '.$rowF['name'].'</label></div>';
			            }
			        }
			        $resF->free();
			        ?>
			    </div>
			</form>
		<?php endif; //$isProjectAdmin ?>
		<?php if(isset($project_private)): ?>
			<?php if($isProjectAdmin == 'TRUE'): ?>
			    <form method="POST" action="../setup/keys" target="_blank">
			        <div class="row form-group">
			            <div class="col-sm-2">
			                Public Key
			            </div>
			            <div class="col-sm-6">
			                <?php echo $projectRow['publicKey']; ?>
			            </div>
			            <div class="col-sm-4">
			                <input type="hidden" name="personal" value="<?php echo base64_encode($project_private)."\n".$projectRow['publicKey']; ?>" />
			                <button type="submit" class="btn btn-warning" name="">Schlüsselpaar Downloaden</button>
			            </div>
			        </div>
			    </form>
			    <br><hr>
				<form method="POST">
					<input type="hidden" name="saveThisProject" value="<?php echo $projectID; ?>" />
				    <h4>Benutzer <div class="page-header-button-group">
				        <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-member" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
						<button type="submit" name="saveReadWrite" value="<?php echo $projectID; ?>" class="btn btn-default"><i class="fa fa-floppy-o"></i></button>
				    </div></h4>
					<!--?php if($projectRow['creator']): ?>
						<div class="row">
							<div class="col-sm-2">Besitzer</div>
							<div class="col-sm-6"></div>
						</div>
					< ?php endif; ? -->
			        <div class="row">
			            <div class="col-sm-6">
			                <h5>Intern</h5>
			                <?php
							$access_select = '<option value="WRITE">Vollzugriff</option><option value="READ">Lesezugriff</option>'; //5af14c95ef1f0
			                $result = $conn->query("SELECT userID, firstname, lastname, access FROM relationship_project_user INNER JOIN UserData e ON userID = e.id WHERE projectID = $projectID"); echo $conn->error;
			                while($result && ($row = $result->fetch_assoc())){
			                    echo '<div class="col-sm-6"><button type="submit" name="removeUser" value="'.$row['userID'].'" class="btn btn-empty" title="Entfernen"><i class="fa fa-times" style="color:red"></i></button>';
			                    echo $row['firstname'].' '.$row['lastname'];
								echo '</div><div class="col-sm-6"><select name="user_access_'.$row['userID'].'" class="js-example-basic-single">'.
								str_replace('value="'.$row['access'].'"', 'selected value="'.$row['access'].'"', $access_select).'</select></div>';
			                }
			                ?>
			            </div>
			            <div class="col-sm-6">
			                <h5>Extern</h5>
			                <?php
			                $result = $conn->query("SELECT userID, firstname, lastname, access FROM relationship_project_extern INNER JOIN external_users e ON userID = e.id
			                INNER JOIN contactPersons c ON c.id = e.contactID WHERE projectID = $projectID"); echo $conn->error;
			                while($result && ($row = $result->fetch_assoc())){
			                    echo '<div class="col-sm-6"><button type="submit" name="removeExtern" value="'.$row['userID'].'" class="btn btn-empty" title="Entfernen"><i class="fa fa-times" style="color:red"></i></button>';
			                    echo $row['firstname'].' '.$row['lastname'];
								echo '</div><div class="col-sm-6"><select name="extern_access_'.$row['userID'].'" class="js-example-basic-single">'.
								str_replace('value="'.$row['access'].'"', 'selected value="'.$row['access'].'"', $access_select).'</select></div>';
			                }
			                ?>
			            </div>
			        </div>
			    </form>
			    <div class="modal fade add-member">
			        <div class="modal-dialog modal-content modal-md">
			            <form method="POST">
							<input type="hidden" name="saveThisProject" value="<?php echo $projectID; ?>" />
			                <div class="modal-header">Benutzer Hinzufügen</div>
			                <div class="modal-body">
			                    <div class="col-xs-12">
			                        <h4>Interne Benutzer</h4>
			                        <select class="js-example-basic-single" name="userID[]" multiple>
			                            <?php
			                            $res_addmem = $conn->query("SELECT id, firstname, lastname FROM UserData WHERE id NOT IN (SELECT DISTINCT userID FROM relationship_project_user WHERE projectID = $projectID)");
			                            while ($res_addmem && ($row_addmem = $res_addmem->fetch_assoc())) {
			                                echo '<option value="'.$row_addmem['id'].'" >'.$row_addmem['firstname'].' '.$row_addmem['lastname'].'</option>';
			                            }
			                            ?>
			                        </select>
			                        <hr>
			                    </div>
			                    <div class="col-xs-12">
			                        <h4>Externe Benutzer</h4>
			                        <select class="js-example-basic-single" name="externID[]" multiple>
			                            <?php
			                            $res_addmem = $conn->query("SELECT e.id, firstname, lastname FROM external_users e INNER JOIN contactPersons c ON c.id = e.contactID WHERE c.clientID = "
			                            .$projectRow['clientID']." AND e.id NOT IN (SELECT DISTINCT userID FROM relationship_project_extern WHERE projectID = $projectID)"); echo $conn->error;
			                            while ($res_addmem && ($row_addmem = $res_addmem->fetch_assoc())) {
			                                echo '<option value="'.$row_addmem['id'].'" >'.$row_addmem['firstname'].' '.$row_addmem['lastname'].'</option>';
			                            }
			                            ?>
			                        </select>
			                    </div>
			                </div>
			                <div class="modal-footer">
			                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
			                    <button type="submit" class="btn btn-warning" name="hire"><?php echo $lang['ADD']; ?></button>
			                </div>
			            </form>
			        </div>
			    </div>
			    <br><hr>
			<?php endif; //$isProjectAdmin ?>
			<?php if(!empty($s3)) : ?>
				<?php
				$result = $conn->query("SELECT name FROM folder_default_sturctures WHERE category = 'COMPANY' AND categoryID = '".$projectRow['companyID']."' AND name NOT IN
				( SELECT name FROM archive WHERE category = 'PROJECT' AND categoryID = '$projectID' AND parent_directory = 'ROOT') ");
				echo $conn->error;
				while($result && ($row = $result->fetch_assoc())){
					$conn->query("INSERT INTO archive(category, categoryID, name, parent_directory, type) VALUES('PROJECT', '$projectID', '".$row['name']."', 'ROOT', 'folder')"); echo $conn->error;
				}

				$upload_viewer = [
				'accessKey' => '',
				'category' => 'PROJECT',
				'categoryID' => $projectID
				];
				include dirname(__DIR__).DIRECTORY_SEPARATOR.'misc'.DIRECTORY_SEPARATOR.'upload_viewer.php';
				?>
			<?php else: ?>
				<h4>Dateifreigabe</h4>
				<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>
					Es konnte keine Verbindung zu einer S3 Schnittstelle hergestellt werden.
					Um den Dateiupload nutzen zu können, überprüfen Sie bitte Ihre Archiv Optionen
				</div>
			<?php endif; //s3 ?>
		<?php endif; //key ?>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
	</div>
  </div>
</div>
