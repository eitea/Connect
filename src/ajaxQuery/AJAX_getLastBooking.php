<?php

require dirname(__DIR__) . "/connection.php";

if (isset($_GET['userID'])) {
    $u = intval($_GET['userID']);
} else {
    $u = 0;
}


$result = $conn->query("SELECT end, timeToUTC FROM projectBookingData, logs
WHERE logs.indexIM = projectBookingData.timestampID AND logs.userID = $u AND DATE(end) = DATE(UTC_TIMESTAMP) ORDER BY end DESC");
if ($result && ($row = $result->fetch_assoc())) {
    $date = new DateTime($row['end']);
    $date->add(new DateInterval("PT" . $row['timeToUTC'] . "H"));
    echo substr($date->format('Y-m-d H:i:s'), 0, 16);
} else {
    $t = localtime(time(), true);
    echo $t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"] + 1)) . "-" . sprintf("%02d", $t["tm_mday"]) . " " . sprintf("%02d", $t["tm_hour"]) . ":" . sprintf("%02d", $t["tm_min"]);
}
?>
