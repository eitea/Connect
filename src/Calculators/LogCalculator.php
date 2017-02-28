<?php
class LogCalculator{
  public $overTimeAdditive = 0;
  public $vacationDays = 0; //can be understood as 'still available vacation days' (considers already used vacation)

  public $vacationHours = 0;
  public $expectedHours = 0;
  public $absolvedHours = 0;
  public $breakCreditHours = 0;
  public $specialLeaveHours = 0;
  public $sickHours = 0;

  public $correctionHours = 0;
  public $saldo = 0;

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

    $result_I = $conn->query("SELECT $intervalTable.*, $userTable.exitDate, $userTable.beginningDate FROM $intervalTable INNER JOIN $userTable ON userID = $userTable.id  WHERE userID = $curID");

    $count = 0; //count the days for the overtimelump
    while($result_I && ($iRow = $result_I->fetch_assoc())){ //foreach interval
      $this->beginDate = $iRow['beginningDate'];
      $this->exitDate = $iRow['exitDate'];

      $i = $iRow['startDate'];
      $j = $iRow['endDate'];

      if(empty($j) && $iRow['exitDate'] == '0000-00-00 00:00:00' ){ //current interval no endDate, user no exit date => calculate until today
        $j = getCurrentTimestamp();
      } elseif(empty($j)){ //current interval and he HAS an exitDate, calculate until the exitDate.
        $j = $iRow['exitDate'];
      }
      $j = carryOverAdder_Hours($j, 24);

      $this->vacationDays = ($iRow['vacPerYear']/365) * (timeDiff_Hours($i, $j) / 24); //accumulated vacation
      while(substr($i,0, 10) != substr($j,0,10) && substr($i,0, 4) <= substr($j,0, 4)) {
        if($count % date('t', strtotime($i)) == 0){ //modulo the amount of days this month has.
          $this->overTimeAdditive += $iRow['overTimeLump'];
        }
        $expectedHours = $iRow[strtolower(date('D', strtotime($i)))];

        if(isHoliday($i)){
          $expectedHours = 0;
        }
        $result = $conn->query("SELECT * FROM $logTable WHERE userID = $curID AND time LIKE'". substr($i, 0, 10) ." %'");
        if($result && $result->num_rows > 0 && ($row = $result->fetch_assoc())){ //user has absolved hours for today (Checkin/Vacation/..)
          if($row['timeEnd'] == '0000-00-00 00:00:00'){
            //open timestamp lowers expected Hours according to how long user has been checked in
            $timeEnd = getCurrentTimestamp();
            if(timeDiff_Hours($row['time'], $timeEnd) >= $expectedHours){ //user has been checked in longer than his expected Hours -> no need to adjust anything
              $this->expectedHours += $expectedHours;
            } else {
              $this->expectedHours += timeDiff_Hours($row['time'], $timeEnd); //else: reduce expected hours to match time he has been here already, so there's no minus he can't keep up to.
            }
          } else {
            $timeEnd = $row['timeEnd'];
            $this->expectedHours += $expectedHours;
          }

          switch($row['status']){
            case 0:
            $this->absolvedHours += timeDiff_Hours($row['time'], $timeEnd);
            $this->breakCreditHours += $row['breakCredit'];
            break;
            case 1:
            $this->vacationHours += timeDiff_Hours($row['time'], $timeEnd);
            $this->vacationDays--;
            break;
            case 2:
            $this->specialLeaveHours += timeDiff_Hours($row['time'], $timeEnd);
            break;
            case 3:
            $this->sickHours += timeDiff_Hours($row['time'], $timeEnd);
          }
        } else { //no log found for today
          $this->expectedHours += $expectedHours;
        }

        $i = carryOverAdder_Hours($i, 24);
        $count++;
      }
    } //end foreach interval

    //correction Hours:
    $result = $conn->query("SELECT * FROM $correctionTable WHERE userID = $curID");
    while($result && ($row = $result->fetch_assoc())){
      if($row['cType'] =='log'){
        $this->correctionHours += $row['hours'] * intval($row['addOrSub']);
      } elseif($row['cType'] == 'vac'){
        $this->vacationDays += $row['hours'] * intval($row['addOrSub']);
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

  private function isHoliday($ts){
    require "connection.php";
    $sql = "SELECT * FROM $holidayTable WHERE begin LIKE '". substr($ts, 0, 10)."%' AND name LIKE '% (§)'";
    $result = mysqli_query($conn, $sql);
    return($result && $result->num_rows>0);
  }
}
?>
