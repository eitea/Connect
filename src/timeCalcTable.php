<?php include 'header.php'; ?>
<?php enableToStamps($userID);?>
<!-- BODY -->

<link rel="stylesheet" type="text/css" href="../plugins/datepicker/css/datepicker.css">
<script src="../plugins/datepicker/js/bootstrap-datepicker.js"> </script>

<div class="page-header">
  <h3><?php echo $lang['TIMESTAMPS']; ?></h3>
</div>

<?php
require 'Calculators/MonthlyCalculator.php';
$currentTimeStamp = getCurrentTimestamp();
if(isset($_POST['filterMonth'])){
  $currentTimeStamp = $_POST['newMonth']. '-01 05:00:00';
}
?>
<script>
$("#calendar").datepicker({
  format: "yyyy-mm",
  viewMode: "months",
  minViewMode: "months"
});
</script>

<form method=post>
  <div class="row form-group">
    <div class="col-xs-6">
      <div class="input-group">
        <input id="calendar" readonly type="text" class="form-control from" name="newMonth" value= <?php echo substr($currentTimeStamp,0,7); ?> >
        <span class="input-group-btn">
          <button class="btn btn-warning" type="submit" name='filterMonth'>Filter</button>
        </span>
      </div>
    </div>
  </div>
  <!-- request collapse -->
  <div class="collapse" id="requestLog_collapse">
    <div class="well">
      <div class="container-fluid">
        <div class="col-md-2">
          <label>ID</label>
          <input id="request_date" type="text" class="form-control" name="request_date" readonly />
        </div>
        <div class="col-md-3">
          <label>Neuer Anfang</label>
          <input type="datetime-local" name="request_start" class="form-control" />
        </div>
        <div class="col-md-3">
          <label>Neues Ende</label>
          <input type="datetime-local" name="request_end" class="form-control" />
        </div>
        <div class="col-md-4">
          <label>Infotext</label>
          <input type="text" name="request_text" class="form-control" placeholder="(Optional)"/>
        </div>
        <div class="col-md-12 text-right">
          <br><br><button type="submit" class="btn btn-warning" name="request_submit"><?php echo $lang['REQUESTS']; ?></button>
        </div>
      </div>
    </div>
  </div>
  <!-- /request collapse -->
</form>
<div class="table-responsive">
  <table class="table table-striped">
    <thead>
      <th><?php echo $lang['WEEKLY_DAY']?></th>
      <th><?php echo $lang['DATE']?></th>
      <th><?php echo $lang['BEGIN']?></th>
      <th><?php echo $lang['BREAK']?></th>
      <th><?php echo $lang['END']?></th>
      <th><?php echo $lang['ACTIVITY']?></th>
      <th><?php echo $lang['SHOULD_TIME']?></th>
      <th><?php echo $lang['IS_TIME']?></th>
      <th><?php echo $lang['DIFFERENCE']?></th>
      <th>Saldo</th>
      <th></th>
    </thead>
    <tbody>
      <?php
      $calculator = new Monthly_Calculator($currentTimeStamp, $userID);

      if($calculator->correctionHours){
        echo "<tr style='font-weight:bold;'>";
        echo "<td>".$lang['CORRECTION']." </td>";
        echo "<td>".$lang_monthToString[intval(substr($calculator->date[0],5,2))]."</td><td></td><td>-</td><td></td><td>-</td><td></td><td></td><td></td>";
        echo "<td>" . sprintf('%+.2f', $calculator->correctionHours) . "</td>";
        echo "</tr>";
      }

      $absolvedHours = array();
      $accumulatedSaldo = $calculator->correctionHours;
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

        $accumulatedSaldo += $difference - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];

        echo "<tr $neutralStyle>";
        echo "<td>" . $lang_weeklyDayToString[$calculator->dayOfWeek[$i]] . "</td>";
        echo "<td>" . $calculator->date[$i] . "</td>";
        echo "<td>" . substr($A,11,5) . "</td>";
        echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
        echo "<td>" . substr($B,11,5) . "</td>";
        echo "<td>" . $lang_activityToString[$calculator->activity[$i]] . "</td>";
        echo "<td>" . displayAsHoursMins($calculator->shouldTime[$i]) . "</td>";
        echo "<td>" . displayAsHoursMins($difference - $calculator->lunchTime[$i]) . "</td>";
        echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . "</td>";
        echo "<td><small>" . displayAsHoursMins($accumulatedSaldo) . "</small></td>";
        echo "<td><a class='btn btn-default' data-toggle='collapse' href='#requestLog_collapse' onclick='$(\"#request_date\").val(\"".$calculator->indecesIM[$i]."\");'><i class='fa fa-pencil'></i></a></td>";
        echo "</tr>";
      }
      ?>
    </tbody>
  </table>
</div>

<!-- /BODY -->
<?php include 'footer.php'; ?>
