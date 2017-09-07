<?php include 'header.php'; enableToCore($userID); ?>
<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
  $error = false;
  if(isset($_POST['save_tasks'])){
    if(!empty($_POST['mail_runtime']) && test_date($_POST['mail_runtime'], "Y-m-d H:i")){
      $pattern = intval($_POST['mail_repeat']);
      $runtime = carryOverAdder_Hours($_POST['mail_runtime'], $timeToUTC * -1); //UTC
      $conn->query("INSERT INTO $taskTable (id, repeatPattern, runtime, lastRuntime, description, callee) VALUES (1, '$pattern', '$runtime', '2000-01-01 12:00:00', 'Mailing schedule', 'sendMailReport.php')
                  ON DUPLICATE KEY UPDATE repeatPattern = '$pattern', runtime = '$runtime'");
      if(mysqli_error($conn)){
        $error = true;
      }
    }
    if(!empty($_POST['restic_runtime']) && test_date($_POST['restic_runtime'], "Y-m-d H:i")){
      $pattern = intval($_POST['restic_repeat']);
      $runtime = carryOverAdder_Hours($_POST['restic_runtime'].':00', $timeToUTC * -1);
      $conn->query("INSERT INTO $taskTable (id, repeatPattern, runtime, lastRuntime, description, callee) VALUES (2, '$pattern', '$runtime', '2000-01-01 12:00:00', 'Restic Backup schedule', 'executeResticBackup.php')
                  ON DUPLICATE KEY UPDATE repeatPattern = '$pattern', runtime = '$runtime'");
      if(mysqli_error($conn)){
        $error = true;
      }
    }

    if(!empty($_POST['lunchbreak_repeat'])){
      $pattern = intval($_POST['lunchbreak_repeat']);
      $conn->query("INSERT INTO $taskTable (id, repeatPattern, runtime, lastRuntime, description, callee) VALUES (3, '$pattern', '2000-01-01 12:00:00', '2000-01-01 12:00:00', 'Lunchbreak Control', 'task_lunchbreak.php')
                  ON DUPLICATE KEY UPDATE repeatPattern = '$pattern'");
      if(mysqli_error($conn)){
        $error = true;
      }
    }
    if(!$error){
      echo '<div class="alert alert-success fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    } else {
      echo $conn->error;
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
  <div class="page-header">
    <h3><?php echo $lang['TASK_SCHEDULER']; ?><div class="page-header-button-group"><button type="submit" class="btn btn-default blinking" name="save_tasks"><i class="fa fa-floppy-o"></i></button></div></h3>
  </div>
  <h4>E-Mail Report</h4><br>
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
      <label>1. Runtime</label>
      <input type='text' maxlength='16' value='<?php echo substr($runtime,0,16); ?>' name='mail_runtime' class='form-control datetimepicker' />
    </div>
  </div>
  <hr>

<?php 
$result = $conn->query("SELECT * FROM taskData WHERE id = 2"); //really?
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
      <label>1. Runtime</label>
      <input type='text' maxlength='16' value='<?php echo substr($runtime,0,16); ?>' name='restic_runtime' class='form-control datetimepicker' />
    </div>
  </div>
<hr>

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
</form>

<?php include 'footer.php'; ?>
