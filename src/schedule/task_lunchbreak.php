<?php

require_once dirname(__DIR__) . "/connection.php";
require_once dirname(__DIR__) . "/utilities.php";

$sql = "SELECT l1.*, pauseAfterHours, hoursOfRest FROM logs l1 INNER JOIN intervalData ON l1.userID = intervalData.userID
WHERE (status = '0' OR status ='5') AND endDate IS NULL AND timeEnd = '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, UTC_TIMESTAMP) >= (pauseAfterHours * 60) 
AND hoursOfRest * 60 >= (SELECT IFNULL(SUM(TIMESTAMPDIFF(MINUTE, start, end)),0) as breakCredit FROM projectBookingData WHERE timestampID = l1.indexIM AND bookingType = 'break' AND start < UTC_TIMESTAMP)";
$result = $conn->query($sql);

while ($result && ($row = $result->fetch_assoc())) {
    $indexIM = $row['indexIM'];
    $break_begin = getCurrentTimestamp();
    $break_end = carryOverAdder_Minutes($break_begin, $row['hoursOfRest'] * 60);
    $conn->query("INSERT INTO projectBookingData (start, end, bookingType, infoText, timestampID) VALUES ('$break_begin', '$break_end', 'break', 'Autocorrect: Lunchbreak', $indexIM)");
    //TODO: Handle overlapped bookings
}

echo $conn->error;
?>