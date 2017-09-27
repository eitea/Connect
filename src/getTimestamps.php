<?php include 'header.php'; enableToTime($userID); ?>
<?php
require_once 'Calculators/IntervalCalculator.php';
$filterings = array("savePage" => $this_page, "user" => 0, "logs" => array(0, 'checked'), "date" => array(substr(getCurrentTimestamp(), 0, 8).'01', date('Y-m-t', strtotime(getCurrentTimestamp()))) ) ;
?>

<div class="page-header">
  <h3>
    <?php echo $lang['TIMESTAMPS']; ?>
    <div class="page-header-button-group">
      <?php include 'misc/set_filter.php'; ?>
      <a class="btn btn-default" data-toggle="modal" data-target=".addInterval" title="<?php echo $lang['ADD']; ?>"><i class="fa fa-plus"></i></a>
    </div>
  </h3>
</div>

<?php
//i need some filterings vars in here
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  
} //endif post

echo "Diese Seite befindet sich gerade im Umbau. Der Funktionsumfang wurde temporär eingeschränkt.";

?>

<!-- ############################### TABLE ################################### -->

<?php
if($filterings['user']):
  $result = $conn->query("SELECT id, firstname FROM $userTable WHERE id = ".$filterings['user']);
  if($result && ($row = $result->fetch_assoc())){
    $x = $row['id'];
  }
  $bookingResultsResults = array(); //lets do something fun
  ?>
  <br>
  <form method="post">
    <table id="mainTable" class="table table-hover table-condensed">
      <thead>
        <th><?php echo $lang['WEEKLY_DAY']; ?></th>
        <th><?php echo $lang['DATE']; ?></th>
        <th><?php echo $lang['BEGIN']; ?></th>
        <th><?php echo $lang['BREAK']; ?></th>
        <th><?php echo $lang['END']; ?></th>
        <th width="60px"><small><?php $larr = explode(' ',$lang['LAST_BOOKING']); echo $larr[0] .'<br>'.$larr[1]; ?></small></th>
        <th><?php echo $lang['ACTIVITY']; ?></th>
        <th><?php echo $lang['SHOULD_TIME']; ?></th>
        <th><?php echo $lang['IS_TIME']; ?></th>
        <th><?php echo $lang['SALDO_DAY']; ?></th>
        <th><?php echo $lang['SALDO_MONTH']; ?></th>
        <th>Saldo</th>
        <th>Option</th>
      </thead>
      <tbody>
        <?php
        $calculator = new Interval_Calculator($x, $filterings['date'][0], $filterings['date'][1]);
        echo "<tr style='color:#626262;'>";
        echo "<td colspan='11'><strong>Vorheriges Saldo: </strong></td>";
        echo "<td>".displayAsHoursMins($calculator->prev_saldo)."</td></tr>";
        $lunchbreakSUM = $expectedHoursSUM = $absolvedHoursSUM = $saldo_month = 0;
        for($i = 0; $i < $calculator->days; $i++){
          //sync with modal creation
          if($filterings['logs'][0] && $calculator->activity[$i] != $filterings['logs'][0]) continue;
          if($filterings['logs'][1] != 'checked' || $calculator->shouldTime[$i] != 0 || $calculator->absolvedTime[$i] != 0){              
            $style = "";
            $tinyEndTime = '-';

            $sql = "SELECT * FROM $roleTable WHERE userID = $x";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $canBook = $row['canBook'];

            if($calculator->end[$i] != '0000-00-00 00:00:00' && ($calculator->activity[$i] == 0 || $calculator->activity[$i] == 5) && $canBook == 'TRUE'){
              $sql = "SELECT bookingTimeBuffer FROM $configTable";
              $result = $conn->query($sql);
              $config = $result->fetch_assoc();

              $sql = "SELECT end FROM $projectBookingTable WHERE timestampID = " . $calculator->indecesIM[$i] ." AND bookingType = 'project' ORDER BY end DESC";
              $result = $conn->query($sql);
              if($result && $result->num_rows > 0){
                $config2 = $result->fetch_assoc();

                $bookingTimeDifference = timeDiff_Hours($config2['end'], $calculator->end[$i]) * 60;
                if($bookingTimeDifference <= $config['bookingTimeBuffer']) $style = "color:#6fcf2c"; //green
                if($bookingTimeDifference > $config['bookingTimeBuffer'])$style = "color:#facf1e"; //yellow
                if($bookingTimeDifference > $config['bookingTimeBuffer'] * 2 || $bookingTimeDifference < 0)$style = "color:#fc8542"; //red
                if($bookingTimeDifference < 0) $style = "color:#f0621c;font-weight:bold"; //monsterred

                if($calculator->end[$i])$tinyEndTime = substr(carryOverAdder_Hours($config2['end'], $calculator->timeToUTC[$i]),11,5);
              }
            }

            $A = $calculator->start[$i];
            $B = $calculator->end[$i];
            if($calculator->start[$i]) $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);
            if($calculator->end[$i] && $calculator->end[$i] != '0000-00-00 00:00:00') $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);

            $saldo_month += $calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];

            $theSaldo = round($calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i], 2);
            $saldoStyle = '';
            if($theSaldo < 0){
              $saldoStyle = 'style=color:#fc8542;'; //red
            } elseif($theSaldo > 0) {
              $saldoStyle = 'style=color:#6fcf2c;'; //green
            }

            $bookingResults = $conn->query("SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
              LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
              LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)
              WHERE timestampID = '".$calculator->indecesIM[$i]."' ORDER BY end ASC");

              $neutralStyle = '';
              if($calculator->shouldTime[$i] == 0 && $calculator->absolvedTime[$i] == 0){
                $neutralStyle = "style=color:#c7c6c6;";
              }

              echo "<tr $neutralStyle>";
              echo '<td>' . $lang['WEEKDAY_TOSTRING'][$calculator->dayOfWeek[$i]] . '</td>';
              echo '<td>' . $calculator->date[$i] . '</td>';
              echo '<td>' . substr($A,11,5) . '</td>';
              echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
              echo '<td>' . substr($B,11,5) . '</td>';
              echo "<td style='$style'><small>" . $tinyEndTime . "</small></td>";
              echo '<td>' . $lang['ACTIVITY_TOSTRING'][$calculator->activity[$i]]. '</td>';
              echo '<td>' . displayAsHoursMins($calculator->shouldTime[$i]) . '</td>';
              echo '<td>' . displayAsHoursMins($calculator->absolvedTime[$i] - $calculator->lunchTime[$i]) . '</td>';
              echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . '</td>';
              echo '<td>' . displayAsHoursMins($saldo_month) . '</td>';
              echo '<td>' . displayAsHoursMins($saldo_month + $calculator->prev_saldo) . '</td>';
              echo '<td>';
              if(strtotime($calculator->date[$i]) >= strtotime($calculator->beginDate)){
                echo '<button type="button" class="btn btn-default" title="'.$lang['EDIT'].'" data-toggle="modal" data-target=".editingModal-'.$i.'"><i class="fa fa-pencil"></i></button> ';
              } else {
                echo '<button type="button" class="btn" style="visibility:hidden">A</button> ';
              }
              if($bookingResults && $bookingResults->num_rows > 0){
                echo '<button type="button" class="btn btn-default" title="'.$lang['PROJECT_BOOKINGS'].'" data-toggle="modal" data-target=".bookingModal-'.$calculator->indecesIM[$i].'" ><i class="fa fa-file-text-o"></i></button> ';
                $bookingResultsResults[] = $bookingResults; //so we can create a modal for each of these valid results outside this loop
                $bookingResultsResults['timeToUTC'][] = $calculator->timeToUTC[$i];
              }
              if($calculator->indecesIM[$i] != 0){ echo '<button type="submit" class="btn btn-default" title="'.$lang['DELETE'].'" name="ts_remove" value="'.$calculator->indecesIM[$i].'"><i class="fa fa-trash-o"></i></button>';}
              echo '</td>';
              echo "</tr>";

              $lunchbreakSUM += $calculator->lunchTime[$i];
              $expectedHoursSUM += $calculator->shouldTime[$i];
              $absolvedHoursSUM += $calculator->absolvedTime[$i] - $calculator->lunchTime[$i];
            }

            if(isset($calculator->endOfMonth[$calculator->date[$i]])){              
              //overTimeLump
              if($calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'] > 0){
                $saldo_month -= $calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'];
                echo "<tr>";
                echo "<td colspan='9'>".$lang['OVERTIME_ALLOWANCE'].": </td>";
                echo "<td style='color:#fc8542;'>-" . displayAsHoursMins($calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'])."</td>";
                echo '<td>'.displayAsHoursMins($saldo_month).'</td>';
                echo '<td>'.displayAsHoursMins($saldo_month + $calculator->prev_saldo).'</td>';
                echo "<td></td></tr>";
              }
              //corrections
              if($calculator->endOfMonth[$calculator->date[$i]]['correction']){
                $saldo_month += $calculator->endOfMonth[$calculator->date[$i]]['correction'];
                echo "<tr>";
                echo "<td colspan='9'>".$lang['CORRECTION'].": </td>";
                echo "<td style='color:#9222cc'>" . displayAsHoursMins($calculator->endOfMonth[$calculator->date[$i]]['correction']) . "</td>";
                echo '<td>'.displayAsHoursMins($saldo_month).'</td>';
                echo "<td>".displayAsHoursMins($saldo_month + $calculator->prev_saldo)."</td><td></td></tr>";
              }
              //partial sum
              echo "<tr style='font-weight:bold;'>";
              echo "<td colspan='3'>Zwischensumme: </td>";
              echo "<td colspan='2'>".displayAsHoursMins($lunchbreakSUM)."</td><td></td><td></td>";
              echo "<td>".displayAsHoursMins($expectedHoursSUM)."</td>";
              echo "<td>".displayAsHoursMins($absolvedHoursSUM)."</td><td> = </td>";
              echo "<td>".displayAsHoursMins($saldo_month).'</td><td></td><td></td></tr>';
            }
          } //endfor

          //complete
          echo "<tr style='font-weight:bold;'>";
          echo "<td colspan='11'>Gesamt: </td>";
          echo '<td>'.displayAsHoursMins($calculator->saldo).'</td>';
          echo "<td></td></tr>";
          ?>
        </tbody>
      </table>
    </form>

