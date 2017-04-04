<?php include 'header.php'; ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['DAYS'].' '.$lang['AVAILABLE'].': '. $lang['VACATION']; ?></h3>
</div>

<?php
$curID = 0;
if(!empty($_GET['curID'])){
  $curID = intval($_GET['curID']);
}
if(isset($_POST['filterUserID'])){
  $curID = intval($_POST['filterUserID']);
}
$currentMonth = substr(getCurrentTimestamp(),0,7);
if(isset($_POST['newMonth'])){
  $currentMonth = $_POST['newMonth'];
}
?>
<form method="post" id="form1" action="display_vacation.php">
  <div class="row form-group">
    <div class="col-xs-3">
      <select id="filterUserID" name="filterUserID" class="js-example-basic-single btn-block">
        <?php
        $result = mysqli_query($conn, "SELECT * FROM $userTable;");
        while($row = $result->fetch_assoc()){
          $i = $row['id'];
          if ($curID == $i) {
            echo "<option value='$i' selected>".$row['firstname'] . " " . $row['lastname']."</option>";
          } else {
            echo "<option value='$i'>".$row['firstname'] . " " . $row['lastname']."</option>";
          }
        }
        ?>
      </select>
    </div>
    <div class="col-xs-3">
      <div class="input-group">
        <input id="calendar" readonly type="text" class="form-control from" name="newMonth" value= <?php echo $currentMonth; ?> >
        <span class="input-group-btn">
          <button class="btn btn-warning" type="submit">Filter</button>
        </span>
      </div>
    </div>
  </div>
</form>
<br>
<h4> <?php echo $lang['USED_DAYS'] .' - ' . $lang_monthToString[intval(substr($currentMonth,5,2))]; ?></h4>
<table class="table table-hover">
  <thead>
    <th>Day of Week</th>
    <th>Date</th>
  </thead>
  <tbody>
    <?php
    $usedDays = 0;
    $result = $conn->query("SELECT time FROM $logTable WHERE userID = $curID AND status='1' AND time LIKE '$currentMonth%'");
    while($result && ($row = $result->fetch_assoc())){
      echo '<tr>';
      echo '<td>'.$lang_weeklyDayToString[strtolower(date('D', strtotime($row['time'])))].'</td>';
      echo '<td>'.substr($row['time'],0,10).'</td>';
      echo '</tr>';
      $usedDays++;
    }
    echo "<tr style='font-weight:bold;'><td></td><td>$usedDays days</td>";
    ?>
  </tbody>
</table>

<br><br>

