<?php include dirname(__DIR__) . '/header.php'; enableToTime($userID); ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
require_once dirname(__DIR__) . '/Calculators/IntervalCalculator.php';
$day = date('w');
$filterings = array("savePage" => $this_page, 'company' => 0, 'client' => 0, 'project' => 0, 'users' => array(), 'bookings' => array(1, '', 'checked'),
'date' => array(date('Y-m-d', strtotime('-'.$day.' days')), substr(getCurrentTimestamp(), 0, 10)), 'logs' => array(0, 'checked'));
$activeTab = 'home';
?>
<div class="page-header-fixed">
<div class="page-header"><h3><?php echo $lang['TIMES'].' - '.$lang['OVERVIEW']; ?><div class="page-header-button-group"><?php include dirname(__DIR__) . '/misc/set_filter.php'; ?></div></h3></div>
</div>
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['setActiveTab'])){$activeTab = $_POST['setActiveTab']; }

    if (!empty($_POST['ts_remove'])){
        $x = intval($_POST['ts_remove']);
        $conn->query("DELETE FROM logs WHERE indexIM=$x;");
        if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
    } elseif(!empty($_POST['bk_remove'])){
        $x = intval($_POST['bk_remove']);
        $conn->query("DELETE FROM projectBookingData WHERE id=$x;");
        if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>'; }
    }
    if(isset($_POST['saveTimestamp'])){
        $imm = $_POST['saveTimestamp'];
        $timeStart = str_replace('T', ' ',$_POST['timesFrom']) .':00';
        $timeFin = str_replace('T', ' ',$_POST['timesTo']) .':00';
        $status = intval($_POST['newActivity']);
        $addBreakVal = floatval($_POST['addBreakValues']);
        if($addBreakVal){ //add a break
            $breakEnd = carryOverAdder_Minutes($timeStart, intval($addBreakVal*60));
            $conn->query("INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType) VALUES('$timeStart', '$breakEnd', $imm, 'Administrative Break', 'break')");
        }
        if($timeFin == '0000-00-00 00:00:00' || $timeFin == ':00' || $_POST['is_open']){
            $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd='0000-00-00 00:00:00', status='$status' WHERE indexIM = $imm";
        } else {
            $sql = "UPDATE $logTable SET time= DATE_SUB('$timeStart', INTERVAL timeToUTC HOUR), timeEnd=DATE_SUB('$timeFin', INTERVAL timeToUTC HOUR), status='$status' WHERE indexIM = $imm";
        }
        if($conn->query($sql)){
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>';
        } else {
            echo mysqli_error($conn);
        }
    } elseif(isset($_POST['addTimestamp'])){
        $creatUser = intval($_POST['addTimestamp']);
        $timeStart = str_replace('T', ' ',$_POST['timesFrom']) .':00';
        $timeFin = str_replace('T', ' ',$_POST['timesTo']) .':00';
        $status = intval($_POST['newActivity']);
        $timeToUTC = intval($_POST['creatTimeZone']);
        $newBreakVal = floatval($_POST['newBreakValues']);
        if($_POST['is_open']){
            $timeFin = '0000-00-00 00:00:00';
        } else {
            if($timeFin != '0000-00-00 00:00:00' && $timeFin != ':00'){ $timeFin = carryOverAdder_Hours($timeFin, ($timeToUTC * -1)); } else {$timeFin = '0000-00-00 00:00:00';}
        }
        $timeStart = carryOverAdder_Hours($timeStart, $timeToUTC * -1); //UTC
        $sql = "INSERT INTO $logTable (time, timeEnd, userID, status, timeToUTC) VALUES('$timeStart', '$timeFin', $creatUser, '$status', '$timeToUTC');";
        $conn->query($sql);
        $insertID = mysqli_insert_id($conn);
        //create break for new timestamp
        if($newBreakVal != 0){
            $timeStart = carryOverAdder_Hours($timeStart, 4);
            $timeFin = carryOverAdder_Minutes($timeStart, intval($newBreakVal*60));
            $conn->query("INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$timeStart', '$timeFin', $insertID, 'Newly created Timestamp break', 'break')");
        }
        if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
    } elseif(isset($_POST['add_multiple'])){
        $filterID = intval($_POST['add_multiple']);
        if(test_Date($_POST['add_multiple_start'].' 08:00:00') && test_Date($_POST['add_multiple_end'].' 08:00:00')){
            $status = intval($_POST['add_multiple_status']);
            $i = $_POST['add_multiple_start'].' 08:00:00';
            $days = (timeDiff_Hours($i, $_POST['add_multiple_end'].' 08:00:00')/24) + 1; //days
            for($j = 0; $j < $days; $j++){
                //get the expected Hours for currenct day (read the latest interval which matches criteria)
                $result = $conn->query("SELECT * FROM $intervalTable WHERE userID = $filterID AND DATE(startDate) <= DATE('$i') ORDER BY startDate DESC");
                if ($result && ($row = $result->fetch_assoc())) {
                    $expected = isHoliday($i) ? 0 : $row[strtolower(date('D', strtotime($i)))];
                    if($expected != 0){
                        $i2 = carryOverAdder_Minutes($i, intval($expected * 60));
                        $sql = "INSERT INTO $logTable (time, timeEnd, userID, timeToUTC, status) VALUES('$i', '$i2', $filterID, '0', '$status')";
                        $conn->query($sql);
                    }
                    $i = carryOverAdder_Hours($i, 24);
                }
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_TIMES_INVALID'].'</div>';
        }
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }
    }
    if(!empty($_POST['editing_save'])){
        //TODO: maybe if the user edits the date and there is another, more fitting timestamp (talking about date) we should change timestampIDs. maybe with a checkbox 'move if available' ?

        $accept = true;
        $x = $_POST['editing_save'];
        $result = $conn->query("SELECT $logTable.timeToUTC FROM $logTable, $projectBookingTable WHERE $projectBookingTable.id = $x AND $projectBookingTable.timestampID = $logTable.indexIM");
        if(!$result || $result->num_rows < 1){
            $accept = false;
            echo mysqli_error($conn);
        }
        $row = $result->fetch_assoc();
        $toUtc = $row['timeToUTC'] * -1;
        if(!test_Date($_POST["editing_time_from_".$x].':00')  || !test_Date($_POST["editing_time_to_".$x].':00')){
            $accept = false;
        }
        $new_projectID = 'NULL';
        if(!empty($_POST["editing_projectID_".$x])){
            $new_projectID = $_POST["editing_projectID_".$x];
        } elseif(isset($_POST["editing_projectID_".$x])) { //breaks dont have this set
            echo 'No ID found';
            $accept = false;
        }

        $new_A = carryOverAdder_Hours($_POST["editing_time_from_".$x].':00', $toUtc);
        $new_B = carryOverAdder_Hours($_POST["editing_time_to_".$x].':00', $toUtc);

        $chargedTimeStart= '0000-00-00 00:00:00';
        $chargedTimeFin = '0000-00-00 00:00:00';
        if(isset($_POST['editing_chargedtime_from_'.$x]) && $_POST['editing_chargedtime_from_'.$x] != '0000-00-00 00:00'){
            $chargedTimeStart = carryOverAdder_Hours($_POST['editing_chargedtime_from_'.$x].':00', $toUtc);
        }
        if(isset($_POST['editing_chargedtime_to_'.$x]) && $_POST['editing_chargedtime_to_'.$x] != '0000-00-00 00:00'){
            $chargedTimeFin = carryOverAdder_Hours($_POST['editing_chargedtime_to_'.$x].':00', $toUtc);
        }

        $new_text = test_input($_POST['editing_infoText_'.$x]);
        if(!$new_text) $accept = false;

        $new_charged = 'FALSE';
        if(isset($_POST['editing_charge']) || isset($_POST['editing_nocharge'])){
            $new_charged = 'TRUE';
        }
        if($accept){
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
            if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_SAVE'].'</div>'; }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
        }
    } elseif(isset($_POST["add"]) && !empty($_POST['user']) && !empty($_POST['add_date']) && isset($_POST['start']) && isset($_POST['end']) && !empty(trim($_POST['infoText']))){
        $accept = true;
        $filterUserID = intval($_POST['user']);

        if(!test_Date(trim($_POST['add_date']).' 08:00:00')){
            $accept = false;
            echo $_POST['add_date'];
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>1: '.$lang['ERROR_TIMES_INVALID'].'.</div>';
        }
        if($accept){
            $result = $conn->query("SELECT * FROM $logTable WHERE userID = $filterUserID AND DATE(time) = DATE(DATE_SUB('".$_POST['add_date']." ".$_POST['start']."', INTERVAL timeToUTC HOUR)) AND status = '0'");
            if(!$result || $result->num_rows < 1){
                $accept = false;
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_TIMESTAMP'].'</div>';
            } else {
                $row = $result->fetch_assoc();
                $indexIM = $row['indexIM'];
                $timeToUTC = $row['timeToUTC'];
            }
        }
        if($accept){
            $startDate = $_POST['add_date']." ".$_POST['start'];
            $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);

            $endDate = $_POST['add_date']." ".$_POST['end'];
            $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);

            $insertInfoText = test_input($_POST['infoText']);
            $insertInternInfoText = test_input($_POST['internInfoText']);

            if(timeDiff_Hours($startDate, $endDate) < 0){
                $endDate = carryOverAdder_Hours($endDate, 24);
            }
            if(timeDiff_Hours($startDate, $endDate) < 0 ||  timeDiff_Hours($startDate, $endDate) > 12){
                $accept = false;
            }
        }
        if($accept){
            if(isset($_POST['addBreak'])){ //checkbox
                $sql = "INSERT INTO $projectBookingTable (start, end, timestampID, infoText, bookingType) VALUES('$startDate', '$endDate', $indexIM, '$insertInfoText', 'break')";
                $conn->query($sql);
                $duration = timeDiff_Hours($startDate, $endDate);
                $sql= "UPDATE $logTable SET breakCredit = (breakCredit + $duration) WHERE indexIm = $indexIM";
                if($conn->query($sql)){
                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                } else {
                    echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
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
                        if($conn->error){ echo $conn->error; } else { echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>'; }
                        $insertInfoText = $insertInternInfoText = '';
                    } else {
                        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_SELECTION'].'</div>';
                }
            }
        }
    } elseif(isset($_POST['add'])) {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
    } elseif(isset($_POST['saveCharged'])){
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
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }
    }
} //endif post
?>

