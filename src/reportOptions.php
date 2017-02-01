<?php include 'header.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['ADVANCED_OPTIONS']; ?></h3>
</div>

<form method=post>
  <?php
  if(isset($_POST['saveButton'])){

  }
  ?>

  <h4>SMTP Einstellungen</h4>
  <div class="container">
    <br>
    <div class="checkbox col-md-4">
      Host
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="smtp_host" />
    </div>
    <br><br>
    <div class="checkbox col-md-4">
      Username
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="smtp_username" />
    </div>
    <br><br>
    <div class="checkbox col-md-4">
      Passwort
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="smtp_password" />
    </div>
    <br><br>
    <div class="checkbox col-md-4">
      Port
    </div>
    <div class="checkbox col-md-8">
      <input type="text" class="form-control" name="smtp_port" />
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
