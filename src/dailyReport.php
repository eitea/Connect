<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" type="text/css" href="../css/table.css">
  <link rel="stylesheet" type="text/css" href="../css/inputTypeTime.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
  <link rel="stylesheet" type="text/css" href="../css/inputTypeText.css">
  <link rel="stylesheet" type="text/css" href="../css/textArea.css">
  <link rel="stylesheet" href="../css/homeMenu.css">

  <link rel="stylesheet" type="text/css" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="../css/spanBlockInput.css">
  <link rel="stylesheet" type="text/css" href="../plugins/datepicker/codebase/dhtmlxcalendar.css">

  <script rel="stylesheet" src="../plugins/datepicker/codebase/dhtmlxcalendar.js"> </script>

<style>
  textarea{
    border-style: hidden;
    width:200px;
    display:inline-block;
    vertical-align:middle;
  }

  input[type="number"]{
    color:darkblue;
    font-family: monospace;
    border-style: hidden;
    width:55px;
    padding:2px;
    border-radius:5px;
    min-width:90px;
  }
</style>

</head>
<body>
<?php

session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php">return</a>');
}

require "connection.php";
require "createTimestamps.php";
require "language.php";
?>

<form method='post' action='getTimestamps.php'>
<h1><button type=submit style=background:none;border:none;><img src='../images/return.png' alt='return' style='width:35px;height:35px;border:0;margin-bottom:5px'></button><?php echo $lang['DAILY_USER_PROJECT']?></h1>

<br><br>

<?php
$filterDay = substr(getCurrentTimestamp(), 0, 10);
$userID = 0;
$booked = 0;

if(isset($_GET['filterDay'])){
  $filterDay = substr($_GET['filterDay'], 0, 10);
  $userID = substr($_GET['filterDay'], 10, strlen($_GET['filterDay']));
}

//variables needed for getTimestamp.php to work:
//$_POST['filteredYear']
//$_POST['filteredMonth']
//$_POST['filteredUserID']
?>
<input type=text name=filteredYear style=display:none; value="<?php echo substr($filterDay,0,4); ?>" >
<input type=text name=filteredMonth style=display:none; value="<?php echo substr($filterDay,5,2); ?>" >
<input type=text name=filteredUserID style=display:none; value="<?php echo $userID; ?>" >

</form>

<form method='post'>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['filterDay'])){
    $filterDay = substr($_POST['filterDay'], 0, 10);
    $userID = substr($_POST['filterDay'], 10, strlen($_POST['filterDay']));
  }

  if(isset($_POST['filterUserID'])){
    $userID = $_POST['filterUserID'];
  }

  if(isset($_POST['booked'])){
    $booked = $_POST['booked'];
  }

  if (isset($_POST['saveChanges']) && isset($_POST['editingIndeces'])) {
    for ($i = 0; $i < count($_POST['editingIndeces']); $i++) {
      $imm = $_POST['editingIndeces'][$i]; //projectBookingTable ID

      $query = "SELECT $logTable.timeToUTC
      FROM $logTable, $projectBookingTable
      WHERE $projectBookingTable.id = $imm
      AND $projectBookingTable.timestampID = $logTable.indexIM";
      $result = mysqli_query($conn, $query);

      if($result && $result->num_rows>0){
        $row = $result->fetch_assoc();
        $toUtc = $row['timeToUTC'] * -1;

        $timeStart = carryOverAdder_Hours($_POST['dateFrom'][$i]." ".$_POST['timesFrom'][$i], $toUtc);
        $timeFin = carryOverAdder_Hours($_POST['dateFrom'][$i]." ".$_POST['timesTo'][$i], $toUtc);
        $infoText = test_input($_POST['infoTextArea'][$i]);

        $sql = "UPDATE $projectBookingTable SET start='$timeStart', end='$timeFin', infoText='$infoText' WHERE id = $imm";
        $conn->query($sql);
        echo mysqli_error($conn);
      }
      echo mysqli_error($conn);
    }
  }

  if(isset($_POST['saveChanges']) && isset($_POST['checkingIndeces'])  && $_POST['booked'] == 1){
    foreach ($_POST["checkingIndeces"] as $e) {
      $sql = "UPDATE $projectBookingTable SET booked = 'TRUE'  WHERE id = $e;";
      $conn->query($sql);

      $sql = "SELECT start, end, projectID FROM $projectBookingTable WHERE id = $e";

      if($result = $conn->query($sql)){
        $row = $result->fetch_assoc();
        $hours = timeDiff_Hours($row['start'], $row['end']);

        $sql = "UPDATE $projectTable SET hours = hours - $hours WHERE id = ".$row['projectID'];
        $conn->query($sql);
        echo mysqli_error($conn);
      }
    }
  }

  if(isset($_POST['addBooking']) && isset($_POST['project']) && isset($_POST['addStart']) && isset($_POST['addEnd']) && $userID != 0){
    $sql = "SELECT * FROM $logTable WHERE time LIKE '$filterDay %' AND status = '0' AND userID = $userID";
    $result = mysqli_query($conn, $sql);
    if($result && $result->num_rows >0){
      $row = $result->fetch_assoc();

      $timeToUTC = $row['timeToUTC'];
      $start = carryOverAdder_Hours($filterDay. " " . $_POST['addStart'], $timeToUTC * -1);
      $end = carryOverAdder_Hours($filterDay. " " . $_POST['addEnd'], $timeToUTC * -1);
      $infoText = $_POST['addInfoText'];
      $projectID = $_POST['project'];
      $isBooked = (isset($_POST['addBooked']))?'TRUE':'FALSE';
      $indexIM = $row['indexIM'];

      $sql = "INSERT INTO $projectBookingTable(start, end, projectID, timestampID, infoText, booked) VALUES('$start', '$end', $projectID, $indexIM, '$infoText', '$isBooked');";

      $conn->query($sql);

      echo mysqli_error($conn);
    }
  }
}
?>

