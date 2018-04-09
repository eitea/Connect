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


    if(isset($_POST['activate_encryption'])){
        if($config_row['activeEncryption'] == 'FALSE'){
            //activate encrytpion
            $accept = true;
            $key_downloads = array();
            $result = $conn->query("SELECT id FROM companyData"); echo $conn->error;
            if(!$result) $accept = false;
            while($result && ($row = $result->fetch_assoc())){
                $companyID = $row['id'];
                $keyPair = sodium_crypto_box_keypair();
                $private = sodium_crypto_box_secretkey($keyPair);
                $public = sodium_crypto_box_publickey($keyPair);
                $key_downloads[] = base64_encode($private)." \n".base64_encode($public);
                $conn->query("UPDATE companyData SET publicPGPKey = '".base64_encode($public)."' WHERE id = $companyID"); echo $conn->error;
                $nonce = random_bytes(24);
                $encrypted = $nonce . sodium_crypto_box($private, $nonce, $private.base64_decode($publicKey));
                $conn->query("INSERT INTO security_company(userID, companyID, privateKey) VALUES ($userID, 1, '".base64_encode($encrypted)."')"); echo $conn->error;
                if($conn->error) $accept = false;
            }
            if($accept && !empty($_POST['module_encrypt'])){ //module
                $stmt = $conn->prepare("INSERT INTO security_modules(module, publicPGPKey, symmetricKey) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $module, $public, $encrypted);
                $stmt_access = $conn->prepare("INSERT INTO security_access(userID, module, privateKey) VALUES(?, ?, ?)");
                $stmt_access->bind_param("iss", $access_user, $module, $access_private_encrypted);
                foreach($_POST['module_encrypt'] as $module){
                    $module = test_input($module, true);
                    $keyPair = sodium_crypto_box_keypair();
                    $private = sodium_crypto_box_secretkey($keyPair);
                    $public = sodium_crypto_box_publickey($keyPair);
                    $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
                    $nonce = random_bytes(24);

                    $encrypted = base64_encode($nonce . sodium_crypto_box($symmetric, $nonce, $private.$public));
                    $public = base64_encode($public);
                    $stmt->execute();
                    if($conn->error) $accept = false;

                    if($accept){ //access
                        secure_module($module, $symmetric);
                        $result = $conn->query("SELECT publicPGPKey, id FROM UserData LEFT JOIN roles ON userID = id WHERE publicPGPKey IS NOT NULL AND ".$query_access_modules[$module]);
                        while($row = $result->fetch_assoc()){
                            $user_public = base64_decode($row['publicPGPKey']);
                            $nonce = random_bytes(24);
                            $access_private_encrypted = base64_encode($nonce . sodium_crypto_box($private, $nonce, $private.$user_public));
                            $access_user = $row['id'];
                            $stmt_access->execute();
                            echo $conn->error;
                        }
                    } else {
                        echo $conn->error;
                    }
                }
                $stmt->close();
                $stmt_access->close();
                $conn->query("UPDATE configurationData SET activeEncryption = 'TRUE'");
            }
            if($accept){
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>O.K.</div>';
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Feature yet to be implemented</div>';
            //TODO: see if any modules were added, and encrypt those


            //TODO: see if modules were removed, and decrypt them.
        }
    }
    if(empty($_POST['activate_encryption']) && $config_row['activeEncryption'] == 'TRUE'){
        $result = $conn->query("SELECT module, privateKey FROM security_access WHERE userID = $userID AND outDated = 'FALSE' ORDER BY recentDate LIMIT 1");
        //decrypt modules user has access to
        while($result && ($row = $result->fetch_assoc())){
            if(array_key_exists($row['module'], $encrypted_modules)){
                    $cipher_private_module = base64_decode($row['privateKey']);
                    $result = $conn->query("SELECT publicPGPKey, symmetricKey FROM security_modules WHERE outDated = 'FALSE'");
                    if($result && ($row = $result->fetch_assoc())){
                        $public_module = base64_decode($row['publicPGPKey']);
                        $cipher_symmetric = base64_decode($row['symmetricKey']);
                        //decrypt access
                        $nonce = mb_substr($cipher_private_module, 0, 24, '8bit');
                        $cipher_private_module = mb_substr($cipher_private_module, 24, null, '8bit');
                        $private_module = sodium_crypto_box_open($cipher_private_module, $nonce, $privateKey.$public_module);
                        //decrypt module
                        $nonce = mb_substr($cipher_symmetric, 0, 24, '8bit');
                        $cipher_symmetric = mb_substr($cipher_symmetric, 24, null, '8bit');
                        $symmetric = sodium_crypto_box_open($cipher_symmetric, $nonce, $private_module.$public_module);

                        if($symmetric){
                            secure_module($row['module'], $symmetric);

                            $conn->query("UPDATE security_company SET outDated = 'TRUE' WHERE module = ".$row['module']);
                            $conn->query("UPDATE security_modules SET outDated = 'TRUE' WHERE module = ".$row['module']);
                            $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE module = ".$row['module']);
                        }
                    }
                }
            }
        }

        if(count($encrypted_modules) == 0) $conn->query("UPDATE configurationData SET activeEncryption = 'FALSE'");
        if(count($encrypted_modules) < $result->num_rows){
            showError('');
        }
    }

    if(!empty($_POST['saveRoles'])){
        $x = intval($_POST['saveRoles']);
        $isDSGVOAdmin = isset($_POST['isDSGVOAdmin']) ? 'TRUE' : 'FALSE';
        $isCoreAdmin = isset($_POST['isCoreAdmin']) ? 'TRUE' : 'FALSE';
        $isDynamicProjectsAdmin = isset($_POST['isDynamicProjectsAdmin']) ? 'TRUE' : 'FALSE';
        $isProjectAdmin = isset($_POST['isProjectAdmin']) ? 'TRUE' : 'FALSE';
        $isReportAdmin = isset($_POST['isReportAdmin']) ? 'TRUE' : 'FALSE';
        $isERPAdmin = isset($_POST['isERPAdmin']) ? 'TRUE' : 'FALSE';
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

        $conn->query("UPDATE roles SET isDSGVOAdmin = '$isDSGVOAdmin', isCoreAdmin = '$isCoreAdmin', isDynamicProjectsAdmin = '$isDynamicProjectsAdmin', isTimeAdmin = '$isTimeAdmin',
        isProjectAdmin = '$isProjectAdmin', isReportAdmin = '$isReportAdmin', isERPAdmin = '$isERPAdmin', isFinanceAdmin = '$isFinanceAdmin', canStamp = '$canStamp',
        canEditTemplates = '$canEditTemplates', canUseSocialMedia = '$canUseSocialMedia', canCreateTasks = '$canCreateTasks', canUseArchive = '$canUseArchive', canUseClients = '$canUseClients',
        canUseSuppliers = '$canUseSuppliers', canEditClients = '$canEditClients', canEditSuppliers = '$canEditSuppliers', canUseWorkflow = '$canUseWorkflow' WHERE userID = '$x'");

        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        }
    }
}

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
        <button type="submit" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>"><i class="fa fa-floppy-o"></i></button>
    </div></h3></div>

    <h4>Verschlüsselung <a role="button" data-toggle="collapse" href="#password_info_encryption"> <i class="fa fa-info-circle"> </i> </a></h4><br>
    <div class="collapse" id="password_info_encryption"><div class="well"><?php echo $lang['INFO_ENCRYPTION']; ?></div></div>

    <div class="row">
        <div class="col-sm-4"><label><input id="activate_encryption" type="checkbox" <?php if($config_row['activeEncryption'] == 'TRUE') echo 'checked'; ?> name="activate_encryption" value="1" /> Aktiv</label></div>
    </div>
    <div id="module-checkboxes" <?php if($config_row['activeEncryption'] == 'FALSE') echo 'style="display:none"'; ?> class="row">
        <div class="col-md-3"><label><input disabled type="checkbox" <?php if(array_key_exists('TIME', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="TIME" /> Zeiterfassung</label></div>
        <div class="col-md-3"><label><input disabled type="checkbox" <?php if(array_key_exists('PROJECT', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="PROJECT" /> Projekte</label></div>
        <div class="col-md-3"><label><input disabled type="checkbox" <?php if(array_key_exists('REPORT', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="REPORT" /> Berichte</label></div>
        <div class="col-md-3"><label><input type="checkbox" <?php if(array_key_exists('ERP', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="ERP" /> ERP</label></div>
        <div class="col-md-3"><label><input disabled type="checkbox" <?php if(array_key_exists('FINANCE', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="FINANCE" /> Finanzen</label></div>
        <div class="col-md-3"><label><input type="checkbox" <?php if(array_key_exists('DSGVO', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="DSGVO" /> DSGVO</label></div>
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
