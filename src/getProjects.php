<?php include 'header.php'; ?>
<?php include "../vendor/deblan/Csv.php"; use Deblan\Csv\Csv; ?>
<?php include 'validate.php'; enableToProject($userID)?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['VIEW_PROJECTS']; ?></h3>
</div>

<?php
$filterDate = substr(getCurrentTimestamp(),0,10); //granularity: set the default to day
$booked = '1';
$filterCompany = 0;
$filterClient = 0;
$filterProject = 0;
$filterUserID = 0;

//careful stairs
if(!empty($_POST['filterYear'])){
  $filterDate = $_POST['filterYear'];
  if(!empty($_POST['filterMonth'])){
    $filterDate .= '-' . $_POST['filterMonth'];
    if(!empty($_POST['filterDay'])){
      $filterDate .= '-' . $_POST['filterDay'];
    }
  }
}

if(isset($_POST['filterCompany'])){
  $filterCompany = $_POST['filterCompany'];
}

if(isset($_POST['filterBooked'])){
  $booked = $_POST['filterBooked'];
}

if(isset($_POST['filterClient'])){
  $filterClient = $_POST['filterClient'];
}

if(isset($_POST['filterProject'])){
  $filterProject = $_POST['filterProject'];
}

if(isset($_POST['filterUserID'])){
  $filterUserID = $_POST['filterUserID'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if(isset($_POST["add"]) && isset($_POST['end']) && !empty(trim($_POST['infoText']))) {
    //get the timestamp. if it doesnt exist -> display a biiiig fat error
    $sql = "SELECT * FROM $logTable WHERE userID = $filterUserID AND time LIKE '$filterDate %' AND status = '0'";
    $result = mysqli_query($conn, $sql);
    if($result && $result->num_rows>0){
      $row = $result->fetch_assoc();
      $indexIM = $row['indexIM']; //this value mustnt change

      $startDate = $filterDate." ".$_POST['start'];
      $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);

      $endDate = $filterDate." ".$_POST['end'];
      $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);

      $insertInfoText = test_input($_POST['infoText']);
      $insertInternInfoText = test_input($_POST['internInfoText']);

      if(timeDiff_Hours($startDate, $endDate) > 0){
        if(isset($_POST['addBreak'])){ //checkbox
          $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText) VALUES('$startDate', '$endDate', $indexIM, '$insertInfoText')";
          $conn->query($sql);
          $duration = timeDiff_Hours($startDate, $endDate);
          $sql= "UPDATE $logTable SET breakCredit = (breakCredit + $duration) WHERE indexIm = $indexIM";
          $conn->query($sql);
          $showUndoButton = TRUE;
        } else {
          if(isset($_POST['project'])){
            $projectID = $_POST['project'];
            $sql = "INSERT INTO $projectBookingTable (start, end, projectID, timestampID, infoText, internInfo) VALUES('$startDate', '$endDate', $projectID, $indexIM, '$insertInfoText', '$insertInternInfoText')";
            $conn->query($sql);
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
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>TIMESTAMP REQUIRED: </strong>Could not create entry. No Timestamp found for that date and user, please create a check-in timestamp first.';
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


if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if (isset($_POST['saveChanges']) && isset($_POST['editingIndeces'])) {
    for ($i = 0; $i < count($_POST['editingIndeces']); $i++) {
      $imm = $_POST['editingIndeces'][$i];
      $query = "SELECT $logTable.timeToUTC
      FROM $logTable, $projectBookingTable
      WHERE $projectBookingTable.id = $imm
      AND $projectBookingTable.timestampID = $logTable.indexIM";
      $result = mysqli_query($conn, $query);

      if($result && $result->num_rows>0){
        $row = $result->fetch_assoc();
        $toUtc = $row['timeToUTC'] * -1;

        $chargedTimeStart= '0000-00-00 00:00:00';
        $chargedTimeFin = '0000-00-00 00:00:00';

        if($_POST['chargedTimesFrom'][$i] != '0000-00-00 00:00'){
          $chargedTimeStart = carryOverAdder_Hours($_POST['chargedTimesFrom'][$i], $toUtc);
        }
        if($_POST['chargedTimesTo'][$i] != '0000-00-00 00:00'){
          $chargedTimeFin = carryOverAdder_Hours($_POST['chargedTimesTo'][$i], $toUtc);
        }

        $timeStart = carryOverAdder_Hours($_POST['timesFrom'][$i], $toUtc);
        $timeFin = carryOverAdder_Hours($_POST['timesTo'][$i], $toUtc);

        $infoText = test_input($_POST['infoTextArea'][$i]);
        $newProjectID = test_input($_POST['projectIDs'][$i]);

        $sql = "UPDATE $projectBookingTable SET
        start='$timeStart', end='$timeFin',
        infoText='$infoText',projectID = $newProjectID,
        chargedTimeStart='$chargedTimeStart', chargedTimeEnd='$chargedTimeFin'
        WHERE id = $imm";

        $conn->query($sql); //UPDATE projectBookingTable to NEW values
        echo mysqli_error($conn);
      } //end if booking has corresponding timestamp
      echo mysqli_error($conn);
    } //end FOR each line

    if(isset($_POST['saveChanges']) && isset($_POST['noCheckCheckingIndeces']) && $_POST['filterBooked'] == 1){
      foreach ($_POST["noCheckCheckingIndeces"] as $e) {
        $sql = "UPDATE $projectBookingTable SET booked = 'TRUE'  WHERE id = $e;";
        $conn->query($sql);
      }
    }
    if(isset($_POST['saveChanges']) && isset($_POST['checkingIndeces']) && $_POST['filterBooked'] == 1){
      foreach ($_POST["checkingIndeces"] as $e) {
        $sql = "UPDATE $projectBookingTable SET booked = 'TRUE'  WHERE id = $e;";
        $conn->query($sql);

        $sql = "SELECT start, end, chargedTimeStart, chargedTimeEnd, projectID FROM $projectBookingTable WHERE id = $e";
        if($result = $conn->query($sql)){
          $row = $result->fetch_assoc();
          $A = $row['start'];
          $B = $row['end'];

          if($row['chargedTimeStart'] != '0000-00-00 00:00:00'){
            $A = $row['chargedTimeStart'];
          }
          if($row['chargedTimeEnd'] != '0000-00-00 00:00:00'){
            $B = $row['chargedTimeEnd'];
          }

          $hours = timeDiff_Hours($A, $B);

          $sql = "UPDATE $projectTable SET hours = hours - $hours WHERE id = ".$row['projectID'];
          $conn->query($sql);
          echo mysqli_error($conn);
        }
      }
    }
  } //end if isset save_changes
}
?>

<script>
function showClients(company, client){
  $.ajax({
    url:'ajaxQuery/AJAX_client.php',
    data:{companyID:company, clientID:client},
    type: 'post',
    success : function(resp){
      $("#filterClient").html(resp);
    },
    error : function(resp){}
  });

  showProjects(client, 0);
};

function showProjects(client, project){
  $.ajax({
    url:'ajaxQuery/AJAX_project.php',
    data:{clientID:client, projectID:project},
    type: 'post',
    success : function(resp){
      $("#filterProject").html(resp);
    },
    error : function(resp){}
  });
};

function showFilters(divID){
  document.getElementById(divID).style.visibility='visible';
}

function textAreaAdjust(o) {
  o.style.height = "1px";
  o.style.height = (o.scrollHeight)+"px";
}

</script>


<form method='post'>
  <!-- ####################-FILTERS-######################################## -->
  <div class="row">
    <div class="col-md-3">
      <!-- SELECT COMPANY -->
      <select style='width:200px' id="filterCompany" name="filterCompany" onchange='showClients(this.value, 0); showFilters("projectAndClientDiv", this.value);showFilters("dateDiv");' class="js-example-basic-single">
        <option value="0">Select Company...</option>
        <?php
        $sql = "SELECT * FROM $companyTable";
        $result = mysqli_query($conn, $sql);
        if($result && $result->num_rows > 0) {
          $row = $result->fetch_assoc();
          do {
            $checked = '';
            if($filterCompany == $row['id']) {
              $checked = 'selected';
            }
            echo "<option $checked value='".$row['id']."' >".$row['name']."</option>";

          } while($row = $result->fetch_assoc());
        }
        ?>
      </select>
      <br><br>
      <!-- SELECT USER -->
      <select id="filterUserID" name="filterUserID" class="js-example-basic-single" style='width:200px' onchange='showFilters("dateDiv");'>
        <?php
        $query = "SELECT * FROM $userTable;";
        $result = mysqli_query($conn, $query);
        echo "<option name=filterUserID value=0>User...</option>";
        while($row = $result->fetch_assoc()){
          $i = $row['id'];
          if ($filterUserID == $i) {
            echo "<option value=$i selected>".$row['firstname'] . " " . $row['lastname']."</option>";
          } else {
            echo "<option value=$i>".$row['firstname'] . " " . $row['lastname']."</option>";
          }
        }
        ?>
      </select>
      <br><br>

      <button type="submit" class="btn btn-warning" name="filter">Filter</button><br><br>
    </div>


    <!-- SELECTS DATE -->
    <div id="dateDiv" class="invisible">
      <div class="col-md-3">
        <div class="form-group">
          <input type=text style='width:200px' readonly class="form-control input-sm" value="<?php echo $lang['DATE']; ?>">
        </div>
        <select style='width:200px' class="js-example-basic-single" name="filterYear">
          <?php
          for($i = substr($filterDate,0,4)-5; $i < substr($filterDate,0,4)+5; $i++){
            $selected = ($i == substr($filterDate,0,4))?'selected':'';
            echo "<option $selected value=$i>$i</option>";
          }
          ?>
        </select>
        <br><br>
        <select style='width:200px' class="js-example-basic-single" name="filterMonth">
          <option value="">-</option>
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
        <br><br>
        <select style='width:200px' class="js-example-basic-single" name="filterDay">
          <option value="">Day..</option>
          <?php
          for($i = 1; $i < 32; $i++){
            $selected= '';
            if ($i == intval(substr($filterDate,8,2))) {
              $selected = 'selected';
            }
            echo "<option $selected value=".sprintf("%02d",$i).">$i</option>";
          }
          ?>
        </select>
      </div>
    </div>


    <!-- SELECTS CLIENT AND PROJECT -->
    <div id="projectAndClientDiv" class="invisible">
      <div class="col-md-3">
        <div class="form-group">
          <input type=text style='width:200px' readonly class="form-control input-sm" value="<?php echo $lang['CLIENT'].' & '.$lang['PROJECT']; ?>">
        </div>
        <div class='form-group'>
          <select id="filterClient" name="filterClient" class="js-example-basic-single" style='width:200px' onchange='showProjects(this.value, 0)' >
          </select>
        </div>
        <div class='form-group'>
          <select id="filterProject" name="filterProject" class="js-example-basic-single" style='width:200px'>
          </select>
        </div>
      </div>
    </div>

    <!-- SELECTS CHARGED -->
    <div class="col-md-3">
      <div class="form-group">
        <input type=text style='width:200px' readonly class="form-control input-sm" value="<?php echo $lang['CHARGED']; ?>">
      </div>
      <div class='form-group'>
        <select name="filterBooked" style='width:200px' class="js-example-basic-single">
          <option value='0' <?php if($booked == '0'){echo 'selected';}?> >---</option>
          <option value='1' <?php if($booked == '1'){echo 'selected';}?> ><?php echo $lang['NOT_CHARGED']; ?></option>
          <option value='2' <?php if($booked == '2'){echo 'selected';}?> ><?php echo $lang['CHARGED']; ?></option>
        </select>
      </div>
    </div>
  </div>
  <br>

  <br>
</div>

<!----------------------------------------------------------------------------->

<?php
if($filterCompany != 0 || $filterUserID != 0):
  ?>

  <script>
  showClients(<?php echo $filterCompany; ?>, <?php echo $filterClient; ?>);
  showProjects(<?php echo $filterClient; ?>, <?php echo $filterProject; ?>);
  document.getElementById("projectAndClientDiv").style.visibility='visible';
  document.getElementById("dateDiv").style.visibility='visible';
  </script>

  <?php endif; ?>

  <script>
  function toggle(source) {
    checkboxes = document.getElementsByName('checkingIndeces[]');
    for(var i = 0; i<checkboxes.length; i++) {
      checkboxes[i].checked = source.checked;
    }
  }
  function toggle2(source) {
    checkboxes = document.getElementsByName('noCheckCheckingIndeces[]');
    for(var i = 0; i<checkboxes.length; i++) {
      checkboxes[i].checked = source.checked;
    }
  }

  function showNewProjects(selectID, client, project){
    $.ajax({
      url:'ajaxQuery/AJAX_project.php',
      data:{clientID:client, projectID:project},
      type: 'post',
      success : function(resp){
        $(selectID).html(resp);
      },
      error : function(resp){}
    });
  };

  function showNewClients(selectID, company, client){
    $.ajax({
      url:'ajaxQuery/AJAX_client.php',
      data:{companyID:company, clientID:client},
      type: 'post',
      success : function(resp){
        $(selectID).html(resp);
      },
      error : function(resp){}
    });

    showProjects(client, 0);
  };
  </script>

  <?php if($filterCompany != 0 || $filterUserID != 0): ?>
  <div class="container-fluid">
  <?php if(!isset($_POST['filterBooked']) || $_POST['filterBooked'] != '1'): ?>
  <fieldset disabled>
  <br><br>
  <div class="alert alert-info" role="alert"><strong>Editing Disabled - </strong>You can only edit entries on 'not charged' option.</div>
  <?php endif; ?>

  <table class="table table-striped table-condensed">
  <thead>
  <tr>
  <th><?php echo $lang['CLIENT'].' & '.$lang['PROJECT']; ?></th>
  <th>Infotext</th>
  <th><?php echo $lang['DATE']; ?></th>
  <th><?php echo $lang['DATE'] .' '. $lang['CHARGED']; ?></th>
  <th><?php echo $lang['MINUTES']; ?></th>
  <th>0.25h</th>
  <th><input type="checkbox" onClick="toggle(this)"> <?php echo $lang['CHARGED']; ?> <br> <input type="checkbox" onClick="toggle2(this)" > <?php echo $lang['NOT_CHARGEABLE']; ?></th>
  <th>Intern</th>
  <th>Detail</th>
  </tr>
  </thead>
  <?php
  $csv = new Csv();
  $csv->setLegend(array($lang['CLIENT'], $lang['PROJECT'], 'Info',
  $lang['DATE'].' - '. $lang['FROM'], $lang['DATE'].' - '. $lang['TO'],
  $lang['TIMES'].' - '. $lang['FROM'], $lang['TIMES'].' - '. $lang['TO'],
  $lang['SUM'].' (min)', $lang['SUM'].' (0.25h)', $lang['HOURS_CREDIT'], 'Person', $lang['HOURLY_RATE']));

  $sum_min = $sum25 = 0;
  $addTimeStart = 0;
  if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if($booked == '2'){
      $bookedQuery= "AND $projectBookingTable.booked = 'TRUE'";
    } elseif($booked == '1'){
      $bookedQuery= "AND $projectBookingTable.booked = 'FALSE'";
    } else {
      $bookedQuery = "";
    }

    if($filterClient == 0){
      $filterClientAdd = "";
    } else {
      $filterClientAdd = "AND $clientTable.id = $filterClient";
    }

    if($filterProject == 0){
      $filterProjectAdd = "";
    } else {
      $filterProjectAdd = "AND $projectTable.id = $filterProject";
    }

    if($filterUserID == 0){
      $filterUserIDAdd = '';
    } else {
      $filterUserIDAdd = "AND $userTable.id = $filterUserID";
    }
    if($filterCompany == 0){
      $filterCompanyAdd = "";
    } else {
      $filterCompanyAdd = "AND $companyTable.id = $filterCompany";
    }

    $sql="SELECT DISTINCT $projectTable.id AS projectID,
    $clientTable.id AS clientID,
    $clientTable.name AS clientName,
    $projectTable.name AS projectName,
    $projectBookingTable.booked,
    $projectBookingTable.id AS projectBookingID,
    $logTable.timeToUTC,
    $projectBookingTable.infoText,
    $projectBookingTable.internInfo,
    $projectBookingTable.start,
    $projectBookingTable.end,
    $projectBookingTable.chargedTimeStart,
    $projectBookingTable.chargedTimeEnd,
    $userTable.firstname, $userTable.lastname,
    $projectTable.hours,
    $projectTable.hourlyPrice
    FROM $projectBookingTable, $logTable, $userTable, $clientTable, $projectTable, $companyTable
    WHERE $projectTable.clientID = $clientTable.id
    AND $clientTable.companyID = $companyTable.id
    AND $projectBookingTable.projectID = $projectTable.id
    AND $projectBookingTable.timestampID = $logTable.indexIM
    AND $userTable.id = $logTable.userID
    AND $projectBookingTable.start LIKE '$filterDate%'
    $filterCompanyAdd
    $bookedQuery
    $filterClientAdd $filterProjectAdd $filterUserIDAdd
    AND $projectBookingTable.projectID IS NOT NULL
    ORDER BY $projectBookingTable.end ASC";

    $result = mysqli_query($conn, $sql);
    if($result && $result->num_rows >0) {
      $numRows = $result->num_rows;
      if(isset($_POST['undo'])){
        $numRows--;
      }
      for ($i=0; $i<$numRows; $i++) {
        $row = $result->fetch_assoc();

        $x = $row['projectBookingID'];
        $timeDiff = timeDiff_Hours($row['start'], $row['end']);
        $t = ceil($timeDiff * 4) / 4;

        $csv_Add = array();
        echo "<tr>";
        $csv_Add[] = $row['clientName'];
        $csv_Add[] = $row['projectName'];


        echo "<td><select style='width:150px' class='js-example-basic-single' onchange='showNewProjects(\" #newProjectName$x \", this.value, 0);' >";
        $sql = "SELECT * FROM $clientTable";
        $clientResult = $conn->query($sql);
        while($clientRow = $clientResult->fetch_assoc()){
          $selected = '';
          if($clientRow['id'] == $row['clientID']){
            $selected = 'selected';
          }
          echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
        }
        echo "</select><br><br>";
        echo "<select style='width:150px' id='newProjectName$x' class='js-example-basic-single' name='projectIDs[]'>";
        $sql = "SELECT * FROM $projectTable WHERE clientID =".$row['clientID'];
        $clientResult = $conn->query($sql);
        while($clientRow = $clientResult->fetch_assoc()){
          $selected = '';
          if($clientRow['id'] == $row['projectID']){
            $selected = 'selected';
          }
          echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
        }
        echo "</select></td>";

        echo "<td><textarea style='resize: none;' name='infoTextArea[]' class='form-control input-sm' onkeyup='textAreaAdjust(this);'>" .$row['infoText']. "</textarea></td>";

        $A = carryOverAdder_Hours($row['start'],$row['timeToUTC']);
        $B = carryOverAdder_Hours($row['end'],$row['timeToUTC']);

        if($row['chargedTimeStart'] == '0000-00-00 00:00:00'){
          $A_charged = '0000-00-00 00:00:00';
        } else {
          $A_charged = carryOverAdder_Hours($row['chargedTimeStart'],$row['timeToUTC']);
        }
        if($row['chargedTimeEnd'] == '0000-00-00 00:00:00'){
          $B_charged = '0000-00-00 00:00:00';
        }else{
          $B_charged = carryOverAdder_Hours($row['chargedTimeEnd'],$row['timeToUTC']);
        }

        $csv_Add[] = $row['infoText'];
        $csv_Add[] = substr($A,0,10);
        $csv_Add[] = substr($B,0,10);
        $csv_Add[] = substr($row['start'],11,6);
        $csv_Add[] = substr($row['end'],11,6);
        $csv_Add[] = number_format((timeDiff_Hours($row['start'], $row['end']))*60, 0, '.', '');
        $csv_Add[] = $t;
        $csv_Add[] = $row['hours'];
        $csv_Add[] = $row['firstname']." ".$row['lastname'];


        echo "<td>
        <input type='text' class='form-control input-sm' style='width:125px;' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='timesFrom[]' value='".substr($A,0,16)."'>
        -
        <input type='text' class='form-control input-sm' style='width:125px;' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='timesTo[]' value='".substr($B,0,16)."'></td>";

        echo "<td>
        <input type='text' class='form-control input-sm' style='width:125px;' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='chargedTimesFrom[]' value='".substr($A_charged,0,16)."'>
        -
        <input type='text' class='form-control input-sm' style='width:125px;' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='chargedTimesTo[]' value='".substr($B_charged,0,16)."'></td>";


        echo "<td>" .number_format((timeDiff_Hours($row['start'], $row['end']))*60, 0, '.', '') . "</td>";

        echo "<td>$t</td>";


        if($row['booked'] != 'TRUE'){
          $selected = "";
        } else {
          $selected = "checked";
        }
        echo "<td><input type='checkbox' $selected name='checkingIndeces[]' value='".$row['projectBookingID']."'>"; //gotta know which ones he wants checked.
        echo " / <input type='checkbox' name='noCheckCheckingIndeces[]' value='".$row['projectBookingID']."'></td>";
        $csv_Add[] = $row['hourlyPrice'];

        $interninfo = $row['internInfo'];
        if(empty($interninfo)){
          echo '<td> </td>';
        } else {
          echo "<td><a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Intern' data-content='$interninfo' data-placement='left'><i class='fa fa-question-circle-o'></i></a></td>";
        }

        $detailInfo = $row['hourlyPrice'] .' || '.$row['hours'] .' || '. $row['firstname']." ".$row['lastname'];
        echo "<td><a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Stundenrate - Stundenkonto - Person' data-content='$detailInfo' data-placement='left'><i class='fa fa-info'></i></a></td>";

        echo "</tr>";

        echo '<input type="text" style="display:none;" name="editingIndeces[]" value="' . $row['projectBookingID'] . '">'; //since we dont know what has been edited: save all.

        $csv->addLine($csv_Add);
        $sum_min += timeDiff_Hours($row['start'], $row['end']);
        $sum25 += $t;
        $addTimeStart = $B;
      } //end while fetch_assoc

      if(isset($_POST['undo'])){
        $row = $result->fetch_assoc();
        if(empty($row['projectID'])){ //undo breaks
          $timeDiff = timeDiff_Hours($row['start'], $row['end']);
          $sql = "UPDATE $logTable SET breakCredit = (breakCredit - $timeDiff) WHERE indexIM = " . $row['timestampID'];
          $conn->query($sql);
        }
        $sql = "DELETE FROM $projectBookingTable WHERE id = " . $row['projectBookingID'];
        $conn->query($sql);
      }

    } else {
      echo mysqli_error($conn);
    }
  }

  echo "<tr>";
  echo "<td style='font-weight:bold'>Summary</td><td></td><td></td><td></td>";
  echo "<td>".number_format($sum_min*60, 2, '.', '')."</td><td>$sum25</td>";
  echo "</tr>";
  ?>
  </table>
  <script>
  $(function () {
    $('[data-toggle="popover"]').popover()
  })

  $('document').ready(function(){
    for(var i = 0; i < document.getElementsByName('infoTextArea[]').length; i++){
      textAreaAdjust(document.getElementsByName('infoTextArea[]')[i]);
    }
  });
  </script>

  <br><br>

  <?php if(isset($_POST['filterBooked']) && $_POST['filterBooked'] == '1'): ?>
  <button type='submit' class="btn btn-warning" name='saveChanges'>Save Changes</button><br><br>
  <?php endif; ?>

  <br><br>

  <!-- ADD BOOKING TO USER, IF DAY AND USER SELECTED -->
  <?php if($filterUserID != 0 && isset($_POST['filterDay'])): ?>

  <div style='text-align:right;'><button type='submit' class="btn btn-warning" name='undo'>Remove last entry</button></div>
  <div class="row">
  <div id=mySelections class="col-xs-9">
  <?php
  $query = "SELECT * FROM $companyTable WHERE id IN (SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $filterUserID) ";
  $result = mysqli_query($conn, $query);
  if($result->num_rows == 1):

    $row = $result->fetch_assoc();
    $query = "SELECT * FROM $clientTable WHERE companyID=".$row['id'];
    $result = mysqli_query($conn, $query);
    if ($result && $result->num_rows > 0) {
      echo '<select style="width:200px" class="js-example-basic-single" id="addSelectClient" name="client" onchange="showNewProjects(\'#addSelectProject\', this.value, 0)">';
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

    <select name="company"  class="js-example-basic-single" style='width:200px' class="" onchange="showNewClients('#addSelectClient', this.value, 0)">
    <option name=cmp value=0>Select...</option>
    <?php
    $query = "SELECT * FROM $companyTable WHERE id IN (SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $filterUserID) ";
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

    <select id="addSelectClient" style='width:200px' class="js-example-basic-single" name="client" onchange="showNewProjects('#addSelectProject', this.value, 0)">
    </select>

    <?php endif; ?>

    <select id="addSelectProject" style='width:200px' class="js-example-basic-single" name="project">
    </select>
    </div>
    </div>

    <div class="row">
    <div class="col-md-8">
    <br><textarea class="form-control" rows="3" name="infoText" placeholder="Info..."></textarea><br>
    </div>
    <div class="col-md-4">
    <br><textarea class="form-control" rows="3" name="internInfoText" placeholder="Intern... (Optional)"></textarea><br>
    </div>
    </div>

    <div class="row">
    <div class="col-md-6">
    <div class="input-group input-daterange">
    <span class="input-group-addon">
    <input type="checkbox" name="addBreak" title="Das ist eine Pause"> <a style="color:black;"> <i class="fa fa-cutlery" aria-hidden="true"> </i> </a>
    </span>
    <input type="time" class="form-control" onkeydown='if (event.keyCode == 13) return false;' name="start" value="<?php echo substr($addTimeStart,11,5); ?>" >
    <span class="input-group-addon"> - </span>
    <input type="time" class="form-control"  min="<?php echo substr($start,0,5); ?>" onkeydown='if (event.keyCode == 13) return false;' name="end">
    <div class="input-group-btn">
    <button class="btn btn-warning" type="submit"  name="add"> + </button>
    </div>
    </div>
    </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info" role="alert"><strong>Adding Disabled - </strong>You can only add entries on specifying day and user (<a href="getTimestamps.php">Check-in required</a>).</div>
    <?php endif; ?>

    <!-- END ADD-BOOKING FIELD -->

    <?php if(!isset($_POST['filterBooked']) || $_POST['filterBooked'] != '1'): ?>
    </fieldset>
    <?php endif; ?>
    </div>
    <?php endif; //end if($filterUserID != 0 && isset($_POST['filterDay'])) ?>
    </form>

    <br><br>
    <?php if($filterCompany != 0 || $filterUserID != 0): ?>
    <form action="csvDownload.php" method="post" target='_blank'>
    <button type='submit' class="btn btn-warning" name=csv value=<?php $csv->setEncoding("UTF-16LE"); echo rawurlencode($csv->compile()); ?>> Download as CSV </button>
    </form>
    <?php endif;?>

    <br><br>
    <?php include 'footer.php'; ?>
