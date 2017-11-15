<?php include 'header.php'; enableToTime($userID); //project/time ?>
<?php
require_once 'Calculators/IntervalCalculator.php';
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0, "users" => array(), "bookings" => array(1, '', 'checked'), "date" => array(substr(getCurrentTimestamp(), 0, 8).'01', date('Y-m-t', strtotime(getCurrentTimestamp()))) ) ;
$activeTab = 'home';
?>

<div class="page-header"><h3><?php echo $lang['TIMESTAMPS']; ?><div class="page-header-button-group"><?php include 'misc/set_filter.php'; ?></div></h3></div>

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
  } elseif (isset($_POST['ts_remove'])){
    $x = intval($_POST['ts_remove']);
    $conn->query("DELETE FROM $logTable WHERE indexIM=$x;");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
  }
} //endif post
?>

<ul class="nav nav-tabs">
  <li <?php if($activeTab == 'home'){echo 'class="active"';}?>><a data-toggle="tab" href="#home" onclick="$('#sav').val('home');"><?php echo $lang['VIEW_PROJECTS']; ?></a></li>
  <?php foreach($filterings['users'] as $u){
    $active = '';
    if($u == $activeTab) $active = 'class="active"';
    $result_fc = mysqli_query($conn, "SELECT firstname FROM UserData WHERE id = $u");
    echo '<li '.$active.' ><a data-toggle="tab" href="#'.$u.'">'.$result_fc->fetch_assoc()['firstname'].'</a></li>';
  }
  ?>
</ul>

