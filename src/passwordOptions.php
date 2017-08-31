<?php require 'header.php'; require_once 'utilities.php';require_once 'createTimestamps.php'; enableToCore($userID);?>
<?php
if(isset($_POST['saveButton'])){
  //allgemein
  $length = intval($_POST['passwordLength']);
  $compl = intval($_POST['passwordComplexity']);
  //erweitert
  $exp = 'FALSE';
  $dur = 0;
  if(isset($_POST['enableTimechange'])){
    if(isset($_POST['enableTimechange_months'])){
      $exp = 'TRUE';
      $dur = intval($_POST['enableTimechange_months']);
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Error: </strong>Please define months when activating password expiration .';
      echo '</div>';
    }
  }
  $type = test_input($_POST['enableTimechange_type']);
  $conn->query("UPDATE $policyTable SET passwordLength = $length, complexity = '$compl', expiration = '$exp', expirationDuration = $dur, expirationType = '$type'");
  echo mysqli_error($conn);

  //master
  $masterPasswordResult = $conn->query("SELECT masterPassword FROM $configTable");
  $masterPasswordSet = strlen($masterPasswordResult->fetch_assoc()["masterPassword"]) != 0;
  if($masterPasswordSet && isset($_POST['masterPass_deactivate'])){
    $conn->query("UPDATE $configTable SET masterPassword = ''");

    //delete all sessions
    ini_set('session.gc_max_lifetime', 0);
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 1);
    session_unset();
    session_destroy();
    redirect('../login/auth');
    die();
  }
  if(isset($_POST['masterPass_current']) && !empty($_POST['masterPass_new']) && !empty($_POST['masterPass_newConfirm'])){
    $passwordCurrent = test_input($_POST['masterPass_current']);
    $password = $_POST['masterPass_new'];
    $passwordConfirm = $_POST['masterPass_newConfirm'];
    $output = '';
    $acceptMasterPassword = true;
    //check if password is clean, but not really necessary, since password will be hashed anyways.
    if(test_input($password) != $password){
      $acceptMasterPassword = false;
    }
    //check if old password matches, if old password exists
    $result = $conn->query("SELECT masterPassword FROM $configTable");
    $row = $result->fetch_assoc();
    if(crypt($passwordCurrent, $row['masterPassword']) != $row['masterPassword'] && !empty($row['masterPassword'])){ //skip this validation if pass in DB is NULL (not been initialized yet)
      //will be set to false if a hash is in the DB and the hash doesnt crypt (doesnt match)
      $acceptMasterPassword = false;
    }
    //check if new passwords matches
    if(strcmp($password, $passwordConfirm) != 0 || !match_passwordpolicy($_POST['masterPass_new'], $output)){
      $acceptMasterPassword = false;
    }
    //if everything was okay:
    if($acceptMasterPassword){
      $resultBank = $conn->query("SELECT * FROM $clientDetailBankTable");
      while($resultBank && ($rowBank = $resultBank->fetch_assoc())){
        $curID = $rowBank['id'];
        //decrypt all that has been encrypted with old password
        $keyValue = openssl_decrypt($rowBank['iv'], 'aes-256-cbc', $passwordCurrent, 0, $rowBank['iv2']);
        $ibanVal = mc_decrypt($rowBank['iban'], $keyValue);
        $bicVal = mc_decrypt($rowBank['bic'], $keyValue);

        //encrypt with new password
        $keyValue = openssl_random_pseudo_bytes(32); //random 64 chars key, uncrypted
        $keyValue = bin2hex($keyValue);

        $ibanVal = mc_encrypt($ibanVal, $keyValue);
        $bicVal = mc_encrypt($bicVal, $keyValue);

        $ivValue = openssl_random_pseudo_bytes(8);
        $ivValue = bin2hex($ivValue);

        //this did not need an iv since keyValue was random anyway, but silly php thinks its smarter than me.
        $keyValue = openssl_encrypt($keyValue, 'aes-256-cbc', $password, 0, $ivValue);

        $conn->query("UPDATE $clientDetailBankTable SET iban='$ibanVal', bic='$bicVal', iv='$keyValue', iv2='$ivValue' WHERE id = $curID");
        echo mysqli_error($conn);
      }

      //articles table
      $result = $conn->query("SELECT * FROM articles");
      while($row = $result->fetch_assoc()){
        $old = mc($row["iv"],$row["iv2"]);
        $new = mc()->from($old,$password);
        $iv = $new->iv;
        $iv2 = $new->iv2;
        $name = $new->change($row["name"]);
        $desc = $new->change($row["description"]);
        $id = $row["id"];
        $conn->query("UPDATE articles SET name = '$name', description = '$desc', iv = '$iv', iv2 = '$iv2' WHERE id = $id");
      }

      //save new passwordhash
      $password = password_hash($password, PASSWORD_BCRYPT);
      $conn->query("UPDATE $configTable SET masterPassword = '$password'");

      //delete all sessions
      ini_set('session.gc_max_lifetime', 0);
      ini_set('session.gc_probability', 1);
      ini_set('session.gc_divisor', 1);
      session_unset();
      session_destroy();
      redirect('../login/auth');
      die();
    } else {
      echo '<br><div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo "<strong>Error: </strong>Passwords were invalid. Please do not use any HTML, SQL or Javascript specific characters. $output";
      echo '</div>';
    }
  }
}

