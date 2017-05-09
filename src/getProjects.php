<?php include 'header.php'; ?>
<?php include "../plugins/csvParser/Csv.php"; use Deblan\Csv\Csv; ?>
<?php enableToProject($userID); ?>
<!-- BODY -->
<style>
.popover{
  max-width: 100%; /* Max Width of the popover (depending on the container!) */
}
.custom{
  width:20%;
  padding:0;
}
#project_filters{
  position:fixed;
  background:white;
  padding-bottom:40px;
  width:90%;
}
#project_body{
  padding-top:350px;
  padding-bottom:300px;
}
#project_footer{
  position:fixed;
  background:white;
  width:100%;
  bottom:0;
  padding-bottom:5px;
}
.seperated_header th{
  height: 0;
  line-height: 0;
  padding-top: 0;
  padding-bottom: 0;
  color: transparent;
  border: none;
  white-space: nowrap;
}
.seperated_header th div{
  position: fixed;
  background: transparent;
  color: black;
  padding: 9px 25px;
  top:415px;
  margin-left: -25px;
  line-height: normal;
}
</style>

<?php
$filterDate = substr(getCurrentTimestamp(),0,10); //granularity: day
$booked = '1';

$filterCompany = $filterClient = $filterProject = $filterUserID = 0;
$filterAddBreaks = $filterAddDrives = "checked";

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['filterYear'])){
    $filterDate = $_POST['filterYear'];
  } else {
    $filterDate = '____';
  }
  if(!empty($_POST['filterMonth'])){
    $filterDate .= '-' . $_POST['filterMonth'];
  } else {
    $filterDate .= '-' . '__';
  }
  if(!empty($_POST['filterDay'])){
    $filterDate .= '-' . $_POST['filterDay'];
  } else {
    $filterDate .= '-' . '__';
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
  if(!isset($_POST['filterAddBreaks'])){
    $filterAddBreaks = "";
  }
  if(!isset($_POST['filterAddDrives'])){
    $filterAddDrives = "";
  }

  if(!empty($_POST['set_all_filters'])){
    $arr = explode(',', $_POST['set_all_filters']);
      $filterDate = $arr[0];
      $filterCompany = $arr[1];
      $filterClient = $arr[2];
      $filterProject = $arr[3];
      $filterUserID = $arr[4];
      $filterAddBreaks = $arr[5];
      $filterAddDrives = $arr[6];
      $booked = $arr[7];
    }
    // echo "<input type='text' name='set_all_filters' style='display:none' value='$filterDate,$filterCompany,$filterClient,$filterProject,$filterUserID,$filterAddBreaks,$filterAddDrives,$booked' />";

  if(!empty($_POST['editing_save'])){ //comes from the modal
    $x = $_POST['editing_save'];
    $result = $conn->query("SELECT $logTable.timeToUTC FROM $logTable, $projectBookingTable WHERE $projectBookingTable.id = $x AND $projectBookingTable.timestampID = $logTable.indexIM");
    $row = $result->fetch_assoc();
    $toUtc = $row['timeToUTC'] * -1;
    if(test_Date($_POST["editing_time_from_".$x].':00') && test_Date($_POST["editing_time_to_".$x].':00')){
      if(!empty($_POST["editing_projectID_".$x])){
        $new_projectID = $_POST["editing_projectID_".$x];
      } else { //break
        $new_projectID = 'NULL';
      }
      $new_A = carryOverAdder_Hours($_POST["editing_time_from_".$x].':00', $toUtc);
      $new_B = carryOverAdder_Hours($_POST["editing_time_to_".$x].':00', $toUtc);

      $chargedTimeStart= '0000-00-00 00:00:00';
      $chargedTimeFin = '0000-00-00 00:00:00';
      if($_POST['editing_chargedtime_from_'.$x] != '0000-00-00 00:00'){
        $chargedTimeStart = carryOverAdder_Hours($_POST['editing_chargedtime_from_'.$x].':00', $toUtc);
      }
      if($_POST['editing_chargedtime_to_'.$x] != '0000-00-00 00:00'){
        $chargedTimeFin = carryOverAdder_Hours($_POST['editing_chargedtime_to_'.$x].':00', $toUtc);
      }
      $new_text = test_input($_POST['editing_infoText_'.$x]);

      $new_charged = 'FALSE';
      if(isset($_POST['editing_charge']) || isset($_POST['editing_nocharge'])){
        $new_charged = 'TRUE';
      }
      $conn->query("UPDATE $projectBookingTable SET start='$new_A', end='$new_B', projectID=$new_projectID, infoText='$new_text', booked='$new_charged', chargedTimeStart='$chargedTimeStart', chargedTimeEnd='$chargedTimeFin' WHERE id = $x");

      //update charged
      if(isset($_POST['editing_charge'])){
        if($chargedTimeStart != '0000-00-00 00:00:00'){
          $new_A = $chargedTimeStart;
        }
        if($chargedTimeFin != '0000-00-00 00:00:00'){
          $new_B = $chargedTimeFin;
        }
        $hours = timeDiff_Hours($new_A, $new_B);
        $sql = "UPDATE $projectTable SET hours = hours - $hours WHERE id = $x";
        $conn->query($sql);
        echo mysqli_error($conn);
      }
      if(!mysqli_error($conn)){
        echo '<div class="alert alert-success alert-over fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo $lang['OK_SAVE'];
        echo '</div>';
      }
    } else {
      echo '<div class="alert alert-danger alert-over fade in">';
      echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Could not change entry: </strong>Input was not correct.';
      echo '</div>';
    }
    echo mysqli_error($conn);
  } elseif(isset($_POST['saveChanges'])){ //i dont want one save button to trigger the other
    if(!empty($_POST['remove_booking'])){
      foreach($_POST['remove_booking'] as $bookingID){
        $conn->query("DELETE FROM $projectBookingTable WHERE id = $bookingID");
      }
    }

    if(isset($_POST['editingIndeces'])) {
      //update free of charge
      if(isset($_POST['noCheckCheckingIndeces'])){
        foreach ($_POST["noCheckCheckingIndeces"] as $e) {
          $sql = "UPDATE $projectBookingTable SET booked = 'TRUE'  WHERE id = $e;";
          $conn->query($sql);
        }
      }
      //charged
      if(isset($_POST['checkingIndeces'])){
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
    } //end if isset charged
  }

  if(isset($_POST["add"]) && isset($_POST['end']) && !empty(trim($_POST['infoText']))){
    //get the timestamp. if it doesnt exist -> display a biiiig fat error
    $sql = "SELECT * FROM $logTable WHERE userID = $filterUserID AND time LIKE '$filterDate %' AND status = '0'";
    $result = mysqli_query($conn, $sql);
    if($result && $result->num_rows > 0){
      $row = $result->fetch_assoc();
      $indexIM = $row['indexIM'];
      $timeToUTC = $row['timeToUTC'];

      $startDate = $filterDate." ".$_POST['start'];
      $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);

      $endDate = $filterDate." ".$_POST['end'];
      $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);

      $insertInfoText = test_input($_POST['infoText']);
      $insertInternInfoText = test_input($_POST['internInfoText']);

      if(timeDiff_Hours($startDate, $endDate) > 0){
        if(isset($_POST['addBreak'])){ //checkbox
          $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$startDate', '$endDate', $indexIM, '$insertInfoText', 'break')";
          $conn->query($sql);
          $duration = timeDiff_Hours($startDate, $endDate);
          $sql= "UPDATE $logTable SET breakCredit = (breakCredit + $duration) WHERE indexIm = $indexIM";
          $conn->query($sql);
        } else {
          if(isset($_POST['addExpenses'])){
            $expenses_price = test_input($_POST['expenses_price']);
            $expenses_info = test_input($_POST['expenses_info']);
            $expenses_unit = test_input($_POST['expenses_unit']);
          } else {
            $expenses_price = $expenses_info = $expenses_unit = '';
          }
          if(isset($_POST['project'])){
            $projectID = test_input($_POST['project']);
            $accept = 'TRUE';
            if(isset($_POST['required_1'])){
              $field_1 = "'".test_input($_POST['required_1'])."'";
              if(empty(test_input($_POST['required_1']))){ $accept = FALSE; }
            } elseif(!empty($_POST['optional_1'])){
              $field_1 = "'".test_input($_POST['optional_1'])."'";
            } else {
              $field_1 = 'NULL';
            }
            if(isset($_POST['required_2'])){
              $field_2 = "'".test_input($_POST['required_2'])."'";
              if(empty(test_input($_POST['required_2']))){ $accept = FALSE; }
            } elseif(!empty($_POST['optional_2'])){
              $field_2 = "'".test_input($_POST['optional_2'])."'";
            } else {
              $field_2 = 'NULL';
            }
            if(isset($_POST['required_3'])){
              $field_3 = "'".test_input($_POST['required_3'])."'";
              if(empty(test_input($_POST['required_3']))){ $accept = FALSE; }
            } elseif(!empty($_POST['optional_3'])){
              $field_3 = "'".test_input($_POST['optional_3'])."'";
            } else {
              $field_3 = 'NULL';
            }
            if($accept){
              if(isset($_POST['addDrive'])){ //add as driving time
                $sql = "INSERT INTO projectBookingData (start, end, projectID, timestampID, infoText, internInfo, bookingType, extra_1, extra_2, extra_3, exp_info, exp_unit, exp_price)
                VALUES('$startDate', '$endDate', $projectID, $indexIM, '$insertInfoText', '$insertInternInfoText', 'drive', $field_1, $field_2, $field_3, '$expenses_info', '$expenses_unit', '$expenses_price')";
              } else { //normal booking
                $sql = "INSERT INTO projectBookingData (start, end, projectID, timestampID, infoText, internInfo, bookingType, extra_1, extra_2, extra_3, exp_info, exp_unit, exp_price)
                VALUES('$startDate', '$endDate', $projectID, $indexIM, '$insertInfoText', '$insertInternInfoText', 'project', $field_1, $field_2, $field_3, '$expenses_info', '$expenses_unit', '$expenses_price')";
              }
              $conn->query($sql);
              echo mysqli_error($conn);
              $insertInfoText = $insertInternInfoText = '';
            } else {
              $error_output = $lang['ERROR_MISSING_FIELDS'];
            }
          } else {
            $error_output = $lang['ERROR_MISSING_SELECTION'];
          }
        }
      } else {
        $error_output = $lang['ERROR_TIMES_INVALID'];
      }
    } else {
      $error_output = $lang['ERROR_MISSING_TIMESTAMP'];
    }
  } elseif(isset($_POST['add'])) {
    $error_output = $lang['ERROR_MISSING_FIELDS'];
  }
} //end if POST
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
function showProjectfields(project){
  $.ajax({
    url:'ajaxQuery/AJAX_getProjectFields.php',
    data:{projectID:project},
    type: 'get',
    success : function(resp){
      $("#project_fields").html(resp);
    },
    error : function(resp){}
  });
}
function showMyDiv(o, toShow){
  if(o.checked){
    document.getElementById(toShow).style.display='block';
  } else {
    document.getElementById(toShow).style.display='none';
  }
}
function showFilters(divID){
  document.getElementById(divID).style.visibility='visible';
}

