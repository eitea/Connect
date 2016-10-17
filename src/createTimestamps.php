
<?php

function checkIn($userID, $status) {
  require 'connection.php';
  $timeIsLikeToday = substr(getCurrentTimestamp(), 0, 10) ." %";
  $timeToUTC =  $_SESSION['timeToUTC'];

  $sql = "SELECT * FROM $logTable WHERE userID = $userID
   AND status = '$status'
   AND time LIKE '$timeIsLikeToday'";

   $result = mysqli_query($conn, $sql);
   if($result && $result->num_rows > 0){
     $row = $result->fetch_assoc();
     $diff = timeDiff_Hours($row['timeEnd'], getCurrentTimestamp());
     if($diff <= 0){
       $diff = 0;
     }
     $sql = "UPDATE $logTable SET timeEnd = '0000-00-00 00:00:00', breakCredit = (breakCredit + $diff) WHERE indexIM =". $row['indexIM'];
      $conn->query($sql);
      echo mysqli_error($conn);
   } else {
     $sql = "SELECT * FROM $bookingTable WHERE userID = $userID";
     $result = $conn->query($sql);
     $row=$result->fetch_assoc();
     $expectedHours = $row[strtolower(date('D', strtotime(getCurrentTimestamp())))];
     $sql = "INSERT INTO  $logTable (time, userID, status, timeToUTC, expectedHours) VALUES (UTC_TIMESTAMP, $userID, '$status', $timeToUTC, $expectedHours);";
     $conn->query($sql);
   }
}

function checkOut($userID, $status) {
  require 'connection.php';
  $query = "SELECT * FROM $logTable WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID AND status = '$status' ";
  $result= mysqli_query($conn, $query);
  $row = $result->fetch_assoc();
  $indexIM = $row['indexIM'];
  $sql = "UPDATE $logTable SET timeEND = UTC_TIMESTAMP WHERE indexIM = $indexIM;";
  $conn->query($sql);
}

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
