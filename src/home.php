<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
<h3>Navigation</h3>
</div>

<?php
  $breakCreditHours = $absolvedHours = $expectedHours = $vacationHours = $specialLeaveHours = $sickHours = $overTimeAdditive = 0;
  $accumulatedVacDays = 0;

  if(isset($_GET['userID'])){
    $curID = $_GET['userID'];
  }

  $sql = "SELECT * FROM $userTable INNER JOIN $vacationTable ON $vacationTable.userID = $userTable.id INNER JOIN $bookingTable ON $bookingTable.userID = $userTable.id WHERE $userTable.id = $curID";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    $userRow = $result->fetch_assoc();
  } else {
    die(mysqli_error($conn));
  }

  $overTimeAdditive = $userRow['overTimeLump'] * ceil(timeDiff_Hours(substr($userRow['beginningDate'],0,11).'05:00:00', substr(getCurrentTimestamp(),0,11).'05:00:00')/(24*30));

  $sql = "SELECT * FROM $logTable WHERE userID = $curID";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      if($row['timeEnd'] == '0000-00-00 00:00:00'){
        $timeEnd = getCurrentTimestamp();

        if(timeDiff_Hours($row['time'], $timeEnd) >= $row['expectedHours']){
          $expectedHours += $row['expectedHours'];
        } else {
          $expectedHours += timeDiff_Hours($row['time'], $timeEnd); //this may only happen for OPEN timestamps
        }

      } else {
        $timeEnd = $row['timeEnd'];
        $expectedHours += $row['expectedHours'];
      }

      switch($row['status']){
        case 0:
          $absolvedHours += timeDiff_Hours($row['time'], $timeEnd);
          $breakCreditHours += $row['breakCredit'];
          break;
        case 1:
          $vacationHours += timeDiff_Hours($row['time'], $timeEnd);
          $accumulatedVacDays++;
          break;
        case 2:
          $specialLeaveHours += timeDiff_Hours($row['time'], $timeEnd);
          break;
        case 3:
          $sickHours += timeDiff_Hours($row['time'], $timeEnd);
      }

    }
  }

  //extra expectedHours from unlogs:
  $sql = "SELECT * FROM $negative_logTable WHERE userID = $curID";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      if(!isHoliday($row['time'])){
        $expectedHours += $row[strtolower(date('D', strtotime($row['time'])))];
      }
    }
  }

  $theBigSum = $absolvedHours - $expectedHours - $breakCreditHours + $vacationHours + $specialLeaveHours - $overTimeAdditive;
  if($theBigSum > 0){
    $color = 'style=color:#00ba29';
  } else {
    $color = 'style=color:red';
  }

?>
<div>
<table class="table table-striped table-bordered">
    <tr>
      <th><?php echo $lang['DESCRIPTION']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
    </tr>
  <?php
  echo '<tr><td>'.$lang['ABSOLVED_HOURS'].': </td><td>+'. number_format($absolvedHours, 2, '.', '') .'</td></tr>';
  echo '<tr><td>'.$lang['EXPECTED_HOURS'].': </td><td>-'. number_format($expectedHours, 2, '.', '') .'</td></tr>';
  echo '<tr><td>'.$lang['LUNCHBREAK'].': </td><td>-'. number_format($breakCreditHours, 2, '.', '') . '</td></tr>';
  echo '<tr><td>'.$lang['VACATION'].': </td><td>+'. number_format($vacationHours, 2, '.', '') .'</td></tr>';
  echo '<tr><td>'.$lang['SPECIAL_LEAVE'].': </td><td>+'. number_format($specialLeaveHours, 2, '.', '').'</td></tr>';
  echo '<tr><td>'.$lang['SICK_LEAVE'].': </td><td>+'. number_format($sickHours, 2, '.', '').'</td></tr>';
  echo '<tr><td>'.$lang['OVERTIME_ALLOWANCE'].': </td><td>-'. $overTimeAdditive . '</td></tr>';
  echo "<tr><td style=font-weight:bold;>Sum: </td><td $color>". number_format($theBigSum, 2, '.', '').'</td></tr>';
  ?>
</table>
</div>
<?php
$theBigSum = $userRow['mon'] + $userRow['tue'] + $userRow['wed'] + $userRow['thu'] + $userRow['fri'] + $userRow['sat'] + $userRow['sun'];
?>
<div>
<table class="table table-striped table-bordered">
    <tr>
      <th><?php echo $lang['TIMETABLE']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
    </tr>
  <?php
  echo '<tr><td>Monday: </td><td>'. $userRow['mon'] .'</td></tr>';
  echo '<tr><td>Tuesday: </td><td>'. $userRow['tue'] .'</td></tr>';
  echo '<tr><td>Wednesday: </td><td>'. $userRow['wed'] .'</td></tr>';
  echo '<tr><td>Thursday: </td><td>'. $userRow['thu'] .'</td></tr>';
  echo '<tr><td>Friday: </td><td>'. $userRow['fri'] .'</td></tr>';
  echo '<tr><td>Saturday: </td><td>'. $userRow['sat'] .'</td></tr>';
  echo '<tr><td>Sunday: </td><td>'. $userRow['sun'] .'</td></tr>';
  echo "<tr><td style=font-weight:bold;>Sum: </td><td>". $theBigSum .'</td></tr>';
  ?>
</table>
</div>

<div>
<table class="table table-striped table-bordered">
  <tr>
    <th><?php echo $lang['DESCRIPTION']; ?> </th>
    <th>Detail</th>
  </tr>
  <?php
  echo '<tr><td>'. $lang['ENTRANCE_DATE'] .'</td><td>'. substr($userRow['beginningDate'],0,10) .'</td></tr>';
  echo '<tr><td>'. $lang['ACCUMULATED_DAYS'] .': '. $lang['VACATION']. '</td><td>'. number_format($userRow['vacationHoursCredit']/24, 2, '.', '') .'</td></tr>';
  echo '<tr><td>'. $lang['USED_DAYS'] .': ' .$lang['VACATION']. '</td><td>'. $accumulatedVacDays .'</td></tr>';
  echo '<tr><td>'. $lang['VACATION_DAYS_PER_YEAR'].'</td><td>'. $userRow['daysPerYear'] .'</td></tr>';
  echo '<tr><td>'. $lang['OVERTIME_ALLOWANCE'].'</td><td>'. $userRow['overTimeLump'] .'</td></tr>';
  ?>
</table>
</div>



<!-- /BODY -->
<?php include 'footer.php'; ?>
