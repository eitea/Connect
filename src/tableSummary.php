  <?php
  require 'Calculators/LogCalculator.php';
  $logSums = new LogCalculator($curID);

  $breakCreditHours = $logSums->breakCreditHours;
  $absolvedHours = $logSums->absolvedHours;
  $expectedHours = $logSums->expectedHours - $logSums->vacationHours;
  $vacationHours = $logSums->vacationHours;
  $specialLeaveHours = $logSums->specialLeaveHours;
  $sickHours = $logSums->sickHours;
  $overTimeAdditive = $logSums->overTimeAdditive;
  $correctionHours = $logSums->correctionHours;
  $availableVacationDays = $logSums->vacationDays;

  $beginDate = $logSums->beginDate;
  $theBigSum = $logSums->saldo;

  if($theBigSum < 0){
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
    <h4> <?php echo $lang['HOURS_COMPLETE']; ?> <div class="pull-right"><a data-toggle="collapse" href="#infoSummarycollapse" aria-expanded="false" aria-controls="collapseExample"><i class="fa fa-question-circle"></i></a></div></h4>
    <hr>

    <div class="collapse" id="infoSummarycollapse">
      <div class="well">
        This is an overall summary of all hours this user has, filters do not apply here. <br>
         Sumamries below display all hours this User has, starting from entrance date until now or, if defined, the date of exit.
      </div>
    </div>

    <div>
    <table class="table table-striped">
      <thead>
        <th><?php echo $lang['DESCRIPTION']; ?></th>
        <th><?php echo $lang['HOURS']; ?></th>
      </thead>
      <tbody>
        <?php
        echo '<tr><td>'.$lang['ABSOLVED_HOURS'].': </td><td>+'. number_format($absolvedHours, 2, '.', '') .'</td></tr>';
        echo '<tr><td>'.$lang['EXPECTED_HOURS'].': </td><td>-'. number_format($expectedHours, 2, '.', '') .'</td></tr>';
        echo '<tr><td>'.$lang['LUNCHBREAK'].': </td><td>-'. number_format($breakCreditHours, 2, '.', '') . '</td></tr>';
        echo '<tr><td>'.$lang['SPECIAL_LEAVE'].': </td><td>+'.number_format($specialLeaveHours,2, '.', '').'</td></tr>';
        echo '<tr><td>'.$lang['SICK_LEAVE'].': </td><td>+'.number_format($sickHours,2,'.','').'</td></tr>';
        echo '<tr><td>'.$lang['OVERTIME_ALLOWANCE'] . ': </td> <td> -' . number_format($overTimeAdditive,2,'.','') . ' </td></tr>';
        echo "<tr><td><a data-toggle='modal' data-target='#correctionModal'>".$lang['CORRECTION'].' '.$lang['HOURS'].'</a>: </td><td>'.sprintf('%+.2f',$correctionHours).'</td></tr>';
        echo "<tr><td style='font-weight:bold;'>".$lang['SUM'].": </td><td $color>". number_format($theBigSum, 2, '.', ''). '</td></tr>';
        ?>
      </tbody>
    </table>
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

  <br>
  <?php
  $theBigSum = $userRow['mon'] + $userRow['tue'] + $userRow['wed'] + $userRow['thu'] + $userRow['fri'] + $userRow['sat'] + $userRow['sun'];
  ?>

  <div>
    <h4> <?php echo $lang['TIMETABLE']; ?></h4>
    <hr>
    <table class="table table-striped">
      <thead>
        <th><?php echo $lang['TIMETABLE']; ?></th>
        <th><?php echo $lang['HOURS']; ?></th>
      </thead>
      <tbody>
        <?php
        echo '<tr><td>'.$lang_weeklyDayToString['mon'].': </td><td>'. $userRow['mon'] .'</td></tr>';
        echo '<tr><td>'.$lang_weeklyDayToString['tue'].': </td><td>'. $userRow['tue'] .'</td></tr>';
        echo '<tr><td>'.$lang_weeklyDayToString['wed'].': </td><td>'. $userRow['wed'] .'</td></tr>';
        echo '<tr><td>'.$lang_weeklyDayToString['thu'].': </td><td>'. $userRow['thu'] .'</td></tr>';
        echo '<tr><td>'.$lang_weeklyDayToString['fri'].': </td><td>'. $userRow['fri'] .'</td></tr>';
        echo '<tr><td>'.$lang_weeklyDayToString['sat'].': </td><td>'. $userRow['sat'] .'</td></tr>';
        echo '<tr><td>'.$lang_weeklyDayToString['sun'].': </td><td>'. $userRow['sun'] .'</td></tr>';
        echo "<tr><td style='font-weight:bold;'>".$lang['SUM'].": </td><td>". $theBigSum .'</td></tr>';
        ?>
      </tbody>
    </table>
  </div>

  <br>
  <div>
    <h4> <?php echo $lang['VACATION']; ?></h4>
    <hr>
    <table class="table table-striped">
      <thead>
        <th><?php echo $lang['DESCRIPTION']; ?> </th>
        <th>Detail</th>
      </thead>
      <tbody>
        <?php
        echo '<tr><td>'. $lang['ENTRANCE_DATE'] .'</td><td>'. substr($userRow['beginningDate'],0,10) .'</td></tr>';
        echo '<tr><td><a href="display_vacation.php?curID='.$curID.'" >'. $lang['DAYS'].' '.$lang['AVAILABLE'].': '. $lang['VACATION']. '</a></td><td>'. sprintf('%.2f', $availableVacationDays) .'</td></tr>';
        echo '<tr><td>'. $lang['VACATION_DAYS_PER_YEAR'].'</td><td>'. $userRow['vacPerYear'] .'</td></tr>';
        echo '<tr><td>'. $lang['OVERTIME_ALLOWANCE'].'</td><td>'. $userRow['overTimeLump'] .'</td></tr>';
        ?>
      </tbody>
    </table>
  </div>
