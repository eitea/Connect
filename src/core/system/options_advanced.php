<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
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

  redirect("../system/advanced");
}



$result = $conn->query("SELECT sslVerify FROM $adminGitHubTable");
$rowGitHubTable = $result->fetch_assoc();

$result = $conn->query("SELECT * FROM configurationData");
$rowConfigTable = $result->fetch_assoc();

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


  <h4>Self Registration</h4>
  <div class="container-fluid">
    <br>
    <div class="checkbox col-md-12">
      <input <?php if($rowConfigTable['enableReg'] == 'TRUE'){echo 'checked';} ?> type='checkbox' name='enableReg' value='TRUE'>
      Allow Users to Register themselves
    </div>
    <br>
  </div>


</form>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
