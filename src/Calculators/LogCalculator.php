<?php
class LogCalculator{
  public $absolvedHours = 0;
  public $expectedHours = 0;
  public $breakCreditHours = 0;
  public $vacationHours = 0;
  public $specialLeaveHours = 0;
  public $sickHours = 0;
  public $correctionHours = 0;
  public $overTimeAdditive = 0;
  public $saldo = 0;

  public $usedVacationDays = 0;
  public $beginDate = "";
  public $exitDate = "";

  private $id;

  public function __construct($userid){
    $this->id = $userid;
    $this->calculateValues();
  }

  public function calculateValues(){
    require "connection.php";
    $curID = $this->id;
    $sql = "SELECT * FROM $userTable INNER JOIN $vacationTable ON $vacationTable.userID = $userTable.id INNER JOIN $bookingTable ON $bookingTable.userID = $userTable.id WHERE $userTable.id = $curID";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      $userRow = $result->fetch_assoc();
    } else {
      die(mysqli_error("No Such User."));
    }

    $this->overTimeAdditive = $userRow['overTimeLump'] * ceil(timeDiff_Hours(substr($userRow['beginningDate'],0,11).'05:00:00', substr(getCurrentTimestamp(),0,11).'05:00:00')/(24*30));
    $beginDate = $userRow['beginningDate'];
    $exitDate = ($userRow['exitDate'] == '0000-00-00 00:00:00') ? '5000-12-30 23:59:59' : $userRow['exitDate'];

    $sql = "SELECT * FROM $logTable WHERE time > '$beginDate' AND time < '$exitDate' AND userID = $curID";
    $result = $conn->query($sql);
    while($result && ($row = $result->fetch_assoc())){
      if($row['timeEnd'] == '0000-00-00 00:00:00'){
        //open timestamp lowers expected Hours according to how long user has been checked in
        $timeEnd = getCurrentTimestamp();
        if(timeDiff_Hours($row['time'], $timeEnd) >= $row['expectedHours']){//user has been checked in longer than his expected Hours
          $this->expectedHours += $row['expectedHours'];
        } else {
          $this->expectedHours += timeDiff_Hours($row['time'], $timeEnd); //alter expected hours to match time he has been here already
        }
      } else {
        $timeEnd = $row['timeEnd'];
        $this->expectedHours += $row['expectedHours'];
      }
      switch($row['status']){
        case 0:
        $this->absolvedHours += timeDiff_Hours($row['time'], $timeEnd);
        $this->breakCreditHours += $row['breakCredit'];
        break;
        case 1:
        $this->vacationHours += timeDiff_Hours($row['time'], $timeEnd);
        $this->usedVacationDays++;
        break;
        case 2:
        $this->specialLeaveHours += timeDiff_Hours($row['time'], $timeEnd);
        break;
        case 3:
        $this->sickHours += timeDiff_Hours($row['time'], $timeEnd);
      }
    }

    //correction Hours:
    $result = $conn->query("SELECT * FROM $correctionTable WHERE userID = $curID");
    while($result && ($row = $result->fetch_assoc())){
      $this->correctionHours += $row['hours'] * intval($row['addOrSub']);
    }

    //extra expectedHours from unlogs:
    $sql = "SELECT * FROM $negative_logTable WHERE userID = $curID AND time > '$beginDate' AND time < '$exitDate'";
    $result = $conn->query($sql);
    while($result && ($row = $result->fetch_assoc())){
      if(!isHoliday($row['time'])){
        $this->expectedHours += $row[strtolower(date('D', strtotime($row['time'])))];
      }
    }

    $this->saldo = $this->absolvedHours - $this->expectedHours - $this->breakCreditHours + $this->vacationHours + $this->specialLeaveHours + $this->sickHours - $this->overTimeAdditive + $this->correctionHours;
  }

  private function timeDiff_Hours($from, $to) {
    $timeEnd = strtotime($to) / 3600;
    $timeBegin = strtotime($from) /3600;
    return $timeEnd - $timeBegin;
  }

  private function getCurrentTimestamp() {
    ini_set('date.timezone', 'UTC');
    $t = localtime(time(), true);
    return ($t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]) . " " . sprintf("%02d", $t["tm_hour"]) . ":" . sprintf("%02d", $t["tm_min"]) . ":" . sprintf("%02d", $t["tm_sec"]));
  }

  private function carryOverAdder_Hours($a, $b) {
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

  private function carryOverAdder_Minutes($a, $b) {
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

  private function isHoliday($ts){
    require "connection.php";
    $sql = "SELECT * FROM $holidayTable WHERE begin LIKE '". substr($ts, 0, 10)."%' AND name LIKE '% (§)'";
    $result = mysqli_query($conn, $sql);
    return($result && $result->num_rows>0);
  }
}
?>
