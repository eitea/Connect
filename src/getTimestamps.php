<?php include 'header.php'; enableToTime($userID); ?>

<div class="page-header">
  <h3><?php echo $lang['TIMESTAMPS']; ?></h3>
</div>

<?php
require_once 'Calculators/IntervalCalculator.php';

$filterDateFrom = substr(getCurrentTimestamp(),0,8) .'01 12:00:00';
$filterDateTo = date('Y-m-t H:i:s', strtotime($filterDateFrom)); // t returns the number of days in the month
$filterID = 0;
$filterStatus ='';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['filterDateFrom']) && !empty($_POST['filterDateTo']) && strlen($_POST['filterDateFrom']) == 7 && strlen($_POST['filterDateTo']) == 7){
    $filterDateFrom = $_POST['filterDateFrom'] .'-01 12:00:00';
    $filterDateTo = date('Y-m-t H:i:s', strtotime($_POST['filterDateTo']));
  }
  if(!empty($_POST['filteredUserID'])){
    $filterID = $_POST['filteredUserID'];
  }
  if(isset($_POST['filterStatus'])){
    $filterStatus = $_POST['filterStatus'];
  }

  if(!empty($_POST['set_all_filters'])){
    $arr = explode(',', $_POST['set_all_filters']);
    $filterDateFrom = $arr[0];
    $filterDateTo = $arr[1];
    $filterID = $arr[2];
    $filterStatus = $arr[3];
  }
  // echo "<input type='text' name='set_all_filters' style='display:none' value='$filterDateFrom,$filterDateTo,$filterID,$filterStatus' />";
  if(isset($_POST['saveChanges'])){
    $imm = $_POST['saveChanges'];
    $timeStart = str_replace('T', ' ',$_POST['timesFrom']) .':00';
    $timeFin = str_replace('T', ' ',$_POST['timesTo']) .':00';
    $status = intval($_POST['newActivity']);
    $newBreakVal = floatval($_POST['newBreakValues']);
    if($imm == 0){ //create new
      $creatUser = $filterID;
      $timeToUTC = intval($_POST['creatTimeZone']);
      if($_POST['is_open']){
        $timeFin = '0000-00-00 00:00:00';
      } else {
        if($timeFin != '0001-01-01 00:00:00' && $timeFin != ':00'){ $timeFin = carryOverAdder_Hours($timeFin, ($timeToUTC * -1)); } else {$timeFin = '0000-00-00 00:00:00';}
      }
      $timeStart = carryOverAdder_Hours($timeStart, $timeToUTC * -1); //UTC
      $sql = "INSERT INTO $logTable (time, timeEnd, breakCredit, userID, status, timeToUTC) VALUES('$timeStart', '$timeFin', '$newBreakVal', $creatUser, '$status', '$timeToUTC');";
      $conn->query($sql);
      $insertID = mysqli_insert_id($conn);
      //create break for new timestamp
      if($newBreakVal != 0){
        $timeStart = carryOverAdder_Hours($timeStart, 4);
        $timeFin = carryOverAdder_Minutes($timeStart, intval($newBreakVal*60));
        $conn->query("INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$timeStart', '$timeFin', $insertID, 'Newly created Timestamp break', 'break')");
      }
      if(!mysqli_error($conn)){
        echo '<div class="alert alert-success fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>O.K.: </strong>'.$lang['OK_SAVE'];
        echo '</div>';
      }
    } else { //update old
      if($timeFin == '0001-01-01 00:00:00' || $timeFin == ':00' || $_POST['is_open']){
        $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd='0000-00-00 00:00:00', breakCredit = '$newBreakVal', status='$status' WHERE indexIM = $imm";
      } else {
        $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd=DATE_SUB('$timeFin', INTERVAL timeToUTC HOUR), breakCredit = '$newBreakVal', status='$status' WHERE indexIM = $imm";
      }
      if($conn->query($sql)){
        echo '<div class="alert alert-success fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>O.K.: </strong>'.$lang['OK_SAVE'];
        echo '</div>';
      } else {
        echo mysqli_error($conn);
      }
    }
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
      <br>
    </div>
    <div class="col-md-3 text-right"> <!-- Filter User -->
      <select name='filteredUserID' style="width:200px" class="js-example-basic-single">
        <?php
        $query = "SELECT * FROM $userTable WHERE id IN (".implode(', ', $available_users).");";
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
      <select name='filterStatus' style="width:150px" class="js-example-basic-single">
        <option value="" >---</option>
        <?php
        for($i = 0; $i < 6; $i++){
          $selected = ($filterStatus == "$i") ? 'selected' : '';
          echo "<option value='$i' $selected>".$lang['ACTIVITY_TOSTRING'][$i]."</option>";
        }
        ?>
      </select>
    </div>
    <div class="col-sm-1">
      <button id="myFilter" type="submit" class="btn btn-warning" name="filter" value="1">Filter</button>
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

  <?php
  if($filterID):
    $result = $conn->query("SELECT id, firstname FROM $userTable WHERE id = $filterID");
    if($result && ($row = $result->fetch_assoc())){
      $x = $row['id'];
    }
    $bookingResultsResults = array(); //lets do something fun
    ?>
    <br>
    <table class="table table-hover table-condensed">
      <thead>
        <th><?php echo $lang['WEEKLY_DAY']; ?></th>
        <th><?php echo $lang['DATE']; ?></th>
        <th><?php echo $lang['BEGIN']; ?></th>
        <th><?php echo $lang['BREAK']; ?></th>
        <th><?php echo $lang['END']; ?></th>
        <th width="60px"><small><?php $larr = explode(' ',$lang['LAST_BOOKING']); echo $larr[0] .'<br>'.$larr[1]; ?></small></th>
        <th><?php echo $lang['ACTIVITY']; ?></th>
        <th><?php echo $lang['SHOULD_TIME']; ?></th>
        <th><?php echo $lang['IS_TIME']; ?></th>
        <th><?php echo $lang['SALDO_DAY']; ?></th>
        <th><?php echo $lang['SALDO_MONTH']; ?></th>
        <th>Option</th>
      </thead>
      <tbody>
        <?php
        $calculator = new Interval_Calculator($filterDateFrom, $filterDateTo, $x);
        $lunchbreakSUM = $expectedHoursSUM = $absolvedHoursSUM = $accumulatedSaldo = 0;
        for($i = 0; $i < $calculator->days; $i++){
          //filterStatus, let's just skip all those taht dont fit?
          if($filterStatus !== '' && $calculator->activity[$i] != $filterStatus) continue;

          $style = "";
          $tinyEndTime = '-';

          $sql = "SELECT * FROM $roleTable WHERE userID = $x";
          $result = $conn->query($sql);
          $row = $result->fetch_assoc();
          $canBook = $row['canBook'];

          if($calculator->end[$i] != '0000-00-00 00:00:00' && $calculator->activity[$i] == 0 && $canBook == 'TRUE'){
            $sql = "SELECT bookingTimeBuffer FROM $configTable";
            $result = $conn->query($sql);
            $config = $result->fetch_assoc();

            $sql = "SELECT end FROM $projectBookingTable WHERE timestampID = " . $calculator->indecesIM[$i] ." AND bookingType = 'project' ORDER BY end DESC";
            $result = $conn->query($sql);
            if($result && $result->num_rows > 0){
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
              if($calculator->end[$i]){
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

          $accumulatedSaldo += $calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];

          $theSaldo = round($calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i], 2);
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

            $neutralStyle = '';
            if($calculator->shouldTime[$i] == 0 && $calculator->absolvedTime[$i] == 0){
              $neutralStyle = "style=color:#c7c6c6;";
            }

            echo "<tr $neutralStyle>";
            echo "<td>" . $lang['WEEKDAY_TOSTRING'][$calculator->dayOfWeek[$i]] . "</td>";
            echo "<td>" . $calculator->date[$i] . "</td>";
            echo "<td>" . substr($A,11,5) . "</td>";
            echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
            echo "<td>" . substr($B,11,5) . "</td>";
            echo "<td style='$style'><small>" . $tinyEndTime . "</small></td>";
            echo "<td>" . $lang['ACTIVITY_TOSTRING'][$calculator->activity[$i]]. "</td>";
            echo "<td>" . displayAsHoursMins($calculator->shouldTime[$i]) . "</td>";
            echo "<td>" . displayAsHoursMins($calculator->absolvedTime[$i] - $calculator->lunchTime[$i]) . "</td>";
            echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . "</td>";
            echo "<td>" . displayAsHoursMins($accumulatedSaldo) . "</td>";
            echo '<td>';
            if(strtotime($calculator->date[$i]) >= strtotime($calculator->beginDate)){
              echo '<button type="button" class="btn btn-default" title="Edit" data-toggle="modal" data-target=".editingModal-'.$i.'"><i class="fa fa-pencil"></i></button>';
            } else {
              echo '<button type="button" class="btn" style="visibility:hidden">A</button>';
            }
            if($bookingResults && $bookingResults->num_rows > 0){
              echo ' <button type="button" class="btn btn-default" data-toggle="modal" data-target=".bookingModal-'.$calculator->indecesIM[$i].'" ><i class="fa fa-file-text-o"></i></button> ';
              $bookingResultsResults[] = $bookingResults; //so we can create a modal for each of these valid results outside this loop
              $bookingResultsResults['timeToUTC'][] = $calculator->timeToUTC[$i];
            }
            if($calculator->indecesIM[$i] != 0){ echo ' <input type="checkbox" name="index[]" value="'.$calculator->indecesIM[$i].'"/>';}
            echo '</td>';
            echo "</tr>";

            $lunchbreakSUM += $calculator->lunchTime[$i];
            $expectedHoursSUM += $calculator->shouldTime[$i];
            $absolvedHoursSUM += $calculator->absolvedTime[$i] - $calculator->lunchTime[$i];
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
          $current_corrections = array_sum($calculator->monthly_correctionHours);
          $accumulatedSaldo += $current_corrections;
          echo "<tr>";
          echo "<td>".$lang['CORRECTION'].":* </td>";
          echo "<td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
          echo "<td style='color:#9222cc'>" . displayAsHoursMins($current_corrections) . "</td>";
          echo "<td>".displayAsHoursMins($accumulatedSaldo)."</td>";
          echo "<td></td></tr>";

          //get all the previous days
          $calculator_P = new Interval_Calculator($calculator->beginDate, carryOverAdder_Hours($filterDateFrom, -48), $x);
          $saldo_from_zero = $overTimeLump = 0;
          for($i = 0; $i < count($calculator_P->monthly_saldo); $i++){
            $saldo_from_zero += $calculator_P->monthly_saldo[$i] + $calculator_P->monthly_correctionHours[$i];
            if($saldo_from_zero > 0){
              if($saldo_from_zero < $calculator_P->overTimeLump_single[$i]){
                $overTimeLump += $saldo_from_zero;
                $saldo_from_zero = 0;
              } else {
                $overTimeLump += $calculator_P->overTimeLump_single[$i];
                $saldo_from_zero -= $calculator_P->overTimeLump_single[$i];
              }
            }
          }

          $p_lunchTime = array_sum($calculator_P->lunchTime);
          $p_shouldTime = array_sum($calculator_P->shouldTime);
          $p_isTime = array_sum($calculator_P->absolvedTime) - $p_lunchTime;
          $accumulatedSaldo += $saldo_from_zero;

          echo "<tr style='color:#626262;'>";
          echo "<td colspan='2'>Vorheriges Saldo:* </td>";
          echo "<td></td>";
          echo "<td>".displayAsHoursMins($p_lunchTime)."</td>";
          echo "<td></td><td></td><td></td>";
          echo "<td>".displayAsHoursMins($p_shouldTime)."</td>";
          echo "<td>".displayAsHoursMins($p_isTime)."</td>";
          echo "<td>".displayAsHoursMins($saldo_from_zero)."</td>";
          echo "<td>".displayAsHoursMins($accumulatedSaldo)."</td>";
          echo "<td></td></tr>";

          //add values from current interval
          $overTimeLump = 0;
          for($i = 0; $i < count($calculator->monthly_saldo); $i++){
            if($accumulatedSaldo > 0){
              if($accumulatedSaldo < $calculator->overTimeLump_single[$i]){
                $overTimeLump += $accumulatedSaldo;
                $accumulatedSaldo = 0;
              } else {
                $overTimeLump += $calculator->overTimeLump_single[$i];
                $accumulatedSaldo -= $calculator->overTimeLump_single[$i];
              }
            }
          }

          //overTimeLump
          echo "<tr>";
          echo "<td colspan='2'>".$lang['OVERTIME_ALLOWANCE'].":* </td>";
          echo "<td></td><td></td><td></td><td></td><td></td><td></td><td></td>";
          echo "<td style='color:#fc8542;'>-" . displayAsHoursMins($overTimeLump) . "</td>"; //its always negative. always.
          echo "<td>".displayAsHoursMins($accumulatedSaldo)."</td>";
          echo "<td></td></tr>";

          $lunchbreakSUM += $p_lunchTime;
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
      <br><br>

      <div class="text-right">
        <button type="submit" class="btn btn-warning" name="delete" value="<?php echo $x; ?>"><?php echo $lang['DELETE']; ?></button>
      </div>
    <?php else: echo '<br><div class="alert alert-info">'.$lang['INFO_REQUIRE_USER'].'</div>'; endif; ?>
  </form>

  <!-- Editing Modall -->
  <?php if($filterID) for($i = 0; $i < $calculator->days; $i++): ?>
    <form method="POST">
      <div class="modal fade editingModal-<?php echo $i; ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-md">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><?php echo substr($calculator->date[$i], 0, 10); ?></h4>
            </div>
            <div class="modal-body">
              <div class="row">
                <div class="col-md-6">
                  <label><?php echo $lang['LUNCHBREAK']; ?></label>
                  <input type="number" step="any" class="form-control input-sm" name="newBreakValues" value="<?php echo sprintf('%.2f', $calculator->lunchTime[$i]); ?>" style='width:70px' />
                </div>
                <div class="col-md-6">
                  <br>
                  <?php
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
                  echo "<select name='newActivity' class='js-example-basic-single' style='width:150px'>";
                  for($j = 0; $j < 5; $j++){ //can't do mixed
                    if($calculator->activity[$i] == $j){
                      echo "<option value='$j' selected>". $lang['ACTIVITY_TOSTRING'][$j] ."</option>";
                    } else {
                      echo "<option value='$j'>". $lang['ACTIVITY_TOSTRING'][$j] ."</option>";
                    }
                  }
                  echo "</select> ";
                  if(!$calculator->indecesIM[$i]){ //timestamp doesnt exist
                    $A = $B = $calculator->date[$i].' 00:00:00';
                    //existing timestamps cant have timeToUTC edited
                    echo ' <select name="creatTimeZone" class="js-example-basic-single" style="width:90px">';
                    for($i_utc = -12; $i_utc <= 12; $i_utc++){
                      if($i_utc == $timeToUTC){
                        echo "<option name='ttz' value='$i_utc' selected>UTC " . sprintf("%+03d", $i_utc) . "</option>";
                      } else {
                        echo "<option name='ttz' value='$i_utc'>UTC " . sprintf("%+03d", $i_utc) . "</option>";
                      }
                    }
                    echo "</select>";
                  }
                  echo "<input type='text' name='set_all_filters' style='display:none' value='$filterDateFrom,$filterDateTo,$filterID,$filterStatus' />";
                  ?>
                </div>
              </div>
              <br><br>
              <div class="row">
                <div class="col-md-6">
                  <label><?php echo $lang['BEGIN']; ?></label>
                  <input id="calendar" type="datetime-local" class='form-control input-sm' onkeydown="return event.keyCode != 13;" name="timesFrom" value="<?php echo substr($A,0,10).'T'. substr($A,11,5) ?>"/>
                </div>
                <div class="col-md-6">
                  <label><?php echo $lang['END']; ?></label>
                  <div class="input-group">
                    <span class="input-group-addon active"><input type="radio" name="is_open" value="0" checked="checked" /></span>
                    <input id="calendar2" type="datetime-local" class='form-control input-sm' onkeydown="return event.keyCode != 13;" name="timesTo" value="<?php echo substr($B,0,10).'T'. substr($B,11,5) ?>"/>
                  </div>
                  <div style="margin-top:5px;"><input type="radio" name="is_open" value="1" style="margin-left:13px;" /><span style="margin-left:20px;"><?php echo $lang['OPEN']; ?></span></div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
              <button type="submit" name="saveChanges" class="btn btn-warning" title="Save" value="<?php echo $calculator->indecesIM[$i]; ?>"><?php echo $lang['SAVE']; ?></button>
            </div>
          </div>
        </div>
      </div>
    </form>
  <?php endfor; ?>

  <!-- Projectbooking Modall -->
  <?php
  if($filterID)
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
                    $A = $B = '';
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

  <!-- /BODY -->
  <?php include 'footer.php'; ?>
