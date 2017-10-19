<?php
/* for the good of all of us, write down all callers
* getTimestamps.php
* timeCalcTable.php
* 
* sendMailReport - utilities.php
* admin_saldoview
* tableSummary
*/
require_once dirname(__DIR__). "/utilities.php";

class Interval_Calculator{
  private $id = 0;
  private $startCalculation = '';
  private $endCalculation = '';

  public $beginDate = '';
  public $exitDate = '';
  public $days = 0;
  public $availableVacation = 0;

  public $date = array();
  public $dayOfWeek = array();
  public $start = array();
  public $end = array();
  public $timeToUTC = array();
  public $indecesIM = array();

  public $lunchTime = array();
  public $shouldTime = array();
  public $activity = array();

  public $absolvedTime = array();
  public $endOfMonth = array(); //[date => [overtime, correction]]
  public $saldo = 0;
  
  public $prev_saldo = 0;
  
  //PHP funfact: 32Bit servers running php cannot calculate dates past 2038 (not enough bits).
  public function __construct($userid, $start = 0, $end = 0){
    $this->id = $userid;
    include dirname(__DIR__). "/connection.php";
    $result = $conn->query("SELECT beginningDate, exitDate FROM UserData WHERE id = $userid");
    if($result && ($row = $result->fetch_assoc())){
      $this->beginDate = substr($row['beginningDate'],0,10).' 12:00:00';
      $this->exitDate = ($row['exitDate'] == '0000-00-00 00:00:00') ? getCurrentTimestamp() : $row['exitDate'];
      $this->exitDate = substr($this->exitDate,0,10).' 12:00:00';
      $this->endCalculation = $this->exitDate;
      $this->startCalculation = $this->beginDate;
    } else {
      return "Invalid userID"; //strrrike
    }

    if($end) $this->endCalculation = substr($end, 0, 10).' 12:00:00';
    if($start) $this->startCalculation = substr($start, 0, 10).' 12:00:00';

    if(timeDiff_hours($this->startCalculation, $this->endCalculation) > 0){
      $this->calculateValues();
    } else {
      echo "ERROR";
    }
  }

