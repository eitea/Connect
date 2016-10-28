<?php
require 'connection.php';
$sql = "SELECT * FROM $logTable WHERE expectedHours IS NULL";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
  $day = strtolower(date('D', strtotime($row['time'])));
  $sql = "UPDATE $logTable INNER JOIN $bookingTable ON $bookingTable.userID = $logTable.userID
  SET expectedHours = $bookingTable.$day WHERE indexIM = " . $row['indexIM'];
  $conn->query($sql);
}
echo mysqli_error($conn);
