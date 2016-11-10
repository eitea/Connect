<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" href="../css/readonly.css">

  <link rel="stylesheet" type="text/css" href="../css/table.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
  <link rel="stylesheet" type="text/css" href="../css/textArea.css">

<style>
  span{
    display:block;
    padding:10px;
    background:#E8E8F1;
    padding-left: 20px;
  }

  input{
    margin-left:5px;
    margin-right:5px;
  }

</style>

</head>
<body>

<?php
  session_start();
  if (!isset($_SESSION['userid'])) {
    die('Please <a href="login.php">login</a> first.');
  }
  require "connection.php";
  require "createTimestamps.php";
  require "language.php";

  $userID = $_SESSION['userid'];
  $timeToUTC = $_SESSION['timeToUTC'];

  $sql = "SELECT * FROM $logTable WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00' AND status = '0'";
  $result = mysqli_query($conn, $sql);
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $start = substr(carryOverAdder_Hours($row['time'], $timeToUTC), 11, 19);
    $date = substr($row['time'], 0, 10);
    $indexIM = $row['indexIM'];
  } else {
    header("refresh:1;url=userHome.php");
    die("Automatic Redirecting... Invalid Access, Check In First.");
  }

$showUndoButton = 0;
$insertInfoText = '';
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST["add"]) && isset($_POST['end']) && !empty(trim($_POST['infoText']))) {
      $startDate = $_POST['date']." ".$_POST['start'];
      $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);

      $endDate = $_POST['date']." ".$_POST['end'];
      $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);

      if(timeDiff_Hours($startDate, $endDate) > 0){
        $info = test_input($_POST['infoText']);
        if(!isset( $_POST['project'])){
          echo '<div class="alert alert-danger fade in">';
          echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
          echo '<strong>Could not create entry: </strong>No Project Selected.';
          echo '</div>';
        } else {
          $projectID = $_POST['project'];
          if(timeDiff_hours($startDate, $endDate) >=0){
            $sql = "INSERT INTO $projectBookingTable (start, end, projectID, timestampID, infoText) VALUES('$startDate', '$endDate', $projectID, $indexIM, '$info')";
            $conn->query($sql);
          }
          $showUndoButton = TRUE;
        }
      } else {
        $insertInfoText = $_POST['infoText'];
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Could not create entry: </strong>Times were not valid.';
        echo '</div>';
      }
    } elseif(isset($_POST['addBreak']) && isset($_POST['startBreak']) && !empty(trim($_POST['infoTextBreak']))){
      $startDate = $_POST['date']." ".$_POST['start'];
      $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);
      $endDate = $_POST['date']." ".$_POST['startBreak'];
      $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);
      if(timeDiff_Hours($startDate, $endDate) > 0){
        $info = test_input($_POST['infoTextBreak']);
        $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText) VALUES('$startDate', '$endDate', $indexIM, '$info')";
        $conn->query($sql);

        $duration = timeDiff_Hours($startDate, $endDate);
        $sql= "UPDATE $logTable SET breakCredit = (breakCredit + $duration) WHERE indexIm = $indexIM";
        $conn->query($sql);
        $showUndoButton = TRUE;
      }
    } elseif(isset($_POST['add']) || isset($_POST['addBreak']) ) {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Could not create entry: </strong>Fields may not be empty.';
      echo '</div>';
    }
  }
?>

<form method="post">
  <h1><?php echo $lang['BOOK_PROJECTS']; ?></h1>
  <br>
  <?php if($showUndoButton): ?>
<div style='text-align:right;'><input type='submit' value='Undo' name='undo'></input></div>
  <?php endif; ?>
<table>
  <thead>
  <tr>
    <th>Start</th>
    <th><?php echo $lang['END']; ?></th>
    <th><?php echo $lang['DATE']; ?></th>
    <th><?php echo $lang['CLIENT']; ?></th>
    <th><?php echo $lang['PROJECT']; ?></th>
    <th>Info</th>
  </tr>
  </thead>

