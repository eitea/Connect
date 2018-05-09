<?php require dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID); ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
$activeTab = 0;
$query_access_modules = array(
    'DSGVO' => "isDSGVOAdmin = 'TRUE'",
    'ERP' => "isERPAdmin = 'TRUE'"
);
$result = $conn->query("SELECT activeEncryption FROM configurationData");
if(!$result) die($lang['ERROR_UNEXPECTED']);
$config_row = $result->fetch_assoc();

$all_modules = array('TIMES' => false, 'PROJECTS' => false, 'REPORTS' => false, 'ERP' => true, 'FINANCES' => false, 'DSGVO' => true); //setup/install_wizard.php
$encrypted_modules = array();
$result = $conn->query("SELECT DISTINCT module, outDated FROM security_modules WHERE outDated = 'FALSE'");
while($row = $result->fetch_assoc()){
    $encrypted_modules[$row['module']] = $row['outDated']; //save module as keys so array_key_exists() or isset() can be used (performance)
}
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    function secure_module($module, $symmetric, $decrypt = false){
        global $conn;
        if($module == 'DSGVO'){
            $stmt = $conn->prepare("UPDATE documents SET txt = ?, name = ? WHERE id = ?"); echo $stmt->error;
            $stmt->bind_param('sss', $text, $head, $id);
            $result = $conn->query("SELECT id, txt, name FROM documents"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $id = $row['id'];
                if($decrypt){
                    $text = simple_decryption($row['txt'], $symmetric);
                    $head = simple_decryption($row['name'], $symmetric);
                } else {
                    $text = simple_encryption($row['txt'], $symmetric);
                    $head = simple_encryption($row['name'], $symmetric);
                }
                $stmt->execute();
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE document_customs SET content = ? WHERE id = ?"); echo $stmt->error;
            $stmt->bind_param('si', $text, $id);
            $result = $conn->query("SELECT id, content FROM document_customs"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $id = $row['id'];
                if($decrypt){
                    $text = simple_decryption($row['content'], $symmetric);
                } else {
                    $text = simple_encryption($row['content'], $symmetric);
                }
                $stmt->execute();
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE dsgvo_vv_settings SET setting = ? WHERE id = ?"); echo $stmt->error;
            $stmt->bind_param('si', $text, $id);
            $result = $conn->query("SELECT id, setting FROM dsgvo_vv_settings"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $id = $row['id'];
                if($decrypt){
                    $text = simple_decryption($row['setting'], $symmetric);
                } else {
                    $text = simple_encryption($row['setting'], $symmetric);
                }
                $stmt->execute();
            }
            $stmt->close();
            echo $conn->error;

            $stmt = $conn->prepare("UPDATE dsgvo_vv_logs SET short_description = ?, long_description = ?, scope = ? WHERE id = ?"); echo $stmt->error;
            $stmt->bind_param('sssi', $short_description, $long_description, $scope, $id);
            $result = $conn->query("SELECT id, short_description, long_description, scope FROM documents"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $id = $row['id'];
                if($decrypt){
                    $short_description = simple_decryption($row['short_description'], $symmetric);
                    $long_description = simple_decryption($row['long_description'], $symmetric);
                    $scope = simple_decryption($row['scope'], $symmetric);
                } else {
                    $short_description = simple_encryption($row['short_description'], $symmetric);
                    $long_description = simple_encryption($row['long_description'], $symmetric);
                    $scope = simple_encryption($row['scope'], $symmetric);
                }
                $stmt->execute();
            }
            $stmt->close();


        } elseif($module == 'ERP'){
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ? WHERE id = ?"); echo $stmt->error;
            $stmt->bind_param('ssi', $name, $text, $id);
            $result = $conn->query("SELECT id, name, description FROM products"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $id = intval($row['id']);
                if($decrypt){
                    $name = simple_decryption($row['name'], $symmetric);
                    $text = simple_decryption($row['description'], $symmetric);
                } else {
                    $name = simple_encryption($row['name'], $symmetric);
                    $text = simple_encryption($row['description'], $symmetric);
                }
                $stmt->execute();
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE articles SET name = ?, description = ? WHERE id = ?"); echo $stmt->error;
            $stmt->bind_param('ssi', $name, $text, $id);
            $result = $conn->query("SELECT id, name, description FROM articles"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $id = intval($row['id']);
                if($decrypt){
                    $name = simple_decryption($row['name'], $symmetric);
                    $text = simple_decryption($row['description'], $symmetric);
                } else {
                    $name = simple_encryption($row['name'], $symmetric);
                    $text = simple_encryption($row['description'], $symmetric);
                }
                $stmt->execute();
            }
            $stmt->close();
        }
    }

    if(isset($_POST['saveSecurity'])){
        if(isset($_POST['activate_encryption']) && $config_row['activeEncryption'] == 'FALSE'){
            $key_downloads = array();
            $result = $conn->query("SELECT id FROM companyData"); echo $conn->error;
            if(!$result) $accept = false;
            while($result && ($row = $result->fetch_assoc())){
				$keyPair = sodium_crypto_box_keypair();
                $private = sodium_crypto_box_secretkey($keyPair);
                $public = sodium_crypto_box_publickey($keyPair);
                $key_downloads[] = base64_encode($private)." \n".base64_encode($public);
				$symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
				$nonce = random_bytes(24);
				$symmetric_encrypted = $nonce . sodium_crypto_box($symmetric, $nonce, $private.$public);
                $conn->query("INSERT INTO security_company (companyID, publicKey, symmetricKey) VALUES (".$row['id'].", '".base64_encode($public)."', '".base64_encode($symmetric_encrypted)."') ");
                $nonce = random_bytes(24);
                $encrypted = $nonce . sodium_crypto_box($private, $nonce, $private.base64_decode($publicKey));
                $conn->query("INSERT INTO security_access(userID, module, optionalID, privateKey) VALUES ($userID, 'COMPANY',".$row['id']." , '".base64_encode($encrypted)."')");
                if($conn->error) $accept = false;
            }
        }
        if(isset($_POST['activate_encryption']) && !empty($_POST['module_encrypt'])){
            //see if modules were added and encrypt those
            $stmt = $conn->prepare("INSERT INTO security_modules(module, publicKey, symmetricKey) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $module, $public, $encrypted);
            $stmt_access = $conn->prepare("INSERT INTO security_access(userID, module, privateKey) VALUES(?, ?, ?)");
            $stmt_access->bind_param("iss", $access_user, $module, $access_private_encrypted);
            foreach($_POST['module_encrypt'] as $module){
                $module = test_input($module, true);
                if(!array_key_exists($module, $encrypted_modules)){
                    $accept = true;
                    $keyPair = sodium_crypto_box_keypair();
                    $private = sodium_crypto_box_secretkey($keyPair);
                    $public = sodium_crypto_box_publickey($keyPair);
                    $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
                    $nonce = random_bytes(24);

                    $encrypted = base64_encode($nonce . sodium_crypto_box($symmetric, $nonce, $private.$public));
                    $public = base64_encode($public);
                    $stmt->execute();
                    if($stmt->error) $accept = false;

                    if($accept){ //access
                        secure_module($module, $symmetric);
                        $result = $conn->query("SELECT s.publicKey, s.userID FROM security_users s LEFT JOIN roles ON s.userID = roles.userID WHERE publicKey IS NOT NULL AND ".$query_access_modules[$module]);
                        while($row = $result->fetch_assoc()){
                            $user_public = base64_decode($row['publicKey']);
                            $nonce = random_bytes(24);
                            $access_private_encrypted = base64_encode($nonce . sodium_crypto_box($private, $nonce, $private.$user_public));
                            $access_user = $row['userID'];
                            $stmt_access->execute();
                            echo $stmt_access->error;
                        }
                        $encrypted_modules[$row['module']] = 'FALSE'; //saves outDated value
                        showSuccess($module . ' was encrypted');
                    } else {
                        showError($stmt->error);
                    }
                }
            }
            $stmt->close();
            $stmt_access->close();
            $conn->query("UPDATE configurationData SET activeEncryption = 'TRUE'");
            if($conn->error){
                showError($conn->error);
            } elseif($config_row['activeEncryption'] == 'FALSE') {
                $config_row['activeEncryption'] = 'TRUE';
                showSuccess('Verschlüsselung wurde aktiviert');
            }
        }

        //see if modules were removed, and decrypt them
        $temp = array();
        if(isset($_POST['module_encrypt'])){
            foreach($_POST['module_encrypt'] as $module){
                $temp[$module] = true;
            }
        }
        foreach($all_modules as $module => $val){
            //if module is encrypted but is now un-checked
            if(array_key_exists($module, $encrypted_modules) && !array_key_exists($module, $temp)){
				//decrypt module user has access to
                $result = $conn->query("SELECT module, privateKey FROM security_access WHERE userID = $userID AND outDated = 'FALSE' AND module = '$module' ORDER BY recentDate LIMIT 1");
                if($result && ($row = $result->fetch_assoc()) && array_key_exists($module, $encrypted_modules)){
                    $cipher_private_module = base64_decode($row['privateKey']);
                    $result = $conn->query("SELECT publicKey, symmetricKey FROM security_modules WHERE module = '$module' AND outDated = 'FALSE'");
                    if($result && ($row = $result->fetch_assoc())){
                        $public_module = base64_decode($row['publicKey']);
                        $cipher_symmetric = base64_decode($row['symmetricKey']);
                        //access
                        $nonce = mb_substr($cipher_private_module, 0, 24, '8bit');
                        $cipher_private_module = mb_substr($cipher_private_module, 24, null, '8bit');
                        $private_module = sodium_crypto_box_open($cipher_private_module, $nonce, base64_decode($privateKey).$public_module);
                        //module
                        $nonce = mb_substr($cipher_symmetric, 0, 24, '8bit');
                        $cipher_symmetric = mb_substr($cipher_symmetric, 24, null, '8bit');
                        $symmetric = sodium_crypto_box_open($cipher_symmetric, $nonce, $private_module.$public_module);

                        if($symmetric){
                            secure_module($module, $symmetric, 1); //decrypt

                            $conn->query("UPDATE security_modules SET outDated = 'TRUE' WHERE module = '$module'");
                            $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = '$module'");

                            unset($encrypted_modules[$module]);
                            showSuccess($module . ' was decrypted');
                        }
                    }
                } else {
                    if($conn->error) showError($conn->error);
                    showError("Module Access not available; you cannot decrypt this module");
                }
            }
        } //endfor
        if(count($encrypted_modules) == 0){
            $conn->query("UPDATE configurationData SET activeEncryption = 'FALSE'");
            $config_row['activeEncryption'] = 'FALSE';
        }
    }

    if(!empty($_POST['saveRoles'])){
        $activeTab = $x = intval($_POST['saveRoles']);
        $isDSGVOAdmin = 'FALSE';
        $isCoreAdmin = isset($_POST['isCoreAdmin']) ? 'TRUE' : 'FALSE';
        $isDynamicProjectsAdmin = isset($_POST['isDynamicProjectsAdmin']) ? 'TRUE' : 'FALSE';
        $isProjectAdmin = isset($_POST['isProjectAdmin']) ? 'TRUE' : 'FALSE';
        $isReportAdmin = isset($_POST['isReportAdmin']) ? 'TRUE' : 'FALSE';
        $isERPAdmin = 'FALSE';
        $isFinanceAdmin = isset($_POST['isFinanceAdmin']) ? 'TRUE' : 'FALSE';
        $canStamp = isset($_POST['canStamp']) ? 'TRUE' : 'FALSE';
        $canEditTemplates = isset($_POST['canEditTemplates']) ? 'TRUE' : 'FALSE';
        $canUseSocialMedia = isset($_POST['canUseSocialMedia']) ? 'TRUE' : 'FALSE';
        $canCreateTasks = isset($_POST['canCreateTasks']) ? 'TRUE' : 'FALSE';
        $canUseArchive = isset($_POST['canUseArchive']) ? 'TRUE' : 'FALSE';
        $canUseClients = isset($_POST['canUseClients']) ? 'TRUE' : 'FALSE';
        $canUseSuppliers = isset($_POST['canUseSuppliers']) ? 'TRUE' : 'FALSE';
        $canEditClients = isset($_POST['canEditClients']) ? 'TRUE' : 'FALSE';
        $canEditSuppliers = isset($_POST['canEditSuppliers']) ? 'TRUE' : 'FALSE';
        $canUseWorkflow = isset($_POST['canUseWorkflow']) ? 'TRUE' : 'FALSE'; //5ab7ae7596e5c

		if(isset($_POST['company'])){
			$result = $conn->query("SELECT id FROM companyData");
			while($row = $result->fetch_assoc()){
				//just completely delete the relationship from table to avoid duplicate entries.
				$conn->query("DELETE FROM $companyToUserRelationshipTable WHERE userID = $x AND companyID = " . $row['id']);
				if(in_array($row['id'], $_POST['company'])){  //if company is checked, insert again
					$conn->query("INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES (".$row['id'].", $x)");
				}
			}
		}

		if(isset($_POST['isDSGVOAdmin'])){
			$isDSGVOAdmin = 'TRUE';
			$result = $conn->query("SELECT u.publicKey, module FROM security_users u LEFT JOIN security_access a ON a.userID = u.userID
				AND module = 'DSGVO' AND a.outDated = 'FALSE' WHERE u.userID = $x AND u.outDated = 'FALSE'");
			if($result && ($row = $result->fetch_assoc()) && !$row['module']){
				$user_public = base64_decode($row['publicKey']);
				//grant which can be granted
				$result = $conn->query("SELECT s.privateKey, m.publicKey FROM security_access s, security_modules m WHERE m.outDated = 'FALSE' AND m.module = 'DSGVO'
					AND s.userID = $userID AND s.outDated = 'FALSE' AND s.module = 'DSGVO' LIMIT 1");
				if($result && ($row = $result->fetch_assoc()) && array_key_exists('DSGVO', $encrypted_modules)){

					$cipher_private_module = base64_decode($row['privateKey']);
					$nonce = mb_substr($cipher_private_module, 0, 24, '8bit');
        			$cipher_private_module = mb_substr($cipher_private_module, 24, null, '8bit');
					$private = sodium_crypto_box_open($cipher_private_module, $nonce, base64_decode($privateKey).base64_decode($row['publicKey']));

		            $nonce = random_bytes(24);
		            $access_private_encrypted = base64_encode($nonce . sodium_crypto_box($private, $nonce, $private.$user_public));

					$conn->query("INSERT INTO security_access(userID, module, privateKey) VALUES($x, 'DSGVO', '$access_private_encrypted')");
					if($conn->error){
						showError($conn->error);
					} else {
						showSuccess("DSGVO Schlüssel wurde hinzugefügt");
					}
				} else {
					showError($conn->error);
				}
			} else {
				showError($conn->error);
			}
		} else {
			$conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'DSGVO' AND userID = $x");
		}

		if(isset($_POST['isERPAdmin'])){
			$isERPAdmin = 'TRUE';
			$result = $conn->query("SELECT u.publicKey, module FROM security_users u LEFT JOIN security_access a ON a.userID = u.userID
				AND module = 'ERP' AND a.outDated = 'FALSE' WHERE u.userID = $x AND u.outDated = 'FALSE'");
			if($result && ($row = $result->fetch_assoc()) && !$row['module']){
				$user_public = base64_decode($row['publicKey']);
				//grant which can be granted
				$result = $conn->query("SELECT s.privateKey, m.publicKey FROM security_access s, security_modules m
					WHERE m.outDated = 'FALSE' AND m.module = 'ERP'
					AND s.userID = $userID AND s.outDated = 'FALSE' AND s.module = 'ERP' LIMIT 1");
				if($result && ($row = $result->fetch_assoc()) && array_key_exists('ERP', $encrypted_modules)){

					$cipher_private_module = base64_decode($row['privateKey']);
					$nonce = mb_substr($cipher_private_module, 0, 24, '8bit');
        			$cipher_private_module = mb_substr($cipher_private_module, 24, null, '8bit');
					$private = sodium_crypto_box_open($cipher_private_module, $nonce, base64_decode($privateKey).base64_decode($row['publicKey']));

		            $nonce = random_bytes(24);
		            $access_private_encrypted = base64_encode($nonce . sodium_crypto_box($private, $nonce, $private.$user_public));

					$conn->query("INSERT INTO security_access(userID, module, privateKey) VALUES($x, 'ERP', '$access_private_encrypted')");
					if($conn->error){
						showError($conn->error);
					} else {
						showSuccess("ERP Schlüssel wurde hinzugefügt");
					}
				} else {
					showError($conn->error);
				}
			} else {
				showError($conn->error);
			}
		} else {
			$conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'ERP' AND userID = $x");
		}

        $conn->query("UPDATE roles SET isDSGVOAdmin = '$isDSGVOAdmin', isCoreAdmin = '$isCoreAdmin', isDynamicProjectsAdmin = '$isDynamicProjectsAdmin', isTimeAdmin = '$isTimeAdmin',
        isProjectAdmin = '$isProjectAdmin', isReportAdmin = '$isReportAdmin', isERPAdmin = '$isERPAdmin', isFinanceAdmin = '$isFinanceAdmin', canStamp = '$canStamp',
        canEditTemplates = '$canEditTemplates', canUseSocialMedia = '$canUseSocialMedia', canCreateTasks = '$canCreateTasks', canUseArchive = '$canUseArchive', canUseClients = '$canUseClients',
        canUseSuppliers = '$canUseSuppliers', canEditClients = '$canEditClients', canEditSuppliers = '$canEditSuppliers', canUseWorkflow = '$canUseWorkflow' WHERE userID = '$x'");

        if($conn->error){
            showError($conn->error);
        } else {
            showSuccess($lang['OK_SAVE']);
        }
    }
}

$selection_company  = '';
$result = $conn->query("SELECT id, name FROM companyData");
while($row = $result->fetch_assoc()){
    $selection_company .= '<div class="col-md-3"><label><input type="checkbox" name="company[]" value="'.$row['id'].'" />' . $row['name'] .'</label><br></div>';
}

$stmt_company_relationship = $conn->prepare("SELECT companyID FROM relationship_company_client WHERE userID = ?");
$stmt_company_relationship->bind_param('i', $x);

if(!empty($key_downloads)){
    echo '<form method="POST" target="_blank" action="../setup/keys">';
    foreach($key_downloads as $dKey){
        echo '<input type="hidden" name="company[]" value="'.$dKey.'" >';
    }
    echo '<button type="submit" class="btn btn-warning">Schlüssel Herunterladen</button>';
    echo '</form>';
}
?>

<form method="POST">
    <div class="page-header"><h3>Security Einstellungen  <div class="page-header-button-group">
        <button type="submit" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>" name="saveSecurity"><i class="fa fa-floppy-o"></i></button>
    </div></h3></div>

    <h4>Verschlüsselung <a role="button" data-toggle="collapse" href="#password_info_encryption"> <i class="fa fa-info-circle"> </i> </a></h4><br>
    <div class="collapse" id="password_info_encryption"><div class="well"><?php echo $lang['INFO_ENCRYPTION']; ?></div></div>

    <div class="row">
        <div class="col-sm-4"><label><input id="activate_encryption" type="checkbox" <?php if($config_row['activeEncryption'] == 'TRUE') echo 'checked'; ?> name="activate_encryption" value="1" /> Aktiv</label></div>
    </div>
    <div id="module-checkboxes" <?php if($config_row['activeEncryption'] == 'FALSE') echo 'style="display:none"'; ?> class="row">
        <?php
        foreach($all_modules as $module => $val){
            $disabled = $val ? '' : 'disabled';
            $checked = ($val && array_key_exists($module, $encrypted_modules)) ? 'checked' : '';
            $lang[$module] = isset($lang[$module]) ? $lang[$module] : $module;
            echo '<div class="col-md-3"><label><input '.$disabled.' type="checkbox" '.$checked.' name="module_encrypt[]" value="'.$module.'" />'.$lang[$module].'</label></div>';
        }
        ?>
    </div>
</form>

<br><hr>
<h4>Benutzer Verwaltung</h4><br>

<div class="container-fluid panel-group" id="accordion">
    <?php
    $result = $conn->query("SELECT * FROM roles");
    while ($result && ($row = $result->fetch_assoc())):
        $x = $row['userID'];
        ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading<?php echo $x; ?>">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $x; ?>"><?php echo $userID_toName[$x]; ?></a>
                </h4>
            </div>
            <div id="collapse<?php echo $x; ?>" class="panel-collapse collapse <?php if($x == $activeTab) echo 'in'; ?>">
                <div class="panel-body">
                    <form method="POST">
						<h4><?php echo $lang['COMPANIES']; ?>:</h4>
						<div class="row checkbox">
							<?php
							$selection_company_checked = $selection_company;
							$stmt_company_relationship->execute();
							$result_relation = $stmt_company_relationship->get_result();
							while($row_relation = $result_relation->fetch_assoc()){
								$needle = 'value="'.$row_relation['companyID'].'"';
								if(strpos($selection_company_checked, $needle) !== false){
									$selection_company_checked = str_replace($needle, $needle.' checked ', $selection_company_checked);
								}
							}
							echo $selection_company_checked;
							?>
							<div class="col-md-12"><small>*<?php echo $lang['INFO_COMPANYLESS_USERS']; ?></small></div>
						</div>
                        <h4><?php echo $lang['ADMIN_MODULES']; ?></h4>
                        <div class="row checkbox">
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isCoreAdmin" <?php if($row['isCoreAdmin'] == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_CORE_OPTIONS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isTimeAdmin" <?php if($row['isTimeAdmin'] == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_TIME_OPTIONS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isProjectAdmin" <?php if($row['isProjectAdmin'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['PROJECTS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isReportAdmin" <?php if($row['isReportAdmin'] == 'TRUE'){echo 'checked';} ?>  /><?php echo $lang['REPORTS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isERPAdmin" <?php if($row['isERPAdmin'] == 'TRUE'){echo 'checked';} ?> />ERP
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isDynamicProjectsAdmin" <?php if($row['isDynamicProjectsAdmin'] == 'TRUE'){echo 'checked';} ?>><?php echo $lang['DYNAMIC_PROJECTS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isFinanceAdmin" <?php if($row['isFinanceAdmin'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['FINANCES']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="isDSGVOAdmin" <?php if($row['isDSGVOAdmin'] == 'TRUE'){echo 'checked';} ?> />DSGVO
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canEditClients" <?php if($row['canEditClients'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_EDIT_CLIENTS']; ?>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canEditSuppliers" <?php if($row['canEditSuppliers'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_EDIT_SUPPLIERS']; ?>
                                </label>
                            </div>
                        </div>
                        <br><h4><?php echo $lang['USER_MODULES']; ?></h4>
                        <div class="row checkbox">
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canStamp" <?php if($row['canStamp'] == 'TRUE'){echo 'checked';} ?>><?php echo $lang['CAN_CHECKIN']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canBook" <?php if($row['canBook'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_BOOK']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canEditTemplates" <?php if($row['canEditTemplates'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_EDIT_TEMPLATES']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseSocialMedia" <?php if($row['canUseSocialMedia'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_SOCIAL_MEDIA']; ?>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canCreateTasks" <?php if($row['canCreateTasks'] == 'TRUE'){echo 'checked';} ?>/><?php echo $lang['CAN_CREATE_TASKS']; ?>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseArchive" <?php if($row['canUseArchive'] == 'TRUE'){echo 'checked';} ?>/><?php echo $lang['CAN_USE_ARCHIVE']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseClients" <?php if($row['canUseClients'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_CLIENTS']; ?>
                                </label><br>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseSuppliers" <?php if($row['canUseSuppliers'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_SUPPLIERS']; ?>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="canUseWorkflow" <?php if($row['canUseWorkflow'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_WORKFLOW']; ?>
                                </label>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-sm-2 col-sm-offset-10 text-right">
                                <button type="submit" name="saveRoles" value="<?php echo $x; ?>" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div><br>
    <?php endwhile; ?>
</div>

<script>
$('#activate_encryption').change(function(){
    if(this.checked){
        $('#module-checkboxes').show();
    } else {
        $('#module-checkboxes').hide();
    }
});
</script>
<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
