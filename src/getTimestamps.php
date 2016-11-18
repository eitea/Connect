<?php include 'header.php'; ?>
<?php include 'validate.php';?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['TIMESTAMPS']; ?></h3>
</div>


  <?php
  require "Calculators/MonthlyCalculator.php";
  ?>

  <form method="post">

  <select name='filteredUserID' style="width:200px" class="js-example-basic-single">
    <?php
    $query = "SELECT * FROM $userTable;";
    $result = mysqli_query($conn, $query);
    $userFilterOptions = array(array("id" => "0", "firstname" => "--Select", "lastname" => "User--"));

    while($row=$result->fetch_assoc()){
      array_push($userFilterOptions, $row);
    }

    foreach ($userFilterOptions as $row) {
      $i = $row['id'];
      $option = $i . " - " . $row['firstname'] . " " . $row['lastname'];
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $filterID = $_POST['filteredUserID'];
      } else {
        $filterID = 0;
      }
      if ($filterID == $i) {
        echo "<option name=filterUserID value=$i selected>$option</option>";
      } else {
        echo "<option name=filterUserID value=$i>$option</option>";
      }
    }
    ?>
  </select>

  <select name='filteredYear' style="width:200px" class="js-example-basic-single">
    <?php
    $currentYear = substr(getCurrentTimestamp(), 0, 4);
    $yearFilterOptions = array("---");
    for ($i = $currentYear - 10; $i < $currentYear + 10; $i++){
      array_push($yearFilterOptions, $i);
    }
    foreach ($yearFilterOptions as $i) {
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $filterYear = $_POST['filteredYear'];
      } else {
        $filterYear = $currentYear;
      }
      if ($i == $filterYear) {
        echo "<option name=filterYear value=$i selected>$i</option>";
      } else {
        echo "<option name=filterYear value=$i>$i</option>";
      }
    }
    ?>
  </select>

  <select name='filteredMonth' style="width:200px" class="js-example-basic-single">
    <?php
    $allMonths = array('---', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', '---');
    $currentMonth = substr(getCurrentTimestamp(), 5, 2);
    for($i = 0; $i < 13; $i++) {
      $option = $allMonths[$i];
      $stringMonthrep = sprintf("%02d", $i);
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $filterMonth = $_POST['filteredMonth'];
      } else {
        $filterMonth = $currentMonth;
      }
      if ($i == $filterMonth) {
        echo "<option name=filterUserID value=$stringMonthrep selected>$option</option>";
      } else {
        echo "<option name=filterUserID value=$stringMonthrep>$option</option>";
      }
    }
    ?>
  </select>

  <button type="submit" class="btn btn-sm btn-warning" name="filter">Filter</button>

  <br><br>

  <!-- ####################################################################### -->
  <?php if($filterID != 0): ?>

      <div class="container-fluid">
        <ul class="nav nav-tabs">
          <li class="active"><a data-toggle="tab" href="#home"><?php $dateObj=DateTime::createFromFormat('!m', $currentMonth); echo $dateObj->format('F');?></a></li>
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
            $sql = "DELETE FROM " . $logTable . " WHERE indexIM=$x;";
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

            $sql = "SELECT * FROM $logTable WHERE userID = $filterID
            AND status = '$activtiy'
            AND time LIKE '$timeIsLike'";

            $result = mysqli_query($conn, $sql);
            if($result && $result->num_rows > 0){ //user already stamped in today
              $row = $result->fetch_assoc();

              $start = $row['timeEnd'];
              $indexIM = $row['indexIM'];

              $diff = timeDiff_Hours($start, $timeBegin);
              if($start != '0000-00-00 00:00:00' && $diff > 0){

                //create break stamp only if its about status 0
                if($activity == 0){
                  $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText) VALUES('$start', '$timeBegin', $indexIM, 'Create auto-break')";
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
            } else { //create new stamp

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

      <script>
      $(document).ready(function(){
        $('[data-toggle="popover"]').popover();
      });
      </script>

      <table class="table table-striped table-condensed text-center">
        <tr>
          <th><?php echo $lang['DELETE']; ?></th>
          <th><?php echo $lang['ACTIVITY']; ?></th>
          <th width=140px><?php echo $lang['FROM']; ?></th>
          <th><?php echo $lang['LUNCHBREAK']; ?></th>
          <th width=140px><?php echo $lang['TO']; ?></th>

          <th><?php echo $lang['SHOULD_TIME']; ?></th>
          <th><?php echo $lang['IS_TIME']; ?></th>
          <th><?php echo $lang['SUM']; ?></th>
          <th><?php echo $lang['DIFFERENCE']; ?></th>

          <th><?php echo $lang['BOOKINGS']; ?></th>
        </tr>
        <?php

        if($filterMonth == 0){
          $filterMonth = getCurrentTimestamp();
        }

        $filterMonth = $filterYear ."-".$filterMonth."-01 05:00:00";

        $calculator = new Monthly_Calculator($filterMonth, $filterID);
        $calculator->calculateValues();

        $absolvedHoursSUM = $expectedHoursSUM = $lunchbreakSUM = $saldoSUM = $isTimeSUM = 0;

        for($i = 0; $i < $calculator->days; $i++){
          if($calculator->start[$i] != '-' && $calculator->activity[$i] >= 0):

            if($calculator->end[$i] == '0000-00-00 00:00:00'){
              $endTime = getCurrentTimestamp();
            } else {
              $endTime = $calculator->end[$i];
            }

            $difference = timeDiff_Hours($calculator->start[$i], $endTime );

            $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
            $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);

            $k = $calculator->indecesIM[$i];


            echo "<tr>";
            echo "<td><input type='checkbox' name='index[]' value= ".$k."></td>";
            echo "<td>" . $lang_activityToString[$calculator->activity[$i]] . "</td>";

            echo "<td><input type='text' class='form-control input-sm' maxlength='16' onkeydown='if (event.keyCode == 13) return false;' name='timesFrom[]' value='" . substr($A,0,-3) . "'></td>";
            echo "<td class='text-center'>" . sprintf('%.2f', $calculator->lunchTime[$i]) . "</td>";
            echo "<td><input type='text' class='form-control input-sm' maxlength='16' onkeydown='if (event.keyCode == 13) return false;' name='timesTo[]' value='" . substr($B,0,-3) . "'></td>";

            echo "<td>" . $calculator->shouldTime[$i] . "</td>";
            echo "<td>" . sprintf('%.2f', $difference) . "</td>";
            echo "<td>" . sprintf('%.2f', $difference - $calculator->lunchTime[$i]) . "</td>";
            echo "<td>" . sprintf('%+.2f', $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i]) . "</td>";

            echo '<td class=text-center><a target="_self" href="dailyReport.php?filterDay='.substr($A,0,10).'&userID='.$filterID.'"><i class="fa fa-question-circle-o"></a></td>';
            echo "</tr>";

            echo '<input type="text" style="display:none;" name="editingIndecesIM[]" value="' . $k . '">';
            $absolvedHoursSUM += $difference - $calculator->lunchTime[$i];
            $expectedHoursSUM += $calculator->shouldTime[$i];
            $lunchbreakSUM += $calculator->lunchTime[$i];
            $saldoSUM += $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];
            $isTimeSUM += $difference;
          endif;
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

            <div class="col-xs-5">
              <div class="input-group input-daterange">
                <span class="input-group-btn">
                  <button class="btn btn-warning" type="submit" name="create"> + </button>
                </span>
                <input type="text" class="form-control datepick" value="" placeholder="Von" size='16' maxlength=16 name="creatFromTime">
                <span class="input-group-addon"> - </span>
                <input type="text" class="form-control datepick" value="" placeholder="Bis" size='16' maxlength=16  name="creatToTime">
              </div>
            </div>

            <div class="text-right">
              <button type="submit" class="btn btn-warning" name="delete"><?php echo $lang['DELETE']; ?></button>
              <button  type="submit" class="btn btn-warning" name="saveChanges"> Save </button>
            </div>

          </div>

        </span>
      <?php endif; ?>
    <?php endif; ?>
</form>


    </div> <!-- menu content ###############################################-->
    <div id="menu1" class="tab-pane fade"><br>

    </div>



<!-- /BODY -->
<?php include 'footer.php'; ?>
