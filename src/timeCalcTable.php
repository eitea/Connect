<?php include 'header.php';?>
<?php enableToStamps($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['MONTHLY_REPORT']; ?></h3>
</div>

<?php
require 'Calculators/IntervalCalculator.php';
$currentTimeStamp = substr(getCurrentTimestamp(),0,7). '-01 05:00:00';
if(isset($_POST['newMonth'])){
  $currentTimeStamp = $_POST['newMonth']. '-01 05:00:00';
}
if(isset($_POST['request_submit']) && !empty($_POST['request_start']) && !empty($_POST['request_end'])){
  $arr = explode(' ', $_POST['request_submit']); //0- indexIM, 1- date
  $startTime = $arr[1] .' '. test_input($_POST['request_start']).':00';
  $endTime = $arr[1] .' '. test_input($_POST['request_end']).':00';
  $requestText = test_input($_POST['request_text']);
  if(timeDiff_Hours($startTime, $endTime) > 0){
    $sql = "INSERT INTO $userRequests(userID, fromDate, toDate, status, requestText, requestType, requestID) VALUES($userID, '$startTime', '$endTime', '0', '$requestText', 'log', '".$arr[0]."' )";
    if($conn->query($sql)){
      echo '<div class="alert alert-success fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo $lang['OK_REQUEST'];
      echo '</div>';
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo $conn->error;
      echo '</div>';
    }
  } else {
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Error: </strong>'.$lang['ERROR_TIMES_INVALID']." $startTime, $endTime";
    echo '</div>';
  }
}
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
</form>

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
    $now = $currentTimeStamp;
    $calculator = new Interval_Calculator($now, carryOverAdder_Hours(date('Y-m-d H:i:s',strtotime('+1 month', strtotime($now))), -24), $userID);
    if(!empty($calculator->monthly_correctionHours[0])){
      $corrections = array_sum($calculator->monthly_correctionHours);
      echo "<tr style='font-weight:bold;'>";
      echo "<td>".$lang['CORRECTION']." </td>";
      echo "<td>".$lang['MONTH_TOSTRING'][intval(substr($calculator->date[0],5,2))]."</td><td></td><td>-</td><td></td><td>-</td><td></td><td></td><td></td>";
      echo "<td>".displayAsHoursMins($corrections)."</td><td></td>";
      echo "</tr>";
    } else {
      $corrections = 0;
    }
    $accumulatedSaldo = $corrections;
    for($i = 0; $i < $calculator->days; $i++){
      if($calculator->start[$i]){
        $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
      } else {
        $A = $calculator->start[$i];
      }
      if($calculator->end[$i]){
        $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);
      } else {
        $B = $calculator->end[$i];
      }

      $theSaldo = round($calculator->absolvedTime[$i] - $calculator->lunchTime[$i] - $calculator->shouldTime[$i], 2);
      $saldoStyle = '';
      if($theSaldo < 0){
        $saldoStyle = 'style=color:#fc8542;'; //red
      } elseif($theSaldo > 0) {
        $saldoStyle = 'style=color:#6fcf2c;'; //green
      }
      $neutralStyle = '';
      if($calculator->shouldTime[$i] == 0 && $calculator->absolvedTime[$i] == 0){
        $neutralStyle = "style=color:#c7c6c6;";
      }

      $accumulatedSaldo += $theSaldo;

      echo "<tr $neutralStyle>";
      echo "<td>" . $lang['WEEKDAY_TOSTRING'][$calculator->dayOfWeek[$i]] . "</td>";
      echo "<td>" . $calculator->date[$i] . "</td>";
      echo "<td>" . substr($A,11,5) . "</td>";
      echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
      echo "<td>" . substr($B,11,5) . "</td>";
      echo "<td>" . $lang['ACTIVITY_TOSTRING'][$calculator->activity[$i]] . "</td>";
      echo "<td>" . displayAsHoursMins($calculator->shouldTime[$i]) . "</td>";
      echo "<td>" . displayAsHoursMins($calculator->absolvedTime[$i] - $calculator->lunchTime[$i]) . "</td>";
      echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . "</td>";
      echo "<td><small>" . displayAsHoursMins($accumulatedSaldo) . "</small></td>";
      echo '<td>';
      echo "<button type='button' class='btn btn-default' data-toggle='modal' data-target='.my-request-$i' ><i class='fa fa-pencil'></i></button>";
      if($calculator->indecesIM[$i]){
        $bookingResult = $conn->query("SELECT id FROM projectBookingData WHERE timestampID = ".$calculator->indecesIM[$i]);
        if($bookingResult && $bookingResult->num_rows > 0){
          echo "<button type='button' class='btn btn-default' data-toggle='modal' data-target='.my-bookings-".$calculator->indecesIM[$i]."' ><i class='fa fa-file-text-o'></i></button>";
        }
      }
      echo '</td>';
      echo "</tr>";
    }
    ?>
  </tbody>
