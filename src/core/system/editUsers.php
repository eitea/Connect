<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php enableToCore($userID);?>
<!-- BODY -->
<div class="page-header-fixed">
<div class="page-header">
  <h3><?php echo $lang['USERS']; ?><div class="page-header-button-group"><a class="btn btn-default" href='register' title="<?php echo $lang['REGISTER']; ?>">+</a></div></h3>
</div>
</div>
<?php
$activeTab = 0;
if(isset($_GET['ACT'])){$activeTab = $_GET['ACT']; }
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if(isset($_POST['deactivate']) && $_POST['deactivate'] != 1 && $_POST['deactivate'] != $userID){
    $x = $_POST['deactivate'];
    $acc = true;
    //copy user table
    $sql = "INSERT IGNORE INTO $deactivatedUserTable(id, firstname, lastname, psw, sid, email, gender, beginningDate, exitDate, preferredLang, terminalPin, kmMoney)
    SELECT id, firstname, lastname, psw, sid, email, gender, beginningDate, exitDate, preferredLang, terminalPin, kmMoney FROM $userTable WHERE id = $x";
    if(!$conn->query($sql)){$acc = false; echo 'userErr: '.mysqli_error($conn);}
    //copy logs
    $sql = "INSERT IGNORE INTO $deactivatedUserLogs(userID, time, timeEnd, status, timeToUTC, indexIM)
    SELECT userID, time, timeEnd, status, timeToUTC, indexIM FROM $logTable WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo 'logErr: '.mysqli_error($conn);}
    //copy intervalTable
    $sql = "INSERT IGNORE INTO $deactivatedUserDataTable(userID, mon, tue, wed, thu, fri, sat, sun, vacPerYear, overTimeLump, pauseAfterHours, hoursOfRest, startDate, endDate)
    SELECT userID, mon, tue, wed, thu, fri, sat, sun, vacPerYear, overTimeLump, pauseAfterHours, hoursOfRest, startDate, endDate FROM $intervalTable WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo '<br>dataErr: '.mysqli_error($conn);}
    //copy projectbookings
    $sql = "INSERT IGNORE INTO $deactivatedUserProjects(start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit)
    SELECT start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit
    FROM $projectBookingTable, $logTable WHERE $logTable.indexIM = $projectBookingTable.timestampID AND $logTable.userID = $x AND projectID IS NOT NULL AND projectID != 0";
    if(!$conn->query($sql)){$acc = false; echo '<br>projErr: '. mysqli_error($conn);}
    //copy projectbookings - foreign key null gets cast to 0... idky.
    $sql = "INSERT IGNORE INTO $deactivatedUserProjects(start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit)
    SELECT start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit
    FROM $projectBookingTable, $logTable WHERE $logTable.indexIM = $projectBookingTable.timestampID AND $logTable.userID = $x AND projectID != 0 AND projectID IS NOT NULL";
    if(!$conn->query($sql)){$acc = false; echo '<br>projErr: '. mysqli_error($conn);}
    //copy projectbookings - null for every null, which is 0 #why
    $sql = "INSERT IGNORE INTO $deactivatedUserProjects(start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit)
    SELECT start, end, NULL, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit
    FROM $projectBookingTable, $logTable WHERE $logTable.indexIM = $projectBookingTable.timestampID AND $logTable.userID = $x AND (projectID = 0 OR projectID IS NULL)";
    if(!$conn->query($sql)){$acc = false; echo '<br>projErr: '. mysqli_error($conn);}
    //copy taveldata
    $sql = "INSERT IGNORE INTO $deactivatedUserTravels(userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses)
    SELECT userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses FROM $travelTable WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo '<br>travelErr: '.mysqli_error($conn);}
    //if successful, delete the user, On Cascade Delete does the rest.
    if($acc){
      $sql  = "DELETE FROM $userTable WHERE id = $x";
      if(!$conn->query($sql)){echo mysqli_error($conn);}
    }
  } elseif(isset($_POST['deactivate'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ADMIN_DELETE'].'</div>';
  }

  if (isset($_POST['deleteUser'])) {
    $x = $_POST['deleteUser'];
    if ($x != 1 && $x != $userID)  {
      $conn->query("DELETE FROM $userTable WHERE id = $x;");
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ADMIN_DELETE'].'</div>';
    }
  }

  if(isset($_POST['addNewInterval']) && !empty($_POST['intervalEnd']) && test_Date($_POST['intervalEnd'].' 05:00:00')){
    $activeTab = $x = $_POST['addNewInterval'];
    $overTimeAll = $vacDaysPerYear = $pauseAfter = $rest = $mon = $tue = $wed = $thu = $fri = $sat = $sun = 0;
    $intervalEnd = $_POST['intervalEnd'].' 05:00:00';

    if (isset($_POST['overTimeAll'.$x]) && is_numeric(str_replace(',','.',$_POST['overTimeAll'.$x]))){
      $overTimeAll = str_replace(',','.',$_POST['overTimeAll'.$x]);
    }

    if (isset($_POST['daysPerYear'.$x]) && is_numeric($_POST['daysPerYear'.$x])){
      $vacDaysPerYear = intval($_POST['daysPerYear'.$x]);
    }

    if (isset($_POST['pauseAfter'.$x]) && is_numeric($_POST['pauseAfter'.$x])){
      $pauseAfter = $_POST['pauseAfter'.$x];
    }

    if (isset($_POST['rest'.$x]) && is_numeric($_POST['rest'.$x])){
      $rest = $_POST['rest'.$x];
    }

    if(isset($_POST['mon'.$x]) && is_numeric($_POST['mon'.$x])){
      $mon = test_input($_POST['mon'.$x]);
    }

    if (isset($_POST['tue'.$x]) && is_numeric($_POST['tue'.$x])){
      $tue = test_input($_POST['tue'.$x]);
    }

    if (isset($_POST['wed'.$x]) && is_numeric($_POST['wed'.$x])){
      $wed = test_input($_POST['wed'.$x]);
    }

    if (isset($_POST['thu'.$x]) && is_numeric($_POST['thu'.$x])){
      $thu = test_input($_POST['thu'.$x]);
    }

    if (isset($_POST['fri'.$x]) && is_numeric($_POST['fri'.$x])){
      $fri = test_input($_POST['fri'.$x]);
    }

    if (isset($_POST['sat'.$x]) && is_numeric($_POST['sat'.$x])){
      $sat = test_input($_POST['sat'.$x]);
    }

    if (isset($_POST['sun'.$x]) && is_numeric($_POST['sun'.$x])){
      $sun = test_input($_POST['sun'.$x]);
    }
    //close up the old one
    $conn->query("UPDATE $intervalTable SET mon='$mon', tue='$tue', wed='$wed', thu='$thu', fri='$fri', sat='$sat', sun='$sun',
      vacPerYear='$vacDaysPerYear', overTimeLump='$overTimeAll', pauseAfterHours='$pauseAfter', hoursOfRest='$rest', endDate='$intervalEnd'
      WHERE userID = $x AND endDate IS NULL");
    //create a new one
    $conn->query("INSERT INTO $intervalTable (userID, mon, tue, wed, thu, fri, sat, sun, vacPerYear, overTimeLump, pauseAfterHours, hoursOfRest, startDate)
    VALUES($x, '$mon', '$tue', '$wed', '$thu', '$fri', '$sat', '$sun', '$vacDaysPerYear', '$overTimeAll', '$pauseAfter', '$rest', '$intervalEnd')");

    echo mysqli_error($conn);
  }

  if (isset($_POST['submitUser'])) {
    $activeTab = $x = $_POST['submitUser'];

    if (!empty($_POST['firstname'.$x])) {
      $firstname = test_input($_POST['firstname'.$x]);
      $sql = "UPDATE $userTable SET firstname= '$firstname' WHERE id = '$x';";
      $conn->query($sql);
    }

    if (!empty($_POST['lastname'.$x])) {
      $lastname = test_input($_POST['lastname'.$x]);
      $sql = "UPDATE $userTable SET lastname= '$lastname' WHERE id = '$x';";
      $conn->query($sql);
    }

    if(!empty($_POST['exitDate'.$x]) && test_Date($_POST['exitDate'.$x] .' 00:00:00')) {
      $exitDate = test_input($_POST['exitDate'.$x]) . ' 00:00:00';
      $conn->query("UPDATE $userTable SET exitDate = '$exitDate' WHERE id = '$x'");
    }

    if(!empty($_POST['coreTime'.$x])) {
      $coreTime = test_input($_POST['coreTime'.$x]);
      $conn->query("UPDATE $userTable SET coreTime = '$coreTime' WHERE id = '$x'");
    }

    if (!empty($_POST['supervisor'.$x])){
      $supervisor = intval($_POST['supervisor'.$x]);
      $conn->query("UPDATE $userTable SET supervisor = $supervisor WHERE id = $x");
    }

    if (!empty($_POST['email'.$x]) && filter_var(test_input($_POST['email'.$x] .'@domain.com'), FILTER_VALIDATE_EMAIL)){
      $email = test_input($_POST['email'.$x]).'@';
      $sql = "UPDATE $userTable SET email = CONCAT('$email', SUBSTRING(email, LOCATE('@', email) + 1)) WHERE id = '$x';";
      $conn->query($sql);
      if($conn->error){
        echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['ERROR_EXISTING_EMAIL'].'</div>';
      }
    } else {
      echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['ERROR_EMAIL'].'</div>';
    }

    if (!empty($_POST['password'.$x]) && !empty($_POST['passwordConfirm'.$x])) {
      $password = $_POST['password'.$x];
      $passwordConfirm = $_POST['passwordConfirm'.$x];
      if (strcmp($password, $passwordConfirm) == 0  && match_passwordpolicy($password)) {
        $psw = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE $userTable SET psw = '$psw' WHERE id = '$x';";
        $conn->query($sql);
      } else {
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Could not change Passwords! </strong>Passwords did not match or were invalid. Password must be at least 8 characters long and contain at least one Capital Letter, one number and one special character.';
        echo '</div>';
      }
    }

    if (isset($_POST['company'.$x])){
      $sql = "SELECT * FROM $companyTable";
      $result = $conn->query($sql);
      while($row = $result->fetch_assoc()){
        //just completely delete the relationship from table to avoid duplicate entries.
        $sql = "DELETE FROM $companyToUserRelationshipTable WHERE userID = $x AND companyID = " . $row['id'];
        $conn->query($sql);
        if(in_array($row['id'], $_POST['company'.$x])){  //if company is checked, insert again
          $sql = "INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES (".$row['id'].", $x)";
          $conn->query($sql);
        }
      }
    }

    if (!empty($_POST['enableProjecting'.$x])) {
      $enableProjecting = test_input($_POST['enableProjecting'.$x]);
      $sql = "UPDATE $userTable SET enableProjecting= '$enableProjecting' WHERE id = '$x';";
      $conn->query($sql);
    }

    if (!empty($_POST['gender'.$x])) {
      $gender = test_input($_POST['gender'.$x]);
      $sql = "UPDATE $userTable SET gender= '$gender' WHERE id = '$x';";
      $conn->query($sql);
    }

    if(isset($_POST['isCoreAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isCoreAdmin = 'TRUE' WHERE userID = $x";
    } else {
      $sql = "UPDATE $roleTable SET isCoreAdmin = 'FALSE' WHERE userID = $x";
    }
    $conn->query($sql);
    if(isset($_POST['isDynamicProjectsAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isDynamicProjectsAdmin = 'TRUE' WHERE userID = $x";
    } else {
        $sql = "UPDATE $roleTable SET isDynamicProjectsAdmin = 'FALSE' WHERE userID = $x";
    }
    $conn->query($sql);

    if(isset($_POST['isTimeAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isTimeAdmin = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET isTimeAdmin = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);

    if(isset($_POST['isProjectAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isProjectAdmin = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET isProjectAdmin = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);
    if(isset($_POST['isReportAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isReportAdmin = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET isReportAdmin = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);
    if(isset($_POST['isERPAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isERPAdmin = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET isERPAdmin = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);
    if(isset($_POST['isFinanceAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isFinanceAdmin = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET isFinanceAdmin = 'FALSE' WHERE userID = '$x'";
    }$conn->query($sql);
    if(isset($_POST['isDSGVOAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isDSGVOAdmin = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET isDSGVOAdmin = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);
    if(isset($_POST['canStamp'.$x])){
      $sql = "UPDATE $roleTable SET canStamp = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET canStamp = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);
    if(isset($_POST['canStamp'.$x]) && isset($_POST['canBook'.$x])){
      $sql = "UPDATE $roleTable SET canBook = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET canBook = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);
    if(isset($_POST['canEditTemplates'.$x])){
      $sql = "UPDATE $roleTable SET canEditTemplates = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET canEditTemplates = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);
    if(isset($_POST['canUseSocialMedia'.$x])){
      $sql = "UPDATE $roleTable SET canUseSocialMedia = 'TRUE' WHERE userID = '$x'";
    } else {
      $sql = "UPDATE $roleTable SET canUseSocialMedia = 'FALSE' WHERE userID = '$x'";
    }
    $conn->query($sql);

    $overTimeAll = $vacDaysPerYear = $pauseAfter = $rest = $mon = $tue = $wed = $thu = $fri = $sat = $sun = 0;

    if (isset($_POST['overTimeAll'.$x]) && is_numeric(str_replace(',','.',$_POST['overTimeAll'.$x]))){
      $overTimeAll = str_replace(',','.',$_POST['overTimeAll'.$x]);
    }

    if (isset($_POST['daysPerYear'.$x]) && is_numeric($_POST['daysPerYear'.$x])){
      $vacDaysPerYear = intval($_POST['daysPerYear'.$x]);
    }

    if (isset($_POST['pauseAfter'.$x]) && is_numeric($_POST['pauseAfter'.$x])){
      $pauseAfter = $_POST['pauseAfter'.$x];
    }

    if (isset($_POST['rest'.$x]) && is_numeric($_POST['rest'.$x])){
      $rest = $_POST['rest'.$x];
    }

    if(isset($_POST['mon'.$x]) && is_numeric($_POST['mon'.$x])){
      $mon = test_input($_POST['mon'.$x]);
    }

    if (isset($_POST['tue'.$x]) && is_numeric($_POST['tue'.$x])){
      $tue = test_input($_POST['tue'.$x]);
    }

    if (isset($_POST['wed'.$x]) && is_numeric($_POST['wed'.$x])){
      $wed = test_input($_POST['wed'.$x]);
    }

    if (isset($_POST['thu'.$x]) && is_numeric($_POST['thu'.$x])){
      $thu = test_input($_POST['thu'.$x]);
    }

    if (isset($_POST['fri'.$x]) && is_numeric($_POST['fri'.$x])){
      $fri = test_input($_POST['fri'.$x]);
    }

    if (isset($_POST['sat'.$x]) && is_numeric($_POST['sat'.$x])){
      $sat = test_input($_POST['sat'.$x]);
    }

    if (isset($_POST['sun'.$x]) && is_numeric($_POST['sun'.$x])){
      $sun = test_input($_POST['sun'.$x]);
    }

    //update latest interval
    $conn->query("UPDATE $intervalTable SET mon='$mon', tue='$tue', wed='$wed', thu='$thu', fri='$fri', sat='$sat', sun='$sun',
      vacPerYear='$vacDaysPerYear', overTimeLump='$overTimeAll', pauseAfterHours='$pauseAfter', hoursOfRest='$rest'
      WHERE userID = $x AND endDate IS NULL");

    echo mysqli_error($conn);
    if($userID == $x){
      redirect("../system/users?ACT=$x");
    }
  }//end if isset submitX
  if(!empty($_POST['saveProfilePicture'])){
    $x = intval($_POST['saveProfilePicture']);
    require_once dirname(dirname(__DIR__)) . "/utilities.php";
    $pp = uploadImage('profilePicture', 1, 1);
    if(!is_array($pp)) {
      $stmt = $conn->prepare("UPDATE socialprofile SET picture = ? WHERE userID = $x");
      echo $conn->error;
      $null = NULL;
      $stmt->bind_param("b", $null);
      $stmt->send_long_data(0, $pp);
      $stmt->execute();
      if($stmt->errno) echo $stmt->error;
      $stmt->close();
    } else {
      echo print_r($pp);
    }
  }
}
?>

<script>
$(document).ready(function(){
    $('[data-toggle="popover"]').popover({
      container: 'body'
    });
});
</script>
<br>
<div class="page-content-fixed-100">
<div class="container-fluid panel-group" id="accordion" role="tablist" aria-multiselectable="true">
  <?php
  $query = "SELECT *, $userTable.id AS user_id FROM $userTable
  INNER JOIN $roleTable ON $roleTable.userID = $userTable.id
  INNER JOIN $intervalTable ON $intervalTable.userID = $userTable.id
  LEFT JOIN socialprofile ON socialprofile.userID = $userTable.id
  WHERE endDate IS NULL ORDER BY $userTable.id ASC";
  $result = mysqli_query($conn, $query);
  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $x = $row['user_id'];

      $firstname = $row['firstname'];
      $lastname = $row['lastname'];
      $gender = $row['gender'];
      $email = $row['email'];
      $begin = $row['beginningDate'];
      $end = $row['exitDate'];
      $coreTime = $row['coreTime'];

      $intervalStart = $row['startDate'];
      $profilePicture = $row['picture'] ? "data:image/jpeg;base64,".base64_encode($row['picture']) : "images/defaultProfilePicture.png";

      $mon = $row['mon'];
      $tue = $row['tue'];
      $wed = $row['wed'];
      $thu = $row['thu'];
      $fri = $row['fri'];
      $sat = $row['sat'];
      $sun = $row['sun'];

      $vacDaysPerYear = $row['vacPerYear'];
      $overTimeAll = $row['overTimeLump'];
      $pauseAfter = $row['pauseAfterHours'];
      $rest = $row['hoursOfRest'];

      $isCoreAdmin = $row['isCoreAdmin'];
      $isDynamicProjectsAdmin = $row['isDynamicProjectsAdmin'];
      $isTimeAdmin = $row['isTimeAdmin'];
      $isProjectAdmin = $row['isProjectAdmin'];
      $isReportAdmin = $row['isReportAdmin'];
      $isERPAdmin = $row['isERPAdmin'];
      $isFinanceAdmin = $row['isFinanceAdmin'];
      $isDSGVOAdmin = $row['isDSGVOAdmin'];
      $canBook = $row['canBook'];
      $canStamp = $row['canStamp'];
      $canEditTemplates = $row['canEditTemplates'];
      $canUseSocialMedia = $row['canUseSocialMedia'];
      $canCreateTasks = $row['canCreateTasks'];

      $eOut = "$firstname $lastname";
      ?>

      <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="heading<?php echo $x; ?>">
          <h4 class="panel-title">
            <div class="row">
              <div class="col-md-6">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $x; ?>">
                  <?php echo $eOut; ?>
                </a>
              </div>
              <div class="col-md-6 text-right">
                <form method="post">
                  <button type='submit' value="<?php echo $x; ?>" name='deactivate' style="background:none; border:none;" title="<?php echo $lang['DEACTIVATE']; ?>">
                    <small><?php echo $lang['DEACTIVATE']; ?></small>
                  </button>
                </form>
              </div>
            </div>
          </h4>
        </div>
        <div id="collapse<?php echo $x; ?>" class="panel-collapse collapse <?php if($x == $activeTab) echo 'in'; ?>" role="tabpanel" aria-labelledby="heading<?php echo $x; ?>">
          <div class="panel-body">

            <!-- #########  CONTENT ######## -->
            <form method="POST" enctype="multipart/form-data">
              <div class="container-fluid">
                <div class="col-sm-2">
                  <label class="btn btn-default btn-block"><?php echo $lang['SOCIAL_UPLOAD_PICTURE']; ?> <input type="file" name="profilePicture" style="display:none"></label><br>
                  <button type="submit" class="btn btn-warning btn-block" name="saveProfilePicture" value="<?php echo $x; ?>"><?php echo $lang['SAVE_PICTURE']; ?></button>
                </div>
                <div class="col-sm-8">
                  <img src='<?php echo $profilePicture; ?>' style='width:120px;height:120px;' class='img-circle center-block'><br>
                </div>
              </div>
            </form>

            <form method="POST">
              <div class="container-fluid">
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style="min-width:150px"><?php echo $lang['FIRSTNAME'] ?></span>
                    <input type="text" class="form-control" name="firstname<?php echo $x; ?>" value="<?php echo $firstname; ?>">
                  </div>
                </div>
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style="min-width:150px"><?php echo $lang['LASTNAME'] ?></span>
                    <input type="text" class="form-control" name="lastname<?php echo $x; ?>" value="<?php echo $lastname; ?>">
                  </div>
                </div>
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style="min-width:150px">Login E-Mail</span>
                    <input type="text" class="form-control" name="email<?php echo $x; ?>" value="<?php echo explode('@', $email)[0]; ?>"/>
                    <span class="input-group-addon" style="min-width:150px">@<?php echo explode('@', $email)[1]; ?></span>
                  </div>
                </div>
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style=min-width:150px><?php echo $lang['NEW_PASSWORD']; ?></span>
                    <input type="password" class="form-control" name="password<?php echo $x; ?>" placeholder="* * * *">
                  </div>
                </div>
                <div class="form-group">
                  <div class="input-group">
                    <span class="input-group-addon" style="min-width:150px"><?php echo $lang['NEW_PASSWORD_CONFIRM']; ?></span>
                    <input type="password" class="form-control" name="passwordConfirm<?php echo $x; ?>" placeholder="* * * *">
                  </div>
                </div>
                <div class="form-group">
                  <div class="col-md-2">
                    <?php echo $lang['SUPERVISOR']; ?>:
                  </div>
                  <div class="col-md-3">
                    <select name="supervisor<?php echo $x; ?>" class="js-example-basic-single" >
                    <option value="0"> ... </option>
                    <?php
                    $sup_result = $conn->query("SELECT id, firstname, lastname FROM UserData");
                    while($sup_row = $sup_result->fetch_assoc()){
                      $selected = ($row['supervisor'] == $sup_row['id']) ? 'selected' : '';
                      echo '<option '.$selected.' value="'.$sup_row['id'].'" >'.$sup_row['firstname'].' '.$sup_row['lastname'].'</option>';
                    }
                    ?>
                    </select>
                  </div>
                  <div class="col-md-3" >
                    <label style="float:right;"  ><?php echo "Last Password Change: ".(date_create($row['lastPswChange'])->format('d.m.Y')); ?></label>
                  </div>
                  <div class="col-md-3" >
                    <button type="button" class="btn btn-danger" onClick="forcePswChange(<?php echo $x; ?>,event)" ><?php echo "Force Password Change"; ?></button>
                  </div>
                </div>
              </div>
              <div class="container-fluid radio">
                <div class="col-md-2">
                  <?php echo $lang['GENDER']; ?>:
                </div>
                <div class="col-md-2">
                  <label>
                    <input type="radio" name="gender<?php echo $x; ?>" value="female" <?php if($gender == 'female'){echo 'checked';} ?> ><i class="fa fa-venus"></i><?php echo $lang['GENDER_TOSTRING']['female']; ?> <br>
                  </label>
                </div>
                <div class="col-md-8">
                  <label>
                    <input type="radio" name="gender<?php echo $x; ?>" value="male" <?php if($gender == 'male'){echo 'checked';} ?> ><i class="fa fa-mars"></i><?php echo $lang['GENDER_TOSTRING']['male']; ?>
                  </label>
                </div>
              </div>
              <div class="container-fluid">
                <div class="col-md-5">
                  <?php echo $lang['ENTRANCE_DATE'] .'<p class="form-control" style="background-color:#ececec">'. substr($begin,0,10); ?></p>
                </div>
                <div class="col-md-2">
                  <?php echo $lang['CORE_TIME']; ?>
                  <p><input type="text" class="form-control timepicker" name="coreTime<?php echo $x; ?>" value="<?php echo $coreTime; ?>" /></p>
                </div>
                <div class="col-md-5">
                  <?php echo $lang['EXIT_DATE']; ?>
                  <input type="text" class="form-control datepicker" name="exitDate<?php echo $x; ?>" value="<?php echo substr($end,0,10); ?>"/>
                </div>
              </div>
              <br>
              <div class="container-fluid">
                <div class="col-md-4">
                  <?php echo $lang['ADMIN_MODULES']; ?>: <br>
                  <div class="checkbox">
                    <div class="col-md-6">
                      <label>
                      <input type="checkbox" name="isCoreAdmin<?php echo $x; ?>" <?php if($isCoreAdmin == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_CORE_OPTIONS']; ?>
                      </label><br>
                      <label>
                        <input type="checkbox" name="isTimeAdmin<?php echo $x; ?>" <?php if($isTimeAdmin == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_TIME_OPTIONS']; ?>
                      </label><br>
                      <label>
                        <input type="checkbox" name="isProjectAdmin<?php echo $x; ?>" <?php if($isProjectAdmin == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?>
                      </label><br>
                      <label>
                        <input type="checkbox" name="isReportAdmin<?php echo $x; ?>" <?php if($isReportAdmin == 'TRUE'){echo 'checked';} ?>  /><?php echo $lang['REPORTS']; ?>
                      </label><br>
                    </div>
                    <div class="col-md-6">
                      <label>
                        <input type="checkbox" name="isERPAdmin<?php echo $x; ?>" <?php if($isERPAdmin == 'TRUE'){echo 'checked';} ?> />ERP
                      </label><br>
                      <label>
                        <input type="checkbox" name="isDynamicProjectsAdmin<?php echo $x; ?>" <?php if($isDynamicProjectsAdmin == 'TRUE'){echo 'checked';} ?>><?php echo $lang['DYNAMIC_PROJECTS']; ?>
                      </label><br>
                      <label>
                        <input type="checkbox" name="isFinanceAdmin<?php echo $x; ?>" <?php if($isFinanceAdmin == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['FINANCES']; ?>
                      </label><br>
                      <label>
                        <input type="checkbox" name="isDSGVOAdmin<?php echo $x; ?>" <?php if($isDSGVOAdmin == 'TRUE'){echo 'checked';} ?> />DSGVO
                      </label>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <?php echo $lang['USER_MODULES']; ?>:
                  <div class="checkbox">
                  <div class="col-md-6">
                      <label>
                        <input type="checkbox" name="canStamp<?php echo $x; ?>" <?php if($canStamp == 'TRUE'){echo 'checked';} ?>><?php echo $lang['CAN_CHECKIN']; ?>
                      </label>
                      <br>
                      <label>
                        <input type="checkbox" name="canBook<?php echo $x; ?>" <?php if($canBook == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_BOOK']; ?>
                      </label>
                      <br>
                      <label>
                        <input type="checkbox" name="canEditTemplates<?php echo $x; ?>" <?php if($canEditTemplates == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_EDIT_TEMPLATES']; ?>
                      </label>
                      <br>
                      <label>
                        <input type="checkbox" name="canUseSocialMedia<?php echo $x; ?>" <?php if($canUseSocialMedia == 'TRUE'){echo 'checked';} ?> /><?php echo $lang['CAN_USE_SOCIAL_MEDIA']; ?>
                      </label>
                    </div>
                    <div class="col-md-6">
                      <label>
                        <input type="checkbox" name="canCreateTasks<?php echo $x; ?>" <?php if($canCreateTasks == 'TRUE'){echo 'checked';} ?>/><?php echo $lang['CAN_CREATE_TASKS']; ?>
                      </label>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <?php echo $lang['COMPANIES']; ?>: <br>
                  <div class="checkbox">
                    <?php
                    $sql = "SELECT * FROM $companyTable";
                    $companyResult = $conn->query($sql);
                    while($companyRow = $companyResult->fetch_assoc()){
                      $resultset2 = $conn->query("SELECT * FROM $companyToUserRelationshipTable WHERE companyID = " . $companyRow['id'] . " AND userID = $x");
                      if($resultset2 && $resultset2->num_rows >0){
                        $selected = 'checked';
                      } else {
                        $selected = '';
                      }
                      echo "<label><input type='checkbox' $selected name='company".$x."[]' value=" .$companyRow['id']. " />" . $companyRow['name'] ."</label><br>";
                    }
                    ?>
                  </div>
                  <small>*<?php echo $lang['INFO_COMPANYLESS_USERS']; ?></small>
                </div>
              </div>
              <br><br>
              <!-- Interval table -->
              <div class="container-fluid well">
                <div class="row">
                  <div class="col-md-3">
                    <?php echo $lang['OVERTIME_ALLOWANCE']; ?>: <br>
                    <input type="number" class="form-control" name="overTimeAll<?php echo $x; ?>" value="<?php echo $overTimeAll; ?>"
                     data-toggle="popover" title="Important!" data-trigger="focus" data-content="This value will always be read at the end of each month."/>
                  </div>
                  <div class="col-md-3">
                    <?php echo $lang['TAKE_BREAK_AFTER']; ?>: <input type="number" class="form-control" step=any  name="pauseAfter<?php echo $x; ?>" value="<?php echo $pauseAfter; ?>"/>
                  </div>
                  <div class="col-md-3">
                    <?php echo $lang['HOURS_OF_REST']; ?>: <input type="number" class="form-control" step=any  name="rest<?php echo $x; ?>" value="<?php echo $rest; ?>"/>
                  </div>
                  <div class="col-md-3">
                    <?php echo $lang['VACATION_DAYS']. $lang['PER_YEAR']; ?>
                    <input type="number" class="form-control" name="daysPerYear<?php echo $x; ?>" value="<?php echo $vacDaysPerYear; ?>"/>
                  </div>
                </div>
                <br>
                <div class="row">
                  <div style="width:11%; float:left; margin-left:3%">
                    <?php echo $lang['WEEKDAY_TOSTRING']['mon']; ?>
                    <input type="number" step="any" class="form-control" name="mon<?php echo $x; ?>" size="2" value= "<?php echo $mon; ?>" />
                  </div>
                  <div style="width:11%; float:left; margin-left:3%">
                    <?php echo $lang['WEEKDAY_TOSTRING']['tue']; ?>
                    <input type="number" step="any" class="form-control" name="tue<?php echo $x; ?>" size="2" value= "<?php echo $tue; ?>" />
                  </div>
                  <div style="width:11%; float:left; margin-left:3%">
                    <?php echo $lang['WEEKDAY_TOSTRING']['wed']; ?>
                    <input type="number" step="any" class="form-control" name="wed<?php echo $x; ?>" size="2" value= "<?php echo $wed; ?>" />
                  </div>
                  <div style="width:11%; float:left; margin-left:3%">
                    <?php echo $lang['WEEKDAY_TOSTRING']['thu']; ?>
                    <input type="number" step="any" class="form-control" name="thu<?php echo $x; ?>" size="2" value= "<?php echo $thu; ?>" />
                  </div>
                  <div style="width:11%; float:left; margin-left:3%">
                    <?php echo $lang['WEEKDAY_TOSTRING']['fri']; ?>
                    <input type="number" step="any" class="form-control" name="fri<?php echo $x; ?>" size="2" value= "<?php echo $fri; ?>" />
                  </div>
                  <div style="width:11%; float:left; margin-left:3%">
                    <?php echo $lang['WEEKDAY_TOSTRING']['sat']; ?>
                    <input type="number" step="any" class="form-control" name="sat<?php echo $x; ?>" size="2" value= "<?php echo $sat; ?>" />
                  </div>
                  <div style="width:10%; float:left; margin-left:3%">
                    <?php echo $lang['WEEKDAY_TOSTRING']['sun']; ?>
                    <input type="number" step="any" class="form-control" name="sun<?php echo $x; ?>" size="2" value= "<?php echo $sun; ?>" />
                  </div>
                </div>
              </div>
              <div class="container-fluid well">
                <div class="row">
                  <div class="col-md-4">
                    <?php echo $lang['VALID_PERIOD'].' ('.$lang['FROM'].' - '.$lang['TO'].')'; ?>:
                  </div>
                  <div class="col-xs-3">
                    <input type="text" readonly class="form-control" value="<?php echo substr($intervalStart,0,10); ?>" />
                  </div>
                  <div class="col-xs-3">
                    <input type="text" class="form-control datepicker" name="intervalEnd" placeholder="yyyy-mm-dd" />
                  </div>
                  <div class="col-xs-2">
                    <button type="submit" class="btn btn-default" name="addNewInterval" value="<?php echo $x; ?>"> <?php echo $lang['CLOSE_INTERVAL']; ?></button>
                  </div>
                </div>
              </div>

              <div class="container">
                <a data-toggle="collapse" href="#intervalCollapse<?php echo $x; ?>" aria-expanded="false" aria-controls="collapseExample">Show all intervals</a>
              </div>
              <!-- Corrections table -->
              <div class="container-fluid collapse" id="intervalCollapse<?php echo $x; ?>">
                <table class="table table-hover">
                  <thead>
                    <th>Start</th>
                    <th>End</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                    <th>Sun</th>
                    <th>Vacation (d)</th>
                    <th>Overtime (h)</th>
                    <th>Pause (h)</th>
                  </thead>
                  <tbody>
                    <?php
                    $intervalR = $conn->query("SELECT * FROM $intervalTable WHERE userID = $x AND endDate IS NOT NULL");
                    while($intervalR && $intRow =  $intervalR->fetch_assoc()){
                      echo "<tr>";
                      echo "<td>".substr($intRow['startDate'],0,10)."</td>";
                      echo "<td>".substr($intRow['endDate'],0,10)."</td>";
                      echo "<td>".$intRow['mon']."</td>";
                      echo "<td>".$intRow['tue']."</td>";
                      echo "<td>".$intRow['wed']."</td>";
                      echo "<td>".$intRow['thu']."</td>";
                      echo "<td>".$intRow['fri']."</td>";
                      echo "<td>".$intRow['sat']."</td>";
                      echo "<td>".$intRow['sun']."</td>";
                      echo "<td>".$intRow['vacPerYear']."</td>";
                      echo "<td>".$intRow['overTimeLump']."</td>";
                      echo "<td>".$intRow['hoursOfRest'] .'h after '. $intRow['pauseAfterHours']."h</td>";
                      echo "</tr>";
                    }
                     ?>
                  </tbody>
                </table>
              </div>
              <br><br>
              <div class="container-fluid">
                <div class="text-right">
                  <button type="button" class="btn btn-danger" data-toggle="modal" data-target=".bs-example-modal-sm<?php echo $x; ?>"><?php echo $lang['REMOVE_USER']; ?></button>
                  <button class="btn btn-warning" type="submit" name="submitUser" value="<?php echo $x; ?>" ><?php echo $lang['SAVE']; ?> </button>
                </div>
              </div>
              <br><br>

            <!-- Delete confirm modal -->
            <div class="modal fade bs-example-modal-sm<?php echo $x; ?>" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">
              <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                  <div class="modal-header">
                    <h4 class="modal-title">Do you really wish to delete <?php echo $firstname.' '.$lastname; ?> ?</h4>
                  </div>
                  <div class="modal-body">
                    All Stamps and Bookings belonging to this User will be lost forever. Do you still wish to proceed?
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">No, I'm sorry.</button>
                    <button class="btn btn-danger" type='submit' name='deleteUser' value="<?php echo $x; ?>"><?php echo $lang['REMOVE_USER']; ?></button>
                  </div>
                </div>
              </div>
            </div>

            </form>
            <!-- /CONTENT -->
          </div>
        </div>
      </div>
      <br>

      <?php
    }
  }
  ?>
  <br><br>
</div>

<script>
  //ALTER TABLE `userdata`  ADD `forcedPwdChange` TINYINT(1) NULL DEFAULT NULL  AFTER `privatePGPKey`;
  function forcePswChange(id,event){
    $.post("ajaxQuery/AJAX_db_utility.php",{function: "forcePwdChange",userid: id},function(data){
      if(data){
        console.log(data);
        event.target.innerHTML = event.target.innerHTML + "<i class='fa fa-check' ></i>"
      }else{
        event.target.innerHTML = event.target.innerHTML + "<i class='fa fa-times' ></i>"
      }
        
      });
  }
</script>
</div>
<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
