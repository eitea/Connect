<?php require dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
$activeTab = 0;
$query_access_modules = array(
    'DSGVO' => "groups.name = 'DSGVO'",
    'ERP' => "groups.name = 'ERP'"
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

$result = $conn->query("SELECT teamID, userID FROM relationship_team_user");
echo $conn->error;
while($result && $row = $result->fetch_assoc()){
    $relationship_team_user[$row["teamID"]][] = $row["userID"];
}

//echo '<pre>', print_r($grantable_modules, 1),'</pre>';

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    if(!empty($_POST['saveDefaultRoles']) || !empty($_POST['saveDefaultRolesAndApply'])) { // default roles for new users
        $stmt_insert_permission_relationship = $conn->prepare("INSERT INTO default_access_permissions (permissionID) VALUES (?)");
        echo $conn->error;
        $stmt_insert_permission_relationship->bind_param("i", $permissionID);
        $conn->query("DELETE FROM default_access_permissions");
        foreach ($_POST as $key => $type) { // type is always 'TRUE'
            if(str_starts_with("PERMISSION", $key)){
                $arr = explode(";", $key);
                $permissionID = intval($arr[2]);
                $stmt_insert_permission_relationship->execute();
                echo $stmt_insert_permission_relationship->error;
            }
        }
        $stmt_insert_permission_relationship->close();
        Permissions::update_cache_default();
        if(!empty($_POST['saveDefaultRolesAndApply'])){
            $result = $conn->query("SELECT id FROM UserData");
            while($result && $row = $result->fetch_assoc()){
                Permissions::apply_defaults($row["id"]);
            }
        }
    }

    if(!empty($_POST['saveRoles'])) {
        $x = intval($_POST['saveRoles']);
        if($x != 1){
            $stmt_insert_permission_relationship = $conn->prepare("INSERT INTO relationship_access_permissions (userID, permissionID) VALUES (?, ?)");
            echo $conn->error;
            $stmt_insert_permission_relationship->bind_param("ii", $x, $permissionID);
            $conn->query("DELETE FROM relationship_access_permissions WHERE userID = $x");
            foreach ($_POST as $key => $type) { // type is always 'TRUE'
                if(str_starts_with("PERMISSION", $key)){
                    $arr = explode(";", $key);
                    $permissionID = intval($arr[2]);
                    $stmt_insert_permission_relationship->execute();
                    echo $stmt_insert_permission_relationship->error;
                }
            }
            $stmt_insert_permission_relationship->close();
        }
        Permissions::update_cache_user($x);
    }

    if(!empty($_POST['saveTeamRoles'])) {
        $x = intval($_POST['saveTeamRoles']);
        $stmt_insert_permission_relationship = $conn->prepare("INSERT INTO relationship_team_access_permissions (teamID, permissionID) VALUES (?, ?)");
        echo $conn->error;
        $stmt_insert_permission_relationship->bind_param("ii", $x, $permissionID);
        $conn->query("DELETE FROM relationship_team_access_permissions WHERE teamID = $x");
        foreach ($_POST as $key => $type) { // type is always 'TRUE'
            if(str_starts_with("PERMISSION", $key)){
                $arr = explode(";", $key);
                $permissionID = intval($arr[2]);
                $stmt_insert_permission_relationship->execute();
                echo $stmt_insert_permission_relationship->error;
            }
        }
        $stmt_insert_permission_relationship->close();
        Permissions::update_cache_team($x);
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

        $inherit_team_permissions = isset($_POST['inherit_team_permissions']) ? 'TRUE' : 'FALSE';
        $conn->query("UPDATE UserData SET inherit_team_permissions = '$inherit_team_permissions' WHERE id = $x");
        Permissions::update_cache_relationship_user_team();

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
    }

    if(!empty($_POST['saveRoles']) || !empty($_POST['saveTeamRoles']) || !empty($_POST['saveDefaultRolesAndApply'])){
        if(!empty($_POST['saveRoles'])){
            $x = intval($_POST['saveRoles']);
            $remove_permission_stmt = $conn->prepare("DELETE FROM relationship_access_permissions WHERE userID = $x AND permissionID IN (SELECT permission.id FROM access_permissions permission INNER JOIN access_permission_groups groups ON groups.id = permission.groupID WHERE groups.name = ?)");
            echo $conn->error;
            $remove_permission_stmt->bind_param('s', $group_to_remove);
            $user_array[] = $x;
        }else if (!empty($_POST['saveTeamRoles'])){
            $x = intval($_POST['saveTeamRoles']);
            $remove_permission_stmt = $conn->prepare("DELETE FROM relationship_team_access_permissions WHERE teamID = $x AND permissionID IN (SELECT permission.id FROM access_permissions permission INNER JOIN access_permission_groups groups ON groups.id = permission.groupID WHERE groups.name = ?)");
            echo $conn->error;
            $remove_permission_stmt->bind_param('s', $group_to_remove);
            foreach ($relationship_team_user[$x] as $uid) {
                $user_array[] = $uid;
            }
        } else {
            // default permissions won't be changed if a user doesn't have a key
            $remove_permission_stmt = $conn->prepare("DELETE FROM relationship_access_permissions WHERE userID = ? AND permissionID IN (SELECT permission.id FROM access_permissions permission INNER JOIN access_permission_groups groups ON groups.id = permission.groupID WHERE groups.name = ?)");
            echo $conn->error;
            $remove_permission_stmt->bind_param('is', $uid, $group_to_remove);
            $result = $conn->query("SELECT id from UserData");
            while($result && $row = $result->fetch_assoc()){
                $user_array[] = $row["id"];
            }
        }
        foreach ($user_array as $uid){ // either do this just for the user where the permissions have changed or for all users of a team where permissions have changed
            if(Permissions::has_any("DSGVO",$uid) /* test if the user has any DSGVO read or write permissions */){
                if(grantAccess('DSGVO', $uid) !== "TRUE"){
                    // remove permission if user doesn't have a key
                    $group_to_remove = "DSGVO";
                    $remove_permission_stmt->execute();
                    showError($conn->error);
                }
            } else {
                $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'DSGVO' AND userID = $uid");
            }
            if(Permissions::has_any("ERP",$uid)) {
                if(grantAccess('ERP', $uid) !== "TRUE"){
                    // remove permission if user doesn't have a key
                    $group_to_remove = "ERP";
                    $remove_permission_stmt->execute();
                    showError($conn->error);
                }
            } else {
                $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'ERP' AND userID = $uid");
            }

            if(isset($_POST['hasTaskAccess'])){
                //TODO: see if user has access already, and if not, and you can grant it, grant it.
				grantAccess('TASK', $uid);
            } else {
                $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'TASK' AND userID = $uid");
            }
            if(isset($_POST['hasChatAccess'])){
    			grantAccess('CHAT', $uid);
            } else {
                $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = 'CHAT' AND userID = $uid");
            }

            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_SAVE']);
            }
            Permissions::update_cache_user($uid);
        }
        if(!empty($_POST['saveTeamRoles'])){
            Permissions::update_cache_team(intval($_POST['saveTeamRoles']));
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
    <div class="page-header"><h3>Security Einstellungen <div class="page-header-button-group">
        <button type="submit" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>" name="saveSecurity"><i class="fa fa-floppy-o"></i></button>
</div></h3></div>

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

<?php

$result = $conn->query("SELECT groups.name group_name, perm.name permission_name, groups.id group_id, perm.id permission_id FROM access_permission_groups groups INNER JOIN access_permissions perm ON groups.id = perm.groupID");
$permission_name_to_ids = [];
while($result && $row = $result->fetch_assoc()){
    $permission_name_to_ids[$row["group_name"]][$row["permission_name"]] = ["group_id"=>$row["group_id"], "permission_id" => $row["permission_id"]];
}

$collapse_counter = 0;

/**
 * Creates a permission tree
 *
 * @param array $permission_groups
 * @param string $name
 * @param int|false $x teamID, userID or neither
 * @param boolean $children_disabled
 * @param "USER"|"TEAM"|"DEFAULT" $mode
 * @return string
 */
function create_collapse_tree($permission_groups, $name, $x, $children_disabled = false, $mode = "USER", &$children_checked_count = 0, &$children_count = 0, &$children_checked_team_count = 0)
{
    global $collapse_counter;
    global $permission_name_to_ids;
    global $grantable_modules;
    global $lang;
    $children_checked_count = 0;
    $children_checked_team_count = 0;
    $children_count = 0;
    $collapse_counter++;
    $id = "permissionCollapseListGroup$collapse_counter";
    $child_groups = "";
    $children = "";
    $toolbar_expand = $toolbar = "";
    if ($name == "DSGVO" && /*array_key_exists('DSGVO', $encrypted_modules)&&*/ !array_key_exists('DSGVO', $grantable_modules)) {
        $children_disabled = true;
    } else if ($name == "ERP" && /*array_key_exists('ERP', $encrypted_modules)&&*/ !array_key_exists('ERP', $grantable_modules)) {
        $children_disabled = true;
    }
    foreach ($permission_groups as $key => $value) {
        if (is_array($value)) {
            $child_groups .= create_collapse_tree($value, $key, $x, $children_disabled, $mode, $children_checked_count_temp, $children_count_temp, $children_checked_team_count_temp);
            $children_checked_count += $children_checked_count_temp;
            $children_count += $children_count_temp;
            $children_checked_team_count += $children_checked_team_count_temp;
            $toolbar_expand = "<a title='Alles ausklappen' data-toggle='tooltip' data-expand-all='#$id' role='button' style='float:right'> <i class='fa fa-fw fa-expand'></i> </a>"; // only show expand if item has children that can be expanded
        } else {
            if ($mode == "USER" ? Permissions::has_user("$name.$value", $x) : ($mode == "TEAM" ? Permissions::has_team("$name.$value", $x) : Permissions::has_default("$name.$value"))) {
                $checked = "checked";
                $children_checked_count++;
            } else {
                $checked = "";
            }
            $children_count++;
            $note = "";
            if ($mode == "USER" && !Permissions::has_user("$name.$value", $x) && Permissions::has("$name.$value", $x)) {
                $note = "<i class='fa fa-check-square-o'></i> (Von Team geerbt)";
                $children_checked_team_count++;
            }
            $group_id = $permission_name_to_ids[$name][$value]["group_id"];
            $permission_id = $permission_name_to_ids[$name][$value]["permission_id"];
            $checkbox_name = "PERMISSION;$group_id;$permission_id";
            if (($x == 1 && $mode == "USER") || $children_disabled) {
                $disabled = "disabled";
            } else {
                $disabled = "";
            }
            $display_name = isset($lang[$value]) ? $lang[$value] : ucwords(strtolower($value));
            $children .= "<li class='list-group-item'><input data-permission-name='$value' $checked $disabled name='$checkbox_name' value='TRUE' type='checkbox'>$display_name $note</li>";
        }
    }
    if (!($x == 1 && $mode == "USER")) {
        $toolbar .= " <a title='Alles aktivieren' data-toggle='tooltip' data-check-all='#$id' role='button' style='float:right'> <i class='fa fa-fw fa-check-square-o'></i> </a>";
        $toolbar .= " <a title='Alles deaktivieren' data-toggle='tooltip' data-uncheck-all='#$id' role='button' style='float:right'> <i class='fa fa-fw fa-square-o'></i> </a>";
    }
    $permission_counter = " <span class='badge' title='Anzahl der Berechtigungen' data-toggle='tooltip' data-count-all='#$id' style='float: left'>$children_checked_count / $children_count</span>&nbsp;";
    if($children_checked_team_count){
        $permission_counter .= "<span class='pull-left' >&nbsp;&nbsp;&nbsp;</span> <span class='badge' title='Vom Team geerbte Berechtigungen' data-toggle='tooltip' style='float: left'>$children_checked_team_count</span>&nbsp;";
    }
    $display_name = isset($lang[$name]) ? $lang[$name] : ucwords(strtolower($name));
    return "
    <div class='panel-group' role='tablist' style='margin:0'>
        <div class='panel panel-default'>
            <div class='panel-heading' role='tab'>
                <h4 class='panel-title'>$permission_counter <a href='#$id' class='' role='button' data-toggle='collapse'> $display_name </a> $toolbar $toolbar_expand </h4>
            </div>
            <div class='panel-collapse collapse' role='tabpanel' id='$id' style='margin-left: 20px'>
                <ul class='list-group'>
                    $children
                </ul>
                <div >
                    $child_groups
                </div>
            </div>
        </div>
    </div>";
}

?>
<h4>Benutzer Verwaltung</h4><br>


<div class="container-fluid panel-group" id="accordion">
    <?php
    $result = $conn->query("SELECT id FROM UserData");
    while ($result && ($row = $result->fetch_assoc())):
        $x = $row['id'];
		$res_access = $conn->query("SELECT module FROM security_access WHERE userID = $x AND outDated = 'FALSE'")->fetch_all();
		$hasAccessTo = array_column($res_access, 0);
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
                        <h4>Berechtigungen </h4>
                            <div class="col-xs-12">
                                <label>
                                    <input type="checkbox" name="inherit_team_permissions" <?php if($x == 1){echo 'disabled';} ?> <?php if(Permissions::user_inherits_team_permissions($x)){echo 'checked';} ?>>Berechtigungen von Team erben
                                </label><br>
                            </div>
                            <br />
                            <br />
                            <?php
                                echo create_collapse_tree(Permissions::$permission_groups, "PERMISSIONS", $x);
                            ?>
						<br><h4>Keys</h4>
                        <div class="row checkbox">
                            <div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="hasTaskAccess" <?php if(in_array('TASK', $hasAccessTo)){echo 'checked';} //TODO: optimize performance ?>>Tasks
                                </label><br>
                            </div>
							<div class="col-md-3">
                                <label>
                                    <input type="checkbox" name="hasChatAccess" <?php if(in_array('CHAT', $hasAccessTo)){echo 'checked';} ?>>Messenger
                                </label><br>
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

<h4>Team Verwaltung</h4><br>


<div class="container-fluid panel-group" id="accordion-team">
    <?php

    $result = $conn->query("SELECT id teamID, name, isDepartment FROM teamData");
    while ($result && ($row = $result->fetch_assoc())):
        $x = $row['teamID'];
        $name = $row["name"];
        $isDepartment = $row["isDepartment"] == "TRUE";
        ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="teamHeading<?php echo $x; ?>">
                <h4 class="panel-title">
                    <a role="button" data-toggle="collapse" data-parent="#accordion-team" href="#teamCollapse<?php echo $x; ?>"><?php echo $name ?> </a> <?php if($isDepartment) echo '<small style="padding-left:35px;color:green;">Abteilung</small>'; ?>
                </h4>
            </div>
            <div id="teamCollapse<?php echo $x; ?>" class="panel-collapse collapse <?php if($x == $activeTab) echo 'in'; ?>">
                <div class="panel-body">
                    <form method="POST">
                        <div class="row">
                        <?php
                            foreach ($relationship_team_user[$x] as $uid) {
                                echo "<div class='col-md-6 col-xs-12' >";
                                echo $userID_toName[$uid];
                                echo "</div>";
                            }
                            ?>
                        </div>
                        <?php
                        echo create_collapse_tree(Permissions::$permission_groups, "PERMISSIONS", $x, false, "TEAM");
                        ?>
                        <div class="row">
                            <div class="col-sm-2 col-sm-offset-10 text-right">
                                <button type="submit" name="saveTeamRoles" value="<?php echo $x; ?>" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div><br>
    <?php endwhile; ?>
</div>

<h4>Andere Berechtigungen</h4><br>

<div class="container-fluid panel-group" id="accordion-default">
    <div class="panel panel-default">
        <div class="panel-heading" role="tab">
            <h4 class="panel-title">
                <a role="button" data-toggle="collapse" data-parent="#accordion-default" href="#defaultCollapse">Neue Benutzer</a>
            </h4>
        </div>
        <div id="defaultCollapse" class="panel-collapse collapse">
            <div class="panel-body">
                <small>Das Übernehmen für alle Benutzer überschreibt bereits vorhandene Berechtigungen nicht, sondern fügt nur die neuen hinzu.</small>
                <form method="POST">
                    <?php
                    echo create_collapse_tree(Permissions::$permission_groups, "PERMISSIONS", false, false, "DEFAULT");
                    ?>
                    <div class="row">
                        <div class="col-xs-12 text-right">
                            <button type="submit" name="saveDefaultRolesAndApply" value="TRUE" class="btn btn-warning"><?php echo $lang['SAVE']; ?> und auf alle Benutzer anwenden</button>
                            <button type="submit" name="saveDefaultRoles" value="TRUE" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$("[data-permission-name]").change(function(){
    var currentPermissionName = $(this).data("permission-name");
    var checked = $(this).prop("checked");
    $(this).closest(".list-group").find("[data-permission-name]").each(function(idx,elem){
        var otherPermissionName = $(elem).data("permission-name");
        if(checked){
            if(currentPermissionName == "ADMIN"){
                $(this).prop("checked",true);
            }
            if(currentPermissionName == "WRITE" && otherPermissionName == "READ"){
                $(this).prop("checked",true);
            }
            if(currentPermissionName == "BOOK" && otherPermissionName == "STAMP"){
                $(this).prop("checked",true);
            }
        }else{
            if(currentPermissionName == "READ" && (otherPermissionName == "ADMIN" || otherPermissionName == 'WRITE')){
                $(this).prop("checked",false);
            }
            if(currentPermissionName == "WRITE" && otherPermissionName == "ADMIN"){
                $(this).prop("checked",false);
            }
            if(currentPermissionName == "USE" && otherPermissionName == "ADMIN"){
                $(this).prop("checked", false);
            }
            if(currentPermissionName == "STAMP" && otherPermissionName == "BOOK"){
                $(this).prop("checked", false);
            }
        }
    });
})

$('[href*="permissionCollapseListGroup"]').click(function(){
    $(this).closest(".panel-group").parent().find('[id*="permissionCollapseListGroup"]').not(this).collapse("hide") // closes all sibling collapses
})
$('[data-expand-all*="permissionCollapseListGroup"]').click(function(){
    $(this).closest(".panel-group").find('[id*="permissionCollapseListGroup"]').not(this).collapse("show") // closes all sibling collapses
})
$('[data-check-all*="permissionCollapseListGroup"]').click(function(){
    $(this).closest(".panel-group").find("[data-permission-name]").prop("checked", true).trigger('change');
})
$('[data-uncheck-all*="permissionCollapseListGroup"]').click(function(){
    $(this).closest(".panel-group").find("[data-permission-name]").prop("checked", false).trigger('change');
})
$('[data-count-all*="permissionCollapseListGroup"]').each(function(){
    var $badge = $(this);
    $badge.closest(".panel-group").find("[data-permission-name]").change(function(){
        setTimeout(function(){
            var checkedCount = $badge.closest(".panel-group").find("[data-permission-name]:checked").length;
            var totalCount = $badge.closest(".panel-group").find("[data-permission-name]").length;
            $badge.html(checkedCount + " / " + totalCount);
        }, 10)
    })
}) 

$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
</script>


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
