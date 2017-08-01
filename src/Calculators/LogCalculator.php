<?php
class LogCalculator{
  public $overTimeAdditive = 0;
  public $vacationDays = 0; //still available vacation days (considers already used vacation)

  public $expectedHours = 0;
  public $absolvedHours = 0;
  public $breakCreditHours = 0;
  public $vacationHours = 0;
  public $specialLeaveHours = 0;
  public $sickHours = 0;
  public $educationHours = 0;

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
    require dirname(__DIR__)."/connection.php";
    $curID = $this->id;
    $is_corrected = 0;
    $result_I = $conn->query("SELECT $intervalTable.*, $userTable.exitDate, $userTable.beginningDate FROM $intervalTable INNER JOIN $userTable ON userID = $userTable.id  WHERE userID = $curID");
    while($result_I && ($iRow = $result_I->fetch_assoc())){ //foreach interval
      $this->beginDate = $iRow['beginningDate'];
      $this->exitDate = $iRow['exitDate'];

      $i = $iRow['startDate'];
      $now = $j = $iRow['endDate'];

       //today or endof int or exit
      if(empty($j) && $iRow['exitDate'] == '0000-00-00 00:00:00' ){
        $now = getCurrentTimestamp();
        $j = carryOverAdder_Hours($now, 24);
      } elseif(empty($j)){
        $j = carryOverAdder_Hours($iRow['exitDate'],24); //include
      }

      $diff = timeDiff_Hours($i, $j);
      $this->vacationDays += ($iRow['vacPerYear']/365) * ($diff / 24);
      while(substr($i,0, 10) != substr($j,0,10) && substr($i,0, 4) <= substr($j,0, 4)){ //days
        $expectedHours = $iRow[strtolower(date('D', strtotime($i)))];
        if(isHoliday($i)){
          $expectedHours = 0;
        }
        $result = $conn->query("SELECT * FROM $logTable WHERE userID = $curID AND time LIKE'". substr($i, 0, 10) ." %'");
        if($result && $result->num_rows > 0 && ($row = $result->fetch_assoc())){
          if($row['timeEnd'] == '0000-00-00 00:00:00'){
            //adjust expected Hours to current time
            $timeEnd = $now;
            if(timeDiff_Hours($row['time'], $timeEnd) >= $expectedHours){
              $this->expectedHours += $expectedHours;
            } else {
              $this->expectedHours += timeDiff_Hours($row['time'], $timeEnd);
            }
          } else {
            $timeEnd = $row['timeEnd'];
            $this->expectedHours += $expectedHours;
          }

          $break_hours = 0;
          $result_break = $conn->query("SELECT TIMESTAMPDIFF(MINUTE, start, end) as breakCredit FROM projectBookingData where bookingType = 'break' AND timestampID = ".$row['indexIM']);
          while($result_break && ($row_break = $result_break->fetch_assoc())) $break_hours += $row_break['breakCredit'] / 60;
          $absolved_today = timeDiff_Hours($row['time'], $timeEnd);
          switch($row['status']){
            case 0:
            $this->absolvedHours += $absolved_today;
            $this->breakCreditHours += $break_hours;
            break;
            case 1:
            $this->vacationHours += $expectedHours;
            $this->vacationDays--;
            break;
            case 2:
            $this->specialLeaveHours += $absolved_today;
            break;
            case 3:
            $this->sickHours += $expectedHours;
            break;
            case 4:
            $this->educationHours += $absolved_today;
            break;
            case 5: //Mixed
            $mixed_diff = 0;
            $mixed_absolvedHours = $absolved_today;
            //select all mixed bookings in this timestamp, and add hours accordingly
            $mixed_result = $conn->query("SELECT mixedStatus, start, end FROM projectBookingData WHERE timestampID = ".$row['indexIM']." AND bookingType='mixed'");
            while($mixed_result && ($mixed_row = $mixed_result->fetch_assoc())){
              $mixed_diff = timeDiff_Hours($mixed_row['start'], $mixed_row['end']);
              switch($mixed_row['mixedStatus']){
                case 1:
                $this->vacationHours += $mixed_diff;
                $mixed_absolvedHours -= $mixed_diff;
                break;
                case 2:
                $this->specialLeaveHours += $mixed_diff;
                $mixed_absolvedHours -= $mixed_diff;
                break;
                case 3:
                $this->sickHours += $mixed_diff;
                $mixed_absolvedHours -= $mixed_diff;
                break;
                case 4:
                $this->educationHours += $mixed_diff;
                $mixed_absolvedHours -= $mixed_diff;
                break;
                case 6:
                //do nothing
              }
            }
            //cancel out possible minus, if not ZA
            $mixed_result = $conn->query("SELECT * FROM mixedInfoData WHERE status != 6 AND timestampID = ".$row['indexIM']);
            if($mixed_result && ($mixed_row = $mixed_result->fetch_assoc())){
              $mixed_absolved = timeDiff_Hours($mixed_row['timeStart'], $mixed_row['timeEnd']);
              //if hours WITHOUT breaks are missing
              if($mixed_absolved > 0 && $absolved_today < $expectedHours){
                $mixed_absolvedHours += $mixed_absolved;
                if($mixed_absolvedHours > $expectedHours){ //if too much: reduce
                  $mixed_absolvedHours = $expectedHours;
                }
              }
            }
            $this->absolvedHours += $mixed_absolvedHours;
            $this->breakCreditHours += $break_hours;
            break;
            case 6: //ZA do nothin
          } //END SWITCH
        } else {
          $this->expectedHours += $expectedHours;
        }

        //correction hours
        if(!$is_corrected){
          $monthly_corrections = 0;
          $result = $conn->query("SELECT * FROM $correctionTable WHERE userID = $curID AND LAST_DAY('$i') >= DATE(createdOn) AND DATE('".substr_replace($i, '01',8,2)."') <= DATE(createdOn)");
          while($result && ($row = $result->fetch_assoc())){
            if($row['cType'] == 'log'){
              $monthly_corrections += $row['hours'] * intval($row['addOrSub']);
            } elseif($row['cType'] == 'vac'){
              $this->vacationDays += $row['hours'] * intval($row['addOrSub']);
            }
          }
          $this->correctionHours += $monthly_corrections;
          $is_corrected = true;
        }

        //EOM Calculations
        if(substr($i, 0, 7) != substr(carryOverAdder_Hours($i, 24), 0, 7)){
          $this->saldo = $this->absolvedHours - $this->expectedHours - $this->breakCreditHours + $this->vacationHours + $this->educationHours + $this->specialLeaveHours + $this->sickHours + $this->correctionHours - $this->overTimeAdditive;
          if($this->saldo > 0){
            if($this->saldo < $iRow['overTimeLump']){
              $this->overTimeAdditive += $this->saldo;
            } else {
              $this->overTimeAdditive += $iRow['overTimeLump'];
            }
          }
          $is_corrected = false;
        }

        $i = carryOverAdder_Hours($i, 24);
      } //end foreach day in intveral
    } //end foreach interval
    $this->saldo = $this->absolvedHours - $this->expectedHours - $this->breakCreditHours + $this->vacationHours + $this->educationHours + $this->specialLeaveHours + $this->sickHours + $this->correctionHours - $this->overTimeAdditive;
  }

  private function timeDiff_Hours($from, $to){
    $timeEnd = strtotime($to) / 3600;
    $timeBegin = strtotime($from) /3600;
    return $timeEnd - $timeBegin;
  }

  private function getCurrentTimestamp(){
    ini_set('date.timezone', 'UTC');
    $t = localtime(time(), true);
    return ($t["tm_year"] + 1900 . "-" . sprintf("%02d", ($t["tm_mon"]+1)) . "-". sprintf("%02d", $t["tm_mday"]) . " " . sprintf("%02d", $t["tm_hour"]) . ":" . sprintf("%02d", $t["tm_min"]) . ":" . sprintf("%02d", $t["tm_sec"]));
  }

  private function carryOverAdder_Hours($a, $b){
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

  private function isHoliday($ts){
    require "connection.php";
    $sql = "SELECT * FROM $holidayTable WHERE begin LIKE '". substr($ts, 0, 10)."%' AND name LIKE '% (§)'";
    $result = mysqli_query($conn, $sql);
    return($result && $result->num_rows>0);
  }
}
?>
