<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID); ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['USERS']; ?></h3>
</div>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if(isset($_POST['deactivate']) && $_POST['deactivate'] != 1){
    $x = $_POST['deactivate'];
    $acc = true;
    //copy user table
    $sql = "INSERT INTO $deactivatedUserTable(id, firstname, lastname, psw, sid, email, gender, overTimeLump, pauseAfterHours, hoursOfRest, beginningDate, exitDate, preferredLang, terminalPin, kmMoney)
    SELECT id, firstname, lastname, psw, sid, email, gender, overTimeLump, pauseAfterHours, hoursOfRest, beginningDate, exitDate, preferredLang, terminalPin, kmMoney FROM $userTable WHERE id = $x";
    if(!$conn->query($sql)){$acc = false; echo 'userErr: '.mysqli_error($conn);}
    //copy logs
    $sql = "INSERT INTO $deactivatedUserLogs(userID, time, timeEnd, status, timeToUTC, breakCredit, expectedHours, indexIM)
    SELECT userID, time, timeEnd, status, timeToUTC, breakCredit, expectedHours, indexIM FROM $logTable WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo 'logErr: '.mysqli_error($conn);}

    //copy unlogs
    $sql = "INSERT INTO $deactivatedUserUnLogs(userID, time, mon, tue, wed, thu, fri, sat, sun)
    SELECT userID, time, mon, tue, wed, thu, fri, sat, sun FROM $negative_logTable WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo '<br>unlogErr: '.mysqli_error($conn);}

    //copy timetable and vacationtable
    $sql = "INSERT INTO $deactivatedUserDataTable(userID, mon, tue, wed, thu, fri, sat, sun, vacationHoursCredit, daysPerYear)
    SELECT $bookingTable.userID, mon, tue, wed, thu, fri, sat, sun, vacationHoursCredit, daysPerYear FROM $bookingTable, $vacationTable WHERE $bookingTable.userID = $x AND $vacationTable.userID = $x";
    if(!$conn->query($sql)){$acc = false; echo '<br>dataErr: '.mysqli_error($conn);}

    //copy projectbookings
    $sql = "INSERT INTO $deactivatedUserProjects(start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType)
    SELECT start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType
    FROM $projectBookingTable INNER JOIN $logTable ON $logTable.indexIM = $projectBookingTable.timestampID WHERE $logTable.userID = $x";
    if(!$conn->query($sql)){$acc = false; echo '<br>projErr: '. mysqli_error($conn);}

    //copy taveldata
    $sql = "INSERT INTO $deactivatedUserTravels(userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses)
    SELECT userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses FROM $travelTable WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo '<br>travelErr: '.mysqli_error($conn);}

    //if successful, delete the user
    if($acc){
      $sql  = "DELETE FROM $userTable WHERE id = $x";
      if(!$conn->query($sql)){echo mysqli_error($conn);}
    }
  }

  if (isset($_POST['deleteUser'])) {
    $x = $_POST['deleteUser'];
    if ($x != 1) {
      $sql = "DELETE FROM $userTable WHERE id = $x;";
      $conn->query($sql);
    } else {
      echo $lang['ADMIN_DELETE'] ."<br>";
    }
    break;
  }

  if (isset($_POST['submitUser'])) {
    $x = $_POST['submitUser'];
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
      $exitDate = test_Input($_POST['exitDate'.$x]) . ' 00:00:00';
      $conn->query("UPDATE $userTable SET exitDate = '$exitDate' WHERE id = '$x'");
    }

    if (!empty($_POST['gender'.$x])) {
      $gender = test_input($_POST['gender'.$x]);
      $sql = "UPDATE $userTable SET gender= '$gender' WHERE id = '$x';";
      $conn->query($sql);
    }

    if (!empty($_POST['enableProjecting'.$x])) {
      $enableProjecting = test_input($_POST['enableProjecting'.$x]);
      $sql = "UPDATE $userTable SET enableProjecting= '$enableProjecting' WHERE id = '$x';";
      $conn->query($sql);
    }

    if (!empty($_POST['email'.$x]) && filter_var(test_input($_POST['email'.$x] .'@domain.com'), FILTER_VALIDATE_EMAIL)){
      $email = test_input($_POST['email'.$x]).'@';
      $sql = "UPDATE $userTable SET email = CONCAT('$email', SUBSTRING(email, LOCATE('@', email) + 1)) WHERE id = '$x';";
      $conn->query($sql);
    } else {
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Invalid E-mail </strong>May not be empty.';
      echo '</div>';
    }

    if (isset($_POST['overTimeAll'.$x]) && is_numeric(str_replace(',','.',$_POST['overTimeAll'.$x]))){
      $overTimeAll = str_replace(',','.',$_POST['overTimeAll'.$x]);
      $sql = "UPDATE $userTable SET overTimeLump= '$overTimeAll' WHERE id = '$x';";
      $conn->query($sql);
    }

    if (isset($_POST['daysPerYear'.$x]) && is_numeric($_POST['daysPerYear'.$x])){
      $vacDaysPerYear = $_POST['daysPerYear'.$x];
      $sql = "UPDATE $vacationTable SET daysPerYear= '$vacDaysPerYear' WHERE userID = '$x';";
      $conn->query($sql);
    }

    if (isset($_POST['vacDaysCredit'.$x]) && is_numeric($_POST['vacDaysCredit'.$x])){
      $vacDaysCredit = $_POST['vacDaysCredit'.$x];
      $sql = "UPDATE $vacationTable SET vacationHoursCredit= '$vacDaysCredit' WHERE userID = '$x';";
      $conn->query($sql);
    }

    if (isset($_POST['pauseAfter'.$x]) && is_numeric($_POST['pauseAfter'.$x])){
      $pauseAfter = $_POST['pauseAfter'.$x];
      $sql = "UPDATE $userTable SET pauseAfterHours= '$pauseAfter' WHERE id = '$x';";
      $conn->query($sql);
    }

    if (isset($_POST['rest'.$x]) && is_numeric($_POST['rest'.$x])){
      $rest = $_POST['rest'.$x];
      $sql = "UPDATE $userTable SET hoursOfRest= '$rest' WHERE id = '$x';";
      $conn->query($sql);
    }

    if(isset($_POST['mon'.$x]) && is_numeric($_POST['mon'.$x])){
      $mon = test_input($_POST['mon'.$x]);
      $sql = "UPDATE $bookingTable SET mon='$mon' WHERE userID = '$x'";
      $conn->query($sql);
    }

    if (isset($_POST['tue'.$x]) && is_numeric($_POST['tue'.$x])){
      $tue = test_input($_POST['tue'.$x]);
      $sql = "UPDATE $bookingTable SET tue='$tue' WHERE userID = '$x'";
      $conn->query($sql);
    }

    if (isset($_POST['wed'.$x]) && is_numeric($_POST['wed'.$x])){
      $wed = test_input($_POST['wed'.$x]);
      $sql = "UPDATE $bookingTable SET wed='$wed' WHERE userID = '$x'";
      $conn->query($sql);
    }

    if (isset($_POST['thu'.$x]) && is_numeric($_POST['thu'.$x])){
      $thu = test_input($_POST['thu'.$x]);
      $sql = "UPDATE $bookingTable SET thu='$thu' WHERE userID = '$x'";
      $conn->query($sql);
    }

    if (isset($_POST['fri'.$x]) && is_numeric($_POST['fri'.$x])){
      $fri = test_input($_POST['fri'.$x]);
      $sql = "UPDATE $bookingTable SET fri='$fri' WHERE userID = '$x'";
      $conn->query($sql);
    }

    if (isset($_POST['sat'.$x]) && is_numeric($_POST['sat'.$x])){
      $sat = test_input($_POST['sat'.$x]);
      $sql = "UPDATE $bookingTable SET sat='$sat' WHERE userID = '$x'";
      $conn->query($sql);
    }

    if (isset($_POST['sun'.$x]) && is_numeric($_POST['sun'.$x])){
      $sun = test_input($_POST['sun'.$x]);
      $sql = "UPDATE $bookingTable SET sun='$sun' WHERE userID = '$x'";
      $conn->query($sql);
    }

    if (!empty($_POST['password'.$x]) && !empty($_POST['passwordConfirm'.$x])) {
      $password = $_POST['password'.$x];
      $passwordConfirm = $_POST['passwordConfirm'.$x];
      if (strcmp($password, $passwordConfirm) == 0) {
        $psw = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE $userTable SET psw = '$psw' WHERE id = '$x';";
        $conn->query($sql);
      } else {
        echo '<div class="alert alert-danger fade in">';
        echo '<a href="" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
        echo '<strong>Did not change Passwords! </strong>Passwords did not match.';
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


    if(isset($_POST['isCoreAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isCoreAdmin = 'TRUE' WHERE userID = $x";
    } else {
      if($x != 1){
        $sql = "UPDATE $roleTable SET isCoreAdmin = 'FALSE' WHERE userID = $x";
      } else {
        $sql = "UPDATE $roleTable SET isCoreAdmin = 'TRUE' WHERE userID = $x";
      }
    }
    $conn->query($sql);

    if(isset($_POST['isTimeAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isTimeAdmin = 'TRUE' WHERE userID = $x";
    } else {
      $sql = "UPDATE $roleTable SET isTimeAdmin = 'FALSE' WHERE userID = $x";
    }
    $conn->query($sql);

    if(isset($_POST['isProjectAdmin'.$x])){
      $sql = "UPDATE $roleTable SET isProjectAdmin = 'TRUE' WHERE userID = $x";
    } else {
      $sql = "UPDATE $roleTable SET isProjectAdmin = 'FALSE' WHERE userID = $x";
    }
    $conn->query($sql);
    if(isset($_POST['canStamp'.$x])){
      $sql = "UPDATE $roleTable SET canStamp = 'TRUE' WHERE userID = $x";
    } else {
      $sql = "UPDATE $roleTable SET canStamp = 'FALSE' WHERE userID = $x";
    }
    $conn->query($sql);
    if(isset($_POST['canStamp'.$x]) && isset($_POST['canBook'.$x])){
      $sql = "UPDATE $roleTable SET canBook = 'TRUE' WHERE userID = $x";
    } else {
      $sql = "UPDATE $roleTable SET canBook = 'FALSE' WHERE userID = $x";
    }
    $conn->query($sql);

  }//end if isset submitX
}
?>

<div class="container-fluid panel-group" id="accordion" role="tablist" aria-multiselectable="true">
  <?php
  $mon=$tue=$wed=$thu=$fri=$sat=$sun=0;
  $firstname=$lastname=$email=$gender=$vacDays=$overTimeAll = $vacDaysCredit = $pauseAfter = $rest = $begin = $passErr= $coreTime = "";

  $query = "SELECT *, $userTable.id AS userID
  FROM $userTable INNER JOIN $bookingTable ON $userTable.id = $bookingTable.userID
  INNER JOIN $vacationTable ON $userTable.id = $vacationTable.userID
  INNER JOIN $roleTable ON $roleTable.userID = $userTable.id
  ORDER BY $userTable.id ASC";

  $result = mysqli_query($conn, $query);
  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $x = $row['userID'];
      $mon = $row['mon'];
      $tue = $row['tue'];
      $wed = $row['wed'];
      $thu = $row['thu'];
      $fri = $row['fri'];
      $sat = $row['sat'];
      $sun = $row['sun'];

      $firstname = $row['firstname'];
      $lastname = $row['lastname'];
      $gender = $row['gender'];
      $email = $row['email'];
      $begin = $row['beginningDate'];
      $end = $row['exitDate'];

      $vacDaysPerYear = $row['daysPerYear'];
      $vacDaysCredit = $row['vacationHoursCredit'];
      $overTimeAll = $row['overTimeLump'];
      $pauseAfter = $row['pauseAfterHours'];
      $rest = $row['hoursOfRest'];

      $isCoreAdmin = $row['isCoreAdmin'];
      $isTimeAdmin = $row['isTimeAdmin'];
      $isProjectAdmin = $row['isProjectAdmin'];
      $canBook = $row['canBook'];
      $canStamp = $row['canStamp'];

      $eOut = "$firstname $lastname";
      ?>

      <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="heading<?php echo $x; ?>">
          <h4 class="panel-title">
            <div class="row">
              <div class="col-md-6">
                <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $x; ?>" aria-expanded="false">
                  <?php echo $eOut; ?>
                </a>
              </div>
              <div class="col-md-6 text-right">
                <form method="post">
                  <button type='submit' value="<?php echo $x; ?>" name='deactivate' style="background:none; border:none;" title="<?php echo $lang['DEACTIVATE']; ?>">
                    <img width="10px" height="10px" src="../images/minus_circle.png" />
                  </button>
                </form>
              </div>
            </div>
          </h4>
        </div>
        <div id="collapse<?php echo $x; ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="heading<?php echo $x; ?>">
          <div class="panel-body">

            <!-- #########  CONTENT ######## -->

            <form method="POST">
              <div class=container-fluid>
                <div class=form-group>
                  <div class="input-group">
                    <span class="input-group-addon" style=min-width:150px><?php echo $lang['FIRSTNAME'] ?></span>
                    <input type="text" class="form-control" name="firstname<?php echo $x; ?>" value="<?php echo $firstname; ?>">
                  </div>
                </div>
                <div class=form-group>
                  <div class="input-group">
                    <span class="input-group-addon" style=min-width:150px><?php echo $lang['LASTNAME'] ?></span>
                    <input type="text" class="form-control" name="lastname<?php echo $x; ?>" value="<?php echo $lastname; ?>">
                  </div>
                </div>
                <div class=form-group>
                  <div class="input-group">
                    <span class="input-group-addon" style=min-width:150px>E-Mail</span>
                    <input type="text" class="form-control" name="email<?php echo $x; ?>" value="<?php echo explode('@', $email)[0]; ?>"/>
                    <span class="input-group-addon" style=min-width:150px>@<?php echo explode('@', $email)[1]; ?></span>
                  </div>
                </div>
                <div class=form-group>
                  <div class="input-group">
                    <span class="input-group-addon" style=min-width:150px><?php echo $lang['NEW_PASSWORD']; ?></span>
                    <input type="password" class="form-control" name="password<?php echo $x; ?>" placeholder="Password">
                  </div>
                </div>
                <div class=form-group>
                  <div class="input-group">
                    <span class="input-group-addon" style=min-width:150px><?php echo $lang['NEW_PASSWORD_CONFIRM']; ?></span>
                    <input type="password" class="form-control" name="passwordConfirm<?php echo $x; ?>" placeholder="Password Confirm">
                  </div>
                </div>
              </div>
              <br>
              <div class="container-fluid">
                <div class=col-md-3>
                  <?php echo $lang['ENTRANCE_DATE'] .'<p class="form-control" style="background-color:#ececec">'. substr($begin,0,10); ?></p>
                </div>
                <div class=col-md-3>
                  <?php echo $lang['EXIT_DATE']; ?>
                  <input type="text" class="form-control" name="exitDate<?php echo $x; ?>" value="<?php echo substr($end,0,10); ?>"/>
                </div>
                <div class=col-md-3>
                  <?php echo $lang['VACATION_DAYS_PER_YEAR']; ?>
                  <input type="number" class="form-control" name="daysPerYear<?php echo $x; ?>" value="<?php echo $vacDaysPerYear; ?>"/>
                </div>
                <div class=col-md-3>
                  <?php echo $lang['AMOUNT_VACATION_DAYS']; ?>: <br>
                  <input type="number" class="form-control" step=any  name="vacDaysCredit<?php echo $x; ?>" value="<?php echo number_format($vacDaysCredit/24, '.', ''); ?>"/>
                </div>
              </div>
              <br>
              <div class="container-fluid">
                <div class=col-md-3>
                  <?php echo $lang['OVERTIME_ALLOWANCE']; ?>: <br>
                  <input type="number" class="form-control" name="overTimeAll<?php echo $x; ?>" value="<?php echo $overTimeAll; ?>"/>
                </div>
                <div class=col-md-3>
                  <?php echo $lang['TAKE_BREAK_AFTER']; ?>: <input type="number" class="form-control" step=any  name="pauseAfter<?php echo $x; ?>" value="<?php echo $pauseAfter; ?>"/>
                </div>
                <div class=col-md-3>
                  <?php echo $lang['HOURS_OF_REST']; ?>: <input type="number" class="form-control" step=any  name="rest<?php echo $x; ?>" value="<?php echo $rest; ?>"/>
                </div>
              </div>
              <br><br>
              <div class=container-fluid>
                <div class="col-md-3">
                  Module: <br>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="isCoreAdmin<?php echo $x; ?>" <?php if($isCoreAdmin == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_CORE_OPTIONS']; ?><br>
                    </label>
                    <label>
                      <input type="checkbox" name="isTimeAdmin<?php echo $x; ?>" <?php if($isTimeAdmin == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_TIME_OPTIONS']; ?><br>
                    </label>
                    <label>
                      <input type="checkbox" name="isProjectAdmin<?php echo $x; ?>" <?php if($isProjectAdmin == 'TRUE'){echo 'checked';} ?>><?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?><br>
                    </label>
                  </div>
                </div>
                <div class="col-md-3">
                  <?php echo $lang['ALLOW_PRJBKING_ACCESS']; ?>: <br>
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="canStamp<?php echo $x; ?>" <?php if($canStamp == 'TRUE'){echo 'checked';} ?>><?php echo $lang['CAN_CHECKIN']; ?>
                    </label>
                    <br>
                    <label>
                      <input type="checkbox" name="canBook<?php echo $x; ?>" <?php if($canBook == 'TRUE'){echo 'checked';} ?>><?php echo $lang['CAN_BOOK']; ?>
                    </label>
                  </div>
                </div>
                <div class="col-md-3">
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
                </div>
                <div class="col-md-3">
                  <?php echo $lang['GENDER']; ?>: <br>
                  <div class="radio">
                    <label>
                      <input type="radio" name="gender<?php echo $x; ?>" value="female" <?php if($gender == 'female'){echo 'checked';} ?> >Female <br>
                    </label>
                  </div>
                  <div class="radio">
                    <label>
                      <input type="radio" name="gender<?php echo $x; ?>" value="male" <?php if($gender == 'male'){echo 'checked';} ?> >Male
                    </label>
                  </div>
                </div>
              </div>
              <br><br>

              <div class=container-fluid>
                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-addon">Mon</span>
                    <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="mon<?php echo $x; ?>" size=2 value= <?php echo $mon; ?>>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-addon">Tue</span>
                    <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="tue<?php echo $x; ?>" size=2 value= <?php echo $tue; ?>>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-addon">Wed</span>
                    <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="wed<?php echo $x; ?>" size=2 value= <?php echo $wed; ?>>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-addon">Thu</span>
                    <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="thu<?php echo $x; ?>" size=2 value= <?php echo $thu; ?>>
                  </div>
                </div>
              </div>
              <br>
              <div class=container-fluid>
                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-addon">Fri</span>
                    <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="fri<?php echo $x; ?>" size=2 value= <?php echo $fri; ?>>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-addon">Sat</span>
                    <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="sat<?php echo $x; ?>" size=2 value= <?php echo $sat; ?>>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-addon">Sun</span>
                    <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="sun<?php echo $x; ?>" size=2 value= <?php echo $sun; ?>>
                  </div>
                </div>
              </div>
              <br><br>

              <div class="container-fluid">
                <div class="text-right">
                  <button type="button" class="btn btn-danger" data-toggle="modal" data-target=".bs-example-modal-sm<?php echo $x; ?>"><?php echo $lang['REMOVE_USER']; ?></button>
                  <button class="btn btn-warning" type="submit" name="submitUser" value="<?php echo $x; ?>" ><?php echo $lang['SAVE']; ?> </button>
                </div>
              </div>
              <br><br>
            </form>

            <!-- Small modal -->
            <div class="modal fade bs-example-modal-sm<?php echo $x; ?>" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">
              <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">

                  <div class="modal-header">
                    <h4 class="modal-title">Do you really wish to delete <?php $firstname.' '.$lastname; ?> ?</h4>
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

            <!-- /CONTENT -->
          </div>
        </div>
      </div>

      <?php
    }
  }
  ?>
  <br><br>
</div>

<div class="container-fluid">
  <div class="text-right">
    <a class="btn btn-warning" href='register_choice.php'><?php echo $lang['REGISTER_NEW_USER']; ?></a>
  </div>
  <!--div class="col-xs-6 text-right">
  <a class="btn btn-warning" href='editUsers_onDate.php'>Make Changes on Date</a>
</div-->
</div>
<!-- /BODY -->
<?php include 'footer.php'; ?>