<input id="filterDateInput" style="border-style:solid; border-color:rgb(233, 233, 233)" type="text" size="11" name="filterDay" value="<?php echo $filterDay; ?>">

<script>
var myCalendar = new dhtmlXCalendarObject(["filterDateInput"]);
myCalendar.setSkin("material");
myCalendar.setDateFormat("%Y-%m-%d");
</script>

<select name="filterUserID">
  <option value=0>Select User...</option>
<?php
$sql = "SELECT * FROM $userTable WHERE enableProjecting = 'TRUE'";
$result = mysqli_query($conn, $sql);
if($result && $result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $first = $row['id'];
  do {
    $checked = '';
    if($userID == $row['id']) {
      $checked = 'selected';
    }
    echo "<option $checked value=".$row['id'].">".$row['firstname']. " " .$row['lastname']."</option>";

  } while($row = $result->fetch_assoc());
}
?>
</select>

<select name="booked">
  <option value='0' <?php if($booked == 0){echo 'selected';}?> >---</option>
  <option value='1' <?php if($booked == 1){echo 'selected';}?> ><?php echo $lang['NOT_CHARGED']; ?></option>
  <option value='2' <?php if($booked == 2){echo 'selected';}?> ><?php echo $lang['CHARGED']; ?></option>
</select>

<input type="submit" name="filter" value="Filter"><br><br>

<br><br>

<script>
function textAreaAdjust(o) {
    o.style.height = "1px";
    o.style.height = (o.scrollHeight)+"px";
}

function toggle(source) {
  checkboxes = document.getElementsByName('checkingIndeces[]');
  for(var i = 0; i<checkboxes.length; i++) {
    checkboxes[i].checked = source.checked;
  }
}

function showClients(str) {
  if (str != "") {
    if (window.XMLHttpRequest) {
      // code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp = new XMLHttpRequest();
    } else {
      // code for IE6, IE5
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
      if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
        document.getElementById("clientHint").innerHTML = xmlhttp.responseText;
        showProjects(xmlhttp.responseText);
      }
    };
    xmlhttp.open("GET","ajaxQuery/AJAX_getClient.php?company="+str,true);
    xmlhttp.send();
  }
}

function showProjects(str) {
  if (str != "") {
    if (window.XMLHttpRequest) {
      // code for IE7+, Firefox, Chrome, Opera, Safari
      xmlhttp = new XMLHttpRequest();
    } else {
      // code for IE6, IE5
      xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange = function() {
      if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
        document.getElementById("txtHint").innerHTML = xmlhttp.responseText;
      }
    };
    xmlhttp.open("GET","ajaxQuery/AJAX_getProjects.php?q="+str,true);
    xmlhttp.send();
  }
}
</script>

<table id='blank' class="table table-striped table-bordered">
  <thead>
<tr>
  <th><?php echo $lang['CLIENT']; ?></th>
  <th><?php echo $lang['PROJECT']; ?></th>
  <th>Info</th>
  <th><?php echo $lang['DATE']; ?></th>
  <th><?php echo $lang['TIME']; ?></th>
  <th><?php echo $lang['SUM']; ?> (min)</th>
  <th><?php echo $lang['SUM']; ?> (0.25h)</th>
  <th><?php echo $lang['CHARGED']; ?> <input type="checkbox" onClick="toggle(this)" /></th>
  <th><?php echo $lang['HOURLY_RATE'];?> (€)</th>
</tr>
</thead>

