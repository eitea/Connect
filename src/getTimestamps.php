<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToTime($userID); ?>
<!-- BODY -->
<link rel="stylesheet" type="text/css" href="../plugins/dhtmlxCalendar/codebase/dhtmlxcalendar.css">
<script src="../plugins/dhtmlxCalendar/codebase/dhtmlxcalendar.js"> </script>

<div class="page-header">
  <h3><?php echo $lang['TIMESTAMPS']; ?></h3>
</div>

<?php
$filterDate = substr(getCurrentTimestamp(),0,7); //granularity: default is year and month
$filterID = 0;
$filterStatus ='';

if (!empty($_POST['filteredUserID'])) {
  $filterID = $_POST['filteredUserID'];
}

if(!empty($_POST['filterYear'])){
  $filterDate = $_POST['filterYear'];
  if(!empty($_POST['filterMonth'])){
    $filterDate .= '-' . $_POST['filterMonth'];
  }
}

if (isset($_POST['filterStatus'])) {
  $filterStatus = $_POST['filterStatus'];
}
?>

<form method="post">
  <select name='filteredUserID' style="width:200px" class="js-example-basic-single">
    <?php
    $query = "SELECT * FROM $userTable;";
    $result = mysqli_query($conn, $query);

    echo "<option name=filterUserID value=0>User...</option>";
    while($row = $result->fetch_assoc()){
      $i = $row['id'];
      if ($filterID == $i) {
        echo "<option name=filterUserID value=$i selected>".$row['firstname'] . " " . $row['lastname']."</option>";
      } else {
        echo "<option name=filterUserID value=$i>".$row['firstname'] . " " . $row['lastname']."</option>";
      }
    }
    ?>
  </select>
  <select name='filterStatus' style="width:100px" class="js-example-basic-single">
    <option value="" >---</option>
    <option value="0" <?php if($filterStatus == '0'){echo 'selected';} ?>><?php echo $lang_activityToString[0]; ?></option>
    <option value="1" <?php if($filterStatus == '1'){echo 'selected';} ?>><?php echo $lang_activityToString[1]; ?></option>
    <option value="2" <?php if($filterStatus == '2'){echo 'selected';} ?>><?php echo $lang_activityToString[2]; ?></option>
    <option value="3" <?php if($filterStatus == '3'){echo 'selected';} ?>><?php echo $lang_activityToString[3]; ?></option>
  </select>

  <select name='filterYear' style="width:100px" class="js-example-basic-single">
    <?php
    for($i = substr($filterDate,0,4)-4; $i < substr($filterDate,0,4)+4; $i++){
      $selected = ($i == substr($filterDate,0,4))?'selected':'';
      echo "<option $selected value=$i>$i</option>";
    }
    ?>
  </select>

  <select name='filterMonth' style="width:100px" class="js-example-basic-single">
    <option value="">---</option>
    <?php
    for($i = 1; $i < 13; $i++) {
      $selected= '';
      if ($i == substr($filterDate,5,2)) {
        $selected = 'selected';
      }
      $dateObj = DateTime::createFromFormat('!m', $i);
      $option = $dateObj->format('F');
      echo "<option $selected name=filterUserID value=".sprintf("%02d",$i).">$option</option>";
    }
    ?>
  </select>

  <button type="submit" class="btn btn-sm btn-warning" name="filter">Filter</button>

  <br><br>

  <!-- ####################################################################### -->
  <?php if($filterID != 0): ?>
    <div class="container-fluid">
      <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#home">Detail</a></li>
        <li><a data-toggle="tab" href="#menu1"><?php echo $lang['OVERVIEW']; ?></a></li>
      </ul>
      <div class="tab-content">
        <div id="home" class="tab-pane fade in active">
          <br>

          <?php
          if ($_SERVER["REQUEST_METHOD"] == "POST") {
            if (isset($_POST['saveChanges'])) {
              for ($i = 0; $i < count($_POST['editingIndecesIM']); $i++) {
                $imm = $_POST['editingIndecesIM'][$i];
                $timeStart = $_POST['timesFrom'][$i] .':00';
                $timeFin = $_POST['timesTo'][$i] .':00';

                if($timeFin != '0000-00-00 00:00:00'){
                  $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd=DATE_SUB('$timeFin', INTERVAL timeToUTC HOUR) WHERE indexIM = $imm";
                } else {
                  $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd='$timeFin' WHERE indexIM = $imm";
                }

                $conn->query($sql);
                echo mysqli_error($conn);

              }
            } elseif (isset($_POST['delete']) && isset($_POST['index'])) {
              $index = $_POST["index"];
              foreach ($index as $x) {
                //deleting a timestamp should create an unlog
                $sql = "SELECT expectedHours, userID, time FROM $logTable WHERE indexIM = $x";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                $day = strtolower(date('D', strtotime($row['time'])));
                $sql = "INSERT INTO $negative_logTable(userID, $day) VALUES(".$row['userID'].", '".$row['expectedHours']."')";
                $conn->query($sql);
                echo mysqli_error($conn);

                $sql = "DELETE FROM $logTable WHERE indexIM=$x;";
                $conn->query($sql);
                echo mysqli_error($conn);
              }
            } elseif (isset($_POST['create']) && !empty($_POST['creatFromTime']) && !empty($_POST['creatToTime']) && $_POST['creatFromTime'] != '0000-00-00 00:00') {
              if($_POST['creatToTime'] == '0000-00-00 00:00' || timeDiff_Hours($_POST['creatFromTime'], $_POST['creatToTime']) > 0) {

                $activtiy = $_POST['action'];
                $timeIsLike = substr($_POST['creatFromTime'], 0, 10) ." %";
                $timeToUTC = $_POST['creatTimeZone'];

                $timeBegin = carryOverAdder_Hours($_POST['creatFromTime'] .':00', ($_POST['creatTimeZone']*-1)); //UTC

                if($_POST['creatToTime'] != '0000-00-00 00:00'){
                  $timeEnd = carryOverAdder_Hours($_POST['creatToTime'] .':00', ($_POST['creatTimeZone']*-1)); //UTC
                } else {
                  $timeEnd = '0000-00-00 00:00:00';
                }

                //gotta see if there already is a timestamp for that day
                $sql = "SELECT * FROM $logTable WHERE userID = $filterID
                AND status = '$activtiy'
                AND time LIKE '$timeIsLike'";

                $result = mysqli_query($conn, $sql);
                if($result && $result->num_rows > 0){ //user already stamped in today
                  $row = $result->fetch_assoc();

                  $start = $row['timeEnd'];
                  $indexIM = $row['indexIM'];

                  $diff = timeDiff_Hours($start, $timeBegin); //beginning of new timestamp has to be later than the end of the existing timestamp and existing timestamp has to be be closed
                  if($start != '0000-00-00 00:00:00' && $diff > 0){
                    //create a break stamp only if its about status 0
                    if($activity == 0){
                      $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$start', '$timeBegin', $indexIM, 'Create auto-break', 'break')";
                      $conn->query($sql);
                      echo mysqli_error($conn);
                    }

                    //update breakCredit and new endTime
                    $sql = "UPDATE $logTable SET timeEnd = '$timeEnd', breakCredit = (breakCredit + $diff) WHERE indexIM =". $row['indexIM'];
                    $conn->query($sql);
                    echo mysqli_error($conn);
                  } else {
                    echo "ERROR - Merging timestamps of same dates: time difference was less or equal 0. Check your times and see if existing timestamp has been closed.";
                  }
                } else { //no existing timestamp yet - create a new stamp

                  //creating a new timestamp should delete absent file if it exists, not caring about the status
                  $sql = "DELETE FROM $negative_logTable WHERE userID = $filterID AND time LIKE '$timeIsLike'";
                  $conn->query($sql);

                  //get expected Hours
                  $sql = "SELECT * FROM $bookingTable WHERE userID = $filterID";
                  $result = $conn->query($sql);
                  $row=$result->fetch_assoc();
                  $expectedHours = $row[strtolower(date('D', strtotime(getCurrentTimestamp())))];
                  $sql = "INSERT INTO $logTable (time, timeEnd, userID, status, timeToUTC, expectedHours) VALUES('$timeBegin', '$timeEnd', $filterID, '$activtiy', '$timeToUTC', '$expectedHours');";
                  $conn->query($sql);
                }
              } else {
                echo "Invalid Timestamps or no user selected";
              }
            }
          }
          ?>

          <table class="table table-striped table-condensed text-center">
            <tr>
              <th><?php echo $lang['DELETE']; ?></th>
              <th><?php echo $lang['WEEKLY_DAY']; ?></th>
              <th><?php echo $lang['ACTIVITY']; ?></th>
              <th width=140px><?php echo $lang['FROM']; ?></th>
              <th><?php echo $lang['LUNCHBREAK']; ?></th>
              <th width=140px><?php echo $lang['TO']; ?></th>

              <th><?php echo $lang['SHOULD_TIME']; ?></th>
              <th><?php echo $lang['IS_TIME']; ?></th>
              <th><?php echo $lang['SUM']; ?></th>
              <th><?php echo $lang['DIFFERENCE']; ?></th>
            </tr>

            <?php
            if(empty($filterStatus)){
              $filterStatusAdd = "";
            } else {
              $filterStatusAdd = "AND status = '$filterStatus'";
            }

            $absolvedHoursSUM = $expectedHoursSUM = $lunchbreakSUM = $saldoSUM = $isTimeSUM = 0;
            $sql = "SELECT * FROM $logTable WHERE userID = $filterID AND time LIKE '$filterDate%' $filterStatusAdd ";
            $result = mysqli_query($conn, $sql);
            if($result && $result->num_rows >0) {
              while($row = $result->fetch_assoc()){

                $A = carryOverAdder_Hours($row['time'], $row['timeToUTC']);

                if($row['timeEnd'] == '0000-00-00 00:00:00'){
                  $B = '0000-00-00 00:00:00';
                  $difference = timeDiff_Hours($row['time'], getCurrentTimestamp());
                } else {
                  $B = carryOverAdder_Hours($row['timeEnd'], $row['timeToUTC']);
                  $difference = timeDiff_Hours($A, $B);
                }

                $k = $row['indexIM'];

                echo "<tr>";
                echo "<td><input type='checkbox' name='index[]' value= ".$k."></td>";
                echo "<td>". $lang_weeklyDayToString[strtolower(date('D', strtotime($A)))] . "</td>";
                echo "<td>" . $lang_activityToString[$row['status']] . "</td>";

                echo "<td><input type='text' class='form-control input-sm' maxlength='16' onkeydown='if (event.keyCode == 13) return false;' name='timesFrom[]' value='" . substr($A,0,-3) . "'></td>";
                echo "<td class='text-center'>" . $row['breakCredit'] . "</td>";
                echo "<td><input type='text' class='form-control input-sm' maxlength='16' onkeydown='if (event.keyCode == 13) return false;' name='timesTo[]' value='" . substr($B,0,-3) . "'></td>";

                echo "<td>" . $row['expectedHours'] . "</td>";
                echo "<td>" . sprintf('%.2f', $difference) . "</td>";
                echo "<td>" . sprintf('%.2f', $difference - $row['breakCredit']) . "</td>";
                echo "<td>" . sprintf('%+.2f', $difference - $row['expectedHours']  - $row['breakCredit']) . "</td>";

                echo '<td class="hidden"><input type="text" style="display:none;" name="editingIndecesIM[]" value="' . $k . '"></td>';
                echo "</tr>";

                $absolvedHoursSUM += $difference -  $row['breakCredit'];
                $expectedHoursSUM +=  $row['expectedHours'];
                $lunchbreakSUM +=  $row['breakCredit'];
                $saldoSUM += $difference -  $row['expectedHours'] -  $row['breakCredit'];
                $isTimeSUM += $difference;
              }
            }


            echo "<tr style=font-weight:bold;>
            <td>Sum: </td>
            <td>-</td> <td>-</td>
            <td>".sprintf('%.2f',$lunchbreakSUM)."</td> <td>-</td>
            <td>".sprintf('%.2f',$expectedHoursSUM)."</td>
            <td>".sprintf('%.2f', $isTimeSUM)."</td>
            <td>".sprintf('%.2f',$absolvedHoursSUM)."</td>
            <td>".sprintf('%+.2f',$saldoSUM)."</td> <td></td></tr>";
            ?>

            <script>
            $("[data-toggle=popover]").popover({html:true})
            </script>

          </table>

          <br>
          <?php if($filterID != 0) : ?>
            <div class="container-fluid">
              <span class="blockInput">
                <select name="action" class="js-example-basic-single">
                  <option name="act" value="0">Work</option>
                  <option name="act" value="1">Vacation</option>
                  <option name="act" value="2">Special Leave</option>
                  <option name="act" value="3">Sick</option>
                </select>

                <select name="creatTimeZone" class="js-example-basic-single" style=width:100px>
                  <?php
                  for($i = -12; $i <= 12; $i++){
                    if($i == $timeToUTC){
                      echo "<option name='ttz' value= $i selected>UTC " . sprintf("%+03d", $i) . "</option>";
                    } else {
                      echo "<option name='ttz' value= $i>UTC " . sprintf("%+03d", $i) . "</option>";
                    }
                  }
                  ?>
                </select>

                <div class="col-xs-6 col-md-5">
                  <div class="input-group input-daterange">
                    <span class="input-group-btn">
                      <button class="btn btn-warning" type="submit" name="create"> + </button>
                    </span>
                    <input id="calendar" type="text" class="form-control datepick" value="" placeholder="Von" size='16' maxlength=16 name="creatFromTime">
                    <span class="input-group-addon"> - </span>
                    <input id="calendar2" type="text" class="form-control datepick" value="" placeholder="Bis" size='16' maxlength=16  name="creatToTime">
                  </div>
                </div>
                <script>
                var myCalendar = new dhtmlXCalendarObject(["calendar","calendar2"]);
                myCalendar.setSkin("material");
                myCalendar.setDateFormat("%Y-%m-%d %H:%i");
                </script>

                <div class="text-right">
                  <button type="submit" class="btn btn-warning" name="delete"><?php echo $lang['DELETE']; ?></button>
                  <button  type="submit" class="btn btn-warning" name="saveChanges"> Save </button>
                </div>
              </div>
            </span>

          <?php endif; ?>
        <?php else: ?>
          <div class="alert alert-info" role="alert"><strong><?php echo $lang['MANDATORY_SETTINGS']; ?>: </strong>Wähle Benutzer und Jahr um Informationen anzuzeigen.</div>
        <?php endif; ?>
      </form>


    </div> <!-- menu content ###############################################-->
    <div id="menu1" class="tab-pane fade"><br>
      Coming Spoon.
    </div>



    <!-- /BODY -->
    <?php include 'footer.php'; ?>
