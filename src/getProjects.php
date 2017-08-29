<?php include 'header.php'; enableToProject($userID); ?>
<?php include dirname(__DIR__) . "/plugins/csvParser/Csv.php"; use Deblan\Csv\Csv; ?>
<style>
.popover{
  max-width: 40%; /* Max Width of the popover (depending on the container!) */
}
</style>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
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
      }
      if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>'; }
    } else {
      echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    }
    echo mysqli_error($conn);
  } elseif(isset($_POST['saveChanges'])){ //i dont want one save button to trigger the other
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
          }
        }
      }
      if(!mysqli_error($conn)){
        echo '<div class="alert alert-success alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
      }
    } //end if isset charged
  }
  if(isset($_POST['delete_booking'])){
    $x = intval($_POST['delete_booking']);
    $conn->query("DELETE FROM projectBookingData WHERE id = $x");
    if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
  }
  if(isset($_POST["add"]) && !empty($_POST['user']) && !empty($_POST['add_date']) && isset($_POST['start']) && isset($_POST['end']) && !empty(trim($_POST['infoText']))){
    if(test_Date($_POST['add_date'].' 08:00:00')){
      $filterUserID = intval($_POST['user']);
      //get the timestamp. always(!) compre UTC to UTC
      $sql = "SELECT * FROM $logTable WHERE userID = $filterUserID AND DATE(time) = DATE(DATE_SUB('".$_POST['add_date']." ".$_POST['start']."', INTERVAL timeToUTC HOUR)) AND status = '0'";
      $result = mysqli_query($conn, $sql);
      if($result && $result->num_rows > 0){
        $row = $result->fetch_assoc();
        $indexIM = $row['indexIM'];
        $timeToUTC = $row['timeToUTC'];

        $startDate = $_POST['add_date']." ".$_POST['start'];
        $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);

        $endDate = $_POST['add_date']." ".$_POST['end'];
        $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);

        $insertInfoText = test_input($_POST['infoText']);
        $insertInternInfoText = test_input($_POST['internInfoText']);

        if(timeDiff_Hours($startDate, $endDate) > 0){
          if(isset($_POST['addBreak'])){ //checkbox
            $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$startDate', '$endDate', $indexIM, '$insertInfoText', 'break')";
            $conn->query($sql);
            $duration = timeDiff_Hours($startDate, $endDate);
            $sql= "UPDATE $logTable SET breakCredit = (breakCredit + $duration) WHERE indexIm = $indexIM";
            if($conn->query($sql)){
              echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            } else {
              echo '<div class="alert alert-success alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
            }
          } else {
            if(isset($_POST['addExpenses'])){
              $expenses_price = test_input($_POST['expenses_price']);
              $expenses_info = test_input($_POST['expenses_info']);
              $expenses_unit = test_input($_POST['expenses_unit']);
            } else {
              $expenses_price = $expenses_unit = 0;
              $expenses_info = '';
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
                if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
                $insertInfoText = $insertInternInfoText = '';
              } else {
                echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
              }
            } else {
              echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_SELECTION'].'</div>';
            }
          }
        } else {
          echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_TIMES_INVALID'].'</div>';
        }
      } else {
        echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_TIMESTAMP'].'</div>';
      }
    } else {
      echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_TIMES_INVALID'].'</div>';
    }
  } elseif(isset($_POST['add'])) {
    echo '<div class="alert alert-danger alert-over"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
  }
} //end if POST

$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0, "user" => 0, "bookings" => array(1, '', 'checked'), "date" => array(substr(getCurrentTimestamp(), 0, 8).'01', date('Y-m-t', strtotime(getCurrentTimestamp()))) ); //init: display all
?>

