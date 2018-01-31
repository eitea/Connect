<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID);?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
//just encrypt the values from current to new, MasterCrypt will handle the masterPass settings
function mc_update_values($current, $new, $statement = '') {
    global $conn;
    $logFile = fopen("./cryptlog.txt", "a");
    if ($statement) {
        fwrite($logFile, "\r\n" . getCurrentTimestamp() . " (UTC): Master password $statement\r\n");
    }
    $i = 0;
    //articles
    $result = $conn->query("SELECT id, name, description, iv, iv2 FROM articles");
    $stmt = $conn->prepare("UPDATE articles SET name = ?, description = ?, iv = ?, iv2 = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $description, $iv, $iv2, $id);
    fwrite($logFile, "\t" . getCurrentTimestamp() . " (UTC): altering articles\r\n");
    while ($row = $result->fetch_assoc()) {
        $i++;
        $mc_old = new MasterCrypt($current, $row['iv'], $row['iv2']);
        $mc_new = new MasterCrypt($new);
        $name = $mc_new->encrypt($mc_old->decrypt($row["name"]));
        $description = $mc_new->encrypt($mc_old->decrypt($row["description"]));
        $iv = $mc_new->iv;
        $iv2 = $mc_new->iv2;
        $id = $row["id"];
        $stmt->execute();
        if ($conn->error) {
            fwrite($logFile, "\t\t" . getCurrentTimestamp() . " (UTC): Error in row with id $id: " . $conn->error . "\r\n");
        }
    }
    $stmt->close();
    //products
    $result = $conn->query("SELECT id, name, description, iv, iv2 FROM products");
    $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, iv = ?, iv2 = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $description, $iv, $iv2, $id);
    fwrite($logFile, "\t" . getCurrentTimestamp() . " (UTC): altering products\r\n");
    while ($row = $result->fetch_assoc()) {
        $i++;
        $mc_old = new MasterCrypt($current, $row['iv'], $row['iv2']);
        $mc_new = new MasterCrypt($new);
        $name = $mc_new->encrypt($mc_old->decrypt($row["name"]));
        $description = $mc_new->encrypt($mc_old->decrypt($row["description"]));
        $iv = $mc_new->iv;
        $iv2 = $mc_new->iv2;
        $id = $row["id"];
        $stmt->execute();
        if ($conn->error) {
            fwrite($logFile, "\t\t" . getCurrentTimestamp() . " (UTC): Error in row with id $id: " . $conn->error . "\r\n");
        }
    }
    $stmt->close();
    //bank data
    $result = $conn->query("SELECT * FROM clientInfoBank");
    $stmt = $conn->prepare("UPDATE clientInfoBank SET bic = ?, iban = ?, bankName = ?, iv = ?, iv2 = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $bic, $iban, $name, $iv, $iv2, $id);
    fwrite($logFile, "\t" . getCurrentTimestamp() . " (UTC): altering bank\r\n");
    while ($row = $result->fetch_assoc()) {
        $i++;
        $mc_old = new MasterCrypt($current, $row['iv'], $row['iv2']);
        $mc_new = new MasterCrypt($new);
        $bic = $mc_new->encrypt($mc_old->decrypt($row['bic']));
        $iban = $mc_new->encrypt($mc_old->decrypt($row["iban"]));
        $name = $mc_new->encrypt($mc_old->decrypt($row["bankName"]));
        $iv = $mc_new->iv;
        $iv2 = $mc_new->iv2;
        $id = $row["id"];
        $stmt->execute();
        if ($conn->error) {
            fwrite($logFile, "\t\t" . getCurrentTimestamp() . " (UTC): Error in row with id $id: " . $conn->error . "\r\n");
        }
    }
    fwrite($logFile, date("y-m-d h:i:s") . ": Finished\r\n");
    fwrite($logFile, date("y-m-d h:i:s") . ":  rows affected\r\n");
    $stmt->close();
    fclose($logFile);
}
if(isset($_POST['saveButton'])){
    //general
    $length = intval($_POST['passwordLength']);
    $compl = intval($_POST['passwordComplexity']);

    //expiration
    $exp = 'FALSE';
    $dur = 0;
    if(isset($_POST['enableTimechange'])){
        if(isset($_POST['enableTimechange_months'])){
            $exp = 'TRUE';
            $dur = intval($_POST['enableTimechange_months']);
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Error: </strong>Please define months when activating password expiration .</div>';
        }
    }
    $type = test_input($_POST['enableTimechange_type']);
    $conn->query("UPDATE $policyTable SET passwordLength = $length, complexity = '$compl', expiration = '$exp', expirationDuration = $dur, expirationType = '$type'");
    echo mysqli_error($conn);
    //masterpassword
    if(!empty($_POST['masterPass_new']) && !empty($_POST['masterPass_newConfirm']) && $_POST['masterPass_new'] == $_POST['masterPass_newConfirm']
    && (!$masterPasswordHash || crypt($_POST['masterPass_current'], $masterPasswordHash) == $masterPasswordHash)){ //from header
        $current = '';
        $new_master = base64_encode($_POST['masterPass_new']);
        if($masterPasswordHash){ $current = base64_encode($_POST['masterPass_current']); }
        if($current && crypt(base64_decode($current), $masterPasswordHash) == $masterPasswordHash){
            mc_update_values($current, $new_master, 'changed');
        } elseif(!$masterPasswordHash) {
            mc_update_values($current, $new_master , 'added');
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_UNEXPTECTED'].'</div>';
        }
        $checkSum = simple_encryption("ABCabc123!", $new_master);
        $new_master = password_hash($_POST['masterPass_new'], PASSWORD_BCRYPT);
        if($conn->query("UPDATE configurationData SET masterPassword = '$new_master', checkSum = '$checkSum'")){
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        }
    }
} elseif(isset($_POST['masterPass_deactivate']) && !empty($_POST['masterPass_current'])){
    mc_update_values($_SESSION['masterpassword'], '', 'removed');
    $conn->query("UPDATE configurationData SET masterPassword = ''"); echo $conn->error;
    $masterPasswordHash = '';
    $_SESSION['masterpassword'] = '';
    $conn->query("UPDATE UserData SET keyCode = '' "); echo $conn->error;
} elseif(isset($_POST['masterPass_deactivate'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>Aktuelles Passwort fehlt.</div>';
}

$result = $conn->query("SELECT * FROM $policyTable");
$row = $result->fetch_assoc();
?>

<form method="POST" id="formPasswordOptions">
    <div class="page-header">
        <h3><?php echo $lang['PASSWORD'].' '.$lang['OPTIONS']; ?>
            <div class="page-header-button-group"><button type="submit" class="btn btn-default blinking" name="saveButton" title="Save"><i class="fa fa-floppy-o"></i></button></div>
        </h3>
    </div>
    <h4><?php echo $lang['ADMIN_CORE_OPTIONS']; ?> <a role="button" data-toggle="collapse" href="#password_info_general"> <i class="fa fa-info-circle"> </i> </a></h4>
    <br>
    <div class="collapse" id="password_info_general">
        <div class="well"><?php echo $lang['INFO_PASSWORD_GENERAL']; ?></div>
    </div>
    <br>
    <div class="container-fluid">
        <div class="col-md-4">
            <?php echo $lang['PASSWORD_MINLENGTH'] ?>:
        </div>
        <div class="col-md-8">
            <input type="number" class="form-control" name="passwordLength" value="<?php echo $row['passwordLength']; ?>" />
        </div>
        <br><br><br>
        <div class="col-md-4">
            <?php echo $lang['COMPLEXITY']; ?>:
        </div>
        <div class="col-md-4">
            <select class="form-control" name="passwordComplexity">
                <option value="0" <?php if($row['complexity'] === '0'){echo 'selected';} ?>><?php echo $lang['COMPLEXITY_TOSTRING']['SIMPLE']; ?></option>
                <option value="1" <?php if($row['complexity'] === '1'){echo 'selected';} ?>><?php echo $lang['COMPLEXITY_TOSTRING']['MEDIUM']; ?></option>
                <option value="2" <?php if($row['complexity'] === '2'){echo 'selected';} ?>><?php echo $lang['COMPLEXITY_TOSTRING']['STRONG']; ?></option>
            </select>
        </div>
    </div>

    <br><hr><br>

    <div class="row">
        <div class="col-xs-8">
            <h4><?php echo $lang['EXPIRATION_DATE']; ?> <a role="button" data-toggle="collapse" href="#password_info_expiry"><i class="fa fa-info-circle"></i></a></h4>
        </div>
        <div class="col-xs-2 checkbox">
            <input type="checkbox" value="person" name="enableTimechange"  <?php if($row['expiration'] == 'TRUE'){echo 'checked';} ?> /> Aktiv
        </div>
    </div>
    <br>
    <div class="collapse" id="password_info_expiry">
        <div class="well">
            <?php echo $lang['INFO_EXPIRATION']; ?>
        </div>
    </div>
    <br>
    <div class="container-fluid">
        <div class="col-md-4">
            Änderung nach Monaten:
        </div>
        <div class="col-md-8">
            <input type="number" class="form-control" name="enableTimechange_months" value="<?php echo $row['expirationDuration']; ?>"/>
        </div>
        <br><br><br>
        <div class="col-md-4">
            Art:
        </div>
        <div class="col-md-4">
            <input type="radio" name="enableTimechange_type" value="ALERT" <?php if($row['expirationType'] != 'FORCE'){echo 'checked';} ?>/> Optional
        </div>
        <div class="col-md-4">
            <input type="radio" name="enableTimechange_type" value="FORCE" <?php if($row['expirationType'] == 'FORCE'){echo 'checked';} ?>/> Zwingend
        </div>
    </div>

    <br><hr><br>

    <h4><?php echo mc_status(); ?><?php echo $lang["MASTER_PASSWORD"]; ?> <small><?php if($masterPasswordHash): echo $lang["ENCRYPTION_ACTIVE"]; else: echo $lang["ENCRYPTION_DEACTIVATED"]; endif;?></small><a role="button" data-toggle="collapse" href="#password_info_master"><i class="fa fa-info-circle"></i></a></h4>
        <br>
        <div class="collapse" id="password_info_master">
            <div class="well">
                Das Masterpasswort wird zum verschlüsseln sensibler Daten verwendet, die nur unter eingabe des Passworts wieder entschlüsselt werden können.
                Es sind insgesamt <b><?php echo mc_total_row_count(); ?></b> Einträge betroffen.
            </div>
        </div>
        <br>
        <div class="container-fluid">
            <?php if($masterPasswordHash): ?>
                <div class="col-md-4">
                    <?php echo $lang["PASSWORD_CURRENT"]; ?>:
                </div>
                <div class="col-md-5">
                    <input type="password" class="form-control" name="masterPass_current" value=""/>
                </div>
                <?php if($masterPasswordHash): ?>
                    <div class="col-md-3"><button type="submit" class="btn btn-warning" name="masterPass_deactivate" value="true"><?php echo $lang["ENCRYPTION_DEACTIVATE"]; ?></button></div>
                <?php endif; ?>
                <br><br>
            <?php endif; ?>
            <div class="col-md-4">
                <?php echo $lang["NEW_PASSWORD"]; ?>:
            </div>
            <div class="col-md-8">
                <input type="password" class="form-control" name="masterPass_new" value=""/>
            </div>
            <br><br>
            <div class="col-md-4">
                <?php echo $lang["NEW_PASSWORD_CONFIRM"]; ?>:
            </div>
            <div class="col-md-8">
                <input type="password" class="form-control" name="masterPass_newConfirm" value=""/>
            </div>
            <br><br>
            <div class="col-md-4">
                <a href="../system/cryptlog" target="_blank"><?php echo $lang["ENCRYPTION_LOG"]; ?> </a>
            </div>
            <br><br><br>
        </div>
    </form>

    <script>
    $("#formPasswordOptions").submit(function(event){
        if($("input[name='masterPass_new']").val() && $("input[name='masterPass_new']").val() == $("input[name='masterPass_newConfirm']").val()){
            alert("<?php echo mc_list_changes(); ?>");
            if (confirm("<?php echo $lang['PASSWORD_CHANGE_PROMPT'];?>") == true) {
                return true;
            } else {
                event.preventDefault();
                return false;
            }
        }
    });
</script>

<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
