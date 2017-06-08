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
  if(!empty($_POST['filterDateFrom']) && strlen($_POST['filterDateFrom']) == 7){
    $filterDateFrom = $_POST['filterDateFrom'] .'-01 12:00:00';
    $filterDateTo = date('Y-m-t H:i:s', strtotime($filterDateFrom));
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
    $filterDateTo = date('Y-m-t H:i:s', strtotime($filterDateFrom));
    $filterID = $arr[1];
    $filterStatus = $arr[2];
  }
  // echo "<input type='text' name='set_all_filters' style='display:none' value='$filterDateFrom,$filterID,$filterStatus' />";
  if(isset($_POST['saveChanges'])){
    $imm = $_POST['saveChanges'];
    $timeStart = str_replace('T', ' ',$_POST['timesFrom']) .':00';
    $timeFin = str_replace('T', ' ',$_POST['timesTo']) .':00';
    $status = intval($_POST['newActivity']);
    if($imm == 0){ //create new
      $creatUser = $filterID;
      $timeToUTC = intval($_POST['creatTimeZone']);
      $newBreakVal = floatval($_POST['newBreakValues']);
      if($_POST['is_open']){
        $timeFin = '0000-00-00 00:00:00';
      } else {
        if($timeFin != '0001-01-01 00:00:00' && $timeFin != ':00'){ $timeFin = carryOverAdder_Hours($timeFin, ($timeToUTC * -1)); } else {$timeFin = '0000-00-00 00:00:00';}
      }
      $timeStart = carryOverAdder_Hours($timeStart, $timeToUTC * -1); //UTC
      $sql = "INSERT INTO $logTable (time, timeEnd, userID, status, timeToUTC) VALUES('$timeStart', '$timeFin', $creatUser, '$status', '$timeToUTC');";
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
      $addBreakVal = floatval($_POST['addBreakValues']);
      if($addBreakVal){//add a break
        $breakEnd = carryOverAdder_Minutes($timeStart, intval($addBreakVal*60));
        $conn->query("INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType) VALUES('$timeStart', '$breakEnd', $imm, 'Administrative Break', 'break')");
      }
      if($timeFin == '0001-01-01 00:00:00' || $timeFin == ':00' || $_POST['is_open']){
        $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd='0000-00-00 00:00:00', status='$status' WHERE indexIM = $imm";
      } else {
        $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd=DATE_SUB('$timeFin', INTERVAL timeToUTC HOUR), status='$status' WHERE indexIM = $imm";
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
  } elseif (isset($_POST['delete'])){
    $activeTab = $_POST['delete'];
    if(isset($_POST['index'])){
      $index = $_POST["index"];
      foreach ($index as $x) {
        $sql = "DELETE FROM $logTable WHERE indexIM=$x;";
        $conn->query($sql);
      }
    }
  } elseif(isset($_POST['splits_save'])){
    $bookingID = intval($_POST['splits_save']);
    if(!empty($_POST['splits_from_'.$bookingID]) && !empty($_POST['splits_to_'.$bookingID])){
      $splitting_activity = intval($_POST['splits_activity_'.$bookingID]);
      $result = $conn->query("SELECT id, timestampID, start, end, timeToUTC FROM $projectBookingTable INNER JOIN $logTable ON $logTable.indexIM = $projectBookingTable.timestampID WHERE id = $bookingID AND bookingType = 'break'");
      if($result && ($row = $result->fetch_assoc())){
        $row['start'] = substr($row['start'],0, 16).':00'; //UTC
        $row['end'] = substr($row['end'],0, 16).':00';
        $split_A = carryOverAdder_Hours(substr_replace($row['start'], $_POST['splits_from_'.$bookingID], 11), $row['timeToUTC'] *-1);
        $split_B = carryOverAdder_Hours(substr_replace($row['end'], $_POST['splits_to_'.$bookingID], 11), $row['timeToUTC'] *-1);
        //valid times
        if(timeDiff_Hours($split_A, $row['start']) <= 0 && timeDiff_Hours($row['end'], $split_B) <= 0 && timeDiff_Hours($split_A, $split_B) > 0){
          if($splitting_activity){
            //create mixed booking
            $conn->query("INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType, mixedStatus ) VALUES('$split_A', '$split_B', ".$row['timestampID'].", 'Split - $splitting_activity', 'mixed', '$splitting_activity') ");
            //update mixed log
            $conn->query("UPDATE $logTable SET status = '5' WHERE indexIM = ".$row['timestampID']);
          }
          //update break start time
          if(timeDiff_Hours($split_B, $row['end']) > 0){
            $conn->query("UPDATE $projectBookingTable SET start = '$split_B' WHERE id = ".$row['id']);
            echo mysqli_error($conn);
            //create second break
            if(timeDiff_Hours($row['start'], $split_A) > 0){ //break in the middle
              $conn->query("INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('".$row['start']."', '$split_A', ".$row['timestampID'].", 'Split Start', 'break') ");
            }
          } elseif(timeDiff_Hours($row['start'], $split_A) > 0) { //change start time
            $conn->query("UPDATE $projectBookingTable SET end = '$split_A' WHERE id = ".$row['id']);
          } else { //delete break
            $conn->query("DELETE FROM $projectBookingTable WHERE id = ".$row['id']);
          }
          echo mysqli_error($conn);
        } else {
          echo '<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">&times;</a>'.$lang['ERROR_TIMES_INVALID'].'</div>';
        }
      } else {
        die("Please do not try this again. It will not work."); //damn bastards trying to script like whaddap
      }
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Error: </strong>'.$lang['ERROR_MISSING_FIELDS'];
      echo '</div>';
    }
  } elseif(!empty($_POST['editing_save'])){ //comes from the modal
    $x = $_POST['editing_save'];
    $result = $conn->query("SELECT $logTable.timeToUTC FROM $logTable, $projectBookingTable WHERE $projectBookingTable.id = $x AND $projectBookingTable.timestampID = $logTable.indexIM");
    $row = $result->fetch_assoc();
    $toUtc = $row['timeToUTC'] * -1;
    if(test_Date($_POST["editing_time_from_".$x].':00') && test_Date($_POST["editing_time_to_".$x].':00')){
      if(!empty($_POST["editing_projectID_".$x])){
        $new_projectID = $_POST["editing_projectID_".$x];
      } else { //break
        $new_projectID = 'NULL';
      }
      $new_A = carryOverAdder_Hours($_POST["editing_time_from_".$x].':00', $toUtc);
      $new_B = carryOverAdder_Hours($_POST["editing_time_to_".$x].':00', $toUtc);

      $chargedTimeStart= '0000-00-00 00:00:00';
      $chargedTimeFin = '0000-00-00 00:00:00';
      if($_POST['editing_chargedtime_from_'.$x] != '0000-00-00 00:00'){
        $chargedTimeStart = carryOverAdder_Hours($_POST['editing_chargedtime_from_'.$x].':00', $toUtc);
      }
      if($_POST['editing_chargedtime_to_'.$x] != '0000-00-00 00:00'){
        $chargedTimeFin = carryOverAdder_Hours($_POST['editing_chargedtime_to_'.$x].':00', $toUtc);
      }
      $new_text = test_input($_POST['editing_infoText_'.$x]);

      $new_charged = 'FALSE';
      if(isset($_POST['editing_charge']) || isset($_POST['editing_nocharge'])){
        $new_charged = 'TRUE';
      }
      $conn->query("UPDATE $projectBookingTable SET start='$new_A', end='$new_B', projectID=$new_projectID, infoText='$new_text', booked='$new_charged', chargedTimeStart='$chargedTimeStart', chargedTimeEnd='$chargedTimeFin' WHERE id = $x");
      //update charged
      if(isset($_POST['editing_charge'])){
        if($chargedTimeStart != '0000-00-00 00:00:00'){
          $new_A = $chargedTimeStart;
        }
        if($chargedTimeFin != '0000-00-00 00:00:00'){
          $new_B = $chargedTimeFin;
        }
        $hours = timeDiff_Hours($new_A, $new_B);
        $sql = "UPDATE $projectTable SET hours = hours - $hours WHERE id = $x";
        $conn->query($sql);
      }
      if(!mysqli_error($conn)){
        echo '<div class="alert alert-success fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo $lang['OK_SAVE'];
        echo '</div>';
      }
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Error: </strong>'.$lang['ERROR_TIMES_INVALID'];
      echo '</div>';
    }
    echo mysqli_error($conn);
  } elseif(isset($_POST['delete_bookings']) && !empty($_POST['delete_bookings_ids'])){
    foreach ($_POST['delete_bookings_ids'] as $x) {
      $conn->query("DELETE FROM projectBookingData WHERE id = $x;");
    }
    if(mysqli_error($conn)){
      echo $conn->error;
    } else {
      echo '<div class="alert alert-success"><a href="#" class="close" data-dismiss="alert">&times;</a>O.K.</div>';
    }
  } elseif(isset($_POST['add_multiple'])){
    if(test_Date($_POST['add_multiple_start'].' 08:00:00') && test_Date($_POST['add_multiple_end'].' 08:00:00')){
      $status = intval($_POST['add_multiple_status']);
      $i = $_POST['add_multiple_start'].' 08:00:00';
      $days = (timeDiff_Hours($i, $_POST['add_multiple_end'].' 08:00:00')/24) + 1; //days
      for($j = 0; $j < $days; $j++){
        //get the expected Hours for currenct day (read the latest interval which matches criteria)
        $result = $conn->query("SELECT * FROM $intervalTable WHERE userID = $filterID AND DATE(startDate) < DATE('$i') ORDER BY startDate DESC");
        if ($result && ($row = $result->fetch_assoc())) {
          $expected = isHoliday($i) ? 0 : $row[strtolower(date('D', strtotime($i)))];
          if($expected != 0){
            $i2 = carryOverAdder_Minutes($i, intval($expected * 60));
            $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status) VALUES('$i', '$i2', $filterID, '0', '$status')";
            $conn->query($sql);
          }
          $i = carryOverAdder_Hours($i, 24);
        } else {
          echo '<div class="alert alert-danger fade in">';
          echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
          echo '<strong>Error 01: </strong>'. mysqli_error($conn);
          echo '</div>';
        }
      }
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Error: </strong>'.$lang['ERROR_TIMES_INVALID'];
      echo '</div>';
    }
    if(!mysqli_error($conn)){
      echo '<div class="alert alert-success fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>O.K: </strong>'.$lang['OK_ADD'];
      echo '</div>';
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Error 02: </strong>'.mysqli_error($conn);
      echo '</div>';
    }
  }
} //endif post
?>

