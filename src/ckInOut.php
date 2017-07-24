<?php
function checkIn($userID) {
  require 'connection.php';
  $timeToUTC =  $_SESSION['timeToUTC'];

  $sql = "SELECT * FROM $logTable WHERE userID = $userID AND time LIKE '".substr(getCurrentTimestamp(), 0, 10). " %'";
  $result = mysqli_query($conn, $sql);
  //user already has a stamp for today
  if($result && $result->num_rows > 0){
    $row = $result->fetch_assoc();
    if($row['status'] && $row['status'] != 5){ //mixed
      $conn->query("INSERT INTO mixedInfoData (timestampID, status, timeStart, timeEnd) VALUES(".$row['indexIM'].", '".$row['status']."', '".$row['time']."', '".$row['timeEnd']."')");
      $conn->query("UPDATE $logTable SET status = '5', time = '".getCurrentTimestamp()."', timeToUTC = $timeToUTC  WHERE indexIM =". $row['indexIM']);
    } else {
      //create a break stamping if youre not early (a silly admin edit)
      if(timeDiff_Hours($row['timeEnd'], getCurrentTimestamp()) > 0){
        $sql = "INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType) VALUES('".$row['timeEnd']."', '".getCurrentTimestamp()."', ".$row['indexIM'].", 'Checkin auto-break', 'break')";
        $conn->query($sql);
      }
    }
    //update timestamp
    $conn->query("UPDATE $logTable SET timeEnd = '0000-00-00 00:00:00' WHERE indexIM =". $row['indexIM']);
    echo mysqli_error($conn);
  } else { //create new stamp
    $sql = "INSERT INTO logs (time, userID, status, timeToUTC) VALUES (UTC_TIMESTAMP, $userID, '0', $timeToUTC);";
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}

function checkOut($userID) {
  require 'connection.php';
  $query = "SELECT time, indexIM FROM logs WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID ";
  $result= mysqli_query($conn, $query);
  $row = $result->fetch_assoc();

  $indexIM = $row['indexIM'];
  $start = $row['time'];

  $sql = "UPDATE $logTable SET timeEnd = UTC_TIMESTAMP WHERE indexIM = $indexIM;";
  $conn->query($sql);

  $result = $conn->query("SELECT * FROM $intervalTable WHERE userID = $userID AND endDate IS NULL");
  $interval_row = $result->fetch_assoc();

  $result = $conn->query("SELECT canBook FROM $roleTable WHERE userID = $userID");
  $row_canBook = $result->fetch_assoc();
  //add break if user cannot book, and was here long enough
  if($row_canBook['canBook'] == 'FALSE' && timeDiff_Hours($row['time'], getCurrentTimestamp()) > $interval_row['pauseAfterHours'] && $interval_row['hoursOfRest'] > 0){
    $minutesOfRest = $row['hoursOfRest'] * 60;
    $result = $conn->query("SELECT $projectBookingTable.id FROM $projectBookingTable WHERE bookingType = 'break' AND timestampID = $indexIM AND TIMESTAMPDIFF(MINUTE, start, end) >= $minutesOfRest ");
    //no existing lunchbreak found
    if(!$result || $result->num_rows <= 0){
      $start = carryOverAdder_Minutes($start, $row['pauseAfterHours']*60);
      $end = carryOverAdder_Minutes($start, $minutesOfRest);
      //create the lunchbreak booking
      $conn->query("INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$start', '$end', $indexIM, 'Lunchbreak for $userID', 'break')");
    }
  }
  return mysqli_error($conn);
}
?>
