<?php include 'header.php'; enableToTime($userID); ?>

<div class="page-header">
  <h3><?php echo $lang['TIMESTAMPS']; ?></h3>
</div>

<?php
require_once 'Calculators/IntervalCalculator.php';
$scrollPos = 0;

$filterDateFrom = substr(getCurrentTimestamp(),0,8) .'01 12:00:00';
$filterDateTo = date('Y-m-t H:i:s', strtotime($filterDateFrom)); // t returns the number of days in the month
$filterID = 0;
$filterStatus ='';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['filterDateFrom']) && !empty($_POST['filterDateTo']) && strlen($_POST['filterDateFrom']) == 7 && strlen($_POST['filterDateTo']) == 7){
    $filterDateFrom = $_POST['filterDateFrom'] .'-01 12:00:00';
    $filterDateTo = date('Y-m-t H:i:s', strtotime($_POST['filterDateTo']));
  }
  if (!empty($_POST['filteredUserID'])) {
    $filterID = $_POST['filteredUserID'];
  }
  if (isset($_POST['filterStatus'])) {
    $filterStatus = $_POST['filterStatus'];
  }

  if(isset($_POST['modifyDate']) || isset($_POST['saveChanges'])){
    $scrollPos = intval($_POST['scrollPos']);
    $imm = isset($_POST['modifyDate'])? $_POST['modifyDate'] : $_POST['saveChanges'];
    $result = $conn->query("SELECT userID FROM $logTable WHERE indexIM = '$imm'");
  }
  if(isset($_POST['saveChanges'])) {
    $scrollPos = intval($_POST['scrollPos']);
    $imm = $_POST['saveChanges'];
    $timeStart = $_POST['timesFrom'] .':00';
    $timeFin = $_POST['timesTo'] .':00';
    $status = intval($_POST['newActivity']);
    $newBreakVal = floatval($_POST['newBreakValues']);
    if(isset($_POST['creatTimeZone']) && ($arr = explode(', ', $imm))){ //create new
      $creatUser = $arr[0];
      $timeToUTC = intval($_POST['creatTimeZone']);
      $timeStart = carryOverAdder_Hours($timeStart, $timeToUTC * -1); //UTC
      if($timeFin != '0001-01-01T00:00:00' && $timeFin != ':00'){ $timeFin = carryOverAdder_Hours($timeFin, ($timeToUTC * -1)); } else {$timeFin = '0000-00-00 00:00:00';}
      $sql = "INSERT INTO $logTable (time, timeEnd, breakCredit, userID, status, timeToUTC) VALUES('$timeStart', '$timeFin', '$newBreakVal', $creatUser, '$status', '$timeToUTC');";
      $conn->query($sql);
      $insertID = mysqli_insert_id($conn);
      echo mysqli_error($conn);
      //create break
      if($newBreakVal != 0){
        $timeStart = carryOverAdder_Hours($timeStart, 4);
        $timeFin = carryOverAdder_Minutes($timeStart, intval($newBreakVal*60));
        $conn->query("INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$timeStart', '$timeFin', $insertID, 'Newly created Timestamp break', 'break')");
        echo mysqli_error($conn);
      }
    } else { //update old
      if($timeFin == '0001-01-01T00:00:00' || $timeFin == ':00'){
        $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd='0000-00-00 00:00:00', breakCredit = '$newBreakVal', status='$status' WHERE indexIM = $imm";
      } else {
        $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd=DATE_SUB('$timeFin', INTERVAL timeToUTC HOUR), breakCredit = '$newBreakVal', status='$status' WHERE indexIM = $imm";
      }
      $conn->query($sql);
    }
    echo mysqli_error($conn);
  } elseif (isset($_POST['delete'])) {
    $activeTab = $_POST['delete'];
    if(isset($_POST['index'])){
      $index = $_POST["index"];
      foreach ($index as $x) {
        $sql = "DELETE FROM $logTable WHERE indexIM=$x;";
        $conn->query($sql);
      }
    }
  }

} //endif post
?>