<!-- ############################### END TABLE END ################################### -->

    <!-- add intervals modal -->
    <form method="POST">
      <div class="modal fade addInterval">
        <div class="modal-dialog modal-content modal-md">
          <div class="modal-header"><h4><?php echo $lang['ADD_TIMESTAMPS']; ?></h4></div>
          <div class="modal-body">
            <div class="input-group input-daterange">
              <input type="text" class="form-control datepicker" value="" placeholder="Von" name="add_multiple_start">
              <span class="input-group-addon"> - </span>
              <input type="text" class="form-control datepicker" value="" placeholder="Bis" name="add_multiple_end">
            </div>
            <br>
            <div class="row">
              <div class="col-md-6">
                <select name="add_multiple_user" class="js-example-basic-single">
                  <?php
                  echo '<option value="0">...</option>';
                  $result_fc = mysqli_query($conn, "SELECT * FROM $userTable WHERE id IN (".implode(', ', $available_users).")");
                  while($result_fc && ($row_fc = $result_fc->fetch_assoc())){
                    $checked = '';
                    if($filterings['user'] == $row_fc['id']) { $checked = 'selected'; }
                    echo "<option $checked value='".$row_fc['id']."' >".$row_fc['firstname'].' '.$row_fc['lastname']."</option>";
                  }
                   ?>
                </select>
              </div>
              <div class="col-md-6">
                <select name="add_multiple_status" class="js-example-basic-single">
                  <option value="1"><?php echo $lang['VACATION']; ?></option>
                  <option value="4"><?php echo $lang['VOCATIONAL_SCHOOL']; ?></option>
                  <option value="2"><?php echo $lang['SPECIAL_LEAVE']; ?></option>
                  <option value="6"><?php echo $lang['COMPENSATORY_TIME']; ?></option>
                </select>
              </div>
            </div>
            <br>
            <small> *<?php echo $lang['INFO_INTERVALS_AS_EXPECTED']; ?></small>
          </div>
          <div class="modal-footer">
            <button class="btn btn-default" type="button" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
            <button class="btn btn-warning" type="submit" name="add_multiple"><?php echo $lang['ADD']; ?></button>
          </div>
        </div>
      </div>
    </form>

    <?php
  else:
    echo '<br><div class="alert alert-info">'.$lang['INFO_REQUIRE_USER'].'</div>';
    echo '<script>document.getElementById("set_filter_search").click();</script>';
  endif;
  ?>

<script>

  </script>
  <!-- /BODY -->
  <?php include 'footer.php'; ?>
