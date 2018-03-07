<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToCore($userID);?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
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
    $conn->query("UPDATE policyData SET passwordLength = $length, complexity = '$compl', expiration = '$exp', expirationDuration = $dur, expirationType = '$type'");
    echo mysqli_error($conn);
} //crypt($_POST['password'], $row['psw']) == $row['psw']
if(isset($_POST['deactive_encryption']) && crypt($_POST['encryption_current_pass'], $userPasswordHash) == $userPasswordHash){
    //TODO: decrypt and outdate all keys
    $conn->query("UPDATE security_company SET outDated = 'TRUE'");
    $conn->query("UPDATE security_modules SET outDated = 'TRUE'");
    $conn->query("UPDATE security_access SET outDated = 'TRUE'");

    $conn->query("UPDATE configurationData SET activeEncryption = 'FALSE'");
} elseif(isset($_POST['deactive_encryption'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
}

if(isset($_POST['active_encryption']) && !empty($_POST['encryption_pass']) && $_POST['encryption_pass_confirm'] == $_POST['encryption_pass']){
    if(crypt($_POST['encryption_current_pass'], $userPasswordHash) == $userPasswordHash){
        $accept = true;
        $err = $content_personal = $content_company = '';
        //user
        $keyPair = sodium_crypto_box_keypair();
        $private = base64_encode(sodium_crypto_box_secretkey($keyPair));
        $user_public = sodium_crypto_box_publickey($keyPair);
        $hash = password_hash($_POST['encryption_pass'], PASSWORD_BCRYPT);
        $content_personal = $private." \n".base64_encode($user_public);
        $encrypted = simple_encryption($private, $_POST['encryption_pass']);
        $_SESSION['privateKey'] = $private;
        $conn->query("UPDATE UserData SET psw = '$hash', publicPGPKey = '".base64_encode($user_public)."', privatePGPKey = '".$encrypted."'  WHERE id = $userID");
        if($conn->error) $accept = false;

        //company
        $result = $conn->query("SELECT id FROM companyData LIMIT 1");
        if($accept && $result && ($row = $result->fetch_assoc())){
            $companyID = $row['id'];
            $keyPair = sodium_crypto_box_keypair();
            $private = sodium_crypto_box_secretkey($keyPair);
            $public = sodium_crypto_box_publickey($keyPair);
            $content_company = base64_encode($private)." \n".base64_encode($public);
            $conn->query("UPDATE companyData SET publicPGPKey = '".base64_encode($public)."' WHERE id = $companyID");
            $nonce = random_bytes(24);
            $encrypted = $nonce . sodium_crypto_box($private, $nonce, $private.$user_public);
            $conn->query("INSERT INTO security_company(userID, companyID, privateKey) VALUES ($userID, 1, '".base64_encode($encrypted)."')");
            if($conn->error) $accept = false;
        } else {
            $err .= $conn->error;
        }

        if($accept){ //module
            $keyPair = sodium_crypto_box_keypair();
            $private = sodium_crypto_box_secretkey($keyPair);
            $public = sodium_crypto_box_publickey($keyPair);
            $symmetric = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $nonce = random_bytes(24);
            $encrypted = $nonce . sodium_crypto_box($symmetric, $nonce, $private.$public);
            $conn->query("INSERT INTO security_modules(module, publicPGPKey, symmetricKey) VALUES ('DSGVO', '".base64_encode($public)."', '".base64_encode($encrypted)."')");
            if($conn->error) $accept = false;
        } else {
            $err .= $conn->error;
        }

        if($accept){ //access
            $nonce = random_bytes(24);
            $encrypted = $nonce . sodium_crypto_box($private, $nonce, $private.$user_public);
            $conn->query("INSERT INTO security_access(userID, module, privateKey) VALUES ($userID, 'DSGVO', '".base64_encode($encrypted)."')");
            if($conn->error) $accept = false;
        } else {
            $err .= $conn->error;
        }

        if($accept){
            $conn->query("UPDATE configurationData SET activeEncryption = 'TRUE'");
            // dsgvo_access cannot decrypt this yet
            // $stmt = $conn->prepare("UPDATE documentProcess SET document_text = ?, document_headline = ? WHERE id = ?");
            // $stmt->bind_param('sss', $text, $head, $id);
            // $result = $conn->query("SELECT id, document_text, document_headline FROM documentProcess");
            // while($result && ($row = $result->fetch_assoc())){
            //     $id = $row['id'];
            //     $text = simple_encryption($row['document_text'], $symmetric);
            //     $head = simple_encryption($row['document_headline'], $symmetric);
            // }
            // $stmt->close();

            $stmt = $conn->prepare("UPDATE documents SET txt = ?, name = ? WHERE id = ?"); echo $stmt->error;
            $stmt->bind_param('sss', $text, $head, $id);
            $result = $conn->query("SELECT id, txt, name FROM documents"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $id = $row['id'];
                $text = simple_encryption($row['txt'], $symmetric);
                $head = simple_encryption($row['name'], $symmetric);
                $stmt->execute();
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE document_customs SET content = ? WHERE id = ?"); echo $stmt->error;
            $stmt->bind_param('si', $text, $id);
            $result = $conn->query("SELECT id, content FROM document_customs"); echo $conn->error;
            while($result && ($row = $result->fetch_assoc())){
                $id = $row['id'];
                $text = simple_encryption($row['content'], $symmetric);
                $stmt->execute();
            }
            $stmt->close();

            echo $conn->error;
        }

        echo $err;
    } else {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Error: </strong>Incorrektes Passwort.</div>';
    }
} elseif(isset($_POST['active_encryption'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
}

$result = $conn->query("SELECT * FROM policyData");
$row = $result->fetch_assoc();
?>

<form method="POST">
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
    <div class="row">
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
</form>

<form method="POST">
    <h4>Verschlüsselung <a role="button" data-toggle="collapse" href="#password_info_encryption"> <i class="fa fa-info-circle"> </i> </a></h4>
    <br>
    <div class="collapse" id="password_info_encryption">
        <div class="well"><?php echo $lang['INFO_ENCRYPTION']; ?></div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-4">
            <label>Aktuelles Passwort</label>
            <input type="password" autocomplete="new-password" name="encryption_current_pass" class="form-control">
        </div>
        <?php
        $result = $conn->query("SELECT activeEncryption FROM configurationData");
        if($result && ($row = $result->fetch_assoc())){
            if($row['activeEncryption'] == 'TRUE'){
                echo '<div class="col-md-4"><label>Deaktivieren</label><br><button type="submit" name="deactive_encryption" class="btn btn-warning">Verschlüsselung Deaktivieren</button></div>';
            } else {
                echo '</div><div class="row">
                <div class="col-md-4">
                <label>Neues Login Passwort</label>
                <input type="password" name="encryption_pass" class="form-control" />
                </div>
                <div class="col-md-4">
                <label>Neues Login Passwort Bestätigen</label>
                <input type="password" name="encryption_pass_confirm" class="form-control" />
                </div>
                <div class="col-md-4">
                <label>Aktivieren</label><br>
                <button type="submit" name="active_encryption" class="btn btn-warning" >Verschlüsselung Aktivieren</button>
                </div>
                </div>';
            }
        }
        ?>
    </div>
</form>

<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
