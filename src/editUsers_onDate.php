<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID); ?>
<!-- BODY -->
<div class="page-header">
  <h3><?php echo $lang['USERS']; ?></h3>
</div>

<?php
if(isset($_POST['save']) && isset($_POST['userID']) && $_POST['userID'] != 0 && test_Date($_POST['saveOnDate'] .' 05:00:00')){
  if (!empty($_POST['mon']) && is_numeric($_POST['mon'])) {
    $mon = $_POST['mon'];
  } else {
    $mon = 8.5;
  }

  if (!empty($_POST['tue']) && is_numeric($_POST['tue'])) {
    $tue = $_POST['tue'];
  } else {
    $tue = 8.5;
  }

  if (!empty($_POST['wed']) && is_numeric($_POST['wed'])) {
    $wed = $_POST['wed'];
  } else {
    $wed = 8.5;
  }

  if (!empty($_POST['thu']) && is_numeric($_POST['thu'])) {
    $thu = $_POST['thu'];
  } else {
    $thu = 8.5;
  }

  if (!empty($_POST['fri']) && is_numeric($_POST['fri'])) {
    $fri = $_POST['fri'];
  } else {
    $fri = 4.5;
  }

  if (!empty($_POST['sat']) && is_numeric($_POST['sat'])) {
    $sat = $_POST['sat'];
  } else {
    $sat = 0;
  }

  if (!empty($_POST['sun']) && is_numeric($_POST['sun'])) {
    $sun = $_POST['sun'];
  } else {
    $sun = 0;
  }

  $tense = timeDiff_Hours(substr(getCurrentTimestamp(),0,10) .' 05:00:00', $_POST['saveOnDate'] .' 05:00:00');
  $date = $_POST['saveOnDate'] .' 01:00:00';
  $userID = $_POST['userID'];
  if($tense > 0){ //future
    $eventName = 'changeTable'.$userID;
    $sql = "DROP EVENT IF EXISTS $eventName";
    $conn->query($sql);
    $sql = "CREATE EVENT $eventName
    ON SCHEDULE AT '$date'
    ON COMPLETION NOT PRESERVE ENABLE
    COMMENT 'Changing timetable on date'
    DO
    UPDATE $bookingTable SET mon = '$mon', tue ='$tue', wed = '$wed', thu = '$thu', fri = '$fri', sat= '$sat', sun = '$sun' WHERE userID = $userID
    ";
    if($conn->query($sql)){
      echo '<div class="alert alert-success fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo "Timetable will be changed on $date for user No.$userID, with: $mon - $tue - $wed - $thu - $fri - $sat - $sun";
      echo '</div>';
    } else {
      echo mysqli_error($conn);
    }
  } elseif($tense < 0){ //past
    //change unlogs
    $sql = "UPDATE $negative_logTable SET mon = '$mon', tue ='$tue', wed = '$wed', thu = '$thu', fri = '$fri', sat= '$sat', sun = '$sun'
    WHERE userID = $userID
    AND time >= '$date' ";
    $conn->query($sql);
    echo mysqli_error($conn);

    //change logs
    $sql = "SELECT * FROM $logTable WHERE userID = $userID AND time >= '$date'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      while($row = $result->fetch_assoc()){
        $expectedHours = 0.0;
        if(strtolower(date('D', strtotime($row['time']))) == 'mon'){
          $expectedHours = $mon;
        } elseif(strtolower(date('D', strtotime($row['time']))) == 'tue'){
          $expectedHours = $tue;
        } elseif(strtolower(date('D', strtotime($row['time']))) == 'wed'){
          $expectedHours = $wed;
        } elseif(strtolower(date('D', strtotime($row['time']))) == 'thu'){
          $expectedHours = $thu;
        } elseif(strtolower(date('D', strtotime($row['time']))) == 'fri'){
          $expectedHours = $fri;
        } elseif(strtolower(date('D', strtotime($row['time']))) == 'sat'){
          $expectedHours = $sat;
        } else {
          $expectedHours = $sun;
        }
        $sql = "UPDATE $logTable SET expectedHours = '$expectedHours' WHERE indexIM =". $row['indexIM'];
        $conn->query($sql);
        echo mysqli_error($conn);
      }
    }
    echo mysqli_error($conn);
    //change timetable
    $sql = "UPDATE $bookingTable SET mon = '$mon', tue ='$tue', wed = '$wed', thu = '$thu', fri = '$fri', sat= '$sat', sun = '$sun' WHERE userID = $userID";
    if($conn->query($sql)){
      echo '<div class="alert alert-success fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo "Timetable was changed on $date for user No.$userID, with: $mon - $tue - $wed - $thu - $fri - $sat - $sun";
      echo '</div>';
    } else {
      echo mysqli_error($conn);
    }
  } else { //now
    $sql = "UPDATE $bookingTable SET mon = '$mon', tue ='$tue', wed = '$wed', thu = '$thu', fri = '$fri', sat= '$sat', sun = '$sun' WHERE userID = $userID";
    $conn->query($sql);
  }
}


?>


<form method=post>
<script type="text/javascript">
$(document).ready(function() {
  $(".js-example-basic-single").select2({ width:'100%' });
});
</script>

<select name='userID'  class="js-example-basic-single" onchange="showTable(this.value)">
  <option name=usa value='0'>Select User ... </option>
<?php
$sql = "SELECT * FROM $userTable INNER JOIN $bookingTable ON $bookingTable.userID = $userTable.id";
$result = $conn->query($sql);
if($result && $result->num_rows > 0){
  $result->fetch_assoc(); //admin
  while($row = $result->fetch_assoc()){
    echo '<option name=usa value='.$row['id'].'>';
    echo $row['firstname'].' '.$row['lastname'];
    echo '</option>';
  }
}

?>
</select>

<br><br>

<div id=divHint>
</div>

<div>
<table class='table table-striped table-bordered'>
<tr>
<th>Mon</th>
<th>Tue</th>
<th>Wed</th>
<th>Thu</th>
<th>Fri</th>
<th>Sat</th>
<th>Sun</th>
</tr>
<tr>
<td><input type=number step=any name=mon value=8.5></input></td>
<td><input type=number step=any name=tue value=8.5></input></td>
<td><input type=number step=any name=wed value=8.5></input></td>
<td><input type=number step=any name=thu value=8.5></input></td>
<td><input type=number step=any name=fri value=4.5></input></td>
<td><input type=number step=any name=sat></input></td>
<td><input type=number step=any name=sun></input></td>
</tr>
</table>
</div>

<div style="clear: both;">
  <br><br>
<input  type=submit name=save value='Submit on: '> <input type=date name=saveOnDate value="<?php echo substr(getCurrentTimestamp(),0,10); ?>">
</div>

<script>
function showTable(str) {
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
        document.getElementById("divHint").innerHTML = xmlhttp.responseText;
      }
    };
    xmlhttp.open("GET","ajaxQuery/AJAX_displEdits.php?q="+str,true);
    xmlhttp.send();
  }
}
</script>

</form>
</body>
