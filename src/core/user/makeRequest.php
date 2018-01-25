<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToStamps($userID); ?>
<style>
.robot-control{
  border:none;
  background:none;
  text-decoration: none;
  box-shadow:none;
}
</style>
<?php
$filterRequest_text = 'Alte ausblenden';
$filterRequest = $unlock = 0;

$currentMonth = substr(getCurrentTimestamp(),0,7);
$result = $conn->query("SELECT kmMoney FROM $userTable WHERE id = $userID");
$kmMoney = $result->fetch_assoc()['kmMoney'];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['captcha'])){
    die("Bot detected. Aborting all running Operations.");
  }
  if(isset($_POST['makeRequest']) && !empty($_POST['start']) && !empty($_POST['end']) && !empty($_POST['day'])){
    if(test_Date($_POST['day'].' '.$_POST['start'].':00') && test_Date($_POST['day'].' '.$_POST['end'].':00')){
      $result = $conn->query("SELECT coreTime FROM UserData WHERE id = $userID");
      $row = $result->fetch_assoc();
      $begin = test_input($_POST['day'].' '.$_POST['start'].':00');
      $end = test_input($_POST['day'].' '.$_POST['end'].':00');
      $infoText = test_input($_POST['requestText']);
      $type = test_input($_POST['requestType']);
      $sql = "INSERT INTO $userRequests (userID, fromDate, toDate, requestText, requestType) VALUES($userID, '$begin', '$end', '$infoText', '$type')";
      $conn->query($sql);
      echo mysqli_error($conn);
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    }

  }elseif(isset($_POST['makeRequest']) && !empty($_POST['start']) && !empty($_POST['end'])){
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
  }elseif(isset($_POST['makeRequest'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
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
      if($conn->error){
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
      } else {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
      }
    }
    echo mysqli_error($conn);
  }
  if(isset($_POST['filterRequest_all'])){
    $filterRequest_text = $lang['DISPLAY_ALL'];
    $filterRequest = 1;
  }
  if(isset($_POST['addDrive']) && !empty($_POST['addInfoText']) && !empty($_POST['addTimeStart']) && !empty($_POST['addTimeEnd'])){
    $timeStart = test_input($_POST['addDate'].' '.$_POST['addTimeStart']) .'.00';
    $timeEnd = test_input($_POST['addDate'].' '.$_POST['addTimeEnd']) .'.00';

    $kmStart = intval($_POST['addKmStart']);
    $kmEnd = intval($_POST['addKmEnd']);
    $countryID = intval($_POST['addCountry']);

    $infotext = test_input($_POST['addInfoText']);

    $expenses = floatval($_POST['addExpenses']);
    $hosting10 = floatval($_POST['addHosting10']);
    $hosting20 = floatval($_POST['addHosting20']);
    $hotel = floatval($_POST['addHotel']);

    if($kmStart <= $kmEnd){
      $sql = "INSERT INTO $travelTable (userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses)
      VALUES($userID, $countryID, '$timeStart', '$timeEnd', '$kmStart', '$kmEnd', '$infotext', '$hotel', '$hosting10', '$hosting20', '$expenses')";
      if(!$conn->query($sql)){
        echo mysqli_error($conn);
      }
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Falsche Eingabe: </strong>km-Stand Anfang muss kleiner sein als km-Stand Ende.</div>';
    }
  } elseif(isset($_POST['addDrive'])) {
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Fehler: </strong>Hervorgehobene Felder dürfen nicht leer sein.</div>';
  }
}
?>

<div class="page-header"><h3><?php echo $lang['REQUESTS']?></h3></div>

