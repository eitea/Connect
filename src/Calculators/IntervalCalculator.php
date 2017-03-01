<?php
/*
* This Calculator is the same as the monthly calculator, it only accepts an interval instead.
*/
class Interval_Calculator{
  public $days = 0;
  public $overTimeLump = 0;
  public $correctionHours = 0;

  private $from = 0;
  private $to = 0;
  private $id = 0;

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

  //month like yyyy-mm-dd hh-mm-ss
  public function __construct($from, $to, $userid){
    $this->from = substr($from, 0, 10).' 12:00:00';
    $this->to = substr($to, 0, 10).' 12:00:00';
    $this->id = $userid;
    $this->days = (timeDiff_Hours($from, $to) / 24) +1; //include the to date.
    $this->calculateValues();
  }

  public function calculateValues(){
    $fromDate = $i = $this->from;
    $id = $this->id;

    require "connection.php";
    $result = $conn->query("SELECT beginningDate, exitDate FROM $userTable WHERE id = $id");
    if($result && ($row = $result->fetch_assoc())){
      $this->beginDate = $beginDate = $row['beginningDate'];
      $exitDate = ($row['exitDate'] == '0000-00-00 00:00:00') ? '5000-12-30 23:59:59' : $row['exitDate'];
    } else { //should never occur
      return "Invalid userID";
    }

    $count = 0;
    $oldMonth = 0;
    for($j = 0; $j < $this->days && $this->days > 0; $j++){ //for each day of the month
      $this->dayOfWeek[] = strtolower(date('D', strtotime($i)));
      $this->date[] = substr($i, 0, 10);
      $currentMonth = substr($i, 0, 7);
      //get the expectedHours from the matching interval
      $sql = "SELECT * FROM $intervalTable WHERE userID = $id AND ( (DATE(startDate) <= DATE('$i') AND endDate IS NULL) OR (endDate IS NOT NULL AND DATE(startDate) <= DATE('$i') AND DATE('$i') < DATE(endDate)) )";
      $result = $conn->query($sql);
      if($result && $result->num_rows == 1){
        $row = $result->fetch_assoc();
        $this->shouldTime[] = $row[strtolower(date('D', strtotime($i)))];
        //add overTimeLump if month changes
        if($oldMonth != $currentMonth){
          $this->overTimeLump += $row['overTimeLump'];
          $oldMonth = $currentMonth;
        }
      } else {
        die("No values available.");
      }

      $sql = "SELECT $logTable.* FROM $logTable WHERE userID = $id AND DATE(time) >= DATE('$beginDate') AND DATE(time) <= DATE('$exitDate') AND time LIKE'". substr($i, 0, 10) ." %' ";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0){ //user has absolved hours for today (Checkin/Vacation/..)
        $row = $result->fetch_assoc();
        $this->start[] = $row['time'];
        $this->end[] = $row['timeEnd'];
        $this->absolvedTime[] = timeDiff_Hours($row['time'], $row['timeEnd']);
        $this->activity[] = $row['status'];
        $this->timeToUTC[] = $row['timeToUTC'];
        $this->indecesIM[] = $row['indexIM'];
        $this->lunchTime[$count] = $row['breakCredit'];
      } else { //user wasnt here today = 0 absolved hours
        $this->start[] = false;
        $this->end[] = false;
        $his->absolvedTime[] = 0;
        $this->activity[] = '-1';
        $this->lunchTime[] = 0;
        $this->timeToUTC[] = 0;
        $this->indecesIM[] = $id.', '.$i;
      }
      //if today is a holiday, poot poot
      if(isHoliday($i)){
        $this->shouldTime[$count] = 0;
      }
      $i = carryOverAdder_Hours($i, 24);
      $count++;
    } //endwhile;
    $this->daysAsNumber[] = $count;

    //correction Hours:
    $result = $conn->query("SELECT * FROM $correctionTable WHERE userID = $id AND cType = 'log' AND DATE('$fromDate') <= DATE(createdOn) AND DATE('$i') >= DATE(createdOn)");
    while($result && ($row = $result->fetch_assoc())){
      $this->correctionHours += $row['hours'] * intval($row['addOrSub']);
    }
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
