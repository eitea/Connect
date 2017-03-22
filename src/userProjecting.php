<?php include 'header.php'; ?>
<?php enableToBookings($userID);?>
<style>
.robot-control{
  display:none;
}
</style>
<?php
$sql = "SELECT * FROM $logTable WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00' AND status = '0'";
$result = mysqli_query($conn, $sql);
if ($result && $result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $start = substr(carryOverAdder_Hours($row['time'], $timeToUTC), 11, 19);
  $date = substr($row['time'], 0, 10);
  $indexIM = $row['indexIM']; //this value must not change
} else {
  redirect("home.php");
}
?>

<div class="page-header">
  <h3><?php echo $lang['BOOK_PROJECTS'] .'<small>: &nbsp ' . $date .'</small>'; ?></h3>
</div>

<?php
$showUndoButton = $showEmergencyUndoButton = 0;
$insertInfoText = $insertInternInfoText = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if(!empty($_POST['captcha'])){
    die("Bot detected. Aborting all Operations.");
  }

  if(isset($_POST["add"]) && isset($_POST['end']) && !empty(trim($_POST['infoText']))) {
    $startDate = $date." ".$_POST['start'];
    $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);

    $endDate = $date." ".$_POST['end'];
    $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);

    $insertInfoText = test_input($_POST['infoText']);
    $insertInternInfoText = test_input($_POST['internInfoText']);

    if(timeDiff_Hours($startDate, $endDate) > 0){
      if(isset($_POST['addBreak'])){ //break
        $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$startDate', '$endDate', $indexIM, '$insertInfoText' , 'break')";
        $conn->query($sql);
        $duration = timeDiff_Hours($startDate, $endDate);
        $sql= "UPDATE $logTable SET breakCredit = (breakCredit + $duration) WHERE indexIm = $indexIM"; //update break credit
        $conn->query($sql);
        $insertInfoText = $insertInternInfoText = '';
        $showUndoButton = TRUE;
      } else {
        if(isset($_POST['project'])){
          $projectID = test_input($_POST['project']);
          if(isset($_POST['addDrive'])){ //add as driving time
            $sql = "INSERT INTO $projectBookingTable (start, end, projectID, timestampID, infoText, internInfo, bookingType) VALUES('$startDate', '$endDate', $projectID, $indexIM, '$insertInfoText', '$insertInternInfoText', 'drive')";
          } else { //normal booking
            $sql = "INSERT INTO $projectBookingTable (start, end, projectID, timestampID, infoText, internInfo, bookingType) VALUES('$startDate', '$endDate', $projectID, $indexIM, '$insertInfoText', '$insertInternInfoText', 'project')";
          }
          $conn->query($sql);
          $insertInfoText = $insertInternInfoText = '';
          $showUndoButton = TRUE;
        } else {
          echo '<div class="alert alert-danger fade in">';
          echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
          echo '<strong>Could not create entry: </strong>No Project selected.';
          echo '</div>';
        }
      }
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Could not create entry: </strong>Times were not valid.';
      echo '</div>';
    }
  } elseif(isset($_POST['add'])) {
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Could not create entry: </strong>Fields may not be empty.';
    echo '</div>';
  }
  echo '<br>';
}

if(isset($_POST['undo']) && $_POST['undo'] == 'emergency'){
  $conn->query("UPDATE $userTable SET emUndo = UTC_TIMESTAMP WHERE id = $userID");
}

$result = $conn->query("SELECT emUndo FROM $userTable WHERE id = $userID");
$row = $result->fetch_assoc();
if(timeDiff_Hours($row['emUndo'], getCurrentTimestamp()) > 2){
  $showEmergencyUndoButton = TRUE;
}
?>

