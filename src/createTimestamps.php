
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

function isHoliday($ts){
  require "connection.php";
  $sql = "SELECT * FROM $holidayTable WHERE begin LIKE '". substr($ts, 0, 10)."%' AND name LIKE '% (§)'";
  $result = mysqli_query($conn, $sql);
  return($result && $result->num_rows>0);
}

/*
function isHoliday($ts){
  require "connection.php";
  $sql = "SELECT * FROM $holidayTable WHERE begin LIKE '". substr($ts, 0, 10)."%'";
  $result = mysqli_query($conn, $sql);
  return($result && $result->num_rows>0);
}
*/

function test_input($data) {
  require "connection.php";
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  $data = $conn->real_escape_string($data);
  return $data;
}

function test_Date($date){
  $dt = DateTime::createFromFormat("Y-m-d H:i:s", $date);
  return $dt && $dt->format("Y-m-d H:i:s") === $date;
}

//$hours is a float
function displayAsHoursMins($hour){
  $hours = $hour;
  $s = '';
  if($hours < 0){
    $s = '-';
    $hours = $hours * -1;
  }
  if($hours >= 1){
    $s .= intval($hours) . 'h ';
    $hours = $hours - intval($hours);
  }
  $s .= intval($hours * 60) .'min';
  return $s;
}

function redirect($url){
  if (!headers_sent()) {
    header('Location: '.$url);
    exit;
  } else {
    echo '<script type="text/javascript">';
    echo 'window.location.href="'.$url.'";';
    echo '</script>';
    echo '<noscript>';
    echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
    echo '</noscript>'; exit;
  }
}

/*see if password matches policy, returns true or false.
* writes error message in optional output
* low - at least x characters (x from policy table)
* medium - at least one capital letter and one number
* high - at least one special character
*/
function match_passwordpolicy($p, &$out = ''){
  require "connection.php";
  $result = $conn->query("SELECT * FROM $policyTable");
  $row = $result->fetch_assoc();

  if(strlen($p) < $row['passwordLength']){
    $out = "Password must be at least " . $row['passwordLength'] . " Characters long.";
    return false;
  }
  if($row['complexity'] === '0'){ //whatever
    return true;
  } elseif($row['complexity'] === '1'){
    if(!preg_match('/[A-Z]/', $p) || !preg_match('/[0-9]/', $p)){
      $out = "Password must contain at least one captial letter and one number";
      return false;
    }
  } elseif($row['complexity'] === '2'){
    if(!preg_match('/[A-Z]/', $p) || !preg_match('/[0-9]/', $p) || !preg_match('/[~\!@#\$%&\*_\-\+\.\?]/', $p)){
      $out = "Password must contain at least one captial letter, one number and one special character (~ ! @ # $ % & * _ - + . ?)";
      return false;
    }
  }
  return true;
}

/*
echo $test=strtotime('2016-02-3 05:44:21');
echo date('Y-m-d H:i:s', $test);
*/
