<?php include 'header.php'; ?>
<?php enableToStamps($userID);?>
<!-- BODY -->

<link rel="stylesheet" type="text/css" href="../plugins/datepicker/css/datepicker.css">
<script src="../plugins/datepicker/js/bootstrap-datepicker.js"> </script>

<div class="page-header">
  <h3><?php echo $lang['TIMESTAMPS']; ?></h3>
</div>

<?php
require 'Calculators/MonthlyCalculator.php';
$currentTimeStamp = getCurrentTimestamp();
if(isset($_POST['newMonth'])){
  $currentTimeStamp = $_POST['newMonth']. '-01 05:00:00';
}
if(isset($_POST['request_submit']) && !empty($_POST['request_start']) && !empty($_POST['request_end'])){
  $arr = explode(' ', $_POST['request_date']); //0- indexIM, 1- date
  $startTime = $arr[1] .' '. test_input($_POST['request_start']).':00';
  $endTime = $arr[1] .' '. test_input($_POST['request_end']).':00';
  $requestText = test_input($_POST['request_text']);
  if(timeDiff_Hours($startTime, $endTime) > 0){
    $conn->query("INSERT INTO $userRequests(userID, fromDate, toDate, status, requestText, requestType, requestID) VALUES($userID, '$startTime', '$endTime', '0', '$requestText', 'log', ".$arr[0].")");
  } else {
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Could not create request: </strong>Invalid times.';
    echo '</div>';
  }
}
echo mysqli_error($conn);
?>

<form method="post" id="form1">
  <div class="row form-group">
    <div class="col-xs-6">
      <div class="input-group">
        <input id="calendar" readonly type="text" class="form-control from" name="newMonth" value= <?php echo substr($currentTimeStamp,0,7); ?> >
        <span class="input-group-btn">
          <button class="btn btn-warning" type="submit">Filter</button>
        </span>
      </div>
    </div>
  </div>
  <!-- request collapse -->
  <?php if(isset($_POST['aButton'])): ?>
    <div class="well">
      <div class="container-fluid">
        <div class="col-md-3">
          <label>ID</label>
          <input id="request_date" type="text" class="form-control" name="request_date" readonly value="<?php echo test_input($_POST['aButton']); ?>"/>
        </div>
        <div class="col-md-2">
          <label>Neuer Anfang</label>
          <input type="time" name="request_start" class="form-control" />
        </div>
        <div class="col-md-2">
          <label>Neues Ende</label>
          <input type="time" name="request_end" class="form-control" />
        </div>
        <div class="col-md-4">
          <label>Infotext</label>
          <input type="text" name="request_text" class="form-control" placeholder="(Optional)"/>
        </div>
        <div class="col-md-1">
          <label>Okay</label>
          <button type="submit" class="btn btn-warning" name="request_submit"><?php echo $lang['REQUESTS']; ?></button>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <!-- /request collapse -->
</form>
<script>
$("#calendar").datepicker({
  format: "yyyy-mm",
  viewMode: "months",
  minViewMode: "months"
});
</script>

<div class="table-responsive">
  <table class="table table-striped">
    <thead>
      <th><?php echo $lang['WEEKLY_DAY']?></th>
      <th><?php echo $lang['DATE']?></th>
      <th><?php echo $lang['BEGIN']?></th>
      <th><?php echo $lang['BREAK']?></th>
      <th><?php echo $lang['END']?></th>
      <th><?php echo $lang['ACTIVITY']?></th>
      <th><?php echo $lang['SHOULD_TIME']?></th>
      <th><?php echo $lang['IS_TIME']?></th>
      <th><?php echo $lang['DIFFERENCE']?></th>
      <th>Saldo</th>
      <th></th>
    </thead>
    <tbody>
      <?php
      $calculator = new Monthly_Calculator($currentTimeStamp, $userID);

      if($calculator->correctionHours){
        echo "<tr style='font-weight:bold;'>";
        echo "<td>".$lang['CORRECTION']." </td>";
        echo "<td>".$lang_monthToString[intval(substr($calculator->date[0],5,2))]."</td><td></td><td>-</td><td></td><td>-</td><td></td><td></td><td></td>";
        echo "<td>" . sprintf('%+.2f', $calculator->correctionHours) . "</td>";
        echo "</tr>";
      }

      $absolvedHours = array();
      $accumulatedSaldo = $calculator->correctionHours;
      for($i = 0; $i < $calculator->days; $i++){
        if($calculator->end[$i] == '0000-00-00 00:00:00'){
          $endTime = getCurrentTimestamp();
        } else {
          $endTime = $calculator->end[$i];
        }

        $difference = timeDiff_Hours($calculator->start[$i], $endTime );
        $absolvedHours[] = $difference - $calculator->lunchTime[$i];

        if($calculator->start[$i] != '-'){
          $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
        } else {
          $A = $calculator->start[$i];
        }
        if($calculator->end[$i] != '-'){
          $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);
        } else {
          $B = $calculator->end[$i];
        }

        $theSaldo = round($difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i], 2);
        $saldoStyle = '';
        if($theSaldo < 0){
          $saldoStyle = 'style=color:#fc8542;'; //red
        } elseif($theSaldo > 0) {
          $saldoStyle = 'style=color:#6fcf2c;'; //green
        }
        $neutralStyle = '';
        if($calculator->shouldTime[$i] == 0 && $difference == 0){
          $neutralStyle = "style=color:#c7c6c6;";
        }

        $accumulatedSaldo += $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];

        echo "<tr $neutralStyle>";
        echo "<td>" . $lang_weeklyDayToString[$calculator->dayOfWeek[$i]] . "</td>";
        echo "<td>" . $calculator->date[$i] . "</td>";
        echo "<td>" . substr($A,11,5) . "</td>";
        echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
        echo "<td>" . substr($B,11,5) . "</td>";
        echo "<td>" . $lang_activityToString[$calculator->activity[$i]] . "</td>";
        echo "<td>" . displayAsHoursMins($calculator->shouldTime[$i]) . "</td>";
        echo "<td>" . displayAsHoursMins($difference - $calculator->lunchTime[$i]) . "</td>";
        echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . "</td>";
        echo "<td><small>" . displayAsHoursMins($accumulatedSaldo) . "</small></td>";
        echo "<td><button type='submit' form='form1' class='btn btn-default' value='".$calculator->indecesIM[$i].' '.$calculator->date[$i]."' name='aButton' ><i class='fa fa-pencil'></i></button></td>";
        echo "</tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<!-- /BODY -->
<?php include 'footer.php'; ?>
