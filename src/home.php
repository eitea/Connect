<?php include 'header.php'; ?>
<?php include 'Calculators/LogCalculator.php'; ?>
<!-- BODY -->
<style>
th:first-child{
  width:80%;
}
</style>
<?php
if(isset($_GET['userID'])){
  $curID = test_input($_GET['userID']);
} else {
  $curID = $userID;
}

$logSums = new LogCalculator($curID);

$breakCreditHours = $logSums->breakCreditHours;
$absolvedHours = $logSums->absolvedHours;
$expectedHours = $logSums->expectedHours;
$vacationHours = $logSums->vacationHours;
$specialLeaveHours = $logSums->specialLeaveHours;
$sickHours = $logSums->sickHours;
$overTimeAdditive = $logSums->overTimeAdditive;

$beginDate = $logSums->beginDate;
$theBigSum = $logSums->saldo;

if($theBigSum < 0){
  $color = 'style=color:red';
} else {
  $color = 'style=color:#00ba29';
}


$sql = "SELECT * FROM $userTable INNER JOIN $vacationTable ON $vacationTable.userID = $userTable.id INNER JOIN $bookingTable ON $bookingTable.userID = $userTable.id WHERE $userTable.id = $curID";
$result = $conn->query($sql);
if($result && $result->num_rows > 0){
  $userRow = $result->fetch_assoc();
} else {
  die(mysqli_error($conn));
}
?>
<div>
  <br>
  <h4> <?php echo $lang['HOURS_COMPLETE']; ?></h4>
  <hr>
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
      echo '<tr><td>'.$lang['VACATION'].': </td><td>+'. number_format($vacationHours, 2, '.', '') .'</td></tr>';
      echo '<tr><td>'.$lang['SPECIAL_LEAVE'].': </td><td>+'. number_format($specialLeaveHours, 2, '.', '').'</td></tr>';
      echo '<tr><td>'.$lang['SICK_LEAVE'].': </td><td>+'. number_format($sickHours, 2, '.', '').'</td></tr>';
      echo '<tr><td>'.$lang['OVERTIME_ALLOWANCE'].': </td><td>-'. $overTimeAdditive . '</td></tr>';
      echo "<tr><td style=font-weight:bold;>Sum: </td><td $color>". number_format($theBigSum, 2, '.', '').'</td></tr>';
      ?>
    </tbody>
  </table>
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
      echo '<tr><td>Monday: </td><td>'. $userRow['mon'] .'</td></tr>';
      echo '<tr><td>Tuesday: </td><td>'. $userRow['tue'] .'</td></tr>';
      echo '<tr><td>Wednesday: </td><td>'. $userRow['wed'] .'</td></tr>';
      echo '<tr><td>Thursday: </td><td>'. $userRow['thu'] .'</td></tr>';
      echo '<tr><td>Friday: </td><td>'. $userRow['fri'] .'</td></tr>';
      echo '<tr><td>Saturday: </td><td>'. $userRow['sat'] .'</td></tr>';
      echo '<tr><td>Sunday: </td><td>'. $userRow['sun'] .'</td></tr>';
      echo "<tr><td style=font-weight:bold;>Sum: </td><td>". $theBigSum .'</td></tr>';
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
      echo '<tr><td>'. $lang['ACCUMULATED_DAYS'] .': '. $lang['VACATION']. '</td><td>'. number_format($userRow['vacationHoursCredit']/24, 2, '.', '') .'</td></tr>';
      echo '<tr><td>'. $lang['USED_DAYS'] .': ' .$lang['VACATION']. '</td><td>'. $logSums->usedVacationDays .'</td></tr>';
      echo '<tr><td>'. $lang['VACATION_DAYS_PER_YEAR'].'</td><td>'. $userRow['daysPerYear'] .'</td></tr>';
      echo '<tr><td>'. $lang['OVERTIME_ALLOWANCE'].'</td><td>'. $userRow['overTimeLump'] .'</td></tr>';
      ?>
    </tbody>
  </table>
</div>


<!-- /BODY -->
<?php include 'footer.php'; ?>
