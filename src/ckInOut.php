<?php
function checkIn($userID) {
  require 'connection.php';
  $timeIsLikeToday = substr(getCurrentTimestamp(), 0, 10) . ' %';
  $timeToUTC =  $_SESSION['timeToUTC'];

  $sql = "SELECT * FROM $logTable WHERE userID = $userID AND time LIKE '$timeIsLikeToday'";
  $result = mysqli_query($conn, $sql);
  if($result && $result->num_rows > 0){ //user already has a stamp for today
    $row = $result->fetch_assoc();

    if($row['status'] && $row['status'] != 5){ //mixed
      //break timestamp down if not early (core time exception)
      if(timeDiff_Hours($row['time'], getCurrentTimestamp()) < 0){
        $sql = "INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType, mixedStatus) VALUES('".$row['time']."', '".getCurrentTimestamp()."', '".$row['indexIM']."', 'Checkin Time', 'mixed', '".$row['status']."')";
        $conn->query($sql);
      }
      $conn->query("UPDATE $logTable SET status = '5' WHERE indexIM =". $row['indexIM']);
      $conn->query("INSERT INTO mixedInfoData (timestampID, status, timeStart, timeEnd, isFillable) VALUES(".$row['indexIM'].", '".$row['status']."', '".$row['time']."', '".$row['timeEnd']."', 'TRUE')");
    } else {
      //create a break stamping if youre not early (a silly admin edit)
      if(timeDiff_Hours($row['time'], getCurrentTimestamp()) < 0){
        $sql = "INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType) VALUES('".$row['timeEnd']."', '".getCurrentTimestamp()."', ".$row['indexIM'].", 'Checkin auto-break', 'break')";
      }
      $conn->query($sql);
    }
    //update timestamp
    $sql = "UPDATE $logTable SET timeEnd = '0000-00-00 00:00:00', timeToUTC = '$timeToUTC' WHERE indexIM =". $row['indexIM'];
    $conn->query($sql);
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

  $sql = "UPDATE $logTable SET timeEND = UTC_TIMESTAMP WHERE indexIM = $indexIM;";
  $conn->query($sql);

  $result = $conn->query("SELECT * FROM $intervalTable WHERE userID = $userID AND endDate IS NULL");
  $interval_row = $result->fetch_assoc();

  $result = $conn->query("SELECT canBook FROM $roleTable WHERE userID = $userID");
  $row = $result->fetch_assoc();
  //add break if user cannot book, and was here long enough
  if($row['canBook'] == 'FALSE' && timeDiff_Hours($row['start'], $row['end']) > $row['pauseAfterHours'] && $row['hoursOfRest'] > 0){
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
