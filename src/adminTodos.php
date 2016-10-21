<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/table.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
</head>
<body>

<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}
require 'connection.php';
require 'language.php';

?>
<h1>Todos: </h1>
<p>Illegal Timestamps</p>
<table id='illTS'>
  <th>User</th>
  <th><?php echo $lang['TIME']; ?></th>
  <th>Status</th>
<?php
$sql = "SELECT $userTable.firstname, $userTable.lastname, $logTable.* FROM $logTable INNER JOIN $userTable ON $userTable.id = $logTable.userID WHERE TIMESTAMPDIFF(HOUR, time, timeEnd) > 12";
$result = $conn->query($sql);
if($result && $result->num_rows > 0){
  while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';
    echo '<td>'. $row['time'] .' - ' . $row['timeEnd'] .'</td>';

    echo '<td>'. $lang_activityToString[$row['status']] .'</td>';
    echo '</tr>';

  }
} else {
  echo mysqli_error($conn);
}
?>
</table>
</body>