$masterPasswordResult = $conn->query("SELECT masterPassword FROM $configTable");
$masterPasswordSet = strlen($masterPasswordResult->fetch_assoc()["masterPassword"]) != 0;

$result = $conn->query("SELECT * FROM $policyTable");
$row = $result->fetch_assoc();
?>


<form method="POST">
  <div class="page-header">
    <h3><?php echo $lang['PASSWORD'].' '.$lang['OPTIONS']; ?>
      <div class="page-header-button-group">
        <button type="submit" class="btn btn-default blinking" name="saveButton" title="Save"><i class="fa fa-floppy-o"></i></button>
      </div>
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
      <input type="radio" name="enableTimechange_type" value="ALERT" <?php if($row['expirationType'] == 'ALERT'){echo 'checked';} ?>/> Optional
    </div>
    <div class="col-md-4">
      <input type="radio" name="enableTimechange_type" value="FORCE" <?php if($row['expirationType'] == 'FORCE'){echo 'checked';} ?>/> Zwingend
    </div>
  </div>

  <br><hr><br>


  <h4>Master Passwort <small><?php if($masterPasswordSet): echo $lang["ENCRYPTION_ACTIVE"]; else: echo $lang["ENCRYPTION_DEACTIVATED"]; endif;?></small><a role="button" data-toggle="collapse" href="#password_info_master"><i class="fa fa-info-circle"></i></a></h4>
  <br>
  <div class="collapse" id="password_info_master">
    <div class="well">
      Das Masterpasswort wird zum verschlüsseln sensibler Daten verwendet, die nur unter eingabe des Passworts wieder entschlüsselt werden können.
    </div>
  </div>
  <br>
  <div class="container-fluid">
    <div class="col-md-4">
      Aktuelles Passwort:
    </div>
    <div class="col-md-8">
      <input type="password" class="form-control" name="masterPass_current" value=""/>
    </div>
    <br><br>
    <div class="col-md-4">
      Neues Passwort:
    </div>
    <div class="col-md-8">
      <input type="password" class="form-control" name="masterPass_new" value=""/>
    </div>
    <br><br>
    <div class="col-md-4">
      Neues Passwort Wiederholen:
    </div>
    <div class="col-md-8">
      <input type="password" class="form-control" name="masterPass_newConfirm" value=""/>
    </div>
    <br><br>
    <div class="col-md-4">
      Verschlüsselung deaktivieren
    </div>
    <div class="col-md-8">
      <input type="checkbox" class="" name="masterPass_deactivate" value="true"/>
    </div>
    <br><br><br>
  </div>
</form>
<?php require 'footer.php'; ?>
