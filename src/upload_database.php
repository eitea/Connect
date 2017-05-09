<?php require 'header.php'; ?>

<?php
if(isset($_POST['accept'])){
  $accept = true;
  if($_FILES["fileToUpload"]["error"] > 0){
    $accept = false;
  } elseif ($_FILES["fileToUpload"]["size"] <= 0) {
    $accept = false;
  } elseif ($_FILES["fileToUpload"]["size"] > 5000000) { //5mb
    $accept = false;
  } elseif ($_FILES["fileToUpload"]["type"] != "application/octet-stream") {
    $accept = false;
  }

  if($accept){
    $file = fopen($_FILES['fileToUpload']['tmp_name'], 'rb');

    if($conn->query("DROP DATABASE $dbName")){
      $conn->query("CREATE DATABASE $dbName");
    } else {
      die(mysqli_error($conn));
    }
    $conn->close();
    $conn = new mysqli($servername, $username, $password, $dbName);

    $conn->query("SET FOREIGN_KEY_CHECKS=0;");
    $templine = '';
    while(($line = fgets($file)) !== false){
      $line = utf8_decode($line);
      //Skip comments
      if (substr($line, 0, 2) == '--' || $line == '') continue;

      $templine .= $line;
      //semicolon at the end = end of the query
      if(substr(trim($line), -1, 1) == ';'){
        $conn->query($templine) or print(mysqli_error($conn));
        $templine = '';
      }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=1;");
    if(!mysqli_error($conn)){
      redirect("logout.php");
    } else {
      $error_output = mysqli_error($conn);
    }
  } else {
    $error_output = $lang['ERROR_INVALID_UPLOAD'];
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

<?php if(isset($_POST['letsAccept'])):  ?>
  <form method="post" enctype="multipart/form-data">
    <div class="container-fluid">
      <br>
      <input type="file" name="fileToUpload" id="fileToUpload" />
      <br><br>
      <button class="btn btn-warning" type="submit" name="accept"><i class='fa fa-upload'></i> Upload</button>
    </div>
  </form>
<?php else: ?>
  <form method="post">
    <button class="btn btn-warning" type="submit" name="letsAccept"><i class='fa fa-upload'></i> <?php echo $lang['YES_I_WILL']; ?></button>
  </form>
<?php endif; ?>
