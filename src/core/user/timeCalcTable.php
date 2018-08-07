<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
$filterings = array('logs' => array(0, 'checked'), 'date' => array(substr(getCurrentTimestamp(),0,7)));

require dirname(dirname(__DIR__)) . '/Calculators/IntervalCalculator.php';
if(isset($_POST['request_submit'])){
  $arr = explode(' ', $_POST['request_submit']); //0- indexIM, 1- date
  if(!empty($_POST['request_start']) && isset($arr[1])){
    $startTime = $arr[1] .' '. test_input($_POST['request_start']).':00';
    if($_POST['request_open']){
      $endTime = '0000-00-00 00:00:00';
    } else {
      if(empty($_POST['request_end'])){
        $endTime = '0000-00-00 00:00:00';
      } else {
        $endTime = $arr[1] .' '. test_input($_POST['request_end']).':00';
        if(timeDiff_Hours($startTime, $endTime) < 0){
          echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_TIMES_INVALID'].'</div>';
          include dirname(dirname(__DIR__)) . '/footer.php';
          die(); //still better than a goto.
        }
      }
    }
    $requestText = test_input($_POST['request_text']);
    if(test_Date($startTime)){
	  $conn->query("INSERT INTO userRequestsData(userID, fromDate, toDate, status, requestText, requestType, requestID, timeToUTC)
		VALUES($userID, '$startTime', '$endTime', '0', '$requestText', 'log', '".$arr[0]."', $timeToUTC )");
      if(!$conn->error){
		showSuccess($lang['OK_REQUEST']);
      } else {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
      }
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_TIMES_INVALID'].'</div>';
    }
  } else {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].' '.$lang['BEGIN'].' '.$lang['MISSING'].'.</div>';
  }
} elseif(!empty($_POST['splits_save'])) {
  $x = intval($_POST['splits_save']);
  if(!empty($_POST['splits_from_'.$x]) && !empty($_POST['splits_to_'.$x])){
    $result = $conn->query("SELECT id, timestampID, start, end, timeToUTC FROM projectBookingData INNER JOIN logs ON logs.indexIM = projectBookingData.timestampID WHERE id = $x AND bookingType = 'break'");
    if($result && ($row = $result->fetch_assoc())){
      $row['start'] = substr($row['start'],0, 16).':00'; //UTC
      $row['end'] = substr($row['end'],0, 16).':00';
      $split_A = carryOverAdder_Hours(substr_replace($row['start'], $_POST['splits_from_'.$x], 11).':00', ($row['timeToUTC']*-1)); //UTC
      $split_B = carryOverAdder_Hours(substr_replace($row['end'], $_POST['splits_to_'.$x], 11).':00', ($row['timeToUTC']*-1));
      //valid times
      if(timeDiff_Hours($row['start'], $split_A) >= 0 && timeDiff_Hours($split_B, $row['end']) >= 0 && timeDiff_Hours($split_A, $split_B) > 0){
        $splits_activity = intval($_POST['splits_activity_'.$x]);
        $sql = "INSERT INTO userRequestsData (userID, fromDate, toDate, status, requestText, requestType, requestID) VALUES($userID, '$split_A', '$split_B', '0', '$splits_activity', 'div', '$x')";
        if($conn->query($sql)){
          echo '<div class="alert alert-success fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['OK_REQUEST'].'</div>';
        }
      } else {
        echo '<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">&times;</a>'.$lang['ERROR_TIMES_INVALID'].'</div>';
      }
    } else {
      $conn->query("UPDATE UserData SET strikeCount = strikecount + 1 WHERE id = $userID");
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Please do not try this again. It will not work.</strong> '.$lang['ERROR_STRIKE'].'</div>';
      include dirname(dirname(__DIR__)) . '/footer.php';
      die(); //TODO for later: we should create a strike system.
    }
  } else {
    echo '<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
  }
}
?>
<div class="page-header-fixed">
<div class="page-header">
  <h3><?php echo $lang['MONTHLY_REPORT']; ?><div class="page-header-button-group"><?php include dirname(dirname(__DIR__)) . '/misc/set_filter.php'; ?></div></h3>
