<?php
function checkIn($userID) {
  require 'connection.php';
  $timeToUTC =  $_SESSION['timeToUTC'];

  $sql = "SELECT * FROM $logTable WHERE userID = $userID AND time LIKE '".substr(getCurrentTimestamp(), 0, 10). " %'";
  $result = mysqli_query($conn, $sql);
  //user already has a stamp for today
  if($result && $result->num_rows > 0){
    $row = $result->fetch_assoc();
    $id = $row['indexIM'];
    if($row['status'] && $row['status'] != 5){ //mixed
      $conn->query("INSERT INTO mixedInfoData (timestampID, status, timeStart, timeEnd) VALUES($id, '".$row['status']."', '".$row['time']."', '".$row['timeEnd']."')");
      $conn->query("UPDATE $logTable SET status = '5', time = '".getCurrentTimestamp()."', timeToUTC = $timeToUTC  WHERE indexIM =". $row['indexIM']);
    } else {
      //create a break stamping if youre not early (a silly admin edit)
      if(timeDiff_Hours($row['timeEnd'], getCurrentTimestamp()) > 0){
        $sql = "INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType) VALUES('".$row['timeEnd']."', '".getCurrentTimestamp()."', $id, 'Checkin auto-break', 'break')";
        $conn->query($sql);
      }
    }
    //update timestamp
    $conn->query("UPDATE $logTable SET timeEnd = '0000-00-00 00:00:00' WHERE indexIM = $id");
    echo mysqli_error($conn);
  } else { //create new stamp
    $sql = "INSERT INTO logs (time, userID, status, timeToUTC) VALUES (UTC_TIMESTAMP, $userID, '0', $timeToUTC);";
    $conn->query($sql);
    echo mysqli_error($conn);
    $id = $conn->insert_id;
  }

  $conn->query("INSERT INTO checkinLogs (timestampID, remoteAddr, userAgent) VALUES($id, '".$_SERVER['REMOTE_ADDR']."', '".$_SERVER['HTTP_USER_AGENT']."')");
}

//will return empty if all was okay
function checkOut($userID, $emoji = 0) {
  require 'connection.php';
  $query = "SELECT time, indexIM FROM logs WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID ";
  $result= mysqli_query($conn, $query);
  $row = $result->fetch_assoc();

  $indexIM = $row['indexIM'];
  $start = $row['time'];
  $emoji = intval($emoji);

  $sql = "UPDATE $logTable SET timeEnd = UTC_TIMESTAMP, emoji = $emoji WHERE indexIM = $indexIM;";
  $conn->query($sql);
  return mysqli_error($conn);
}
?>
