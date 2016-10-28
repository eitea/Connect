<?php
class Monthly_Calculator{
  public $days = 0;
  public $canBook = FALSE;
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

//month like yyyy-mm-dd hh-mm-ss
  public function __construct($month, $userid){
    $this->month = $month;
    $this->id = $userid;
    $this->days = date('t', strtotime($month));
  }


//call this manually, will add different calculation methods in future
  public function calculateValues(){
    $month = $this->month;
    $id = $this->id;

    $endOfMonth = date('t', strtotime($month));
    $month = substr_replace($month, $endOfMonth, 8, 2);

    require "connection.php";
    $i = substr_replace($month, "01", 8, 2);

    $count = 1;
    while(substr($i,0, 10) != substr(carryOverAdder_Hours($month, 24),0,10)) { //from 1st to "31th", including last day
      $this->dayOfWeek[] = strtolower(date('D', strtotime($i)));
      $this->date[] = substr($i, 0, 10);

      $sql = "SELECT * FROM $logTable WHERE $logTable.userID = $id AND $logTable.time LIKE'". substr($i, 0, 10) ." %'";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0){ //guy checked in today
          $row = $result->fetch_assoc();
          $this->start[] = $row['time'];
          $this->end[] = $row['timeEnd'];
          $this->activity[] = $row['status'];
          $this->shouldTime[] = $row['expectedHours'];
          $this->timeToUTC[] = $row['timeToUTC'];
          $this->indecesIM[] = $row['indexIM'];
          $this->lunchTime[$count-1] = $row['breakCredit']; //initial value

          //he can only have lunch when he was actually here
          $sql="SELECT * FROM $userTable WHERE id = $id";
          $result = $conn->query($sql);
          $row2 = $result->fetch_assoc();
          //is he supposed to have lunch?
          if($row['status'] == 0 && timeDiff_Hours($row['time'], $row['timeEnd']) >= $row2['pauseAfterHours']){
            if($row2['enableProjecting'] == 'TRUE'){
              //he has access? leave it at the breakCredit, since thats the accumulated breaktime.
              $this->canBook = 'TRUE';
            } else {
              //he has no access? just add his default value of lunch break.
              $this->lunchTime[$count-1] += $row2['hoursOfRest'];
            }
          }
          if(isHoliday($row['time'])){
            $this->shouldTime[$count-1] = 0;
          }

        } else {
          //if there is NO log entry for the day, fill out start and end with EMPTY STRING and expected hours from the absentLog
          $sql = "SELECT * FROM $negative_logTable WHERE time LIKE '". substr($i, 0, 10) ." %' AND userID = $id";
          $result = $conn->query($sql);
          // for today there is no entry: get the absentLog entry. there MUST be an entry for that, or the dates in the future/waay past.
          if($result && $result->num_rows > 0){
            $row = $result->fetch_assoc();
            $this->start[] = '-';
            $this->end[] = '-';
            $this->activity[] = '-1';
            $this->lunchTime[] = 0;
            $this->shouldTime[] = $row[strtolower(date('D', strtotime($i)))];
            $this->indecesIM[] = $row['negative_indexIM'];
            $this->timeToUTC[] = 0;

            if(isHoliday($row['time'])){
              $this->shouldTime[$count-1] = 0;
            }
          } else { //future/past date
            $this->start[] = '-';
            $this->end[] = '-';
            $this->activity[] = '-1';
            $this->lunchTime[] = 0;
            $this->shouldTime[] = 0;
            $this->indecesIM[] = 0;
            $this->timeToUTC[] = 0;
          }
        }

        $i = carryOverAdder_Hours($i, 24);
        $this->daysAsNumber[] = $count;
        $count++;
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
