
<?php
//$to - $from in Hours.
function timeDiff_Hours($from, $to) {
  $timeEnd = strtotime($to) / 3600;
  $timeBegin = strtotime($from) /3600;
  return $timeEnd - $timeBegin;
}

function getCurrentTimestamp() {
  ini_set('date.timezone', 'UTC');
  $t = localtime(time(), true);
  return ($t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]) . " " . sprintf("%02d", $t["tm_hour"]) . ":" . sprintf("%02d", $t["tm_min"]) . ":" . sprintf("%02d", $t["tm_sec"]));
}

function carryOverAdder_Hours($a, $b) {
  if($a == '0000-00-00 00:00:00'){
    return $a;
  }
  $date = new DateTime($a);
  if($b<0){
    $b *= -1;
    $date->sub(new DateInterval("PT".$b."H"));
  } else {
    $date->add(new DateInterval("PT".$b."H"));
}
  return $date->format('Y-m-d H:i:s');
}

function carryOverAdder_Minutes($a, $b) {
  if($a == '0000-00-00 00:00:00'){
    return $a;
  }
  $date = new DateTime($a);
  if($b<0){
    $b *= -1;
    $date->sub(new DateInterval("PT".$b."M"));
  } else {
    $date->add(new DateInterval("PT".$b."M"));
}
  return $date->format('Y-m-d H:i:s');
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);

  return $data;
}

function isHoliday($ts){
  require "connection.php";
  $sql = "SELECT * FROM $holidayTable WHERE begin LIKE '". substr($ts, 0, 10)."%' AND name LIKE '% (§)'";
  $result = mysqli_query($conn, $sql);
  return($result && $result->num_rows>0);
}

function test_Date($date){
  $dt = DateTime::createFromFormat("Y-m-d H:i:s", $date);
  return $dt !== false && !array_sum($dt->getLastErrors());
}


/*
echo $test=strtotime('2016-02-3 05:44:21');
echo "<br>";
echo date('Y-m-d H:i:s', $test);
*/
