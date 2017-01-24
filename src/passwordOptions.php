<?php require 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID);?>

<?php
if(isset($_POST['saveButton'])){
  $length = intval($_POST['passwordLength']);
  $compl = intval($_POST['passwordComplexity']);

  if(isset($_POST['enableTimechange'])){
    $exp = 'TRUE';
  } else {
    $exp = 'FALSE';
  }

  $type = test_input($_POST['enableTimechange_type']);

  $dur = intval($_POST['enableTimechange_months']);
  $conn->query("UPDATE $policyTable SET passwordLength = $length, complexity = '$compl', expiration = '$exp', expirationDuration = $dur, expirationType = '$type'");
  echo mysqli_error($conn);
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
    <h4>Erweitert</h4>
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

  <div class="text-right">
    <button type="submit" class="btn btn-warning" name="saveButton">Speichern </button>
  </div>
</form>
<?php require 'footer.php'; ?>