  public function calculateValues(){
    include dirname(__DIR__). "/connection.php";
    $id = $this->id;
    $i = $this->beginDate;

    while(timeDiff_Hours($i, $this->endCalculation) >= 0){
      $this->dayOfWeek[$this->days] = strtolower(date('D', strtotime($i)));
      $this->date[$this->days] = substr($i, 0, 10);

      //get interval 
      $sql = "SELECT * FROM $intervalTable WHERE userID = $id AND (endDate IS NULL AND (DATE(startDate) <= DATE('$i')) OR (endDate IS NOT NULL AND DATE(startDate) <= DATE('$i') AND DATE('$i') < DATE(endDate)))";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0){
        $interval_row = $result->fetch_assoc();
        $this->shouldTime[$this->days] = $interval_row[strtolower(date('D', strtotime($i)))];
        $this->availableVacation += $interval_row['vacPerYear'] / 365;
      } else {
        echo $conn->error;
        $interval_row['overTimeLump'] = 0;
        $this->shouldTime[$this->days] = 0;
      }

      $sql = "SELECT $logTable.* FROM $logTable WHERE userID = $id AND DATE_ADD(time, INTERVAL timeToUTC  hour) LIKE '". substr($i, 0, 10) ." %' ";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0){
        $row = $result->fetch_assoc();
        $this->start[$this->days] = $row['time'];
        $this->end[$this->days] = $row['timeEnd'];
        $this->activity[$this->days] = $row['status'];
        $this->timeToUTC[$this->days] = $row['timeToUTC'];
        $this->indecesIM[$this->days] = $row['indexIM'];
        $this->absolvedTime[$this->days] = ($row['timeEnd'] == '0000-00-00 00:00:00') ? timeDiff_Hours($row['time'], getCurrentTimestamp()) : timeDiff_Hours($row['time'], $row['timeEnd']);
        $row_break['breakCredit'] = 0;
        $result_break = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, start, end)) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = ".$row['indexIM']);
        if($result_break && $result_break->num_rows > 0) $row_break = $result_break->fetch_assoc();
        $this->lunchTime[$this->days] = $row_break['breakCredit'] / 60;
        if($row['status'] == 1) $this->availableVacation--;
      } else {
        $this->start[$this->days] = false;
        $this->end[$this->days] = false;
        $this->absolvedTime[$this->days] = 0;
        $this->activity[$this->days] = '-1';
        $this->lunchTime[$this->days] = 0;
        $this->timeToUTC[$this->days] = 0;
        $this->indecesIM[$this->days] = 0;
      }
      if(isHoliday($i)){
        $this->shouldTime[$this->days] = 0;
      }
      if($this->absolvedTime[$this->days] == 0 && timeDiff_Hours($i, substr(getCurrentTimestamp(), 0, 10).' 12:00:00') < 0 ){
        $this->shouldTime[$this->days] = 0;
      }
      if(timeDiff_Hours($i, $this->exitDate) < 0){
        $this->shouldTime[$this->days] = 0;
        $this->absolvedTime[$this->days] = 0;
      }
      //mixed
      if($this->activity[$this->days] == 5){
        $absolved_bonus = 0;
        //in case of splitted break: remove it from bonus (just like lunchbreak removes from abolved hours)
        $splits_result = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, start, end)) AS split_absolved FROM projectBookingData WHERE bookingType = 'mixed' AND timestampID = ".$this->indecesIM[$this->days]);
        if($splits_result && ($splits_row = $splits_result->fetch_assoc())){
          $absolved_bonus -= $splits_row['split_absolved'];
        }
        $mixed_result = $conn->query("SELECT * FROM mixedInfoData WHERE status != 6 AND timestampID = ".$this->indecesIM[$this->days]);
        if($mixed_result && ($mixed_row = $mixed_result->fetch_assoc())){
          $absolved_bonus += timeDiff_Hours($mixed_row['timeStart'], $mixed_row['timeEnd']);
        }
        if($absolved_bonus > 0 && $mixed_row['status'] == 1){
          //do I have to check here if it was in core time or not?
          $this->absolvedTime[$this->days] += $absolved_bonus;
          $this->availableVacation -= 0.5;
        } else {
          //if hours are missing (any breaks will cause a minus)
          if($absolved_bonus > 0 && $this->absolvedTime[$this->days] < $this->shouldTime[$this->days]){
            $this->absolvedTime[$this->days] += $absolved_bonus; //fill up
            if($this->absolvedTime[$this->days] > $this->shouldTime[$this->days]){ //if too much: reduce
              $this->absolvedTime[$this->days] = $this->shouldTime[$this->days];
            }
          }
        }
      }
      //ZA
      if($this->activity[$this->days] == 6){
        $this->absolvedTime[$this->days] = 0;
      }

      $this->saldo = $this->saldo + $this->absolvedTime[$this->days] - $this->lunchTime[$this->days] - $this->shouldTime[$this->days] ;
      //EOM calculations
      if(substr($i, 0, 7) != substr(carryOverAdder_Hours($i, 24), 0, 7)){
        if($this->saldo > 0){
          $this->endOfMonth[$this->date[$this->days]]['overTimeLump'] = $interval_row['overTimeLump'];
          if(($this->saldo - $interval_row['overTimeLump']) < 0 ) {
            $this->endOfMonth[$this->date[$this->days]]['overTimeLump'] = $this->saldo ;
            $this->saldo = 0;
          } else {
            $this->saldo -= $interval_row['overTimeLump'];
          }
        } else {
          $this->endOfMonth[$this->date[$this->days]]['overTimeLump'] = 0;
        }

        $result = $conn->query("SELECT * FROM $correctionTable WHERE userID = $id AND cType = 'log' AND DATE('$i') >= DATE(createdOn) AND DATE(createdOn) >= DATE('".substr($i,0,7)."-01')");
        $current_corrections = 0;
        while($result && ($row = $result->fetch_assoc())){
          $current_corrections += $row['hours'] * intval($row['addOrSub']);
        }
        $this->endOfMonth[$this->date[$this->days]]['correction'] = $current_corrections;
        $this->saldo += $current_corrections;
      }
      
      if(timeDiff_Hours($i, $this->startCalculation) > 0){
        $this->prev_saldo = $this->saldo;
      } else {
        $this->days++;
      }
      $i = carryOverAdder_Hours($i, 24);
    } //endfor;
  }
}
?>
