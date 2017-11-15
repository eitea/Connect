<?php include 'header.php'; enableToTime($userID); ?>
<?php
require_once 'Calculators/IntervalCalculator.php';
$filterings = array("savePage" => $this_page, "user" => 0, "logs" => array(0, 'checked'), "date" => array(substr(getCurrentTimestamp(), 0, 8).'01', date('Y-m-t', strtotime(getCurrentTimestamp()))) ) ;
?>

<div class="page-header">
  <h3>
    <?php echo $lang['TIMESTAMPS']; ?>
    <div class="page-header-button-group">
      <?php include 'misc/set_filter.php'; ?>
      <a class="btn btn-default" data-toggle="modal" data-target=".addInterval" <?php if(!$filterings['user']) echo 'disabled'; ?> title="<?php echo $lang['ADD']; ?>"><i class="fa fa-plus"></i></a>
    </div>
  </h3>
</div>

<?php
//i need some filterings vars in here
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['saveChanges'])){
    $imm = $_POST['saveChanges'];
    $timeStart = str_replace('T', ' ',$_POST['timesFrom']) .':00';
    $timeFin = str_replace('T', ' ',$_POST['timesTo']) .':00';
    $status = intval($_POST['newActivity']);
    if($imm == 0){ //create new
      $creatUser = intval($filterings['user']);
      $timeToUTC = intval($_POST['creatTimeZone']);
      $newBreakVal = floatval($_POST['newBreakValues']);
      if($_POST['is_open']){
        $timeFin = '0000-00-00 00:00:00';
      } else {
        if($timeFin != '0000-00-00 00:00:00' && $timeFin != ':00'){ $timeFin = carryOverAdder_Hours($timeFin, ($timeToUTC * -1)); } else {$timeFin = '0000-00-00 00:00:00';}
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
      if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
    } else { //update old
      $addBreakVal = floatval($_POST['addBreakValues']);
      if($addBreakVal){ //add a break
        $breakEnd = carryOverAdder_Minutes($timeStart, intval($addBreakVal*60));
        $conn->query("INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType) VALUES('$timeStart', '$breakEnd', $imm, 'Administrative Break', 'break')");
      }
      if($timeFin == '0000-00-00 00:00:00' || $timeFin == ':00' || $_POST['is_open']){
        $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd='0000-00-00 00:00:00', status='$status' WHERE indexIM = $imm";
      } else {
        $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd=DATE_SUB('$timeFin', INTERVAL timeToUTC HOUR), status='$status' WHERE indexIM = $imm";
      }
      if($conn->query($sql)){
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
      } else {
        echo mysqli_error($conn);
      }
    }
  }

  if (isset($_POST['ts_remove'])){
    $x = intval($_POST['ts_remove']);
    $conn->query("DELETE FROM $logTable WHERE indexIM=$x;");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
  }

  if(isset($_POST['add_multiple']) && !empty($_POST['add_multiple_user'])){
    $filterID = intval($_POST['add_multiple_user']);
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
          echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        }
      }
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_TIMES_INVALID'].'</div>';
    }
    if($conn->error){ echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>'; }
    else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
  } elseif(isset($_POST['add_multiple'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_SELECTION'].'</div>';
  }

} //endif post
?>

Diese Seite ist veraltet und wird nicht mehr gewartet. Benutzung auf eigene Gefahr.
<!-- ############################### TABLE ################################### -->

<?php
if($filterings['user']):
  $result = $conn->query("SELECT id, firstname FROM $userTable WHERE id = ".$filterings['user']);
  if($result && ($row = $result->fetch_assoc())){
    $x = $row['id'];
  }
  ?>
  <br>
  <form method="post">
    <table id="mainTable" class="table table-hover table-condensed">
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
        <th>Saldo</th>
        <th></th>
        <th>Option</th>
      </thead>
      <tbody>
        <?php
        $calculator = new Interval_Calculator($x, $filterings['date'][0], $filterings['date'][1]);

        $bookingStmt = $conn->prepare("SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
        LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id) LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id) WHERE timestampID = ? ORDER BY end DESC");
        $bookingStmt->bind_param("i", $calcIndexIM);

        echo "<tr style='color:#626262;'>";
        echo "<td colspan='11'><strong>Vorheriges Saldo: </strong></td>";
        echo "<td>".displayAsHoursMins($calculator->prev_saldo)."</td><td></td></tr>";
        $lunchbreakSUM = $expectedHoursSUM = $absolvedHoursSUM = $saldo_month = 0;
        for($i = 0; $i < $calculator->days; $i++){
          $calcIndexIM = $calculator->indecesIM[$i];
          //sync with modal creation
          if($filterings['logs'][0] && $calculator->activity[$i] != $filterings['logs'][0]) continue;
          if($filterings['logs'][1] != 'checked' || $calculator->shouldTime[$i] != 0 || $calculator->absolvedTime[$i] != 0){              
            $style = "";
            $tinyEndTime = '-';
            $bookingStmt->execute();
            $result = $bookingStmt->get_result();

            if($calculator->end[$i] != '0000-00-00 00:00:00' && $canBook == 'TRUE' && ($calculator->activity[$i] == 0 || $calculator->activity[$i] == 5)){
              $config2 = $result->fetch_assoc();
              $bookingTimeDifference = timeDiff_Hours($config2['end'], $calculator->end[$i]) * 60;
              if($bookingTimeDifference <= $bookingTimeBuffer) $style = "color:#6fcf2c"; //green
              if($bookingTimeDifference > $bookingTimeBuffer)$style = "color:#facf1e"; //yellow
              if($bookingTimeDifference > $bookingTimeBuffer * 2 || $bookingTimeDifference < 0)$style = "color:#fc8542"; //red
              if($bookingTimeDifference < 0) $style = "color:#f0621c;font-weight:bold"; //monsterred
              if($calculator->end[$i]) $tinyEndTime = substr(carryOverAdder_Hours($config2['end'], $calculator->timeToUTC[$i]),11,5);
            }

            $A = $calculator->start[$i];
            $B = $calculator->end[$i];
            if($calculator->start[$i]) $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
            if($calculator->end[$i] && $calculator->end[$i] != '0000-00-00 00:00:00') $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);

            $saldo_month += $calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];

            $theSaldo = round($calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i], 2);
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

            echo "<tr $neutralStyle>";
            echo '<td>' . $lang['WEEKDAY_TOSTRING'][$calculator->dayOfWeek[$i]] . '</td>';
            echo '<td>' . $calculator->date[$i] . '</td>';
            echo '<td>' . substr($A,11,5) . '</td>';
            echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
            echo '<td>' . substr($B,11,5) . '</td>';
            echo "<td style='$style'><small>" . $tinyEndTime . "</small></td>";
            echo '<td>' . $lang['ACTIVITY_TOSTRING'][$calculator->activity[$i]]. '</td>';
            echo '<td>' . displayAsHoursMins($calculator->shouldTime[$i]) . '</td>';
            echo '<td>' . displayAsHoursMins($calculator->absolvedTime[$i] - $calculator->lunchTime[$i]) . '</td>';
            echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . '</td>';
            echo '<td>' . displayAsHoursMins($saldo_month) . '</td>';
            echo '<td>' . displayAsHoursMins($saldo_month + $calculator->prev_saldo) . '</td>';
            echo '<td>'.$lang['EMOJI_TOSTRING'][$calculator->feeling[$i]].'</td>';
            echo '<td>';
            if(strtotime($calculator->date[$i]. ' 12:00:00') >= strtotime($calculator->beginDate)){
              echo '<button type="button" class="btn btn-default" title="'.$lang['EDIT'].'" onclick="appendModal('.$calcIndexIM.', '.$i.', \''.$calculator->date[$i].'\')"  data-toggle="modal" data-target=".editingModal-'.$i.'"><i class="fa fa-pencil"></i></button> ';
            }
            if($calculator->indecesIM[$i] != 0){ echo '<button type="submit" class="btn btn-default" title="'.$lang['DELETE'].'" name="ts_remove" value="'.$calcIndexIM.'"><i class="fa fa-trash-o"></i></button>';}
            echo '</td>';           
            echo '</tr>';

            $lunchbreakSUM += $calculator->lunchTime[$i];
            $expectedHoursSUM += $calculator->shouldTime[$i];
            $absolvedHoursSUM += $calculator->absolvedTime[$i] - $calculator->lunchTime[$i];
          }

          if(isset($calculator->endOfMonth[$calculator->date[$i]])){
            //overTimeLump
            if($calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'] > 0){
              $saldo_month -= $calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'];
              echo "<tr>";
              echo "<td colspan='9'>".$lang['OVERTIME_ALLOWANCE'].": </td>";
              echo "<td style='color:#fc8542;'>-" . displayAsHoursMins($calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'])."</td>";
              echo '<td>'.displayAsHoursMins($saldo_month).'</td>';
              echo '<td>'.displayAsHoursMins($saldo_month + $calculator->prev_saldo).'</td>';
              echo "<td></td><td></td></tr>";
            }
            //corrections
            if($calculator->endOfMonth[$calculator->date[$i]]['correction']){
              $saldo_month += $calculator->endOfMonth[$calculator->date[$i]]['correction'];
              echo "<tr>";
              echo "<td colspan='9'>".$lang['CORRECTION'].": </td>";
              echo "<td style='color:#9222cc'>" . displayAsHoursMins($calculator->endOfMonth[$calculator->date[$i]]['correction']) . "</td>";
              echo '<td>'.displayAsHoursMins($saldo_month).'</td>';
              echo "<td>".displayAsHoursMins($saldo_month + $calculator->prev_saldo)."</td><td></td><td></td></tr>";
            }
          }
        } //endfor

        //complete
        echo "<tr style='font-weight:bold;'>";
        echo "<td colspan='11'>Gesamt: </td>";
        echo '<td>'.displayAsHoursMins($calculator->saldo).'</td>';
        echo "<td></td><td></td></tr>";
        ?>
      </tbody>
    </table>
  </form>

  <!-- ############################### END TABLE END ################################### -->

  <div id="editingModalDiv"></div>

  <!-- add intervals modal -->
  <form method="POST">
    <div class="modal fade addInterval">
      <div class="modal-dialog modal-content modal-md">
        <div class="modal-header"><h4><?php echo $lang['ADD_TIMESTAMPS']; ?></h4></div>
        <div class="modal-body">
          <div class="input-group input-daterange">
            <input type="text" class="form-control datepicker" value="" placeholder="Von" name="add_multiple_start">
            <span class="input-group-addon"> - </span>
            <input type="text" class="form-control datepicker" value="" placeholder="Bis" name="add_multiple_end">
          </div>
          <br>
          <div class="row">
            <div class="col-md-6">
              <select name="add_multiple_user" class="js-example-basic-single">
                <?php
                echo '<option value="0">...</option>';
                $result_fc = mysqli_query($conn, "SELECT * FROM $userTable WHERE id IN (".implode(', ', $available_users).")");
                while($result_fc && ($row_fc = $result_fc->fetch_assoc())){
                  $checked = '';
                  if($filterings['user'] == $row_fc['id']) { $checked = 'selected'; }
                  echo "<option $checked value='".$row_fc['id']."' >".$row_fc['firstname'].' '.$row_fc['lastname']."</option>";
                }
                ?>
              </select>
            </div>
            <div class="col-md-6">
              <select name="add_multiple_status" class="js-example-basic-single">
                <option value="1"><?php echo $lang['VACATION']; ?></option>
                <option value="4"><?php echo $lang['VOCATIONAL_SCHOOL']; ?></option>
                <option value="2"><?php echo $lang['SPECIAL_LEAVE']; ?></option>
                <option value="6"><?php echo $lang['COMPENSATORY_TIME']; ?></option>
              </select>
            </div>
          </div>
          <br>
          <small> *<?php echo $lang['INFO_INTERVALS_AS_EXPECTED']; ?></small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-default" type="button" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
          <button class="btn btn-warning" type="submit" name="add_multiple"><?php echo $lang['ADD']; ?></button>
        </div>
      </div>
    </div>
  </form>
<?php
else:
  echo '<br><div class="alert alert-info">'.$lang['INFO_REQUIRE_USER'].'</div>';
  echo '<script>document.getElementById("set_filter_search").click();</script>';
endif;
?>

<script>
var existingModals = new Array();
function appendModal(id, index, date){
  if(existingModals.indexOf(index) == -1){
    $.ajax({
    url:'ajaxQuery/AJAX_timeModal.php',
    data:{timestampID:id, index:index, date:date},
    type: 'get',
    success : function(resp){
      $("#editingModalDiv").append(resp);
      existingModals.push(index);
      onPageLoad();
      $('.editingModal-'+index).modal('show');
    },
    error : function(resp){}
   });
  }
}
$('.clicker').click(function(){
  $(this).nextUntil('.clicker').slideToggle('normal');
});
</script>
<!-- /BODY -->
<?php include 'footer.php'; ?>