<?php
if($booked == '2'){
  $bookedQuery= "AND $projectBookingTable.booked = 'TRUE'";
} elseif($booked == '1') {
  $bookedQuery= "AND $projectBookingTable.booked = 'FALSE'";
} else {
  $bookedQuery = "";
}

  $sql="SELECT DISTINCT $clientTable.name AS clientName, $companyTable.name AS companyName, $projectTable.name AS projectName, $projectBookingTable.id AS projectBookingID,
                        $projectBookingTable.start, $projectBookingTable.end, $projectBookingTable.infoText, $logTable.timeToUTC,
                        $projectTable.hours, $projectBookingTable.booked, $projectTable.hourlyPrice, $logTable.indexIM
        FROM $projectBookingTable, $logTable, $userTable, $projectTable, $companyTable, $clientTable
        WHERE $projectBookingTable.timeStampID = $logTable.indexIM
        AND $projectBookingTable.projectID = $projectTable.id
        AND $projectTable.clientID = $clientTable.id
        AND $clientTable.companyID = $companyTable.id
        AND $logTable.userID = $userID
        AND $projectBookingTable.start LIKE '$filterDay %'
        $bookedQuery
        ORDER BY $projectBookingTable.start DESC";

  $result = mysqli_query($conn, $sql);
  if($result && $result->num_rows >0) {
    while($row = $result->fetch_assoc()) {
      $timeDiff = timeDiff_Hours($row['start'], $row['end']);
      $t = ceil($timeDiff * 4) / 4;

      echo "<tr>";
      echo "<td>" .$row['clientName']. "</td>";
      echo "<td>" .$row['projectName']. "</td>";
      echo "<td style='text-align:left'><textarea name='infoTextArea[]' onkeyup='textAreaAdjust(this)'>" .$row['infoText']. "</textarea></td>";
      echo "<td><input type='text' style='max-width:85px; background:none;' readonly name='dateFrom[]' value='".substr($row['start'], 0, 10)."'></td>";

      echo "<td><input onkeydown='if(event.keyCode == 13){return false;}' type='time' name='timesFrom[]' value='". substr(carryOverAdder_Hours($row['start'],$row['timeToUTC']),11,5) ."'>
      - <input onkeydown='if(event.keyCode == 13){return false;}' type='time' name='timesTo[]' value='". substr(carryOverAdder_Hours($row['end'],$row['timeToUTC']),11,5) ."'></td>";
      echo "<td>" .number_format((timeDiff_Hours($row['start'], $row['end']))*60, 2, '.', '') . "</td>";
      echo "<td>$t</td>";

      $selected = ($row['booked'] != 'TRUE') ? "":"checked";

      echo "<td><input type='checkbox' $selected name='checkingIndeces[]' value='".$row['projectBookingID']."'></td>";
      echo "<td>".$row['hourlyPrice']."</td>";
      echo "</tr>";

      echo '<input type="text" style="display:none;" name="editingIndeces[]" value="' . $row['projectBookingID'] . '">';

    }
  } else {
    echo mysqli_error($conn);
    echo "-";
  }

?>
</table>
<span class="blockInput">

<?php
$query = "SELECT * FROM $companyTable WHERE id IN (SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID) ";
$result = mysqli_query($conn, $query);
if($result->num_rows == 1):

  $row = $result->fetch_assoc();
  $query = "SELECT * FROM $clientTable WHERE companyID=".$row['id'];
  $result = mysqli_query($conn, $query);
  if ($result && $result->num_rows > 0) {
    echo '<select id="clientHint" name="client" onchange="showProjects(this.value)">';
    echo "<option name='act' value=0>Select...</option>";
    while ($row = $result->fetch_assoc()) {
      $cmpnyID = $row['id'];
      $cmpnyName = $row['name'];
      echo "<option name='act' value=$cmpnyID>$cmpnyName</option>";
    }
  }
  echo '</select>';
else:
?>

<select name="company" onchange="showClients(this.value)">
<option name=cmp value=0>Select...</option>
<?php
$query = "SELECT * FROM $companyTable WHERE id IN (SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID) ";
$result = mysqli_query($conn, $query);
if ($result && $result->num_rows > 1) {
  while ($row = $result->fetch_assoc()) {
    $cmpnyID = $row['id'];
    $cmpnyName = $row['name'];
    echo "<option name='cmp' value=$cmpnyID>$cmpnyName</option>";
  }
}
?>
</select>

<select id="clientHint" name="client" onchange="showProjects(this.value)">
</select>

<?php endif; ?>

<select id="txtHint" name="project">
</select>

<textarea rows="1" onkeyup="textAreaAdjust(this)" placeholder="Info" name="addInfoText">
</textarea>

<input type="time" onkeydown='if (event.keyCode == 13) return false;' name="addStart" size="4" >
- <input type="time" onkeydown='if (event.keyCode == 13) return false;' name="addEnd" size=4>

<?php echo $lang['CHARGED']; ?> <input type="checkbox" name="addBooked">

<input type="submit" name="addBooking" value="+"/>

<script>
$('#clientHint').selectize({
    create: true,
    sortField: 'text'
});
</script>
</span>


<br><br>
<?php
if(isset($_POST['filterBooked']) && $_POST['filterBooked'] == '1'){
  echo "<input type='submit' name='saveChanges' value='Save Changes'>";
}
?>
<br>

<script>
for(var i = 0; i < document.getElementsByName('infoTextArea[]').length; i++){
  textAreaAdjust(document.getElementsByName('infoTextArea[]')[i]);
}
</script>

</form>
</body>
