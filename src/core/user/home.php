<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<link rel='stylesheet' href='plugins/fullcalendar/fullcalendar.css' />
<script src="plugins/chartsjs/Chart.min.js"></script>
<script src='plugins/fullcalendar/lib/moment.min.js'></script>
<script src='plugins/fullcalendar/fullcalendar.js'></script>
<script src='plugins/fullcalendar/locale/de-at.js'></script>
<style>
#statisticChart, #analysisChart{
  margin:50px 0px 50px 0px;
}
</style>

<div class="container">
  <div class="col-md-6">
    <canvas id="analysisChart" width="200" height="100" style="max-width:400px; max-height:200px;"></canvas>
  </div>
  <div class="col-md-6">
    <canvas id="statisticChart" width="200" height="100" style="max-width:400px; max-height:200px;" ></canvas>
  </div>
</div>

<script type="text/javascript">
setTimeout(function(){
  location = ''
},180000)
</script>

<?php
require dirname(dirname(__DIR__)) . '/Calculators/IntervalCalculator.php';
$logSums = new Interval_Calculator($userID);

if($logSums->saldo < 0){
  $color = 'style="color:red"';
} else {
  $color = 'style="color:#00ba29"';
}

$result_Sum = $conn->query("SELECT $intervalTable.*, beginningDate FROM $userTable INNER JOIN $intervalTable ON $intervalTable.userID = $userTable.id WHERE $userTable.id = $userID AND endDate IS NULL");
if($result_Sum && $result_Sum->num_rows > 0){
  $userRow = $result_Sum->fetch_assoc();
} else {
  echo $conn->error;
}
?>
<br><br>
<div class="container-fluid">
  <div class="col-md-5">
    <ul class="nav nav-tabs" role="tablist">
      <li class="active"><a href="#saldo" data-toggle="tab">Saldo</a></li>
      <li><a href="#weekHours" data-toggle="tab"><?php echo $lang['TIMETABLE']; ?></a></li>
      <li><a href="#other" data-toggle="tab"><?php echo $lang['DATA']; ?></a></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane active" id="saldo">
        <table class="table table-striped">
          <thead>
            <th><?php echo $lang['DESCRIPTION']; ?></th>
            <th><?php echo $lang['HOURS']; ?></th>
          </thead>
          <tbody>
            <?php
            echo '<tr><td>'.$lang['EXPECTED_HOURS'].'</td><td>-'. number_format(array_sum($logSums->shouldTime), 2, '.', '') .'</td></tr>';
            echo '<tr><td>'.$lang['ABSOLVED_HOURS'].'</td><td>+'. number_format(array_sum($logSums->absolvedTime), 2, '.', '') .'</td></tr>';
            echo '<tr><td>'.$lang['LUNCHBREAK'].'</td><td>-'. number_format(array_sum($logSums->lunchTime), 2, '.', '') . '</td></tr>';
            $overTimeAdditive = $corrections = 0;
            foreach($logSums->endOfMonth as $arr){
              $overTimeAdditive += $arr['overTimeLump'];
              $corrections += $arr['correction'];
            }
            if($overTimeAdditive) echo '<tr><td>'.$lang['OVERTIME_ALLOWANCE'] . '</td> <td> -' . number_format($overTimeAdditive) . ' </td></tr>';
            if($corrections) echo "<tr><td><a data-toggle='modal' data-target='#correctionModal'>".$lang['CORRECTION'].' '.$lang['HOURS'].'</a></td><td>'.sprintf('%+.2f',$corrections).'</td></tr>';
            echo "<tr><td style='font-weight:bold;'>".$lang['SUM']."</td><td $color>". number_format($logSums->saldo, 2, '.', ''). '</td></tr>';
            ?>
          </tbody>
        </table>
      </div>
      <div class="tab-pane" id="weekHours">
        <table class="table table-striped">
          <thead>
            <th><?php echo $lang['TIMETABLE']; ?></th>
            <th><?php echo $lang['HOURS']; ?></th>
          </thead>
          <tbody>
            <?php
            $theBigSum = $userRow['mon'] + $userRow['tue'] + $userRow['wed'] + $userRow['thu'] + $userRow['fri'] + $userRow['sat'] + $userRow['sun'];
            echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['mon'].'</td><td>'. $userRow['mon'] .'</td></tr>';
            echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['tue'].'</td><td>'. $userRow['tue'] .'</td></tr>';
            echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['wed'].'</td><td>'. $userRow['wed'] .'</td></tr>';
            echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['thu'].'</td><td>'. $userRow['thu'] .'</td></tr>';
            echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['fri'].'</td><td>'. $userRow['fri'] .'</td></tr>';
            echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['sat'].'</td><td>'. $userRow['sat'] .'</td></tr>';
            echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['sun'].'</td><td>'. $userRow['sun'] .'</td></tr>';
            echo "<tr><td style='font-weight:bold;'>".$lang['SUM']."</td><td>". $theBigSum .'</td></tr>';
            ?>
          </tbody>
        </table>
      </div>
      <div class="tab-pane" id="other">
        <table class="table table-striped">
          <thead>
            <th><?php echo $lang['DESCRIPTION']; ?> </th>
            <th>Detail</th>
          </thead>
          <tbody>
            <?php
            echo '<tr><td>'. $lang['ENTRANCE_DATE'] .'</td><td>'. substr($userRow['beginningDate'],0,10) .'</td></tr>';
            echo '<tr><td><a href="../time/vacations?curID='.$userID.'" >'. $lang['VACATION_DAYS'].' '.$lang['AVAILABLE'].'</a></td><td>'. sprintf('%.2f', $logSums->availableVacation) .'</td></tr>';
            echo '<tr><td>'. $lang['VACATION_DAYS'].$lang['PER_YEAR'].'</td><td>'. $userRow['vacPerYear'] .'</td></tr>';
            echo '<tr><td>'. $lang['OVERTIME_ALLOWANCE'].'</td><td>'. $userRow['overTimeLump'] .'</td></tr>';
            ?>
          </tbody>
        </table>
      </div>
    </div> <!-- End tab content -->

    <?php if($showReadyPlan == 'TRUE' || $isCoreAdmin == 'TRUE'): ?>
      <br><h4><?php echo $lang['READY_STATUS'];?></h4>
      <table class="table table-striped">
        <thead>
          <th>Name</th>
          <th>Checkin</th>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SELECT enableReadyCheck FROM $configTable");
          $row = $result->fetch_assoc();
          $today = substr(getCurrentTimestamp(),0,10);
          $sql = "SELECT * FROM $logTable INNER JOIN $userTable ON $userTable.id = $logTable.userID WHERE time LIKE '$today %' AND timeEnd = '0000-00-00 00:00:00' ORDER BY lastname ASC";
          $result = $conn->query($sql);
          if($result && $result->num_rows > 0){
            while($row = $result->fetch_assoc()){
              echo '<tr><td>' . $row['firstname'] .' '. $row['lastname'] .'</td>';
              echo '<td>'. substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']), 11, 5) . '</td></tr>';
            }
          } else {
            echo mysqli_error($conn);
          }
          ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <div class="col-md-7">
    <div id='calendar'></div>
  </div>
