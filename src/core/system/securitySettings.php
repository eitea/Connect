<?php require dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
$activeTab = 0;

$result = $conn->query("SELECT activeEncryption FROM configurationData");
if(!$result) die($lang['ERROR_UNEXPECTED']);
$config_row = $result->fetch_assoc();

$encrypted_modules = array();
$result = $conn->query("SELECT DISTINCT module FROM security_modules WHERE outDated = 'FALSE'");
while($row = $result->fetch_assoc()){
    $encrypted_modules[$row['module']] = $row['outDated'];
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(empty($_POST['activate_encryption']) && $config_row['activeEncryption'] == 'TRUE'){
        //you can only deactivate if you can decrypt every module.
        $result = $conn->query("SELECT privateKey FROM security_access WHERE userID = $userID AND outDated = 'FALSE'");
        if(count($encrypted_modules) > $result->num_rows){
            
        }
        $conn->query("UPDATE security_company SET outDated = 'TRUE'");
        $conn->query("UPDATE security_modules SET outDated = 'TRUE'");
        $conn->query("UPDATE security_access SET outDated = 'TRUE'");

        $conn->query("UPDATE configurationData SET activeEncryption = 'FALSE'");
    }
    if(isset($_POST['activate_encryption'])){
        if($config_row['activeEncryption'] == 'FALSE'){
            //activate encrytpion
            $accept = true;
            $key_downloads = array();
            foreach($available_companies as $companyID){
                $keyPair = sodium_crypto_box_keypair();
                $private = sodium_crypto_box_secretkey($keyPair);
                $public = sodium_crypto_box_publickey($keyPair);
                $key_downloads['company_'.$companyID] = base64_encode($private)." \n".base64_encode($public);
                $conn->query("UPDATE companyData SET publicPGPKey = '".base64_encode($public)."' WHERE id = $companyID"); echo $conn->error;
                $nonce = random_bytes(24);
                $encrypted = $nonce . sodium_crypto_box($private, $nonce, $private.$user_public);
                $conn->query("INSERT INTO security_company(userID, companyID, privateKey) VALUES ($userID, 1, '".base64_encode($encrypted)."')"); echo $conn->error;
                if($conn->error) $accept = false;
            }
            if($accept && !empty($_POST['module_encrypt'])){ //module
                $stmt = $conn->prepare("INSERT INTO security_modules(module, publicPGPKey, symmetricKey) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $module, $public, $encrypted);
                foreach($_POST['module_encrypt'] as $module){
                    $module = test_input($module, true);
                    $keyPair = sodium_crypto_box_keypair();
                    $private = sodium_crypto_box_secretkey($keyPair);
                    $public = sodium_crypto_box_publickey($keyPair);
                    $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
                    $nonce = random_bytes(24);

                    //$conn->query("INSERT INTO security_modules(module, publicPGPKey, symmetricKey) VALUES ('DSGVO', '".base64_encode($public)."', '".base64_encode($encrypted)."')");
                    $encrypted = $nonce . sodium_crypto_box($symmetric, $nonce, $private.$public);
                    $public = base64_encode($public);
                    $stmt->execute();
                    if($conn->error) $accept = false;

                    if($accept){ //access
                        //TODO: foreach user with access to corresponding module, encrypt key with public key.
                        $result = $conn->query("SELECT * FROM roles");
                        while($row = $result->fetch_assoc()){
                        }

                        $nonce = random_bytes(24);
                        $encrypted = $nonce . sodium_crypto_box($private, $nonce, $private.$user_public);
                        $conn->query("INSERT INTO security_access(userID, module, privateKey) VALUES ($userID, 'DSGVO', '".base64_encode($encrypted)."')");
                        if($conn->error) $accept = false;
                    } else {
                        $err .= $conn->error;
                    }



                }
                $stmt->close();
            }
        } else {

        }
    }

    if(!empty($_POST['saveRoles'])){
        $x = intval($_POST['saveRoles']);
        if(isset($_POST['isDSGVOAdmin'])){
            $conn->query("UPDATE $roleTable SET isDSGVOAdmin = 'TRUE' WHERE userID = '$x'");
        } else {
            $conn->query("UPDATE $roleTable SET isDSGVOAdmin = 'FALSE' WHERE userID = '$x'");
        }
        if(isset($_POST['isCoreAdmin'])){
            $sql = "UPDATE $roleTable SET isCoreAdmin = 'TRUE' WHERE userID = $x";
        } else {
            $sql = "UPDATE $roleTable SET isCoreAdmin = 'FALSE' WHERE userID = $x";
        }
        $conn->query($sql);
        if(isset($_POST['isDynamicProjectsAdmin'])){
            $sql = "UPDATE $roleTable SET isDynamicProjectsAdmin = 'TRUE' WHERE userID = $x";
        } else {
            $sql = "UPDATE $roleTable SET isDynamicProjectsAdmin = 'FALSE' WHERE userID = $x";
        }
        $conn->query($sql);
        if(isset($_POST['isTimeAdmin'])){
            $sql = "UPDATE $roleTable SET isTimeAdmin = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET isTimeAdmin = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['isProjectAdmin'])){
            $sql = "UPDATE $roleTable SET isProjectAdmin = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET isProjectAdmin = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['isReportAdmin'])){
            $sql = "UPDATE $roleTable SET isReportAdmin = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET isReportAdmin = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['isERPAdmin'])){
            $sql = "UPDATE $roleTable SET isERPAdmin = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET isERPAdmin = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['isFinanceAdmin'])){
            $sql = "UPDATE $roleTable SET isFinanceAdmin = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET isFinanceAdmin = 'FALSE' WHERE userID = '$x'";
        }$conn->query($sql);
        if(isset($_POST['canStamp'])){
            $sql = "UPDATE $roleTable SET canStamp = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canStamp = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canStamp']) && isset($_POST['canBook'])){
            $sql = "UPDATE $roleTable SET canBook = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canBook = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canEditTemplates'])){
            $sql = "UPDATE $roleTable SET canEditTemplates = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canEditTemplates = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canUseSocialMedia'])){
            $sql = "UPDATE $roleTable SET canUseSocialMedia = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canUseSocialMedia = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canCreateTasks'])){
            $sql = "UPDATE $roleTable SET canCreateTasks = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canCreateTasks = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canUseArchive'])){
            $sql = "UPDATE $roleTable SET canUseArchive = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canUseArchive = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canUseClients'])){
            $sql = "UPDATE $roleTable SET canUseClients = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canUseClients = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canUseSuppliers'])){
            $sql = "UPDATE $roleTable SET canUseSuppliers = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canUseSuppliers = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canEditClients'])){
            $sql = "UPDATE $roleTable SET canEditClients = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canEditClients = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
        if(isset($_POST['canEditSuppliers'])){
            $sql = "UPDATE $roleTable SET canEditSuppliers = 'TRUE' WHERE userID = '$x'";
        } else {
            $sql = "UPDATE $roleTable SET canEditSuppliers = 'FALSE' WHERE userID = '$x'";
        }
        $conn->query($sql);
    }
}
?>

<?php
if(!empty($key_downloads)){
    //TODO: present keys for download
}
?>

<form method="POST">
    <div class="page-header"><h3>Security Einstellungen  <div class="page-header-button-group">
        <button type="submit" class="btn btn-default" title="<?php echo $lang['SAVE']; ?>"><i class="fa fa-floppy-o"></i></button>
    </div></h3></div>

    <h4>Verschlüsselung <a role="button" data-toggle="collapse" href="#password_info_encryption"> <i class="fa fa-info-circle"> </i> </a></h4><br>
    <div class="collapse" id="password_info_encryption"><div class="well"><?php echo $lang['INFO_ENCRYPTION']; ?></div></div>

    <div class="row">
        <div class="col-sm-4"><label><input id="activate_encryption" type="checkbox" <?php if($config_row['activeEncryption'] == 'TRUE') echo 'checked'; ?> name="activate_encryption" value="0" /> Aktiv</label></div>
    </div>
    <div id="module-checkboxes" <?php if($config_row['activeEncryption'] == 'FALSE') echo 'style="display:none"'; ?> class="row">
        <div class="col-md-3"><label><input disabled type="checkbox" <?php if(array_key_exists('TIME', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="TIME" /> Zeiterfassung</label></div>
        <div class="col-md-3"><label><input disabled type="checkbox" <?php if(array_key_exists('PROJECT', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="PROJECT" /> Projekte</label></div>
        <div class="col-md-3"><label><input disabled type="checkbox" <?php if(array_key_exists('REPORT', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="REPORT" /> Berichte</label></div>
        <div class="col-md-3"><label><input disabled type="checkbox" <?php if(array_key_exists('ERP', $encrypted_modules)) echo 'checked'; ?> name="module_encrypt[]" value="ERP" /> ERP</label></div>
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
                                     <input type="checkbox" name="isProjectAdmin" <?php if($row['isProjectAdmin'] == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?>
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