</div>
</div>
<div class="page-content-fixed-130">
<table class="table table-hover datatable">
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
    <th></th>
  </thead>
  <tbody>
    <?php
    $now = $filterings['date'][0].'-01';
    $end = date('Y-m-t', strtotime($now));
    $calculator = new Interval_Calculator($userID, $now, $end);

    $accumulatedSaldo = 0;
    for($i = 0; $i < $calculator->days; $i++){
      if($filterings['logs'][0] && $calculator->activity[$i] != $filterings['logs'][0]) continue;
      if($filterings['logs'][1] == 'checked' && $calculator->shouldTime[$i] == 0 && $calculator->absolvedTime[$i] == 0) continue;

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
      echo '<td>'.$lang['EMOJI_TOSTRING'][$calculator->feeling[$i]].'</td>';
      echo '<td>';
      echo "<button type='button' class='btn btn-default' data-toggle='modal' data-target='.my-request-$i' ><i class='fa fa-pencil'></i></button>";
      if($calculator->indecesIM[$i]){
        $bookingResult = $conn->query("SELECT id FROM projectBookingData WHERE timestampID = ".$calculator->indecesIM[$i]);
        if($bookingResult && $bookingResult->num_rows > 0){
          echo " <button type='button' class='btn btn-default' data-toggle='modal' data-target='.my-bookings-".$calculator->indecesIM[$i]."' ><i class='fa fa-file-text-o'></i></button>";
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
          <div class="row">
            <div class="col-md-6">
              <label><?php echo $lang['FROM']; ?></label>
              <br>
              <div class="radio"> <!-- beauty purposes -->
                <input type="text" name="request_start" class="form-control timepicker" value="<?php echo substr(carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]), 11, 5); ?>" />
              </div>
            </div>
            <div class="col-md-6">
              <label><?php echo $lang['TO']; ?></label>
              <div class="radio">
                <label><input type="radio" name="request_open" value="0" /><input type="text" name="request_end" class="form-control timepicker" style="display:inline;max-width:200px;" min="01:00" value="<?php echo substr(carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]), 11, 5); ?>"/></label>
                <br><br>
                <label><input type="radio" name="request_open" value="1" checked /> <?php echo $lang['OPEN']; ?></label>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-12">
              <label>Infotext</label>
              <input type="text" name="request_text" class="form-control" placeholder="(Optional)"/>
              <small>Anfangs- und Endzeit müssen angegeben werden. Die Anfangszeit muss immer kleiner als die Endzeit sein. Leere Felder sind ungültig.
              Die Uhrzeit wird bei Bewilligung exakt auf die angegebenen Daten <u>ausgebessert</u>.  Sonderzeichen werden automatisch entfernt.</small>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default"data-dismiss="modal" >Cancel</button>
          <button type="submit" class="btn btn-warning" name="request_submit" value="<?php echo $calculator->indecesIM[$i].' '.$calculator->date[$i]; ?>"><?php echo $lang['MAKE_REQUEST']; ?></button>
        </div>
      </div>
    </div>
  </form>

  <!-- BOOKINGS -->
  <?php
  $bookingResult = false;
  if($calculator->indecesIM[$i]){
    $bookingResult = $conn->query("SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM projectBookingData
      LEFT JOIN logs ON logs.indexIM = projectBookingData.timestampID
      LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
      LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)
      WHERE timestampID = '".$calculator->indecesIM[$i]."' ORDER BY end ASC");
    }
    echo mysqli_error($conn);
    if($bookingResult && $bookingResult->num_rows > 0):
      ?>
      <form method="POST">
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
                  <th width="100px">Datum</th>
                  <th width="80px">Start</th>
                  <th width="80px"><?php echo $lang['END']; ?></th>
                  <th>Info</th>
                  <th></th>
                </thead>
                <tbody>
                  <?php
                  while($row = $bookingResult->fetch_assoc()) {
                    $A = substr(carryOverAdder_Hours($row['start'], $row['timeToUTC']), 11, 5);
                    $B = substr(carryOverAdder_Hours($row['end'], $row['timeToUTC']), 11, 5);
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
                    echo '<td></td><td></td></tr>';
                    if($row['bookingType'] == 'break'){
                      $x = $row['bookingTableID'];
                      echo '<tr style="background-color:#f0f0f0">';
                      echo "<td></td><td></td><td><i class='fa fa-arrow-right'</td><td>Split:</td>";
                      echo '<td><input type="text" min="'.$A.'" max="'.$B.'" class="form-control timepicker" name="splits_from_'.$x.'" />'.'</td>';
                      echo '<td><input type="text" min="'.$A.'" max="'.$B.'" class="form-control timepicker" name="splits_to_'.$x.'" />'.'</td>';
                      echo '<td>';
                      echo "<select name='splits_activity_".$x."' class='js-example-basic-single' style='width:150px'>";
                      for($j = 0; $j < 5; $j++){ //can't do mixed split
                        echo "<option value='$j'>". $lang['ACTIVITY_TOSTRING'][$j] ."</option>";
                      }
                      echo "</select>";
                      echo '</td>';
                      echo '<td><button type="submit" class="btn btn-warning" name="splits_save" value="'.$x.'">'.$lang['MAKE_REQUEST'].'</button></td><td></td>';
                      echo '</tr>';
                    }
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
      </form>
    <?php endif; ?>
  <?php endfor; ?>

  <script>
  $('.datatable').DataTable({
  order: [[ 1, "desc" ]],
  columns: [{orderable: false}, null, {orderable: false}, null, {orderable: false}, {orderable: false}, {orderable: false}, null, null, null, {orderable: false}, {orderable: false}],
  deferRender: true,
  responsive: true,
  autoWidth: false,
  paginate: false,
  language: {
    <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
  }
});
</script>
</div>
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