function changeValue(cVal, id, val){
  if(cVal == ''){
    document.getElementById(id).selectedIndex = val;
    $('#' + id).val(val).change();
  }
}
</script>


  <!-- ####################-FILTERS-######################################## -->
  <form method='post'>
  <div id="project_filters" class="container-fluid">
    <div class="page-header">
      <div class="row">
        <div class="col-sm-4">
          <h3><?php echo $lang['VIEW_PROJECTS']; ?></h3>
        </div>
        <div class="col-sm-8">
          <?php if($error_output): ?>
          <div class="alert alert-danger fade in">
            <a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Error: </strong> <?php echo $error_output; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-xs-3 custom">
      <!-- SELECT COMPANY -->
      <select style='width:200px' id="filterCompany" name="filterCompany" onchange='showClients(this.value, 0); showFilters("projectAndClientDiv", this.value);showFilters("dateDiv");' class="js-example-basic-single">
        <?php
        $sql = "SELECT * FROM $companyTable WHERE id IN (".implode(', ', $available_companies).")";
        $result = mysqli_query($conn, $sql);
        if($result && $result->num_rows > 1) {
          echo '<option value="0">Select Company...</option>';
        }
        while($result && ($row = $result->fetch_assoc())){
          $checked = '';
          if($filterCompany == $row['id']) {
            $checked = 'selected';
          }
          echo "<option $checked value='".$row['id']."' >".$row['name']."</option>";
        }
        if($result && $result->num_rows == 1) {
          $filterCompany = $available_companies[1]; //easy peasy
          echo '<option value="0">- Empty -</option>';
        }
        ?>
      </select>
      <br><br>
      <!-- SELECT USER -->
      <select id="filterUserID" name="filterUserID" class="js-example-basic-single" style='width:200px' onchange='showFilters("dateDiv");'>
        <?php
        $query = "SELECT * FROM $userTable WHERE id IN (".implode(', ', $available_users).");";
        $result = mysqli_query($conn, $query);
        if($result && $result->num_rows > 1) {
          echo "<option name=filterUserID value=0>User...</option>";
        }
        while($row = $result->fetch_assoc()){
          $i = $row['id'];
          if ($filterUserID == $i) {
            echo "<option value=$i selected>".$row['firstname'] . " " . $row['lastname']."</option>";
          } else {
            echo "<option value=$i>".$row['firstname'] . " " . $row['lastname']."</option>";
          }
        }
        if($result && $result->num_rows == 1) {
          $filterUserID = $available_users[1]; //lemon squeezy
          echo '<option value="0">- Empty -</option>';
        }
        ?>
      </select>
      <br><br>
      <div class="container">
        <div class="checkbox">
          <input type="checkbox" name="filterAddBreaks" <?php echo $filterAddBreaks; ?>> <?php echo $lang['BREAKS']; ?> <br><br>
        </div>
        <div class="checkbox">
          <input type="checkbox" name="filterAddDrives" <?php echo $filterAddDrives; ?>> <?php echo $lang['DRIVES']; ?>
        </div>
      </div>
      <br>
      <button type="submit" class="btn btn-warning" name="filter">Filter</button><br><br>
    </div>

    <!-- SELECTS DATE -->
    <div id="dateDiv" class="invisible">
      <div class="col-xs-3 custom">
        <div class="form-group">
          <input type=text style='width:200px;border:none;background-color:#dbecf7' readonly class="form-control input-sm" value="<?php echo $lang['DATE']; ?>">
        </div>
        <select style='width:200px' onchange="changeValue(this.value, 'filterMonth', '')" class="js-example-basic-single" name="filterYear">
          <option value=""> --- </option>
          <?php
          for($i = 2015; $i < 2025; $i++){
            $selected = ($i == substr($filterDate,0,4))?'selected':'';
            echo "<option $selected value=$i>$i</option>";
          }
          ?>
        </select>
        <br><br>
        <select style='width:200px' onchange="changeValue(this.value, 'filterDay', '')" class="js-example-basic-single" name="filterMonth" id="filterMonth">
          <option value=""> --- </option>
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
        <select style='width:200px' class="js-example-basic-single" name="filterDay" id="filterDay">
          <option value=""> --- </option>
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
        <br><br>
      </div>
    </div>

    <!-- SELECTS CLIENT AND PROJECT -->
    <div id="projectAndClientDiv" class="invisible">
      <div class="col-xs-3 custom">
        <div class="form-group">
          <input type=text style='width:200px;border:none;background-color:#dbecf7' readonly class="form-control input-sm" value="<?php echo $lang['CLIENT'].' & '.$lang['PROJECT']; ?>">
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
    <div class="col-xs-3 custom">
      <div class="form-group">
        <input type=text style='width:200px;border:none;background-color:#dbecf7' readonly class="form-control input-sm" value="<?php echo $lang['CHARGED']; ?>">
      </div>
      <div class='form-group'>
        <select name="filterBooked" style='width:200px' class="js-example-basic-single">
          <option value='0' <?php if($booked == '0'){echo 'selected';}?> >---</option>
          <option value='1' <?php if($booked == '1'){echo 'selected';}?> ><?php echo $lang['NOT_CHARGED']; ?></option>
          <option value='2' <?php if($booked == '2'){echo 'selected';}?> ><?php echo $lang['CHARGED']; ?></option>
        </select>
      </div>
      <?php if($booked != 1){ echo '<small>*Entries can only be edited if they have not been charged yet</small>'; } ?>
    </div>
  </div>
  <br><br>
