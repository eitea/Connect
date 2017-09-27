<?php
require 'Calculators/IntervalCalculator.php';
$logSums = new Interval_Calculator($curID);

if($logSums->saldo < 0){
  $color = 'style=color:red';
} else {
  $color = 'style=color:#00ba29';
}

$result_Sum = $conn->query("SELECT * FROM $userTable INNER JOIN $intervalTable ON $intervalTable.userID = $userTable.id WHERE $userTable.id = $curID AND endDate IS NULL");
if($result_Sum && $result_Sum->num_rows > 0){
  $userRow = $result_Sum->fetch_assoc();
} else {
  die(mysqli_error($conn));
}
?>

<div class="container-fluid text-right"><a data-toggle="collapse" href="#infoSummarycollapse" aria-expanded="false" aria-controls="collapseExample"><i class="fa fa-question-circle"></i></a></div>
<div class="collapse" id="infoSummarycollapse">
  <div class="well">
    Sumamries below display all hours this User has, starting from entrance date until now or, if defined, the date of exit.
  </div>
</div>


<div class="container-fluid">
  <div class="col-md-4">
    <h4>Saldo</h4><hr>
    <table class="table table-striped">
      <thead>
        <th><?php echo $lang['DESCRIPTION']; ?></th>
        <th><?php echo $lang['HOURS']; ?></th>
      </thead>
      <tbody>
        <?php
        echo '<tr><td>'.$lang['EXPECTED_HOURS'].'</td><td>-'. number_format(array_sum($logSums->shouldTime), 2, '.', '') .'</td></tr>';
        echo '<tr><td>'.$lang['ABSOLVED_HOURS'].'</td><td>+'. number_format(array_sum($logSums->absolvedTime), 2, '.', '') .'</td></tr>';
        echo '<tr><td>'.$lang['LUNCHBREAK'].'</td><td>-'. number_format(array_sum($logSums->lunchTime), 2, '.', '') . '</td></tr>';
        $overTimeAdditive = $corrections = 0;
        foreach($logSums->endOfMonth as $arr){
          $overTimeAdditive += $arr['overTimeLump'];
          $corrections += $arr['correction'];
        }
        if($overTimeAdditive) echo '<tr><td>'.$lang['OVERTIME_ALLOWANCE'] . '</td> <td> -' . number_format($overTimeAdditive) . ' </td></tr>';
        if($corrections) echo "<tr><td><a data-toggle='modal' data-target='#correctionModal'>".$lang['CORRECTION'].' '.$lang['HOURS'].'</a></td><td>'.sprintf('%+.2f',$corrections).'</td></tr>';
        echo "<tr><td style='font-weight:bold;'>".$lang['SUM']."</td><td $color>". number_format($logSums->saldo, 2, '.', ''). '</td></tr>';
        ?>
      </tbody>
    </table>
  </div>

  <div class="col-md-4">
    <h4> <?php echo $lang['TIMETABLE']; ?></h4><hr>
    <table class="table table-striped">
      <thead>
        <th><?php echo $lang['TIMETABLE']; ?></th>
        <th><?php echo $lang['HOURS']; ?></th>
      </thead>
      <tbody>
        <?php
        $theBigSum = $userRow['mon'] + $userRow['tue'] + $userRow['wed'] + $userRow['thu'] + $userRow['fri'] + $userRow['sat'] + $userRow['sun'];
        echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['mon'].'</td><td>'. $userRow['mon'] .'</td></tr>';
        echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['tue'].'</td><td>'. $userRow['tue'] .'</td></tr>';
        echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['wed'].'</td><td>'. $userRow['wed'] .'</td></tr>';
        echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['thu'].'</td><td>'. $userRow['thu'] .'</td></tr>';
        echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['fri'].'</td><td>'. $userRow['fri'] .'</td></tr>';
        echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['sat'].'</td><td>'. $userRow['sat'] .'</td></tr>';
        echo '<tr><td>'.$lang['WEEKDAY_TOSTRING']['sun'].'</td><td>'. $userRow['sun'] .'</td></tr>';
        echo "<tr><td style='font-weight:bold;'>".$lang['SUM']."</td><td>". $theBigSum .'</td></tr>';
        ?>
      </tbody>
    </table>
  </div>

  <div class="col-md-4">
    <h4> <?php echo $lang['DATA']; ?></h4><hr>
    <table class="table table-striped">
      <thead>
        <th><?php echo $lang['DESCRIPTION']; ?> </th>
        <th>Detail</th>
      </thead>
      <tbody>
        <?php
        echo '<tr><td>'. $lang['ENTRANCE_DATE'] .'</td><td>'. substr($userRow['beginningDate'],0,10) .'</td></tr>';
        echo '<tr><td><a href="../time/vacations?curID='.$curID.'" >'. $lang['VACATION_DAYS'].' '.$lang['AVAILABLE'].'</a></td><td>'. sprintf('%.2f', $logSums->availableVacation) .'</td></tr>';
        echo '<tr><td>'. $lang['VACATION_DAYS'].$lang['PER_YEAR'].'</td><td>'. $userRow['vacPerYear'] .'</td></tr>';
        echo '<tr><td>'. $lang['OVERTIME_ALLOWANCE'].'</td><td>'. $userRow['overTimeLump'] .'</td></tr>';
        ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="correctionModal" tabindex="-1">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><?php echo $lang['CORRECTION']; ?></h4>
      </div>
      <div class="modal-body">
        <table class="table table-hover">
          <thead>
            <th><?php echo $lang['CORRECTION'] .' '. $lang['DATE']; ?></th>
            <th><?php echo $lang['ADJUSTMENTS'].' '. $lang['HOURS']; ?></th>
            <th>Info</th>
          </thead>
          <tbody>
            <?php
            $result = $conn->query("SELECT * FROM $correctionTable WHERE userID = $curID AND cType='log'");
            while($result && ($row = $result->fetch_assoc())){
              echo '<tr>';
              echo '<td>'.substr($row['createdOn'],0,10).'</td>';
              echo '<td>'.sprintf("%+.2f",$row['hours'] * $row['addOrSub']).'</td>';
              echo '<td>'.$row['infoText'].'</td>';
              echo '</tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
