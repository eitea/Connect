<!DOCTYPE html>
<meta charset="utf-8">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/table.css">
  <link rel="stylesheet" type=text/css href = "../css/submitButt.css" >

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>
  <script src="../plugins/chartjs/Chart.min.js"></script>

</head>
<body>

<?php
  session_start();
  if (!isset($_SESSION['userid'])) {
    die('Please <a href="login.php">login</a> first.');
  }

  $userID = $_SESSION['userid'];

  require "connection.php";
  require "createTimestamps.php";
  require "language.php";

  require 'Calculators/MonthlyCalculator.php';
  require 'Calculators/YearlyCalculator.php';

  $currentTimeStamp = getCurrentTimestamp();
  $currentYear = substr($currentTimeStamp, 0, 4);
  $currentMonth = substr($currentTimeStamp, 5, 2);
?>

<h1><?php echo $lang['VIEW_TIMESTAMPS']; ?></h1>
<br><br>

<form method=post>
<select name='filteredMonth'>
<?php
  if (isset($_POST['applyFilter'])) {
    $currentMonth = sprintf("%02d", $_POST['filteredMonth']);
  }

  for($i = 1; $i < 13; $i++) {
    if ($i == $currentMonth) {
      $selected = 'selected';
    } else {
      $selected= '';
    }
    $dateObj = DateTime::createFromFormat('!m', $i);
    $option = $dateObj->format('F');
    echo "<option $selected name=filterUserID value=$i>$option</option>";
  }


  $currentTimeStamp = $currentYear . '-' . $currentMonth . '-01 05:00:00';
?>
</select>

<input type="submit" class="button" name="applyFilter" value="Filter" />
</form>

<br><br>

<div class="container">
  <ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#home"><?php $dateObj=DateTime::createFromFormat('!m', $currentMonth); echo $dateObj->format('F');?></a></li>
    <li><a data-toggle="tab" href="#menu1">Details</a></li>
    <li><a data-toggle="tab" href="#menu2"><?php echo $currentYear; ?></a></li>
  </ul>

<div class="tab-content">
  <div id="home" class="tab-pane fade in active">
<table>
  <tr>
    <th><?php echo $lang['WEEKLY_DAY']?></th>
    <th><?php echo $lang['DATE']?></th>
    <th><?php echo $lang['BEGIN']?></th>
    <th><?php echo $lang['LUNCHBREAK']?></th>
    <th><?php echo $lang['END']?></th>

    <th><?php echo $lang['ACTIVITY']?></th>
    <th><?php echo $lang['SHOULD_TIME']?></th>
    <th><?php echo $lang['IS_TIME']?></th>
    <th><?php echo $lang['DIFFERENCE']?></th>
    <th>Saldo <?php echo $lang['ACCUMULATED']?></th>
  </tr>

<?php
  $calculator = new Monthly_Calculator($currentTimeStamp, $userID);
  $calculator->calculateValues();

  $absolvedHours = array();
  $accumulatedSaldo = 0;
  for($i = 0; $i < $calculator->days; $i++){
    if($calculator->end[$i] == '0000-00-00 00:00:00'){
      $endTime = getCurrentTimestamp();
    } else {
      $endTime = $calculator->end[$i];
    }

    $difference = timeDiff_Hours($calculator->start[$i], $endTime );
    $absolvedHours[] = $difference - $calculator->lunchTime[$i];

    if($calculator->start[$i] != '-'){
      $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
    } else {
      $A = $calculator->start[$i];
    }

    if($calculator->end[$i] != '-'){
      $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);
    } else {
      $B = $calculator->end[$i];
    }

    $accumulatedSaldo += $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];

    echo "<tr>";
    echo "<td>" . $lang_weeklyDayToString[$calculator->dayOfWeek[$i]] . "</td>";
    echo "<td>" . $calculator->date[$i] . "</td>";
    echo "<td>" . substr($A,11,5) . "</td>";
    echo "<td>" . sprintf('%.2f', $calculator->lunchTime[$i]) . "</td>";
    echo "<td>" . substr($B,11,5) . "</td>";
    echo "<td>" . $lang_activityToString[$calculator->activity[$i]] . "</td>";
    echo "<td>" . $calculator->shouldTime[$i] . "</td>";
    echo "<td>" . sprintf('%.2f', $difference - $calculator->lunchTime[$i]) . "</td>";
    echo "<td>" . sprintf('%+.2f', $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i]) . "</td>";
    echo "<td>" . sprintf('%+.2f', $accumulatedSaldo) . "</td>";
    echo "</tr>";

}

?>

</table>

</div> <!-- HOME menu content ###############################################-->
  <div id="menu1" class="tab-pane fade"><br>

    <canvas id="myChart" width="500" height="250px"></canvas>
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

  <!-- menu division  ++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->

  <div id="menu2" class="tab-pane fade"><br>
    <?php
    $calculator = new Yearly_Calculator($currentTimeStamp, $userID);
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

</div> <!-- tab content -->
</div> <!--tab container -->
</body>
