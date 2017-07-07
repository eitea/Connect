<?php include 'header.php'; ?>
<?php enableToStamps($userID); ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['MY_REQUESTS']?></h3>
</div>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['makeRequest']) && !empty($_POST['start']) && !empty($_POST['end'])){
    if(test_Date($_POST['start'].' 08:00:00') && test_Date($_POST['end'].' 08:00:00')){
      $result = $conn->query("SELECT coreTime FROM UserData WHERE id = $userID");
      $row = $result->fetch_assoc();
      $begin = test_input($_POST['start'].' '.$row['coreTime']);
      $end = test_input($_POST['end'].' '.$row['coreTime']);
      $infoText = test_input($_POST['requestText']);
      $type = test_input($_POST['requestType']);
      $sql = "INSERT INTO $userRequests (userID, fromDate, toDate, requestText, requestType) VALUES($userID, '$begin', '$end', '$infoText', '$type')";
      $conn->query($sql);
      echo mysqli_error($conn);
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Failed! </strong>Invalid Dates.';
      echo '</div>';
    }
  } elseif(isset($_POST['deleteRequest'])){
    $sql = "DELETE FROM $userRequests WHERE id =". $_POST['deleteRequest'];
    $conn->query($sql);
  } elseif(isset($_POST['request_lunchbreak']) && !empty($_POST['lunch_FROM']) && !empty($_POST['lunch_TO'])){
    $timestampID = $_POST['request_lunchbreak'];
    $from = carryOverAdder_Hours(substr(getCurrentTimestamp(), 0, 11).$_POST['lunch_FROM'].':00', $timeToUTC * -1);
    $to = carryOverAdder_Hours(substr(getCurrentTimestamp(), 0, 11) . $_POST['lunch_TO'] . ':00', $timeToUTC * -1);
    $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$from', '$to', $timestampID, 'Request Lunchbreak', 'break')";
    if($conn->query($sql)){
      $insertId = mysqli_insert_id($conn);
      $from = carryOverAdder_Hours($from, $timeToUTC);
      $to = carryOverAdder_Hours($to, $timeToUTC);
      $conn->query("INSERT INTO $userRequests (userID, fromDate, toDate, requestType, requestID) VALUES($userID, '$from', '$to', 'brk', $insertId)");
      //update breakCredit
      $breakDiff = timeDiff_Hours($from, $to);
      $conn->query("UPDATE $logTable SET breakCredit = (breakCredit + $breakDiff) WHERE indexIM = $timestampID");
    }
    echo mysqli_error($conn);
  }
}
?>

<br><br>

<form method="post">
  <div class="row">
    <div class="col-md-6">
      <div class="input-group input-daterange">
        <input id='calendar' type="date" class="form-control" value="" placeholder="Von" name="start">
        <span class="input-group-addon"> - </span>
        <input id='calendar2' type="date" class="form-control" value="" placeholder="Bis" name="end">
      </div><br>
    </div>
    <div class="col-md-4">
      <select name="requestType" class="js-example-basic-single">
        <option value="vac"><?php echo $lang['VACATION']; ?></option>
        <option value="scl"><?php echo $lang['VOCATIONAL_SCHOOL']; ?></option>
        <option value="spl"><?php echo $lang['SPECIAL_LEAVE']; ?></option>
        <option value="cto"><?php echo $lang['COMPENSATORY_TIME']; ?></option>
      </select><br>
    </div>
    <div class="col-md-2">
      <button class="btn btn-warning" type="submit" name="makeRequest"><?php echo $lang['REQUESTS']; ?></button><br>
    </div>
  </div>
  <div class="row">
    <div class="col-xs-6">
      <input type="text" class="form-control" placeholder="Info... (Optional)" name="requestText">
    </div>
  </div>
</form>
<br><br><br>
<script>
var myCalendar = new dhtmlXCalendarObject(["calendar","calendar2"]);
myCalendar.setSkin("material");
myCalendar.setDateFormat("%Y-%m-%d");
</script>

<?php
$sql = "SELECT * FROM $userRequests WHERE userID = $userID AND requestType NOT IN ('acc', 'brk')";
$result = $conn->query($sql);
if($result && $result->num_rows > 0): ?>
<form method="post">
  <table class="table table-hover">
    <tr>
      <th><?php echo $lang['TYPE']; ?></th>
      <th><?php echo $lang['FROM']; ?></th>
      <th><?php echo $lang['TO']; ?></th>
      <th>Status</th>
      <th><?php echo $lang['REPLY_TEXT']; ?> </th>
      <th class="text-center"><?php echo $lang['REQUESTS']. ' '. $lang['DELETE']; ?></th>
    </tr>
    <tbody>
      <?php
      while($row = $result->fetch_assoc()){
        if(timeDiff_Hours($row['toDate'], getCurrentTimestamp()) > 0 && $row['status'] == 2){
          continue;
        }
        $style = "";
        if($row['status'] == 0) {
          $style="";
        } elseif ($row['status'] == 1) {
          $style="#b52140";
        } elseif ($row['status'] == 2) {
          $style="#13b436";
        }

        echo "<tr>";
        echo '<td>' . $lang['REQUEST_TOSTRING'][$row['requestType']] . '</td>';
        echo '<td>' . substr($row['fromDate'],0,10) .'</td>';
        echo '<td>' . substr($row['toDate'],0,10) .'</td>';
        echo "<td style='color:$style'>" . $lang['REQUESTSTATUS_TOSTRING'][$row['status']] .'</td>';
        echo '<td>' . $row['answerText'] . '</td>';
        echo '<td class="text-center"> <button type="submit" name="deleteRequest" value="'.$row['id'].'" class="btn btn-warning" title="'.$lang['MESSAGE_DELETE_REQUEST'].'">
        <i class="fa fa-trash-o ></i>"</button> </td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>
</form>
<?php endif; ?>

<?php
$result = $conn->query("SELECT indexIM FROM logs WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00'");
if($result && ($row = $result->fetch_assoc())):
 ?>
<br><hr><br>
<h4><?php echo $lang['TODAY']; ?></h4>
<br>
<div class="col-sm-3"><label><?php echo $lang['FORGOTTEN_LUNCHBREAK']; ?>: </label></div>
<form method="POST">
  <div class="col-sm-3"><div class="input-group"><span class="input-group-addon"><?php echo $lang['FROM']; ?></span><input type="time" class="form-control" name="lunch_FROM" /></div></div>
  <div class="col-sm-3"><div class="input-group"><span class="input-group-addon"><?php echo $lang['TO']; ?></span><input type="time" class="form-control" name="lunch_TO" /></div></div>
  <div class="col-sm-3"><button type="submit" class="btn btn-warning" name="request_lunchbreak" value="<?php echo $row['indexIM']; ?>"><?php echo $lang['REQUESTS']; ?></button></div>
</form>
<?php endif; ?>
<!-- /BODY -->
<?php include 'footer.php'; ?>
