<?php require 'header.php'; enableToCore($userID); ?>

<div class="page-header">
  <h3>Download Backup SQL</h3>
</div>

<form method="POST" target="_blank" action="downloadSql">
  <div class="container-fluid">
    <div class="col-md-4">
      <label><input type="checkbox" name="setPassword" value="1" /> ZIP mit Passwort versehen: </label>
      <br><small>*Funktioniert auf Windows nur mit zip Installation.</small>
    </div>
    <div class="col-md-6" >
      <input type="password" disabled name="password" class="form-control" placeholder="<?php echo $lang['PASSWORD']; ?>" />
    </div>
    <div class="col-md-12">
      <br>
      <button type="submit" class="btn btn-warning" name="start_Download">Download ZIP<i class="fa fa-download"></i></button>
    </div>
  </div>
</form>
<?php include 'footer.php'; ?>