</div>

<div class="modal fade" id="correctionModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?php echo $lang['CORRECTION']; ?></h4>
      </div>
      <div class="modal-body">
        <table class="table table-hover">
          <thead>
            <th><?php echo $lang['CORRECTION'] .' '. $lang['DATE']; ?></th>
            <th><?php echo $lang['ADJUSTMENTS'].' '. $lang['HOURS']; ?></th>
            <th>Info</th>
          </thead>
          <tbody>
            <?php
            $result = $conn->query("SELECT * FROM $correctionTable WHERE userID = $userID AND cType='log'");
            while($result && ($row = $result->fetch_assoc())){
              echo '<tr>';
              echo '<td>'.substr($row['createdOn'],0,10).'</td>';
              echo '<td>'.sprintf("%+.2f",$row['hours'] * $row['addOrSub']).'</td>';
              echo '<td>'.$row['infoText'].'</td>';
              echo '</tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>

<?php  //PROCESSING CHART DATA:
$mean_mon = $mean_tue = $mean_wed = $mean_thu = $mean_fri = $mean_sat = $mean_sun = 0;
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 ) AS times FROM $logTable WHERE WEEKDAY(time) = 0 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_mon = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows); }
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 ) AS times FROM $logTable WHERE WEEKDAY(time) = 1 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_tue = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 ) AS times FROM $logTable WHERE WEEKDAY(time) = 2 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_wed = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 ) AS times FROM $logTable WHERE WEEKDAY(time) = 3 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_thu = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 ) AS times FROM $logTable WHERE WEEKDAY(time) = 4 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_fri = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 ) AS times FROM $logTable WHERE WEEKDAY(time) = 5 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_sat = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60) AS times FROM $logTable WHERE WEEKDAY(time) = 6 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_sun = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}

