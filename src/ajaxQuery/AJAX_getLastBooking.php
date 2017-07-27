<?php
require dirname(__DIR__)."/connection.php";

if(isset($_GET['userID'])){
  $u = intval($_GET['userID']);
} else {
  $u = 0;
}

$result = $conn->query("SELECT end, timeToUTC FROM projectBookingData, logs
WHERE logs.indexIM = projectBookingData.timestampID AND logs.userID = $u ORDER BY end DESC");
if($result && ($row = $result->fetch_assoc())){
  echo substr(carryOverAdder_Hours($row['end'], $row['timeToUTC']), 11, 5);
} else {
  echo '04:44';
}

function carryOverAdder_Hours($a, $b){
  if($a == '0000-00-00 00:00:00'){
    return $a;
  }
  $date = new DateTime($a);
  if($b < 0){
    $b *= -1;
    $date->sub(new DateInterval("PT".$b."H"));
  } else {
    $date->add(new DateInterval("PT".$b."H"));
  }
  return $date->format('Y-m-d H:i:s');
}
?>
