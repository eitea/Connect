<?php include 'header.php'; ?>

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

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if (!empty($_POST['filteredUserID'])) {
    $filterID = $_POST['filteredUserID'];
  }
  if (isset($_POST['filterStatus'])) {
    $filterStatus = $_POST['filterStatus'];
  }
  if(!empty($_POST['filterYear'])){
    $filterDate = $_POST['filterYear'];
    if(!empty($_POST['filterMonth'])){
      $filterDate .= '-' . $_POST['filterMonth'];
    }
  }
}
?>

<form method="post">

  <!-- ############################### FILTER ################################### -->

  <select name='filteredUserID' style="width:200px" class="js-example-basic-single">
    <?php
    $query = "SELECT * FROM $userTable;";
    $result = mysqli_query($conn, $query);

    echo "<option name=filterUserID value=0>User...</option>";
    while($row = $result->fetch_assoc()){
      $i = $row['id'];
      if ($filterID == $i) {
        echo "<option name='filterUserID' value='$i' selected>".$row['firstname'] . " " . $row['lastname']."</option>";
      } else {
        echo "<option name='filterUserID' value='$i' >".$row['firstname'] . " " . $row['lastname']."</option>";
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

  <!-- ############################### POST ################################### -->

  <?php
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['saveChanges'])) {
      for ($i = 0; $i < count($_POST['editingIndecesIM']); $i++) {
        $imm = $_POST['editingIndecesIM'][$i];
        $timeStart = $_POST['timesFrom'][$i] .':00';
        $timeFin = $_POST['timesTo'][$i] .':00';
        $status = $_POST['newActivity'][$i];

        $newBreakVal = floatval($_POST['newBreakValues'][$i]);

        if($timeFin != '0000-00-00 00:00:00'){
          $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd=DATE_SUB('$timeFin', INTERVAL timeToUTC HOUR), breakCredit = '$newBreakVal', status='$status' WHERE indexIM = $imm";
        } else {
          $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd='$timeFin', breakCredit = '$newBreakVal', status='$status' WHERE indexIM = $imm";
        }

        $conn->query($sql);
        echo mysqli_error($conn);
      }
    }
  }
  ?>
  <?php if($filterID != 0): ?>
    <ul class="nav nav-tabs">
      <li class="active"><a data-toggle="tab" href="#home">Detail</a></li>
      <li><a data-toggle="tab" href="#menu1"><?php echo $lang['OVERVIEW']; ?></a></li>
    </ul>
    <div class="tab-content">
      <div id="home" class="tab-pane fade in active">
        <br>
        <table class="table table-striped table-condensed text-center">
          <tr>
            <th><?php echo $lang['DELETE']; ?></th>
            <th><?php echo $lang['WEEKLY_DAY']; ?></th>
            <th><?php echo $lang['ACTIVITY']; ?></th>
            <th width="140px"><?php echo $lang['FROM']; ?></th>
            <th><?php echo $lang['LUNCHBREAK']; ?></th>
            <th width="140px"><?php echo $lang['TO']; ?></th>

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
          $absolvedHoursSUM = $lunchbreakSUM = $saldoSUM = $isTimeSUM = 0;

          $sql = "SELECT * FROM $logTable WHERE userID = $filterID AND time LIKE '$filterDate%' $filterStatusAdd ORDER BY time ASC";
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
              echo "<td><input type='checkbox' name='index[]' value='$k' /></td>";
              echo "<td>". $lang_weeklyDayToString[strtolower(date('D', strtotime($A)))] . "</td>";

              echo "<td><select name='newActivity[]' class='js-example-basic-single'>";
              for($j = 0; $j < 4; $j++){
                if($row['status'] == $j){
                  echo "<option value='$j' selected>". $lang_activityToString[$j] ."</option>";
                } else {
                  echo "<option value='$j'>". $lang_activityToString[$j] ."</option>";
                }
              }
              echo "</select></td>";

              echo "<td><input type='text' class='form-control input-sm' maxlength='16' onkeydown='if (event.keyCode == 13) return false;' name='timesFrom[]' value='" . substr($A,0,-3) . "' /></td>";
              echo "<td><div style='display:inline-block;text-align:center'><input type='number' step='any' class='form-control input-sm' name='newBreakValues[]' value='" . $row['breakCredit']. "' style='width:70px' /></div></td>";
              echo "<td><input type='text' class='form-control input-sm' maxlength='16' onkeydown='if (event.keyCode == 13) return false;' name='timesTo[]' value='" . substr($B,0,-3) . "' /></td>";

              echo "<td></td>";
              echo "<td>" . sprintf('%.2f', $difference) . "</td>";
              echo "<td>" . sprintf('%.2f', $difference - $row['breakCredit']) . "</td>";
              echo "<td>" . sprintf('%+.2f', $difference - $row['breakCredit']) . "</td>";

              echo '<td class="hidden"><input type="text" style="display:none;" name="editingIndecesIM[]" value="' . $k . '"></td>';
              echo "</tr>";

              $absolvedHoursSUM += $difference -  $row['breakCredit'];
              $lunchbreakSUM +=  $row['breakCredit'];
              $saldoSUM += $difference -  $row['breakCredit'];
              $isTimeSUM += $difference;
            }
          }

          echo "<tr style=font-weight:bold;>
          <td>Sum: </td>
          <td>-</td> <td>-</td> <td>-</td>
          <td>".sprintf('%.2f',$lunchbreakSUM)."</td> <td>-</td>
          <td></td>
          <td>".sprintf('%.2f', $isTimeSUM)."</td>
          <td>".sprintf('%.2f',$absolvedHoursSUM)."</td>
          <td>".sprintf('%+.2f',$saldoSUM)."</td> <td></td></tr>";
          ?>

          <script>
          $("[data-toggle=popover]").popover({html:true})
          </script>

        </table>

        </form>


      </div> <!-- menu content ###############################################-->
      <div id="menu1" class="tab-pane fade"><br>
        <script>
          function resizeIframe(obj) {
            obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
          }
        </script>
        <iframe src="tableSummary.php?userID=<?php echo $filterID; ?>" style='width:100%; border:none;' scrolling='no' onload='resizeIframe(this)'></iframe>
      </div>
    </div>
<?php else: ?>
  <div class="alert alert-info" role="alert"><strong><?php echo $lang['MANDATORY_SETTINGS']; ?>: </strong>Wähle Benutzer und Jahr um Informationen anzuzeigen.</div>
<?php endif; ?>

<!-- /BODY -->
<?php include 'footer.php'; ?>
