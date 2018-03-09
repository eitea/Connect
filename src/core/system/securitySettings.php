<?php
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
        //TODO: do not overwrite existing
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
        if($accept) echo '<div class="alert alert-success"><a data-dismiss="alert" class="close">&times;</a>Verschlüsselung wurde erfolgreich aktiviert</div>';
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
