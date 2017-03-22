<?php include 'header.php'; ?>
<?php enableToTime($userID);?>
<!-- BODY -->
<title>TODOs</title>
<div class="page-header">
  <h3><?php echo $lang['FOUNDERRORS']; ?></h3>
</div>
<?php

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
?>

<!--GENERAL REQUESTS -------------------------------------------------------------------------->

<?php
$sql ="SELECT * FROM $userRequests WHERE status = '0'";
$result = $conn->query($sql);
if($result && $result->num_rows > 0):
  ?>
  <h4> <?php echo $lang['UNANSWERED_REQUESTS']; ?>: </h4>

  <?php
  echo $result->num_rows . " Urlaubsanfrage: ";
  echo "<a href=allowVacations.php > Beantworten</a><br><hr><br>";
endif;
?>

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
