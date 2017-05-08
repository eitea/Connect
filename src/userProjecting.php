<?php require_once 'header.php'; ?>
<?php enableToBookings($userID);?>
<?php
$sql = "SELECT * FROM $logTable WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00'";
$result = mysqli_query($conn, $sql);
if($result && $result->num_rows > 0){
  $row = $result->fetch_assoc();
  $start = substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']), 11, 5);
  $end = substr(carryOverAdder_Hours(getCurrentTimestamp(), $row['timeToUTC']), 11, 5);
  $date = substr($row['time'], 0, 10);
  $indexIM = $row['indexIM']; //this value must not change
  $timeToUTC = $row['timeToUTC']; //just in case.
} else {
  redirect("home.php");
}

//ADDENDUMS
$request_addendum = array();
$result = $conn->query("SELECT indexIM, timeToUTC FROM logs WHERE userID = $userID AND DATE('".carryOverAdder_Hours($start, -182)."') < time"); //182h = 8 days
while($result && ($row = $result->fetch_assoc())){
  $i = $row['indexIM'];
  $res_b = $conn->query("SELECT * FROM projectBookingData WHERE timestampID = $i ORDER BY start ASC");
  if($res_b && $res_b->num_rows > 0){
    $row_b_prev = $res_b->fetch_assoc();
    $endTime = $row_b_prev['end'];
    while($row_b = $res_b->fetch_assoc()){ //next
      if(timeDiff_Hours($endTime, $row_b['start']) > $bookingTimeBuffer/60){
        //user will be FORCED to add, i know the next add has boundaries
        $request_addendum['timeToUTC'] = $row['timeToUTC'];
        $request_addendum['prev_row'] = $row_b_prev;
        $request_addendum['cur_row'] = $row_b;
        //for the post request, we have to know what he is currently editing
        $indexIM = $i;
        $date = substr($row_b['start'],0,10);
        $timeToUTC = $row['timeToUTC'];
        break;
      }
      $endTime = $row_b['end'];
      $row_b_prev = $row_b; //trivial
    }
    if($request_addendum) break;
  }
}

$showUndoButton = $showEmergencyUndoButton = 0;
$insertInfoText = $insertInternInfoText = $missing_highlights = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if(!empty($_POST['captcha'])){
    die("Bot detected. Aborting all Operations.");
  }

  if(isset($_POST["add"]) && isset($_POST['end']) && !empty(trim($_POST['infoText']))){
    $startDate = $date." ".$_POST['start'];
    $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);

    $endDate = $date." ".$_POST['end'];
    $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);

    $insertInfoText = test_input($_POST['infoText']);
    $insertInternInfoText = test_input($_POST['internInfoText']);

    if(timeDiff_Hours($startDate, $endDate) > 0){
      if(isset($_POST['addBreak'])){ //break
        $startDate = substr($startDate, 0, 17). rand(10,59);
        $endDate = substr($endDate, 0, 17). rand(10,59);
        $sql = "INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType) VALUES('$startDate', '$endDate', $indexIM, '$insertInfoText' , 'break')";
        $conn->query($sql);
        $duration = timeDiff_Hours($startDate, $endDate);
        $sql= "UPDATE logs SET breakCredit = (breakCredit + $duration) WHERE indexIm = $indexIM"; //update break credit
        $conn->query($sql);
        $insertInfoText = $insertInternInfoText = '';
        $showUndoButton = TRUE;
      } else {
        if(isset($_POST['addExpenses'])){
          $expenses_price = test_input($_POST['expenses_price']);
          $expenses_info = test_input($_POST['expenses_info']);
          $expenses_unit = test_input($_POST['expenses_unit']);
        } else {
          $expenses_price = $expenses_info = $expenses_unit = '';
        }
        if(isset($_POST['filterProject'])){
          $projectID = test_input($_POST['filterProject']);
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
            $insertInfoText = $insertInternInfoText = '';
            $showUndoButton = TRUE;
            if($request_addendum) redirect('userProjecting.php');
          } else {
            echo '<div class="alert alert-danger fade in">';
            echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
            echo '<strong>Could not create entry: </strong>Required fields may not be empty. (Highlighted)';
            echo '</div>';
            $missing_highlights = 'required-field';
          }
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
      echo '<strong>Could not create entry: </strong>Entered times were invalid, click the infobutton for more detail.';
      echo '</div>';
    }
  } elseif(isset($_POST['add'])){
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="userProjecting.php" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Could not create entry: </strong> Fields may not be empty. (Highlighted)';
    $missing_highlights = 'required-field';
    echo '</div>';
  }
}