<div class="page-content-fixed-150">
<ul class="nav nav-tabs">
  <li <?php if($activeTab == 'home'){echo 'class="active"';}?>><a data-toggle="tab" href="#home"><?php echo $lang['PROJECTS']; ?></a></li>
  <?php foreach($filterings['users'] as $u){
    $active = '';
    if($u == $activeTab) $active = 'class="active"';
    $result_fc = mysqli_query($conn, "SELECT firstname FROM UserData WHERE id = $u");
    echo '<li '.$active.' ><a data-toggle="tab" href="#'.$u.'">'.$result_fc->fetch_assoc()['firstname'].'</a></li>';
  }
  ?>
</ul>

<div class="tab-content">
  <!-- ############################### Projects ################################### -->
  <div id="home" class="tab-pane fade <?php if($activeTab == 'home'){echo 'active in';}?>">
  <div class="page-header"><h3><?php echo $lang['PROJECTS']; ?><div class="page-header-button-group">
    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#addProjectBookings" title="<?php echo $lang['BOOKINGS'] .' '.$lang['ADD']; ?>"><i class="fa fa-plus"></i></button>
    <button type='submit' class="btn btn-default" name='saveCharged' form="project_table" title="<?php echo $lang['SAVE']; ?>"><i class="fa fa-floppy-o"></i></button>
    <button type="submit" class="btn btn-default" name="csvDownload" form="csvForm" title="CSV Download"><i class="fa fa-download"></i> CSV</button>
    <form action="../project/pdfDownload" method="POST" target='_blank' style="display:inline-block">
      <?php //quess who needs queries.
      $companyQuery = $clientQuery = $projectQuery = $userQuery = $chargedQuery = $breakQuery = $driveQuery = "";
      if($filterings['company']){$companyQuery = "AND companyData.id = ".$filterings['company']; }
      if($filterings['client']){$clientQuery = "AND clientData.id = ".$filterings['client']; }
      if($filterings['project']){$projectQuery = "AND projectData.id = ".$filterings['project']; }
      if($filterings['users']){$userQuery = "AND UserData.id IN (".implode(', ', $filterings['users']).")"; }
      if($filterings['bookings'][0] == '2'){$chargedQuery = "AND $projectBookingTable.booked = 'TRUE' "; } elseif($filterings['bookings'][0] == '1'){$chargedQuery= " AND $projectBookingTable.booked = 'FALSE' "; }
      if(!$filterings['bookings'][1]){$breakQuery = "AND $projectBookingTable.bookingType != 'break' "; } //projectID == NULL
      if(!$filterings['bookings'][2]){$driveQuery = "AND $projectBookingTable.bookingType != 'drive' "; }
      ?>
      <input type="hidden" name="filterQuery" value="<?php echo "WHERE DATE_ADD(projectBookingData.start, INTERVAL logs.timeToUTC HOUR) >= '".$filterings['date'][0]."'
      AND DATE(DATE_ADD($projectBookingTable.end, INTERVAL $logTable.timeToUTC HOUR)) <= DATE('".$filterings['date'][1]."')
      AND (($projectBookingTable.projectID IS NULL $breakQuery $userQuery) OR ( 1 $userQuery $chargedQuery $companyQuery $clientQuery $projectQuery $driveQuery $breakQuery))"; ?>" />
      <div class="dropdown">
        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" title="PDF Download"><i class="fa fa-download"></i> PDF</button>
        <ul class="dropdown-menu">
            <li><button type="submit" name="templateID" value="-1" class="btn btn-empty">Detallierte <?php echo $lang['OVERVIEW']; //5ac72a2d8a093 ?></button></li>
            <li><button type="submit" name="templateID" value="-2" class="btn btn-empty"><?php echo $lang['OVERVIEW']; //5ac72a2d8a093 ?></button></li>
			<li><button type="submit" name="templateID" value="-3" class="btn btn-empty">Aufgerundete <?php echo $lang['OVERVIEW']; //5afa777be1d4e ?></button></li>
        </ul>
      </div>
    </form>
  </div></h3></div>

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
  WHERE DATE_ADD(projectBookingData.start, INTERVAL logs.timeToUTC HOUR) >= DATE('".$filterings['date'][0]."')
  AND DATE(DATE_ADD($projectBookingTable.end, INTERVAL $logTable.timeToUTC HOUR)) <= DATE('".$filterings['date'][1]."')
  AND (($projectBookingTable.projectID IS NULL $breakQuery $userQuery) OR ( 1 $userQuery $chargedQuery $companyQuery $clientQuery $projectQuery $driveQuery $breakQuery))
  ORDER BY $projectBookingTable.start ASC";
  $result = $conn->query($sql);
  if(!$filterings['users'] && (!$result || $result->num_rows <= 0)){
    echo '<script>document.getElementById("set_filter_search").click();</script>';
  }
  ?>
  <form id="project_table" method="post">
    <table id="projectTable" class="table table-hover table-condensed">
      <thead>
        <th><?php echo $conn->error ; ?>Sym.</th>
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
      </thead>
      <tbody>
        <?php
        $csv = $lang['CLIENT'].';'.$lang['PROJECT'].';Info;'.$lang['DATE'].' - '. $lang['FROM'].';'. $lang['DATE'].' - '. $lang['TO'].';'.
        $lang['TIMES'].' - '. $lang['FROM'].';'.$lang['TIMES'].' - '. $lang['TO'].';'.$lang['SUM'].' (min)'.';'.$lang['HOURS_CREDIT'].';Person;'.$lang['HOURLY_RATE'].';'.
        $lang['ADDITIONAL_FIELDS'].';'.$lang['EXPENSES']."\n";

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

          $csv .= iconv('UTF-8','windows-1252',$row['clientName']) .';';
          $csv .= iconv('UTF-8','windows-1252',$row['projectName']) .';';
          $csv .= iconv('UTF-8','windows-1252', str_replace(array("\r", "\n",";"), ' ', $row['infoText'])) .';';
          $csv .= substr($A,0,10) .';';
          $csv .= substr($B,0,10) .';';
          $csv .= substr($A,11,6) .';';
          $csv .= substr($B,11,6) .';';
          $csv .= number_format((timeDiff_Hours($row['start'], $row['end']))*60, 0, '.', '') .';';
          $csv .= $row['hours'] .';';
          $csv .= $row['firstname']." ".$row['lastname'] .';';
          $csv .= ' '.$row['hourlyPrice'].' ';

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
          $csv .= $csv_optionalinfo .';';
          if($row['exp_unit'] > 0){ $expensesinfo .= $lang['QUANTITY'].': '.$row['exp_unit'].'<br>'; $csv_expensesinfo .= "{$lang['QUANTITY']}: {$row['exp_unit']},"; }
          if($row['exp_price'] > 0){ $expensesinfo .= $lang['PRICE_STK'].': '.$row['exp_price'].'<br>'; $csv_expensesinfo .= "{$lang['PRICE_STK']}: {$row['exp_price']}"; }
          if($row['exp_info']){ $expensesinfo .= $lang['DESCRIPTION'].': '.$row['exp_info'].'<br>'; $csv_expensesinfo .= ", {$lang['DESCRIPTION']}: {$row['exp_info']}"; }
          $csv .= $csv_expensesinfo .';';
          echo "<td>";
          if($row['booked'] == 'FALSE'){ echo '<button type="button" onclick="appendModal_proj('.$x.', '.$userID.')" class="btn btn-default"><i class="fa fa-pencil"></i></button>'; }
          if($row['bookingType'] != 'break'){ echo "<a tabindex='0' role='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Stundenkonto - Stundenrate - Projektstatus' data-content='$detailInfo' data-placement='left'><i class='fa fa-info-circle'></i></a>";}
          if(!empty($interninfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='Intern' data-content='$interninfo' data-placement='left'><i class='fa fa-question-circle-o'></i></a>"; }
          if(!empty($optionalinfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['ADDITIONAL_FIELDS']."' data-content='$optionalinfo' data-placement='left'><i class='fa fa-question-circle'></i></a>"; }
          if(!empty($expensesinfo)){ echo "<a type='button' class='btn btn-default' data-toggle='popover' data-trigger='hover' title='".$lang['EXPENSES']."' data-content='$expensesinfo' data-placement='left'><i class='fa fa-plus'></i></a>"; }
          echo "<button type='submit' class='btn btn-default' value='$x' name='bk_remove'><i class='fa fa-trash-o'></i></button>";
          echo '</td>';
          echo "</tr>";

          $csv .= "\n";
          $sum_min += timeDiff_Hours($row['start'], $row['end']);
        } //end while fetch_assoc
        echo "<tr>";
        echo '<td style="font-weight:bold">'.$lang['SUM'].'</td> <td></td> <td></td> <td></td> <td></td> <td></td>';
        echo "<td>".number_format($sum_min*60, 2, '.', '')."</td> <td></td> <td></td>";
        echo "</tr>";
        ?>
      </tbody>
    </table>
  </form>
  <form id="csvForm" method="POST" target="_blank" action="../project/csvDownload"><input type="hidden" name='csv' value="<?php echo rawurlencode($csv); ?>" /></form>
</div>

<!-- ############################### UserData ################################### -->

<?php foreach($filterings['users'] as $x):
if($activeTab == $x) {echo "<div id=\"$x\" class=\"tab-pane fade active in\">"; } else { echo "<div id='$x' class='tab-pane fade'>"; }
?>
<div class="page-header"><h3><?php echo $lang['TIMESTAMPS']; ?>
<div class="page-header-button-group">
<a class="btn btn-default" data-toggle="modal" data-target=".addInterval-<?php echo $x; ?>" title="<?php echo $lang['ADD']; ?>"><i class="fa fa-plus"></i></a>
</h3></div>
  <form method="POST">
    <input type="hidden" name="setActiveTab" value="<?php echo $x; ?>" />
    <table class="table table-hover table-condensed">
      <thead>
        <th><?php echo $lang['WEEKLY_DAY']; ?></th>
        <th style="min-width:90px"><?php echo $lang['DATE']; ?></th>
        <th><?php echo $lang['BEGIN']; ?></th>
        <th><?php echo $lang['BREAK']; ?></th>
        <th><?php echo $lang['END']; ?></th>
        <th><?php echo $lang['ACTIVITY']; ?></th>
        <th><?php echo $lang['SHOULD_TIME']; ?></th>
        <th><?php echo $lang['IS_TIME']; ?></th>
        <th><?php echo $lang['SALDO_DAY']; ?></th>
        <th><?php echo $lang['SALDO_MONTH']; ?></th>
        <th>Saldo</th>
        <th><?php echo $lang['EVALUATION']; ?></th>
        <th style="min-width:110px">Option</th>
      </thead>
      <tbody>
        <?php
        $calculator = new Interval_Calculator($x, $filterings['date'][0], $filterings['date'][1]);
        $bookingStmt = $conn->prepare("SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
        LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id) LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id) WHERE timestampID = ? ORDER BY end ASC");
        $bookingStmt->bind_param("i", $calcIndexIM);

        echo "<tr style='color:#626262;'>";
        echo "<td colspan='10'><strong>Vorheriges Saldo: </strong></td>";
        echo "<td>".displayAsHoursMins($calculator->prev_saldo)."</td><td></td><td></td></tr>";
        $lunchbreakSUM = $expectedHoursSUM = $absolvedHoursSUM = $saldo_month = 0;
        for($i = 0; $i < $calculator->days; $i++){
          $calcIndexIM = $calculator->indecesIM[$i];
          if($filterings['logs'][0] && $calculator->activity[$i] != $filterings['logs'][0]) continue;
          if($filterings['logs'][1] == 'checked' && $calculator->shouldTime[$i] == 0 && $calculator->absolvedTime[$i] == 0) continue;
            $tinyEndTime = '-';

            $A = $calculator->start[$i];
            $B = $calculator->end[$i];
            if($A){ $A = carryOverAdder_Hours($calculator->start[$i], $calculator->timeToUTC[$i]);}
            if($B && $B != '0000-00-00 00:00:00') $B = carryOverAdder_Hours($calculator->end[$i], $calculator->timeToUTC[$i]);

            $saldo_month += $calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i];
            $theSaldo = round($calculator->absolvedTime[$i] - $calculator->shouldTime[$i] - $calculator->lunchTime[$i], 2);

            $saldoStyle = 'style=color:#6fcf2c;'; //green
            if($theSaldo < 0) $saldoStyle = 'style=color:#fc8542;'; //red

            $neutralStyle = '';
            if($calculator->shouldTime[$i] == 0 && $calculator->absolvedTime[$i] == 0) $neutralStyle = "style=color:#c7c6c6;";

            $booking_content =  $rowClass = '';
            if($calcIndexIM){
              $booking_content = '<tr><td colspan="13" style="padding:0; border:0;">';
              $bookingStmt->execute();
              $result = $bookingStmt->get_result();
              if($result->num_rows > 0){$rowClass = 'class="clicker"';}
              while($row = $result->fetch_assoc()){
                $Ap = substr(carryOverAdder_Hours($row['start'], $calculator->timeToUTC[$i]), 11, 5);
                $Bp = substr(carryOverAdder_Hours($row['end'], $calculator->timeToUTC[$i]), 11, 5);
                $booking_content .= '<div class="row" style="display:none;color:#797979;">';
                $booking_content .= '<div class="col-xs-1"></div>';
                $booking_content .= '<div class="col-sm-1">'.$row['name'].'</div>';
                $booking_content .= '<div class="col-sm-1">'.$row['projectName'].'</div>';
                $booking_content .= '<div class="col-sm-1">'.$Ap.'</div>';
                $booking_content .= '<div class="col-sm-1">'.$Bp.'</div>';
                $booking_content .= '<div class="col-sm-5">'.$row['infoText'].'</div>';
                $booking_content .= '<div class="col-sm-2"><button type="submit" class="btn btn-default" value="'.$row['bookingTableID'].'" name="bk_remove"><i class="fa fa-trash-o"></i></button>';
                if($row['booked'] == 'FALSE'){
                  $booking_content .= '<button type="button" onclick="appendModal_proj('.$row['bookingTableID'].', '.$x.');" class="btn btn-default" ><i class="fa fa-pencil"></i></button>';
                }
                $booking_content .= '</div></div>';
              }
              $booking_content .= '</td></tr>';
            }

            echo "<tr $rowClass $neutralStyle>";
            echo '<td>' . $lang['WEEKDAY_TOSTRING'][$calculator->dayOfWeek[$i]] . '</td>';
            echo '<td>' . $calculator->date[$i] . '</td>';
            echo '<td>' . substr($A,11,5) . '</td>';
            echo "<td><small>" . displayAsHoursMins($calculator->lunchTime[$i]) . "</small></td>";
            echo '<td>' . substr($B,11,5) . '</td>';
            echo '<td>' . $lang['ACTIVITY_TOSTRING'][$calculator->activity[$i]]. '</td>';
            echo '<td>' . displayAsHoursMins($calculator->shouldTime[$i]) . '</td>';
            echo '<td>' . displayAsHoursMins($calculator->absolvedTime[$i] - $calculator->lunchTime[$i]) . '</td>';
            echo "<td $saldoStyle>" . displayAsHoursMins($theSaldo) . '</td>';
            echo '<td>' . displayAsHoursMins($saldo_month) . '</td>';
            echo '<td>' . displayAsHoursMins($saldo_month + $calculator->prev_saldo) . '</td>';
            echo '<td>'.$lang['EMOJI_TOSTRING'][$calculator->feeling[$i]].'</td>';
            echo '<td>';
            if(strtotime($calculator->date[$i].' 23:59:59') >= strtotime($calculator->beginDate)){
              echo '<button type="button" class="btn btn-default" title="'.$lang['EDIT'].'" onclick="appendModal_time('.$calcIndexIM.', '.$i.', \''.$calculator->date[$i].'\', '.$x.')"><i class="fa fa-pencil"></i></button> ';
            }
            if($calculator->indecesIM[$i] != 0){ echo '<button type="submit" class="btn btn-default" title="'.$lang['DELETE'].'" name="ts_remove" value="'.$calcIndexIM.'"><i class="fa fa-trash-o"></i></button>';}
            echo '</td>';
            echo "</tr>";

            echo $booking_content;

            $lunchbreakSUM += $calculator->lunchTime[$i];
            $expectedHoursSUM += $calculator->shouldTime[$i];
            $absolvedHoursSUM += $calculator->absolvedTime[$i] - $calculator->lunchTime[$i];

          if(isset($calculator->endOfMonth[$calculator->date[$i]])){
            //overTimeLump
            if($calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'] > 0){
              $saldo_month -= $calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'];
              echo "<tr>";
              echo "<td colspan='9'>".$lang['OVERTIME_ALLOWANCE'].": </td>";
              echo "<td style='color:#fc8542;'>-" . displayAsHoursMins($calculator->endOfMonth[$calculator->date[$i]]['overTimeLump'])."</td>";
              echo '<td>'.displayAsHoursMins($saldo_month).'</td>';
              echo '<td>'.displayAsHoursMins($saldo_month + $calculator->prev_saldo).'</td>';
              echo "<td></td></tr>";
            }
            //corrections
            if($calculator->endOfMonth[$calculator->date[$i]]['correction']){
              $saldo_month += $calculator->endOfMonth[$calculator->date[$i]]['correction'];
              echo "<tr>";
              echo "<td colspan='9'>".$lang['CORRECTION'].": </td>";
              echo "<td style='color:#9222cc'>" . displayAsHoursMins($calculator->endOfMonth[$calculator->date[$i]]['correction']) . "</td>";
              echo '<td>'.displayAsHoursMins($saldo_month).'</td>';
              echo "<td>".displayAsHoursMins($saldo_month + $calculator->prev_saldo)."</td><td></td></tr>";
            }
          }
        } //endfor
        echo "<tr style='font-weight:bold;'>";
        echo "<td colspan='10'>Gesamt: </td>";
        echo '<td>'.displayAsHoursMins($calculator->saldo).'</td>';
        echo "<td></td><td></td></tr>";
        ?>
      </tbody>
    </table>
  </form>
  <!-- add intervals modal -->
  <form method="POST">
    <div class="modal fade addInterval-<?php echo $x; ?>">
      <div class="modal-dialog modal-content modal-md">
        <div class="modal-header"><h4><?php echo $lang['ADD_TIMESTAMPS']; ?></h4></div>
        <div class="modal-body">
          <div class="input-group input-daterange">
            <input type="text" class="form-control datepicker" value="" placeholder="Von" name="add_multiple_start">
            <span class="input-group-addon"> - </span>
            <input type="text" class="form-control datepicker" value="" placeholder="Bis" name="add_multiple_end">
          </div>
          <br>
          <div class="row">
            <div class="col-md-6">
              <select name="add_multiple_status" class="js-example-basic-single">
                <option value="1"><?php echo $lang['VACATION']; ?></option>
                <option value="4"><?php echo $lang['VOCATIONAL_SCHOOL']; ?></option>
                <option value="2"><?php echo $lang['SPECIAL_LEAVE']; ?></option>
                <option value="6"><?php echo $lang['COMPENSATORY_TIME']; ?></option>
              </select>
            </div>
          </div>
          <br>
          <small> *<?php echo $lang['INFO_INTERVALS_AS_EXPECTED']; ?></small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-default" type="button" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
          <button class="btn btn-warning" type="submit" name="add_multiple" value="<?php echo $x; ?>"><?php echo $lang['ADD']; ?></button>
        </div>
      </div>
    </div>
  </form>