$today = getCurrentTimestamp();
$result = $conn->query("SELECT * FROM $intervalTable WHERE userID = $userID AND endDate IS NULL");
$row = $result->fetch_assoc();
if(isHoliday($today)){
  $expected_today = 0;
} else {
  $expected_today = floatval($row[strtolower(date('D', strtotime($today)))]);
}
$result = $conn->query("SELECT * FROM $logTable WHERE userID = $userID AND (time LIKE '".substr($today, 0, 10)." %' OR timeEnd = '0000-00-00 00:00:00')");
if($result && ($row = $result->fetch_assoc())){
  $break_hours = 0;
  $result_break = $conn->query("SELECT TIMESTAMPDIFF(MINUTE, start, end) as breakCredit FROM projectBookingData where bookingType = 'break' AND timestampID = ".$row['indexIM']);
  while($result_break && ($row_break = $result_break->fetch_assoc())) $break_hours += $row_break['breakCredit'] / 60;
  $break_today = $break_hours;
  if($row['timeEnd'] == '0000-00-00 00:00:00'){
    $absolved_today = timeDiff_Hours($row['time'], $today);
  } else {
    $absolved_today = timeDiff_Hours($row['time'], $row['timeEnd']);
  }
  if($row['status'] == '5'){
    $mixed_result = $conn->query("SELECT * FROM mixedInfoData WHERE timestampID = ".$row['indexIM']);
    if($mixed_result && ($mixed_row = $mixed_result->fetch_assoc())){
      $mixed_absolved = timeDiff_Hours($mixed_row['timeStart'], $mixed_row['timeEnd']);
      if($mixed_absolved > ($expected_today - $absolved_today)){
        $absolved_today = $expected_today;
      } else { //splits are absolved times
        $splits_result = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, start, end)) AS split_absolved FROM projectBookingData WHERE bookingType = 'mixed' AND timestampID = ".$row['indexIM']);
        if($splits_result && ($splits_row = $splits_result->fetch_assoc())){
          $mixed_absolved -= $splits_row['split_absolved'];
        }
        $absolved_today = $mixed_absolved;
      }
    }
  }

  $absolved_today += 0.01;
  $absolved_today -= $break_today;
  if($absolved_today > $expected_today){
    $surplus_today = $absolved_today - $expected_today;
    $absolved_today = $expected_today;
    $expected_today = 0;
  } else {
    $surplus_today = 0;
    $expected_today -= $absolved_today;
  }
  $absolved_today = sprintf('%.2f', $absolved_today);
  $expected_today = sprintf('%.2f', $expected_today);
  $surplus_today = sprintf('%.2f', $surplus_today);
} else {
  $absolved_today = 0;
}


