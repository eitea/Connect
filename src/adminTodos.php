<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/table.css">
  <link rel="stylesheet" href="../css/submitButt.css">
  <link rel="stylesheet" href="../css/homeMenu.css">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>

  <style>
  p{
    font-size:24px;
    color:#0078ab;
  }
  </style>
</head>
<body>
<form method=post>
<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}
require 'connection.php';
require 'createTimestamps.php';
require 'language.php';

if(isset($_POST['autoCorrect']) && isset($_POST['autoCorrects'])){
  foreach($_POST['autoCorrects'] as $indexIM){
    $sql = "SELECT * FROM $logTable WHERE indexIM = $indexIM";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    $adjustedTime = carryOverAdder_Hours($row['time'], floor($row['expectedHours']));
    $adjustedTime = carryOverAdder_Minutes($adjustedTime, (($row['expectedHours'] * 60) % 60));

    $sql = "UPDATE $logTable SET timeEnd = '$adjustedTime' WHERE indexIM =" .$row['indexIM'];
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}
?>

<?php
$sql ="SELECT * FROM $userRequests WHERE status = '0'";
$result = $conn->query($sql);
if($result && $result->num_rows > 0):
?>
  <p> Unanswered Requests: </p>

<?php
echo $result->num_rows . " Vacation Request/s: ";
echo "<a href=allowVacations.php > Answer</a>";
endif;
?>
<!-- -------------------------------------------------------------------------->
<?php
//if ($row['timeEnd'] != '0000-00-00 00:00:00' && timeDiff_Hours($row['time'], $row['timeEnd']) > $row['pauseAfterHours'] && $row['breakCredit'] < $row['hoursOfRest']){
$sql = "SELECT * FROM $logTable INNER JOIN $userTable ON $logTable.userID = $userTable.id
WHERE enableProjecting = 'TRUE' AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(HOUR, time, timeEnd) > pauseAfterHours AND breakCredit < hoursOfRest AND status = '0'";

$result = $conn->query($sql);
if($result && $result->num_rows > 0):
?>
<p> Illegal Lunchbreaks: </p>
<a href="fixLunchbreak.php" target='_blank'><span>Autorepair (Refresh afterwards)</span></a><br><br>

<table>
  <th>Name</th>
  <th><?php echo $lang['TIME']; ?></th>
  <th><?php echo $lang['HOURS']; ?></th>
  <th><?php echo $lang['LUNCHBREAK']; ?></th>
  <th></th>

  <?php
  echo $result->num_rows . ' Invalid lunchbreaks to repair for users with booking-access: <br>';
  while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';
    echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
    echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']), 2, '.', '') .'</td>';
    echo '<td><input type=text size=2 name="lunchbreaks[]" value='.$row['breakCredit'].' ></td>';
    echo '<td><input type=text style=display:none name="lunchbreakIndeces[]" value='.$row['indexIM'].' ></td>';
    echo '</tr>';
  }
  ?>

</table>

<?php endif; echo mysqli_error($conn); ?>
<!-- -------------------------------------------------------------------------->

<?php
$sql = "SELECT $userTable.firstname, $userTable.lastname, $logTable.*
FROM $logTable
INNER JOIN $userTable ON $userTable.id = $logTable.userID
WHERE TIMESTAMPDIFF(HOUR, time, timeEnd) > 12 OR TIMESTAMPDIFF(HOUR, time, timeEnd) < 0";

$result = $conn->query($sql);
if($result && $result->num_rows > 0):
?>

<p>Illegal Timestamps: </p>

<table id='illTS'>
  <th>User</th>
  <th>Status</th>
  <th><?php echo $lang['TIME']; ?></th>
  <th><?php echo $lang['HOURS']; ?></th>
  <th>Autocorrect</th>

<?php
  while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';
    echo '<td>'. $lang_activityToString[$row['status']] .'</td>';
    echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
    echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']), 2, '.', '') .'</td>';
    echo '<td><input type=checkbox name="autoCorrects[]" value='.$row['indexIM'].' ></td>';
    echo '</tr>';
  }

?>

</table>
<br>
<input type='submit' name='autoCorrect' value='Autocorrect'><small> - For forgotten checkouts: Set end-time to match expected hours </small></input>
<br><br>
<br><br>

<?php
endif;
?>
<!-- -------------------------------------------------------------------------->



</form>
</body>
