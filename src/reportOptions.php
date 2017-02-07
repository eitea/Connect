<?php include 'header.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3> E-mail <?php echo $lang['OPTIONS']; ?></h3>
</div>

<form method=post>
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
    if(!empty($_POST['smtp_username'])){
      $val = test_input($_POST['smtp_username']);
      $conn->query("UPDATE $mailOptionsTable SET username = '$val'");
    }
    if(!empty($_POST['smtp_password'])){
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

  <h4>SMTP Einstellungen</h4>
  <div class="container">
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
    <br><br>
    <div class="checkbox col-md-4">
      Absender-Adresse
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="mail_sender"  value="<?php echo $row['sender']; ?>"/>
    </div>
    <br><br><br>
    <div class="checkbox col-md-4">
      Host
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="smtp_host" value="<?php echo $row['host']; ?>"/>
    </div>
    <br><br>
    <div class="checkbox col-md-4">
      Port
    </div>
    <div class="checkbox col-md-8">
      <input type="number" class="form-control" name="smtp_port"  value="<?php echo $row['port']; ?>"/>
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

  <br><hr><br>

  <div class="text-right">
    <button type="submit" class="btn btn-warning" name="saveButton"><?php echo $lang['SAVE']; ?> </button>
  </div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
