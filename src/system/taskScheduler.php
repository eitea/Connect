<?php include dirname(__DIR__) . '/header.php'; enableToCore($userID); ?>
<?php
if(isset($_POST['save_task'])){
  $error = false;
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(test_date($_POST['mail_runtime_date'].' '.$_POST['mail_runtime_time'], "Y-m-d H:i")){
      $pattern = intval($_POST['mail_repeat']);
      $runtime = carryOverAdder_Hours($_POST['mail_runtime_date'].' '.$_POST['mail_runtime_time'], $timeToUTC * -1); //UTC
      $time = substr($runtime, 11, 8);
      $conn->query("INSERT INTO $taskTable (id, repeatPattern, runtime, lastRuntime, description, callee) VALUES (1, '$pattern', '$runtime', '2000-01-01 12:00:00', 'Mailing schedule', 'sendMailReport.php')
                  ON DUPLICATE KEY UPDATE repeatPattern = '$pattern', runtime = '$runtime', lastRuntime = CONCAT(DATE(lastRuntime), ' $time')");
      if(mysqli_error($conn)){
        $error = $conn->error;
      }
    }
    if(test_date($_POST['restic_runtime_date'].' '.$_POST['restic_runtime_time'], "Y-m-d H:i")){
      $pattern = intval($_POST['restic_repeat']);
      $runtime = carryOverAdder_Hours($_POST['restic_runtime_date'].' '.$_POST['restic_runtime_time'], $timeToUTC * -1);
      $time = substr($runtime, 11, 8);
      $conn->query("INSERT INTO $taskTable (id, repeatPattern, runtime, lastRuntime, description, callee) VALUES (2, '$pattern', '$runtime', '2000-01-01 12:00:00', 'Restic Backup schedule', 'executeResticBackup.php')
                  ON DUPLICATE KEY UPDATE repeatPattern = '$pattern', runtime = '$runtime', lastRuntime = CONCAT(DATE(lastRuntime), ' $time')");
      if(mysqli_error($conn)){
        $error = $conn->error;
      }
    }

    if(!empty($_POST['lunchbreak_repeat'])){
      $pattern = intval($_POST['lunchbreak_repeat']);
      $conn->query("INSERT INTO $taskTable (id, repeatPattern, runtime, lastRuntime, description, callee) VALUES (3, '$pattern', '2000-01-01 12:00:00', '2000-01-01 12:00:00', 'Lunchbreak Control', 'task_lunchbreak.php')
                  ON DUPLICATE KEY UPDATE repeatPattern = '$pattern'");
      if(mysqli_error($conn)){
        $error = $conn->error;
      }
    }
    if(!empty($_POST['email_task'])){
      $pattern = intval($_POST['email_task']);
      $conn->query("INSERT INTO $taskTable (id, repeatPattern, runtime, lastRuntime, description, callee) VALUES (4, '$pattern', '2000-01-01 12:00:00', '2000-01-01 12:00:00', 'Email Tasks', 'getAllEmailtasks.php')
                  ON DUPLICATE KEY UPDATE repeatPattern = '$pattern'");
      if(mysqli_error($conn)){
        $error = $conn->error;
      }
    }
    if(!$error){
      echo '<div class="alert alert-success fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    } else {
      echo $error;
    }
  }
}
$result = $conn->query("SELECT * FROM taskData WHERE id = 1");
if($result && ($row = $result->fetch_assoc())){
  $pattern = $row['repeatPattern'];
  $runtime = carryOverAdder_Hours($row['runtime'], $timeToUTC);
} else {
  $pattern = '-1';
  $runtime = getCurrentTimestamp();
}
?>

<form method="POST">
<div class="page-seperated-body" style="min-height:100vh">
  <div class="page-header page-seperated-section">
    <h3><?php echo $lang['TASK_SCHEDULER']; ?>
      <div style="display:inline;float:right;"><a role="button" data-toggle="collapse" href="#info_taskplanner"><i class="fa fa-info-circle"></i></a></div>
      <div class="page-header-button-group"><button type="submit" class="btn btn-default blinking" name="save_task"><i class="fa fa-floppy-o"></i></button></div>
    </h3>
  </div>
  <div class="collapse" id="info_taskplanner"><div class="well"><?php echo $lang['INFO_TASKS']; ?></div></div>


  <div class="page-seperated-section">
    <h4>E-Mail Report</h4>
    <br>
    <div class="container-fluid">
      <div class="col-sm-2">
        <label>Status</label>
        <select class="js-example-basic-single btn-block" name='mail_repeat'>
          <?php
          for($i = -1; $i < 5; $i++){
            if($pattern == $i){ $checked = 'selected'; } else { $checked = ''; }
            echo "<option $checked value='$i' >".$lang['SCHEDULE_TOSTRING'][$i] .'</option>';
          }
          ?>
        </select>
      </div>
      <div class="col-sm-3 col-sm-offset-1">
        <label>1. <?php echo $lang['RUNTIME'].' - '.$lang['DATE']; ?> </label>
        <input type='text' maxlength='10' value='<?php echo substr($runtime,0,10); ?>' name='mail_runtime_date' class='form-control datepicker' />
      </div>
      <div class="col-sm-2">
        <label><?php echo $lang['TIMES']; ?> </label>
        <input type='text' maxlength='5' value='<?php echo substr($runtime,11,5); ?>' name='mail_runtime_time' class='form-control timepicker' />
      </div>
    </div><br>
  </div>

  <br>

  <div class="page-seperated-section">
    <?php 
    $result = $conn->query("SELECT * FROM taskData WHERE id = 2");
    if($result && ($row = $result->fetch_assoc())){
      $pattern = $row['repeatPattern'];
      $runtime = carryOverAdder_Hours($row['runtime'], $timeToUTC);
    } else {
      $pattern = '-1';
      $runtime = getCurrentTimestamp();
    }
    ?>
    <h4>Restic Database Backup</h4><br>
    <div class="container-fluid">
      <div class="col-sm-2">
        <label>Status</label>
        <select class="js-example-basic-single btn-block" name='restic_repeat'>
          <?php
          for($i = -1; $i < 5; $i++){
            if($pattern == $i){ $checked = 'selected'; } else { $checked = ''; }
            echo "<option $checked value='$i' >".$lang['SCHEDULE_TOSTRING'][$i] .'</option>';
          }
          ?>
        </select>
      </div>
      <div class="col-sm-3 col-sm-offset-1">
        <label>1. <?php echo $lang['RUNTIME'].' - '.$lang['DATE']; ?> </label>
        <input type='text' maxlength='10' value='<?php echo substr($runtime,0,10); ?>' name='restic_runtime_date' class='form-control datepicker' />
      </div>
      <div class="col-sm-2">
        <label><?php echo $lang['TIMES']; ?> </label>
        <input type='text' maxlength='5' value='<?php echo substr($runtime,11,5); ?>' name='restic_runtime_time' class='form-control timepicker' />
      </div>
    </div><br>
  </div>

  <br>

  <div class="page-seperated-section">
  <?php 
  $result = $conn->query("SELECT * FROM taskData WHERE id = 3");
  if($result && ($row = $result->fetch_assoc())){
    $pattern = $row['repeatPattern'];
    $runtime = carryOverAdder_Hours($row['runtime'], $timeToUTC);
  } else {
    $pattern = '-1';
    $runtime = getCurrentTimestamp();
  }
  ?>
    <h4><?php echo $lang['ILLEGAL_LUNCHBREAK']; ?></h4><br>
    <div class="container-fluid">
      <div class="col-sm-2">
        <label>Status</label>
        <select class="js-example-basic-single btn-block" name='lunchbreak_repeat'>
          <?php        
          if($pattern == 1){ $checked = 'selected'; } else { $checked = ''; }
            echo "<option value='-1' >".$lang['SCHEDULE_TOSTRING'][-1] .'</option>';
            echo "<option $checked value='1' >".$lang['SCHEDULE_TOSTRING'][1] .'</option>';
          ?>
        </select>
      </div>
      <div class="col-sm-8 col-sm-offset-1">
        <?php echo $lang['INFO_LUNCHBREAK_TASK']; ?>
      </div>
    </div>
  </div>

  <br>

  <div class="page-seperated-section">
  <?php 
  $result = $conn->query("SELECT * FROM taskData WHERE id = 4");
  if($result && ($row = $result->fetch_assoc())){
    $pattern = $row['repeatPattern'];
    $runtime = carryOverAdder_Hours($row['runtime'], $timeToUTC);
  } else {
    $pattern = '-1';
    $runtime = getCurrentTimestamp();
  }
  ?>
    <h4><?php echo $lang['EMAIL_TASK']; ?></h4><br>
    <div class="container-fluid">
      <div class="col-sm-2">
        <label>Status</label>
        <select class="js-example-basic-single btn-block" name='email_task'>
        <?php
          for($i = -1; $i < 6; $i++){
            if($pattern == $i){ $checked = 'selected'; } else { $checked = ''; }
            echo "<option $checked value='$i' >".$lang['SCHEDULE_TOSTRING'][$i] .'</option>';
          }
          ?>
        </select>
      </div>
      <div class="col-sm-8 col-sm-offset-1">
        <?php echo $lang['INFO_LUNCHBREAK_TASK']; ?>
      </div>
    </div>
  </div>
</div>
</form>

<?php include dirname(__DIR__) . '/footer.php'; ?>
