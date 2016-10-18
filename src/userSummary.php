<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="../css/table.css">

</head>
<style>
div{
  text-align:center;
}
tr td:nth-child(1) { /* I don't think they are 0 based */
   text-align: left;
}
</style>
<body>
  <?php
  session_start();
  if (!isset($_SESSION['userid'])) {
    die('Please <a href="login.php">login</a> first.');
  }

  $userID = $_SESSION['userid'];

  require "connection.php";
  require "createTimestamps.php";
  require "language.php";

  $breakCreditHours = $absolvedHours = $expectedHours = $vacationHours = $specialLeaveHours = $sickHours = $ZA_Hours = 0;

  $sql = "SELECT * FROM $logTable, $userTable WHERE userID = $userID AND timeEnd != '0000-00-00 00:00:00' AND $userTable.id = $userID ";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      if($row['timeEnd'] == '0000-00-00 00:00:00'){
        $timeEnd = getCurrentTimestamp();
      } else {
        $timeEnd = $row['timeEnd'];
      }

      switch($row['status']){
        case 0:
          $absolvedHours += timeDiff_Hours($row['time'], $timeEnd);
          $breakCreditHours += $row['breakCredit'];
          if($row['enableProjecting'] == 'FALSE' && timeDiff_Hours($row['time'], $timeEnd) > $row['pauseAfterHours']){
            $breakCreditHours += $row['hoursOfRest'];
          }
          break;
        case 1:
          $vacationHours += timeDiff_Hours($row['time'], $timeEnd);
          break;
        case 2:
          $specialLeaveHours += timeDiff_Hours($row['time'], $timeEnd);
          break;
        case 3:
          $sickHours += timeDiff_Hours($row['time'], $timeEnd);
          break;
        case 4:
          $ZA_Hours += timeDiff_Hours($row['time'], $timeEnd);
      }

      $expectedHours += $row['expectedHours'];
    }
  }

  //extra expectedHours from unlogs:
  $sql = "SELECT * FROM $negative_logTable WHERE userID = $userID";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      if(!isHoliday($row['time'])){
        $expectedHours += strtolower(date('D', strtotime($row['time'])));
      }
    }
  }

  $theBigSum = $absolvedHours - $expectedHours - $breakCreditHours + $vacationHours + $specialLeaveHours;
  if($theBigSum > 0){
    $color = 'style=color:green';
  } else {
    $color = 'style=color:red';
  }
  ?>

<div>
<table class="table table-striped table-bordered" cellspacing="0" style='width:500px'>
  <tr>
    <th style=text-align:left>Description</th>
    <th width=20%>Hours</th>
  </tr>
<?php
echo '<tr><td>Absolved Hours: </td><td>'. number_format($absolvedHours, 2, '.', '').'</td></tr>';
echo '<tr><td>Expected Hours: </td><td>'. number_format($expectedHours, 2, '.', '').'</td></tr>';
echo '<tr><td>LunchTime: </td><td>'. number_format($breakCreditHours, 2, '.', '').'</td></tr>';
echo '<tr><td>Hours in Vacation: </td><td>'. number_format($vacationHours, 2, '.', '').'</td></tr>';
echo '<tr><td>Special Absence: </td><td>'. number_format($specialLeaveHours, 2, '.', '').'</td></tr>';
echo '<tr><td>Sick Time: </td><td>'. number_format($sickHours, 2, '.', '').'</td></tr>';
echo '<tr><td>ZA: </td><td>'. number_format($ZA_Hours, 2, '.', '').'</td></tr>';
echo "<tr><td style=font-weight:bold;$color>Summary: </td><td $color>". number_format($theBigSum, 2, '.', '').'</td></tr>';
?>
</table>
</div>
</body>
