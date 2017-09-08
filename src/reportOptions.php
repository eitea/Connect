<?php include 'header.php'; enableToCore($userID);?>
<!-- BODY -->
<?php
if(isset($_POST['saveButton'])){
  if(!empty($_POST['smtp_host'])){
    $val = test_input($_POST['smtp_host']);
    $conn->query("UPDATE $mailOptionsTable SET host = '$val'");
  }
  if(!empty($_POST['smtp_port'])){
    $val = intval($_POST['smtp_port']);
    $conn->query("UPDATE $mailOptionsTable SET port = '$val'");
  }
  if(isset($_POST['smtp_username'])){
    $val = test_input($_POST['smtp_username']);
    $conn->query("UPDATE $mailOptionsTable SET username = '$val'");
  }
  if(isset($_POST['smtp_password'])){
    $val = test_input($_POST['smtp_password']);
    $conn->query("UPDATE $mailOptionsTable SET password = '$val'");
  }
  if(isset($_POST['smtp_secure'])){
    $val = test_input($_POST['smtp_secure']);
    $conn->query("UPDATE $mailOptionsTable SET smtpSecure = '$val'");
  }
  if(isset($_POST['mail_sender'])){
    $val = test_input($_POST['mail_sender']);
    $conn->query("UPDATE $mailOptionsTable SET sender = '$val'");
  }
  echo mysqli_error($conn);
}

$result = $conn->query("SELECT * FROM $mailOptionsTable");
$row = $result->fetch_assoc();
?>

<form method=post>
  <div class="page-header">
    <h3>E-mail <?php echo $lang['OPTIONS']; ?>
    <div class="page-header-button-group"><button type="submit" class="btn btn-default blinking" title="<?php echo $lang['SAVE']; ?>" name="saveButton"><i class="fa fa-floppy-o"></i></button></div>
    <div style="display:inline;float:right;"><a role="button" data-toggle="collapse" href="#info_emailserver"><i class="fa fa-info-circle"></i></a></div>
    </h3>
  </div>

  <div class="collapse" id="info_emailserver"><div class="well">
  <?php 
  if(isset($_SESSION['language']) && $_SESSION['language'] == 'ENG'){
    echo 'To send reports and login informations via email, an external e-mail server is required. <br>
          When attempting to send an email, Connect will always work with the entered information below.';
  } elseif(!isset($_SESSION['language']) || $_SESSION['language'] == 'GER'){
    echo 'Um Reports oder Login Informationen abzuschicken wird ein externer E-Mail Server benötigt. <br>
          Sobald Informationen als E-Mail abgeschickt werden sollen, wird Connect die unten stehenden Daten verwenden.';
  }
  ?></div>
  </div>


  <h4>SMTP Einstellungen</h4>
  <div class="container-fluid">
    <br>
    <div class="checkbox col-md-4">
      SMTP Security
    </div>
    <div class="checkbox col-md-8">
      <select class="js-example-basic-single" name="smtp_secure" style="width:200px">
        <option value="" <?php if($row['smtpSecure'] == ''){echo "selected";} ?>> - </option>
        <option value="tls" <?php if($row['smtpSecure'] == 'tls'){echo "selected";} ?>> TLS </option>
        <option value="ssl" <?php if($row['smtpSecure'] == 'ssl'){echo "selected";} ?>> SSL </option>
      </select>
    </div>
  </div>
  <div class="container-fluid">
    <br><br>
    <div class="checkbox col-md-4">
      Absender-Adresse
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="mail_sender"  value="<?php echo $row['sender']; ?>" />
    </div>
    <br><br><br>
    <div class="checkbox col-md-4">
      Host
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="smtp_host" value="<?php echo $row['host']; ?>" />
    </div>
    <br><br>
    <div class="checkbox col-md-4">
      Port
    </div>
    <div class="checkbox col-md-8">
      <input type="number" class="form-control" name="smtp_port"  value="<?php echo $row['port']; ?>" />
    </div>
    <br><br><br>
    <div class="checkbox col-md-4">
      Username
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="smtp_username"  value="<?php echo $row['username']; ?>" />
    </div>
    <br><br>
    <div class="checkbox col-md-4">
      Passwort
    </div>
    <div class="checkbox col-md-8">
      <input type="password" class="form-control" name="smtp_password" />
    </div>
    <br>
  </div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
