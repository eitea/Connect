<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" type="text/css" href="../plugins/datepicker/codebase/dhtmlxcalendar.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" href="../css/table.css">
  <link rel="stylesheet" href="../css/spanBlockInput.css">
  <link rel="stylesheet" href="../css/submitButt.css">

  <script src="../plugins/datepicker/codebase/dhtmlxcalendar.js"> </script>

</head>
<body>
<form method="post">
<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
$userID = $_SESSION['userid'];
require 'connection.php';
require 'language.php';
require 'createTimestamps.php';

$message = '';
if(isset($_POST['makeRequest']) && !empty($_POST['start']) && !empty($_POST['end']) && !empty($_POST['requestText'])){
  if(test_Date($_POST['start']) && test_Date($_POST['end'])){
    $sql = "INSERT INTO $userRequests (userID, fromDate, toDate, requestText) VALUES($userID, '".$_POST['start']."', '".$_POST['end']."', '".$_POST['requestText']."')";
    $conn->query($sql);
  } else {
    echo 'Invalid Dates.';
  }
} else {
  $message = $lang['FIELDS_REQUIRED'];
}
?>

<h1><?php echo $lang['VACATION']?></h1>

<br>
<div style=float:left;padding-bottom:500px>
<table>
  <tr>
    <td>From: </td><td> <input id="calendar" type='text' name='start' value=''> </td>
  </tr>
  <tr>
    <td>Until: </td><td> <input id="calendar2" type='text' name='end' value=''> </td>
  </tr>
  <tr>
    <td><input type='submit' name='makeRequest' value='Request Vacation' ></td> <td> <input type="text" name="requestText" value="" placeholder = "Info Text"> </td>
  </tr>
  <tr>
    <td><small>*<?php echo $message; ?></small></td>
  </tr>
</table>
</div>

<script>
var myCalendar = new dhtmlXCalendarObject(["calendar","calendar2"]);
myCalendar.setSkin("material");
myCalendar.setDateFormat("%Y-%m-%d %H:%i:%s");
</script>

<div style=float:left;margin-left:5%;>
<table>
  <tr>
    <th style=width:50%><?php echo $lang['TIME']; ?></th>
    <th>Status</th>
    <th><?php echo $lang['REPLY_TEXT']; ?>

    <?php
    $sql = "SELECT * FROM $userRequests WHERE userID = $userID";
    $result = $conn->query($sql);
    if($result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        $style = "";
        if($row['status'] == 0) {
          $style="style=background-color:#ffdf5b";
        } elseif ($row['status'] == 1) {
          $style="style=background-color:#fc8542";
        } elseif ($row['status'] == 2){
          $style="style=background-color:#abff99";
        }
        echo "<tr $style>";
        echo '<td>' . substr($row['fromDate'],0,16) .' - '. substr($row['toDate'],0,16) .'</td>';
        echo '<td>' . $lang_vacationRequestStatus[$row['status']] . '</td>';
        echo '<td>' . $row['answerText'] . '</td>';
        echo '</tr>';
      }
    }
     ?>
  </tr>
</table>
<br><br><br>
</div>

</form>
</body>
</html>
