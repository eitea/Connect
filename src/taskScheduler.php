<?php include 'header.php'; enableToCore($userID); ?>
<?php
if(isset($_POST['save_tasks'])){
  if(!empty($_POST['mail_runtime']) && test_date($_POST['mail_runtime'])){
    $pattern = intval($_POST['mail_repeat']);
    $runtime = carryOverAdder_Hours($_POST['mail_runtime'], $timeToUTC * -1); //UTC
    $conn->query("INSERT INTO $taskTable (id, repeatPattern, runtime, lastRuntime, description, callee) VALUES (1, '$pattern', '$runtime', '2000-01-01 12:00:00', 'Mailing schedule', 'sendMailReport.php')
                ON DUPLICATE KEY UPDATE repeatPattern = '$pattern', runtime = '$runtime'");
    if(!mysqli_error($conn)){
      echo '<div class="alert alert-success fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
    }
  }
}
$pattern = '-1';
$runtime = getCurrentTimestamp();

$result = $conn->query("SELECT * FROM taskData");
if($result && ($row = $result->fetch_assoc())){
  $pattern = $row['repeatPattern'];
  $runtime = $row['runtime'];
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
      <input type='text' maxlength='19' value='<?php echo $runtime; ?>' name='mail_runtime' class='form-control datetimepicker' />
    </div>
  </div>
</form>

<?php include 'footer.php'; ?>
