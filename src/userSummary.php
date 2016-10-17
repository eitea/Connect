<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="../plugins/datatables/css/dataTables.bootstrap.min.css">

</head>

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

  $sql = "SELECT * FROM $logTable WHERE userID = $userID AND timeEnd != '0000-00-00 00:00:00' ";
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
  // for today there is no entry: get the absentLog entry. there MUST be an entry for that, or the expected hours of that day will also be 0.
  if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
      $expectedHours += strtolower(date('D', strtotime($row['time'])));
    }
  }

  ?>

<table class="table table-striped table-bordered" cellspacing="0" style='width:40%'>
  <tr>
    <th>Description</th>
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
echo '<tr><td style=font-weight:bold>Summary: </td><td>'. number_format($absolvedHours - $expectedHours - $breakCreditHours + $vacationHours + $specialLeaveHours, 2, '.', '').'</td></tr>';
?>
</table>
</body>