<div class="tab-content">
  <!-- ############################### Projects ################################### -->

  <div id="home" class="tab-pane fade <?php if($activeTab == 'home'){echo 'in active';}?>">

  <div class="page-header"><h3><?php echo $lang['VIEW_PROJECTS']; ?></h3></div>

  <?php //quess who needs queries.
    $companyQuery = $clientQuery = $projectQuery = $userQuery = $chargedQuery = $breakQuery = $driveQuery = "";
    if($filterings['company']){$companyQuery = "AND $companyTable.id = ".$filterings['company']; }
    if($filterings['client']){$clientQuery = "AND $clientTable.id = ".$filterings['client']; }
    if($filterings['project']){$projectQuery = "AND $projectTable.id = ".$filterings['project']; }
    if($filterings['users']){$userQuery = "AND $userTable.id IN (".implode(', ', $filterings['users']).')'; }
    if($filterings['bookings'][0] == '2'){$chargedQuery = "AND $projectBookingTable.booked = 'TRUE' "; } elseif($filterings['bookings'][0] == '1'){$chargedQuery= " AND $projectBookingTable.booked = 'FALSE' "; }
    if(!$filterings['bookings'][1]){$breakQuery = "AND $projectBookingTable.bookingType != 'break' "; } //projectID == NULL
    if(!$filterings['bookings'][2]){$driveQuery = "AND $projectBookingTable.bookingType != 'drive' "; }

    //filtering a project means excluding all breaks. we dont want that, which explains the query below.
    $sql = "SELECT $projectTable.id AS projectID,
    $clientTable.id AS clientID, $companyTable.id AS companyID,
    $companyTable.name AS companyName, $clientTable.name AS clientName, $projectTable.name AS projectName,
    $userTable.id AS userID, $userTable.firstname, $userTable.lastname,
    $projectBookingTable.*, $projectBookingTable.id AS projectBookingID,
    $logTable.timeToUTC,
    $projectTable.hours, $projectTable.hourlyPrice, $projectTable.status
    FROM $projectBookingTable
    INNER JOIN $logTable ON  $projectBookingTable.timeStampID = $logTable.indexIM
    INNER JOIN $userTable ON $logTable.userID = $userTable.id
    LEFT JOIN $projectTable ON $projectBookingTable.projectID = $projectTable.id
    LEFT JOIN $clientTable ON $projectTable.clientID = $clientTable.id
    LEFT JOIN $companyTable ON $clientTable.companyID = $companyTable.id
    WHERE DATE_ADD($projectBookingTable.start, INTERVAL $logTable.timeToUTC HOUR) >= DATE('".$filterings['date'][0]."')
    AND DATE(DATE_ADD($projectBookingTable.end, INTERVAL $logTable.timeToUTC HOUR)) <= DATE('".$filterings['date'][1]."')
    AND (($projectBookingTable.projectID IS NULL $breakQuery $userQuery) OR ( 1 $userQuery $chargedQuery $companyQuery $clientQuery $projectQuery $driveQuery $breakQuery))
    ORDER BY $projectBookingTable.start ASC";
    $result = $conn->query($sql);
    if(!$result || $result->num_rows <= 0){
      echo '<script>document.getElementById("set_filter_search").click();</script>';
    }
  ?>
  <form id="project_table" method="post">
    <table id="projectTable" class="table table-hover table-condensed">
      <thead>
        <th><?php echo $conn->error ; ?></th>
        <th><?php echo $lang['COMPANY'].' - '.$lang['CLIENT'].' - '.$lang['PROJECT']; ?></th>
        <th><?php echo $lang['USERS']; ?></th>
        <th style="max-width:300px">Infotext</th>
        <th><?php echo $lang['DATE']; ?></th>
        <th><?php echo $lang['DATE'] .' '. $lang['CHARGED']; ?></th>
        <th><?php echo $lang['MINUTES']; ?></th>
        <th>
          <label><input type="radio" class="disable-styling" onClick="toggle('checkingIndeces', 'noCheckCheckingIndeces')" name="toggleRadio"> <?php echo $lang['CHARGED']; ?></label>
          <label><input type="radio" class="disable-styling" onClick="toggle('noCheckCheckingIndeces', 'checkingIndeces')" name="toggleRadio"> <?php echo $lang['NOT_CHARGEABLE']; ?></label>
        </th>
        <th>Detail</th>
        <th></th>
      </thead>
      <tbody>
        <?php
        $csv = $lang['CLIENT'].';'.$lang['PROJECT'].';Info;'.$lang['DATE'].' - '. $lang['FROM'].';'. $lang['DATE'].' - '. $lang['TO'].';'.
        $lang['TIMES'].' - '. $lang['FROM'].';'.$lang['TIMES'].' - '. $lang['TO'].';'.$lang['SUM'].' (min)'.';'.$lang['HOURS_CREDIT'].';Person;'.$lang['HOURLY_RATE'].';'.
        $lang['ADDITIONAL_FIELDS'].';'.$lang['EXPENSES']."\n";

        $sum_min = 0;
        $numRows = $result->num_rows;
        for($i = 0; $i < $numRows; $i++) {
          $row = $result->fetch_assoc();
          //have to make sure admin can only see what is available to him
          if(($row['companyID'] && !in_array($row['companyID'], $available_companies)) || ($row['userID'] && !in_array($row['userID'], $available_users))){
            continue;
          }
          $x = $row['projectBookingID'];
          $timeDiff = timeDiff_Hours($row['start'], $row['end']);

          if($row['bookingType'] == 'break'){
            $icon = "fa fa-cutlery";
          } elseif($row['bookingType'] == 'drive'){
            $icon = "fa fa-car";
          } elseif($row['bookingType'] == 'mixed'){
            $icon = "fa fa-plus";
            $row['infoText'] = $lang['ACTIVITY_TOSTRING'][$row['mixedStatus']];
          } else {
            $icon = "fa fa-bookmark";
          }

          echo '<tr>';
          echo "<td style='width:10px'><i class='$icon'></i></td>";
          echo '<td>'.$row['companyName'].'<br> '.$row['clientName'].'<br> '.$row['projectName'].'</td>';
          echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
          echo '<td style="max-width:300px">'.$row['infoText'].'</td>';

          $A = carryOverAdder_Hours($row['start'],$row['timeToUTC']);
          $B = carryOverAdder_Hours($row['end'],$row['timeToUTC']);

          $A_charged = $B_charged = '--';
          if($row['chargedTimeStart'] != '0000-00-00 00:00:00'){
            $A_charged = carryOverAdder_Hours($row['chargedTimeStart'],$row['timeToUTC']);
          }
          if($row['chargedTimeEnd'] != '0000-00-00 00:00:00'){
            $B_charged = carryOverAdder_Hours($row['chargedTimeEnd'],$row['timeToUTC']);
          }

          $csv .= $row['clientName'] .';';
          $csv .= $row['projectName'] .';';
          $csv .= str_replace(array("\r", "\n",";"), ' ', $row['infoText']) .';';
          $csv .= substr($A,0,10) .';';
          $csv .= substr($B,0,10) .';';
          $csv .= substr($A,11,6) .';';
          $csv .= substr($B,11,6) .';';
          $csv .= number_format((timeDiff_Hours($row['start'], $row['end']))*60, 0, '.', '') .';';
          $csv .= $row['hours'] .';';
          $csv .= $row['firstname']." ".$row['lastname'] .';';
          $csv .= ' '.$row['hourlyPrice'].' ';

          echo '<td style="width:200px;">'.substr($A,0,16).' - '.substr($B,11,5)."</td>";
          echo '<td style="max-width:200px;whitpre;">'.$lang['FROM'].': '.substr($A_charged,0,16)."<br>".$lang['TO'].':   '.substr($B_charged,0,16)."</td>";
          echo "<td>" .number_format((timeDiff_Hours($row['start'], $row['end']))*60, 0, '.', '') . "</td>";

          if($row['bookingType'] != 'break' && $row['booked'] != 'TRUE'){ //if this is a break or has been charged already, do not display dis
            echo "<td><input id='".$row['projectBookingID']."_01' type='checkbox' class='disable-styling' onclick='toggle2(\"".$row['projectBookingID']."_02\")' name='checkingIndeces[]' value='".$row['projectBookingID']."'>"; //gotta know which ones he wants checked.
            echo " / <input id='".$row['projectBookingID']."_02' type='checkbox' class='disable-styling' onclick='toggle2(\"".$row['projectBookingID']."_01\")' name='noCheckCheckingIndeces[]' value='".$row['projectBookingID']."'></td>";
          } else {
            echo "<td></td>";
          }

          $projStat = (!empty($row['status']))? $lang['PRODUCTIVE'] :  $lang['PRODUCTIVE_FALSE'];
          $detailInfo = $row['hours'] .' || '. $row['hourlyPrice'] .' || '. $projStat;
          $interninfo = $row['internInfo'];
          $optionalinfo = $csv_optionalinfo = $expensesinfo = $csv_expensesinfo = '';
          $extraFldRes = $conn->query("SELECT name FROM $companyExtraFieldsTable WHERE companyID = ".$row['companyID']);
          if($extraFldRes && $extraFldRes->num_rows > 0){
            $extraFldRow = $extraFldRes->fetch_assoc();
            if($row['extra_1']){$optionalinfo = '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_1'].'<br>'; $csv_optionalinfo = $extraFldRow['name'].': '.$row['extra_1']; }
          }
          if($extraFldRes && $extraFldRes->num_rows > 1){
            $extraFldRow = $extraFldRes->fetch_assoc();
            if($row['extra_2']){$optionalinfo .= '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_2'].'<br>'; $csv_optionalinfo .= ', '.$extraFldRow['name'].': '.$row['extra_2'];}
          }
          if($extraFldRes && $extraFldRes->num_rows > 2){
            $extraFldRow = $extraFldRes->fetch_assoc();
            if($row['extra_3']){$optionalinfo .= '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_3']; $csv_optionalinfo .= ', '.$extraFldRow['name'].': '.$row['extra_3'];}
          }
          $csv .= $csv_optionalinfo .';';
          if($row['exp_unit'] > 0){ $expensesinfo .= $lang['QUANTITY'].': '.$row['exp_unit'].'<br>'; $csv_expensesinfo .= "{$lang['QUANTITY']}: {$row['exp_unit']},"; }
          if($row['exp_price'] > 0){ $expensesinfo .= $lang['PRICE_STK'].': '.$row['exp_price'].'<br>'; $csv_expensesinfo .= "{$lang['PRICE_STK']}: {$row['exp_price']}"; }
          if($row['exp_info']){ $expensesinfo .= $lang['DESCRIPTION'].': '.$row['exp_info'].'<br>'; $csv_expensesinfo .= ", {$lang['DESCRIPTION']}: {$row['exp_info']}"; }
          $csv .= $csv_expensesinfo .';';
          echo "<td>";
          if($row['booked'] == 'FALSE'){ echo '<button type="button" onclick="appendModal_proj('.$x.', '.$userID.')" class="btn btn-default" data-toggle="modal" data-target=".editingModal-'.$x.'" ><i class="fa fa-pencil"></i></button>'; }
          if($row['bookingType'] != 'break'){ echo "<a tabindex='0' role='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Stundenkonto - Stundenrate - Projektstatus' data-content='$detailInfo' data-placement='left'><i class='fa fa-info-circle'></i></a>";}
          if(!empty($interninfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Intern' data-content='$interninfo' data-placement='left'><i class='fa fa-question-circle-o'></i></a>"; }
          if(!empty($optionalinfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['ADDITIONAL_FIELDS']."' data-content='$optionalinfo' data-placement='left'><i class='fa fa-question-circle'></i></a>"; }
          if(!empty($expensesinfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['EXPENSES']."' data-content='$expensesinfo' data-placement='left'><i class='fa fa-plus'></i></a>"; }
          echo "<button type='submit' class='btn btn-default' value='$x' name='delete_booking'><i class='fa fa-trash-o'></i></button>";
          echo '</td>';
          echo '<td><input type="hidden" name="editingIndeces[]" value="'.$row['projectBookingID'].'"></td>'; //needed to check what has been charged
          echo "</tr>";

          $csv .= "\n";
          $sum_min += timeDiff_Hours($row['start'], $row['end']);
        } //end while fetch_assoc

        echo "<tr>";
        echo '<td style="font-weight:bold">'.$lang['SUM'].'</td> <td></td> <td></td> <td></td> <td></td> <td></td>';
        echo "<td>".number_format($sum_min*60, 2, '.', '')."</td> <td></td> <td></td> <td></td>";
        echo "</tr>";
        ?>
      </tbody>
    </table>
  </form>
  <form id="csvForm" method="POST" target="_blank" action="../project/csvDownload"><input type="hidden" name='csv' value="<?php echo rawurlencode($csv); ?>" /></form>
</div>

<!-- ############################### UserData ################################### -->

<?php foreach($filterings['users'] as $x): 
if($activeTab == $x) {echo "<div id='$x' class'tab-pane fade in active'>"; } else { echo "<div id='$x' class='tab-pane fade'>"; }
?>
<br><br>
  <form method="POST">
    <table class="table table-hover table-condensed">
      <thead>
        <th><?php echo $lang['WEEKLY_DAY']; ?></th>
        <th style="min-width:90px"><?php echo $lang['DATE']; ?></th>
        <th><?php echo $lang['BEGIN']; ?></th>
        <th><?php echo $lang['BREAK']; ?></th>
        <th><?php echo $lang['END']; ?></th>
        <th><?php echo $lang['ACTIVITY']; ?></th>
        <th><?php echo $lang['SHOULD_TIME']; ?></th>
        <th><?php echo $lang['IS_TIME']; ?></th>
        <th><?php echo $lang['SALDO_DAY']; ?></th>
        <th><?php echo $lang['SALDO_MONTH']; ?></th>
        <th>Saldo</th>
        <th><?php echo $lang['EVALUATION']; ?></th>
        <th style="min-width:110px">Option</th>
      </thead>
      <tbody>
        <?php
        $calculator = new Interval_Calculator($x, $filterings['date'][0], $filterings['date'][1]);
        $bookingStmt = $conn->prepare("SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
        LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id) LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id) WHERE timestampID = ? ORDER BY end ASC");
        $bookingStmt->bind_param("i", $calcIndexIM);

        echo "<tr style='color:#626262;'>";
        echo "<td colspan='10'><strong>Vorheriges Saldo: </strong></td>";
        echo "<td>".displayAsHoursMins($calculator->prev_saldo)."</td><td></td><td></td></tr>";
        $lunchbreakSUM = $expectedHoursSUM = $absolvedHoursSUM = $saldo_month = 0;
        for($i = 0; $i < $calculator->days; $i++){
          $calcIndexIM = $calculator->indecesIM[$i];
          //if($filterings['logs'][0] && $calculator->activity[$i] != $filterings['logs'][0]) continue;
          //$filterings['logs'][1] == 'checked' && $calculator->shouldTime[$i] == 0 && $calculator->absolvedTime[$i] == 0) continue;
            $tinyEndTime = '-';

            $A = $calculator->start[$i];
            $B = $calculator->end[$i];
            if($calculator->start[$i]) $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
            if($calculator->end[$i] && $calculator->end[$i] != '0000-00-00 00:00:00') $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);

            $saldo_month += $calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];
            $theSaldo = round($calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i], 2);

            $saldoStyle = 'style=color:#6fcf2c;'; //green
            if($theSaldo < 0) $saldoStyle = 'style=color:#fc8542;'; //red

            $neutralStyle = '';
            if($calculator->shouldTime[$i] == 0 && $calculator->absolvedTime[$i] == 0) $neutralStyle = "style=color:#c7c6c6;";

            echo "<tr class='clicker' $neutralStyle>";
            echo '<td>' . $lang['WEEKDAY_TOSTRING'][$calculator->dayOfWeek[$i]] . '</td>';
            echo '<td>' . $calculator->date[$i] . '</td>';
            echo '<td>' . substr($A,11,5) . '</td>';
            echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
            echo '<td>' . substr($B,11,5) . '</td>';
            echo '<td>' . $lang['ACTIVITY_TOSTRING'][$calculator->activity[$i]]. '</td>';
            echo '<td>' . displayAsHoursMins($calculator->shouldTime[$i]) . '</td>';
            echo '<td>' . displayAsHoursMins($calculator->absolvedTime[$i] - $calculator->lunchTime[$i]) . '</td>';
            echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . '</td>';
            echo '<td>' . displayAsHoursMins($saldo_month) . '</td>';
            echo '<td>' . displayAsHoursMins($saldo_month + $calculator->prev_saldo) . '</td>';
            echo '<td>'.$lang['EMOJI_TOSTRING'][$calculator->feeling[$i]].'</td>';
            echo '<td>';
            if(strtotime($calculator->date[$i]) >= strtotime($calculator->beginDate)){
              echo '<button type="button" class="btn btn-default" title="'.$lang['EDIT'].'" onclick="appendModal_time('.$calcIndexIM.', '.$i.', \''.$calculator->date[$i].'\')"  data-toggle="modal" data-target=".editingModal-'.$i.'"><i class="fa fa-pencil"></i></button> ';
            }
            if($calculator->indecesIM[$i] != 0){ echo '<button type="submit" class="btn btn-default" title="'.$lang['DELETE'].'" name="ts_remove" value="'.$calcIndexIM.'"><i class="fa fa-trash-o"></i></button>';}
            echo '</td>';
            echo "</tr>";

            $lunchbreakSUM += $calculator->lunchTime[$i];
            $expectedHoursSUM += $calculator->shouldTime[$i];
            $absolvedHoursSUM += $calculator->absolvedTime[$i] - $calculator->lunchTime[$i];

            if($calcIndexIM){
              $bookingStmt->execute();
              $result = $bookingStmt->get_result();
              while($row = $result->fetch_assoc()){
                $A = substr(carryOverAdder_Hours($row['start'], $calculator->timeToUTC[$i]), 11, 5);
                $B = substr(carryOverAdder_Hours($row['end'], $calculator->timeToUTC[$i]), 11, 5);
                echo '<tr style="display:none;color:#797979;"><td colspan="13"><div class="row">';
                echo '<div class="col-xs-1"></div>';
                echo '<div class="col-sm-2">'.$row['name'].'</div>';
                echo '<div class="col-sm-2">'.$row['projectName'].'</div>';
                echo '<div class="col-sm-1">'.$A.'</div>';
                echo '<div class="col-sm-1">'.$B.'</div>';
                echo '<div class="col-sm-4">'.$row['infoText'].'</div>';
                echo '</div></td></tr>';
              }
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
              echo "<td></td></tr>";
            }
            //corrections
            if($calculator->endOfMonth[$calculator->date[$i]]['correction']){
              $saldo_month += $calculator->endOfMonth[$calculator->date[$i]]['correction'];
              echo "<tr>";
              echo "<td colspan='9'>".$lang['CORRECTION'].": </td>";
              echo "<td style='color:#9222cc'>" . displayAsHoursMins($calculator->endOfMonth[$calculator->date[$i]]['correction']) . "</td>";
              echo '<td>'.displayAsHoursMins($saldo_month).'</td>';
              echo "<td>".displayAsHoursMins($saldo_month + $calculator->prev_saldo)."</td><td></td></tr>";
            }
          }
        } //endfor
        echo "<tr style='font-weight:bold;'>";
        echo "<td colspan='11'>Gesamt: </td>";
        echo '<td>'.displayAsHoursMins($calculator->saldo).'</td>';
        echo "<td></td></tr>";
        ?>
      </tbody>
    </table>
  </form>