</form>
<!----------------------------------------------------------------------------->
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  //build your query depending on filter options
  if($booked == '2'){
    $bookedQuery= " AND $projectBookingTable.booked = 'TRUE' ";
  } elseif($booked == '1'){
    $bookedQuery= " AND $projectBookingTable.booked = 'FALSE' ";
  } else {
    $bookedQuery = " ";
  }
  if($filterUserID == 0){
    $filterUserIDAdd = '';
  } else {
    $filterUserIDAdd = " AND $userTable.id = $filterUserID ";
  }
  if($filterCompany == 0){
    $filterCompanyAdd = "";
  } else {
    $filterCompanyAdd = " AND $companyTable.id = $filterCompany ";
  }
  if($filterClient == 0){
    $filterClientAdd = "";
  } else {
    $filterClientAdd = " AND $clientTable.id = $filterClient ";
  }
  if($filterProject == 0){
    $filterProjectAdd = "";
  } else {
    $filterProjectAdd = " AND $projectTable.id = $filterProject ";
  }
  //filter activates if he does NOT want to show drives or breaks
  $filterNoDriveAdd = ""; //he wants drives
  if($filterAddDrives == ""){
    $filterNoDriveAdd = " AND $projectBookingTable.bookingType != 'drive' "; //he doesnt want drives
  }
  $filterProjectClientCompany = $filterCompanyAdd . $filterClientAdd . $filterProjectAdd;
  //he does NOT want breaks
  if($filterAddBreaks == ""){
    $filterNoBreakAdd = " AND $projectBookingTable.bookingType != 'break' "; //he doesnt want breaks
  } else { //he wants breaks -> a break doesnt have a project, company, client. only a user.
    $filterNoBreakAdd = "";
    if($filterUserID != 0){ //a break can only be assigned to a user
      if(strlen($filterProjectClientCompany) > 3){ //he filters for something
        $filterProjectClientCompany = " AND ((".substr($filterProjectClientCompany,4).") OR ($projectTable.id IS NULL)) ";
      }
    } else {
      echo "<div class='alert alert-info' role='alert'>Select a User to display his breaks. Breaks cannot be assigned to a Project.</div>";
    }
  }

  $sql="SELECT $projectTable.id AS projectID,
  $clientTable.id AS clientID,
  $companyTable.id AS companyID,
  $clientTable.name AS clientName,
  $projectTable.name AS projectName,
  $userTable.id AS userID,
  $projectBookingTable.*,
  $projectBookingTable.id AS projectBookingID,
  $logTable.timeToUTC,
  $userTable.firstname, $userTable.lastname,
  $projectTable.hours,
  $projectTable.hourlyPrice,
  $projectTable.status
  FROM $projectBookingTable
  INNER JOIN $logTable ON  $projectBookingTable.timeStampID = $logTable.indexIM
  INNER JOIN $userTable ON $logTable.userID = $userTable.id
  LEFT JOIN $projectTable ON $projectBookingTable.projectID = $projectTable.id
  LEFT JOIN $clientTable ON $projectTable.clientID = $clientTable.id
  LEFT JOIN $companyTable ON $clientTable.companyID = $companyTable.id
  WHERE $projectBookingTable.start LIKE '$filterDate%'
  $bookedQuery
  $filterProjectClientCompany $filterUserIDAdd
  $filterNoBreakAdd $filterNoDriveAdd
  ORDER BY $projectBookingTable.start ASC";
  /*
  $sql = "SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
  LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
  LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)

  WHERE ($projectBookingTable.timestampID = $indexIM AND $projectBookingTable.start LIKE '$date %' )
  OR ($projectBookingTable.projectID IS NULL AND $projectBookingTable.start LIKE '$date %' AND $projectBookingTable.timestampID = $indexIM) ORDER BY end ASC;";
  */
  $result = $conn->query($sql);
  $editingResult = $conn->query($sql); //f*ck you php
} else {
  $result = false;
}
?>

