<?php
function timeDiff_Hours($from, $to) {
  $timeBegin = strtotime($from) /3600;
  $timeEnd = strtotime($to) / 3600;
  return $timeEnd - $timeBegin;
}

function getCurrentTimestamp(){
  ini_set('date.timezone', 'UTC');
  $t = localtime(time(), true);
  return ($t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]) . " " . sprintf("%02d", $t["tm_hour"]) . ":" . sprintf("%02d", $t["tm_min"]) . ":" . sprintf("%02d", $t["tm_sec"]));
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

function carryOverAdder_Minutes($a, $b){
  if($a == '0000-00-00 00:00:00'){
    return $a;
  }
  $date = new DateTime($a);
  if($b < 0){
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
function isHoliday($ts){|
  require "connection.php";
  $sql = "SELECT * FROM $holidayTable WHERE begin LIKE '". substr($ts, 0, 10)."%'";
  $result = mysqli_query($conn, $sql);
  return($result && $result->num_rows>0);
}
*/

function test_input($data){
  $data = preg_replace("~[^A-Za-z0-9\-?!=:.,/@€§$%()+*öäüÖÄÜß_ ]~", "", $data);
  $data = trim($data);
  return $data;
}

function test_Date($date){
  $dt = DateTime::createFromFormat("Y-m-d H:i:s", $date);
  return $dt && $dt->format("Y-m-d H:i:s") === $date;
}

function test_Time($time){
  return preg_match("/^([01][0-9]|2[0-3]):([0-5][0-9])$/", $time);
}

//$hours is a float
function displayAsHoursMins($hour){
  $hours = round($hour, 2); //i know params are passed by value if not specified otherwise, but still.. I got trust issues with this language
  $s = '';
  if($hours < 0){
    $s = '-';
    $hours = $hours * -1;
  }
  if($hours >= 1){
    $s .= intval($hours) . 'h ';
    $hours = $hours - intval($hours);
  }
  $s .= round($hours * 60) .'min';
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

function getNextERP($identifier, $companyID, $offset = 0){
  require "connection.php";
  $offset = 0;
  if(!$companyID){$companyID = $available_companies[1]; }
  $result = $conn->query("SELECT * FROM erpNumbers WHERE companyID = $companyID");
  if($row = $result->fetch_assoc()){
    $offset = $row['erp_'.strtolower($identifier)];
    $offset--;
    if($offset < 0) $offset = 0;
  }
  $vals = array($offset);

  //get all the little shits which contain my shit
  $result = $conn->query("SELECT id_number, history FROM proposals, clientData WHERE clientID = clientData.id AND companyID = $companyID AND (id_number LIKE '$identifier%' OR history LIKE '%$identifier%')");
  while($result && ($row = $result->fetch_assoc())){
    $history = explode(' ', $row['history']);
    $history[] = $row['id_number'];
    foreach($history as $h){
      if(substr($h, 0, strlen($identifier)) == $identifier){
        $vals[] = intval(substr($h, strlen($identifier))); //trim 0s
      }
    }
  }
  return $identifier . sprintf('%0'.(10-strlen($identifier)).'d', max($vals) +1);
}

function randomPassword($length = 8){
  $pool = array('abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '1234567890', '!@#$*+?');
  shuffle($pool);
  $psw = array();
  for($i = 0; $i < $length; $i++){
    $psw[] = $pool[$i % 4][rand(0, strlen($pool[$i % 4]) -1)];
    if($i > 3){
      shuffle($pool);
    }
  }
  return implode($psw);
}

/*
echo $test=strtotime('2016-02-3 05:44:21');
echo date('Y-m-d H:i:s', $test);
*/
