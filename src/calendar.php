<?php include 'header.php'; ?>

<div class="page-header">
  <h3><?php echo $lang['CALENDAR']; ?></h3>
</div>

<link rel='stylesheet' href='plugins/fullcalendar/fullcalendar.css' />
<script src='plugins/fullcalendar/lib/moment.min.js'></script>
<script src='plugins/fullcalendar/fullcalendar.js'></script>
<?php
$dates = '';
$start = getCurrentTimestamp(); //normal users can only see future dates
if($isCoreAdmin) { $start = date('Y-m-d', strtotime('-1 year')); }
$result = $conn->query("SELECT time, status, userID, firstname, lastname FROM logs INNER JOIN $userTable ON $userTable.id = logs.userID WHERE status != 0 AND DATE(time) > DATE('$start') ORDER BY userID, time, status");
if($result && ($row = $result->fetch_assoc())){
  $start = substr($row['time'], 0, 10);
  $prev_row = $row;
  if($result && ($row = $result->fetch_assoc())){
    $colors = array('', '#81e8e5', '#d4b6ff', '#ffa24b', '#ceddf0', '', '#ffa4a4');
    do {
      if($prev_row['status'] != 5){
        $title = $lang['ACTIVITY_TOSTRING'][$prev_row['status']] . ': ' . $prev_row['firstname'] . ' ' . $prev_row['lastname'];
        $color = $colors[$prev_row['status']];
      } else {
        continue;
      }
      if($prev_row['status'] != $row['status'] || $prev_row['userID'] != $row['userID'] || timeDiff_Hours($prev_row['time'], $row['time']) > 36){ //chain
        $end = substr(carryOverAdder_Hours($prev_row['time'],24), 0, 10); //adding hours would display '5a' for 5am.
        $dates .= "{ title: '$title', start: '$start', end: '$end', backgroundColor: '$color'},";
        $start = substr($row['time'], 0, 10);
      }
      $prev_row = $row;
    } while($row = $result->fetch_assoc());
  }
} else {
  echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
}

?>
<div id='calendar' style='height: 900px;'></div>
<script>
$(document).ready(function(){
  $("#calendar").fullCalendar({
    height: 600,
    firstDay: 1,
    header: {
      left: "prev, next today",
      center: "title",
      right: "month, agendaWeek, listMonth"
    },
    defaultView: "month",
    events: [<?php echo $dates; ?>],
    eventTextColor: '#6D6D6D',
    eventBorderColor: '#FFFFFF'
  });
});
</script>

<?php
if($isCoreAdmin) {
  echo "<a href='../time/check' class='btn btn-warning'>" .$lang['VACATION_REQUESTS']. "</a>";
}
?>
<?php include 'footer.php'; ?>
