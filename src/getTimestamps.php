<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
  <link rel="stylesheet" type="text/css" href="../css/table.css">
  <link rel="stylesheet" type="text/css" href="../css/inputTypeText.css">
  <link rel="stylesheet" type="text/css" href="../css/inputTypeTime.css">
  <link rel="stylesheet" type="text/css" href="../css/spanBlockInput.css">
  <link rel="stylesheet" type="text/css" href="../plugins/datepicker/codebase/dhtmlxcalendar.css">

  <script src="../plugins/datepicker/codebase/dhtmlxcalendar.js"> </script>
  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>
  <script src="../plugins/chartjs/Chart.min.js"></script>

  <style>
  .popover{
      max-width: 60%; /* Max Width of the popover (depending on the container!) */
      font-size:11px;
  }
  iframe {
    width:100%;
    border:none;
  }
  </style>
</head>
<body>

<form method="post">

<div class="container">

    <br>
    <?php
    session_start();
    if (!isset($_SESSION['userid'])) {
      die('Please <a href="login.php">login</a> first.');
    }
    if ($_SESSION['userid'] != 1) {
      die('Access denied. <a href="logout.php"> return</a>');
    }

    require "connection.php";
    require "createTimestamps.php";
    require "language.php";


    require "Calculators/MonthlyCalculator.php";
    require "Calculators/YearlyCalculator.php";
    ?>

    <h1><?php echo $lang['VIEW_TIMESTAMPS']?></h1>
    <br><br>

    <select name='filteredUserID'>
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

    <select name='filteredYear'>
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

    <select name='filteredMonth'>
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

    <input type="submit" class="button" name="filter" value="Filter"/>

    <br><br>

<!-- ####################################################################### -->

<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#home">Home</a></li>

  <?php if($filterID != 0): echo $filterMonth;?>

  <li><a data-toggle="tab" href="#menu1"><?php $dateObj=DateTime::createFromFormat('!m', $filterMonth); echo $dateObj->format('F');?></a></li>
  <li><a data-toggle="tab" href="#menu2"><?php echo $filterYear; ?></a></li>
  <li><a data-toggle="tab" href="#menu3">Summary</a></li>
  <?php endif; ?>
</ul>

<div class="tab-content">
  <div id="home" class="tab-pane fade in active">
