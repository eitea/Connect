<?php
/* for the good of all of us, write down all callers
getTimestamps.php
timeCalcTable.php
*/
class Interval_Calculator{
  public $days = 0;

  private $from = 0;
  private $to = 0;
  private $id = 0;

  public $monthly_saldo = array();
  public $overTimeLump_single = array();
  public $monthly_correctionHours = array();

  public $dayOfWeek = array();
  public $daysAsNumber = array();

  public $date = array();
  public $start = array();
  public $end = array();
  public $timeToUTC = array();
  public $indecesIM = array();

  public $lunchTime = array();
  public $shouldTime = array();
  public $absolvedTime = array();
  public $activity = array();

  public $beginDate = '';
  public $exitDate = "";

  public function __construct($from, $to, $userid){
    $this->from = substr($from, 0, 10).' 12:00:00';
    $this->to = substr($to, 0, 10).' 12:00:00';
    $this->id = $userid;
    $this->days = (timeDiff_Hours($from, $to) / 24)+1;
    if($this->days > 1){
      $this->calculateValues();
    }
  }

  public function calculateValues(){
    $i = $this->from;
    $id = $this->id;

    require "connection.php";
    $result = $conn->query("SELECT beginningDate, exitDate FROM $userTable WHERE id = $id");
    if($result && ($row = $result->fetch_assoc())){
      $this->beginDate = $beginDate = substr($row['beginningDate'],0,11).'00:00:00';
      //PHP funfact #038: 32Bit servers running php cannot calculate dates past 2038 (not enough bits).
      $this->exitDate = $exitDate =  ($row['exitDate'] == '0000-00-00 00:00:00') ? '2037-12-30 23:00:00' : $row['exitDate'];
    } else {
      return "Invalid userID";
    }

    $count = $current_saldo_month = 0;
    for($j = 0; $j < $this->days; $j++){ //for each day of the month
      $this->dayOfWeek[] = strtolower(date('D', strtotime($i)));
      $this->date[] = substr($i, 0, 10);
      //get the expectedHours
      $sql = "SELECT * FROM $intervalTable WHERE userID = $id AND ((DATE(startDate) <= DATE('$i') AND endDate IS NULL) OR (endDate IS NOT NULL AND DATE(startDate) <= DATE('$i') AND DATE('$i') < DATE(endDate)))";
      $result = $conn->query($sql);
      if($result && $result->num_rows == 1){
        $interval_row = $result->fetch_assoc();
        $this->shouldTime[] = $interval_row[strtolower(date('D', strtotime($i)))];
      } else { //no interval
        $interval_row['overTimeLump'] = 0;
        $this->shouldTime[] = 0;
      }

      $sql = "SELECT $logTable.* FROM $logTable WHERE userID = $id AND DATE(time) >= DATE('$beginDate') AND DATE(time) <= DATE('$exitDate') AND DATE_ADD(time, INTERVAL timeToUTC  hour) LIKE'". substr($i, 0, 10) ." %' ";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0){
        $row = $result->fetch_assoc();
        $this->start[] = $row['time'];
        $this->end[] = $row['timeEnd'];
        $this->activity[] = $row['status'];
        $this->timeToUTC[] = $row['timeToUTC'];
        $this->indecesIM[] = $row['indexIM'];
        $this->absolvedTime[] = ($row['timeEnd'] == '0000-00-00 00:00:00') ? timeDiff_Hours($row['time'], getCurrentTimestamp()) : timeDiff_Hours($row['time'], $row['timeEnd']);
        $break_hours = 0;
        $result_break = $conn->query("SELECT TIMESTAMPDIFF(MINUTE, start, end) as breakCredit FROM projectBookingData where bookingType = 'break' AND timestampID = ".$row['indexIM']);
        while($result_break && ($row_break = $result_break->fetch_assoc())) $break_hours += $row_break['breakCredit'] / 60;
        $this->lunchTime[] = $break_hours;
      } else {
        $this->start[] = false;
        $this->end[] = false;
        $this->absolvedTime[] = 0;
        $this->activity[] = '-1';
        $this->lunchTime[] = 0;
        $this->timeToUTC[] = 0;
        $this->indecesIM[] = 0;
      }
      if(isHoliday($i)){
        $this->shouldTime[$count] = 0;
      }
      if($this->absolvedTime[$count] == 0 && timeDiff_Hours($i, substr(getCurrentTimestamp(), 0, 10).' 12:00:00') < 0 ){
        $this->shouldTime[$count] = 0;
      }
      if(timeDiff_Hours($i, substr($exitDate, 0, 10).' 12:00:00') < 0 ){ //past entry dates
        $this->shouldTime[$count] = 0;
      }

      //mixed Timestamps
      if($this->activity[$count] == 5){
        $mixed_result = $conn->query("SELECT * FROM mixedInfoData WHERE status != 6 AND timestampID = ".$this->indecesIM[$count]);
        if($mixed_result && ($mixed_row = $mixed_result->fetch_assoc())){
          $mixed_absolved = timeDiff_Hours($mixed_row['timeStart'], $mixed_row['timeEnd']);
          //in case of splitted break, we only want to add what's left of mixed_absolved to the end of the day.
          $splits_result = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, start, end)) AS split_absolved FROM projectBookingData WHERE bookingType = 'mixed' AND timestampID = ".$this->indecesIM[$count]);
          if($splits_result && ($splits_row = $splits_result->fetch_assoc())){
            $mixed_absolved -= $splits_row['split_absolved'];
          }
          //if hours are missing (any breaks will cause a minus)
          if($mixed_absolved > 0 && $this->absolvedTime[$count] < $this->shouldTime[$count]){
            $this->absolvedTime[$count] += $mixed_absolved; //fill up
            if($this->absolvedTime[$count] > $this->shouldTime[$count]){ //if too much: reduce
              $this->absolvedTime[$count] = $this->shouldTime[$count];
            }
          }
        }
      }
      //ZA
      if($this->activity[$count] == 6){
        $this->absolvedTime[$count] = 0;
      }

      //EOM calculations
      $current_saldo_month += $this->absolvedTime[$count] - $this->lunchTime[$count] - $this->shouldTime[$count];
      if(substr($i, 0, 7) != substr(carryOverAdder_Hours($i, 24), 0, 7)){
        //clean values
        $this->monthly_saldo[] = $current_saldo_month;
        $this->overTimeLump_single[] = $interval_row['overTimeLump'];
        //correctionHours
        $result = $conn->query("SELECT * FROM $correctionTable WHERE userID = $id AND cType = 'log' AND DATE(createdOn) >= DATE('".substr($i,0,7)."-01') AND DATE('$i') > DATE(createdOn)");
        $current_corrections = 0;
        while($result && ($row = $result->fetch_assoc())){
          $current_corrections += $row['hours'] * intval($row['addOrSub']);
        }
        $this->monthly_correctionHours[] = $current_corrections;
        $current_saldo_month = 0;
      }
      $i = carryOverAdder_Hours($i, 24);
      $count++;
    } //endfor;
    $this->daysAsNumber[] = $count;
  }

  private function timeDiff_Hours($from, $to) {
    $timeEnd = strtotime($to) / 3600;
    $timeBegin = strtotime($from) /3600;
    return $timeEnd - $timeBegin;
  }

  //$a = timestamp in form of Y-m-d H:i:s, $b = amount of hours;
  private function carryOverAdder_Hours($a, $b) {
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
