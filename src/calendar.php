<?php include 'header.php'; ?>

<div class="page-header">
  <h3><?php echo $lang['VACATION'].' '.'Calendar' ?></h3>
</div>

<link rel='stylesheet' href='/plugins/fullcalendar/fullcalendar.css' />
<script src='/plugins/fullcalendar/lib/jquery.min.js'></script>
<script src='/plugins/fullcalendar/lib/moment.min.js'></script>
<script src='/plugins/fullcalendar/fullcalendar.js'></script>

<style>
.fc-today{
   background-color: #dffbce !important;
}
</style>
<?php
//prefer the request, since user can delete his requests by himself for a 'cleanup'. This way the calendar won't get bigger and bigger as long as the system goes on
$sql = "SELECT * FROM $userRequests INNER JOIN $userTable ON $userTable.id = $userRequests.userID WHERE $userRequests.status = '2' AND $userRequests.requestType = 'vac'";
$result = $conn->query($sql);
$vacs = '';
if($result && $result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    $title = $lang['VACATION'] . ': ' . $row['firstname'] . ' ' . $row['lastname'];
    $start = substr($row['fromDate'], 0, 10).' 04:00';
    $end = substr($row['toDate'], 0, 10).' 23:00';
    $vacs .= "{ title: '$title', start: '$start', end: '$end'},";
  }
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
    events: [<?php echo $vacs; ?>],
    eventColor: '#A3F375',
    eventTextColor: '#6D6D6D'
  });
});
</script>

<?php
if ($_SESSION['userid'] == 1) {
  echo "<a href='allowVacations.php'>" .$lang['VACATION_REQUESTS']. "</a>";
}
?>
<?php include 'footer.php'; ?>