<div style="position:fixed;background:white;width:100%;z-index:1">
  <div class="page-header">
    <h3><?php echo $lang['VIEW_PROJECTS']; ?>
      <div class="page-header-button-group">
        <?php include "misc/set_filter.php"; //this is where the magic happens ?>
        <button type='submit' class="btn btn-default" name='saveChanges' form="project_table"><i class="fa fa-floppy-o"></i></button>
        <button type="button" class="btn btn-default" data-toggle="modal" data-target=".add-booking" title="<?php echo $lang['BOOKINGS'] .' '.$lang['ADD']; ?>"><i class="fa fa-plus"></i></button>
        <form id="csvDownPlace" action="csvDownload" method="POST" target='_blank' style="display:inline"></form>
        <form action="pdfDownload" method="POST" target='_blank' style="display:inline-block">
          <?php //quess who needs queries.
          $companyQuery = $clientQuery = $projectQuery = $productiveQuery = $userQuery = $chargedQuery = $breakQuery = $driveQuery = "";
          if($filterings['company']){$companyQuery = "AND $companyTable.id = ".$filterings['company']; }
          if($filterings['client']){$clientQuery = "AND $clientTable.id = ".$filterings['client']; }
          if($filterings['project']){$projectQuery = "AND $projectTable.id = ".$filterings['project']; }
          if($filterings['user']){$userQuery = "AND $userTable.id = ".$filterings['user']; }
          if($filterings['bookings'][0] == '2'){$chargedQuery = "AND $projectBookingTable.booked = 'TRUE' "; } elseif($filterings['bookings'][0] == '1'){$chargedQuery= " AND $projectBookingTable.booked = 'FALSE' "; }
          if(!$filterings['bookings'][1]){$breakQuery = "AND $projectBookingTable.bookingType != 'break' "; } //projectID == NULL
          if(!$filterings['bookings'][2]){$driveQuery = "AND $projectBookingTable.bookingType != 'drive' "; }
          ?>
          <input type="hidden" name="filterQuery" value="<?php echo "WHERE DATE_ADD($projectBookingTable.start, INTERVAL $logTable.timeToUTC HOUR) > '".$filterings['date'][0]."'
          AND DATE_ADD($projectBookingTable.end, INTERVAL $logTable.timeToUTC HOUR) < '".$filterings['date'][1]."'
          AND (($projectBookingTable.projectID IS NULL $breakQuery $driveQuery $userQuery) OR ( 1 $chargedQuery $companyQuery $clientQuery $projectQuery $productiveQuery $userQuery $breakQuery $driveQuery))"; ?>" />
          <div class="dropdown">
            <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-download"></i> PDF</button>
            <ul class="dropdown-menu">
              <?php
              $res = $conn->query("SELECT * FROM $pdfTemplateTable");
              while($res && ($row = $res->fetch_assoc())){ echo "<li><button type='submit' name='templateID' value='".$row['id']."' class='btn' style='background:none'>".$row['name']."</button></li>"; }
              ?>
            </ul>
          </div>
        </form>
      </div>
    </h3>
  </div>
