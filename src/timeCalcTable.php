<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToStamps($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['TIMESTAMPS']; ?></h3>
</div>

<?php
require 'Calculators/MonthlyCalculator.php';
require 'Calculators/YearlyCalculator.php';

$currentTimeStamp = getCurrentTimestamp();
$currentYear = substr($currentTimeStamp, 0, 4);
$currentMonth = substr($currentTimeStamp, 5, 2);
?>

<form method=post>
  <?php
  if(isset($_POST['filterMonth'])){
    $currentYear = substr($_POST['newMonth'],0,4);
    $currentMonth = substr($_POST['newMonth'],5,2);
  }
  ?>

  <div class="form-group">
    <div class="col-lg-6">
      <div class="input-group">

        <input id="calendar" type="text" class="form-control from" name="newMonth" value= <?php echo $currentYear.'-'.$currentMonth; ?> >

        <span class="input-group-btn">
          <button class="btn btn-warning" type="submit" name='filterMonth'>Filter</button>
        </span>
      </div><!-- /input-group -->
    </div><!-- /.col-lg-6 -->
  </div>

  <script>
  $("#calendar").datepicker({
    format: "yyyy-mm",
    viewMode: "months",
    minViewMode: "months"
  });
  </script>

</form>

<div class="container">
  <ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#home"><?php $dateObj=DateTime::createFromFormat('!m', $currentMonth); echo $dateObj->format('F');?></a></li>
    <li><a data-toggle="tab" href="#menu1"><?php echo $lang['OVERVIEW']; ?></a></li>
  </ul>

  <div class="tab-content">
    <div id="home" class="tab-pane fade in active">
      <br>
      <div class="table-responsive">
        <table class="table table-hover table-striped">
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
          <tbody>
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
          </tbody>
        </div>
      </table>

    </div> <!-- menu content ###############################################-->
    <div id="menu1" class="tab-pane fade"><br>

    </div>

    <!-- /BODY -->
    <?php include 'footer.php'; ?>
