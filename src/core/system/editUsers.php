<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php enableToCore($userID);?>
<!-- BODY -->
<div class="page-header-fixed">
    <div class="page-header">
      <h3><?php echo $lang['USERS']; ?><div class="page-header-button-group"><a class="btn btn-default" href='register' title="<?php echo $lang['REGISTER']; ?>">+</a></div></h3>
    </div>
</div>
<div class="page-content-fixed-100">
<?php
$activeTab = 0;
if(isset($_GET['ACT'])){ $activeTab = $_GET['ACT']; }
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if(isset($_POST['deactivate']) && $_POST['deactivate'] != 1 && $_POST['deactivate'] != $userID){
    $x = $_POST['deactivate'];
    $acc = true;
    //copy user table
    $sql = "INSERT IGNORE INTO $deactivatedUserTable(id, firstname, lastname, psw, sid, email, gender, beginningDate, exitDate, preferredLang, terminalPin, kmMoney)
    SELECT id, firstname, lastname, psw, sid, email, gender, beginningDate, exitDate, preferredLang, terminalPin, kmMoney FROM UserData WHERE id = $x";
    if(!$conn->query($sql)){$acc = false; echo 'userErr: '.mysqli_error($conn);}
    //copy logs
    $sql = "INSERT IGNORE INTO $deactivatedUserLogs(userID, time, timeEnd, status, timeToUTC, indexIM)
    SELECT userID, time, timeEnd, status, timeToUTC, indexIM FROM logs WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo 'logErr: '.mysqli_error($conn);}
    //copy intervalTable
    $sql = "INSERT IGNORE INTO $deactivatedUserDataTable(userID, mon, tue, wed, thu, fri, sat, sun, vacPerYear, overTimeLump, pauseAfterHours, hoursOfRest, startDate, endDate)
    SELECT userID, mon, tue, wed, thu, fri, sat, sun, vacPerYear, overTimeLump, pauseAfterHours, hoursOfRest, startDate, endDate FROM $intervalTable WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo '<br>dataErr: '.mysqli_error($conn);}
    //copy projectbookings
    $sql = "INSERT IGNORE INTO $deactivatedUserProjects(start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit)
    SELECT start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit
    FROM $projectBookingTable, logs WHERE logs.indexIM = $projectBookingTable.timestampID AND logs.userID = $x AND projectID IS NOT NULL AND projectID != 0";
    if(!$conn->query($sql)){$acc = false; echo '<br>projErr: '. mysqli_error($conn);}
    //copy projectbookings - foreign key null gets cast to 0... idky.
    $sql = "INSERT IGNORE INTO $deactivatedUserProjects(start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit)
    SELECT start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit
    FROM $projectBookingTable, logs WHERE logs.indexIM = $projectBookingTable.timestampID AND logs.userID = $x AND projectID != 0 AND projectID IS NOT NULL";
    if(!$conn->query($sql)){$acc = false; echo '<br>projErr: '. mysqli_error($conn);}
    //copy projectbookings - null for every null, which is 0 #why
    $sql = "INSERT IGNORE INTO $deactivatedUserProjects(start, end, projectID, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit)
    SELECT start, end, NULL, timestampID, infoText, booked, internInfo, chargedTimeStart, chargedTimeEnd, bookingType, mixedStatus, extra_1, extra_2, extra_3, exp_info, exp_price, exp_unit
    FROM $projectBookingTable, logs WHERE logs.indexIM = $projectBookingTable.timestampID AND logs.userID = $x AND (projectID = 0 OR projectID IS NULL)";
    if(!$conn->query($sql)){$acc = false; echo '<br>projErr: '. mysqli_error($conn);}
    //copy taveldata
    $sql = "INSERT IGNORE INTO $deactivatedUserTravels(userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses)
    SELECT userID, countryID, travelDayStart, travelDayEnd, kmStart, kmEnd, infoText, hotelCosts, hosting10, hosting20, expenses FROM $travelTable WHERE userID = $x";
    if(!$conn->query($sql)){$acc = false; echo '<br>travelErr: '.mysqli_error($conn);}
    //if successful, delete the user, On Cascade Delete does the rest.
    if($acc){
      if(!$conn->query("DELETE FROM UserData WHERE id = $x")){echo mysqli_error($conn);}
    }
  } elseif(isset($_POST['deactivate'])){
    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ADMIN_DELETE'].'</div>';
  }

  if (isset($_POST['deleteUser'])) {
    $x = $_POST['deleteUser'];
    if ($x != 1 && $x != $userID)  {
      $conn->query("DELETE FROM UserData WHERE id = $x;");
    } else {
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ADMIN_DELETE'].'</div>';
    }
  }

  $overTimeAll = $vacDaysPerYear = $pauseAfter = $rest = $mon = $tue = $wed = $thu = $fri = $sat = $sun = 0;
  if (isset($_POST['overTimeAll']) && is_numeric(str_replace(',','.',$_POST['overTimeAll']))){
      $overTimeAll = str_replace(',','.',$_POST['overTimeAll']);
  }
  if (isset($_POST['daysPerYear']) && is_numeric($_POST['daysPerYear'])){
      $vacDaysPerYear = intval($_POST['daysPerYear']);
  }
  if (isset($_POST['pauseAfter']) && is_numeric($_POST['pauseAfter'])){
      $pauseAfter = $_POST['pauseAfter'];
  }
  if (isset($_POST['rest']) && is_numeric($_POST['rest'])){
      $rest = $_POST['rest'];
  }
  if (isset($_POST['mon']) && is_numeric($_POST['mon'])){
      $mon = test_input($_POST['mon']);
  }
  if (isset($_POST['tue']) && is_numeric($_POST['tue'])){
      $tue = test_input($_POST['tue']);
  }
  if (isset($_POST['wed']) && is_numeric($_POST['wed'])){
      $wed = test_input($_POST['wed']);
  }
  if (isset($_POST['thu']) && is_numeric($_POST['thu'])){
      $thu = test_input($_POST['thu']);
  }
  if (isset($_POST['fri']) && is_numeric($_POST['fri'])){
      $fri = test_input($_POST['fri']);
  }
  if (isset($_POST['sat']) && is_numeric($_POST['sat'])){
      $sat = test_input($_POST['sat']);
  }
  if (isset($_POST['sun']) && is_numeric($_POST['sun'])){
      $sun = test_input($_POST['sun']);
  }

  if(isset($_POST['addNewInterval']) && !empty($_POST['intervalEnd']) && test_Date($_POST['intervalEnd'].' 05:00:00')){
      $activeTab = $x = $_POST['addNewInterval'];
      $intervalEnd = $_POST['intervalEnd'].' 05:00:00';
      //close up the old one
      $conn->query("UPDATE $intervalTable SET mon='$mon', tue='$tue', wed='$wed', thu='$thu', fri='$fri', sat='$sat', sun='$sun', vacPerYear='$vacDaysPerYear',
          overTimeLump='$overTimeAll', pauseAfterHours='$pauseAfter', hoursOfRest='$rest', endDate='$intervalEnd' WHERE userID = $x AND endDate IS NULL");
      //create a new one
      $conn->query("INSERT INTO $intervalTable (userID, mon, tue, wed, thu, fri, sat, sun, vacPerYear, overTimeLump, pauseAfterHours, hoursOfRest, startDate)
      VALUES($x, '$mon', '$tue', '$wed', '$thu', '$fri', '$sat', '$sun', '$vacDaysPerYear', '$overTimeAll', '$pauseAfter', '$rest', '$intervalEnd')");

      echo mysqli_error($conn);
  }

  if (isset($_POST['submitUser'])) {
      $activeTab = $x = $_POST['submitUser'];
      if (!empty($_POST['firstname'])) {
          $val = test_input($_POST['firstname']);
          $conn->query("UPDATE UserData SET firstname= '$val' WHERE id = '$x';");
      }
      if (!empty($_POST['lastname'])) {
          $val = test_input($_POST['lastname']);
          $conn->query("UPDATE UserData SET lastname= '$val' WHERE id = '$x';");
      }
      if(!empty($_POST['exitDate']) && test_Date($_POST['exitDate'] .' 00:00:00')) {
          $val = test_input($_POST['exitDate']) . ' 00:00:00';
          $conn->query("UPDATE UserData SET exitDate = '$val' WHERE id = '$x'");
      }
      if(!empty($_POST['coreTime'])) {
          $val = test_input($_POST['coreTime']);
          $conn->query("UPDATE UserData SET coreTime = '$val' WHERE id = '$x'");
      }
      if (!empty($_POST['supervisor'])){
          $val = intval($_POST['supervisor']);
          $conn->query("UPDATE UserData SET supervisor = $val WHERE id = $x");
      }
      if (!empty($_POST['email']) && filter_var(test_input($_POST['email'] .'@domain.com'), FILTER_VALIDATE_EMAIL)){
          $val = test_input($_POST['email']).'@';
          $conn->query("UPDATE UserData SET email = CONCAT('$val', SUBSTRING(email, LOCATE('@', email) + 1)) WHERE id = '$x';");
      } else {
          echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['ERROR_EMAIL'].'</div>';
      }
	  if (!empty($_POST['real_email']) && filter_var(test_input($_POST['real_email']), FILTER_VALIDATE_EMAIL)){
          $val = test_input($_POST['real_email']);
          $conn->query("UPDATE UserData SET real_email = '$val' WHERE id = '$x';");
      } elseif(!empty($_POST['real_email'])) {
          echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['ERROR_EMAIL'].__LINE__.'</div>';
      }
      if (!empty($_POST['gender'])) {
          $val = test_input($_POST['gender']);
		  $conn->query("UPDATE UserData SET gender= '$val' WHERE id = '$x';");
	  }
	  if(!empty($_POST['main_company'])){
		  $val = intval($_POST['main_company']);
		  $conn->query("UPDATE UserData SET companyID = $val WHERE id = '$x'");
	  }
	  if(!empty($_POST['canLogin'])){ //5b2931a15ad87
		  $conn->query("UPDATE UserData SET canLogin = 'TRUE' WHERE id = $x");
	  } else {
		  $conn->query("UPDATE UserData SET canLogin = 'FALSE' WHERE id = $x");
	  }
      if (!empty($_POST['password']) && !empty($_POST['passwordConfirm'])) {

          if (strcmp($_POST['password'], $_POST['passwordConfirm']) == 0  && match_passwordpolicy($_POST['password'])) {
              $psw = password_hash($_POST['password'], PASSWORD_BCRYPT);
              if($x == $userID){
                  $private_encrypt = simple_encryption($privateKey, $_POST['password']);
				  $conn->query("UPDATE security_users SET privateKey = '$private_encrypted' WHERE userID = $userID AND outDated = 'FALSE'");
                  $conn->query("UPDATE UserData SET psw = '$psw', lastPswChange = UTC_TIMESTAMP WHERE id = '$userID'");
              } else {
				  //hard reset. user will loose ability to decrypt his access with this
                  $keyPair = sodium_crypto_box_keypair();
                  $private = base64_encode(sodium_crypto_box_secretkey($keyPair));
                  $user_public = sodium_crypto_box_publickey($keyPair);

                  $private_encrypt = simple_encryption($private, $_POST['password']);
                  $conn->query("UPDATE UserData SET psw = '$psw', lastPswChange = UTC_TIMESTAMP, forcedPwdChange = 1 WHERE id = '$x';");
				  $err = $conn->error;
				  $conn->query("UPDATE security_users SET outDated = 'TRUE' WHERE userID = $x");
				  $err = $conn->error;
				  $conn->query("UPDATE security_access SET outDated = 'TRUE' WHERE userID = $x");
				  $err .= $conn->error;
				  $conn->query("INSERT INTO security_users (userID, publicKey, privateKey) VALUES ($x, '".base64_encode($user_public)."', '$private_encrypt' )");
				  $err .= $conn->error;
				  if($err){
					  showError($err);
				  } else {
					  showSuccess('Passwort wurde erfolgreich geändert');
				  }
              }
          } else {
			  showError('Could not change Passwords! Passwords did not match or were invalid.
			  Password must be at least 8 characters long and contain at least one Capital Letter, one number and one special character');
          }
      }

    //update latest interval
    $conn->query("UPDATE $intervalTable SET mon='$mon', tue='$tue', wed='$wed', thu='$thu', fri='$fri', sat='$sat', sun='$sun', vacPerYear='$vacDaysPerYear',
        overTimeLump='$overTimeAll', pauseAfterHours='$pauseAfter', hoursOfRest='$rest' WHERE userID = $x AND endDate IS NULL");

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
} //end POST

if(isset($_SESSION["LAST_ERRORS"])){
  foreach ($_SESSION["LAST_ERRORS"] as $err) {
    echo $err;
  }
  $_SESSION["LAST_ERRORS"] = array();
}
$selection_main_company = '';
$result = $conn->query("SELECT id, name FROM companyData");
while($row = $result->fetch_assoc()){
    $selection_main_company .= '<option value="'.$row['id'].'">' . $row['name'] .'</option>';
}
$stmt_company_relationship = $conn->prepare("SELECT companyID FROM relationship_company_client WHERE userID = ?");
$stmt_company_relationship->bind_param('i', $x);
?>
<br>

<div class="container-fluid panel-group" id="accordion">
  <?php
  $result = $conn->query("SELECT *, UserData.id AS user_id FROM UserData
  INNER JOIN $intervalTable ON $intervalTable.userID = UserData.id
  LEFT JOIN socialprofile ON socialprofile.userID = UserData.id
  WHERE endDate IS NULL ORDER BY UserData.id ASC");
  if ($result && $result->num_rows > 0):
    while ($row = $result->fetch_assoc()):
      $x = $row['user_id'];
      $profilePicture = $row['picture'] ? "data:image/jpeg;base64,".base64_encode($row['picture']) : "images/defaultProfilePicture.png";
      ?>

      <div class="panel panel-default">
        <div class="panel-heading" id="heading<?php echo $x; ?>">
          <h4 class="panel-title">
            <div class="row">
              <div class="col-md-6">
                <a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?php echo $x; ?>">
                  <?php echo $row['firstname'].' '.$row['lastname']; ?>
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

        <div id="collapse<?php echo $x; ?>" class="panel-collapse collapse <?php if($x == $activeTab) echo 'in'; ?>">
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
				<div class="row form-group">
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon" style="min-width:150px"><?php echo $lang['FIRSTNAME'] ?></span>
							<input type="text" class="form-control" name="firstname" value="<?php echo $row['firstname']; ?>">
						</div>
					</div>
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon" style="min-width:150px"><?php echo $lang['LASTNAME'] ?></span>
							<input type="text" class="form-control" name="lastname" value="<?php echo $row['lastname']; ?>">
						</div>
					</div>
				</div>
				<div class="container-fluid">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon" style="min-width:150px">Login Adresse</span>
							<input type="text" class="form-control" name="email" value="<?php echo explode('@', $row['email'])[0]; ?>"/>
							<span class="input-group-addon" style="min-width:150px">@<?php echo explode('@', $row['email'])[1]; ?></span>
						</div>
					</div>
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon" style="min-width:150px">E-Mail Adresse</span>
							<input type="email" class="form-control" name="real_email" value="<?php echo $row['real_email']; ?>"/>
						</div>
					</div>
				</div>
				<div class="row form-group">
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon" style="min-width:150px"><?php echo $lang['NEW_PASSWORD']; ?></span>
							<input type="password" class="form-control" name="password" placeholder="* * * *">
						</div>
						<small>Achtung: Benutzer verliert dadurch jeden seiner Zugriffe. Erzwingt eine Passwortänderung.</small>
					</div>
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon" style="min-width:150px"><?php echo $lang['NEW_PASSWORD_CONFIRM']; ?></span>
							<input type="password" class="form-control" name="passwordConfirm" placeholder="* * * *">
						</div>
					</div>
				</div>
              <div class="row">
                  <div class="col-md-2">
                    <?php echo $lang['GENDER']; ?>:
                  </div>
                  <div class="col-md-2">
                    <label>
                      <input type="radio" name="gender" value="female" <?php if($row['gender'] == 'female'){echo 'checked';} ?> ><i class="fa fa-venus"></i><?php echo $lang['GENDER_TOSTRING']['female']; ?> <br>
                    </label>
                  </div>
                  <div class="col-md-2">
                    <label>
                      <input type="radio" name="gender" value="male" <?php if($row['gender'] == 'male'){echo 'checked';} ?> ><i class="fa fa-mars"></i><?php echo $lang['GENDER_TOSTRING']['male']; ?>
                    </label>
                  </div>
              </div>
              <div class="row">
                  <div class="col-md-2">
                    <?php echo $lang['SUPERVISOR']; ?>:
                  </div>
                  <div class="col-md-3">
                    <select name="supervisor" class="js-example-basic-single" >
                    <option value="0"> ... </option>
                    <?php
                    foreach($userID_toName as $id => $name){
                      $selected = ($row['supervisor'] == $id) ? 'selected' : '';
                      echo '<option '.$selected.' value="'.$id.'" >'.$name.'</option>';
                    }
                    ?>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <?php echo 'Letzte Passwort Änderung:'; ?>
                  </div>
                  <div class="col-md-2">
                      <?php echo date('d.m.Y', strtotime($row['lastPswChange'])); ?>
                  </div>
                  <div class="col-md-3" >
                    <button type="button" class="btn btn-link" onClick="forcePswChange(<?php echo $x; ?>,event)"
						title="Der Benutzer wird beim nächsten Login dazu aufgefordert, sein Passwort zu ändern." >Letzte Passwort Änderung Erzwingen</button>
                  </div>
              </div>
              <div class="row">
                  <div class="col-md-2">
                      Hauptmandant:
                  </div>
                  <div class="col-md-3">
                      <select name="main_company" class="js-example-basic-single"> <option value=""> .... </option>
                          <?php echo str_replace('<option value="'.$row['companyID'].'">', '<option selected value="'.$row['companyID'].'">', $selection_main_company); ?>
                      </select>
                  </div>
                  <div class="col-md-2">
                    <?php echo 'Letzter Login:'; ?>
                  </div>
                  <div class="col-md-5">
                      <?php if($row['lastLogin']) echo date('d.m.Y H:i', strtotime($row['lastLogin']) + $timeToUTC * 3600); //5ac7126421a8b ?>
                  </div>
              </div>
			  <div class="row checkbox"> <div class="col-md-2"> Login Berechtigung: </div>
				  <div class="col-md-3">
					  <label> <input type="checkbox" name="canLogin" <?php if($row['canLogin'] == 'TRUE'){echo 'checked';} ?> /> <?php echo $lang['CAN_LOGIN'];?> </label>
				  </div>
			  </div>
              <div class="row">
                <div class="col-md-5">
                  <?php echo $lang['ENTRANCE_DATE'] .'<p class="form-control" style="background-color:#ececec">'. substr($row['beginningDate'],0,10); ?></p>
                </div>
                <div class="col-md-2">
                  <?php echo $lang['CORE_TIME']; ?>
                  <p><input type="text" class="form-control timepicker" name="coreTime" value="<?php echo $row['coreTime']; ?>" /></p>
                </div>
                <div class="col-md-5">
                  <?php echo $lang['EXIT_DATE']; ?>
                  <input type="text" class="form-control datepicker" name="exitDate" value="<?php echo substr($row['exitDate'],0,10); ?>"/>
                </div>
              </div>
              <!-- ROLES AND COMPANY MOVED TO SECURITY -->
              <!-- Interval table -->
              <div class="container-fluid well">
                <div class="row">
                  <div class="col-md-3">
                    <?php echo $lang['OVERTIME_ALLOWANCE']; ?>: <br>
                    <input type="number" class="form-control" name="overTimeAll" value="<?php echo $row['overTimeLump']; ?>"
                     data-toggle="popover" title="Important!" data-trigger="focus" data-content="This value will always be read at the end of each month."/>
                  </div>
                  <div class="col-md-3">
                    <?php echo $lang['TAKE_BREAK_AFTER']; ?>: <input type="number" class="form-control" step=any  name="pauseAfter" value="<?php echo $row['pauseAfterHours']; ?>"/>
                  </div>
                  <div class="col-md-3">
                    <?php echo $lang['HOURS_OF_REST']; ?>: <input type="number" class="form-control" step=any  name="rest" value="<?php echo $row['hoursOfRest']; ?>"/>
                  </div>
                  <div class="col-md-3">
                    <?php echo $lang['VACATION_DAYS']. $lang['PER_YEAR']; ?>
                    <input type="number" class="form-control" name="daysPerYear" value="<?php echo $row['vacPerYear']; ?>"/>
                  </div>
                </div>
                <br>
                <div class="row">
                    <div class="col-md-7">
                        <div style="width:23%; float:left;">
                            <?php echo $lang['WEEKDAY_TOSTRING']['mon']; ?>
                            <input type="number" step="any" class="form-control" name="mon" size="2" value= "<?php echo $row['mon']; ?>" />
                        </div>
                        <div style="width:23%; float:left; margin-left:3%">
                            <?php echo $lang['WEEKDAY_TOSTRING']['tue']; ?>
                            <input type="number" step="any" class="form-control" name="tue" size="2" value= "<?php echo $row['tue']; ?>" />
                        </div>
                        <div style="width:22%; float:left; margin-left:3%">
                            <?php echo $lang['WEEKDAY_TOSTRING']['wed']; ?>
                            <input type="number" step="any" class="form-control" name="wed" size="2" value= "<?php echo $row['wed']; ?>" />
                        </div>
                        <div style="width:23%; float:left; margin-left:3%">
                            <?php echo $lang['WEEKDAY_TOSTRING']['thu']; ?>
                            <input type="number" step="any" class="form-control" name="thu" size="2" value= "<?php echo $row['thu']; ?>" />
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div style="width:31%; float:left;">
                            <?php echo $lang['WEEKDAY_TOSTRING']['fri']; ?>
                            <input type="number" step="any" class="form-control" name="fri" size="2" value= "<?php echo $row['fri']; ?>" />
                        </div>
                        <div style="width:32%; float:left; margin-left:3%">
                            <?php echo $lang['WEEKDAY_TOSTRING']['sat']; ?>
                            <input type="number" step="any" class="form-control" name="sat" size="2" value= "<?php echo $row['sat']; ?>" />
                        </div>
                        <div style="width:31%; float:left; margin-left:3%">
                            <?php echo $lang['WEEKDAY_TOSTRING']['sun']; ?>
                            <input type="number" step="any" class="form-control" name="sun" size="2" value= "<?php echo $row['sun']; ?>" />
                        </div>
                    </div>
                </div>
              </div>
              <div class="container-fluid well">
                <div class="row">
                  <div class="col-md-4">
                    <?php echo $lang['VALID_PERIOD'].' ('.$lang['FROM'].' - '.$lang['TO'].')'; ?>:
                  </div>
                  <div class="col-xs-3">
                    <input type="text" readonly class="form-control" value="<?php echo substr($row['startDate'],0,10); ?>" />
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
            <div class="modal fade bs-example-modal-sm<?php echo $x; ?>" tabindex="-1">
              <div class="modal-dialog modal-md">
                <div class="modal-content">
                  <div class="modal-header">
                    <h4 class="modal-title"><?php echo sprintf($lang['ASK_DELETE'], $row['firstname'].' '.$row['lastname']); ?></h4>
                  </div>
                  <div class="modal-body">
                    Alle zugehörigen Daten zu diesem Benutzer werden gelöscht. Trotzdem fortfahren?
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
  endwhile;
endif;
$stmt_company_relationship->close();
  ?>
  <br><br>
</div>

<script>
function forcePswChange(id,event){
    $.post("ajaxQuery/AJAX_db_utility.php",{function: "forcePwdChange",userid: id},function(data){
        if(data){
            event.target.innerHTML = event.target.innerHTML + "<i class='fa fa-check' ></i>"
        } else {
            event.target.innerHTML = event.target.innerHTML + "<i class='fa fa-times' ></i>"
        }
    });
}
$(document).ready(function(){
    $('[data-toggle="popover"]').popover({
        container: 'body'
    });
});
</script>
</div>
<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
