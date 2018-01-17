<?php include 'header.php'; enableToCore($userID);?>
<?php
if(isset($_POST['saveButton'])){
  if(isset($_POST['ssl'])){
    $status = 'TRUE';
  } else {
    $status = 'FALSE';
  }
  $sql = "UPDATE $adminGitHubTable SET sslVerify = '$status'";
  $conn->query($sql);

  if(isset($_POST['cd'])){
    $cd = $_POST['cd'];
    $sql = "UPDATE $configTable SET cooldownTimer = '$cd';";
    $conn->query($sql);
  }

  if(isset($_POST['bufferTime'])){
    $bufferTime = $_POST['bufferTime'];
    $sql = "UPDATE $configTable SET bookingTimeBuffer = '$bufferTime';";
    $conn->query($sql);
  }

  if(isset($_POST['enableReadyCheck'])){
    $status = 'TRUE';
  } else {
    $status = 'FALSE';
  }
  if(isset($_POST['enableReg'])){
    $regStatus = 'TRUE';
  } else {
    $regStatus = 'FALSE';
  }
  $sql = "UPDATE $configTable SET enableReadyCheck = '$status', enableReg = '$regStatus'";
  $conn->query($sql);

  $status = isset($_POST['enableSocialMedia']) ? 'TRUE' : 'FALSE';
  $conn->query("UPDATE $moduleTable SET enableSocialMedia = '$status'");

  $status = isset($_POST['enableDynamicProjects']) ? 'TRUE' : 'FALSE';
  $conn->query("UPDATE $moduleTable SET enableDynamicProjects = '$status'");

  $status = isset($_POST['enableS3Archive']) ? 'TRUE' : 'FALSE';
  $conn->query("UPDATE $moduleTable SET enableS3Archive = '$status'");

  redirect("../system/advanced");
}

if(isset($_POST['saveS3'])){
  if(isset($_POST['server'])){
    require dirname(__DIR__)."\plugins\aws\autoload.php";
    try{
      $credentials = array('key' => $_POST['aKey'], 'secret' => $_POST['sKey']);
      $testconfig = array(
        'version' => 'latest',
        'region' => '',
        'endpoint' => $_POST['server'],
        'use_path_style_endpoint' => true,
        'credentials' => $credentials
      );
      $test = new Aws\S3\S3Client($testconfig);
      $test->listBuckets();
      $conn->query("UPDATE $moduleTable SET enableS3Archive = 'TRUE'");
      $conf = fopen(dirname(__DIR__) .'/src/connection_config.php', 'a');
      $txt = "\$credentials = array('key' => '".$_POST['aKey']."', 'secret' => '".$_POST['sKey']."');\$s3config = array('version' => 'latest','region' => '','endpoint' => '".$_POST['server']."','use_path_style_endpoint' => true,'credentials' => '\$credentials');";
      fwrite($conf, $txt);
      fclose($conf);
    }catch(Exception $e){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
    }
  }else{
    try{
      $lines = file(dirname(__DIR__) .'/src/connection_config.php');
      $last = sizeof($lines)-1;
      unset($lines[$last]);

      $conf = fopen(dirname(__DIR__) .'/src/connection_config.php', 'w');
      fwrite($conf,implode('',$lines));
      fclose($conf);

      $conn->query("UPDATE $moduleTable SET enableS3Archive = 'FALSE'");
    }catch(Exception $e){
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$e.'</div>';
    }
  }
}


$result = $conn->query("SELECT sslVerify FROM $adminGitHubTable");
$rowGitHubTable = $result->fetch_assoc();

$result = $conn->query("SELECT * FROM $configTable");
$rowConfigTable = $result->fetch_assoc();

$result = $conn->query("SELECT * FROM modules");
$rowModuleTable = $result->fetch_assoc();
?>

<form method=post>
  <div class="page-header">
    <h3><?php echo $lang['ADVANCED_OPTIONS']; ?>  <div class="page-header-button-group"><button type="submit" class="btn btn-default blinking" name="saveButton"><i class="fa fa-floppy-o"></i></button></div></h3>
  </div>

  <h4>GitHub</h4>
  <div class="container-fluid">
    <br>
    <div class="checkbox col-md-12">
      <input <?php if($rowGitHubTable['sslVerify'] == 'TRUE'){echo 'checked';} ?> type='checkbox' name='ssl' value='TRUE'>
      SSL Certificate Validation
    </div>
    <br>
  </div>
  <br><hr><br>

  <h4>Buffers</h4>
  <div class="container-fluid">
    <div class="container-fluid">
      <div class=col-md-6>
        Disable-time for In/Out Buttons:
        <input type="number" class="form-control" name="cd" value="<?php echo $rowConfigTable['cooldownTimer']; ?>">
      </div>
      <div class=col-md-6>
        Project-Time Buffer
        <input type="number" class="form-control" name="bufferTime" value="<?php echo $rowConfigTable['bookingTimeBuffer'] ?>" >
      </div>
    </div>
    <br>
  </div>
  <br><hr><br>

  <h4>Display</h4>
  <div class="container-fluid">
    <br>
    <div class="checkbox col-md-12">
      <input <?php if($rowConfigTable['enableReadyCheck'] == 'TRUE'){echo 'checked';} ?> type='checkbox' name='enableReadyCheck' value='TRUE'>
      Display Attendance to all Users
    </div>
    <br>
  </div>
  <br><hr><br>

  <h4>Modules</h4>
  <div class="container-fluid">
    <br>
    <div class="checkbox col-md-12">
      <label>
        <input <?php if($rowModuleTable['enableS3Archive'] == 'TRUE'){echo "checked ";} ?> data-toggle='modal' data-target='#s3Input'  onChange="showS3Input(event)" type='checkbox' name='enableS3Archive' value='TRUE'>
        S3 Archive
      </label>
    </div>
    <br>
  </div>
  <br><hr><br>

  <h4>Self Registration</h4>
  <div class="container-fluid">
    <br>
    <div class="checkbox col-md-12">
      <input <?php if($rowConfigTable['enableReg'] == 'TRUE'){echo 'checked';} ?> type='checkbox' name='enableReg' value='TRUE'>
      Allow Users to Register themselves
    </div>
    <br>
  </div>


  <div class="modal fade" id="s3Input" role="dialog">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <h4 class="modal-title">Archiv Zugangsdaten</h4>
              </div>
              <br>
              <div class="modal-body">
              <?php if($rowModuleTable['enableS3Archive'] == 'FALSE'){
                  echo '<div class="col-md-12"><label>Server</label><input class="form-control" name="server"><br></div>';
                  echo '<div class="col-md-12"><label>Access Key</label><input class="form-control" name="aKey"><br></div>';
                  echo '<div class="col-md-12"><label>Secret Key</label><input class="form-control" name="sKey"><br></div>';
                }else{
                  echo '<div class="col-md-12"><label>Are your sure you want to delete your S3 Configuration?</label><br></div>';
                } ?>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="saveS3"><?php echo $lang['SAVE']; ?></button>
              </div>
          </div>
      </div>
  </div>
</form>
<script>
  function showS3Input(event){
    if(event.target.checked){
      event.target.checked = false;
    }else{
      event.target.checked = true;
    }
    console.log(event);
  }
</script>

<!-- /BODY -->
<?php include 'footer.php'; ?>
