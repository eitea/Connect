<?php include 'header.php'; ?>
<script src="/plugins/chartsjs/Chart.min.js"></script>
<!-- BODY -->
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
},120000)
</script>

<?php
$curID = $userID;
include 'tableSummary.php'; //this is how it goes

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
$expected_today = floatval($row[strtolower(date('D', strtotime($today)))]);
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
?>

<script>
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
        text: '<?php echo $lang['AVERAGE'].' '. $lang['HOURS']; ?>'
      }
    },
    data: {
      labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
      datasets: [{
        label: "Mittel",
        backgroundColor: [
          <?php
          if($_SESSION['color'] == 'dark'){
            echo "'#6D6D6D', '#A3F375', '#6D6D6D', '#A3F375', '#6D6D6D', '#A3F375', '#6D6D6D'";
          } else {
            echo "'rgba(251, 231, 54, 0.5)', 'rgba(189, 209, 71, 0.5)', 'rgba(75, 192, 192, 0.5)', 'rgba(90, 163, 231, 0.5)', 'rgba(154, 125, 210, 0.5)'";
          }
          ?>
        ],
        data: [<?php echo $mean_mon.', '.$mean_tue.', '.$mean_wed.', '.$mean_thu.', '.$mean_fri.', '.$mean_sat.', '.$mean_sun; ?>]
      }
    ]}
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
          <?php
          if($_SESSION['color'] == 'dark'){
            echo "'#A3F375', '#75bdee', '#DDDDDD', '#6D6D6D'";
          } else {
            echo "'#fba636', '#75bdee', '#828282', '#7fcb51' ";
          }
          ?>
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
        text: '<?php echo $lang['TODAY']; ?>'
      }
    }
  });
  <?php endif; ?>
});
</script>

  <!-- /BODY -->
  <?php include 'footer.php'; ?>