<div class="row">
  <div class="col-sm-6 pull-right">
    <h5><?php echo $lang['ACCUMULATED_DAYS']; ?></h5>
    <ul>
      <?php
      //t returns the number of days in the month of a given date
      $i = $currentMonth .'-01 05:00:00';
      $j = date("Y-m-t H:i:s", strtotime($currentMonth));

      $gatheredDays = 0;
      $result_I = $conn->query("SELECT $intervalTable.*, $userTable.exitDate, $userTable.beginningDate FROM $intervalTable INNER JOIN $userTable ON userID = $userTable.id
        WHERE userID = $curID AND DATE(startDate) <= DATE('$i') AND (DATE(endDate) >= DATE('$j') OR endDate IS NULL)"); //select all intervals which fit into this month

        while($result_I && ($iRow = $result_I->fetch_assoc())){ //foreach interval
          if(!empty($iRow['endDate'])){ //current interval has endDate
            $j = $iRow['endDate'];
          } elseif($iRow['exitDate'] != '0000-00-00 00:00:00'){ //current interval and he HAS an exitDate, calculate until the exitDate.
            $j = $iRow['exitDate'];
          }
          $dayDiff = intval(timeDiff_Hours($i, $j) / 24);
          $gatheredDays += ($iRow['vacPerYear']/365) * $dayDiff; //accumulated vacation
          $i = substr($i, 0, 10);
          $j = substr($j, 0, 10);
          $gatheredDays = round($gatheredDays);
          echo "<li>From $i - Until $j  ($dayDiff days difference)<ul><li>".$iRow['vacPerYear']." / 365 * $dayDiff = $gatheredDays days</li></ul></li>";
          $i = $iRow['endDate'];
        }

        $correctionDays = 0;
        $result = $conn->query("SELECT hours, addOrSub FROM $correctionTable WHERE userID = $curID AND cType = 'vac' AND createdOn LIKE '$currentMonth%'");
        while($result && ($row = $result->fetch_assoc())){
          $correctionDays += intval($row['hours']) * intval($row['addOrSub']);
        }
        ?>
    </ul>
  </div>
  <div class="col-sm-6 pull-left">
    <h4> Saldo <?php echo $lang_monthToString[intval(substr($currentMonth,5,2))]; ?> </h4>
    <ul>
      <li><?php echo round($gatheredDays - $usedDays + $correctionDays) .' '. $lang['DAYS'] . ' (' . sprintf('%+d ', $correctionDays) . $lang['DAYS'] . ' ' . $lang['CORRECTION'].')'; ?></li>
    </ul>
  </div>
</div>

<br><hr><br>

<div class="row">
  <div class="col-sm-6 pull-right">
    <h5><?php echo $lang['ACCUMULATED_DAYS']; ?></h5>
    <ul>
      <?php
      $gatheredDays = $vac = 0;
      $result_I = $conn->query("SELECT $intervalTable.*, $userTable.exitDate, $userTable.beginningDate FROM $intervalTable INNER JOIN $userTable ON userID = $userTable.id  WHERE userID = $curID");
      while($result_I && ($iRow = $result_I->fetch_assoc())){ //foreach interval
        $vac = intval($iRow['vacPerYear']);
        $i = $iRow['startDate'];
        $now = $j = $iRow['endDate'];
        if(empty($j) && $iRow['exitDate'] == '0000-00-00 00:00:00' ){ //current interval no endDate, user no exit date => calculate until today
          $j = getCurrentTimestamp();

        } elseif(empty($j)){ //current interval and he HAS an exitDate, calculate until the exitDate.
          $j = $iRow['exitDate'];
        }
        $gatheredDays += ($vac/365) * (timeDiff_Hours($i, $j) / 24 + 1); //accumulated vacation
      }
      $dayDiff = intval(timeDiff_Hours($i, $j) / 24)+1;

      $i = substr($i, 0, 10);
      $j = substr($j, 0, 10);
      $gatheredDays = round($gatheredDays);
      echo "<li>From $i - Until $j  ($dayDiff days difference)<ul><li> $vac / 365 * $dayDiff = $gatheredDays days</li></ul></li>";
      ?>
    </ul>
  </div>
  <div class="col-sm-6 pull-left">
    <h4> Saldo <?php echo $lang['COMPLETE']; ?> </h4>
    <ul>
      <?php
      $usedDays = 0;
      $result = $conn->query("SELECT COUNT(time) AS usedDays FROM $logTable WHERE userID = $curID AND status='1'");
      if($result && ($row = $result->fetch_assoc())){
        $usedDays = $row['usedDays'];
        echo "<li>".$lang['USED_DAYS'].": $usedDays ". $lang['DAYS'] ."</li>";
      }
      $correctionDays = 0;
      $result = $conn->query("SELECT hours, addOrSub FROM $correctionTable WHERE userID = $curID AND cType = 'vac'");
      while($result && ($row = $result->fetch_assoc())){
        $correctionDays += intval($row['hours']) * intval($row['addOrSub']);
      }
      ?>
      <li><?php echo round($gatheredDays - $usedDays + $correctionDays) .' '. $lang['DAYS'] . ' (' . sprintf('%+d ', $correctionDays) . $lang['DAYS'] . ' ' . $lang['CORRECTION'].')'; ?></li>
    </ul>
  </div>
</div>


<script>
$("#calendar").datepicker({
  format: "yyyy-mm",
  viewMode: "months",
  minViewMode: "months"
});
</script>

<!-- /BODY -->
<?php include 'footer.php'; ?>
