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

<?php
$filterDay = substr(getCurrentTimestamp(), 0, 10);
$userID = 0;
$booked = 0;

if(isset($_GET['filterDay'])){
  $filterDay = $_GET['filterDay'];
  $userID = $_GET['userID'];
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(isset($_POST['filterDay'])){
    $filterDay = substr($_POST['filterDay'], 0, 10);
  }

  if(isset($_POST['filterUserID'])){
    $userID = $_POST['filterUserID'];
  }

  if(isset($_POST['booked'])){
    $booked = $_POST['booked'];
  }
}


//variables needed for getTimestamp.php to work:
//$_POST['filteredYear']
//$_POST['filteredMonth']
//$_POST['filteredUserID']
?>
<form method='post' action='getTimestamps.php'>
<h1><button type=submit style=background:none;border:none;><img src='../images/return.png' alt='return' style='width:35px;height:35px;border:0;margin-bottom:5px'></button><?php echo $lang['DAILY_USER_PROJECT']?></h1>


<input type=text name=filteredYear style=display:none; value="<?php echo substr($filterDay,0,4); ?>" >
<input type=text name=filteredMonth style=display:none; value="<?php echo substr($filterDay,5,2); ?>" >
<input type=text name=filteredUserID style=display:none; value="<?php echo $userID; ?>" >

<br><br>
</form>



<form method='post'>
<input id="filterDateInput" style="border-style:solid; border-color:rgb(233, 233, 233)" type="text" size="11" name="filterDay" value="<?php echo $filterDay; ?>">

<script>
var myCalendar = new dhtmlXCalendarObject(["filterDateInput"]);
myCalendar.setSkin("material");
myCalendar.setDateFormat("%Y-%m-%d");
</script>

<select name="filterUserID">
  <option value=0>Select User...</option>
<?php
$sql = "SELECT * FROM $userTable";
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

</form>

<table id='blank' class="table table-striped table-bordered">
  <thead>
<tr>
  <th><?php echo $lang['CLIENT']; ?></th>
  <th><?php echo $lang['PROJECT']; ?></th>
  <th width=25%>Info</th>
  <th width=200px><?php echo $lang['TIME']; ?></th>
  <th><?php echo $lang['SUM']; ?> (min)</th>
  <th><?php echo $lang['SUM']; ?> (0.25h)</th>
  <th><?php echo $lang['CHARGED']; ?></th>
  <th><?php echo $lang['HOURLY_RATE'];?> (€)</th>
  <th><?php echo $lang['EDIT']; ?></th>
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

/*
$sql = "SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)
WHERE ($projectBookingTable.timestampID = $indexIM AND $projectBookingTable.start LIKE '$date %' )
OR ($projectBookingTable.projectID IS NULL AND $projectBookingTable.start LIKE '$date %' AND $projectBookingTable.timestampID = $indexIM) ORDER BY end ASC;";


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

reminder: looking for bugs? try to figure out why this sql thing works and if it is supposed to work (it works, but im not sure if its supposed to)...
        */
  $sql = "SELECT DISTINCT $clientTable.name AS clientName, $companyTable.name AS companyName, $projectTable.name AS projectName,
                        $projectBookingTable.start, $projectBookingTable.end, $projectBookingTable.infoText, $logTable.timeToUTC,
                        $projectBookingTable.booked, $projectTable.hourlyPrice, $projectBookingTable.projectID, $logTable.userID,
                        $projectTable.clientID, $clientTable.companyID
          FROM $projectBookingTable
          LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
          LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)
          LEFT JOIN $logTable ON ($projectBookingTable.timestampID = $logTable.indexIM)
          LEFT JOIN $companyTable ON ($clientTable.companyID = $companyTable.id)
          WHERE ($projectBookingTable.start LIKE '$filterDay %' AND $logTable.userID = $userID)
          OR ($projectBookingTable.projectID IS NULL AND $projectBookingTable.start LIKE '$filterDay %'  AND $logTable.userID = $userID ) ORDER BY end ASC;";

  $result = mysqli_query($conn, $sql);
  if($result && $result->num_rows >0) {
    while($row = $result->fetch_assoc()) {
      $timeDiff = timeDiff_Hours($row['start'], $row['end']);
      $t = ceil($timeDiff * 4) / 4;

      echo "<tr>";
      echo "<td>" .$row['clientName']. "</td>";
      echo "<td>" .$row['projectName']. "</td>";
      echo "<td style='text-align:left'>" .$row['infoText']. "</td>";

      $A =  carryOverAdder_Hours($row['start'],$row['timeToUTC']);
      $B = carryOverAdder_Hours($row['end'],$row['timeToUTC']);
      echo "<td>$A<br>$B</td>";
      echo "<td>" .number_format((timeDiff_Hours($row['start'], $row['end']))*60, 2, '.', '') . "</td>";
      echo "<td>$t</td>";

      $selected = ($row['booked'] != 'TRUE') ? "No":"Yes";

      echo "<td>$selected</td>";
      echo "<td>".$row['hourlyPrice']."</td>";

      if(!empty($row['projectID'])):
      echo '<td>';
      echo "<form method='post' action='getProjects.php'>";
      echo "<button type=submit style=background:none;border:none;><img src='../images/pencil.png' alt='edit' style='width:25px;height:25px;border:0;margin-bottom:5px'></button>";
      echo "<input name=filterCompany type=text style=display:none value=" .$row['companyID'].' />'; //filterCompany
      echo "<input name=filterYear type=text style=display:none value= " .substr($A,0,4).' />'; //Year
      echo "<input name=filterMonth type=text style=display:none value= " .substr($A,5,2).' />'; //Month
      echo "<input name=filterDay type=text style=display:none value= " .substr($A,8,2).' />'; //Day
      echo "<input name=filterUserID type=text style=display:none value= " .$row['userID'].' />'; //userID
      echo "<input name=filterClient type=text style=display:none value= " .$row['clientID'].' />';
      echo "<input name=filterProject type=text style=display:none value= " .$row['projectID'].' />';
      echo "</form>";
      echo '</td>';
      else :
        echo '<td></td>';
      endif;

      echo "</tr>";
    }
  } else {
    echo mysqli_error($conn);
  }

?>
</table>
</body>
