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
    $sql = "INSERT INTO $logTable (time, userID, status, timeToUTC) VALUES (UTC_TIMESTAMP, $userID, '0', $timeToUTC);";
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}

function checkOut($userID) {
  require 'connection.php';
  $query = "SELECT time, indexIM FROM $logTable WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID AND status = '0' ";
  $result= mysqli_query($conn, $query);
  $row = $result->fetch_assoc();

  $indexIM = $row['indexIM'];
  $start = $row['time'];

  $sql = "UPDATE $logTable SET timeEND = UTC_TIMESTAMP WHERE indexIM = $indexIM;";
  $conn->query($sql);
  //auto-insert lunchbreak if user cannot book it himself.
  $result = $conn->query("SELECT canBook FROM $roleTable WHERE userID = $userID");
  $row = $result->fetch_assoc();
  if($row['canBook'] == 'FALSE'){
    //check if user was here for over 6h
    $sql = "SELECT hoursOfRest, pauseAfterHours FROM $intervaltable
    WHERE userID = $userID AND endDate IS NULL
    AND TIMESTAMPDIFF(MINUTE, '$start', UTC_TIMESTAMP) > (pauseAfterHours * 60)
    AND hoursOfRest > 0";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      $row = $result->fetch_assoc();
      $minutesOfRest = $row['hoursOfRest'] * 60;
      //check if he didnt fullfill the lunchbreak. Note: He did not fulfill the lunchbreak if there is no COMPLETE 0,5h break booking.
      $result2 = $conn->query("SELECT $projectBookingTable.id FROM $projectBookingTable WHERE bookingType = 'break' AND timestampID = $indexIM AND TIMESTAMPDIFF(MINUTE, start, end) >= $minutesOfRest ");
        if(!$result2 || $result2->num_rows <= 0){
          //Add pauseAfterHours to start, and add complete hoursOfRest to that
          $start = carryOverAdder_Minutes($start, $row['pauseAfterHours']*60);
          $end = carryOverAdder_Minutes($start, $minutesOfRest);
          //create the lunchbreak booking
          $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$start', '$end', $indexIM, 'Lunchbreak for $userID', 'break')";
          $conn->query($sql);
          echo mysqli_error($conn);

          //update timestamp
          $sql = "UPDATE $logTable SET breakCredit = (breakCredit + ".$row['hoursOfRest'].") WHERE indexIM = $indexIM";
          if($conn->query($sql)){
            echo "Unconsumed Lunchbreak detected.";
          } else {
            echo mysqli_error($conn);
          }
        }
      }
    } else {
      //or all was good, & he did nuttin wong.
      echo mysqli_error($conn);
    }
  }
?>
