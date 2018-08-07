<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php
if(isset($_POST['accept'])){
  $accept = true;
  if($_FILES["fileToUpload"]["error"] > 0){
    $accept = false;
    $error_output = $_FILES["fileToUpload"]["error"] .'<a href="http://php.net/manual/en/features.file-upload.errors.php" target="_blank"> Fehlermeldung</a>';
  } elseif ($_FILES["fileToUpload"]["size"] <= 0) {
    $accept = false;
    $error_output = "Fehler beim Upload";
} elseif ($_FILES["fileToUpload"]["size"] > 32000000) {
    $accept = false;
    $error_output = "Datei zu groß (max 32Mb)";
  } elseif ($_FILES["fileToUpload"]["type"] != "application/octet-stream") {
    $accept = false;
    $error_output = "Ungültige Datei";
  }

  if($accept){
    $file = fopen($_FILES['fileToUpload']['tmp_name'], 'rb');
    if(!$file){
      $accept = false;
    }
  }

  if($accept){
    //changes here have to be copied to resticBackup.php
    require dirname(dirname(dirname(__DIR__))).'/plugins/mysqldump/MySQLImport.php';
    $conn->close();
    $conn = new mysqli($servername,$username,$password);
    $conn->query("DROP DATABASE $dbName");
    $conn->query("CREATE DATABASE $dbName");
    $conn->close();
    $conn = new mysqli($servername,$username,$password,$dbName);
    $import = new MySQLImport($conn);
    $import->load($file);
    redirect("../user/logout");
  }
}

if($error_output){
  echo "<div class='alert alert-danger'>Error: $error_output</div>";
}
?>

<div class="page-header">
<h4><?php echo $lang['DB_RESTORE']; ?></h4>
</div>
<br>
<?php echo $lang['WARNING_RESTORE']; ?>
<br><br>

<?php
$accept = true;
if(getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])){
  $accept = false;
  $hash = '$2y$10$UsylMC44RCq73448jLFhU.SMzSBpFGK5d0uRSgh.7rLmo.f2gOvyO';
  if(crypt($_POST['restore_password'], $hash) == $hash){ $accept = true; }
}
if($accept && isset($_POST['letsAccept'])):  ?>
  <form method="post" enctype="multipart/form-data">
    <div class="container-fluid">
      <br>
      <input type="file" name="fileToUpload" id="fileToUpload" />
      <br><br>
      <button class="btn btn-warning" type="submit" name="accept"><i class='fa fa-upload'></i> Upload</button>
      <small>.sql Format</small>
    </div>
  </form>
<?php else: ?>
  <form method="post">
    <?php if(getenv('IS_CONTAINER') || isset($_SERVER['IS_CONTAINER'])): ?>
      <div class="col-sm-2"><input type="text" name="restore_password" placeholder="password" class="form-control" /></div>
    <?php endif; ?>
    <div class="col-sm-3"><button class="btn btn-warning" type="submit" name="letsAccept"><i class='fa fa-upload'></i> <?php echo $lang['YES_I_WILL']; ?></button></div>
  </form>
<?php endif; ?>
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