<?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      if (isset($_POST['saveChanges'])) {
        for ($i = 0; $i < count($_POST['editingIndecesIM']); $i++) {
          $imm = $_POST['editingIndecesIM'][$i];
          $query = "SELECT * FROM $logTable WHERE indexIM=$imm";
          $row = mysqli_query($conn, $query)->fetch_assoc();
          $toUtc = $row['timeToUTC'] * -1;

          $timeStart = carryOverAdder_Hours($_POST['timesFrom'][$i] .':00', $toUtc);
          $timeFin = carryOverAdder_Hours($_POST['timesTo'][$i] .':00', $toUtc);

          $sql = "UPDATE $logTable SET time='$timeStart', timeEnd='$timeFin' WHERE indexIM = $imm";
          $conn->query($sql);
          echo mysqli_error($conn);
        }
      } elseif (isset($_POST['delete']) && isset($_POST['index'])) {
        $index = $_POST["index"];
        foreach ($index as $x) {
          $sql = "DELETE FROM " . $logTable . " WHERE indexIM=$x;";
          if (!$conn->query($sql)) {
            echo mysqli_error($conn);
          }
        }
      } elseif (isset($_POST['create']) && !empty($_POST['creatFromTime']) && !empty($_POST['creatToTime'])) {
        if($filterID != 0) {
          $thisuserID = $filterID;
          $activtiy = $_POST['action'];
          $creatTimeZone = $_POST['creatTimeZone'];
          $timeBegin = carryOverAdder_Hours($_POST['creatFromTime'], ($creatTimeZone*-1));
          $timeEnd = carryOverAdder_Hours($_POST['creatToTime'], ($creatTimeZone*-1));
          $day = strtolower(date('D', strtotime($timeBegin)));
          $sql = "SELECT $day FROM $bookingTable WHERE userID = $thisuserID";
          $result2 = $conn->query($sql);
          $row2 = $result2->fetch_assoc();
          $day = $row2[$day];

          $sql = "INSERT INTO $logTable (time, timeEnd, userID, status, timeToUTC, expectedHours) VALUES('$timeBegin', '$timeEnd', $thisuserID, '$activtiy', '$creatTimeZone', '$day');";
          $conn->query($sql);
        }
      }
    }
    ?>

  <script>
  $(document).ready(function(){
    $('[data-toggle="popover"]').popover();
  });
  </script>

    <table>
      <tr>
        <th><?php echo $lang['DELETE']?></th>
        <th><?php echo $lang['ACTIVITY']?></th>
        <th><?php echo $lang['FROM']?></th>
        <th><?php echo $lang['LUNCHBREAK']?></th>
        <th><?php echo $lang['TO']?></th>

        <th><?php echo $lang['SHOULD_TIME']?></th>
        <th><?php echo $lang['IS_TIME']?></th>
        <th><?php echo $lang['SUM']?></th>
        <th><?php echo $lang['DIFFERENCE']?></th>

        <th>Detail</th>
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
  $sql="SELECT DISTINCT $clientTable.name AS clientName, $companyTable.name AS companyName, $projectTable.name AS projectName, $projectBookingTable.start, $projectBookingTable.end, $projectBookingTable.infoText
        FROM $projectBookingTable, $logTable, $userTable, $projectTable, $companyTable, $clientTable
        WHERE $projectBookingTable.timeStampID = $logTable.indexIM
        AND $projectBookingTable.projectID = $projectTable.id
        AND $projectTable.clientID = $clientTable.id
        AND $clientTable.companyID = $companyTable.id
        AND $logTable.indexIM = $k";

  $result2 = $conn->query($sql);
  $popOverContent = "";
  if ($result2 && $result2->num_rows > 0) {
    $popOverContent = "<dl>";
    while ($roww = $result2->fetch_assoc()) {
      $popOverContent .= "<dt>" . carryOverAdder_Hours($roww['start'],  $calculator->timeToUTC[$i])
      . " - " . carryOverAdder_Hours($roww['end'],  $calculator->timeToUTC[$i]) . "</dt>";
      $popOverContent .= "<dd>" .$roww['companyName']." > " .$roww['clientName']." > " .$roww['projectName']."</dd>";
      $popOverContent .= "<dd>" .$roww['infoText']."</dd>";
    }
    $popOverContent .= "</dl>";
  }

  echo "<tr>";
  echo "<td><input type='checkbox' name='index[]' value= ".$i."></td>";
  echo "<td>" . $lang_activityToString[$calculator->activity[$i]] . "</td>";

  echo "<td><input maxlength='16' type=text onkeydown='if (event.keyCode == 13) return false;' name='timesFrom[]' value='" . substr($A,0,-3) . "'></td>";
  echo "<td>" . sprintf('%.2f', $calculator->lunchTime[$i]) . "</td>";
  echo "<td><input type=text maxlength='16' onkeydown='if (event.keyCode == 13) return false;' name='timesTo[]' value='" . substr($B,0,-3) . "'></td>";

  echo "<td>" . $calculator->shouldTime[$i] . "</td>";
  echo "<td>" . sprintf('%.2f', $difference) . "</td>";
  echo "<td>" . sprintf('%.2f', $difference - $calculator->lunchTime[$i]) . "</td>";
  echo "<td>" . sprintf('%+.2f', $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i]) . "</td>";

  echo '<td><a target="_self" href="dailyReport.php?filterDay='.substr($B,0,10) . $filterID.'" title="Bookings" data-toggle="popover" data-trigger="hover" data-placement="left" data-content="'.$popOverContent.'"><img width=15px height=15px src="../images/Question_Circle.jpg"></a></td>';
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
    <td>".sprintf('%+.2f',$saldoSUM)."</td> </tr>";

?>

<script>
$("[data-toggle=popover]").popover({html:true})
</script>

</table>