<form method="post">
  <?php if($showUndoButton): ?>
    <div style='text-align:right;'><button type='submit' class="btn btn-warning" name='undo' value='noEmergency'>Undo</button></div>
  <?php elseif($showEmergencyUndoButton): ?>
    <div style='text-align:right;'><button type='submit' class="btn btn-danger" name='undo' value='emergency' title='Emergency Undo. Can only be pressed every 2 Hours'>Undo</button></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-12">
      <table class="table table-hover table-striped">
        <thead>
            <th></th>
            <th>Start</th>
            <th><?php echo $lang['END']; ?></th>
            <th><?php echo $lang['CLIENT']; ?></th>
            <th><?php echo $lang['PROJECT']; ?></th>
            <th>Info</th>
            <th>Intern</th>
        </thead>
        <tbody>
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

              if($row['bookingType'] == 'break'){
                $icon = "fa fa-cutlery";
              } elseif($row['bookingType'] == 'drive'){
                $icon = "fa fa-car";
              } else {
                $icon = "fa fa-star-o"; //fa-paw, fa-moon-o, star-o, snowflake-o, heart, umbrella, leafs, bolt, music, bookmark
              }

              echo "<tr>";
              echo "<td><i class='$icon'></i></td>";
              echo "<td>". substr(carryOverAdder_Hours($row['start'],$timeToUTC), 11, 5) ."</td>";
              echo "<td>". substr(carryOverAdder_Hours($row['end'], $timeToUTC), 11, 5) ."</td>";
              echo "<td>". $row['name'] ."</td>";
              echo "<td>". $row['projectName'] ."</td>";
              echo "<td style='text-align:left'>". $row['infoText'] ."</td>";
              echo "<td style='text-align:left'>". $row['internInfo'] ."</td>";
              echo "</tr>";

              $start = substr(carryOverAdder_Hours($row['end'], $timeToUTC), 11, 8);
              $date = substr(carryOverAdder_Hours($row['end'], $timeToUTC), 0, 10);
            }
            if(isset($_POST['undo'])){
              $row = $result->fetch_assoc();
              if($row['bookingType'] == 'break'){ //undo breaks
                $timeDiff = timeDiff_Hours($row['start'], $row['end']);
                $sql = "UPDATE $logTable SET breakCredit = (breakCredit - $timeDiff) WHERE indexIM = " . $row['timestampID'];
                $conn->query($sql);
              }
              $sql = "DELETE FROM $projectBookingTable WHERE id = " . $row['bookingTableID'];
              $conn->query($sql);
            }
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>


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
    o.style.height = "90px";
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

  function hideMyDiv(o){
    if(o.checked){
      document.getElementById('mySelections').style.display='none';
    } else {
      document.getElementById('mySelections').style.display='inline';
    }
  }
  </script>

  <br><br><br>

  <div class="container-fluid">
    <div class="checkbox">
      <div class="col-sm-2">
        <input type="checkbox" onclick="hideMyDiv(this)" name="addBreak" title="Das ist eine Pause"> <a style="color:black;"> <i class="fa fa-cutlery" aria-hidden="true"> </i> </a> Pause
      </div>
      <div class="col-sm-3">
        <input type="checkbox" name="addDrive" title="Fahrzeit"> <a style="color:black;"> <i class="fa fa-car" aria-hidden="true"> </i> </a> Fahrzeit
      </div>
    </div>
  </div>

  <!-- SELECTS -->
  <div class="row">
    <div id=mySelections class="col-xs-9"><br>
      <?php
      $query = "SELECT * FROM $companyTable WHERE id IN (SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID) ";
      $result = mysqli_query($conn, $query);
      if($result->num_rows == 1):

        $row = $result->fetch_assoc();
        $query = "SELECT * FROM $clientTable WHERE companyID=".$row['id'];
        $result = mysqli_query($conn, $query);
        if ($result && $result->num_rows > 0) {
          echo '<select style="width:200px" class="js-example-basic-single" id="clientHint" name="client" onchange="showProjects(this.value)">';
          echo "<option name='act' value=0>Firma...</option>";
          while ($row = $result->fetch_assoc()) {
            $cmpnyID = $row['id'];
            $cmpnyName = $row['name'];
            echo "<option name='act' value=$cmpnyID>$cmpnyName</option>";
          }
        }
        echo '</select>';
      else:
        ?>
        <select name="company"  class="js-example-basic-single" style='width:200px' class="" onchange="showClients(this.value)">
          <option name=cmp value=0>Firma...</option>
          <?php
          $query = "SELECT * FROM $companyTable WHERE id IN (SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID) ";
          $result = mysqli_query($conn, $query);
          if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $cmpnyID = $row['id'];
              $cmpnyName = $row['name'];
              echo "<option name='cmp' value=$cmpnyID>$cmpnyName</option>";
            }
          }
          ?>
        </select>

        <select id="clientHint" style='width:200px' class="js-example-basic-single" name="client" onchange="showProjects(this.value)">
        </select>

      <?php endif; ?>

      <select id="txtHint" style='width:200px' class="js-example-basic-single" name="project">
      </select>
    </div>
  </div>
  <!-- /SELECTS -->

  <div class="row">
    <div class="col-md-8">
      <br><textarea class="form-control" style='resize:none;overflow:hidden' rows="3" name="infoText" placeholder="Info..."  onkeyup='textAreaAdjust(this);'><?php echo $insertInfoText; ?></textarea><br>
    </div>
    <div class="col-md-4">
      <br><textarea class="form-control" style='resize:none;overflow:hidden' rows="3" name="internInfoText" placeholder="Intern... (Optional)" onkeyup='textAreaAdjust(this);'><?php echo $insertInternInfoText; ?></textarea><br>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="input-group input-daterange">
        <input type="time" class="form-control" readonly name="start" value="<?php echo substr($start,0,5); ?>" >
        <span class="input-group-addon"> - </span>
        <input type="time" class="form-control"  min="<?php echo substr($start,0,5); ?>"  name="end" value="<?php echo substr(carryOverAdder_Hours(getCurrentTimestamp(), $timeToUTC), 11, 5); ?>">
        <div class="input-group-btn">
          <button class="btn btn-warning" type="submit"  name="add"> + </button>
        </div>
      </div>
    </div>
  </div>
  <div class="robot-control"> <input type="text" name="captcha" value="" /></div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
