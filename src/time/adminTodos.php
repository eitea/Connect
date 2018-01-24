<?php include dirname(__DIR__) . '/header.php'; ?>
<?php enableToTime($userID);?>
<!-- BODY -->
<title>TODOs</title>
<div class="page-header">
  <h3><?php echo $lang['FOUNDERRORS']; ?></h3>
</div>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  //illegal lunchbreaks
  if(isset($_POST['saveNewBreaks']) && !empty($_POST['lunchbreakIndeces'])){
    foreach($_POST['lunchbreakIndeces'] as $indexIM){
      $result = $conn->query("SELECT hoursOfRest, pauseAfterHours, $logTable.time FROM $intervalTable INNER JOIN $logTable ON $logTable.userID = $intervalTable.userID WHERE $logTable.indexIM = $indexIM AND endDate IS NULL");
      if($result && ($row = $result->fetch_assoc())){        
        $result_book = $conn->query("SELECT end FROM projectBookingData WHERE timestampID = $indexIM ORDER BY start DESC");
        if($result_book && ($row_book = $result_book->fetch_assoc())){
          //grab last booking
          $row_break['breakCredit'] = 0;
          $result_break = $conn->query("SELECT SUM(TIMESTAMPDIFF(MINUTE, start, end)) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = $indexIM");
          if($result_break && $result_break->num_rows > 0) $row_break = $result_break->fetch_assoc();
          $missingBreak = intval($row['hoursOfRest'] * 60 - $row_break['breakCredit']);
          //unexpected missing break error
          if($missingBreak < 0 || $missingBreak > $row['hoursOfRest']*60) {echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_UNEXPECTED'].": $missingBreak $indexIM </div>"; break;}
          $break_begin = $row_book['end'];
          $break_end = carryOverAdder_Minutes($break_begin, $missingBreak);
          $conn->query("INSERT INTO projectBookingData (start, end, bookingType, infoText, timestampID) VALUES ('$break_begin', '$break_end', 'break', 'Admin Autocorrected Lunchbreak', $indexIM)");
          echo mysqli_error($conn);
        } else {
          $break_begin = carryOverAdder_Minutes($row['time'], $row['pauseAfterHours'] * 60);
          $break_end = carryOverAdder_Minutes($break_begin, $row['hoursOfRest'] * 60);
          $conn->query("INSERT INTO projectBookingData (start, end, bookingType, infoText, timestampID) VALUES ('$break_begin', '$break_end', 'break', 'Admin Autocorrected Lunchbreak', $indexIM)");
          echo mysqli_error($conn);
        }
      }
    }
  }
  //repair forgotten check outs
  if(isset($_POST['autoCorrect']) && !empty($_POST['autoCorrects'])){
    foreach($_POST['autoCorrects'] as $indexIM){
      $result = $conn->query("SELECT $intervalTable.*, $logTable.time FROM $logTable, $intervalTable WHERE indexIM = $indexIM AND $logTable.userID = $intervalTable.userID AND endDate IS NULL");
      $row = $result->fetch_assoc();
      //date for query in projectbookingTable
      $date = substr($row['time'],0,10);
      //first, match expectedHours. if user has booking, overwrite this var
      $adjustedTime = carryOverAdder_Hours($row['time'], floor($row[strtolower(date('D', strtotime($row['time'])))]));
      $adjustedTime = carryOverAdder_Minutes($adjustedTime, (($row[strtolower(date('D', strtotime($row['time'])))] * 60) % 60));
      //adjust to match expectedHours OR last projectbooking, if any of these even exist
      $result = $conn->query("SELECT canBook FROM $roleTable WHERE userID = ".$row['userID']);
      if(($rowCanBook = $result->fetch_assoc()) && $rowCanBook['canBook'] == 'TRUE'){
        $sql = "SELECT $projectBookingTable.end FROM $projectBookingTable
        WHERE ($projectBookingTable.timestampID = $indexIM AND $projectBookingTable.start LIKE '$date %' )";
        $result = mysqli_query($conn, $sql);
        if($result && $result->num_rows > 0){ //does a booking exist?
          $rowLastBooking = $result->fetch_assoc();
          $adjustedTime = $rowLastBooking['end'];
        }
      }
      $conn->query("UPDATE $logTable SET timeEnd = '$adjustedTime' WHERE indexIM = $indexIM");
      echo mysqli_error($conn);
    }
  }
  //gemini
  if(isset($_POST['deleteGemini']) && !empty($_POST['geminiIndeces'])){
    foreach(array_unique($_POST['geminiIndeces']) as $indexIM){
      $sql = "DELETE FROM $logTable WHERE indexIM = $indexIM";
      $conn->query($sql);
      echo mysqli_error($conn);
    }
  }
  //user Requests
  if(isset($_POST['okay'])){ //vacation, special leave, compensatory time or school
    $requestID = $_POST['okay'];
    $result = $conn->query("SELECT *, $intervalTable.id AS intervalID FROM $userRequests INNER JOIN $intervalTable ON $intervalTable.userID = $userRequests.userID
    WHERE status = '0' AND $userRequests.id = $requestID AND $intervalTable.endDate IS NULL");
    if($result && ($row = $result->fetch_assoc())){
      $i = $row['fromDate'];
      $days = (timeDiff_Hours($i, $row['toDate'])/24) + 1; //days
      if($row['requestType'] == 'doc')$days = (timeDiff_Hours($i, $row['toDate'])/24); //days
      for($j = 0; $j < $days; $j++){
        $expected = isHoliday($i) ? 0 : $row[strtolower(date('D', strtotime($i)))];
        if($expected != 0){ //only insert if expectedHours != 0
          $expectedHours = floor($expected);
          $expectedMinutes = ($expected * 60) % 60;
          $i2 = carryOverAdder_Hours($i, $expectedHours);
          $i2 = carryOverAdder_Minutes($i2, $expectedMinutes);
          if($row['requestType'] == 'doc'){
            $coreTime = $conn->query("SELECT coreTime FROM userdata WHERE id =". $row['userID']);
            $coreTime = $coreTime->fetch_assoc()['coreTime'];
            $coreTime = new DateTime(date('Y-m-d',strtotime($row['fromDate']))." ".$coreTime);
            $fromTime =	new DateTime($row['fromDate']);
            $rndBoolForScience = false; // maybe he comes back after the appointment and continues to work
            if(($fromTime->diff($coreTime)->h>0||$fromTime->diff($coreTime)->i>0)&&$fromTime->diff($coreTime)->invert==0){
              $i = date('Y-m-d H:i:s',$coreTime->getTimestamp());
              if(timeDiff_Hours(date('Y-m-d H:i:s',$coreTime->getTimestamp()), $row['toDate'])<$expected){
                $i2 = $row['toDate'];
                
              }else{
                echo "<script>console.log('".date('Y-m-d H:i',$coreTime->getTimestamp())."')</script>";
                $i2 = carryOverAdder_Minutes(carryOverAdder_Hours(date('Y-m-d H:i:s',$coreTime->getTimestamp()), $expectedHours>$row['pauseAfterHours'] ? $expectedHours + $row['hoursOfRest'] : $expectedHours),$expectedMinutes);
                $rndBoolForScience = true;
              }
            }else{
              $i = $row['fromDate'];
              if(timeDiff_Hours(date('Y-m-d H:i:s',$coreTime->getTimestamp()), $row['toDate'])<$expected){
                $i2 = $row['toDate'];
                
              }else{
                echo "<script>console.log('".$expectedHours."')</script>";
                $i2 = carryOverAdder_Minutes(carryOverAdder_Hours(date('Y-m-d H:i:s',$coreTime->getTimestamp()), $expectedHours>$row['pauseAfterHours'] ? $expectedHours + $row['hoursOfRest'] : $expectedHours),$expectedMinutes);
                $rndBoolForScience = true;
              }
            }
            
            $alreadyWorked = $conn->query("SELECT indexIM, time, timeEnd  FROM $logTable WHERE userID = ".$row['userID']." AND DATE(time) = '".date('Y-m-d',$fromTime->getTimestamp())."'");
            if($alreadyWorked){
              $alreadyWorked = $alreadyWorked->fetch_assoc();
              $previousStart = new DateTime($alreadyWorked['time']);
              $previousEnd = new DateTime($alreadyWorked['timeEnd']);
              $newStart = new DateTime($i);
              $newEnd = new DateTime($i2);
              $i = $previousStart<$newStart ? date('Y-m-d H:i:s',$previousStart->getTimestamp()) : $i;
              if($previousEnd<$newEnd){
                if($rndBoolForScience){
                  $i2 = date('Y-m-d H:i:s',strtotime($i)+ (($expectedHours>$row['pauseAfterHours'] ? $expectedHours + $row['hoursOfRest'] : $expectedHours) *3600) + ($expectedMinutes*60));
                  echo "<script>console.log('".$i2."')</script>";
                }
              }else{
                $i2 = date('Y-m-d H:i:s',$previousEnd->getTimestamp());
              }
              $sql = "UPDATE $logTable SET time = '$i', timeEnd = '$i2', status = '5' WHERE indexIM = ".$alreadyWorked['indexIM'];
            }else{
              $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status) VALUES('$i', '$i2', ".$row['userID'].", '0', '5')";
            }
            
          }
          if($row['requestType'] == 'vac'){
            $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status) VALUES('$i', '$i2', ".$row['userID'].", '0', '1')";
          } elseif($row['requestType'] == 'scl'){
            $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status) VALUES('$i', '$i2', ".$row['userID'].", '0', '4')";
          } elseif($row['requestType'] == 'spl'){
            $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status) VALUES('$i', '$i2', ".$row['userID'].", '0', '2')";
          } elseif($row['requestType'] == 'cto'){
            $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status) VALUES('$i', '$i2', ".$row['userID'].", '0', '6')";
          }
          $conn->query($sql);
          echo mysqli_error($conn);
        }
        $i = carryOverAdder_Hours($i, 24);
      }
      $answerText = test_input($_POST['answerText'. $requestID]); //inputs are always set
      $conn->query("UPDATE $userRequests SET status = '2', answerText = '$answerText' WHERE id = $requestID");
    } else {
      echo $conn->error;
    }
  } elseif(isset($_POST['nokay'])){
    $requestID = $_POST['nokay'];
    $answerText = test_input($_POST['answerText'. $requestID]);
    $conn->query("UPDATE $userRequests SET status = '1',answerText = '$answerText' WHERE id = $requestID");
  }
  //account
  if(isset($_POST['okay_acc'])){
    $requestID = $_POST['okay_acc'];
    $conn->query("UPDATE $userRequests SET status = '2' WHERE id = $requestID");
    redirect("../system/users");
  } elseif(isset($_POST['nokay_acc'])){
    $requestID = $_POST['nokay_acc'];
    $conn->query("DELETE FROM $userTable WHERE id = $requestID"); //FK dependency will delete all requests etc.
  }
  //log
  if(isset($_POST['okay_log'])){
    $requestID = intval($_POST['okay_log']);
    $result = $conn->query("SELECT * FROM $userRequests WHERE id = $requestID");
    $row = $result->fetch_assoc();
    $timeStart = $row['fromDate'];
    $timeFin = $row['toDate'];
    $indexIM = $row['requestID'];
    $user = $row['userID'];
    if($indexIM != 0){
      if($timeFin != '0000-00-00 00:00:00'){
        $conn->query("UPDATE $logTable SET time = DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd = DATE_SUB('$timeFin', INTERVAL timeToUTC HOUR) WHERE indexIM = $indexIM");
      } else {
        $conn->query("UPDATE $logTable SET time = DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd = '$timeFin' WHERE indexIM = $indexIM");
      }
    } else { //timestamp doesnt exist
      $utcTime = $row['timeToUTC'];
      $timeStart = carryOverAdder_Hours($timeStart, $utcTime * -1);
      if($timeFin != '0000-00-00 00:00:00') $timeFin = carryOverAdder_Hours($timeFin, $utcTime * -1);
      $conn->query("INSERT INTO $logTable(time, timeEnd, userID, timeToUTC, status) VALUES('$timeStart', '$timeFin', $user, $utcTime , '0')");
    }
    if($conn->error){ echo $conn->error; } else {echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';}
    $answerText = $_POST['answerText'. $requestID];
    $conn->query("UPDATE $userRequests SET status = '2',answerText = '$answerText' WHERE id = $requestID");
  } elseif(isset($_POST['nokay_log'])){
    $requestID = $_POST['nokay_log'];
    $answerText = test_input($_POST['answerText'. $requestID]);
    $conn->query("UPDATE $userRequests SET status = '1',answerText = '$answerText' WHERE id = $requestID");
  }
  //break
  if(isset($_POST['okay_brk'])){ //does nothing
    $requestID = intval($_POST['okay_brk']);
    $answerText = test_input($_POST['answerText'. $requestID]);
    $conn->query("UPDATE $userRequests SET status = '2', answerText = '$answerText' WHERE id = $requestID");
  } elseif(isset($_POST['nokay_brk'])){
    $requestID = intval($_POST['nokay_brk']);
    $answerText = $_POST['answerText'. $requestID];
    $conn->query("UPDATE $userRequests SET status = '1', answerText = '$answerText' WHERE id = $requestID");
    $result = $conn->query("SELECT requestID FROM $userRequests WHERE id = $requestID");
    $row = $result->fetch_assoc();
    $bookingID = $row['requestID'];
    //delete break
    $conn->query("DELETE FROM $projectBookingTable WHERE id = $bookingID");
    echo $conn->error;
  }
  //splits
  if(isset($_POST['nokay_div'])){
    $requestID = intval($_POST['nokay_div']);
    $answerText = test_input($_POST['answerText'. $requestID]);
    $conn->query("UPDATE $userRequests SET status = '1', answerText = '$answerText' WHERE id = $requestID");
  } elseif(isset($_POST['okay_div'])){
    $requestID = intval($_POST['okay_div']);
    $result = $conn->query("SELECT * FROM $userRequests WHERE id = $requestID");
    $row = $result->fetch_assoc();
    $bookingID = $row['requestID'];
    $split_A = $row['fromDate'];
    $split_B = $row['toDate'];
    $splitting_activity = $row['requestText'];
    $result = $conn->query("SELECT id, timestampID, start, end, timeToUTC FROM $projectBookingTable INNER JOIN $logTable ON $logTable.indexIM = $projectBookingTable.timestampID WHERE id = $bookingID AND bookingType = 'break'");
    $row = $result->fetch_assoc();
    $row['start'] = substr($row['start'],0, 16).':00';
    $row['end'] = substr($row['end'],0, 16).':00';
    if($splitting_activity){
      //create mixed booking
      $conn->query("INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType, mixedStatus ) VALUES('$split_A', '$split_B', ".$row['timestampID'].", 'Split - $splitting_activity', 'mixed', '$splitting_activity') ");
      //update mixed log
      $conn->query("UPDATE $logTable SET status = '5' WHERE indexIM = ".$row['timestampID']);
    }
    //update break start time
    if(timeDiff_Hours($split_B, $row['end']) > 0){
      $conn->query("UPDATE $projectBookingTable SET start = '$split_B' WHERE id = ".$row['id']);
      echo mysqli_error($conn);
      //create second break
      if(timeDiff_Hours($row['start'], $split_A) > 0){ //break in the middle
        $conn->query("INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('".$row['start']."', '$split_A', ".$row['timestampID'].", 'Split Start', 'break') ");
      }
    } elseif(timeDiff_Hours($row['start'], $split_A) > 0) { //change start time
      $conn->query("UPDATE $projectBookingTable SET end = '$split_A' WHERE id = ".$row['id']);
    } else { //delete break
      $conn->query("DELETE FROM $projectBookingTable WHERE id = ".$row['id']);
    }
    $answerText = $_POST['answerText'. $requestID];
    $conn->query("UPDATE $userRequests SET status = '2', answerText = '$answerText' WHERE id = $requestID");
  }

  if(mysqli_error($conn)){
    echo mysqli_error($conn);
  }
}
?>