</div>
<?php
//filtering a project means excluding all breaks. we dont want that, which explains the query below.
$sql = "SELECT $projectTable.id AS projectID,
$clientTable.id AS clientID, $companyTable.id AS companyID,
$companyTable.name AS companyName, $clientTable.name AS clientName, $projectTable.name AS projectName,
$userTable.id AS userID, $userTable.firstname, $userTable.lastname,
$projectBookingTable.*, $projectBookingTable.id AS projectBookingID,
$logTable.timeToUTC,
$projectTable.hours, $projectTable.hourlyPrice, $projectTable.status
FROM $projectBookingTable
INNER JOIN $logTable ON  $projectBookingTable.timeStampID = $logTable.indexIM
INNER JOIN $userTable ON $logTable.userID = $userTable.id
LEFT JOIN $projectTable ON $projectBookingTable.projectID = $projectTable.id
LEFT JOIN $clientTable ON $projectTable.clientID = $clientTable.id
LEFT JOIN $companyTable ON $clientTable.companyID = $companyTable.id
WHERE DATE_ADD($projectBookingTable.start, INTERVAL $logTable.timeToUTC HOUR) > '".$filterings['date'][0]."'
AND DATE_ADD($projectBookingTable.end, INTERVAL $logTable.timeToUTC HOUR) < '".$filterings['date'][1]."'
AND (($projectBookingTable.projectID IS NULL $breakQuery $userQuery) OR ( 1 $userQuery $chargedQuery $companyQuery $clientQuery $projectQuery $productiveQuery $driveQuery $breakQuery))
ORDER BY $projectBookingTable.start ASC";
$result = $conn->query($sql);
$editingResult = $conn->query($sql); //f*ck you php
if(!$result || $result->num_rows <= 0){
  echo '<script>document.getElementById("set_filter_search").click();</script>';
}
?>
<form id="project_table" method="post">
  <div style="margin-top:120px;"></div>
  <table class="table table-hover table-condensed">
    <thead>
      <th><?php echo $conn->error ; ?></th>
      <th><?php echo $lang['COMPANY'].' - '.$lang['CLIENT'].' - '.$lang['PROJECT']; ?></th>
      <th><?php echo $lang['USERS']; ?></th>
      <th style="max-width:300px">Infotext</th>
      <th><?php echo $lang['DATE']; ?></th>
      <th><?php echo $lang['DATE'] .' '. $lang['CHARGED']; ?></th>
      <th><?php echo $lang['MINUTES']; ?></th>
      <th>
        <label><input type="radio" class="disable-styling" onClick="toggle('checkingIndeces', 'noCheckCheckingIndeces')" name="toggleRadio"> <?php echo $lang['CHARGED']; ?></label>
        <label><input type="radio" class="disable-styling" onClick="toggle('noCheckCheckingIndeces', 'checkingIndeces')" name="toggleRadio"> <?php echo $lang['NOT_CHARGEABLE']; ?></label>
      </th>
      <th>Detail</th>
      <th></th>
    </thead>
    <tbody>
      <?php
      $csv = new Csv();
      $csv->setLegend(array($lang['CLIENT'], $lang['PROJECT'], 'Info', $lang['DATE'].' - '. $lang['FROM'], $lang['DATE'].' - '. $lang['TO'],
      $lang['TIMES'].' - '. $lang['FROM'], $lang['TIMES'].' - '. $lang['TO'], $lang['SUM'].' (min)', $lang['HOURS_CREDIT'], 'Person', $lang['HOURLY_RATE'],
      $lang['ADDITIONAL_FIELDS'], $lang['EXPENSES']));

      $sum_min = 0;
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
        } elseif($row['bookingType'] == 'mixed'){
          $icon = "fa fa-plus";
          $row['infoText'] = $lang['ACTIVITY_TOSTRING'][$row['mixedStatus']];
        } else {
          $icon = "fa fa-bookmark";
        }

        $csv_Add = array();
        echo '<tr>';
        echo "<td style='width:10px'><i class='$icon'></i></td>";
        echo '<td>'.$row['companyName'].'<br> '.$row['clientName'].'<br> '.$row['projectName'].'</td>';
        echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
        echo '<td style="max-width:300px">'.$row['infoText'].'</td>';

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

        echo '<td style="width:200px;">'.substr($A,0,16).' - '.substr($B,11,5)."</td>";
        echo '<td style="max-width:200px;whitpre;">'.$lang['FROM'].': '.substr($A_charged,0,16)."<br>".$lang['TO'].':   '.substr($B_charged,0,16)."</td>";
        echo "<td>" .number_format((timeDiff_Hours($row['start'], $row['end']))*60, 0, '.', '') . "</td>";

        if($row['bookingType'] != 'break' && $row['booked'] != 'TRUE'){ //if this is a break or has been charged already, do not display dis
          echo "<td><input id='".$row['projectBookingID']."_01' type='checkbox' class='disable-styling' onclick='toggle2(\"".$row['projectBookingID']."_02\")' name='checkingIndeces[]' value='".$row['projectBookingID']."'>"; //gotta know which ones he wants checked.
          echo " / <input id='".$row['projectBookingID']."_02' type='checkbox' class='disable-styling' onclick='toggle2(\"".$row['projectBookingID']."_01\")' name='noCheckCheckingIndeces[]' value='".$row['projectBookingID']."'></td>";
        } else {
          echo "<td></td>";
        }

        $projStat = (!empty($row['status']))? $lang['PRODUCTIVE'] :  $lang['PRODUCTIVE_FALSE'];
        $detailInfo = $row['hours'] .' || '. $row['hourlyPrice'] .' || '. $projStat;
        $interninfo = $row['internInfo'];
        $optionalinfo = $csv_optionalinfo = $expensesinfo = $csv_expensesinfo = '';
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
        $csv_Add[] = $csv_optionalinfo;
        if($row['exp_unit'] > 0){ $expensesinfo .= $lang['QUANTITY'].': '.$row['exp_unit'].'<br>'; $csv_expensesinfo .= "{$lang['QUANTITY']}: {$row['exp_unit']},"; }
        if($row['exp_price'] > 0){ $expensesinfo .= $lang['PRICE_STK'].': '.$row['exp_price'].'<br>'; $csv_expensesinfo .= "{$lang['PRICE_STK']}: {$row['exp_price']}"; }
        if($row['exp_info']){ $expensesinfo .= $lang['DESCRIPTION'].': '.$row['exp_info'].'<br>'; $csv_expensesinfo .= ", {$lang['DESCRIPTION']}: {$row['exp_info']}"; }
        $csv_Add[] = $csv_expensesinfo;
        echo "<td>";
        if($row['booked'] == 'FALSE'){ echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target=".editingModal-'.$x.'" ><i class="fa fa-pencil"></i></button>'; }
        if($row['bookingType'] != 'break'){ echo "<a tabindex='0' role='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Stundenkonto - Stundenrate - Projektstatus' data-content='$detailInfo' data-placement='left'><i class='fa fa-info-circle'></i></a>";}
        if(!empty($interninfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Intern' data-content='$interninfo' data-placement='left'><i class='fa fa-question-circle-o'></i></a>"; }
        if(!empty($optionalinfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['ADDITIONAL_FIELDS']."' data-content='$optionalinfo' data-placement='left'><i class='fa fa-question-circle'></i></a>"; }
        if(!empty($expensesinfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['EXPENSES']."' data-content='$expensesinfo' data-placement='left'><i class='fa fa-plus'></i></a>"; }
        echo "<button type='submit' class='btn btn-default' value='$x' name='delete_booking'><i class='fa fa-trash-o'></i></button>";
        echo '</td>';
        echo '<td><input type="hidden" name="editingIndeces[]" value="'.$row['projectBookingID'].'"></td>'; //needed to check what has been charged
        echo "</tr>";

        $csv->addLine($csv_Add);
        $sum_min += timeDiff_Hours($row['start'], $row['end']);
      } //end while fetch_assoc

      echo "<tr>";
      echo "<td style='font-weight:bold'>Summary</td> <td></td> <td></td> <td></td> <td></td> <td></td>";
      echo "<td>".number_format($sum_min*60, 2, '.', '')."</td> <td></td> <td></td> <td></td>";
      echo "</tr>";
      ?>
    </tbody>
  </table>
</form>

<button id="csvDown" type='submit' class="btn btn-default" name="csv" value=<?php $csv->setEncoding("UTF-16LE"); echo rawurlencode($csv->compile()); ?> ><i class="fa fa-download"></i> CSV</button>
<script>
$(function () {
  $('[data-toggle="popover"]').popover({html : true});
  $("#csvDown").appendTo("#csvDownPlace");
})
</script>

<!-- Projectbooking Modal -->
<?php while($row = $editingResult->fetch_assoc()): $x = $row['projectBookingID']; ?>
  <form method="post">
    <div class="modal fade editingModal-<?php echo $x ?>" role="dialog" aria-labelledby="mySmallModalLabel">
      <div class="modal-dialog modal-lg modal-content" role="document">
        <div class="modal-header">
          <h4 class="modal-title"><?php echo substr($row['start'], 0, 10); ?></h4>
        </div>
        <div class="modal-body" style="max-height: 80vh;  overflow-y: auto;">
          <div class="row">
          <?php
          if(!empty($row['projectID'])){ //if this is no break, display client/project selection
            if(count($available_companies) > 1){
              echo "<div class='col-md-4'><select class='js-example-basic-single' onchange='showClients(\"#newClient$x\", this.value, 0, 0, \"#newProjectName$x\");' >";
              $companyResult = $conn->query("SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
              while($companyRow = $companyResult->fetch_assoc()){
                $selected = '';
                if($companyRow['id'] == $row['companyID']){
                  $selected = 'selected';
                }
                echo "<option $selected value=".$companyRow['id'].">".$companyRow['name']."</option>";
              }
              echo '</select></div>';
            }
            echo "<div class='col-md-4'><select id='newClient$x' class='js-example-basic-single' onchange='showProjects(\" #newProjectName$x \", this.value, 0);' >";
            $sql = "SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).") ORDER BY NAME ASC";
            if($filterings['company']){
              $sql = "SELECT * FROM $clientTable WHERE companyID = ".$filterings['company']." ORDER BY NAME ASC";
            }
            $clientResult = $conn->query($sql);
            while($clientRow = $clientResult->fetch_assoc()){
              $selected = '';
              if($clientRow['id'] == $row['clientID']){
                $selected = 'selected';
              }
              echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
            }
            echo "</select></div><div class='col-md-4'> <select id='newProjectName$x' class='js-example-basic-single' name='editing_projectID_$x'>";
            $sql = "SELECT * FROM $projectTable WHERE clientID =".$row['clientID'].'  ORDER BY NAME ASC';
            $clientResult = $conn->query($sql);
            while($clientRow = $clientResult->fetch_assoc()){
              $selected = '';
              if($clientRow['id'] == $row['projectID']){
                $selected = 'selected';
              }
              echo "<option $selected value=".$clientRow['id'].">".$clientRow['name']."</option>";
            }
            echo "</select></div> <br><br>";
          } //end if(!break)

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
        </div>
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
          if($row['bookingType'] != 'break' && $row['booked'] == 'FALSE'){//cant charge a break, can you
            echo "<div class='row'><div class='col-xs-2 col-xs-offset-8'><input id='".$x."_1' type='checkbox' onclick='toggle2(\"".$x."_2\")' name='editing_charge' value='".$x."' /> <label>".$lang['CHARGED']. "</label> </div>";
            echo "<div class='col-xs-2'><input id='".$x."_2' type='checkbox' onclick='toggle2(\"".$x."_1\")' name='editing_nocharge' value='".$x."' /> <label>".$lang['NOT_CHARGEABLE']. "</label> </div></div>";
          }
          ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning" name="editing_save" value="<?php echo $x;?>"><?php echo $lang['SAVE']; ?></button>
        </div>
      </div>
    </div>
  </form>
<?php endwhile;?>


<!-- ADD BOOKING TO USER -->
<form method="POST">
  <div class="modal fade add-booking">
    <div class="modal-dialog modal-lg modal-content" role="document">
      <div class="modal-header">
        <h4 class="modal-title"><?php echo $lang['ADD']; ?></h4>
      </div>
      <div class="modal-body">
        <div class="row checkbox">
          <div class="col-sm-3">
            <label><input type="checkbox" onchange="hideMyDiv(this, 'mySelections');" name="addBreak" title="Das ist eine Pause"><a style="color:black;"><i class="fa fa-cutlery" aria-hidden="true"></i></a>Pause</label>
          </div>
          <div class="col-sm-3">
            <label><input type="checkbox" name="addDrive" title="Fahrzeit"><a style="color:black;"><i class="fa fa-car" aria-hidden="true"></i></a>Fahrzeit</label>
          </div>
          <div class="col-sm-3">
            <label><input type="checkbox" name="addExpenses" onchange="showMyDiv(this, 'hide_expenses')" /><?php echo $lang['EXPENSES']; ?></label>
          </div>
        </div>
        <div class="row">
          <div class="col-xs-3">
            <label><?php echo $lang['USERS']; ?></label>
            <select class="js-example-basic-single" name="user" onchange="showLastBooking(this.value);">
              <?php
              $result = $conn->query("SELECT * FROM $userTable WHERE id IN (".implode(', ', $available_users).")");
              while ($result && ($row = $result->fetch_assoc())) {
                $selected = '';
                if($filterings['user'] == $row['id']) {
                  $selected = 'selected';
                }
                echo '<option '.$selected.' value="'.$row['id'].'">'.$row['firstname'].' '.$row['lastname'].'</option>';
              }
              ?>
            </select>
          </div>
          <div id="mySelections" style="display:inline">
            <?php
            $result = $conn->query("SELECT * FROM companyData WHERE id IN(".implode(', ', $available_companies).")");
            if($result && $result->num_rows > 1):
              ?>
              <div class="col-xs-3">
                <label><?php echo $lang['COMPANY']; ?></label>
                <select name="company" class="js-example-basic-single" onchange="showClients('#addSelectClient', this.value, 0, 0, '#addSelectProject');">
                  <option value="0">...</option>
                  <?php
                  while ($row = $result->fetch_assoc()) {
                    $selected = '';
                    if($filterings['company'] == $row['id']) {
                      $selected = 'selected';
                    }
                    echo "<option $selected value='".$row['id']."'>".$row['name']."</option>";
                  }
                  ?>
                </select>
              </div>
            <?php endif; ?>
            <div class="col-xs-3">
              <label><?php echo $lang['CLIENT']; ?></label>
              <select class="js-example-basic-single" id="addSelectClient" name="client" onchange="showProjects('#addSelectProject', this.value, 0);">
                <option value="0">...</option>
                <?php
                $row = $result->fetch_assoc();
                $query = "SELECT * FROM $clientTable WHERE companyID IN (".implode(', ', $available_companies).")";
                $result = mysqli_query($conn, $query);
                if ($result && $result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    echo "<option value='".$row['id']."'>".$row['name']."</option>";
                  }
                }
                ?>
              </select>
            </div>
            <div class="col-xs-3">
              <label><?php echo $lang['PROJECT']; ?></label>
              <select id="addSelectProject" class="js-example-basic-single" name="project" onchange="showProjectfields(this.value);">
              </select>
            </div>
          </div>
        </div>
        <div id="hide_expenses" class="row" style="display:none">
          <br>
          <div class="col-md-3">
            <input type="number" step="0.01" name="expenses_unit" class="form-control" placeholder="<?php echo $lang['QUANTITY']; ?>" />
          </div>
          <div class="col-md-3">
            <input type="number" step="0.01" name="expenses_price" class="form-control" placeholder="<?php echo $lang['PRICE_STK']; ?>" />
          </div>
          <div class="col-md-6">
            <input type="text" name="expenses_info" class="form-control" placeholder="<?php echo $lang['DESCRIPTION']; ?>" />
          </div>
        </div>
        <div class="row">
          <div class="col-xs-8">
            <br><textarea class="form-control" rows="3" name="infoText" placeholder="Info..."></textarea><br>
          </div>
          <div class="col-xs-4">
            <br><textarea class="form-control" rows="3" name="internInfoText" placeholder="Intern... (Optional)"></textarea><br>
          </div>
        </div>
        <div id="project_fields" class="row">
        </div>
        <br>
        <div class="row">
          <div class="col-sm-3">
            <label><?php echo $lang['DATE']; ?></label>
            <input type="date" class="form-control" name="add_date" value="<?php echo $filterings['date'][0]; ?>"/>
          </div>
          <div class="col-xs-6">
            <label><?php echo $lang['TIME']; ?></label>
            <div class="input-group">
              <input id="time_field" type="time" class="form-control" onkeydown='if (event.keyCode == 13) return false;' name="start" value=""/>
              <span class="input-group-addon"> - </span>
              <input type="time" class="form-control" onkeydown='if (event.keyCode == 13) return false;' name="end"/>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" name="add" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
        <button type="submit" class="btn btn-warning" name="add"><?php echo $lang['ADD']; ?></button>
      </div>
    </div>
  </div>
</form>

<script src="plugins/jsCookie/src/js.cookie.js"></script>
<script>
function showClients(place, company, client, project, projectPlace){
  $.ajax({
    url:'ajaxQuery/AJAX_getClient.php',
    data:{companyID:company, clientID:client},
    type: 'get',
    success : function(resp){
      $(place).html(resp);
    },
    error : function(resp){}
  });
  showProjects(projectPlace, client, project);
};
function showProjects(place, client, project){
  $.ajax({
    url:'ajaxQuery/AJAX_getProjects.php',
    data:{clientID:client, projectID:project},
    type: 'get',
    success : function(resp){
      $(place).html(resp);
    },
    error : function(resp){}
  });
  showProjectfields(project);
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
function showLastBooking(id){
  $.ajax({
    url:'ajaxQuery/AJAX_getLastBooking.php',
    type:'get',
    data:{userID:id},
    success: function(resp){
      $("#time_field").val(resp);
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
function hideMyDiv(o, toShow){
  if(o.checked){
    document.getElementById(toShow).style.display='none';
  } else {
    document.getElementById(toShow).style.display='block';
  }
}

if(Cookies.get('checkboxValues') !== undefined){
  var checkboxValues = Cookies.getJSON('checkboxValues');
  if(checkboxValues){
    $.each(checkboxValues, function(key, value) {
      var elem = document.getElementById(key)
      if(elem){
        elem.checked = value;
      }
    });
  }
} else {
  var checkboxValues = {};
}
$(":checkbox").on("change", function(){
  if(this.id){
    checkboxValues[this.id] = this.checked;
  }
  Cookies.set('checkboxValues', checkboxValues, { expires: 1, path: '' });
});

function toggle(checkId, uncheckId) {
  checkboxes = document.getElementsByName(checkId + '[]');
  checkboxesUncheck = document.getElementsByName(uncheckId + '[]');
  for(var i = 0; i<checkboxes.length; i++) {
    checkboxes[i].checked = true;
    checkboxesUncheck[i].checked = false;
    checkboxValues[checkboxes[i].id] = true;
  }
  Cookies.set('checkboxValues', checkboxValues, { expires: 1, path: '' });
}
function toggle2(uncheckID){
  uncheckBox = document.getElementById(uncheckID);
  uncheckBox.checked = false;
  checkboxValues[uncheckBox.id] = false;
}

$(document).ready(function(){
  $('.table').DataTable({
    order: [],
    ordering: false,
    deferRender: true,
    responsive: true,
    autoWidth: false,
    language: {
      <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    },
    dom: 'f',
    paginate: false,
    fixedHeader: {
      header: true,
      headerOffset: 160,
      zTop: 1
    }
  });
});
</script>

<?php
if(!empty($filterings['company'])){
  echo '<script>';
  echo 'showClients("#addSelectClient", '.$filterings['company'].', '.$filterings['client'].', '.$filterings['project'].', "#addSelectProject");';
  echo '</script>';
}
if(!empty($filterings['user'])){
  echo '<script>showLastBooking('.$filterings['user'].');</script>';
}
?>
<?php include 'footer.php'; ?>
