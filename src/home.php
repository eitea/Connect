<?php include 'header.php'; ?>
<script src="../plugins/chartsjs/Chart.min.js"></script>
<!-- BODY -->
<style>
canvas{
  margin:50px 0px 50px 0px;
}
</style>

<div class="container-fluid">
  <div class="col-md-6">
    <canvas id="analysisChart" width="200" height="100"></canvas>
  </div>
  <div class="col-md-6">
    <canvas id="statisticChart" width="200" height="100"></canvas>
  </div>
</div>

<?php
$curID = $userID;
include 'tableSummary.php'; //this is how it goes

$mean_mon = $mean_tue = $mean_wed = $mean_thu = $mean_fri = $mean_sat = $mean_sun = 0;
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 - breakCredit) AS times FROM $logTable WHERE WEEKDAY(time) = 0 AND userID = 5 AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_mon = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows); }
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 - breakCredit) AS times FROM $logTable WHERE WEEKDAY(time) = 1 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_tue = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 - breakCredit) AS times FROM $logTable WHERE WEEKDAY(time) = 2 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_wed = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 - breakCredit) AS times FROM $logTable WHERE WEEKDAY(time) = 3 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_thu = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 - breakCredit) AS times FROM $logTable WHERE WEEKDAY(time) = 4 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_fri = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 - breakCredit) AS times FROM $logTable WHERE WEEKDAY(time) = 5 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_sat = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}
$result = $conn->query("SELECT (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60 - breakCredit) AS times FROM $logTable WHERE WEEKDAY(time) = 6 AND userID = $userID AND timeEnd != '0000-00-00 00:00:00'");
if($result && $result->num_rows > 0){$mean_sun = sprintf('%.2f',array_sum(array_column($result->fetch_all(),0))/$result->num_rows);}

$today = getCurrentTimestamp();
$result = $conn->query("SELECT * FROM $intervalTable WHERE userID = $userID AND endDate IS NULL");
$row = $result->fetch_assoc();
$expected_today = floatval($row[strtolower(date('D', strtotime($today)))]);
$result = $conn->query("SELECT time, timeEnd, breakCredit FROM $logTable WHERE userID = $userID AND time LIKE '".substr($today,0,10)." %'");
$row = $result->fetch_assoc();
$break_today = floatval($row['breakCredit']);
if($row['timeEnd'] == '0000-00-00 00:00:00'){
  $absolved_today = timeDiff_Hours($row['time'], $today);
} else {
  $absolved_today = timeDiff_Hours($row['time'], $row['timeEnd']);
}
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
?>

<script>
var ctx_analysis = document.getElementById("analysisChart");
var myAnalysisChart = new Chart(ctx_analysis, {
  type: 'horizontalBar',
  options: {
    legend:{
      display: false
    },
    title:{
      display:true,
      text: 'Durchschnittliche Stunden'
    }
  },
  data: {
    labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    datasets: [{
      label: "Mittel",
      backgroundColor: [
        'rgba(255, 99, 132, 0.5)',
        'rgba(54, 162, 235, 0.5)',
        'rgba(255, 206, 86, 0.5)',
        'rgba(75, 192, 192, 0.5)',
        'rgba(153, 102, 255, 0.5)'
      ],
      data: [<?php echo $mean_mon.', '.$mean_tue.', '.$mean_wed.', '.$mean_thu.', '.$mean_fri.', '.$mean_sat.', '.$mean_sun; ?>]
    }
  ]}
});

var ctx_statistic = document.getElementById("statisticChart");
var myStatisticChart = new Chart(ctx_statistic, {
  type: 'doughnut',
  data: {
    labels: [
      "Absolviert",
      "Pause",
      "Erwartet",
      "Überstunden"
    ],
    datasets: [{
      data: [<?php echo $absolved_today.', '.$break_today.', '.$expected_today.', '.$surplus_today; ?>],
      backgroundColor: [
        "#fba636",
        "#75bdee",
        "#828282",
        "#7fcb51"
      ]
    }]
  },
  options: {
    legend:{
      display: false
    },
    title: {
      display: true,
      text: 'Heute'
    }
  }
});
</script>

  <!-- /BODY -->
  <?php include 'footer.php'; ?>
