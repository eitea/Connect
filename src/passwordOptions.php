<?php require 'header.php'; ?>
<?php enableToCore($userID);?>

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
    //check if old password matches
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
    //if all matched:
    if($acceptMasterPassword){
      $password = password_hash($password, PASSWORD_BCRYPT);
      $conn->query("UPDATE $configTable SET masterPassword = '$password'");
    } else {
      echo '<br><div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo "<strong>Error: </strong>Passwords were invalid. Please do not use any HTML, SQL or Javascript specific characters. $output";
      echo '</div>';
    }
  }
}

$result = $conn->query("SELECT * FROM $policyTable");
$row = $result->fetch_assoc();
?>

<div class="page-header">
  <h3>Passwordpolicy <a role="button" data-toggle="collapse" href="#passwordpolicyInfo"> <i class="fa fa-info-circle"> </i> </a> </h3>
</div>

<div class="collapse" id="passwordpolicyInfo">
  <div class="well">
    Einstellungen der Länge und Komplexität der Passwörter der Benutzer. <br>
    Einfach - Keine Restriktion <br>
    Mittel - Passwörter müssen mind. 1 Großbuchstaben und 1 Zahl enthalten <br>
    Stark - Passwörter müssen mind. 1 Großbuchstaben, 1 Zahl und 1 Sonderzeichen enthalten <br>
    <br>
    Passwörter können ein Verfallsdatum bekommen (Erweitert - Aktiv), wodurch nach Ablauf der Zeit der Benutzer dazu aufgefordert wird sein Passwort zu ändern.
    Die Aufforderung kann den Benutzer entweder Zwingen, oder ihm die Entscheidung überlassen.
  </div>
</div>

<form method="POST">
  <h4>Allgemein</h4>
  <br><br>
  <div class="container">
    <div class="col-md-4">
      Minimale Passwortlänge:
    </div>
    <div class="col-md-8">
      <input type="number" class="form-control" name="passwordLength" value="<?php echo $row['passwordLength']; ?>" />
    </div>
    <br><br><br>
    <div class="col-md-4">
      Komplexität:
    </div>
    <div class="col-md-4">
      <select class="form-control" name="passwordComplexity">
        <option value="0" <?php if($row['complexity'] === '0'){echo 'selected';} ?>>Einfach</option>
        <option value="1" <?php if($row['complexity'] === '1'){echo 'selected';} ?>>Medium</option>
        <option value="2" <?php if($row['complexity'] === '2'){echo 'selected';} ?>>Stark</option>
      </select>
    </div>
  </div>

  <br><hr><br>

  <div class="col-xs-8">
    <h4>Verfallsdatum</h4>
  </div>
  <div class="col-xs-2 checkbox">
    <input type="checkbox" value="person" name="enableTimechange"  <?php if($row['expiration'] == 'TRUE'){echo 'checked';} ?> /> Aktiv
  </div>
  <br><br><br>
  <div class="container">
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

  <div class="col-xs-12">
    <h4>Master Passwort Ändern</h4>
  </div>
  <br><br><br>
  <div class="container">
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
    <br><br><br>
  </div>

  <br><hr><br>

  <div class="text-right">
    <button type="submit" class="btn btn-warning" name="saveButton">Speichern </button>
  </div>
</form>
<?php require 'footer.php'; ?>
