<?php
class Monthly_Calculator{
  public $days = 0;
  public $overTimeLump = 0;

  private $month = 0;
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
  public $activity = array();

//ts should be like yyyy-mm-dd hh-mm-ss
  public function __construct($ts, $userid){
    $this->month = $ts;
    $this->id = $userid;
    $this->days = date('t', strtotime($ts));
    $this->calculateValues();
  }

  public function calculateValues(){
    $i = substr($this->month, 0,7).'-01 12:00:00';
    $j = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($i)));
    $id = $this->id;

    require "connection.php";
    $result = $conn->query("SELECT beginningDate, exitDate FROM $userTable WHERE id = $id");
    if($result && ($row = $result->fetch_assoc())){
      $beginDate = $row['beginningDate'];
      $exitDate = ($row['exitDate'] == '0000-00-00 00:00:00') ? '5000-12-30 23:59:59' : $row['exitDate'];
    } else { //should never occur
      die("Invalid userID");
    }

    $count = 0;
    while(substr($i,0, 10) != substr($j,0,10) && substr($i,0, 4) <= substr($j,0, 4)) { //for EVERY day of the month (excluding the last day of j of course)
      $this->dayOfWeek[] = strtolower(date('D', strtotime($i)));
      $this->date[] = substr($i, 0, 10);
      //get the expectedHours from the matching interval
      $sql = "SELECT * FROM $intervalTable WHERE userID = $id AND ( (DATE(startDate) <= DATE('$i') AND endDate IS NULL) OR (endDate IS NOT NULL AND DATE(startDate) <= DATE('$i') AND DATE('$i') < DATE(endDate)) )";
      $result = $conn->query($sql);
      if($result && $result->num_rows == 1){
        $row = $result->fetch_assoc();
        $this->shouldTime[] = $row[strtolower(date('D', strtotime($i)))];
        $this->overTimeLump = $row['overTimeLump'];
      } else { //the date doesnt have an interval, it goes out of the intervals bounds.
        $this->shouldTime[] = 0;
      }

      $sql = "SELECT $logTable.* FROM $logTable WHERE userID = $id AND time >= '$beginDate' AND time <= '$exitDate' AND time LIKE'". substr($i, 0, 10) ." %'";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0){ //user has absolved hours for today (Checkin/Vacation/..)
        $row = $result->fetch_assoc();
        $this->start[] = $row['time'];
        $this->end[] = $row['timeEnd'];
        $this->activity[] = $row['status'];
        $this->timeToUTC[] = $row['timeToUTC'];
        $this->indecesIM[] = $row['indexIM'];
        $this->lunchTime[] = $row['breakCredit'];
      } else { //user wasnt here today = 0 absolved hours
        $this->start[] = '-';
        $this->end[] = '-';
        $this->activity[] = '-1';
        $this->lunchTime[] = 0;
        $this->timeToUTC[] = 0;
        $this->indecesIM[] = 0;
      }
      //if today is a holiday, poot poot
      if(isHoliday($i)){
        $this->shouldTime[$count] = 0;
      }
      $i = carryOverAdder_Hours($i, 24);
      $count++;
    } //endwhile;
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
