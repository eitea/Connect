<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToTime($userID);?>
<!-- BODY -->

<div class="page-header">
<h3><?php echo $lang['MONTHLY_REPORT']; ?></h3>
</div>

<form method="post">

<?php
$filterMonth = substr(getCurrentTimestamp(),0,7);
$filterUserID = "0";

if(isset($_POST['filterMonth'])){
  $filterMonth = $_POST['filterMonth'];
}

if(isset($_POST['filterUserID'])){
  $filterUserID = $_POST['filterUserID'];
}
?>

<div class="row">
  <div class="col-lg-3">
  <select name='filterUserID' class='js-example-basic-single' style="width:220px">
  <?php
    $sql = "SELECT * FROM $userTable";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      echo "<option value=0>Select...</option>";
      while($row = $result->fetch_assoc()){
        $selected = "";
        if($filterUserID == $row['id']){
          $selected = "selected";
        }
        echo "<option $selected value=".$row['id'].">";
        echo $row['firstname'] . " " . $row['lastname'];
        echo "</option>";
      }
    }
  ?>
  </select>
</div>
  <div class="col-lg-6">
    <div class="input-group">
      <input type="month" class="form-control" name="filterMonth" value=<?php echo $filterMonth; ?> >
      <span class="input-group-btn">
        <button type="submit" class="btn btn-warning" name="filter">Display</button>
      </span>
    </div>
  </div>
</div>
</form>

<br><br>
<table class="table table-hover">
  <thead>
    <th><?php echo $lang['WEEKLY_DAY']; ?></th>
    <th><?php echo $lang['DATE']; ?></th>
    <th><?php echo $lang['BEGIN']; ?></th>
    <th><?php echo $lang['BREAK']; ?></th>
    <th><?php echo $lang['END']; ?></th>
    <th style='width:40px'><small><?php echo $lang['LAST_BOOKING']; ?></small></th>
    <th><?php echo $lang['ACTIVITY']; ?></th>
    <th><?php echo $lang['SHOULD_TIME']; ?></th>
    <th><?php echo $lang['IS_TIME']; ?></th>
    <th><?php echo $lang['DIFFERENCE']; ?></th>
    <th>Saldo</th>
    <th width=100px;><?php echo $lang['EDIT']; ?></th>
  </thead>
  <tbody>
<?php
if(isset($_POST['filterUserID']) && $_POST['filterUserID'] != 0){
  require 'Calculators/MonthlyCalculator.php';
  $filterMonth .= '-01 00:00:00';

  $calculator = new Monthly_Calculator($filterMonth, $filterUserID);
  $calculator->calculateValues();

  $accumulatedSaldo = 0;
  for($i = 0; $i < $calculator->days; $i++){
    if($calculator->end[$i] == '0000-00-00 00:00:00'){
      $endTime = getCurrentTimestamp();
    } else {
      $endTime = $calculator->end[$i];
    }
    $difference = timeDiff_Hours($calculator->start[$i], $endTime );


    /*
    -1 .... absent (should not occur!)
    0 ..... arrival
    1 ..... vacation
    2 ..... special leave
    3 .... sickness
    4 ..... time balancing
    */
    $style = "";
    $tinyEndTime = '-';

    $sql = "SELECT * FROM $roleTable WHERE userID =".$_POST['filterUserID'];
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $canBook = $row['canBook'];

    if($calculator->end[$i] != '-' && $calculator->end[$i] != '0000-00-00 00:00:00' && $calculator->activity[$i] == 0 && $canBook == 'TRUE'){
      $sql = "SELECT bookingTimeBuffer FROM $configTable";
      $result = $conn->query($sql);
      $config = $result->fetch_assoc();

      $sql = "SELECT end FROM $projectBookingTable WHERE timestampID = " . $calculator->indecesIM[$i] ." ORDER BY end DESC";
      $result = $conn->query($sql);
      if($result && $result->num_rows > 0) {
        $config2 = $result->fetch_assoc();

        $bookingTimeDifference = timeDiff_Hours($config2['end'], $calculator->end[$i]) * 60;

        if($bookingTimeDifference <= $config['bookingTimeBuffer']){
          $style = "color:#6fcf2c"; //green
        }
        if($bookingTimeDifference > $config['bookingTimeBuffer']){
          $style = "color:#facf1e"; //yellow
        }
        if($bookingTimeDifference > $config['bookingTimeBuffer'] * 2){
          $style = "color:#fc8542"; //red
        }
        if($calculator->end[$i] != '-'){
          $tinyEndTime = substr(carryOverAdder_Hours($config2['end'], $calculator->timeToUTC[$i]),11,5);
        }
      }
    }

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

    $theSaldo = round($difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i], 2);
    $saldoStyle = '';
    if($theSaldo < 0){
      $saldoStyle = 'style=color:#fc8542;'; //red
    } elseif($theSaldo > 0) {
      $saldoStyle = 'style=color:#6fcf2c;'; //green
    }

$neutralStyle = '';
    if($calculator->shouldTime[$i] == 0 && $difference == 0){
      $neutralStyle = "style=color:#c7c6c6;";
    }

    echo "<tr $neutralStyle>";
    echo "<td>" . $lang_weeklyDayToString[$calculator->dayOfWeek[$i]] . "</td>";
    echo "<td>" . $calculator->date[$i] . "</td>";
    echo "<td>" . substr($A,11,5) . "</td>";
    echo "<td>" . sprintf('%.2f', $calculator->lunchTime[$i]) . "</td>";
    echo "<td>" . substr($B,11,5)  . "</td>";
    echo "<td style='$style'><small>" . $tinyEndTime . "</small></td>";
    echo "<td>" . $lang_activityToString[$calculator->activity[$i]]. "</td>";
    echo "<td>" . $calculator->shouldTime[$i] . "</td>";
    echo "<td>" . sprintf('%.2f', $difference - $calculator->lunchTime[$i]) . "</td>";
    echo "<td $saldoStyle>" . sprintf('%+.2f', $theSaldo) . "</td>";
    echo "<td>" . sprintf('%+.2f', $accumulatedSaldo) . "</td>";
    echo "<td>";
    ?>
    <div class="col-xs-6">
    <form method="post" action="getProjects.php" target="_blank">
      <!--filteredUserID filterYear filterMonth filterDay filterBooked-->
        <input type="text" name="filterUserID" style="display:none" value="<?php echo $filterUserID; ?>" >
        <input type="text" name="filterYear" style="display:none" value="<?php echo $calculator->date[$i]; ?>" >

      <button type="submit" class="btn btn-default" title="Project View"><i class="fa fa-search"></i></button>
    </form>
  </div>
  <div class="col-xs-6">
    <form method="post" action="getTimestamps.php" target="_blank">
      <!--filteredUserID filterYear filterMonth filterStatus-->
        <input type='text' name="filteredUserID" style="display:none" value="<?php echo $filterUserID; ?>" >
        <input type='text' name="filterYear" style="display:none" value="<?php echo $calculator->date[$i]; ?>" >

      <button type="submit" class="btn btn-default" title="Timestamp View"><i class="fa fa-pencil"></i></button>
    </form>
  </div>
    <?php
    echo "</td>";
    echo "</tr>";

/* TODO: continue here
    echo "<tr>";
    echo "<td>Summe: </td>";

    echo "</tr>"
*/
  }
}
?>
</tbody>
</table>

<!-- /BODY -->
<?php include 'footer.php'; ?>
