<?php include 'header.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['ADVANCED_OPTIONS']; ?></h3>
</div>

<form method=post>
  <?php
  $result = $conn->query("SELECT * FROM $mailOptionsTable");
  $row = $result->fetch_assoc();

  if(isset($_POST['saveButton'])){
    if(!empty($_POST['smtp_host'])){

    }
    if(!empty($_POST['smtp_port'])){
      $val = intval($_POST['smpt_port']);
      $conn->query("UPDATE $mailOptionsTable SET port = '$val'");
    }
    if(!empty($_POST['smtp_username'])){

    }
    if(!empty($_POST['smtp_password'])){
    }
    echo mysqli_error($conn);
  }
  ?>

  <h4>SMTP Einstellungen</h4>
  <div class="container">
    <br>
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
