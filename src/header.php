<?php
session_start();
if(empty($_SESSION['userid'])){
  die('Please <a href="login.php">login</a> first.');
}

$userID = $_SESSION['userid'];
$timeToUTC = $_SESSION['timeToUTC'];
$this_page = basename($_SERVER['PHP_SELF']);
$setActiveLink = 'style="color:#ed9c21;"';

require "connection.php";
require "createTimestamps.php";
require 'validate.php';
require "language.php";

if($this_page != "editCustomer_detail.php"){
  unset($_SESSION['unlock']);
}

$sql = "SELECT * FROM roles WHERE userID = $userID";
$result = $conn->query($sql);
if($result && $result->num_rows > 0){
  $row = $result->fetch_assoc();
  $isCoreAdmin = $row['isCoreAdmin'];
  $isTimeAdmin = $row['isTimeAdmin'];
  $isProjectAdmin = $row['isProjectAdmin'];
  $isReportAdmin = $row['isReportAdmin'];
  $isERPAdmin = $row['isERPAdmin'];
  $canBook = $row['canBook'];
  $canStamp = $row['canStamp'];
  $canEditTemplates = $row['canEditTemplates'];
} else {
  $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = $isReportAdmin = $isERPAdmin = FALSE;
  $canBook = $canStamp = $canEditTemplates = FALSE;
}

if($userID == 1){ //superuser
  $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = $isReportAdmin = $isERPAdmin = 'TRUE';
  $canStamp = 'TRUE';
}

$result = $conn->query("SELECT lastPswChange FROM UserData WHERE id = $userID");
$row = $result->fetch_assoc();
$lastPswChange = $row['lastPswChange'];

$result = $conn->query("SELECT enableReadyCheck FROM configurationData");
$row = $result->fetch_assoc();
$showReadyPlan = $row['enableReadyCheck'];

if($isTimeAdmin){
  $numberOfAlerts = 0;
  //requests
  $result = $conn->query("SELECT id FROM $userRequests WHERE status = '0'");
  if($result && $result->num_rows > 0){ $numberOfAlerts += $result->num_rows; }
  //forgotten checkouts
  $result = $conn->query("SELECT indexIM FROM $logTable WHERE (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60) > 22 OR TIMESTAMPDIFF(MINUTE, time, timeEnd) < 0");
  if($result && $result->num_rows > 0){ $numberOfAlerts += $result->num_rows; }
  //gemini date in logs
  $result = $conn->query("SELECT * FROM $logTable l1, $userTable WHERE l1.userID = $userTable.id AND EXISTS(SELECT * FROM $logTable l2 WHERE DATE(DATE_ADD(l1.time, INTERVAL timeToUTC  hour)) = DATE(DATE_ADD(l2.time, INTERVAL timeToUTC  hour)) AND l1.userID = l2.userID AND l1.indexIM != l2.indexIM) ORDER BY l1.time DESC");
  if($result && $result->num_rows > 0){ $numberOfAlerts += $result->num_rows; }
  //lunchbreaks
  $result = $conn->query("SELECT indexIM FROM $logTable l1 INNER JOIN $userTable ON l1.userID = $userTable.id INNER JOIN $intervalTable ON $userTable.id = $intervalTable.userID
  WHERE (status = '0' OR status ='5') AND endDate IS NULL AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, timeEND) > (pauseAfterHours * 60) AND
  !EXISTS(SELECT id FROM projectBookingData WHERE TIMESTAMPDIFF(MINUTE, start, end) >= (hoursOfRest * 60) AND timestampID = l1.indexIM)");
  if($result && $result->num_rows > 0){ $numberOfAlerts += $result->num_rows; }
}

$result = $conn->query("SELECT DISTINCT companyID FROM $companyToUserRelationshipTable WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while($result && ($row= $result->fetch_assoc())){
  $available_companies[] = $row['companyID'];
}
$result = $conn->query("SELECT DISTINCT userID FROM $companyToUserRelationshipTable WHERE companyID IN(".implode(', ', $available_companies).") OR $userID = 1");
$available_users = array('-1');
while($result && ($row = $result->fetch_assoc())){
  $available_users[] = $row['userID'];
}

