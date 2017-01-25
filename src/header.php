<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../plugins/font-awesome/css/font-awesome.min.css">
  <link href="../plugins/homeMenu/homeMenu.css" rel="stylesheet">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>

  <link rel="stylesheet" type="text/css" href="../plugins/select2/css/select2.min.css">
  <script src='../plugins/select2/js/select2.js'></script>

  <title>T-Time</title>
</head>

<script>
$(document).ready(function() {
  $(".js-example-basic-single").select2();
});

jQuery.fn.preventDoubleSubmission = function() {
  $(this).on('submit',function(e){
    var $form = $(this);
    if ($form.data('submitted') === true) {
      // Previously submitted - don't submit again
      e.preventDefault();
    } else {
      // Mark it so that the next submit can be ignored
      $form.data('submitted', true);
    }
  });
  // Keep chainability
  return this;
};

$('form').preventDoubleSubmission();
</script>

<body>
  <?php
  session_start();
  if(empty($_SESSION['userid'])){
    die('Please <a href="login.php">login</a> first.');
  }
  $userID = $_SESSION['userid'];
  $timeToUTC = $_SESSION['timeToUTC'];

  $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = FALSE;
  $canBook = $canStamp = FALSE;
  $this_page = basename($_SERVER['PHP_SELF']);
  $setActiveLink = 'style="color:#ed9c21;"';

  require "connection.php";
  require "createTimestamps.php";
  //language require is below

  $sql = "SELECT * FROM $roleTable WHERE userID = $userID";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){
    $row = $result->fetch_assoc();
    $isCoreAdmin = $row['isCoreAdmin'];
    $isTimeAdmin = $row['isTimeAdmin'];
    $isProjectAdmin = $row['isProjectAdmin'];

    $canBook = $row['canBook'];
    $canStamp = $row['canStamp'];
    $canEditTemplates = $row['canEditTemplates'];
  }

  $result = $conn->query("SELECT lastPswChange FROM $userTable WHERE id = $userID");
  $row = $result->fetch_assoc();
  $lastPswChange = $row['lastPswChange'];

  $result = $conn->query("SELECT enableReadyCheck FROM $configTable");
  $row = $result->fetch_assoc();
  $showReadyPlan = $row['enableReadyCheck'];

  if($isTimeAdmin){
    $numberOfAlerts = 0;

    $result = $conn->query("SELECT COUNT(*) FROM $userRequests WHERE status = '0'");
    if($result && ($row = $result->fetch_assoc())){ $numberOfAlerts += reset($row); }

    $result = $conn->query("SELECT COUNT(*) FROM $logTable INNER JOIN $userTable ON $logTable.userID = $userTable.id WHERE timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(HOUR, time, timeEnd) > pauseAfterHours AND breakCredit < hoursOfRest AND status = '0'");
    if($result && ($row = $result->fetch_assoc())){ $numberOfAlerts += reset($row); }

    $result = $conn->query("SELECT COUNT(*) FROM $logTable INNER JOIN $userTable ON $userTable.id = $logTable.userID WHERE (TIMESTAMPDIFF(HOUR, time, timeEnd) - breakCredit) > 12 OR (TIMESTAMPDIFF(HOUR, time, timeEnd) - breakCredit) < 0");
    if($result && ($row = $result->fetch_assoc())){ $numberOfAlerts += reset($row); }

    $result = $conn->query("SELECT COUNT(*) FROM $logTable l1, $userTable WHERE l1.userID = $userTable.id AND EXISTS(SELECT * FROM $logTable l2 WHERE DATE(l1.time) = DATE(l2.time) AND l1.userID = l2.userID AND l1.indexIM != l2.indexIM) ORDER BY l1.time DESC");
    if($result && ($row = $result->fetch_assoc())){ $numberOfAlerts += reset($row); }

    $result = $conn->query("SELECT COUNT(*) FROM $logTable,$negative_logTable, $userTable WHERE $logTable.userID = $userTable.id AND $logTable.userID = $negative_logTable.userID AND 0 = datediff($logTable.time, $negative_logTable.time)");
    if($result && ($row = $result->fetch_assoc())){ $numberOfAlerts += reset($row); }
  }

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['savePAS']) && !empty($_POST['password']) && !empty($_POST['passwordConfirm'])) {
      $password = $_POST['password'];
      $passwordConfirm = $_POST['passwordConfirm'];
      $output = '';
      if (strcmp($password, $passwordConfirm) == 0 && match_passwordpolicy($password, $output)) {
        $psw = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE $userTable SET psw = '$psw', lastPswChange = UTC_TIMESTAMP WHERE id = '$userID';";
        $conn->query($sql);
        echo '<div class="alert alert-success fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Success! </strong>Password successfully changed.';
        echo '</div>';
      } else {
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo "<strong>Failed! </strong>Passwords did not match or were invalid. $output";
        echo '</div>';
      }
    }

    if (isset($_POST['savePIN'])) {
      if(is_numeric($_POST['pinCode']) && !empty($_POST['pinCode'])){
        $sql = "UPDATE $userTable SET terminalPin = '".$_POST['pinCode']."' WHERE id = '$userID';";
        $conn->query($sql);
        echo '<div class="alert alert-success fade in">';
        echo '<a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Success! </strong>Your Pincode was changed.';
        echo '</div>';
      } else {
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Failed: </strong>Invalid PIN.';
        echo '</div>';
      }
    }

    if(isset($_POST['stampIn']) || isset($_POST['stampOut'])){
      require "ckInOut.php";
      echo '<div class="alert alert-info fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo "<strong>Checkin/out recognized: </strong> Refresh in a few minutes.";
      echo '</div>';

      if (isset($_POST['stampIn'])) {
        checkIn($userID);
      } elseif (isset($_POST['stampOut'])) {
        checkOut($userID);
      }
    }

    if(isset($_POST["GERMAN"])){
      $sql="UPDATE $userTable SET preferredLang='GER' WHERE id = 1";
      $conn->query($sql);
      $_SESSION['language'] = 'GER';
    } elseif(isset($_POST['ENGLISH'])){
      $sql="UPDATE $userTable SET preferredLang='ENG' WHERE id = 1";
      $conn->query($sql);
      $_SESSION['language'] = 'ENG';
    }
    echo mysqli_error($conn);
  }

  require "language.php";

  if ($_SERVER["REQUEST_METHOD"] == "POST"){
    if ((time() - $_SESSION['posttimer']) <= 2){
      unset($_POST);
    } else {
      $_SESSION['posttimer'] = time();
    }
  }
  ?>

  <!-- navbar -->
  <nav class="navbar navbar-default navbar-fixed-top hidden-xs">
    <div class="container-fluid">
      <div class="navbar-header">
        <a class="navbar-brand" href="home.php">T-Time</a>
      </div>

      <div>
        <ul class="nav navbar-nav">
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
          <?php if($isTimeAdmin == 'TRUE' && $numberOfAlerts > 0): ?> <span class="badge" style="margin:0 15px 0 30px;background-color:#ed9c21;"><a href="adminTodos.php" style="color:white;" title="Your Database is in an invalid state, please fix these Errors after clicking this button. "> <?php echo $numberOfAlerts; ?> </a></span> <?php endif; ?>
            <p class="navbar-text"><?php echo $_SESSION['firstname']; ?></p>
            <a class="btn navbar-btn" data-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample"><i class="fa fa-info"></i></a>
            <a class="btn navbar-btn" data-toggle="modal" data-target="#myModal"><i class="fa fa-gears"></i></a>
            <a class="btn navbar-btn" href="logout.php" title="Logout"><i class="fa fa-sign-out"></i></a>
          </div>
        </div>
      </div> <!-- /container -->
    </nav>
    <!-- /navbar -->

    <div class="collapse" id="collapseExample">
      <div class="well">
        <a href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include 'version_number.php'; echo $VERSION_TEXT; ?>
        <br>
        The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
        the Software will perform substantially in accordance with the functional specifications set forth in the documentation.
      </div>
    </div>

    <form method=post>
      <!-- modal -->
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

            <div class="modal-body">
              PIN-Code: <br>
              <input type="number" class="form-control" name="pinCode" > <br><br>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-info" name="savePIN">Save PIN</button>
            </div>

          </div>
        </div>
      </div>
      <!-- /modal -->
    </form>

    <!-- side menu -->
    <div class="row affix-row">
      <div class="col-sm-3 col-md-2 affix-sidebar">
        <div class="sidebar-nav">
          <div class="navbar navbar-default" role="navigation">

            <div class="navbar-collapse collapse sidebar-navbar-collapse">
              <ul class="nav navbar-nav" id="sidenav01">
                <?php if($canStamp == 'TRUE'): ?>
                  <?php
                  $showProjectBookingLink = $cd = 0;
                  $query = "SELECT * FROM $configTable";
                  $result = mysqli_query($conn, $query);
                  if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $cd = $row['cooldownTimer'];
                  }
                  //display checkin or checkout + disabled
                  $query = "SELECT * FROM $logTable WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID AND status = '0' ";
                  $result = mysqli_query($conn, $query);
                  if ($result && $result->num_rows > 0) { //open timestamps must be closed
                    $row = $result->fetch_assoc();
                    $now = getCurrentTimestamp();
                    $indexIM = $row['indexIM'];
                    if(timeDiff_Hours($row['time'],$now) > $cd/60){ //he either has waited long enough
                      $sql = "SELECT * FROM $projectBookingTable WHERE timestampID = $indexIM AND projectID IS NULL ORDER BY end DESC"; //but what if he just came from a break
                      $result = mysqli_query($conn, $sql);
                      if ($result && $result->num_rows > 0) { //he did a break
                        $row = $result->fetch_assoc();
                        if(timeDiff_Hours($row['end'],$now) > $cd/60){ //and that break was NOT recently
                          $disabled = '';
                        } else { //but if it was just recently:
                          $disabled = 'disabled';
                        }
                      } else { //he has no breaks at all & he waited -> fine
                        $disabled = '';
                      }
                    } else {
                      $disabled = 'disabled';
                    }
                    $buttonVal = $lang['CHECK_OUT'];
                    echo "<li><br><div class=container-fluid><form method=post><button $disabled type='submit' class='btn btn-warning' name='stampOut'>$buttonVal</button></form></div><br></li>";
                    $showProjectBookingLink = TRUE;
                  } else {
                    $today = getCurrentTimestamp();
                    $timeIsLikeToday = substr($today, 0, 10) ." %";
                    $disabled = '';

                    $sql = "SELECT * FROM $logTable WHERE userID = $userID
                    AND status = '0'
                    AND time LIKE '$timeIsLikeToday'
                    AND TIMESTAMPDIFF(MINUTE, timeEnd, '$today') < $cd";
                    $result = mysqli_query($conn, $sql);
                    if($result && $result->num_rows > 0){
                      $disabled = 'disabled';
                    }
                    $buttonVal = $lang['CHECK_IN'];
                    echo "<li><br><div class=container-fluid><form method=post><button $disabled type='submit' class='btn btn-warning' name='stampIn'>$buttonVal</button></form></div><br></li>";
                  }
                  ?>

                  <!-- User-Section: BASIC -->

                  <li><a <?php if($this_page =='timeCalcTable.php'){echo $setActiveLink;}?> href="timeCalcTable.php"><i class="fa fa-clock-o"></i> <span><?php echo $lang['VIEW_TIMESTAMPS']; ?></span></a></li>
                  <li><a <?php if($this_page =='calendar.php'){echo $setActiveLink;}?> href="calendar.php"><i class="fa fa-calendar"></i> <span><?php echo $lang['CALENDAR']; ?></span></a></li>
                  <li><a <?php if($this_page =='makeRequest.php'){echo $setActiveLink;}?> href="makeRequest.php"><i class="fa fa-calendar-plus-o"></i> <span><?php echo $lang['VACATION'] .' '. $lang['REQUESTS']; ?></span></a></li>
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
                    <li><a <?php if($this_page =='templateSelect.php'){echo $setActiveLink;} ?> href="templateSelect.php"><i class="fa fa-file-pdf-o"></i><span><?php echo $lang['PDF_TEMPLATES']; ?></span></a></li>
                  <?php endif; ?>

                  <!-- Section One: CORE -->

                  <?php if($isCoreAdmin == 'TRUE'): ?>
                    <li role="separator" class="active"><br><br>
                      <a id="adminOption_CORE" href="#" data-toggle="collapse" data-target="#toggleAdminOption_CORE" data-parent="#sidenav01" class="collapse in"> <span><?php echo $lang['ADMIN_CORE_OPTIONS']; ?></span> <i class="fa fa-caret-down"></i></a>
                      <div class="collapse" id="toggleAdminOption_CORE" style="height: 0px;">
                        <ul class="nav navbar-nav">
                          <li>
                            <a id="coreUserToggle" href="#" data-toggle="collapse" data-target="#toggleUsers" data-parent="#sidenav01" class="collapse in">
                              <i class="fa fa-users"></i> <span><?php echo $lang['USERS']; ?></span> <i class="fa fa-caret-down"></i>
                            </a>
                            <div class="collapse" id="toggleUsers" style="height: 0px;">
                              <ul class="nav nav-list">
                                <li><a <?php if($this_page =='editUsers.php'){echo $setActiveLink;}?> href="editUsers.php"><?php echo $lang['EDIT_USERS']; ?></a></li>
                                <li><a <?php if($this_page =='register_choice.php'){echo $setActiveLink;}?> href="register_choice.php"><?php echo $lang['REGISTER_NEW_USER']; ?></a></li>
                                <li><a <?php if($this_page =='deactivatedUsers.php'){echo $setActiveLink;}?> href="deactivatedUsers.php"><?php echo $lang['USER_INACTIVE']; ?></a></li>
                              </ul>
                            </div>
                          </li>
                          <li>
                            <a id="coreSettingsToggle" href="#" data-toggle="collapse" data-target="#toggleSettings" data-parent="#sidenav01" class="collapsed">
                              <i class="fa fa-gear"></i> <span><?php echo $lang['SETTINGS']; ?></span> <i class="fa fa-caret-down"></i>
                            </a>
                            <div class="collapse" id="toggleSettings" style="height: 0px;">
                              <ul class="nav nav-list">
                                <li><a <?php if($this_page =='editCompanies.php'){echo $setActiveLink;}?> href="editCompanies.php"><?php echo $lang['COMPANIES']; ?></a></li>
                                <li><a <?php if($this_page =='configureLDAP.php'){echo $setActiveLink;}?> href="configureLDAP.php"><span>LDAP</span></a></li>
                                <li><a <?php if($this_page =='editHolidays.php'){echo $setActiveLink;}?> href="editHolidays.php"><span><?php echo $lang['HOLIDAYS']; ?></span></a></li>
                                <li><a <?php if($this_page =='advancedOptions.php'){echo $setActiveLink;}?> href="advancedOptions.php"><span><?php echo $lang['ADVANCED_OPTIONS']; ?></span></a></li>
                                <li><a <?php if($this_page =='passwordOptions.php'){echo $setActiveLink;}?> href="passwordOptions.php"><span>Password Options</span></a></li>
                                <li><a <?php if($this_page =='pullGitRepo.php'){echo $setActiveLink;}?> href="pullGitRepo.php"><span>Update</span></a></li>
                              </ul>
                            </div>
                          </li>
                          <li><a <?php if($this_page =='sqlDownload.php'){echo $setActiveLink;}?> href="sqlDownload.php" target="_blank"> <i class="fa fa-database"></i> <span> DB Backup</span> </a></li>
                          <li><a <?php if($this_page =='templateSelect.php'){echo $setActiveLink;}?> href="templateSelect.php"> <i class="fa fa-file-pdf-o"></i> <span><?php echo $lang['PDF_TEMPLATES']; ?></span> </a></li>
                        </ul>
                      </div>
                    </li>
                  <?php endif; ?>

                  <?php
                  if($this_page == "editUsers.php" || $this_page == "register_choice.php" || $this_page == "deactivatedUsers.php"){
                    echo "<script>document.getElementById('coreUserToggle').click();document.getElementById('adminOption_CORE').click();</script>";
                  } elseif($this_page == "editCompanies.php" || $this_page == "configureLDAP.php" || $this_page == "editHolidays.php" || $this_page == "advancedOptions.php" || $this_page == "pullGitRepo.php" || $this_page == "passwordOptions.php"){
                    echo "<script>document.getElementById('coreSettingsToggle').click();document.getElementById('adminOption_CORE').click();</script>";
                  } elseif($this_page == "sqlDownload.php" || $this_page == "templateSelect.php") {
                    echo "<script>document.getElementById('adminOption_CORE').click();</script>";
                  }
                  ?>

                  <!-- Section Two: TIME -->
                  <?php if($isTimeAdmin == 'TRUE'): ?>
                    <li role="separator" class="active"><br><br>
                      <a id="adminOption_TIME" href="#" data-toggle="collapse" data-target="#toggleAdminOption_TIME" data-parent="#sidenav01" class="collapse in"> <span><?php echo $lang['ADMIN_TIME_OPTIONS']; ?></span> <i class="fa fa-caret-down"></i></a>
                      <div class="collapse" id="toggleAdminOption_TIME" style="height: 0px;">
                        <ul class="nav navbar-nav">

                          <li><a <?php if($this_page =='getTimestamps.php'){echo $setActiveLink;}?> href="getTimestamps.php"><i class="fa fa-pencil"></i> <span><?php echo $lang['TIMESTAMPS'].' '.$lang['EDIT']; ?></span></a></li>
                          <li><a <?php if($this_page =='monhtlyReport.php'){echo $setActiveLink;}?> href="monthlyReport.php"><i class="fa fa-book"></i> <?php echo $lang['MONTHLY_REPORT']; ?></a></li>
                          <li><a <?php if($this_page =='allowVacations.php'){echo $setActiveLink;}?> href="allowVacations.php"><i class="fa fa-check-square-o"></i> <?php echo $lang['VACATION']; ?></a></li>
                          <li><a <?php if($this_page =='getTravellingExpenses.php'){echo $setActiveLink;}?> href="getTravellingExpenses.php"><i class="fa fa-plane"></i> <?php echo $lang['TRAVEL_FORM']; ?></a></li>
                          <li><a href="calendar.php"><i class="fa fa-calendar"></i> <span><?php echo $lang['CALENDAR']; ?></span></a></li>
                          <hr>
                          <li><a <?php if($this_page =='adminTodos.php'){echo $setActiveLink;}?> href="adminTodos.php"><i class="fa fa-exclamation-triangle"></i> <?php echo $lang['FOUNDERRORS']; ?></a></li>
                        </ul>
                      </div>
                    </li>
                  <?php endif; ?>

                  <?php
                  if($this_page == "getTimestamps.php" || $this_page == "monthlyReport.php" || $this_page == "allowVacations.php" || $this_page == "adminTodos.php"){
                    echo "<script>document.getElementById('adminOption_TIME').click();</script>";
                  }
                  ?>

                  <!-- Section Three: PROJECTS -->
                  <?php if($isProjectAdmin == 'TRUE'): ?>
                    <li role="separator" class="active"><br><br>
                      <a id="adminOption_PROJECT" href="#" data-toggle="collapse" data-target="#toggleAdminOption_PROJECT" data-parent="#sidenav01" class="collapse in">
                        <span><?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?></span> <i class="fa fa-caret-down"></i>
                      </a>
                      <div class="collapse" id="toggleAdminOption_PROJECT" style="height: 0px;">
                        <ul class="nav navbar-nav">
                          <li><a <?php if($this_page =='getProjects.php'){echo $setActiveLink;}?> href="getProjects.php"><i class="fa fa-history"></i>
                            <span><?php echo $lang['PROJECT_BOOKINGS']; ?></span>
                          </a></li>
                          <li><a <?php if($this_page =='editCustomers.php'){echo $setActiveLink;}?> href="editCustomers.php"><i class="fa fa-briefcase"></i>
                            <span><?php echo $lang['CLIENTS']; ?></span>
                          </a></li>
                          <li><a <?php if($this_page =='editProjects.php'){echo $setActiveLink;}?> href="editProjects.php"><i class="fa fa-tags"></i>
                            <span><?php echo $lang['VIEW_PROJECTS']; ?></span>
                          </a></li>
                        </ul>
                      </div>
                    </li>
                  <?php endif; ?>

                  <?php
                  if($this_page == "getProjects.php" || $this_page == "editCustomers.php" || $this_page == "editProjects.php"){
                    echo "<script>document.getElementById('adminOption_PROJECT').click();</script>";
                  }
                  ?>

                </ul>
              </div><!--/.nav-collapse -->
            </div>
          </div>
        </div>

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
        <div class="col-sm-9 col-md-10 affix-content">
          <div class="container">
