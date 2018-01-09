<?php require 'header.php'; enableToCore($userID); ?>

<div class="page-header">
  <h3>Download Backup SQL</h3>
</div>

<form method="POST" target="_blank" action="downloadSql">
  <div class="container-fluid">
    <div class="col-md-4">
      <label><input type="checkbox" name="setPassword" checked value="1" /> ZIP mit Passwort versehen: </label>
    </div>
    <div class="col-md-6" >
      <div class="input-group">
        <span class="input-group-btn">
          <button id="ranNumgen" class="btn btn-default" type="button">Zufallszahl generieren</button>
        </span>
        <input id="randNum" type="text" name="password" class="form-control" placeholder="<?php echo $lang['PASSWORD']; ?>" />
      </div>
    </div>
    <div class="col-md-12">
      <br>
      <button type="submit" class="btn btn-warning" name="start_Download">Download ZIP<i class="fa fa-download"></i></button>
    </div>
  </div>
</form>

<script>
$('#ranNumgen').click(function(e){
  var text = "";
  var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  for (var i = 0; i < 8; i++)
    text += possible.charAt(Math.floor(Math.random() * possible.length));

  $('#randNum').val(text);
});
</script>
<?php include 'footer.php'; ?>
