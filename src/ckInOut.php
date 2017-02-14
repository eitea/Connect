<?php
function checkIn($userID) {
  require 'connection.php';
  $timeIsLikeToday = substr(getCurrentTimestamp(), 0, 10) . ' %';
  $timeToUTC =  $_SESSION['timeToUTC'];

  $sql = "SELECT * FROM $logTable WHERE userID = $userID
   AND status = '0'
   AND time LIKE '$timeIsLikeToday'";

   $result = mysqli_query($conn, $sql);
   if($result && $result->num_rows > 0){ //user already stamped in today
     $row = $result->fetch_assoc();
     $diff = timeDiff_Hours($row['timeEnd'], getCurrentTimestamp());
     if($diff <= 0){
       $diff = 0;
     }
     //create a break stamping
     $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('".$row['timeEnd']."', '".getCurrentTimestamp()."', ".$row['indexIM'].", 'Checkin auto-break', 'break')";
     $conn->query($sql);
     echo mysqli_error($conn);
     //update timestamp
     $sql = "UPDATE $logTable SET timeEnd = '0000-00-00 00:00:00', breakCredit = (breakCredit + $diff) WHERE indexIM =". $row['indexIM'];
     $conn->query($sql);
     echo mysqli_error($conn);
   } else { //create new stamp
     $sql = "SELECT * FROM $bookingTable WHERE userID = $userID";
     $result = $conn->query($sql);
     $row=$result->fetch_assoc();
     $expectedHours = $row[strtolower(date('D', strtotime(getCurrentTimestamp())))];
     $sql = "INSERT INTO $logTable (time, userID, status, timeToUTC, expectedHours) VALUES (UTC_TIMESTAMP, $userID, '0', $timeToUTC, $expectedHours);";
     $conn->query($sql);
     echo mysqli_error($conn);
   }
}

function checkOut($userID) {
  require 'connection.php';
  $query = "SELECT time, indexIM,breakCredit FROM $logTable WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID AND status = '0' ";
  $result= mysqli_query($conn, $query);
  $row = $result->fetch_assoc();

  $indexIM = $row['indexIM'];
  $breakCredit = $row['breakCredit'];
  $start = $row['time'];

  $sql = "UPDATE $logTable SET timeEND = UTC_TIMESTAMP WHERE indexIM = $indexIM;";
  $conn->query($sql);
  //auto-insert lunchbreak if user cannot book it himself.
  $result = $conn->query("SELECT canBook FROM $roleTable WHERE userID = $userID");
  $row = $result->fetch_assoc();
  if($row['canBook'] == 'FALSE'){
    //check if user was here for over 6h and didnt fullfill the lunchbreak.
    $sql = "SELECT * FROM $userTable
    WHERE $userTable.id = $userID
    AND TIMESTAMPDIFF(MINUTE, '$start', UTC_TIMESTAMP) > (pauseAfterHours * 60)
    AND hoursOfrest > $breakCredit";

    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      $row = $result->fetch_assoc();

      //take the missing time to fulfill the lunchbreak
      $minutes = ($row['hoursOfRest'] - $breakCredit) * 60;
      //create the lunchbreak booking
      $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$start', DATE_ADD('$start', INTERVAL $minutes MINUTE), $indexIM, 'Lunchbreak for $userID', 'break')";
      $conn->query($sql);
      echo mysqli_error($conn);

      //update timestamp
      $sql = "UPDATE $logTable SET breakCredit = (".$row['hoursOfRest'].") WHERE indexIM = $indexIM";
      $conn->query($sql);
      echo mysqli_error($conn);
    } else {
      //or all was good, & he did nuttin wong.
      echo mysqli_error($conn);
    }
  }
}

?>