<!--GENERAL REQUESTS -------------------------------------------------------------------------->

<?php
$result = $conn->query("SELECT * FROM $userRequests WHERE status = '0'");
if($result && $result->num_rows > 0):
  echo '<h4>'.$lang['UNANSWERED_REQUESTS'].': </h4><br>';
?>
  <form method="POST">
    <table class="table table-hover">
      <th><?php echo $lang['REQUEST_TYPE']; ?></th>
      <th>Name</th>
      <th><?php echo $lang['TIMES']; ?></th>
      <th><?php echo $lang['REASON']; ?></th>
      <th class="text-center"><?php echo $lang['REPLY_TEXT']; ?></th>
      <tbody>
        <?php
        $sql = "SELECT $userRequests.*, $userTable.firstname, $userTable.lastname FROM $userRequests INNER JOIN $userTable ON $userTable.id = $userRequests.userID WHERE status = '0'";
        $result = $conn->query($sql);
        if($result && $result->num_rows > 0){
          while($row = $result->fetch_assoc()){
            echo '<tr>';
            echo '<td>'. $lang['REQUEST_TOSTRING'][$row['requestType']].'</td>';
            echo '<td>'. $row['firstname']. ' ' .$row['lastname'] . '</td>';
            if($row['requestType'] == 'acc'){
              echo '<td>'. substr($row['fromDate'],0,10). '</td>';
              echo '<td> --- </td>';
              echo '<td class="text-center"><button type="submit" class="btn btn-default" name="okay_acc" value="'.$row['id'].'" > <img width=18px height=18px src="../images/okay.png"> </button> ';
              echo '<button type="submit" class="btn btn-default" name="nokay_acc" value="'.$row['userID'].'"> <img width=18px height=18px src="../images/not_okay.png"> </button></td>';
            } elseif($row['requestType'] == 'vac' || $row['requestType'] == 'spl' || $row['requestType'] == 'scl' || $row['requestType'] == 'cto') {
              echo '<td>'. substr($row['fromDate'],0,10) . ' - ' . substr($row['toDate'],0,10) . '</td>';
              echo '<td>'. $row['requestText'].'</td>';
              echo '<td><div class="input-group">';
              echo '<input type="text" class="form-control" name="answerText'.$row['id'].'" placeholder="Reply... (Optional)" />';
              echo '<span class="input-group-btn">';
              echo '<button type="submit" class="btn btn-default" name="okay" value="'.$row['id'].'" > <img width="18px" height="18px" src="../images/okay.png"> </button> ';
              echo '<button type="submit" class="btn btn-default" name="nokay" value="'.$row['id'].'"> <img width="18px" height="18px" src="../images/not_okay.png"> </button> ';
              echo '</span></div></td>';
            } elseif($row['requestType'] == 'log') {
              $result = $conn->query("SELECT * FROM logs WHERE indexIM = ".$row['requestID']);
              $oldRow = $result->fetch_assoc();
              echo '<td>'. $lang['PREVIOUS'].': '.substr(carryOverAdder_Hours($oldRow['time'], $oldRow['timeToUTC']), 0, 16).' - '.substr(carryOverAdder_Hours($oldRow['timeEnd'], $oldRow['timeToUTC']), 11,5).'<br>'.
               $lang['NEW'].': '.substr($row['fromDate'],0,16).' - ' .substr($row['toDate'],11,5). '</td>';
              echo '<td>'. $row['requestText'].'</td>';
              echo '<td><div class="input-group">';
              echo '<input type="text" class="form-control" name="answerText'.$row['id'].'" placeholder="Reply... (Optional)" />';
              echo '<span class="input-group-btn">';
              echo '<button type="submit" class="btn btn-default" name="okay_log" value="'.$row['id'].'" > <img width=18px height=18px src="../images/okay.png"> </button> ';
              echo '<button type="submit" class="btn btn-default" name="nokay_log" value="'.$row['id'].'"> <img width=18px height=18px src="../images/not_okay.png"> </button> ';
              echo '</span></div></td>';
            } elseif($row['requestType'] == 'brk') {
              echo '<td>'. substr($row['fromDate'],0,16) . ' - ' . substr($row['toDate'],11,5) . '</td>';
              echo '<td></td>';
              echo '<td><div class="input-group">';
              echo '<input type="text" class="form-control" name="answerText'.$row['id'].'" placeholder="Reply... (Optional)" />';
              echo '<span class="input-group-btn">';
              echo '<button type="submit" class="btn btn-default" name="okay_brk" value="'.$row['id'].'" > <img width=18px height=18px src="../images/okay.png"> </button> ';
              echo '<button type="submit" class="btn btn-default" name="nokay_brk" value="'.$row['id'].'"> <img width=18px height=18px src="../images/not_okay.png"> </button> ';
              echo '</span></div></td>';
            } elseif($row['requestType'] == 'div'){
              echo '<td>'. substr($row['fromDate'],0,16) . ' - ' . substr($row['toDate'],11,5).' (utc)</td>';
              echo '<td>'. $lang['ACTIVITY_TOSTRING'][$row['requestText']].'</td>';
              echo '<td><div class="input-group">';
              echo '<input type="text" class="form-control" name="answerText'.$row['id'].'" placeholder="Reply... (Optional)" />';
              echo '<span class="input-group-btn">';
              echo '<button type="submit" class="btn btn-default" name="okay_div" value="'.$row['id'].'" > <img width="18px" height="18px" src="../images/okay.png"> </button> ';
              echo '<button type="submit" class="btn btn-default" name="nokay_div" value="'.$row['id'].'"> <img width="18px" height="18px" src="../images/not_okay.png"> </button> ';
              echo '</span></div></td>';
            }elseif($row['requestType'] == 'doc'){
              echo '<td>'. substr($row['fromDate'],0,16) . ' - ' . substr($row['toDate'],11,5).' (utc)</td>';
              echo '<td></td>';
              echo '<td><div class="input-group">';
              echo '<input type="text" class="form-control" name="answerText'.$row['id'].'" placeholder="Reply... (Optional)" />';
              echo '<span class="input-group-btn">';
              echo '<button type="submit" class="btn btn-default" name="okay" value="'.$row['id'].'" > <img width="18px" height="18px" src="../images/okay.png"> </button> ';
              echo '<button type="submit" class="btn btn-default" name="nokay" value="'.$row['id'].'"> <img width="18px" height="18px" src="../images/not_okay.png"> </button> ';
              echo '</span></div></td>';
            }
            echo '</tr>';
          }
        }
        ?>
     </tbody>
    </table>
  </form>
