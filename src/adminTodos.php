<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/table.css">
  <link rel="stylesheet" href="../css/submitButt.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
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

<h1>Todos: </h1>
<p>Illegal Timestamps</p>
<table id='illTS'>
  <th>Autocorrect</th>
  <th>User</th>
  <th><?php echo $lang['TIME']; ?></th>
  <th><?php echo $lang['HOURS']; ?></th>
  <th>Status</th>

<?php
$sql = "SELECT $userTable.firstname, $userTable.lastname, $logTable.*
FROM $logTable
INNER JOIN $userTable ON $userTable.id = $logTable.userID
WHERE TIMESTAMPDIFF(HOUR, time, timeEnd) > 12 OR TIMESTAMPDIFF(HOUR, time, timeEnd) < 0";

$result = $conn->query($sql);
if($result && $result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td><input type=checkbox name="autoCorrects[]" value='.$row['indexIM'].' </td>';
    echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';
    echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
    echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']), 2, '.', '') .'</td>';
    echo '<td>'. $lang_activityToString[$row['status']] .'</td>';
    echo '</tr>';
  }
} else {
  echo mysqli_error($conn);
}
?>

</table>
<br>
<input type='submit' name='autoCorrect' value='Autocorrect'><small> - For forgotten checkouts: Set end-time to match expected hours </small></input>
<br><br>
<br><br>
</form>
</body>