<!-- ############################### FILTER ################################### -->

<form method="post">
  <div class="row">
    <div class="col-md-4"> <!-- Date Interval-->
      <div class="input-group">
        <input id="calendar" type="text" maxlength="7" class="form-control from" name="filterDateFrom" value=<?php echo substr($filterDateFrom,0,7); ?> >
        <span class="input-group-addon"> - </span>
        <input id="calendar2" type="text" maxlength="7" class="form-control"  name="filterDateTo" value="<?php echo substr($filterDateTo,0,7); ?>">
      </div>
    </div>
    <div class="col-md-3 text-right"> <!-- Filter User -->
      <select name='filteredUserID' style="width:200px" class="js-example-basic-single">
        <?php
        $query = "SELECT * FROM $userTable;";
        $result = mysqli_query($conn, $query);
        echo "<option name='filterUserID' value='0'>Benutzer ... </option>";
        while($row = $result->fetch_assoc()){
          $i = $row['id'];
          if ($filterID == $i) {
            echo "<option name='filterUserID' value='$i' selected>".$row['firstname'] . " " . $row['lastname']."</option>";
          } else {
            echo "<option name='filterUserID' value='$i' >".$row['firstname'] . " " . $row['lastname']."</option>";
          }
        }
        ?>
      </select>
      <br><br><!-- Filter Status -->
      <select name='filterStatus' style="width:100px" class="js-example-basic-single">
        <option value="" >---</option>
        <option value="0" <?php if($filterStatus == '0'){echo 'selected';} ?>><?php echo $lang_activityToString[0]; ?></option>
        <option value="1" <?php if($filterStatus == '1'){echo 'selected';} ?>><?php echo $lang_activityToString[1]; ?></option>
        <option value="2" <?php if($filterStatus == '2'){echo 'selected';} ?>><?php echo $lang_activityToString[2]; ?></option>
        <option value="3" <?php if($filterStatus == '3'){echo 'selected';} ?>><?php echo $lang_activityToString[3]; ?></option>
      </select>
    </div>
    <div class="col-sm-1">
      <button id="myFilter" type="submit" class="btn btn-sm btn-warning" name="filter" value="1">Filter</button>
    </div>
  </div>
  <script>
  $("#calendar").datepicker({
    format: "yyyy-mm",
    viewMode: "months",
    minViewMode: "months"
  });
  $("#calendar2").datepicker({
    format: "yyyy-mm",
    viewMode: "months",
    minViewMode: "months"
  });
  </script>
  <!-- ############################### TABLE ################################### -->

  <ul class="nav nav-tabs">
    <?php
    if($filterID):
      $result = $conn->query("SELECT id, firstname FROM $userTable WHERE id = $filterID");
      if($result && ($row = $result->fetch_assoc())){
        $x = $row['id'];
        //onclick sets value of filter button to keep tab selected when filtering again
        echo "<li class='active'><a data-toggle='tab' href='#userTab' >".$row['firstname']."</a></li>";
        echo '<li><a data-toggle="tab" href="#menu_summary" >'.$lang['OVERVIEW'] .'</a></li>';
      }
      $bookingResultsResults = array(); //lets do something fun
      ?>
    </ul>

    <div class="tab-content">
      <div id='userTab' class='tab-pane fade in active'><br>
        <section>
          <div class="table-scrollable-container" >
            <table class="table table-hover table-condensed table-scrollable">
              <thead>
                <th><div><?php echo $lang['WEEKLY_DAY']; ?></div></th>
                <th><div><?php echo $lang['DATE']; ?></div></th>
                <th><?php echo $lang['BEGIN']; ?><div><?php echo $lang['BEGIN']; ?></div></th>
                <th><div><?php echo $lang['BREAK']; ?></div></th>
                <th><?php echo $lang['END']; ?><div><?php echo $lang['END']; ?></div></th>
                <th width="60px"><div style="top:-5px;font-size:10px;"><?php $larr = explode(' ',$lang['LAST_BOOKING']); echo $larr[0] .'<br>'.$larr[1]; ?></div></th>
                <th><div><?php echo $lang['ACTIVITY']; ?></div></th>
                <th><div><?php echo $lang['SHOULD_TIME']; ?></div></th>
                <th><div><?php echo $lang['IS_TIME']; ?></div></th>
                <th><div><?php echo $lang['DIFFERENCE']; ?></div></th>
                <th><div>Saldo</div></th>
                <th style="text-align:right;width:160px;"><div>Option</div></th>
              </thead>
              <tbody>
                <?php
                $calculator = new Interval_Calculator($filterDateFrom, $filterDateTo, $x);
                $lunchbreakSUM = $expectedHoursSUM = $absolvedHoursSUM = $accumulatedSaldo = 0;
                for($i = 0; $i < $calculator->days; $i++){
                  //filterStatus, let's just skip all those taht dont fit?
                  if($filterStatus !== '' && $calculator->activity[$i] != $filterStatus) continue;

                  if($calculator->end[$i] == '0000-00-00 00:00:00'){
                    $endTime = getCurrentTimestamp();
                  } else {
                    $endTime = $calculator->end[$i];
                  }
                  $difference = timeDiff_Hours($calculator->start[$i], $endTime );

                  $style = "";
                  $tinyEndTime = '-';

                  $sql = "SELECT * FROM $roleTable WHERE userID = $x";
                  $result = $conn->query($sql);
                  $row = $result->fetch_assoc();
                  $canBook = $row['canBook'];

                  if($calculator->end[$i] != '-' && $calculator->end[$i] != '0000-00-00 00:00:00' && $calculator->activity[$i] == 0 && $canBook == 'TRUE'){
                    $sql = "SELECT bookingTimeBuffer FROM $configTable";
                    $result = $conn->query($sql);
                    $config = $result->fetch_assoc();

                    $sql = "SELECT end FROM $projectBookingTable WHERE timestampID = " . $calculator->indecesIM[$i] ." AND bookingType = 'project' ORDER BY end DESC";
                    $result = $conn->query($sql);
                    if($result && $result->num_rows > 0) {
                      $config2 = $result->fetch_assoc();

                      $bookingTimeDifference = timeDiff_Hours($config2['end'], $calculator->end[$i]) * 60;

                      if($bookingTimeDifference <= $config['bookingTimeBuffer']){
                        $style = "color:#6fcf2c"; //green
                      }
                      if($bookingTimeDifference > $config['bookingTimeBuffer']){
                        $style = "color:#facf1e"; //yellow
                      }
                      if($bookingTimeDifference > $config['bookingTimeBuffer'] * 2 || $bookingTimeDifference < 0){
                        $style = "color:#fc8542"; //red
                      }
                      if($bookingTimeDifference < 0){
                        $style = "color:#f0621c;font-weight:bold"; //monsterred
                      }
                      if($calculator->end[$i] != '-'){
                        $tinyEndTime = substr(carryOverAdder_Hours($config2['end'], $calculator->timeToUTC[$i]),11,5);
                      }
                    }
                  }

                  if($calculator->start[$i]){
                    $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
                  } else {
                    $A = $calculator->start[$i];
                  }
                  if($calculator->end[$i] && $calculator->end[$i] != '0000-00-00 00:00:00'){
                    $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);
                  } else {
                    $B = $calculator->end[$i];
                  }

                  $accumulatedSaldo += $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];

                  $theSaldo = round($difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i], 2);
                  $saldoStyle = '';
                  if($theSaldo < 0){
                    $saldoStyle = 'style=color:#fc8542;'; //red
                  } elseif($theSaldo > 0) {
                    $saldoStyle = 'style=color:#6fcf2c;'; //green
                  }

                  $bookingResults = $conn->query("SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
                    LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
                    LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)
                    WHERE timestampID = '".$calculator->indecesIM[$i]."' ORDER BY end ASC");
                    echo mysqli_error($conn);


                  $neutralStyle = '';
                  if($calculator->shouldTime[$i] == 0 && $difference == 0){
                    $neutralStyle = "style=color:#c7c6c6;";
                  }
                  //pressing edit on a button makes row editable, (scrollheight preserved via js at bottom of page)
                  if(isset($_POST['modifyDate']) && $_POST['modifyDate'] === $calculator->indecesIM[$i]){
                    echo "<tr>";
                    echo "<td>" . $lang_weeklyDayToString[$calculator->dayOfWeek[$i]] . "</td>";
                    if(($arr = explode(', ', $_POST['modifyDate'])) && count($arr) > 1){ //for non existing timestamps, indexIM consists of (userID, date)
                      $A = $B = $arr[1];
                      echo '<td><select name="creatTimeZone" class="js-example-basic-single" style=width:90px>';
                      for($i_utc = -12; $i_utc <= 12; $i_utc++){
                        if($i_utc == $timeToUTC){
                          echo "<option name='ttz' value='$i_utc' selected>UTC " . sprintf("%+03d", $i_utc) . "</option>";
                        } else {
                          echo "<option name='ttz' value='$i_utc'>UTC " . sprintf("%+03d", $i_utc) . "</option>";
                        }
                      }
                      echo "</select></td>";
                    } else { //existing timestamps cant have timeToUTC edited
                      echo '<td></td>';
                    }

                    echo "<td><input id='calendar' type='datetime-local' class='form-control input-sm' onkeydown='if (event.keyCode == 13) return false;' name='timesFrom' value='" . substr($A,0,10).'T'. substr($A,11,5) . "' /></td>";
                    echo "<td><div style='display:inline-block;text-align:center'><input type='number' step='any' class='form-control input-sm' name='newBreakValues' value='" . sprintf('%.2f', $calculator->lunchTime[$i]). "' style='width:70px' /></div></td>";
                    echo "<td><input id='calendar2' type='datetime-local' class='form-control input-sm' onkeydown='if (event.keyCode == 13) return false;' name='timesTo' value='" . substr($B,0,10).'T'. substr($B,11,5) . "' /></td>";
                    echo "<td style='$style'><small>" . $tinyEndTime . "</small></td>";
                    echo "<td><select name='newActivity' class='js-example-basic-single'>";
                    for($j = 0; $j < 4; $j++){
                      if($calculator->activity[$i] == $j){
                        echo "<option value='$j' selected>". $lang_activityToString[$j] ."</option>";
                      } else {
                        echo "<option value='$j'>". $lang_activityToString[$j] ."</option>";
                      }
                    }
                    echo "</select></td>";
                    echo "<td>" . $calculator->shouldTime[$i] . "</td>";
                    echo "<td>" . sprintf('%.2f', $difference - $calculator->lunchTime[$i]) . "</td>";
                    echo "<td $saldoStyle>" . sprintf('%+.2f', $theSaldo) . "</td>";
                    echo "<td>" . sprintf('%+.2f', $accumulatedSaldo) . "</td>";
                    echo '<td><button type="submit" name="saveChanges" class="btn btn-warning" title="Edit" value="'.$calculator->indecesIM[$i].'"><i class="fa fa-floppy-o"></i></button></td>';
                    echo "</tr>";
                  } else {
                    echo "<tr $neutralStyle>";
                    echo "<td>" . $lang_weeklyDayToString[$calculator->dayOfWeek[$i]] . "</td>";
                    echo "<td>" . $calculator->date[$i] . "</td>";
                    echo "<td>" . substr($A,11,5) . "</td>";
                    echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
                    echo "<td>" . substr($B,11,5) . "</td>";
                    echo "<td style='$style'><small>" . $tinyEndTime . "</small></td>";
                    echo "<td>" . $lang_activityToString[$calculator->activity[$i]]. "</td>";
                    echo "<td>" . displayAsHoursMins($calculator->shouldTime[$i]) . "</td>";
                    echo "<td>" . displayAsHoursMins($difference - $calculator->lunchTime[$i]) . "</td>";
                    echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . "</td>";
                    echo "<td><small>" . displayAsHoursMins($accumulatedSaldo) . "</small></td>";
                    echo '<td><button type="submit" name="modifyDate" class="btn btn-default" title="Edit" value="'.$calculator->indecesIM[$i].'"><i class="fa fa-pencil"></i></button>';
                    if($bookingResults && $bookingResults->num_rows > 0){
                      echo ' <button type="button" class="btn btn-default" data-toggle="modal" data-target=".bookingModal-'.$calculator->indecesIM[$i].'" ><i class="fa fa-file-text-o"></i></button> ';
                      $bookingResultsResults[] = $bookingResults; //so we can create a modal for each of these valid results outside this loop
                      $bookingResultsResults['timeToUTC'][] = $calculator->timeToUTC[$i];
                    }
                    if(!preg_match('/,\s/', $calculator->indecesIM[$i])){ echo ' <input type="checkbox" name="index[]" value="'.$calculator->indecesIM[$i].'"/></td>';}
                    echo "</tr>";
                  }

                  $lunchbreakSUM += $calculator->lunchTime[$i];
                  $expectedHoursSUM += $calculator->shouldTime[$i];
                  $absolvedHoursSUM += $difference - $calculator->lunchTime[$i];
                } //endfor

                //partial sum
                echo '<tr class="blank_row"><td colspan="12"></td><tr>';
                echo "<tr style='font-weight:bold;'>";
                echo "<td colspan='2'>Zwischensumme:* </td>";
                echo "<td></td>";
                echo "<td>".displayAsHoursMins($lunchbreakSUM)."</td>";
                echo "<td></td><td></td><td></td>";
                echo "<td>".displayAsHoursMins($expectedHoursSUM)."</td>";
                echo "<td>".displayAsHoursMins($absolvedHoursSUM)."</td>";
                echo "<td> = </td>";
                echo "<td>".displayAsHoursMins($accumulatedSaldo)."</td>";
                echo "<td></td></tr>";

                //correctionHours
                $accumulatedSaldo += $calculator->correctionHours;
                echo "<tr>";
                echo "<td>".$lang['CORRECTION'].":* </td>";
                echo "<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
                echo "<td style='color:#9222cc'>" . displayAsHoursMins($calculator->correctionHours) . "</td>";
                echo "<td>".displayAsHoursMins($accumulatedSaldo)."</td>";
                echo "<td></td></tr>";

                //overTimeLump
                $accumulatedSaldo -= $calculator->overTimeLump;
                echo "<tr>";
                echo "<td colspan='2'>".$lang['OVERTIME_ALLOWANCE'].":* </td>";
                echo "<td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
                echo "<td style='color:#fc8542;'>-" . displayAsHoursMins($calculator->overTimeLump) . "</td>"; //its always negative. always.
                echo "<td>".displayAsHoursMins($accumulatedSaldo)."</td>";
                echo "<td></td></tr>";

                //get saldo from aall the previous days
                $calculator_P = new Interval_Calculator($calculator->beginDate, carryOverAdder_Hours($filterDateFrom, -48), $x);

                $p_shouldTime = array_sum($calculator_P->shouldTime) + $calculator_P->overTimeLump;
                $p_isTime = array_sum($calculator_P->absolvedTime) - array_sum($calculator_P->lunchTime) + $calculator_P->correctionHours;
                $accumulatedSaldo += $p_isTime - $p_shouldTime;

                echo "<tr style='color:#626262;'>";
                echo "<td colspan='2'>Vorheriges Saldo:* </td>";
                echo "<td></td>";
                echo "<td>".displayAsHoursMins(array_sum($calculator_P->lunchTime))."</td>";
                echo "<td></td><td></td><td></td>";
                echo "<td>".displayAsHoursMins($p_shouldTime)."</td>";
                echo "<td>".displayAsHoursMins($p_isTime)."</td>";
                echo "<td>".displayAsHoursMins($p_isTime - $p_shouldTime)."</td>";
                echo "<td>".displayAsHoursMins($accumulatedSaldo)."</td>";
                echo "<td></td></tr>";

                $lunchbreakSUM += array_sum($calculator_P->lunchTime);
                $expectedHoursSUM += $p_shouldTime;
                $absolvedHoursSUM += $p_isTime;

                echo '<tr class="blank_row"><td colspan="12"></td><tr>';
                echo "<tr style='font-weight:bold;'>";
                echo "<td colspan='2'>Summe:* </td>";
                echo "<td></td>";
                echo "<td>".displayAsHoursMins($lunchbreakSUM)."</td>";
                echo "<td></td><td></td><td></td>";
                echo "<td>".displayAsHoursMins($expectedHoursSUM)."</td>";
                echo "<td>".displayAsHoursMins($absolvedHoursSUM)."</td>";
                echo "<td> = </td>";
                echo "<td>".displayAsHoursMins($accumulatedSaldo)."</td>";
                echo "<td></td></tr>";
                ?>
                <tr><td colspan="12"><small>*Angaben in Stunden</small></td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Projectbooking Modall -->
        <?php
        for($i = 0; $i < count($bookingResultsResults)-1; $i++): //timeToUTC takes up 1 count too much
          $bookingResult = $bookingResultsResults[$i];
          $timeToUTC = $bookingResultsResults['timeToUTC'][$i];
          $row = $bookingResult->fetch_assoc(); //we need this in here for the timestampID in modal class.
          ?>
          <div class="modal fade bookingModal-<?php echo $row['timestampID']; ?>" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h4 class="modal-title"><?php echo substr($row['start'], 0, 10); ?></h4>
                </div>
                <div class="modal-body" style="max-height: 80vh;  overflow-y: auto;">
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
                      do {
                        $icon = "fa fa-bookmark";
                        if($row['bookingType'] == 'break'){
                          $icon = "fa fa-cutlery";
                        } elseif($row['bookingType'] == 'drive'){
                          $icon = "fa fa-car";
                        }
                        echo '<tr>';
                        echo "<td><i class='$icon'></i></td>";
                        echo "<td>". $row['name'] ."</td>";
                        echo "<td>". $row['projectName'] ."</td>";
                        echo "<td>". substr($row['start'], 0, 10) ."</td>";
                        echo "<td>". substr(carryOverAdder_Hours($row['start'], $timeToUTC), 11, 5) ."</td>";
                        echo "<td>". substr(carryOverAdder_Hours($row['end'], $timeToUTC), 11, 5) ."</td>";
                        echo "<td style='text-align:left'>". $row['infoText'] ."</td>";
                        echo '</tr>';
                      } while($row = $bookingResult->fetch_assoc());
                      ?>
                    </tbody>
                  </table>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
                </div>
              </div>
            </div>
          </div>
        <?php endfor; ?>
        <br><br>
        <div class="text-right">
          <br>
          <input id="scrollPos" type="text" name="scrollPos" value="<?php echo $scrollPos; ?>" style="display:none;" />
          <button type="submit" class="btn btn-warning" name="delete" value="<?php echo $x; ?>">Delete</button>
          <br><br>
        </div>
      </div>

      <div id="menu_summary" class="tab-pane fade"><br>
        <?php
        $curID = $filterID;
        include 'tableSummary.php';
        ?>
      </div>

    <?php
    else:
      echo '<br><div class="alert alert-info">Select a User to Continue </div>';
    endif;  //end if filterUserID
    ?>
  </div>
</form>

<script>
window.onload = function () {
  if ($("#scrollPos").val() != 0 ) {
    setTimeout(function(){
      $(".table-scrollable-container").scrollTop($("#scrollPos").val());
    }, 200);
  }
}
$(document).ready(function() {
  // When scrolling happens....
  $(".table-scrollable-container").on("scroll", function() {
    $("#scrollPos").val($(".table-scrollable-container").scrollTop());
  });
});
</script>

<!-- /BODY -->
<?php include 'footer.php'; ?>