//CALENDAR STUFF
$dates = array();
$start = getCurrentTimestamp(); //normal users can only see future dates
if($isCoreAdmin == 'TRUE') { $start = date('Y-m-d', strtotime('-1 year')); }
$result = $conn->query("SELECT time, status, userID, firstname, lastname FROM logs INNER JOIN $userTable ON $userTable.id = logs.userID
  WHERE status != 0 AND status != 5 AND DATE(time) >= DATE('$start') ORDER BY userID, status, time");
  if($result && ($row = $result->fetch_assoc())){
    $start = substr($row['time'], 0, 10);
    $prev_row = $row;
    if($result && ($row = $result->fetch_assoc())){
      do {
        if($prev_row['userID'] != $row['userID'] || timeDiff_Hours($prev_row['time'], $row['time']) > 36){ //cut chain
          $title = 'Abwesend: ' . $prev_row['firstname'] . ' ' . $prev_row['lastname'];
          $end = substr(carryOverAdder_Hours($prev_row['time'],24), 0, 10); //adding hours would display '5a' for 5am.
          $dates[] = "{ title: '$title', start: '$start', end: '$end', backgroundColor: '#81e8e5'}";
          $start = substr($row['time'], 0, 10);
        }
        $prev_row = $row;
      } while($row = $result->fetch_assoc());
      $title = 'Abwesend: ' . $prev_row['firstname'] . ' ' . $prev_row['lastname'];
      $end = substr(carryOverAdder_Hours($prev_row['time'],24), 0, 10); //adding hours would display '5a' for 5am.
      $dates[] = "{ title: '$title', start: '$start', end: '$end', backgroundColor: '#81e8e5'}";
    }
  } else {
    $conn->error;
  }

$result = $conn->query("SELECT begin, name FROM holidays WHERE RIGHT(name, 4) = ' (§)' AND begin > '$start' AND begin < '".date('Y-m-d', strtotime('+1 year'))."'"); echo $conn->error;
while($result && ($row = $result->fetch_assoc())){
    $dates[] = "{ title: '".$row['name']."', start: '".substr($row['begin'], 0, 10)."', textColor: 'white', backgroundColor: '#999'}";
}
  ?>

  <script>
  $(document).ready(function(){
    setTimeout(function () {
      $("#calendar").fullCalendar({
        height: 500,
        firstDay: 1,
        header: {
          left: 'prev, next today',
          center: 'title',
          right: 'month, agendaWeek, listMonth'
        },
        events: [<?php echo implode(', ', $dates); ?>],
        eventTextColor: '#6D6D6D',
        eventBorderColor: '#FFFFFF'
      });
    });
  });
  $(function(){
    var ctx_analysis = document.getElementById("analysisChart");
    var myAnalysisChart = new Chart(ctx_analysis, {
      type: 'horizontalBar',
      options: {
        legend:{
          display: false
        },
        title:{
          display:true,
          text: '<?php echo $lang['AVERAGE'].' '. $lang['WORKING_HOURS']; ?>'
        }
      },
      data: {
        labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        datasets: [{
          label: "Mittel",
          backgroundColor: [
            'rgba(251, 231, 54, 0.5)', 'rgba(189, 209, 71, 0.5)', 'rgba(75, 192, 192, 0.5)', 'rgba(90, 163, 231, 0.5)', 'rgba(154, 125, 210, 0.5)'
          ],
          data: [<?php echo $mean_mon.', '.$mean_tue.', '.$mean_wed.', '.$mean_thu.', '.$mean_fri.', '.$mean_sat.', '.$mean_sun; ?>]
        }]
      }
    });

    <?php if($absolved_today): ?>
    var ctx_statistic = document.getElementById("statisticChart");
    var myStatisticChart = new Chart(ctx_statistic, {
      type: 'doughnut',
      data: {
        labels: [
          "<?php echo $lang['ABSOLVED']; ?>",
          "<?php echo $lang['BREAK']; ?>",
          "<?php echo $lang['EXPECTED']; ?>",
          "<?php echo $lang['OVERTIME']; ?>"
        ],
        datasets: [{
          data: [<?php echo $absolved_today.', '.$break_today.', '.$expected_today.', '.$surplus_today; ?>],
          backgroundColor: [
            '#fba636', '#75bdee', '#828282', '#7fcb51'
          ]
        }]
      },
      options: {
        legend:{
          display: true,
          position: 'right'
        },
        tooltips: {
          callbacks: {
            label: function(tooltipItems, data) {
              return ' ' + data.labels[tooltipItems.index] +': ' + Math.round(data.datasets[0].data[tooltipItems.index]*100)/100 + 'h';
            }
          }
        },
        title: {
          display: true,
          text: '<?php echo $lang['TODAY'] .' '. date('d.M', strtotime($today)); ?>'
        }
      }
    });
    <?php endif; ?>
  });
</script>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