<?php endif; ?>
  <div class="container-fluid"><div class="text-right"><a href="requests"><?php echo $lang['REQUESTS'].' '.$lang['OVERVIEW']; ?></a></div></div>
<br><hr><br>
<form method="POST">

  <!--ILLEGAL TIMESTAMPS -------------------------------------------------------------------------->
  <?php
  $sql = "SELECT $userTable.firstname, $userTable.lastname, $logTable.* FROM $logTable
  INNER JOIN $userTable ON $userTable.id = $logTable.userID
  WHERE (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60) > 22 OR TIMESTAMPDIFF(MINUTE, time, timeEnd) < 0";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0):
  ?>
    <h4><?php echo $lang['ILLEGAL_TIMESTAMPS']; ?>:</h4>
    <div class="h4 text-right">
      <a role="button" data-toggle="collapse" href="#illegal_timestamp_info" aria-expanded="false" aria-controls="illegal_lunchbreak_info">
        <i class="fa fa-info-circle"></i>
      </a>
    </div>
    <div class="collapse" id="illegal_timestamp_info">
      <div class="well">
        Die Dauer des Zeitstempels beträgt über 22, oder weniger als 0 Stunden. <br>
        Die Autokorrektur passt die Endzeit der Zeitstempel den geleisteten bzw. erwarteten Stunden inkl. der Mittagspause an. <br>
        Autokorrektur wird empfohlen für Zeitstempel, dessen Differenz von Anfangs- u. Endzeit nicht negativ ist.
      </div>
    </div>
    <table id='illTS' class="table table-hover">
      <th>User</th>
      <th>Status</th>
      <th><?php echo $lang['TIME']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th>Autocorrect</th>
      <tbody>
        <?php
        while($row = $result->fetch_assoc()){
          echo '<tr>';
          echo '<td>'. $row['firstname'] .' '. $row['lastname'] .'</td>';
          echo '<td>'. $lang['ACTIVITY_TOSTRING'][$row['status']] .'</td>';
          echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
          echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']), 2, '.', '') .'</td>';
          echo '<td><input type=checkbox name="autoCorrects[]" value='.$row['indexIM'].' ></td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
    <br>
    <button type='submit' class="btn btn-warning" name='autoCorrect'>Autocorrect</button>
    <br><hr><br>
  <?php endif;  ?>

  
<!--ILLEGAL LUNCHBREAK -------------------------------------------------------------------------->
  <?php
  $sql = "SELECT l1.*, firstname, lastname, pauseAfterHours, hoursOfRest FROM logs l1
  INNER JOIN UserData ON l1.userID = UserData.id INNER JOIN intervalData ON UserData.id = intervalData.userID
  WHERE (status = '0' OR status ='5') AND endDate IS NULL AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, timeEnd) > (pauseAfterHours * 60) 
  AND hoursOfRest * 60 > (SELECT IFNULL(SUM(TIMESTAMPDIFF(MINUTE, start, end)),0) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = l1.indexIM)";

  $result = $conn->query($sql);
  if($result && $result->num_rows > 0):
  ?>
    <h4> <?php echo $lang['ILLEGAL_LUNCHBREAK']; ?>:</h4>
    <div class="h4 text-right">
      <a role="button" data-toggle="collapse" href="#illegal_lunchbreak_info" aria-expanded="false" aria-controls="illegal_lunchbreak_info">
        <i class="fa fa-info-circle"></i>
      </a>
    </div>
    <div class="collapse" id="illegal_lunchbreak_info">
      <div class="well">
        <?php echo $lang['INFO_ILLEGAL_LUNCHBREAK']; ?>
      </div>
    </div>

    <table class="table table-hover">
      <th>Name</th>
      <th><?php echo $lang['TIME']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th><input type="checkbox" onchange="toggle(this, 'lunchbreakIndeces[]');" /></th>
      <tbody>
        <?php
        while($row = $result->fetch_assoc()){
          echo '<tr>';
          echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';
          echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
          echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']), 2, '.', '') .'</td>';
          echo '<td><input type="checkbox" name="lunchbreakIndeces[]" value='.$row['indexIM'].' ></td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
    <br>
    <button type='submit' class="btn btn-warning" name='saveNewBreaks' >Autocorrect</button>
    <br><hr><br>
  <?php endif; echo mysqli_error($conn);?>

  <!--GEMINI -------------------------------------------------------------------------->

  <?php
  $sql = "SELECT * FROM $logTable l1, $userTable WHERE l1.userID = $userTable.id
  AND EXISTS(SELECT * FROM $logTable l2 WHERE DATE(DATE_ADD(l1.time, INTERVAL timeToUTC  hour)) = DATE(DATE_ADD(l2.time, INTERVAL timeToUTC  hour)) AND l1.userID = l2.userID AND l1.indexIM != l2.indexIM) ORDER BY l1.time DESC";

  $result = $conn->query($sql);
  if($result && $result->num_rows > 0):
    ?>
    <h4><?php echo $lang['ILLEGAL_TIMESTAMPS']; ?>: Gemini</h4>
    <div class="h4 text-right">
      <a role="button" data-toggle="collapse" href="#illegal_gemini_info" aria-expanded="false" aria-controls="illegal_lunchbreak_info">
        <i class="fa fa-info-circle"></i>
      </a>
    </div>
    <div class="collapse" id="illegal_gemini_info">
      <div class="well">
        <?php echo $lang['INFO_GEMINI']; ?>
      </div>
    </div>
    <table id='dubble' class="table table-hover">
      <th>User</th>
      <th width=40%><?php echo $lang['TIMESTAMPS']; ?> 1</th>
      <th width=40%><?php echo $lang['TIMESTAMPS']; ?> 2</th>
      <tbody>
        <?php
        $rowDP = $result->fetch_assoc();
        $rowDP2 = $result->fetch_assoc();
        $uneven = $rowDP;
        //not sure how this works
        while(true) {
          $row = $rowDP;
          if($rowDP['userID'] != $rowDP2['userID']){
            $row2 = $uneven;
          } else {
            $row2 = $rowDP2;
          }
          echo '<tr>';
          echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';

          echo '<td><div class="checkbox">';
          echo '<input type=checkbox name="geminiIndeces[]" value="'.$row['indexIM'].'" />';
          echo $lang['ACTIVITY_TOSTRING'][$row['status']] .' - '.$row['indexIM']. ' - ';
          echo carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']);
          echo '</div></td>';

          echo '<td><div class="checkbox">';
          echo '<input type=checkbox name="geminiIndeces[]" value="'.$row2['indexIM'].'" />';
          echo $lang['ACTIVITY_TOSTRING'][$row2['status']] .' - '.$row2['indexIM']. ' - ';
          echo carryOverAdder_Hours($row2['time'], $row2['timeToUTC']) .' - '. carryOverAdder_Hours($row2['timeEnd'], $row2['timeToUTC']);
          echo '</div></td>';
          echo '</tr>';
          if($rowDP['userID'] == $rowDP2['userID']){
            $uneven = $rowDP;
            if(!($rowDP = $result->fetch_assoc()) || !($rowDP2 = $result->fetch_assoc())){
              break;
            }
          } else {
            $rowDP = $result->fetch_assoc();
          }
        }
        ?>
      </tbody>
    </table>
    <br>
    <button type='submit' class="btn btn-warning" name='deleteGemini'><?php echo $lang['DELETE']; ?></button>
    <br><hr><br>
    <?php
  endif;
  echo mysqli_error($conn);
  ?>
  <!-- -------------------------------------------------------------------------->
</form>

<script>
function toggle(source, target) {
  if(source.checked){
    $("[name='"+target+"']").prop('checked', true);
  } else {
    $("[name='"+target+"']").prop('checked', false);
  }
}
</script>

<!-- /BODY -->
<?php include dirname(__DIR__) . '/footer.php'; ?>
