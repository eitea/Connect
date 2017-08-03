<?php include 'header.php'; enableToStamps($userID); ?>
<?php
$filterRequest_text = 'Alte ausblenden';
$filterRequest = $unlock = 0;
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
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    }
  } elseif(isset($_POST['makeRequest'])){
    if($_POST['requestText'] == 'I demand an easteregg'){
      $unlock = TRUE;
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
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
  if(isset($_POST['filterRequest_all'])){
    $filterRequest_text = $lang['DISPLAY_ALL'];
    $filterRequest = 1;
  }
}
?>

<div class="page-header">
  <h3><?php echo $lang['MY_REQUESTS']?></h3>
</div>
<br><br>
<form method="post">
  <div class="row">
    <div class="col-sm-6 col-lg-4">
      <select name="requestType" class="js-example-basic-single">
        <option value="vac"><?php echo $lang['VACATION']; ?></option>
        <option value="scl"><?php echo $lang['VOCATIONAL_SCHOOL']; ?></option>
        <option value="spl"><?php echo $lang['SPECIAL_LEAVE']; ?></option>
        <option value="cto"><?php echo $lang['COMPENSATORY_TIME']; ?></option>
      </select>
    </div>
    <div class="col-sm-6 col-lg-4">
      <input type="text" class="form-control" placeholder="Info... (Optional)" name="requestText">
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6 col-lg-4">
      <div class="input-group input-daterange">
        <span class="input-group-addon"> <?php echo $lang['FROM'];?> </span>
        <input id='calendar' type="date" class="form-control" value="" placeholder="Von" name="start">
      </div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="input-group input-daterange">
        <span class="input-group-addon"> <?php echo $lang['TO'];?> </span>
        <input id='calendar2' type="date" class="form-control" value="" placeholder="Bis" name="end">
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-12 col-lg-8 text-right">
      <button class="btn btn-warning" type="submit" name="makeRequest"><?php echo $lang['REQUESTS']; ?></button><br>
    </div>
  </div>
</form>
<br><br><br>
<?php
$sql = "SELECT * FROM $userRequests WHERE userID = $userID AND requestType NOT IN ('acc', 'brk')";
$result = $conn->query($sql);
if($result && $result->num_rows > 0): ?>
<form method="POST">
  <?php if($filterRequest) echo '<input type="hidden" name="filterRequest_all" value="1" />' ?>
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
        if(!$filterRequest && timeDiff_Hours($row['toDate'], getCurrentTimestamp()) > 0 && $row['status'] == 2){
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
        echo '<td class="text-center"><button type="submit" name="deleteRequest" value="'.$row['id'].'" class="btn btn-warning" title="'.$lang['MESSAGE_DELETE_REQUEST'].'">
        <i class="fa fa-trash-o ></i>"</button></td>';
        echo '</tr>';
      }
      ?>
  </tbody>
</table>
</form>
<div class="row">
  <div class="col-sm-2">
    <form method="post">
      <?php
      echo '<div class="dropdown"><a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown">'.$filterRequest_text.'<i class="fa fa-caret-down"></i></a><ul class="dropdown-menu">';
      echo '<li><button type="submit" name="filterRequest_default" class="btn btn-link" >Alte ausblenden</button></li>';
      echo '<li><button type="submit" name="filterRequest_all" class="btn btn-link" >'.$lang['DISPLAY_ALL'].'</button></li>';
      echo '</ul></div>';
      ?>
    </form>
  </div>
</div>
<?php endif; ?>

<?php $result = $conn->query("SELECT indexIM FROM logs WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00'");
if($result && ($row = $result->fetch_assoc())): ?>
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

<?php if($unlock): ?>
<br><br><br>

<link rel=stylesheet type=text/css href=/plugins/jsPlugin/css/main-snake.css />
<div style="display:none">
<div id="mode-wrapper"><button id="Easy">Easy</button><br /><button id="Medium">Medium</button><br /><button id="Difficult">Difficult</button></div>
<button id="high-score">High Score</button>
</div>
<div id="game-area" tabindex="0"></div>
<script type="text/javascript" src="/plugins/jsPlugin/js/snake.js"></script>
<script type="text/javascript">
var mySnakeBoard = new SNAKE.Board(  {
  boardContainer: "game-area",
  fullScreen: false
});
</script>

<?php endif; ?>
<script>
var myCalendar = new dhtmlXCalendarObject(["calendar","calendar2"]);
myCalendar.setSkin("material");
myCalendar.setDateFormat("%Y-%m-%d");
</script>
<?php include 'footer.php'; ?>
