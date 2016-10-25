<?php
require 'connection.php';

//recalculates lunchbreak based on booking
//get all logs with the breakCredit
$sql = "SELECT indexIM, userID, enableProjecting FROM $logTable INNER JOIN $userTable ON $logTable.userID = $userTable.id WHERE $userTable.enableProjecting = 'TRUE'";
$result = $conn->query($sql);

//fix 1 : update lunchbreak to fit booked lunchbreaks
while($row = $result->fetch_assoc()){
  //get all the break bookings made for that log
  $indexIM = $row['indexIM'];
  $sql = "SELECT * FROM $projectBookingTable WHERE timestampID = $indexIM AND projectID IS NULL";
  $result2 = $conn->query($sql);
  $correctBreakCredit = 0;
  while($row2 = $result2->fetch_assoc()){
    $correctBreakCredit += timeDiff_Hours($row2['start'], $row2['end']);
  }
  $sql = "UPDATE $logTable SET breakCredit = $correctBreakCredit WHERE indexIM = $indexIM";
  $conn->query($sql);
}

$sql = "SELECT * FROM $logTable INNER JOIN $userTable ON $logTable.userID = $userTable.id
WHERE enableProjecting = 'TRUE' AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(HOUR, time, timeEnd) > pauseAfterHours AND breakCredit < hoursOfRest";
$result = $conn->query($sql);
if($result && $result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    $sql = "UPDATE $logTable SET breakCredit = '". $row['hoursOfRest'] . "' WHERE indexIM = " . $row['indexIM'];
    $conn->query($sql);
  }
}

//$to - $from in Hours.
function timeDiff_Hours($from, $to) {
  $timeEnd = strtotime($to) / 3600;
  $timeBegin = strtotime($from) /3600;
  return $timeEnd - $timeBegin;
}
?>

<script type='text/javascript'>
window.close();
</script>