$validation_output = $error_output = '';
if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['savePAS']) && !empty($_POST['password']) && !empty($_POST['passwordConfirm'])){
    if(test_input($_POST['password']) != $_POST['password']){
      die("Malicious Code Injection Detected, please do not use any HTML, SQL or Javascript specific characters.");
    }
    $password = $_POST['password'];
    $passwordConfirm = $_POST['passwordConfirm'];
    $output = '';
    if(strcmp($password, $passwordConfirm) == 0 && match_passwordpolicy($password, $output)){
      $psw = password_hash($password, PASSWORD_BCRYPT);
      $sql = "UPDATE $userTable SET psw = '$psw', lastPswChange = UTC_TIMESTAMP WHERE id = '$userID';";
      $conn->query($sql);
      $validation_output  = '<div class="alert alert-success fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><strong>Success! </strong>Password successfully changed.</div>';
    } else {
      $validation_output  = '<div class="alert alert-danger fade in"><a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      $validation_output .= "<strong>Failed! </strong>Passwords did not match or were invalid. $output".'</div>';
    }
  } elseif(isset($_POST['savePIN'])){
    if(is_numeric($_POST['pinCode']) && !empty($_POST['pinCode'])){
      $sql = "UPDATE $userTable SET terminalPin = '".$_POST['pinCode']."' WHERE id = '$userID';";
      $conn->query($sql);
      $validation_output  = '<div class="alert alert-success fade in"><a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      $validation_output .= '<strong>Success! </strong>Your Pincode was changed.</div>';
    } else {
      $validation_output  = '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      $validation_output .= '<strong>Failed: </strong>Invalid PIN.</div>';
    }
  } elseif(isset($_POST['stampIn']) || isset($_POST['stampOut'])){
    require "ckInOut.php";
    $validation_output  = '<div class="alert alert-info fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    if(isset($_POST['stampIn'])){
      checkIn($userID);
      $validation_output .= $lang['INFO_CHECKIN'].'</div>';
    } elseif(isset($_POST['stampOut'])){
      $error_output = checkOut($userID);
      $validation_output .= $lang['INFO_CHECKOUT'].'</div>';
    }
  } elseif(isset($_POST["GERMAN"])){
    $sql="UPDATE $userTable SET preferredLang='GER' WHERE id = 1";
    $conn->query($sql);
    $_SESSION['language'] = 'GER';
    $validation_output = mysqli_error($conn);
  } elseif(isset($_POST['ENGLISH'])){
    $sql="UPDATE $userTable SET preferredLang='ENG' WHERE id = 1";
    $conn->query($sql);
    $_SESSION['language'] = 'ENG';
    $validation_output = mysqli_error($conn);
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">

  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../plugins/font-awesome/css/font-awesome.min.css">

  <link href="../plugins/homeMenu/homeMenu.css" rel="stylesheet">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>

  <link rel="stylesheet" type="text/css" href="../plugins/select2/css/select2.css">
  <script src='../plugins/select2/js/select2.js'></script>

  <link rel="stylesheet" type="text/css" href="../plugins/datepicker/css/datepicker.css">
  <script src="../plugins/datepicker/js/bootstrap-datepicker.js"> </script>
  <title>Connect</title>
</head>
<script>
document.onreadystatechange = function() {
  var state = document.readyState
  if (state == 'complete') {
    document.getElementById("loader").style.display = "none";
    document.getElementById("bodyContent").style.display = "block";
  }
}
$(document).ready(function() {
  if ($(".js-example-basic-single")[0]){
    $(".js-example-basic-single").select2();
  }

  if ($('#seconds').length) { //something like a if(exists(..))
    var sec = parseInt(document.getElementById("seconds").innerHTML) + parseInt(document.getElementById("minutes").innerHTML) * 60 + parseInt(document.getElementById("hours").innerHTML) * 3600;

    function pad(val) {
      return val > 9 ? val : "0" + val;
    }
    window.setInterval(function(){
      document.getElementById("seconds").innerHTML = pad(++sec % 60);
      document.getElementById("minutes").innerHTML = pad(parseInt((sec / 60) % 60, 10));
      document.getElementById("hours").innerHTML = pad(parseInt(sec / 3600, 10));
    }, 1000);
  }
});
</script>

<body id="body_container" class="is-table-row">
  <div id="loader"></div>

  <!-- navbar -->
  <nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
      <div class="navbar-header hidden-xs">
        <a class="navbar-brand" href="home.php">Connect</a>
      </div>
      <ul class="nav navbar-nav hidden-xs">
        <li class="dropdown">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-language" ></i> <span class="caret"></span></a>
          <ul class="dropdown-menu">
            <form method=post class="navbar-form navbar-left">
              <li><button type="submit" style=background:none;border:none name="ENGLISH"><img width="30px" height="20px" src="../images/eng.png"></button> English</li>
              <li role="separator" class="divider"></li>
              <li><button type="submit" style=background:none;border:none  name="GERMAN"><img width="30px" height="20px" src="../images/ger.png"></button> Deutsch</li>
            </form>
          </ul>
        </li>
      </ul>
      <div class="navbar-right" style="margin-right:10px">
        <a class="btn navbar-btn hidden-sm hidden-md hidden-lg" data-toggle="collapse" data-target="#sidemenu"><i class="fa fa-bars"></i></a>
        <?php if($isTimeAdmin == 'TRUE' && $numberOfAlerts > 0): ?> <span class="badge hidden-xs" style="margin:0 15px 0 30px;background-color:#ed9c21;"><a href="adminTodos.php" style="color:white;" title="Your Database is in an invalid state, please fix these Errors after clicking this button. "> <?php echo $numberOfAlerts; ?> </a></span> <?php endif; ?>
        <span class="navbar-text hidden-xs"><?php echo $_SESSION['firstname']; ?></span>
        <a class="btn navbar-btn" data-toggle="collapse" href="#infoDiv_collapse"><i class="fa fa-info"></i></a>
        <a class="btn navbar-btn" data-toggle="modal" data-target="#myModal"><i class="fa fa-gears"></i></a>
        <a class="btn navbar-btn" href="logout.php" title="Logout"><i class="fa fa-sign-out"></i></a>
      </div>
    </div>
  </nav>
  <!-- /navbar -->
  <div class="collapse" id="infoDiv_collapse">
    <div class="well">
      <a href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include 'version_number.php'; echo $VERSION_TEXT; ?>
      <br>
      The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
      the Software will perform substantially in accordance with the functional specifications set forth in the documentation.
    </div>
  </div>

  <!-- modal -->
  <form method=post>
    <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="myModalLabel">Settings</h4>
          </div>
          <div class="modal-body">
            <?php echo $lang['NEW_PASSWORD']?>: <br>
            <input type="password" class="form-control" name="password" ><br>
            <?php echo $lang['NEW_PASSWORD_CONFIRM']?>: <br>
            <input type="password" class="form-control" name="passwordConfirm" ><br><br>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" name="savePAS">Save Password</button>
          </div>
          <!--
          <div class="modal-body">
            PIN-Code: <br>
            <input type="number" class="form-control" name="pinCode" > <br><br>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-info" name="savePIN">Save PIN</button>
          </div>
        -->
        </div>
      </div>
    </div>
  </form>
  <!-- /modal -->
  <?php
  $showProjectBookingLink = $cd = $diff = 0;
  $result = mysqli_query($conn, "SELECT * FROM $configTable");
  if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $cd = $row['cooldownTimer'];
    $bookingTimeBuffer = $row['bookingTimeBuffer'];
  } else {
    $cd = 0;
    $bookingTimeBuffer = 5;
  }
  //display checkin or checkout + disabled
  $query = "SELECT * FROM $logTable WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID";
  $result = mysqli_query($conn, $query);
  if ($result && $result->num_rows > 0) { //open timestamps must be closed
    $buttonVal = $lang['CHECK_OUT'];
    $checkInButton =  "<button type='submit' class='btn btn-warning' name='stampOut'>$buttonVal</button>";
    $showProjectBookingLink = TRUE;
  } else {
    $today = getCurrentTimestamp();
    $timeIsLikeToday = substr($today, 0, 10) ." %";
    $disabled = '';

    $sql = "SELECT * FROM $logTable WHERE userID = $userID AND time LIKE '$timeIsLikeToday' AND status = '0'";
    $result = mysqli_query($conn, $sql);
    if($result && $result->num_rows > 0){
      $row = $result->fetch_assoc();
      $diff = timeDiff_Hours($row['timeEnd'], $today) + 0.0003;
      if($diff > $cd/60){ //he has waited long enough to check in
        $disabled = '';
      } else {
        $disabled = 'disabled';
      }
    }
    $buttonVal = $lang['CHECK_IN'];
    $checkInButton = "<button $disabled type='submit' class='btn btn-warning' name='stampIn'>$buttonVal</button>";
  }
  ?>

  <!-- side menu -->
  <div id="sidemenu" class="affix-sidebar sidebar-nav hidden-scrollbar">
    <div class="inner">
      <div class="navbar navbar-default" role="navigation">
        <ul class="nav navbar-nav" id="sidenav01">
          <?php if($canStamp == 'TRUE'): ?>
            <li>
              <div class='container-fluid'>
                <form method='post' action="home.php"><br>
                  <?php
                  echo $checkInButton;
                  if($diff)
                  echo "&nbsp <span id='hours'>".sprintf("%02d",$diff)."</span>:<span id='minutes'>".sprintf("%02d",($diff * 60) % 60)."</span>:<span id='seconds'>".sprintf("%02d",($diff * 3600) % 60)."</span>";
                  ?>
                </form><br>
              </div>
            </li>
            <!-- User-Section: BASIC -->
            <li><a <?php if($this_page =='home.php'){echo $setActiveLink;}?> href="home.php"><i class="fa fa-home"></i> <span><?php echo $lang['OVERVIEW']; ?></span></a></li>
            <li><a <?php if($this_page =='timeCalcTable.php'){echo $setActiveLink;}?> href="timeCalcTable.php"><i class="fa fa-clock-o"></i> <span><?php echo $lang['VIEW_TIMESTAMPS']; ?></span></a></li>
            <li><a <?php if($this_page =='calendar.php'){echo $setActiveLink;}?> href="calendar.php"><i class="fa fa-calendar"></i> <span><?php echo $lang['CALENDAR']; ?></span></a></li>
            <li><a <?php if($this_page =='makeRequest.php'){echo $setActiveLink;}?> href="makeRequest.php"><i class="fa fa-calendar-plus-o"></i> <span><?php echo $lang['REQUESTS']; ?></span></a></li>
            <li><a <?php if($this_page =='travelingForm.php'){echo $setActiveLink;}?> href="travelingForm.php"><i class="fa fa-suitcase"></i> <span><?php echo $lang['TRAVEL_FORM']; ?></span></a></li>

            <?php if($showReadyPlan == 'TRUE'): ?>
              <li><a <?php if($this_page =='readyPlan.php'){echo $setActiveLink;}?> href="readyPlan.php"><i class="fa fa-user-times"></i> <?php echo $lang['READY_STATUS']; ?></a></li>
            <?php endif; ?>

            <!-- User-Section: BOOKING -->
            <?php if($canBook == 'TRUE' && $showProjectBookingLink): //a user cannot do projects if he cannot checkin m8 ?>
              <hr>
              <li><a <?php if($this_page =='userProjecting.php'){echo $setActiveLink;} ?> href="userProjecting.php"><i class="fa fa-bookmark"></i><span><?php echo $lang['BOOK_PROJECTS']; ?></span></a></li>
            <?php endif; ?>
          <?php endif; //endif(canStamp)?>

          <!-- User-Section: EDITING -->
          <?php if($canEditTemplates == 'TRUE'):?>
            <hr>
            <li><a <?php if($this_page =='templateSelect.php'){echo $setActiveLink; $this_page='nutter';} ?> href="templateSelect.php"><i class="fa fa-file-pdf-o"></i><span>Report Designer</span></a></li>
          <?php endif; ?>
        </ul>
      </div>
      <br>
      <div class="panel-group" id="sidebar-accordion">
        <!-- Section One: CORE -->
        <?php if($isCoreAdmin == 'TRUE'): ?>
          <div class="panel panel-default panel-borderless">
            <div class="panel-heading" role="tab" id="headingCore">
              <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-core"  id="adminOption_CORE">
                <?php echo $lang['ADMIN_CORE_OPTIONS']; ?><i class="fa fa-caret-down pull-right"></i>
              </a>
            </div>
            <div id="collapse-core" role="tabpanel" class="panel-collapse collapse"  aria-labelledby="headingCore">
              <div class="panel-body">
                <ul class="nav navbar-nav">
                  <li>
                    <a id="coreUserToggle" href="#" data-toggle="collapse" data-target="#toggleUsers" data-parent="#sidenav01" class="collapse in">
                      <i class="fa fa-users"></i> <span><?php echo $lang['USERS']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleUsers" style="height: 0px;">
                      <ul class="nav nav-list">
                        <li><a <?php if($this_page =='editUsers.php'){echo $setActiveLink;}?> href="editUsers.php"><?php echo $lang['EDIT_USERS']; ?></a></li>
                        <li><a <?php if($this_page =='admin_saldoview.php'){echo $setActiveLink;}?> href="admin_saldoview.php"><?php echo $lang['USERS']; ?> Saldo</a></li>
                        <li><a <?php if($this_page =='register_basic.php'){echo $setActiveLink;}?> href="register_basic.php"><?php echo $lang['REGISTER_NEW_USER']; ?></a></li>
                        <li><a <?php if($this_page =='deactivatedUsers.php'){echo $setActiveLink;}?> href="deactivatedUsers.php"><?php echo $lang['USER_INACTIVE']; ?></a></li>
                      </ul>
                    </div>
                  </li>
                  <li>
                    <a id="coreCompanyToggle" <?php if($this_page =='editCompanies.php'){echo $setActiveLink;}?> href="#" data-toggle="collapse" data-target="#toggleCompany" data-parent="#sidenav01" class="collapse in">
                      <i class="fa fa-building-o"></i> <span><?php echo $lang['COMPANIES']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleCompany" style="height: 0px;">
                      <ul class="nav nav-list">
                        <?php
                        $result = $conn->query("SELECT * FROM $companyTable");
                        while($result && ($row = $result->fetch_assoc())){
                          if(in_array($row['id'], $available_companies)){
                            echo "<li><a href='editCompanies.php?cmp=".$row['id']."'>".$row['name']."</a></li>";
                          }
                        }
                        ?>
                        <li><a <?php if($this_page =='new_Companies.php'){echo $setActiveLink;}?> href="new_Companies.php"><?php echo $lang['CREATE_NEW_COMPANY']; ?></a></li>
                      </ul>
                    </div>
                  </li>
                  <li><a <?php if($this_page =='editCustomers.php'){echo $setActiveLink;}?> href="editCustomers.php"><i class="fa fa-briefcase"></i><span><?php echo $lang['CLIENTS']; ?></span></a></li>
                  <li><a <?php if($this_page =='teamConfig.php'){echo $setActiveLink;}?> href="teamConfig.php"><i class="fa fa-share-alt"></i>Teams</a></li>
                  <li>
                    <a id="coreSettingsToggle" href="#" data-toggle="collapse" data-target="#toggleSettings" data-parent="#sidenav01" class="collapsed">
                      <i class="fa fa-gear"></i> <span><?php echo $lang['SETTINGS']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleSettings" style="height: 0px;">
                      <ul class="nav nav-list">
                        <li><a <?php if($this_page =='editHolidays.php'){echo $setActiveLink;}?> href="editHolidays.php"><span><?php echo $lang['HOLIDAYS']; ?></span></a></li>
                        <li><a <?php if($this_page =='advancedOptions.php'){echo $setActiveLink;}?> href="advancedOptions.php"><span><?php echo $lang['ADVANCED_OPTIONS']; ?></span></a></li>
                        <li><a <?php if($this_page =='passwordOptions.php'){echo $setActiveLink;}?> href="passwordOptions.php"><span><?php echo $lang['PASSWORD'].' '.$lang['OPTIONS']; ?></span></a></li>
                        <li><a <?php if($this_page =='reportOptions.php'){echo $setActiveLink;}?> href="reportOptions.php"><span> E-mail <?php echo $lang['OPTIONS']; ?> </span></a></li>
                        <li><a <?php if($this_page =='editTaxes.php'){echo $setActiveLink;}?> href="editTaxes.php"><span><?php echo $lang['TAX_RATES']; ?></span></a></li>
                        <li><a <?php if($this_page =='taskScheduler.php'){echo $setActiveLink;}?> href="taskScheduler.php"><span><?php echo $lang['TASK_SCHEDULER']; ?> </pan></a></li>
                        <li><a <?php if($this_page =='pullGitRepo.php'){echo $setActiveLink;}?> href="pullGitRepo.php"><span>Update</span></a></li>
                        <li><a <?php if($this_page =='sqlDownload.php'){echo $setActiveLink;}?> href="sqlDownload.php" target="_blank"><span> DB Backup</span> <i class="fa fa-download"></i> </a></li>
                        <li><a <?php if($this_page =='upload_database.php'){echo $setActiveLink;}?> href="upload_database.php"><span> <?php echo $lang['DB_RESTORE']; ?></span> </a></li>
                      </ul>
                    </div>
                  </li>
                  <?php if($canEditTemplates != 'TRUE'):?><li><a <?php if($this_page =='templateSelect.php'){echo $setActiveLink;}?> href="templateSelect.php"> <i class="fa fa-file-pdf-o"></i> <span>Report Designer</span> </a></li><?php endif; ?>
                </ul>
              </div>
            </div>
          </div>
          <?php
          if($this_page == "editUsers.php" || $this_page == "admin_saldoview.php" || $this_page == "register_basic.php" || $this_page == "deactivatedUsers.php"){
            echo "<script>document.getElementById('coreUserToggle').click();document.getElementById('adminOption_CORE').click();</script>";
          } elseif($this_page == "reportOptions.php" || $this_page == "editHolidays.php" || $this_page == "advancedOptions.php" || $this_page == "taskScheduler.php" || $this_page == "pullGitRepo.php" || $this_page == "passwordOptions.php" || $this_page =="editTaxes.php"){
            echo "<script>document.getElementById('coreSettingsToggle').click();document.getElementById('adminOption_CORE').click();</script>";
          } elseif($this_page == "editCompanies.php" || $this_page == "editCompanies_fields.php"){
            echo "<script>document.getElementById('coreCompanyToggle').click();document.getElementById('adminOption_CORE').click();</script>";
          } elseif($this_page == "sqlDownload.php" || $this_page == "templateSelect.php" || $this_page == "teamConfig.php" || $this_page == "upload_database.php" || $this_page == "editCustomers.php" ) {
            echo "<script>document.getElementById('adminOption_CORE').click();</script>";
          }
          ?>
        <?php endif; ?>

        <!-- Section Two: TIME -->
        <?php if($isTimeAdmin == 'TRUE'): ?>
          <div class="panel panel-default panel-borderless">
            <div class="panel-heading" role="tab" id="headingTime">
              <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-time"  id="adminOption_TIME">
                <?php echo $lang['ADMIN_TIME_OPTIONS']; ?><i class="fa fa-caret-down pull-right"></i>
              </a>
            </div>
            <div id="collapse-time" class="panel-collapse collapse" role="tabpanel"  aria-labelledby="headingTime">
              <div class="panel-body">
                <ul class="nav navbar-nav">
                  <li><a <?php if($this_page =='getTimestamps.php'){echo $setActiveLink;}?> href="getTimestamps.php"><i class="fa fa-history"></i> <span><?php echo $lang['TIMES'].' '.$lang['VIEW']; ?></span></a></li>
                  <li><a <?php if($this_page =='bookAdjustments.php'){echo $setActiveLink;}?> href="bookAdjustments.php"><i class="fa fa-plus"></i> <?php echo $lang['CORRECTION']; ?></a></li>
                  <li><a <?php if($this_page =='getTravellingExpenses.php'){echo $setActiveLink;}?> href="getTravellingExpenses.php"><i class="fa fa-plane"></i> <?php echo $lang['TRAVEL_FORM']; ?></a></li>
                  <li><a <?php if($this_page =='display_vacation.php'){echo $setActiveLink;}?> href="display_vacation.php"><i class="fa fa-suitcase"></i> <?php echo $lang['VACATION']; ?></a></li>
                  <li><a <?php if($this_page =='adminTodos.php'){echo $setActiveLink;}?> href="adminTodos.php"><i class="fa fa-exclamation-triangle"></i> <?php echo $lang['CHECKLIST']; ?></a></li>
                </ul>
              </div>
            </div>
          </div>
          <?php
          if($this_page == "getTimestamps.php" || $this_page == "monthlyReport.php" || $this_page == "adminTodos.php" || $this_page == "getTravellingExpenses.php" || $this_page == "bookAdjustments.php" || $this_page == "getTimestamps_select.php" || $this_page == 'display_vacation.php'){
            echo "<script>document.getElementById('adminOption_TIME').click();</script>";
          }
          ?>
        <?php endif; ?>

        <!-- Section Three: PROJECTS -->
        <?php if($isProjectAdmin == 'TRUE'): ?>
          <div class="panel panel-default panel-borderless">
            <div class="panel-heading" role="tab" id="headingProject">
              <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-project"  id="adminOption_PROJECT">
                <?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?><i class="fa fa-caret-down pull-right"></i>
              </a>
            </div>
            <div id="collapse-project" class="panel-collapse collapse" role="tabpanel"  aria-labelledby="headingProject">
              <div class="panel-body">
                <ul class="nav navbar-nav">
                  <li><a <?php if($this_page =='getProjects.php'){echo $setActiveLink;}?> href="getProjects.php"><i class="fa fa-history"></i>
                    <span><?php echo $lang['PROJECT_BOOKINGS']; ?></span>
                  </a></li>
                  <li><a <?php if($this_page =='editProjects.php'){echo $setActiveLink;}?> href="editProjects.php"><i class="fa fa-tags"></i>
                    <span><?php echo $lang['VIEW_PROJECTS']; ?></span>
                  </a></li>
                </ul>
              </div>
            </div>
          </div>
          <?php
          if($this_page == "getProjects.php"|| $this_page == "editProjects.php"){
            echo "<script>$('#adminOption_PROJECT').click();</script>";
          }
          ?>
        <?php endif; ?>
        <!-- Section Four: REPORTS -->
        <?php if($isReportAdmin == 'TRUE'): ?>
          <div class="panel panel-default panel-borderless">
            <div class="panel-heading" role="tab" id="headingReport">
              <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-report"  id="adminOption_REPORT">
                <?php echo $lang['REPORTS']; ?><i class="fa fa-caret-down pull-right"></i>
              </a>
            </div>
            <div id="collapse-report" class="panel-collapse collapse" role="tabpanel"  aria-labelledby="headingReport">
              <div class="panel-body">
                <ul class="nav navbar-nav">
                  <li><a target="_blank" href="sendMailReport.php"><i class="fa fa-envelope-open-o"></i><span> Send E-Mails </span></a></li>
                  <li><a <?php if($this_page =='report_productivity.php'){echo $setActiveLink;}?> href="report_productivity.php"><i class="fa fa-bar-chart"></i><span><?php echo $lang['PRODUCTIVITY']; ?></span></a></li>
                </ul>
              </div>
            </div>
          </div>
          <?php
          if($this_page == "report_productivity.php"){
            echo "<script>$('#adminOption_REPORT').click();</script>";
          }

          endif;
          ?>
          <!-- Section Five: ERP -->
          <?php
          if($isERPAdmin == 'TRUE'):
          ?>
          <div class="panel panel-default panel-borderless">
            <div class="panel-heading" role="tab" id="headingERP">
              <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-erp"  id="adminOption_ERP">
                ERP<i class="fa fa-caret-down pull-right"></i>
              </a>
            </div>
            <div id="collapse-erp" class="panel-collapse collapse" role="tabpanel"  aria-labelledby="headingERP">
              <div class="panel-body">
                <ul class="nav navbar-nav">
                  <li><a <?php if($this_page =='offer_proposal_process.php'){echo $setActiveLink;}?> href="offer_proposal_process.php"><i class="fa fa-file-o"></i><span><?php echo $lang['NEW_PROCESS']; ?></span></a></li>
                  <li><a <?php if($this_page =='product_articles.php'){echo $setActiveLink;}?> href="product_articles.php"><i class="fa fa-shopping-basket"></i><span><?php echo $lang['ARTICLE']; ?></span></a></li>
                  <li><a href="offer_proposals.php"><i class="fa fa-file-text-o"></i><span><?php echo $lang['OFFERS']; ?></span></a></li>
                  <li><a href="offer_proposals.php?trans=1"><i class="fa fa-file-text-o"></i><span><?php echo $lang['ORDER_CONFIRMATIONS']; ?></span></a></li>
                  <li><a href="offer_proposals.php?trans=2"><i class="fa fa-file-text-o"></i><span><?php echo $lang['RECEIPTS']; ?></span></a></li>
                  </ul>
              </div>
            </div>
          </div>
          <br>
          <?php
          if($this_page == "offer_proposals.php" || $this_page == "offer_proposal_edit.php" || $this_page == "product_articles.php" ){
            echo "<script>$('#adminOption_ERP').click();</script>";
          }
          ?>
        <?php endif; ?>
        <br>
      </div> <!-- /accordions -->
      <br><br><br>
    </div>
  </div>
  <!-- /side menu -->

  <?php
  $result = $conn->query("SELECT * FROM $policyTable");
  $row = $result->fetch_assoc();

  if($row['expiration'] == 'TRUE'){ //can a password even expire?
    $pswDate = date('Y-m-d', strtotime("+".$row['expirationDuration']." months", strtotime($lastPswChange)));
    if(timeDiff_Hours($pswDate, getCurrentTimestamp()) > 0){ //has my password actually expired?
      if($row['expirationType'] == 'FORCE'){ //now, either we force a passwordChange
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Your Password has expired. </strong> Please change it by clicking on the gears in the top right corner.';
        echo '</div>';
        echo "</div></body></html>";
        die();
      } elseif($row['expirationType'] == 'ALERT'){ //or we just alert
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Your Password has expired. </strong> Please change it by clicking on the gears in the top right corner.';
        echo '</div>';
      }
    }
  }
  ?>

  <div id="bodyContent" style="display:none;" >
    <div class="affix-content">
      <div class="container-fluid">
        <span><?php echo $validation_output; ?></span>
        <span><?php echo $error_output; ?></span>