if(isset($_POST['undo']) && $_POST['undo'] == 'emergency'){
  $conn->query("UPDATE UserData SET emUndo = UTC_TIMESTAMP WHERE id = $userID");
}

$result = $conn->query("SELECT emUndo FROM UserData WHERE id = $userID");
$row = $result->fetch_assoc();
if(timeDiff_Hours($row['emUndo'], getCurrentTimestamp()) > 2){
  $showEmergencyUndoButton = TRUE;
}

echo mysqli_error($conn);
?>

<style>
.robot-control{
  display:none;
}
</style>

<div class="page-header">
  <h3><?php echo $lang['BOOK_PROJECTS'] .'<small>: &nbsp ' . $date .'</small>'; ?></h3>
</div>

<form method="post">
<?php if(!$request_addendum): ?>
  <div style='text-align:right;'>
    <?php if($showUndoButton): ?>
      <button type='submit' class="btn btn-warning" name='undo' value='noEmergency'>Undo</button>
    <?php elseif($showEmergencyUndoButton): ?>
      <button type='submit' class="btn btn-danger" name='undo' value='emergency' title='Emergency Undo. Can only be pressed every 2 Hours'>Undo</button>
    <?php endif; ?>
    <button type='button' class="btn btn-default" style="border:0;background:0;" data-toggle="collapse" href="#userProjecting_info" aria-expanded="false"><i class="fa fa-question-circle-o fa-2x"></i></button>
  </div>
  <br>
  <div class="collapse" id="userProjecting_info">
    <div class="well">
      <?php echo $lang['USER_PROJECTING_INFO']; ?>
    </div>
  </div>
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
          if($result && $result->num_rows > 0){
            $numRows = $result->num_rows;
            if(isset($_POST['undo'])){
              $numRows--;
            }

            for($i=0; $i<$numRows; $i++){
              $row = $result->fetch_assoc();
              if($row['bookingType'] == 'break'){
                $icon = "fa fa-cutlery";
              } elseif($row['bookingType'] == 'drive'){
                $icon = "fa fa-car";
              } else {
                $icon = "fa fa-bookmark"; //fa-paw, fa-moon-o, star-o, snowflake-o, heart, umbrella, leafs, bolt, music, bookmark
              }
              $interninfo = $row['internInfo'];
              $expensesinfo = $optionalinfo = '';
              $extraFldRes = $conn->query("SELECT name FROM $companyExtraFieldsTable WHERE companyID = ".$row['companyID']);
              if($extraFldRes && $extraFldRes->num_rows > 0){
                $extraFldRow = $extraFldRes->fetch_assoc();
                if($row['extra_1']){$optionalinfo = '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_1'].'<br>'; }
              }
              if($extraFldRes && $extraFldRes->num_rows > 1){
                $extraFldRow = $extraFldRes->fetch_assoc();
                if($row['extra_2']){$optionalinfo .= '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_2'].'<br>'; }
              }
              if($extraFldRes && $extraFldRes->num_rows > 2){
                $extraFldRow = $extraFldRes->fetch_assoc();
                if($row['extra_3']){$optionalinfo .= '<strong>'.$extraFldRow['name'].'</strong><br>'.$row['extra_3']; }
              }
              if($row['exp_unit'] > 0) $expensesinfo .= $lang['QUANTITY'].': '.$row['exp_unit'].'<br>';
              if($row['exp_price'] > 0) $expensesinfo .= $lang['PRICE_STK'].': '.$row['exp_price'].'<br>';
              if($row['exp_info']) $expensesinfo .= $lang['DESCRIPTION'].': '.$row['exp_info'].'<br>';

              echo "<tr>";
              echo "<td><i class='$icon'></i></td>";
              echo "<td>". substr(carryOverAdder_Hours($row['start'],$timeToUTC), 11, 5) ."</td>";
              echo "<td>". substr(carryOverAdder_Hours($row['end'], $timeToUTC), 11, 5) ."</td>";
              echo "<td>". $row['name'] ."</td>";
              echo "<td>". $row['projectName'] ."</td>";
              echo "<td style='text-align:left'>". $row['infoText'] ."</td>";
              echo "<td style='text-align:left'>";
              if(!empty($interninfo)){ echo " <a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Intern' data-content='$interninfo' data-placement='left'><i class='fa fa-question-circle-o'></i></a>"; }
              if(!empty($optionalinfo)){ echo " <a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Optional' data-content='$optionalinfo' data-placement='left'><i class='fa fa-question-circle'></i></a>"; }
              if(!empty($expensesinfo)){ echo " <a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['EXPENSES']."' data-content='$expensesinfo' data-placement='left'><i class='fa fa-plus'></i></a>"; }             echo '</td>';
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
<?php
else:
  include "userProjecting_addendum.php";
endif;
?>
  <br><br><br>

  <div class="container-fluid">
    <div class="checkbox">
      <div class="col-sm-3">
        <input type="checkbox" name="addDrive" /> <a style="color:black;"> <i class="fa fa-car" aria-hidden="true"> </i> </a> <?php echo $lang['TRAVEL_TIME']; ?>
      </div>
      <div class="col-sm-3">
        <input type="checkbox" name="addExpenses" onchange="showMyDiv(this, 'hide_expenses')" /><?php echo $lang['EXPENSES']; ?>
      </div>
      <div id="hide_break" class="col-sm-3"></div>
    </div>
  </div>

  <!-- SELECTS -->
  <div class="row">
    <div id=mySelections class="col-xs-9"><br>
      <?php if(count($available_companies) > 2): ?>
        <select name="filterCompany"  class="js-example-basic-single" style='width:200px' class="" onchange="showClients(this.value, 0)">
          <option value="0"><?php echo $lang['COMPANY']; ?>...</option>
          <?php
          $query = "SELECT * FROM $companyTable WHERE id IN (".implode(', ', $available_companies).") ";
          $result = mysqli_query($conn, $query);
          if($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $cmpnyID = $row['id'];
              $cmpnyName = $row['name'];
              echo "<option name='cmp' value='$cmpnyID'>$cmpnyName</option>";
            }
          }
          ?>
        </select>
      <?php
        $setCompany = 0;
      else:
        $setCompany = $available_companies[1];
      endif;
      echo '<select id="clientHint" style="width:200px" class="js-example-basic-single" name="filterClient" onchange="showProjects(this.value, 0)">';
      $result = mysqli_query($conn, "SELECT * FROM $clientTable WHERE companyID=$setCompany");
      echo "<option value='0'>".$lang['CLIENT']."...</option>";
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $cmpnyID = $row['id'];
          $cmpnyName = $row['name'];
          echo "<option value='$cmpnyID'>$cmpnyName</option>";
        }
      }
      echo '</select>';
      ?>
      <select id="projectHint" style='width:200px' class="js-example-basic-single" name="filterProject" onchange="showProjectfields(this.value);">
      </select>
    </div>
  </div>
  <!-- /SELECTS -->

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
    <div class="col-md-8">
      <br><textarea class="form-control <?php echo $missing_highlights; ?>" style='resize:none;overflow:hidden' rows="3" name="infoText" placeholder="Info..."  onkeyup='textAreaAdjust(this);'><?php echo $insertInfoText; ?></textarea><br>
    </div>
    <div class="col-md-4">
      <br><textarea class="form-control required-field-subtle" style='resize:none;overflow:hidden' rows="3" name="internInfoText" placeholder="Intern... (Optional)" onkeyup='textAreaAdjust(this);'><?php echo $insertInternInfoText; ?></textarea><br>
    </div>
  </div>

  <div id="project_fields" class="row">
  </div><br>

  <div class="row">
    <div class="col-md-6">
      <div class="input-group input-daterange">
        <input type="time" class="form-control" onkeypress="return event.keyCode != 13;" readonly name="start" value="<?php echo substr($start,0,5); ?>" >
        <span class="input-group-addon"> - </span>
        <input type="time" class="form-control" onkeypress="return event.keyCode != 13;"  min="<?php echo substr($start,0,5); ?>"  name="end" value="<?php echo $end; ?>" />
        <div class="input-group-btn">
          <button class="btn btn-warning" type="submit"  name="add"> + </button>
        </div>
      </div>
    </div>
  </div>
  <div class="robot-control"> <input type="text" name="captcha" value="" /></div>
</form>

<script>
$(function () {
  $('[data-toggle="popover"]').popover({html : true});
});
function textAreaAdjust(o) {
  o.style.height = "90px";
  o.style.height = (o.scrollHeight)+"px";
}
function showClients(company, client){
  $.ajax({
    url:'ajaxQuery/AJAX_getClient.php',
    data:{companyID:company, clientID:client},
    type: 'get',
    success : function(resp){
      $("#clientHint").html(resp);
    },
    error : function(resp){},
    complete : function(resp){
      showProjects(client, 0);
    }
  });
}
function showProjects(client, project){
  $.ajax({
    url:'ajaxQuery/AJAX_getProjects.php',
    data:{clientID:client, projectID:project},
    type: 'get',
    success : function(resp){
      $("#projectHint").html(resp);
    },
    error : function(resp){},
    complete : function(resp){
      showProjectfields($("#projectHint").val());
    }
  });
}
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
</script>

<?php require_once 'footer.php'; ?>
