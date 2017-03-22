<?php include 'header.php'; ?>
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
      $result = $conn->query("SELECT pauseAfterHours, hoursOfRest FROM $intervalTable WHERE userID = $uId AND endDate IS NULL");
      if($result && ($row = $result->fetch_assoc())){
        $start = carryOverAdder_Minutes($row['time'], $row['pauseAfterHours'] * 60);
        $end = carryOverAdder_Minutes($start, $row['hoursOfRest'] * 60);
        $conn->query("INSERT INTO $projectBookingTable (timestampID, bookingType, start, end, infoText) VALUES($indexIM, 'break', '$start', '$end', 'Admin added missing lunchbreak')");
        $conn->query("UPDATE $logTable SET breakCredit = (breakCredit + ".$row['hoursOfRest'].") WHERE indexIM = $indexIM");
        echo mysqli_error($conn);
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
      if(($rowCanBook = $result->fetch_assoc()) && $rowCanBook['canBook'] == 'TRUE'){ //match last projectbooking
        $sql = "SELECT $projectBookingTable.end FROM $projectBookingTable
        WHERE ($projectBookingTable.timestampID = $indexIM AND $projectBookingTable.start LIKE '$date %' )";
        $result = mysqli_query($conn, $sql);
        if ($result && $result->num_rows > 0) { //does a booking exist?
          $rowLastBooking = $result->fetch_assoc();
          $adjustedTime = $rowLastBooking['end']; //adjust break later.
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
  if(isset($_POST['okay'])){
    $requestID = $_POST['okay'];
    $result = $conn->query("SELECT *, $intervalTable.id AS intervalID FROM $userRequests INNER JOIN $intervalTable ON $intervalTable.userID = $userRequests.userID
    WHERE status = '0' AND $userRequests.id = $requestID AND $intervalTable.endDate IS NULL");
    if($result && ($row = $result->fetch_assoc())){
      $i = $row['fromDate'];
      $days = (timeDiff_Hours($i, $row['toDate'])/24) + 1; //days
      for($j = 0; $j < $days; $j++){
        $expected = isHoliday($i) ? 0 : $row[strtolower(date('D', strtotime($i)))];
        if($expected != 0){ //only insert if expectedHours != 0
          $expectedHours = floor($expected);
          $expectedMinutes = ($expected * 60) % 60;
          $i2 = carryOverAdder_Hours($i, $expectedHours);
          $i2 = carryOverAdder_Minutes($i2, $expectedMinutes);
          $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status, breakCredit) VALUES('$i', '$i2', ".$row['userID'].", '0', '1', 0)";
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
    $answerText = $_POST['answerText'. $requestID];
    $conn->query("UPDATE $userRequests SET status = '1',answerText = '$answerText' WHERE id = $requestID");
  }
  if(isset($_POST['okay_acc'])){ //okay it and redirect to edit user
    $requestID = $_POST['okay_acc'];
    $conn->query("UPDATE $userRequests SET status = '2' WHERE id = $requestID");
  } elseif(isset($_POST['nokay_acc'])){ //delete account
    $requestID = $_POST['nokay_acc'];
    $conn->query("DELETE FROM $userTable WHERE id = $requestID"); //FK dependency will delete all requests etc.
  }
  if(isset($_POST['okay_log'])){

  } elseif(isset($_POST['nokay_log'])){

  }
}

?>

<!--GENERAL REQUESTS -------------------------------------------------------------------------->

<?php
$sql ="SELECT * FROM $userRequests WHERE status = '0'";
$result = $conn->query($sql);
if($result && $result->num_rows > 0):
  echo '<h4>'.$lang['UNANSWERED_REQUESTS'].': </h4><br>';
?>
  <form method="POST">
    <table class="table table-hover">
      <th>Request Type</th>
      <th>Name</th>
      <th><?php echo $lang['DATE']; ?></th>
      <th><?php echo $lang['REASON']; ?></th>
      <th class="text-center"><?php echo $lang['REPLY_TEXT']; ?></th>
      <tbody>
        <?php
        $sql = "SELECT * FROM $userRequests INNER JOIN $userTable ON $userTable.id = $userRequests.userID WHERE status = '0'";
        $result = $conn->query($sql);
        if($result && $result->num_rows > 0){
          while($row = $result->fetch_assoc()){
            echo '<tr>';
            echo '<td>'. $lang_requestToString[$row['requestType']].'</td>';
            echo '<td>'. $row['firstname']. ' ' .$row['lastname'] . '</td>';
            if($row['requestType'] == 'acc'){
              echo '<td>'. substr($row['fromDate'],0,10). '</td>';
              echo '<td> --- </td>';
              echo '<td class="text-center"><button type="submit" class="btn btn-default" name="okay_acc" value="'.$row['id'].'" > <img width=18px height=18px src="../images/okay.png"> </button> ';
              echo '<button type="submit" class="btn btn-default" name="nokay_acc" value="'.$row['userID'].'"> <img width=18px height=18px src="../images/not_okay.png"> </button></td>';
            } elseif($row['requestType'] == 'vac') {
              echo '<td>'. substr($row['fromDate'],0,10) . ' - ' . substr($row['toDate'],0,10) . '</td>';
              echo '<td>'. $row['requestText'].'</td>';
              echo '<td><div class="input-group">';
              echo '<input type=text class="form-control" name="answerText'.$row['id'].'" placeholder="Reply... (Optional)" />';
              echo '<span class="input-group-btn">';
              echo '<button type="submit" class="btn btn-default" name="okay" value="'.$row['id'].'" > <img width=18px height=18px src="../images/okay.png"> </button> ';
              echo '<button type="submit" class="btn btn-default" name="nokay" value="'.$row['id'].'"> <img width=18px height=18px src="../images/not_okay.png"> </button> ';
              echo '</span></div></td>';
            } elseif($row['requestType'] == 'log') {

            }
            echo '</tr>';
          }
        }
        ?>
     </tbody>
    </table>
  </form>
<br><hr><br>
<?php endif; ?>

<!--ILLEGAL LUNCHBREAK -------------------------------------------------------------------------->

<form method="POST">
  <?php //select all timestamps that do not have at least one complete break booking
  $sql = "SELECT indexIM, $logTable.userID, firstname, lastname, pauseAfterHours, hoursOfRest FROM $logTable l1 INNER JOIN $userTable ON l1.userID = $userTable.id $intervalTable ON l1.userID = $intervalTable.userID
  WHERE status = '0' AND endDate IS NULL AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, timeEND) > (pauseAfterHours * 60)
  AND !EXISTS(SELECT id FROM $projectBookingTable WHERE timestampID = l1.indexIM AND bookingType = 'break' AND (hoursOfRest * 60 DIV 1) <= TIMESTAMPDIFF(MINUTE, start, end))";
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
        Für die gelisteten Zeitstempel wurde keine Mittagspause gefunden.<br>
        Die Autokorrektur trägt eine vollständige Mittagspause nach (Diese Pause wird dazugerechnet).
      </div>
    </div>

    <table class="table table-hover">
      <th>Name</th>
      <th><?php echo $lang['TIME']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th><?php echo $lang['LUNCHBREAK']; ?></th>
      <th><input type="checkbox" onclick="toggle(this, 'lunchbreakIndeces');" /></th>
      <tbody>
        <?php
        while($row = $result->fetch_assoc()){
          echo '<tr>';
          echo '<td>'. $row['firstname'] .' ' . $row['lastname'] .'</td>';
          echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
          echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']), 2, '.', '') .'</td>';
          echo '<td>'. $row['breakCredit'].'</td>';
          echo '<td><input type="checkbox" name="lunchbreakIndeces[]" value='.$row['indexIM'].' ></td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
    <br>
    <button type='submit' class="btn btn-warning" name='saveNewBreaks' >Autocorrect</button>
    <br><hr><br>
  <?php echo mysqli_error($conn); endif;?>

  <!--ILLEGAL TIMESTAMPS -------------------------------------------------------------------------->

  <?php
  $sql = "SELECT $userTable.firstname, $userTable.lastname, $logTable.* FROM $logTable
  INNER JOIN $userTable ON $userTable.id = $logTable.userID
  WHERE (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60) > 22 OR (TIMESTAMPDIFF(MINUTE, time, timeEnd) - breakCredit*60) < 0";

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
      <th><?php echo $lang['BREAK']; ?></th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th>Autocorrect</th>
      <tbody>
        <?php
        while($row = $result->fetch_assoc()){
          echo '<tr>';
          echo '<td>'. $row['firstname'] .' '. $row['lastname'] .'</td>';
          echo '<td>'. $lang_activityToString[$row['status']] .'</td>';
          echo '<td>'. carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']) .'</td>';
          echo '<td>'. $row['breakCredit'].'</td>';
          echo '<td>'. number_format(timeDiff_Hours($row['time'], $row['timeEnd']) - $row['breakCredit'], 2, '.', '') .'</td>';
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

  <!--GEMINI -------------------------------------------------------------------------->

  <?php
  $sql = "SELECT * FROM $logTable l1, $userTable WHERE l1.userID = $userTable.id
  AND EXISTS(SELECT * FROM $logTable l2 WHERE DATE(l1.time) = DATE(l2.time) AND l1.userID = l2.userID AND l1.indexIM != l2.indexIM) ORDER BY l1.time DESC";

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
        Es existiert mehr als nur ein Zeitstempel für einen Benutzer an nur einem Tag.<br>
        Ein Benutzer darf allerdings pro Tag nur eine Art von Zeitstempel besitzen. <br>
        Bitte entscheiden Sie, welcher der beiden Zeitstempel gelöscht werden soll. Sie können auch beide Stempel löschen. <br>
        (Bemerkung: ZA ist kein Stempel)
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
        //dis is magic. do not touch
        while(true) {
          //uneven row handling
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
          echo $lang_activityToString[$row['status']] .' - '.$row['indexIM']. ' - ';
          echo carryOverAdder_Hours($row['time'], $row['timeToUTC']) .' - ' . carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']);
          echo '</div></td>';

          echo '<td><div class="checkbox">';
          echo '<input type=checkbox name="geminiIndeces[]" value="'.$row2['indexIM'].'" />';
          echo $lang_activityToString[$row2['status']] .' - '.$row2['indexIM']. ' - ';
          echo carryOverAdder_Hours($row2['time'], $row2['timeToUTC']) .' - '. carryOverAdder_Hours($row2['timeEnd'], $row2['timeToUTC']);
          echo '</div></td>';
          echo '</tr>';
          //uneven incrementation
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
  checkboxes = document.getElementsByName(target + '[]');
  for(var i = 0; i<checkboxes.length; i++) {
    checkboxes[i].checked = source.checked;
  }
}
</script>

<!-- /BODY -->
<?php include 'footer.php'; ?>