</div>
<?php endforeach; ?>
</div>

<!-- ############################### END TABBING START MODALS ################################### -->
<div id="editingModalDiv"></div>

<!-- ADD BOOKING TO USER -->
<form method="POST">
  <div id="addProjectBookings" class="modal fade">
    <div class="modal-dialog modal-lg modal-content" role="document">
      <div class="modal-header"><h4 class="modal-title"><?php echo $lang['ADD']; ?></h4></div>
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
              echo '<option>...</option>';
              while ($result && ($row = $result->fetch_assoc())) {
                $selected = '';
                if($filterings['users'][0] == $row['id']) {
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
              <select id="addSelectProject" class="js-example-basic-single" name="project" onchange="showProjectfields(this.value);"></select>
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
        <div id="project_fields" class="row"></div>
        <br>
        <div class="row">
          <div class="col-sm-3">
            <label><?php echo $lang['DATE']; ?></label>
            <input id="date_field" type="text" class="form-control datepicker" name="add_date" placeholder="yyyy-mm-dd" maxlength="10" />
          </div>
          <div class="col-xs-6">
            <label><?php echo $lang['TIME']; ?></label>
            <div class="input-group">
              <input id="time_field" type="time" class="form-control timepicker" onkeydown='if (event.keyCode == 13) return false;' name="start" placeholder="00:00"/>
              <span class="input-group-addon"> - </span>
              <input type="time" class="form-control timepicker" onkeydown='if (event.keyCode == 13) return false;' name="end" placeholder="00:00"/>
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
$(function () {
  $('[data-toggle="popover"]').popover({html : true});
  $("#csvDown").appendTo("#csvDownPlace");
});

var existingModals = new Array();
function appendModal_time(id, index, date, userID){
  if(existingModals.indexOf(index) == -1){
    $.ajax({
    url:'ajaxQuery/AJAX_timeModal.php',
    data:{timestampID:id, index:index, date:date, userID:userID},
    type: 'get',
    success : function(resp){
      $("#editingModalDiv").append(resp);
      existingModals.push(index);
      onPageLoad();
      $('.editingModal-'+index).modal('show');
    },
    error : function(resp){}
   });
  } else {
    $('.editingModal-'+index).modal('show');
  }
  event.stopPropagation(); //event stop for tr clicker
}
var existingModals_p = new Array();
function appendModal_proj(id, user){
  if(existingModals_p.indexOf(id) == -1){
    $.ajax({
    url:'ajaxQuery/AJAX_bookingModal.php',
    data:{bookingID:id, userID:user},
    type: 'get',
    success : function(resp){
      $("#editingModalDiv").append(resp);
      existingModals_p.push(id);
      onPageLoad();
      $('.editingModal-'+id).modal('show');
    },
    error : function(resp){}
   });
  } else {
    $('.editingModal-'+id).modal('show');
  }
}

$('.clicker').click(function(){
  $(this).next('tr').find('.row').slideToggle();
});

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
      $("#date_field").val(resp.substr(0,10));
      $("#time_field").val(resp.substr(11,17));
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
  $('#projectTable').DataTable({
    order: [],
    ordering: false,
    language: {
      <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    },
    responsive: true,
    dom: 'ft',
    autoWidth: false,
    fixedHeader: {
      header: true,
      headerOffset: 50,
      zTop: 1
    },
    paging: false
  });
  setTimeout(function(){ window.dispatchEvent(new Event('resize')); $('#projectTable').trigger('column-reorder.dt'); }, 500);
});
</script>
</div>
<!-- /BODY -->
<?php include dirname(__DIR__) . '/footer.php'; ?>