<h4><?php echo $lang['MY_REQUESTS']; ?></h4><br>
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
  </tr>
  <tbody>
      <?php
      while($row = $result->fetch_assoc()){
        //hide: old requests, accepted/ denied requests,
        if(!$filterRequest && timeDiff_Hours($row['toDate'], getCurrentTimestamp()) > 0 && $row['status'] != 0){
          continue;
          //always display: future dates, open requests
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
        if($row['requestType'] == 'log'||$row['requestType'] == 'doc'){
          echo '<td>'.substr($row['fromDate'],0,16).'</td>';
          echo '<td>'.substr($row['toDate'],0,16).'</td>';
        } else {
          echo '<td>'.substr($row['fromDate'],0,10) .'</td>';
          echo '<td>'.substr($row['toDate'],0,10) .'</td>';
        }
        echo "<td style='color:$style'>" . $lang['REQUESTSTATUS_TOSTRING'][$row['status']] .'</td>';
        echo '<td>' . $row['answerText'] . '</td>';
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

<br><hr><br>
<h4><?php echo $lang['MAKE_REQUEST']; ?></h4><br>

<form method="POST" onsubmit="giveData(event)">
<div class="row"><div class="col-sm-3 col-sm-offset-1"><label><?php echo $lang['TIMES']; ?>: </label></div></div>
  <div class="row">
    <div class="col-sm-3 col-sm-offset-1">
      <select onchange="checkIfDoc(event)" name="requestType" class="js-example-basic-single">
        <option value="vac"><?php echo $lang['VACATION']; ?></option>
        <option value="scl"><?php echo $lang['VOCATIONAL_SCHOOL']; ?></option>
        <option value="spl"><?php echo $lang['SPECIAL_LEAVE']; ?></option>
        <option value="cto"><?php echo $lang['COMPENSATORY_TIME']; ?></option>
        <option value="doc"><?php echo $lang['DOCTOR']; ?></option>
      </select>
    </div>
    <div class="col-sm-3"><input type="text" class="form-control" placeholder="Info... (Optional)" name="requestText"></div>
    </div>
    <div class="row">
    <div class="col-sm-3 col-sm-offset-1">
      <div class="input-group input-daterange">
        <span class="input-group-addon"> <?php echo $lang['FROM'];?> </span><input type="text" class="form-control datepicker" id="from" name="start"></div>
    </div>
    <div class="col-sm-3">
      <div class="input-group input-daterange">
        <span class="input-group-addon"> <?php echo $lang['TO'];?> </span><input type="text" class="form-control datepicker" id="to" name="end"></div>
    </div>
    <div class="col-sm-3"><button class="btn btn-warning" type="submit" name="makeRequest"><?php echo $lang['MAKE_REQUEST']; ?></button><br></div>
  </div>
</form>
<?php $result = $conn->query("SELECT indexIM FROM logs WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00'");
if($result && ($row = $result->fetch_assoc())): ?>
<form method="POST"><br>
  <div class="row"><div class="col-sm-3 col-sm-offset-1"><label><?php echo $lang['FORGOTTEN_LUNCHBREAK']; ?>: </label></div></div>
  <div class="row">
    <div class="col-sm-3 col-sm-offset-1"><div class="input-group"><span class="input-group-addon"><?php echo $lang['FROM']; ?></span><input type="text" class="form-control timepicker" name="lunch_FROM" /></div></div>
    <div class="col-sm-3"><div class="input-group"><span class="input-group-addon"><?php echo $lang['TO']; ?></span><input type="text" class="form-control timepicker" name="lunch_TO" /></div></div>
    <div class="col-sm-3"><button type="submit" class="btn btn-warning" name="request_lunchbreak" value="<?php echo $row['indexIM']; ?>"><?php echo $lang['MAKE_REQUEST']; ?></button></div>
  </div>
</form>
<?php endif; ?>

<!-- ############################## TRAVELS ################################ --><br><hr><br>

<h4><?php echo $lang['TRAVEL_FORM']; ?></h4><br>
<div class="container-fluid">
  <div class="col-md-4">
    Name: <?php echo $_SESSION['firstname']; ?>
    <br><br>
    Monat: <?php echo $lang['MONTH_TOSTRING'][intval(substr($currentMonth,5,2))]; ?>
  </div>
  <div class="col-md-4">
    <strong>Kilometergeld: <?php echo $kmMoney; ?> <small> pro km </small></strong>
  </div>
</div>
<br><br>
<table class="table table-hover table-condensed">
  <thead class="h6" style="border-width:medium; border-top-style:solid; border-color:#e8e8e8">
    <th>Reiseantritt</th>
    <th>Reiseende</th>
    <th style="text-center">km Anfang</th>
    <th style="text-center">km Ende</th>
    <th>Grund-Ort-Firma</th>
    <th style="background-color:#f1f3f4">Dauer</th>
    <th style="background-color:#f1f3f4">Land</th>
    <th style="background-color:#f1f3f4">Taggeld</th>
    <th style="background-color:#f1f3f4">km</th>
    <th style="background-color:#f1f3f4">kmGeld</th>
    <th>Hotel</th>
    <th style="text-center">Bewirtung 10%</th>
    <th style="text-center">Bewirtung 20%</th>
    <th>Spesen</th>
    <th style="background-color:#f1f3f4">Total</th>
  </thead>
  <tbody>
    <?php
    $sql = "SELECT * FROM $travelTable INNER JOIN $travelCountryTable ON $travelCountryTable.id = $travelTable.countryID WHERE travelDayStart LIKE '$currentMonth%' AND userID = $userID";
    $result = $conn->query($sql);
    $durationSum = $daySum = $kmSum = $kmPaySum = $hotelSum = $hosting10Sum = $hosting20Sum = $expensesSum = $totalSum = 0;
    while($result && ($row = $result->fetch_assoc())):
      $countryMun = $row['dayPay'];
      $timeDiff = timeDiff_Hours($row['travelDayStart'], $row['travelDayEnd']);
      if($timeDiff <= 3){
        $dayPay = 0;
      } elseif($timeDiff <= 12){
        $dayPay = $timeDiff * $countryMun/12;
      } else {
        $dayPay = $countryMun;
      }
      $drovenKM = $row['kmEnd'] - $row['kmStart'];
      $drovenKMPay = $drovenKM * $kmMoney;
      $total = ($drovenKM * $kmMoney) + $row['hotelCosts'] + $row['hosting10'] + $row['hosting20'] + $row['expenses'];


      $durationSum += $timeDiff;
      $daySum += $dayPay;
      $kmSum += $drovenKM;
      $kmPaySum += $drovenKMPay;
      $hotelSum +=$row['hotelCosts'];
      $hosting10Sum += $row['hosting10'];
      $hosting20Sum += $row['hosting20'];
      $expensesSum += $row['expenses'];
      $totalSum += $total;
      ?>
      <tr>
        <td><?php echo substr($row['travelDayStart'],0,-3); ?></td>
        <td><?php echo substr($row['travelDayEnd'],0,-3); ?></td>
        <td><?php echo $row['kmStart']; ?></td>
        <td><?php echo $row['kmEnd']; ?></td>

        <td><?php echo $row['infoText']; ?></td>

        <td style="background-color:#f2f4f5"><?php echo sprintf("%.2f", $timeDiff); ?></td>
        <td style="background-color:#f2f4f5"><?php if(!empty($row['identifier'])){echo $row['identifier'];} ?></td>
        <td style="background-color:#f2f4f5"><?php echo sprintf("%.2f", $dayPay); ?></td>
        <td style="background-color:#f2f4f5"><?php echo $drovenKM; ?></td>
        <td style="background-color:#f2f4f5"><?php echo $drovenKMPay; ?></td>

        <td><?php echo $row['hotelCosts']; ?></td>
        <td><?php echo $row['hosting10']; ?></td>
        <td><?php echo $row['hosting20']; ?></td>
        <td><?php echo $row['expenses']; ?></td>

        <td style="background-color:#f2f4f5"><?php echo $total; ?></td>
      </tr>
    <?php endwhile; ?>
    <tr style="font-weight:bold; text-align:right;">
      <td></td>
      <td></td>
      <td></td>
      <td></td>
      <td>Summen:</td>
      <td><?php echo sprintf("%.2f", $durationSum); ?></td>
      <td></td>
      <td><?php echo sprintf("%.2f", $daySum);?></td>
      <td><?php echo $kmSum;?></td>
      <td><?php echo $kmPaySum;?></td>
      <td><?php echo $hotelSum;?></td>
      <td><?php echo $hosting10Sum;?></td>
      <td><?php echo $hosting20Sum;?></td>
      <td><?php echo $expensesSum;?></td>
      <td><?php echo $totalSum;?></td>
    </tr>
  </tbody>
</table>
<br>
<hr><hr>
<br>

<form method="POST" autocomplete="off">
  <div class="row">
    <div class="col-md-3">
      <input type="text" class="form-control required-field datepicker" placeholder="YYYY-MM-DD" onkeydown='if (event.keyCode == 13) return false;' name="addDate" value="<?php echo substr(getCurrentTimestamp(),0,10); ?>">
    </div>
    <div class="col-md-3">
      <div class="input-group">
        <input type="time"  class="form-control required-field timepicker" placeholder="00:00" onkeydown='if (event.keyCode == 13) return false;' name="addTimeStart" />
        <span class="input-group-addon"> - </span>
        <input type="time" class="form-control required-field timepicker" placeholder="00:00" onkeydown='if (event.keyCode == 13) return false;' name="addTimeEnd" />
      </div>
    </div>
    <div class="col-md-5">
      <div class="input-group">
        <input type="number" class="form-control" name="addKmStart" placeholder="km-Stand Anfang" />
        <span class="input-group-addon"> - </span>
        <input type="number" class="form-control" name="addKmEnd" placeholder="km-Stand Ende" />
      </div>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-2">
      <select class="js-example-basic-single btn-block" name="addCountry">
        <?php
        $result = mysqli_query($conn, "SELECT * FROM $travelCountryTable");
        while($result && ($row = $result->fetch_assoc())){
          echo "<option value=".$row['id'].">".$row['countryName']."</option>";
        }
        ?>
      </select>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-md-9">
      <textarea class="form-control required-field" rows="6" name="addInfoText" placeholder="Grund - Besuchte Orte - Firmen"></textarea><br>
    </div>
    <div class="col-md-2">
      <input type="number" step="any" class="form-control" name="addHotel" placeholder="Hotelkosten">
      <input type="number" step="any" class="form-control" name="addHosting10" placeholder="Bewirtung 10%">
      <input type="number" step="any" class="form-control" name="addHosting20" placeholder="Bewirtung 20%">
      <input type="number" step="any" class="form-control" name="addExpenses" placeholder="Spesen">
    </div>
  </div>
  <br><br>
  <div class="text-right">
    <button type="submit" class="btn btn-warning" name="addDrive"><?php echo $lang['ADD']; ?></button>
  </div>
  <input id="myCaptcha" class="robot-control" type="text" name="captcha" value="" />
</form>


<script>
  var wasDoc = false;
  function checkIfDoc(evt){
    if(evt.currentTarget.selectedOptions[0].value === "doc"){
      wasDoc = true;
      var form = evt.currentTarget.form;
      var divOut = document.createElement("DIV");
      var divIn = document.createElement("DIV");
      var span = document.createElement("SPAN");
      var inputDay = document.createElement("INPUT");
      var inputFrom = document.createElement("INPUT");
      var inputTo = document.createElement("INPUT");
      divOut.className = "col-sm-3 col-sm-offset-1";
      divOut.id = "deleteMe";
      divIn.className = "input-group input-daterange";
      span.className = "input-group-addon";
      span.innerHTML = "<?php echo $lang['DAY']; ?>";
      inputDay.type = "TEXT";
      inputDay.name = "day";
      inputDay.className = "form-control datepicker";
      inputFrom.className = "form-control timepicker";
      inputFrom.name = "start";
      inputFrom.id = "from";
      inputTo.className = "form-control timepicker";
      inputTo.name = "end";
      inputTo.id = "to";
      divIn.append(span);
      divIn.append(inputDay);
      divOut.append(divIn);
      divOut.append(document.createElement("BR"));
      form.append(divOut);
      var parent = document.getElementById('from').parentNode;
      parent.removeChild(parent.lastElementChild);
      parent.appendChild(inputFrom);
      parent = document.getElementById('to').parentNode;
      parent.removeChild(parent.lastElementChild);
      parent.appendChild(inputTo);
      onPageLoad();
    }else{
      if(wasDoc){
        wasDoc = false;
        var form = evt.currentTarget.form;
        var inputFrom = document.getElementById("from");
        var inputTo = document.getElementById("to");
        inputFrom.className = "form-control datepicker";
        inputFrom.name = "from";
        inputTo.className = "form-control datepicker";
        inputTo.name = "to";
        form.removeChild(document.getElementById("deleteMe"));
        onPageLoad();
      }
    }
  }
</script>

<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