<?php if($filterCompany || $filterUserID ): //i want my filter displayed even if there were no bookings ?>
  <script>
  showClients(<?php echo $filterCompany; ?>, <?php echo $filterClient; ?>);
  showProjects(<?php echo $filterClient; ?>, <?php echo $filterProject; ?>);
  document.getElementById("projectAndClientDiv").style.visibility='visible';
  document.getElementById("dateDiv").style.visibility='visible';

  function toggle(checkId, uncheckId) {
    checkboxes = document.getElementsByName(checkId + '[]');
    checkboxesUncheck = document.getElementsByName(uncheckId + '[]');
    for(var i = 0; i<checkboxes.length; i++) {
      checkboxes[i].checked = true;
      checkboxesUncheck[i].checked = false;
    }
  }

  function toggle2(uncheckID){
    uncheckBox = document.getElementById(uncheckID);
    uncheckBox.checked = false;
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
<?php endif; ?>

<?php if($result && $result->num_rows > 0): ?>
<div id="project_body">
  <form id="project_table" method="post">
    <?php echo "<input type='text' name='set_all_filters' style='display:none' value='$filterDate,$filterCompany,$filterClient,$filterProject,$filterUserID,$filterAddBreaks,$filterAddDrives,$booked' />"; ?>
    <table class="table table-striped table-condensed seperated_header">
      <thead>
        <th><div><?php echo $lang['DELETE']; ?></div></th>
        <th>Kunde und Pro<div><?php echo $lang['CLIENT'].' & '.$lang['PROJECT']; ?></div></th>
        <th>Info <div>Infotext</div></th>
        <th>Date<div><?php echo $lang['DATE']; ?></div></th>
        <th>Datum Verrechnt <div><?php echo $lang['DATE'] .' '. $lang['CHARGED']; ?></div></th>
        <th>Minuten<div><?php echo $lang['MINUTES']; ?></div></th>
        <th>1234567890<div style="margin-top:-10px"><input type="radio" onClick="toggle('checkingIndeces', 'noCheckCheckingIndeces')" name="toggleRadio"> <?php echo $lang['CHARGED']; ?><br>
          <input type="radio" onClick="toggle('noCheckCheckingIndeces', 'checkingIndeces')" name="toggleRadio"> <?php echo $lang['NOT_CHARGEABLE']; ?></div></th>
          <th>Detail<div>Detail</div></th>
          <th></th>
        </thead>
        <?php
        $csv = new Csv();
        $csv->setLegend(array($lang['CLIENT'], $lang['PROJECT'], 'Info',
        $lang['DATE'].' - '. $lang['FROM'], $lang['DATE'].' - '. $lang['TO'],
        $lang['TIMES'].' - '. $lang['FROM'], $lang['TIMES'].' - '. $lang['TO'],
        $lang['SUM'].' (min)', $lang['HOURS_CREDIT'], 'Person', $lang['HOURLY_RATE'], $lang['ADDITIONAL_FIELDS']));

        $sum_min = 0;
        $addTimeStart = 0;
        $numRows = $result->num_rows;
        for($i = 0; $i < $numRows; $i++) {
          $row = $result->fetch_assoc();
          //have to make sure admin can only see what is available to him
          if(($row['companyID'] && !in_array($row['companyID'], $available_companies)) || ($row['userID'] && !in_array($row['userID'], $available_users))){
            continue;
          }
          $x = $row['projectBookingID'];
          $timeDiff = timeDiff_Hours($row['start'], $row['end']);

          if($row['bookingType'] == 'break'){
            $icon = "fa fa-cutlery";
          } elseif($row['bookingType'] == 'drive'){
            $icon = "fa fa-car";
          } else {
            $icon = "fa fa-bookmark";
          }

          $csv_Add = array();
          echo "<tr>";
          echo "<td><input type='checkbox' name='remove_booking[]' value='$x'/><i class='$icon'></i></td>";
          echo "<td>".$row['clientName'].'<br> '.$row['projectName']."</td>";
          echo '<td style="max-width:200px">'.$row['infoText']."</td>";

          $A = carryOverAdder_Hours($row['start'],$row['timeToUTC']);
          $B = carryOverAdder_Hours($row['end'],$row['timeToUTC']);
          $A_charged = $B_charged = '--';

          if($row['chargedTimeStart'] != '0000-00-00 00:00:00'){
            $A_charged = carryOverAdder_Hours($row['chargedTimeStart'],$row['timeToUTC']);
          }
          if($row['chargedTimeEnd'] != '0000-00-00 00:00:00'){
            $B_charged = carryOverAdder_Hours($row['chargedTimeEnd'],$row['timeToUTC']);
          }

          $csv_Add[] = $row['clientName'];
          $csv_Add[] = $row['projectName'];
          $csv_Add[] = str_replace(array("\r", "\n",";"), ' ', $row['infoText']);
          $csv_Add[] = substr($A,0,10);
          $csv_Add[] = substr($B,0,10);
          $csv_Add[] = substr($A,11,6);
          $csv_Add[] = substr($B,11,6);
          $csv_Add[] = number_format((timeDiff_Hours($row['start'], $row['end']))*60, 0, '.', '');
          $csv_Add[] = $row['hours'];
          $csv_Add[] = $row['firstname']." ".$row['lastname'];
          $csv_Add[] = ' '.$row['hourlyPrice'].' ';

          echo '<td style="width:200px;white-space: pre;">'.$lang['FROM'].': '.substr($A,0,16)."<br>".$lang['TO'].':   '.substr($B,0,16)."</td>";
          echo '<td style="max-width:200px;white-space: pre;">'.$lang['FROM'].': '.substr($A_charged,0,16)."<br>".$lang['TO'].':   '.substr($B_charged,0,16)."</td>";
          echo "<td>" .number_format((timeDiff_Hours($row['start'], $row['end']))*60, 0, '.', '') . "</td>";

          if($row['bookingType'] != 'break' && $row['booked'] != 'TRUE'){ //if this is a break or has been charged already, do not display dis
            echo "<td><input id='".$row['projectBookingID']."_01' type='checkbox' onclick='toggle2(\"".$row['projectBookingID']."_02\")' name='checkingIndeces[]' value='".$row['projectBookingID']."'>"; //gotta know which ones he wants checked.
            echo " / <input id='".$row['projectBookingID']."_02' type='checkbox' onclick='toggle2(\"".$row['projectBookingID']."_01\")' name='noCheckCheckingIndeces[]' value='".$row['projectBookingID']."'></td>";
          } else {
            echo "<td></td>";
          }

          $projStat = (!empty($row['status']))? $lang['PRODUCTIVE'] :  $lang['PRODUCTIVE_FALSE'];
          $detailInfo = $row['firstname']." ".$row['lastname'] .' || '. $row['hours'] .' || '. $row['hourlyPrice'] .' || '. $projStat;
          $interninfo = $row['internInfo'];
          $optionalinfo = $csv_optionalinfo = $expensesinfo = '';
          $extraFldRes = $conn->query("SELECT name FROM $companyExtraFieldsTable WHERE companyID = ".$row['companyID']);
          if($extraFldRes && $extraFldRes->num_rows > 0){
            $extraFldRow = $extraFldRes->fetch_assoc();
            if($row['extra_1']){$optionalinfo = '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_1'].'<br>'; $csv_optionalinfo = $extraFldRow['name'].': '.$row['extra_1']; }
          }
          if($extraFldRes && $extraFldRes->num_rows > 1){
            $extraFldRow = $extraFldRes->fetch_assoc();
            if($row['extra_2']){$optionalinfo .= '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_2'].'<br>'; $csv_optionalinfo .= ', '.$extraFldRow['name'].': '.$row['extra_2'];}
          }
          if($extraFldRes && $extraFldRes->num_rows > 2){
            $extraFldRow = $extraFldRes->fetch_assoc();
            if($row['extra_3']){$optionalinfo .= '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_3']; $csv_optionalinfo .= ', '.$extraFldRow['name'].': '.$row['extra_3'];}
          }
          if($row['exp_unit'] > 0) $expensesinfo .= $lang['QUANTITY'].': '.$row['exp_unit'].'<br>';
          if($row['exp_price'] > 0) $expensesinfo .= $lang['PRICE_STK'].': '.$row['exp_price'].'<br>';
          if($row['exp_info']) $expensesinfo .= $lang['DESCRIPTION'].': '.$row['exp_info'].'<br>';
          $csv_Add[] = $csv_optionalinfo;
          echo "<td>";
          if($row['booked'] == 'FALSE'){
            echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target=".editingModal-'.$x.'" ><i class="fa fa-pencil"></i></button> ';
          }
          echo "<a tabindex='0' role='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Person - Stundenkonto - Stundenrate - Projektstatus' data-content='$detailInfo' data-placement='left'><i class='fa fa-info'></i></a>";
          if(!empty($interninfo)){ echo " <a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Intern' data-content='$interninfo' data-placement='left'><i class='fa fa-question-circle-o'></i></a>"; }
          if(!empty($optionalinfo)){ echo " <a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['ADDITIONAL_FIELDS']."' data-content='$optionalinfo' data-placement='left'><i class='fa fa-question-circle'></i></a>"; }
          if(!empty($expensesinfo)){ echo " <a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['EXPENSES']."' data-content='$expensesinfo' data-placement='left'><i class='fa fa-plus'></i></a>"; }             echo '</td>';
          echo "</td>";
          echo '<td><input type="text" style="display:none;" name="editingIndeces[]" value="' . $row['projectBookingID'] . '"></td>'; //needed to check what has been charged
          echo "</tr>";

          $csv->addLine($csv_Add);
          $sum_min += timeDiff_Hours($row['start'], $row['end']);
          $addTimeStart = $B;
        } //end while fetch_assoc

        echo "<tr>";
        echo "<td style='font-weight:bold'>Summary</td> <td></td> <td></td> <td></td> <td></td>";
        echo "<td>".number_format($sum_min*60, 2, '.', '')."</td> <td></td> <td></td> <td></td>";
        echo "</tr>";
        ?>
    </table>
  </form>
</div>
<script>
$(function () {
  $('[data-toggle="popover"]').popover({html : true});
})
</script>

<!-- Projectbooking Modal -->
<?php while($row = $editingResult->fetch_assoc()): $x = $row['projectBookingID']; ?>
<form method="post">
  <div class="modal fade editingModal-<?php echo $x ?>" role="dialog" aria-labelledby="mySmallModalLabel">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo substr($row['start'], 0, 10); ?></h4>
        </div>
        <div class="modal-body" style="max-height: 80vh;  overflow-y: auto;">
          <?php
          echo "<input type='text' name='set_all_filters' style='display:none' value='$filterDate,$filterCompany,$filterClient,$filterProject,$filterUserID,$filterAddBreaks,$filterAddDrives,$booked' />";
          if(!empty($row['projectID'])){ //if this is a break, do not display client/project selection
            echo "<select style='width:200px' class='js-example-basic-single' onchange='showNewProjects(\" #newProjectName$x \", this.value, 0);' >";
            $sql = "SELECT * FROM $clientTable ORDER BY NAME ASC";
            if($filterCompany){
              $sql = "SELECT * FROM $clientTable WHERE companyID = $filterCompany ORDER BY NAME ASC";
            }
            $clientResult = $conn->query($sql);
            while($clientRow = $clientResult->fetch_assoc()){
              $selected = '';
              if($clientRow['id'] == $row['clientID']){
                $selected = 'selected';
              }
              echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
            }
            echo "</select> <select style='width:200px' id='newProjectName$x' class='js-example-basic-single' name='editing_projectID_$x'>";
            $sql = "SELECT * FROM $projectTable WHERE clientID =".$row['clientID'].'  ORDER BY NAME ASC';
            $clientResult = $conn->query($sql);
            while($clientRow = $clientResult->fetch_assoc()){
              $selected = '';
              if($clientRow['id'] == $row['projectID']){
                $selected = 'selected';
              }
              echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
            }
            echo "</select> <br><br>";
          } //end if(break)

          $A = carryOverAdder_Hours($row['start'],$row['timeToUTC']);
          $B = carryOverAdder_Hours($row['end'],$row['timeToUTC']);

          if($row['chargedTimeStart'] == '0000-00-00 00:00:00'){
            $A_charged = '0000-00-00 00:00:00';
          } else {
            $A_charged = carryOverAdder_Hours($row['chargedTimeStart'],$row['timeToUTC']);
          }
          if($row['chargedTimeEnd'] == '0000-00-00 00:00:00'){
            $B_charged = '0000-00-00 00:00:00';
          } else {
            $B_charged = carryOverAdder_Hours($row['chargedTimeEnd'],$row['timeToUTC']);
          }
          ?>

          <label><?php echo $lang['DATE']; ?>:</label>
          <div class="row">
            <div class="col-xs-6"><input type='text' class='form-control' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name="editing_time_from_<?php echo $x;?>" value="<?php echo substr($A,0,16); ?>"></div>
            <div class="col-xs-6"><input type='text' class='form-control' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_time_to_<?php echo $x;?>' value="<?php echo substr($B,0,16); ?>"></div>
          </div>
          <br>
          <label><?php echo $lang['DATE'] .' '. $lang['CHARGED']; ?>:</label>
          <div class="row">
            <div class="col-xs-6"><input type='text' class='form-control' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_chargedtime_from_<?php echo $x;?>' value="<?php echo substr($A_charged,0,16); ?>"></div>
            <div class="col-xs-6"><input type='text' class='form-control' maxlength='16' onkeydown='if(event.keyCode == 13){return false;}' name='editing_chargedtime_to_<?php echo $x;?>' value="<?php echo substr($B_charged,0,16); ?>"></div>
          </div>
          <br>
          <label>Infotext</label>
          <textarea style='resize:none;' name='editing_infoText_<?php echo $x;?>' class='form-control' rows="5"><?php echo $row['infoText']; ?></textarea>
          <br>
          <?php
          if($row['bookingType'] != 'break'){//cant charge a break, can you
            echo "<div class='row'><div class='col-xs-2 col-xs-offset-8'><input id='".$x."_1' type='checkbox' onclick='toggle2(\"".$x."_2\")' $selected name='editing_charge' value='".$x."' /> <label>".$lang['CHARGED']. "</label> </div>"; //gotta know which ones he wants checked.
            echo "<div class='col-xs-2'><input id='".$x."_2' type='checkbox' onclick='toggle2(\"".$x."_1\")' name='editing_nocharge' value='".$x."' /> <label>".$lang['NOT_CHARGEABLE']. "</label> </div></div>";
          }
          ?>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning" name="editing_save" value="<?php echo $x;?>"><?php echo $lang['SAVE']; ?></button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</form>
<?php endwhile;?>
<?php endif; //end if($result && $result->num_rows > 0) ?>

<div id="project_footer">
  <br><!-- BUTTONS -->
  <div class="row">
    <?php
    $diabled = 'disabled';
    if($filterUserID != 0 && preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $filterDate)){ $diabled = ''; }
    ?>
    <div class="col-xs-6">
      <button type='submit' class="btn btn-warning" name='saveChanges' form="project_table"><?php echo $lang['SAVE']; ?></button>
      <button class="btn btn-default" <?php echo $diabled; ?> data-toggle="collapse" href="#add_bookings_collapse" title="Specify day and user to add bookings"><?php echo $lang['BOOKINGS'] .' '.$lang['ADD']; ?></button>
    </div>
    <?php if($result && $result->num_rows > 0): ?>
    <div class="col-xs-2">
      <form action="csvDownload.php" method="post" target='_blank'>
        <button type='submit' class="btn btn-default btn-block" name=csv value=<?php $csv->setEncoding("UTF-16LE"); echo rawurlencode($csv->compile()); ?>> Download as CSV </button>
      </form>
    </div>
    <div class="col-xs-2">
      <form action="pdfDownload.php" method="post" target='_blank'>
        <input type="text" style="display:none" name="filterQuery" value="<?php echo "WHERE $projectBookingTable.start LIKE '$filterDate%' ". $bookedQuery. $filterProjectClientCompany. $filterUserIDAdd. $filterNoBreakAdd. $filterNoDriveAdd; ?>" />
        <div class="dropup">
          <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Download as PDF
            <span class="caret"></span>
          </button>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenu2">
            <?php
            $result = $conn->query("SELECT * FROM $pdfTemplateTable");
            while($result && ($row = $result->fetch_assoc())){
              echo "<li><button type='submit' name='templateID' value='".$row['id']."' class='btn' style='background:none'>".$row['name']."</button></li>";
            }
            ?>
          </ul>
        </div>
      </form>
    </div>
  <?php endif; //end if($result && $result->num_rows > 0) ?>
  </div>
