<?php require dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php require_permission("READ","CORE","SECURITY") ?>
<?php
$activeTab = 0;
$query_access_modules = array(
    'DSGVO' => "groups.name = 'DSGVO' AND (r.type = 'READ' OR r.type = 'WRITE')",
    'ERP' => "groups.name = 'ERP' AND (r.type = 'READ' OR r.type = 'WRITE')"
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

$grantable_modules = array();
$result = $conn->query("SELECT a.module, a.privateKey, m.publicKey FROM security_access a
	LEFT JOIN security_modules m ON m.module = a.module AND m.outDated = 'FALSE' WHERE a.outDated = 'FALSE' AND a.userID = $userID");
while($row = $result->fetch_assoc()){
    $grantable_modules[$row['module']]['private'] = $row['privateKey'];
	$grantable_modules[$row['module']]['public'] = $row['publicKey'];
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && has_permission("WRITE","CORE","SECURITY")){

    if(!empty($_POST['saveRoles'])) {
        $x = intval($_POST['saveRoles']);
        if($x != 1){
            $stmt_insert_permission_relationship = $conn->prepare("INSERT INTO relationship_access_permissions (userID, permissionID, type) VALUES (?, ?, ?)");
            echo $conn->error;
            $stmt_insert_permission_relationship->bind_param("iis", $x, $permissionID, $type);
            $conn->query("DELETE FROM relationship_access_permissions WHERE userID = $x");
            foreach ($_POST as $key => $type) {
                if(str_starts_with("PERMISSION", $key)){
                    $arr = explode(";", $key);
                    // $groupID = intval($arr[1]);
                    $permissionID = intval($arr[2]);
                    $stmt_insert_permission_relationship->execute();
                    echo $stmt_insert_permission_relationship->error;
                }
            }
            $stmt_insert_permission_relationship->close();
        }
    }

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

	function grantAccess($module, $user){
		global $conn;
		global $grantable_modules;
		global $encrypted_modules;
		global $privateKey;
		$result = $conn->query("SELECT u.publicKey, module FROM security_users u LEFT JOIN security_access a ON a.userID = u.userID
			AND module = '$module' AND a.outDated = 'FALSE' WHERE u.userID = $user AND u.outDated = 'FALSE'");
		if($result && ($row = $result->fetch_assoc()) && !$row['module']){
			$user_public = base64_decode($row['publicKey']);
			//grant which can be granted
			if(array_key_exists($module, $grantable_modules) && array_key_exists($module, $encrypted_modules)){
				$cipher_private_module = base64_decode($grantable_modules[$module]['private']);
				try{
					$nonce = mb_substr($cipher_private_module, 0, 24, '8bit');
					$cipher_private_module = mb_substr($cipher_private_module, 24, null, '8bit');
					$private = sodium_crypto_box_open($cipher_private_module, $nonce, base64_decode($privateKey).base64_decode($grantable_modules[$module]['public']));
					$nonce = random_bytes(24);
					$access_private_encrypted = base64_encode($nonce . sodium_crypto_box($private, $nonce, $private.$user_public));
				} catch (Exception $e){
					echo $e;
					//echo '<br>length:',strlen(base64_decode($privateKey).base64_decode($grantable_modules[$module]['public']));
					return 'FALSE';
				}

				$conn->query("INSERT INTO security_access(userID, module, privateKey) VALUES($user, '$module', '$access_private_encrypted')");
				if($conn->error){
					showError($conn->error.__LINE__);
					return 'FALSE';
				} else {
					showSuccess("$module Schlüssel wurde hinzugefügt");
				}
			} elseif(array_key_exists($module, $encrypted_modules) && !array_key_exists($module, $grantable_modules)) {
				showInfo("Fehlende $module Verschlüsselung: Sie können keinen Modulzugriff gewähren, zudem Sie selbst keinen Zugriff besitzen.");
				return 'FALSE';
			}
		} else {
			if($conn->error){
				showError($conn->error.__LINE__);
			} elseif($result->num_rows < 1) {
				showError("Nicht registrierter Benutzer: Dieser Benutzer muss sich zuerst einloggen");
				return 'FALSE';
			}
		}
		return 'TRUE';
	}
	if(!empty($_POST['session_timer'])){
		$sessionTimer = round($_POST['session_timer'], 2);
		$conn->query("UPDATE configurationData SET sessionTime = $sessionTimer");
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
                        $result = $conn->query("SELECT s.publicKey, s.userID FROM security_users s LEFT JOIN relationship_access_permissions r ON s.userID = r.userID LEFT JOIN access_permissions access ON r.permissionID = access.id LEFT JOIN access_permission_groups groups ON access.groupID = groups.id WHERE publicKey IS NOT NULL AND ".$query_access_modules[$module]." GROUP BY s.userID");
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
        $isDynamicProjectsAdmin = isset($_POST['isDynamicProjectsAdmin']) ? 'TRUE' : 'FALSE';
		$isTimeAdmin = isset($_POST['isTimeAdmin']) ? 'TRUE' : 'FALSE';
        $isProjectAdmin = isset($_POST['isProjectAdmin']) ? 'TRUE' : 'FALSE';
        $isReportAdmin = isset($_POST['isReportAdmin']) ? 'TRUE' : 'FALSE';
        $isFinanceAdmin = isset($_POST['isFinanceAdmin']) ? 'TRUE' : 'FALSE';
        $canStamp = isset($_POST['canStamp']) ? 'TRUE' : 'FALSE';
		$canBook = isset($_POST['canBook']) ? 'TRUE' : 'FALSE'; //5afc1e6e44373
        $canEditTemplates = isset($_POST['canEditTemplates']) ? 'TRUE' : 'FALSE';
        $canUseSocialMedia = isset($_POST['canUseSocialMedia']) ? 'TRUE' : 'FALSE';
        $canCreateTasks = isset($_POST['canCreateTasks']) ? 'TRUE' : 'FALSE';
        $canUseArchive = isset($_POST['canUseArchive']) ? 'TRUE' : 'FALSE';
        $canUseClients = isset($_POST['canUseClients']) ? 'TRUE' : 'FALSE';
        $canUseSuppliers = isset($_POST['canUseSuppliers']) ? 'TRUE' : 'FALSE';
        $canEditClients = isset($_POST['canEditClients']) ? 'TRUE' : 'FALSE';
        $canEditSuppliers = isset($_POST['canEditSuppliers']) ? 'TRUE' : 'FALSE';
        $canUseWorkflow = isset($_POST['canUseWorkflow']) ? 'TRUE' : 'FALSE'; //5ab7ae7596e5c
		$canSendToExtern = isset($_POST['canSendToExtern']) ? 'TRUE' : 'FALSE';

		//TODO: we need to grant access to these too.
		$conn->query("DELETE FROM relationship_company_client WHERE userID = $x");
		if(isset($_POST['company'])){
			$result = $conn->query("SELECT id FROM companyData");
			while($row = $result->fetch_assoc()){
				//just completely delete the relationship from table to avoid duplicate entries.
				$conn->query("DELETE FROM relationship_company_client WHERE userID = $x AND companyID = " . $row['id']);
				if(in_array($row['id'], $_POST['company'])){  //if company is checked, insert again
					$conn->query("INSERT INTO relationship_company_client (companyID, userID) VALUES (".$row['id'].", $x)");
				}
			}
        }
        $remove_permission_stmt = $conn->prepare("DELETE FROM relationship_access_permissions WHERE userID = $x AND permissionID IN (SELECT permission.id FROM access_permissions permission INNER JOIN access_permission_groups groups ON groups.id = permission.groupID WHERE groups.name = ?)");
        echo $conn->error;
        $remove_permission_stmt->bind_param('s', $group_to_remove);
        // if(isset($_POST['isDSGVOAdmin'])){
		if(has_permission("READ", "DSGVO",false,$x) /* test if the user has any DSGVO read or write permissions */){
            if(grantAccess('DSGVO', $x) !== "TRUE"){
                echo "test1";
                // remove permission if user doesn't have a key
                $group_to_remove = "DSGVO";
                $remove_permission_stmt->execute();
                showError($conn->error);
            }
		} else {
			$conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'DSGVO' AND userID = $x");
		}
		if(has_permission("READ", "ERP",false,$x)) {
			if(grantAccess('ERP', $x) !== "TRUE"){
                echo "test2";
                // remove permission if user doesn't have a key
                $group_to_remove = "ERP";
                $remove_permission_stmt->execute();
                showError($conn->error);
            }
		} else {
			$conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'ERP' AND userID = $x");
        }
        $isDSGVOAdmin = has_permission("READ", "DSGVO",false,$x) ? 'TRUE' : 'FALSE'; // remove when all permissions are updated
        $isERPAdmin = has_permission("READ", "ERP",false,$x) ? 'TRUE' : 'FALSE'; // remove when all permissions are updated
        $isCoreAdmin = has_permission("READ", "CORE",false,$x) ? 'TRUE' : 'FALSE'; // remove when all permissions are updated

		$conn->query("UPDATE roles SET isDSGVOAdmin = '$isDSGVOAdmin', isCoreAdmin = '$isCoreAdmin', isDynamicProjectsAdmin = '$isDynamicProjectsAdmin',
        isTimeAdmin = '$isTimeAdmin', isProjectAdmin = '$isProjectAdmin', isReportAdmin = '$isReportAdmin', isERPAdmin = '$isERPAdmin', canBook = '$canBook',
        isFinanceAdmin = '$isFinanceAdmin', canStamp = '$canStamp', canEditTemplates = '$canEditTemplates', canEditClients = '$canEditClients',
        canUseSocialMedia = '$canUseSocialMedia', canCreateTasks = '$canCreateTasks', canUseArchive = '$canUseArchive', canUseClients = '$canUseClients',
        canUseSuppliers = '$canUseSuppliers', canEditSuppliers = '$canEditSuppliers', canUseWorkflow = '$canUseWorkflow', canSendToExtern = '$canSendToExtern'
        WHERE userID = '$x'");

		if(isset($_POST['hasTaskAccess'])){
			//TODO: see if user has access already, and if not, and you can grant it, grant it.
		} else {
			$conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'TASK' AND userID = $x");
		}
		if(isset($_POST['hasChatAccess'])){

		} else {
			$conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'CHAT' AND userID = $x");
		}

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
    <div class="page-header"><h3>Security Einstellungen  <?php if(has_permission("WRITE","CORE","SECURITY")): ?><div class="page-header-button-group">
        <button type="submit" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>" name="saveSecurity"><i class="fa fa-floppy-o"></i></button>
</div><?php endif; ?></h3></div>

    <h4>Verschlüsselung <a role="button" data-toggle="collapse" href="#password_info_encryption"> <i class="fa fa-info-circle"></i></a></h4><br>
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
	<hr>
	<h4>Session Time <a href="#session_time_info" data-toggle="collapse"> <i class="fa fa-info-circle"></i></a></h4><br>
	<div class="collapse" id="session_time_info">
		<div class="well">Der Session timer definiert die maximale Zeit, die ein Benutzer ohne Unterbrechung eingeloggt bleiben darf. Wird in Stunden angegeben.</div>
	</div>
	<div class="row">
        <div class="col-sm-4"><input type="number" step="0.5" name="session_timer" value="<?php echo $sessionTimer; ?>" class="form-control" /></div>
    </div>

</form>

<br><hr>
<?php require "security_settings_tree.php"; ?>

<script>
$('#activate_encryption').change(function(){
    if(this.checked){
        $('#module-checkboxes').show();
    } else {
        $('#module-checkboxes').hide();
    }
});
</script>
<?php if(!has_permission("WRITE","CORE","SECURITY")): ?>
<script>
$('#bodyContent .affix-content input').prop("disabled", true); // disable all input on this page (not header)
</script>
<?php endif; ?>
<?php require dirname(dirname(__DIR__)) . '/footer.php'; ?>
