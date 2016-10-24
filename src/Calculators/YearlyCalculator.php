<?php

class Yearly_Calculator{
  private $id = 0;
  private $year = 0;

  public $monthShortName;

  public $lunchTime = array();
  public $shouldTime = array();
  public $actualTime = array();

  public $isTime = array();

  //year like yyyy-mm-dd
  public function __construct($year, $userid){
    $this->year = $year;
    $this->id = $userid;
    $this->monthShortName = array("'Jan'", "'Feb'", "'Mar'", "'Apr'", "'May'", "'Jun'", "'Jul'", "'Aug'", "'Sep'", "'Oct'", "'Nov'", "'Dec'");
  }

  public function calculateValues(){
    $month = substr($this->year,0,4) ."-01-01 05:00:00";
    for($i = 1; $i <= 12; $i++) {
      $month = substr_replace($month, sprintf("%'.02d", $i),5,2);
      $m = new Monthly_Calculator($month, $this->id);
      $this->shouldTime[] = array_sum($m->shouldTime);
      $this->lunchTime[] = array_sum($m->lunchTime);
      /*
      $t = 0;
      for($j = 0; $j <= $m->days; $j++){
        $t += timeDiff_Hours($m->start[$j], $m->end[$j]);
      }
      $this->isTime[] = $t;

      $this->actualTime[] = $t - array_sum($m->lunchTime) - $m->overTimeLump;
      */

    }
  }

}
?>
