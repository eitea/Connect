<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['ADVANCED_OPTIONS']; ?></h3>
</div>

<form method=post>
  <?php
  if(isset($_POST['saveButton'])){

  }

  ?>

  <h4>GitHub</h4>
  <div class="container">
    <br>
    <div class="checkbox col-md-12">
      <input <?php if($rowGitHubTable['sslVerify'] == 'TRUE'){echo 'checked';} ?> type='checkbox' name='ssl' value='TRUE'>
      SSL Certificate Validation
    </div>
    <br>
  </div>

  <br><hr><br>

  <h4>Terminal</h4>
  <div class="container">
    <br>
    <div class="input-group">
      <span class="input-group-addon"> User-Agent </span>
      <input type=text class="form-control" name="userAgent" value="<?php echo $rowPiConnTable['header']; ?>" >
    </div>
  </div>

  <br><hr><br>

  <div class="text-right">
    <button type="submit" class="btn btn-warning" name="saveButton">Save </button>
  </div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
