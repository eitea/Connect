<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../plugins/homeMenu/homeMenu.css" rel="stylesheet">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>
</head>
<body>
  <?php
  include 'connection.php';
  include 'language.php';
  include 'createTimestamps.php';

  if(isset($_GET['userID'])){
    $curID = test_input($_GET['userID']);
  } else {
    die();
  }

  include 'Calculators/LogCalculator.php';
  $logSums = new LogCalculator($curID);

  $breakCreditHours = $logSums->breakCreditHours;
  $absolvedHours = $logSums->absolvedHours;
  $expectedHours = $logSums->expectedHours;
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


  $sql = "SELECT * FROM $userTable INNER JOIN $intervalTable ON $intervalTable.userID = $userTable.id WHERE $userTable.id = $curID AND endDate IS NULL";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    $userRow = $result->fetch_assoc();
  } else {
    die(mysqli_error($conn));
  }
  ?>
    <h4> <?php echo $lang['HOURS_COMPLETE']; ?></h4>
    <hr>
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
        echo '<tr><td>'.$lang['VACATION'].': </td><td>+'. number_format($vacationHours, 2, '.', '') .'</td></tr>';
        echo '<tr><td>'.$lang['SPECIAL_LEAVE'].': </td><td>+'. number_format($specialLeaveHours, 2, '.', '').'</td></tr>';
        echo '<tr><td>'.$lang['SICK_LEAVE'].': </td><td>+'. number_format($sickHours, 2, '.', '').'</td></tr>';
        echo '<tr><td>'.$lang['OVERTIME_ALLOWANCE'].': </td><td>-'. $overTimeAdditive . '</td></tr>';
        echo "<tr><td><a href='displayCorrections.php?userID=$curID'>".$lang['CORRECTION'].' '.$lang['HOURS'].'</a>: </td><td>'. sprintf('%+.2f', $correctionHours) . '</td></tr>';
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
        echo '<tr><td>'. $lang['DAYS'].' '.$lang['AVAILABLE'].': '. $lang['VACATION']. '</td><td>'. sprintf('%.2f', $availableVacationDays) .'</td></tr>';
        echo '<tr><td>'. $lang['VACATION_DAYS_PER_YEAR'].'</td><td>'. $userRow['vacPerYear'] .'</td></tr>';
        echo '<tr><td>'. $lang['OVERTIME_ALLOWANCE'].'</td><td>'. $userRow['overTimeLump'] .'</td></tr>';
        ?>
      </tbody>
    </table>
  </div>
</body>
</html>