<br>
<?php if($filterID != 0) : ?>
  <span class="blockInput">
    <select name="action">
      <option name="act" value="0">Work</option>
      <option name="act" value="1">Vacation</option>
      <option name="act" value="2">Special Leave</option>
      <option name="act" value="3">Sick</option>
    </select>

    From:    <input type="text" id="calendar" size="19" name="creatFromTime">

    To:      <input type="text" id="calendar2" size="19" name="creatToTime">

    UTC:

    <select name="creatTimeZone">
      <?php
      for($i = -12; $i <= 12; $i++){
        if($i == $_SESSION['timeToUTC']){
          echo "<option name='ttz' value= $i selected>" . sprintf("%+03d", $i) . "</option>";
        } else {
          echo "<option name='ttz' value= $i>" . sprintf("%+03d", $i) . "</option>";
        }
      }
      ?>
    </select>

    <script>
    var myCalendar = new dhtmlXCalendarObject(["calendar","calendar2"]);
    myCalendar.setSkin("material");
    myCalendar.setDateFormat("%Y-%m-%d %H:%i:%s");
    </script>

    <input type="submit" class="button" name="create" value="+">
  </span>
  <br><br><input type="submit" class="button" name="delete" value="Delete"> <input
  type="submit" class="button" name="saveChanges" value="Save Changes"><br>
<?php endif; ?>

</div> <!-- HOME menu content ###############################################-->

<div id="menu1" class="tab-pane fade">
  <canvas id="myChart" width="500" height="250px"></canvas>

  <?php

  $absolvedHours = array();
  for($i = 0; $i < $calculator->days; $i++){
    if($calculator->end[$i] == '0000-00-00 00:00:00'){
      $endTime = getCurrentTimestamp();
    } else {
      $endTime = $calculator->end[$i];
    }

    $difference = timeDiff_Hours($calculator->start[$i], $endTime );
    $absolvedHours[] = $difference - $calculator->lunchTime[$i];
  }
  ?>

  <script>
  var ctx = document.getElementById("myChart");
  var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [<?php echo implode(", ", $calculator->daysAsNumber); ?>],
      datasets: [
        {
          label: "Absolved",
          data: [<?php echo implode(", ", $absolvedHours); ?>],
          backgroundColor: 'rgb(255, 144, 214)'
        },
        {
          label: "Expected",
          data: [<?php echo implode(", ", $calculator->shouldTime); ?>],
          backgroundColor: 'rgb(255, 0, 168)'
        }
      ]
    },
    options: {
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero:true
          }
        }]
      }
    }
  });
  </script>
</div>

<!-- menu division HERE ++++++++++++++++++++++++++++++++++++++++++++++++++++ -->

<div id="menu2" class="tab-pane fade">
  <br><br>
  <?php
  $calculator = new Yearly_Calculator(getCurrentTimestamp(), $filterID);
  $calculator->calculateValues();

  ?>

  <canvas id="yearChart" width="500" height="250px"></canvas>
  <script>
  var ctx = document.getElementById("yearChart");
  var my2ndChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [<?php echo implode(", ", $calculator->monthShortName); ?>],
      datasets: [
        {
          label: "Absolved",
          data: [<?php echo implode(", ", $calculator->actualTime); ?>],
          backgroundColor: 'rgb(255, 144, 214)'
        },
        {
          label: "Expected",
          data: [<?php echo implode(", ", $calculator->shouldTime); ?>],
          backgroundColor: 'rgb(255, 0, 168)'
        },
        {
          label: "Lunch",
          data: [<?php echo implode(", ", $calculator->lunchTime); ?>],
          backgroundColor: 'rgb(178, 0, 207)'
        }
      ]
    },
    options: {
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero:true
          }
        }]
      }
    }
  });
  </script>
</div>

<!-- menu division HERE ++++++++++++++++++++++++++++++++++++++++++++++++++++ -->

<div id="menu3" class="tab-pane fade">
  <br><br>
<iframe onload="resizeIframe(this)" scrolling=no src=userSummary.php?userID=<?php echo $filterID; ?>></iframe>
<script>
  function resizeIframe(obj) {
    obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
  }
</script>
</div>


</div> <!-- tab content -->
</div> <!--tab container -->

</form>
</body>
</html>
