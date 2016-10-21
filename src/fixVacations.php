<?php
require 'connection.php';
require 'createTimestamps.php';
$sql = "SELECT * FROM $logTable WHERE status = '1'";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
  $absolvedHours = timeDiff_Hours($row['time'], $row['timeEnd']);
  if($absolvedHours != $row['expectedHours']){
    $adjustedTime = carryOverAdder_Hours($row['time'], floor($row['expectedHours']));
    $adjustedTime = carryOverAdder_Hours($adjustedTime, ($row['expectedHours'] * 60) % 60);

    $sql = "UPDATE $logTable SET timeEnd = '$adjustedTime' WHERE indexIM =" .$row['indexIM'];
    $conn->query($sql);
  }
}
 ?>
