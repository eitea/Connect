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
    $result = $conn->query("SELECT beginningDate, exitDate FROM $userTable WHERE id = $id");
    if($result && ($row = $result->fetch_assoc())){
      $beginDate = $row['beginningDate'];
      $exitDate = ($row['exitDate'] == '0000-00-00 00:00:00') ? '5000-12-30 23:59:59' : $row['exitDate'];
    } else { //should never occur
      die("Invalid userID");
    }

    $i = substr_replace($month, "01", 8, 2);

    $count = 0;
    while(substr($i,0, 10) != substr(carryOverAdder_Hours($month, 24),0,10)) { //for EVERY day of month
      $this->dayOfWeek[] = strtolower(date('D', strtotime($i)));
      $this->date[] = substr($i, 0, 10);

      $sql = "SELECT $logTable.* FROM $logTable WHERE userID = $id AND time >= '$beginDate' AND time <= '$exitDate' AND time LIKE'". substr($i, 0, 10) ." %'";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0){ //user has absolved hours for today (Checkin/Vacation/..)
          $row = $result->fetch_assoc();
          $this->start[] = $row['time'];
          $this->end[] = $row['timeEnd'];
          $this->activity[] = $row['status'];
          $this->shouldTime[] = $row['expectedHours'];
          $this->timeToUTC[] = $row['timeToUTC'];
          $this->indecesIM[] = $row['indexIM'];
          $this->lunchTime[$count] = $row['breakCredit'];
        } else { //user wasnt here today = 0 absolved hours
          $this->start[] = '-';
          $this->end[] = '-';
          $this->activity[] = '-1';
          $this->lunchTime[] = 0;
          $this->timeToUTC[] = 0;
          //check the absentLog
          $sql = "SELECT * FROM $negative_logTable WHERE userID = $id AND time > '$beginDate' AND time < '$exitDate' AND time LIKE '". substr($i, 0, 10) ." %'";
          $result = $conn->query($sql);
          if($result && $result->num_rows > 0){ //he has an absent entry -> fetch his expected hours
            $row = $result->fetch_assoc();
            $this->shouldTime[] = $row[strtolower(date('D', strtotime($i)))];
            $this->indecesIM[] = $row['negative_indexIM'];
          } else { //no absent entry means date lies before entry or after exit date
            $this->shouldTime[] = 0;
            $this->indecesIM[] = 0;
          }
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