<!-- ############################### FILTER ################################### -->

<form method="post">
  <div class="row">
    <div class="col-md-4"> <!-- Date Interval-->
      <input id="calendar" type="text" maxlength="7" class="form-control" name="filterDateFrom" value=<?php echo substr($filterDateFrom,0,7); ?> >
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

          if($calculator->end[$i] != '0000-00-00 00:00:00' && ($calculator->activity[$i] == 0 || $calculator->activity[$i] == 5) && $canBook == 'TRUE'){
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
      <a class="btn btn-default" data-toggle="collapse" href="#add-interval"><?php echo $lang['ADD_TIMESTAMPS']; ?></a>
      <div id="add-interval" class="container-fluid collapse" >
        <br>
        <div class="row"><small> *<?php echo $lang['INFO_INTERVALS_AS_EXPECTED']; ?></small></div>
        <div class="row">
          <div class="col-xs-6">
            <div class="input-group input-daterange">
              <input id='multiple_calendar' type="date" class="form-control" value="" placeholder="Von" name="add_multiple_start">
              <span class="input-group-addon"> - </span>
              <input id='multiple_calendar2' type="date" class="form-control" value="" placeholder="Bis" name="add_multiple_end">
            </div>
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6 text-right">
            <select name="add_multiple_status" class="js-example-basic-single">
              <option value="1"><?php echo $lang['VACATION']; ?></option>
              <option value="4"><?php echo $lang['VOCATIONAL_SCHOOL']; ?></option>
              <option value="2"><?php echo $lang['SPECIAL_LEAVE']; ?></option>
              <option value="6"><?php echo $lang['COMPENSATORY_TIME']; ?></option>
            </select>
            <button class="btn btn-warning" type="submit" name="add_multiple"><?php echo $lang['ADD']; ?></button>
          </div>
        </div>
        <br>
      </div>
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
                  echo '<div class="col-md-6"><br>';
                  echo "<select name='newActivity' class='js-example-basic-single' style='width:150px'>";
                  for($j = 0; $j < 7; $j++){
                    if($calculator->activity[$i] == $j){
                      echo "<option value='$j' selected>". $lang['ACTIVITY_TOSTRING'][$j] ."</option>";
                    } else {
                      echo "<option value='$j'>". $lang['ACTIVITY_TOSTRING'][$j] ."</option>";
                    }
                  }
                  echo "</select> ";
                  if(!$calculator->indecesIM[$i]){ //timestamp doesnt exist
                    $A = $B = $calculator->date[$i].' 12:00:00';
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
                  echo "<input type='hidden' name='set_all_filters' style='display:none' value='$filterDateFrom,$filterID,$filterStatus' />";
                  echo '</div>';
                  echo '<div class="col-md-6">';
                  echo '<label>'.$lang['LUNCHBREAK'].'</label>';
                  if(!$calculator->indecesIM[$i]){
                    echo '<input type="number" step="0.01" class="form-control" name="newBreakValues" value="0.0" style="width:100px" />';
                  } else {
                    echo ': '.$lang['ADDITION']."<input type='number' step='0.01' name='addBreakValues' value='0.0' class='form-control' style='width:100px' />";
                  }
                  echo '</div>';
                ?>
              </div>
              <br><br>
              <div class="row">
                <div class="col-md-6">
                  <label><?php echo $lang['BEGIN']; ?></label>
                  <input type="datetime-local" class='form-control input-sm' onkeydown="return event.keyCode != 13;" name="timesFrom" value="<?php echo substr($A,0,10).'T'. substr($A,11,5) ?>"/>
                </div>
                <div class="col-md-6">
                  <label><?php echo $lang['END']; ?></label>
                  <div class="checkbox">
                    <label>
                      <input type="radio" name="is_open" value="0" checked="checked" />
                      <input type="datetime-local" style="display:inline;max-width:180px;" class='form-control input-sm' onkeydown="return event.keyCode != 13;" name="timesTo" value="<?php echo substr($B,0,10).'T'. substr($B,11,5) ?>"/>
                    </label>
                    <br><br>
                    <label><input type="radio" name="is_open" value="1" style="margin-left:13px;" /><?php echo $lang['OPEN']; ?></label>
                  </div>
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
    $timeToUTC = $bookingResultsResults['timeToUTC'][$i];
    $bookingResult = $bookingResultsResults[$i]; //uncloneable object
    $row = $bookingResult->fetch_assoc(); //we need this in here for the timestampID in modal class.
    ?>
  <form method="post">
    <div class="modal fade bookingModal-<?php echo $row['timestampID']; ?>" tabindex="-1">
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
                <th>Option</th>
              </thead>
              <tbody>
                <?php
                echo "<input type='hidden' name='set_all_filters' style='display:none' value='$filterDateFrom,$filterID,$filterStatus' />";
                do {
                  $x = $row['bookingTableID'];
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
                  echo '<td>';
                  echo '<button type="button" class="btn btn-default" data-dismiss="modal" data-toggle="modal" data-target=".editingProjectsModal-'.$x.'" ><i class="fa fa-pencil"></i></button> ';
                  echo '<input type="checkbox" name="delete_bookings_ids[]" value="'.$x.'" /> ';
                  echo '</td>';
                  echo '</tr>';
                  if($row['bookingType'] == 'break'){
                    echo '<tr style="background-color:#f0f0f0">';
                    echo "<td></td><td></td><td><i class='fa fa-arrow-right'</td><td>Split:</td>";
                    echo '<td><input type="time" min="'.$A.'" max="'.$B.'" class="form-control" name="splits_from_'.$x.'" />'.'</td>';
                    echo '<td><input type="time" min="'.$A.'" max="'.$B.'" class="form-control" name="splits_to_'.$x.'" />'.'</td>';
                    echo '<td>';
                    echo "<select name='splits_activity_".$x."' class='js-example-basic-single' style='width:150px'>";
                    for($j = 0; $j < 5; $j++){ //can't do mixed split
                      echo "<option value='$j'>". $lang['ACTIVITY_TOSTRING'][$j] ."</option>";
                    }
                    echo "</select>";
                    echo '</td>';
                    echo '<td><button type="submit" class="btn btn-warning" name="splits_save" value="'.$x.'"><i class="fa fa-floppy-o"></i></button></td>';
                    echo '</tr>';
                  }
                } while($row = $bookingResult->fetch_assoc());
                ?>
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
            <button type="submit" class="btn btn-warning" name="delete_bookings"><?php echo $lang['DELETE_SELECTION']; ?></button>
          </div>
        </div>
      </div>
    </div>
  </form>
  <?php
  //loop through this shit again
  mysqli_data_seek($bookingResult,0);
  while($row = $bookingResult->fetch_assoc()):
  $x = $row['bookingTableID'];
  ?>
    <!-- Edit bookings (time only) -->
    <form method="post">
      <div class="modal fade editingProjectsModal-<?php echo $x ?>">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title"><?php echo substr($row['start'], 0, 10); ?></h4>
            </div>
            <div class="modal-body" style="max-height: 80vh;  overflow-y: auto;">
              <?php
              echo "<input type='hidden' name='set_all_filters' style='display:none' value='$filterDateFrom,$filterID,$filterStatus' />";
              if(!empty($row['projectID'])){ //if this is a break, do not display client/project selection
                echo "<select style='width:200px' class='js-example-basic-single' onchange='showNewProjects(\" #newProjectName$x \", this.value, 0);' >";
                $sql = "SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).") ORDER BY NAME ASC";
                $clientResult = $conn->query($sql);
                while($clientRow = $clientResult->fetch_assoc()){
                  $selected = '';
                  if($clientRow['id'] == $row['clientID']){
                    $selected = 'selected';
                  }
                  echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
                }
                echo "</select> <select style='width:200px' id='newProjectName$x' class='js-example-basic-single' name='editing_projectID_$x'>";
                $sql = "SELECT * FROM $projectTable WHERE clientID =".$row['clientID'].'  ORDER BY NAME ASC';
                $clientResult = $conn->query($sql);
                while($clientRow = $clientResult->fetch_assoc()){
                  $selected = '';
                  if($clientRow['id'] == $row['projectID']){
                    $selected = 'selected';
                  }
                  echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
                }
                echo "</select> <br><br>";
              } //end if(break)

              $A = carryOverAdder_Hours($row['start'],$timeToUTC);
              $B = carryOverAdder_Hours($row['end'],$timeToUTC);

              if($row['chargedTimeStart'] == '0000-00-00 00:00:00'){
                $A_charged = '0000-00-00 00:00:00';
              } else {
                $A_charged = carryOverAdder_Hours($row['chargedTimeStart'],$timeToUTC);
              }
              if($row['chargedTimeEnd'] == '0000-00-00 00:00:00'){
                $B_charged = '0000-00-00 00:00:00';
              } else {
                $B_charged = carryOverAdder_Hours($row['chargedTimeEnd'],$timeToUTC);
              }
              ?>
              <label><?php echo $lang['DATE']; ?>:</label>
              <div class="row">
                <div class="col-xs-6"><input type='text' class='form-control' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name="editing_time_from_<?php echo $x;?>" value="<?php echo substr($A,0,16); ?>"></div>
                <div class="col-xs-6"><input type='text' class='form-control' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_time_to_<?php echo $x;?>' value="<?php echo substr($B,0,16); ?>"></div>
              </div>
              <br>
              <label><?php echo $lang['DATE'] .' '. $lang['CHARGED']; ?>:</label>
              <div class="row">
                <div class="col-xs-6"><input type='text' class='form-control' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_chargedtime_from_<?php echo $x;?>' value="<?php echo substr($A_charged,0,16); ?>"></div>
                <div class="col-xs-6"><input type='text' class='form-control' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_chargedtime_to_<?php echo $x;?>' value="<?php echo substr($B_charged,0,16); ?>"></div>
              </div>
              <br>
              <label>Infotext</label>
              <textarea style='resize:none;' name='editing_infoText_<?php echo $x;?>' class='form-control' rows="5"><?php echo $row['infoText']; ?></textarea>
              <br>
              <?php
              if($row['bookingType'] != 'break' && $row['booked'] == 'FALSE'){//cant charge a break, can you
                echo "<div class='row'><div class='col-xs-2 col-xs-offset-8'><input id='".$x."_1' type='checkbox' onclick='toggle2(\"".$x."_2\")' name='editing_charge' value='".$x."' /> <label>".$lang['CHARGED']. "</label> </div>"; //gotta know which ones he wants checked.
                echo "<div class='col-xs-2'><input id='".$x."_2' type='checkbox' onclick='toggle2(\"".$x."_1\")' name='editing_nocharge' value='".$x."' /> <label>".$lang['NOT_CHARGEABLE']. "</label> </div></div>";
              }
              ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-warning" name="editing_save" value="<?php echo $x;?>"><?php echo $lang['SAVE']; ?></button>
            </div>
          </div>
        </div>
      </div>
    </form>
  <?php endwhile; ?>
<?php endfor; ?>
<script>
$('.modal').on('hidden.bs.modal', function (e) {
  if($('.modal').hasClass('in')) {
    $('body').addClass('modal-open');
  }
});
function showNewProjects(selectID, client, project){
  $.ajax({
    url:'ajaxQuery/AJAX_project.php',
    data:{clientID:client, projectID:project},
    type: 'post',
    success : function(resp){
      $(selectID).html(resp);
    },
    error : function(resp){}
  });
};
function toggle2(uncheckID){
  uncheckBox = document.getElementById(uncheckID);
  uncheckBox.checked = false;
}

var myCalendar = new dhtmlXCalendarObject(["multiple_calendar","multiple_calendar2"]);
myCalendar.setSkin("material");
myCalendar.setDateFormat("%Y-%m-%d");
</script>
  <!-- /BODY -->
  <?php include 'footer.php'; ?>