<br>

  <!-- ADD BOOKING TO USER -->
<form method="POST">
  <div class="collapse" id="add_bookings_collapse" aria-expanded="false">
    <div class="well">
      <div class="container-fluid">
        <div class="checkbox">
          <div class="col-sm-2">
            <input type="checkbox" onclick="hideMyDiv(this)" name="addBreak" title="Das ist eine Pause"> <a style="color:black;"> <i class="fa fa-cutlery" aria-hidden="true"> </i> </a> Pause
          </div>
          <div class="col-sm-2">
            <input type="checkbox" name="addDrive" title="Fahrzeit"> <a style="color:black;"> <i class="fa fa-car" aria-hidden="true"> </i> </a> Fahrzeit
          </div>
          <div class="col-sm-2">
            <input type="checkbox" name="addExpenses" onchange="showMyDiv(this, 'hide_expenses')" /><?php echo $lang['EXPENSES']; ?>
          </div>
        </div>
      </div>
      <div class="row">
        <div id="mySelections" class="col-xs-9"><br>
          <?php
          echo "<input type='text' name='set_all_filters' style='display:none' value='$filterDate,$filterCompany,$filterClient,$filterProject,$filterUserID,$filterAddBreaks,$filterAddDrives,$booked' />";
          $query = "SELECT * FROM companyData WHERE id IN (SELECT DISTINCT companyID FROM relationship_company_client WHERE userID = $filterUserID) ";
          $result = mysqli_query($conn, $query);
          if($result->num_rows == 1):
            $row = $result->fetch_assoc();
            $query = "SELECT * FROM $clientTable WHERE companyID=".$row['id'];
            $result = mysqli_query($conn, $query);
            if ($result && $result->num_rows > 0) {
              echo '<select style="width:200px" class="js-example-basic-single" id="addSelectClient" name="client" onchange="showNewProjects(\'#addSelectProject\', this.value, 0)">';
              echo "<option name='act' value='0'>Select...</option>";
              while ($row = $result->fetch_assoc()) {
                $cmpnyID = $row['id'];
                $cmpnyName = $row['name'];
                echo "<option name='act' value='$cmpnyID'>$cmpnyName</option>";
              }
            }
            echo '</select>';
          else:
            ?>
            <select name="company"  class="js-example-basic-single" style='width:200px' class="" onchange="showNewClients('#addSelectClient', this.value, 0)">
              <option name=cmp value=0>Select...</option>
              <?php
              if ($result && $result->num_rows > 1) {
                while ($row = $result->fetch_assoc()) {
                  $cmpnyID = $row['id'];
                  $cmpnyName = $row['name'];
                  echo "<option name='cmp' value='$cmpnyID'>$cmpnyName</option>";
                }
              }
              ?>
            </select>
            <select id="addSelectClient" style='width:200px' class="js-example-basic-single" name="client" onchange="showNewProjects('#addSelectProject', this.value, 0)">
            </select>
          <?php endif; ?>
          <select id="addSelectProject" style='width:200px' class="js-example-basic-single" name="project" onchange="showProjectfields(this.value);">
          </select>
        </div>
      </div>
      <div id="hide_expenses" class="row" style="display:none">
        <br>
        <div class="col-md-2">
          <input type="number" step="0.01" name="expenses_unit" class="form-control" placeholder="<?php echo $lang['QUANTITY']; ?>" />
        </div>
        <div class="col-md-2">
          <input type="number" step="0.01" name="expenses_price" class="form-control" placeholder="<?php echo $lang['PRICE_STK']; ?>" />
        </div>
        <div class="col-md-8">
          <input type="text" name="expenses_info" class="form-control" placeholder="<?php echo $lang['DESCRIPTION']; ?>" />
        </div>
      </div>
      <div class="row">
        <div class="col-xs-6">
          <br><textarea class="form-control" rows="3" name="infoText" placeholder="Info..."></textarea><br>
        </div>
        <div class="col-xs-3">
          <br><textarea class="form-control" rows="3" name="internInfoText" placeholder="Intern... (Optional)"></textarea><br>
        </div>
      </div>
      <div id="project_fields" class="row">
      </div><br>
      <div class="row">
        <div class="col-xs-6">
          <div class="input-group">
            <input type="time" class="form-control" onkeydown='if (event.keyCode == 13) return false;' name="start" value="<?php echo substr($addTimeStart,11,5); ?>" >
            <span class="input-group-addon"> - </span>
            <input type="time" class="form-control" onkeydown='if (event.keyCode == 13) return false;' name="end">
            <div class="input-group-btn">
              <button class="btn btn-primary" type="submit"  name="add"> + </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
<!-- END ADD-BOOKING FIELD -->
</div>


<?php include 'footer.php'; ?>