</table>

<?php for($i = 0; $i < $calculator->days; $i++): ?>
  <form method="POST">
    <div class="modal fade my-request-<?php echo $i; ?>">
      <div class="modal-dialog modal-md modal-content">
        <div class="modal-header">
          <h4>Anfrage: <?php echo $calculator->date[$i]; ?></h4>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <div class="col-md-6">
              <label>Neuer Anfang</label>
              <input type="time" name="request_start" class="form-control" />
            </div>
            <div class="col-md-6">
              <label>Neues Ende</label>
              <input type="time" name="request_end" class="form-control" />
            </div>
          </div>
          <div class="container-fluid">
            <div class="col-md-4">
              <label>Infotext</label>
              <input type="text" name="request_text" class="form-control" placeholder="(Optional)"/>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default"data-dismiss="modal" >Cancel</button>
          <button type="submit" class="btn btn-warning" name="request_submit" value="<?php echo $calculator->indecesIM[$i].' '.$calculator->date[$i]; ?>"><?php echo $lang['REQUESTS']; ?></button>
        </div>
      </div>
    </div>
  </form>
  <?php
  $bookingResult = false;
  if($calculator->indecesIM[$i]){
    $bookingResult = $conn->query("SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM projectBookingData
      LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
      LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)
      WHERE timestampID = '".$calculator->indecesIM[$i]."' ORDER BY end ASC");
  }
  echo mysqli_error($conn);
  if($bookingResult && $bookingResult->num_rows > 0):
  ?>
  <div class="modal fade my-bookings-<?php echo $calculator->indecesIM[$i]; ?>">
    <div class="modal-dialog modal-lg modal-content">
      <div class="modal-header">
        <h4><?php echo $calculator->date[$i]; ?></h4>
      </div>
      <div class="modal-body">
        <table class="table table-hover">
          <thead>
            <th></th>
            <th><?php echo $lang['CLIENT']; ?></th>
            <th><?php echo $lang['PROJECT']; ?></th>
            <th width="15%">Datum</th>
            <th>Start</th>
            <th><?php echo $lang['END']; ?></th>
            <th>Info</th>
          </thead>
          <tbody>
            <?php
            while($row = $bookingResult->fetch_assoc()) {
              $x = $row['id'];
              $A = substr(carryOverAdder_Hours($row['start'], $timeToUTC), 11, 5);
              $B = substr(carryOverAdder_Hours($row['end'], $timeToUTC), 11, 5);
              $C = $row['infoText'];
              $icon = "fa fa-bookmark";
              if($row['bookingType'] == 'break'){
                $icon = "fa fa-cutlery";
              } elseif($row['bookingType'] == 'drive'){
                $icon = "fa fa-car";
              } elseif($row['bookingType'] == 'mixed'){
                $icon = "fa fa-plus";
                $C = $lang['ACTIVITY_TOSTRING'][$row['mixedStatus']];
              }
              echo '<tr>';
              echo "<td><i class='$icon'></i></td>";
              echo "<td>". $row['name'] ."</td>";
              echo "<td>". $row['projectName'] ."</td>";
              echo "<td>". substr($row['start'], 0, 10) ."</td>";
              echo "<td>$A</td>";
              echo "<td>$B</td>";
              echo "<td style='text-align:left'>$C</td>";
              echo '</tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal" >OK</button>
      </div>
    </div>
  </div>
  <?php endif; ?>
<?php endfor; ?>


<script>
$("#calendar").datepicker({
  format: "yyyy-mm",
  viewMode: "months",
  minViewMode: "months"
});
</script>
<!-- /BODY -->
<?php include 'footer.php'; ?>