<?php
$readOnly = "";
$sql = "SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)
WHERE ($projectBookingTable.timestampID = $indexIM AND $projectBookingTable.start LIKE '$date %' )
OR ($projectBookingTable.projectID IS NULL AND $projectBookingTable.start LIKE '$date %' AND $projectBookingTable.timestampID = $indexIM) ORDER BY end ASC;";

$result = mysqli_query($conn, $sql);
if ($result && $result->num_rows > 0) {
  $numRows = $result->num_rows;
  if(isset($_POST['undo'])){
    $numRows--;
  }
  for ($i=0; $i<$numRows; $i++) {
    $row = $result->fetch_assoc();
    echo "<tr>";
    echo "<td>". substr(carryOverAdder_Hours($row['start'],$timeToUTC), 11, 8) ."</td>";
    echo "<td>". substr(carryOverAdder_Hours($row['end'], $timeToUTC), 11, 8) ."</td>";
    echo "<td>". substr(carryOverAdder_Hours($row['end'], $timeToUTC), 0, 10) ."</td>";
    echo "<td>". $row['name'] ."</td>";
    echo "<td>". $row['projectName'] ."</td>";
    echo "<td style='text-align:left'>". $row['infoText'] ."</td>";
    echo "</tr>";

    $start = substr(carryOverAdder_Hours($row['end'], $timeToUTC), 11, 8);
    $date = substr(carryOverAdder_Hours($row['end'], $timeToUTC), 0, 10);
  }
  if(isset($_POST['undo'])){
    $row = $result->fetch_assoc();
    if(empty($row['projectID'])){ //undo breaks
      $timeDiff = timeDiff_Hours($row['start'], $row['end']);
      $sql = "UPDATE $logTable SET breakCredit = (breakCredit - $timeDiff) WHERE indexIM = " . $row['timestampID'];
      $conn->query($sql);
    }
    echo "remove entry";
    $sql = "DELETE FROM $projectBookingTable WHERE id = " . $row['bookingTableID'];
    $conn->query($sql);
  }
}
?>
</table>

<script>
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
    xmlhttp.open("GET","ajaxQuery/AJAX_getClient.php?company="+str+"&p=0",true);
    xmlhttp.send();
  }
}
function textAreaAdjust(o) {
    o.style.height = "1px";
    o.style.height = (o.scrollHeight)+"px";
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
    xmlhttp.open("GET","ajaxQuery/AJAX_getProjects.php?q="+str+"&p=0",true);
    xmlhttp.send();
  }
}
</script><br><br>
<span>
    <input type="time" readonly onkeydown='if (event.keyCode == 13) return false;' name="start" size="4" value="<?php echo substr($start,0,5); ?>" >
  - <input type="time" min="<?php echo substr($start,0,5); ?>" max="<?php echo substr(carryOverAdder_Hours(getCurrentTimestamp(), $timeToUTC), 11, 5); ?>" onkeydown='if (event.keyCode == 13) return false;' name="end" size=4>

  <input type="date" readonly onkeydown='if (event.keyCode == 13) return false;' name="date" value= <?php echo $date; ?> >

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

<input type="submit" class="button" name="add" value="+"> <br>

<textarea maxlength="500" placeholder="Info" name="infoText" onkeyup='textAreaAdjust(this)'>
  <?php echo $insertInfoText; ?>
</textarea>
</span><br>

<span>
  <?php echo $lang['BREAK'] . ' '. $lang['TO'] ; ?>:
  <input type="time" min="<?php echo substr($start,0,5); ?>" max="<?php echo substr(carryOverAdder_Hours(getCurrentTimestamp(), $timeToUTC), 11, 5); ?>" onkeydown='if (event.keyCode == 13) return false;' name="startBreak" size="4" value=''>
  <input type="text" placeholder="Info" onkeydown='if (event.keyCode == 13) return false;' name="infoTextBreak">
  <input type="submit" class="button" name="addBreak" value="+">
</span>
</form>
</body>
