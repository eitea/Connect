<?php
require 'connection.php';
require 'createTimestamps.php';

//recalculates lunchbreak based on bookings
//check all logs
$sql = "SELECT * FROM $logTable WHERE $logTable.status = '0'";
$result = $conn->query($sql);

//fix 1 : update breakCredit to fit booked lunchbreaks
while($row = $result->fetch_assoc()){
  $indexIM = $row['indexIM'];
  //get all the break bookings made for that log
  $sql = "SELECT * FROM $projectBookingTable WHERE timestampID = $indexIM AND projectID IS NULL";
  $result2 = $conn->query($sql);
  $correctBreakCredit = 0;
  while($row2 = $result2->fetch_assoc()){
    $correctBreakCredit += timeDiff_Hours($row2['start'], $row2['end']);
  }
  $sql = "UPDATE $logTable SET breakCredit = $correctBreakCredit WHERE indexIM = $indexIM";
  $conn->query($sql);
  echo mysqli_error($conn);
}

//fix 2 : repair the illegal stampings, where he was here for over 6 ours but lunched for less than 30minutes
$sql = "SELECT hoursOfRest, pauseAfterHours, time, timeEnd, status, breakCredit, indexIM FROM $logTable INNER JOIN $userTable ON $logTable.userID = $userTable.id
WHERE timeEnd != '0000-00-00 00:00:00'
AND TIMESTAMPDIFF(HOUR, time, timeEnd) > pauseAfterHours
AND breakCredit < hoursOfRest
AND status = '0'";

$result = $conn->query($sql);
if($result && $result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    //update breakcredit
    $sql = "UPDATE $logTable SET breakCredit = '". $row['hoursOfRest'] . "' WHERE indexIM = " . $row['indexIM'];
    $conn->query($sql);
    echo mysqli_error($conn);

    $diff = $row['hoursOfRest'] - $row['breakCredit'];
    $newTime = carryOverAdder_Hours($row['time'], floor($diff));
    $newTime = carryOverAdder_Minutes($newTime, (($diff * 60) % 60));
    //create booking
    $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText) VALUES('".$row['time']."', '$newTime', ".$row['indexIM'].", 'Fixing lunch auto-break')";
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}

echo mysqli_error($conn);

?>
