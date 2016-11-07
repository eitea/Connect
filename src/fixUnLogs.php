<?php
require 'connection.php';

$sql = "SELECT * FROM $userTable";
$result = $conn->query($sql);
while($row = $result->fetch_assoc()){
  $userID = $row['id'];

  //fix1: remove all unlogs before entry date
  $entryDate = $row['beginningDate'];
  $sql = "DELETE FROM $negative_logTable WHERE userID = $userID AND time < '$entryDate'";
  $conn->query($sql);
  echo mysqli_error($conn);

}


?>