</div>
<?php endforeach; ?>
</div>

<!-- ############################### END TABBING END ################################### -->

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

<script>
var existingModals = new Array();
function appendModal_time(id, index, date){
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
  } else {
    $('.editingModal-'+index).modal('show');
  }
  event.stopPropagation(); //propagation stop for tr clicker
}

var existingModals_p = new Array();
function appendModal_proj(id, user){
  if(existingModals_p.indexOf(id) == -1){
    if(existingModals_p.indexOf(id) == -1){
    $.ajax({
    url:'ajaxQuery/AJAX_bookingModal.php',
    data:{bookingID:id, userID:user},
    type: 'get',
    success : function(resp){
      $("#editingModalDiv").append(resp);
      existingModals_p.push(id);
      onPageLoad();
      $('.editingModal-'+id).modal('show');
    },
    error : function(resp){}
   });
  }
  }
}
$('.clicker').click(function(){
  $(this).nextUntil('.clicker').toggle('normal');
});


function showClients(place, company, client, project, projectPlace){
  $.ajax({
    url:'ajaxQuery/AJAX_getClient.php',
    data:{companyID:company, clientID:client},
    type: 'get',
    success : function(resp){
      $(place).html(resp);
    },
    error : function(resp){}
  });
  showProjects(projectPlace, client, project);
};
function showProjects(place, client, project){
  $.ajax({
    url:'ajaxQuery/AJAX_getProjects.php',
    data:{clientID:client, projectID:project},
    type: 'get',
    success : function(resp){
      $(place).html(resp);
    },
    error : function(resp){}
  });
  showProjectfields(project);
};
function showProjectfields(project){
  $.ajax({
    url:'ajaxQuery/AJAX_getProjectFields.php',
    data:{projectID:project},
    type: 'get',
    success : function(resp){
      $("#project_fields").html(resp);
    },
    error : function(resp){}
  });
}
function showLastBooking(id){
  $.ajax({
    url:'ajaxQuery/AJAX_getLastBooking.php',
    type:'get',
    data:{userID:id},
    success: function(resp){
      $("#date_field").val(resp.substr(0,10));
      $("#time_field").val(resp.substr(11,17));
    },
    error : function(resp){}
  });
}
function showMyDiv(o, toShow){
  if(o.checked){
    document.getElementById(toShow).style.display='block';
  } else {
    document.getElementById(toShow).style.display='none';
  }
}
function hideMyDiv(o, toShow){
  if(o.checked){
    document.getElementById(toShow).style.display='none';
  } else {
    document.getElementById(toShow).style.display='block';
  }
}

function toggle(checkId, uncheckId) {
  checkboxes = document.getElementsByName(checkId + '[]');
  checkboxesUncheck = document.getElementsByName(uncheckId + '[]');
  for(var i = 0; i<checkboxes.length; i++) {
    checkboxes[i].checked = true;
    checkboxesUncheck[i].checked = false;
    checkboxValues[checkboxes[i].id] = true;
  }
  Cookies.set('checkboxValues', checkboxValues, { expires: 1, path: '' });
}
function toggle2(uncheckID){
  uncheckBox = document.getElementById(uncheckID);
  uncheckBox.checked = false;
  checkboxValues[uncheckBox.id] = false;
}

$(document).ready(function(){
  $('#projectTable').DataTable({
    order: [],
    ordering: false,
    language: {
      <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    },
    responsive: true,
    dom: 'f',
    autoWidth: false,
    fixedHeader: {
      header: true,
      headerOffset: 50,
      zTop: 1
    },
    paging: false
  });
  setTimeout(function(){ window.dispatchEvent(new Event('resize')); $('#projectTable').trigger('column-reorder.dt'); }, 500);
});
</script>
<!-- /BODY -->
<?php include 'footer.php'; ?>
