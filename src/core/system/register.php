<?php include dirname(dirname(__DIR__)) . '/header.php'; ?>
<?php enableToCore($userID)?>
<!-- BODY -->

<div class="page-header">
<h3>Register</h3>
</div>

<?php
use PHPMailer\PHPMailer\PHPMailer;
$step = 1;
$firstname = $lastname = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  //step 1
  if (!empty($_POST["firstname"]) && !empty($_POST["lastname"])) {
    $firstname = test_input($_POST["firstname"]);
    $lastname = test_input($_POST['lastname']);
    $step = 2;
  }

  $t = strtotime(carryOverAdder_Hours(getCurrentTimestamp(), 24));
  $begin = date('Y-m-d', strtotime('last Monday', $t)). ' 01:00:00';
  if(substr($begin, 5, 2) != substr(getCurrentTimestamp(), 5, 2)){ //different month
    $begin = date('Y-m-01'). ' 01:00:00';
  }
  $pass = randomPassword();

  $result = $conn->query("SELECT email FROM $userTable");
  $row = $result->fetch_assoc();
  $emailpostfix = strrchr($row['email'], "@");

  if(empty($emailpostfix)){
    echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo 'Could not split Domain: Please check the email Adress of your admin Account in DB.</div>';
    die();
  }

  //step 2
  if(isset($_POST['create'])){
    $accept = true;
    $gender = $_POST['gender'];
    if(!empty($_POST['entryDate']) && test_Date($_POST['entryDate'] ." 05:00:00")){
      $begin = $_POST['entryDate'] ." 05:00:00";
    } else {
      $accept = false;
    }
    if(!empty($_POST['mail']) && filter_var($_POST['mail'].$emailpostfix, FILTER_VALIDATE_EMAIL)){
      $email = test_input($_POST['mail']) .$emailpostfix;
      $mail_result = $conn->query("SELECT email FROM $userTable WHERE email = '$email'");
      if($mail_result && $mail_result->num_rows > 0){
        $accept = false;
        echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert">&times;</a>'.$lang['ERROR_EXISTING_EMAIL'].'</div>';
      }
    } else {
      $accept = false;
      echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert">&times;</a>Invalid E-Mail Address.</div>';
    }

    if(!empty($_POST['yourPas']) && match_passwordpolicy($_POST['yourPas'], $out)){
      $pass = test_input($_POST['yourPas']);
    } else {
      $accept = false;
      echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert">&times;</a>Invalid Password.</div>';
    }

    if(is_numeric($_POST['overTimeLump']) && is_numeric($_POST['pauseAfter']) && is_numeric($_POST['hoursOfRest']) && is_numeric($_POST['vacDaysPerYear'])){
      $vacDaysPerYear = $_POST['vacDaysPerYear'];
      $overTimeLump = $_POST['overTimeLump'];
      $pauseAfter = $_POST['pauseAfter'];
      $hoursOfRest = $_POST['hoursOfRest'];
    } else {
      $accept = false;
      echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    }

    if (is_numeric($_POST['mon']) && is_numeric($_POST['tue']) && is_numeric($_POST['wed']) && is_numeric($_POST['thu']) && is_numeric($_POST['fri']) && is_numeric($_POST['sat']) && is_numeric($_POST['sun'])) {
      $mon = $_POST['mon'];
      $tue = $_POST['tue'];
      $wed = $_POST['wed'];
      $thu = $_POST['thu'];
      $fri = $_POST['fri'];
      $sat = $_POST['sat'];
      $sun = $_POST['sun'];
    } else {
      $accept = false;
    }

    $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = $isReportAdmin = $isERPAdmin = $isFinanceAdmin = $isDSGVOAdmin = 'FALSE';
    $canBook = $canStamp = $canEdit = $canUseSocialMedia = 'FALSE';
    if(isset($_POST['isCoreAdmin'])){
      $isCoreAdmin = 'TRUE';
    }
    if(isset($_POST['isTimeAdmin'])){
      $isTimeAdmin = 'TRUE';
    }
    if(isset($_POST['isProjectAdmin'])){
      $isProjectAdmin = 'TRUE';
    }
    if(isset($_POST['isReportAdmin'])){
      $isReportAdmin = 'TRUE';
    }
    if(isset($_POST['isERPAdmin'])){
      $isERPAdmin = 'TRUE';
    }
    if(isset($_POST['isFinanceAdmin'])){
      $isFinanceAdmin = 'TRUE';
    }
    if(isset($_POST['isDSGVOAdmin'])){
      $isDSGVOAdmin = 'TRUE';
    }
    if(isset($_POST['canStamp'])){
      $canStamp = 'TRUE';
    }
    if(isset($_POST['canStamp']) && isset($_POST['canBook'])){
      $canBook = 'TRUE';
    }
    if(isset($_POST['canEditTemplates'])){
      $canEdit = 'TRUE';
    }
    if(isset($_POST['canUseSocialMedia'])){
      $canUseSocialMedia = 'TRUE';
    }
    if(isset($_POST['create'])){
      if($accept){
        //send accessdata if user gets created
        if(!empty($_POST['real_email']) && filter_var($_POST['real_email'], FILTER_VALIDATE_EMAIL)){
          require dirname(dirname(dirname(__DIR__))).'/plugins/phpMailer/autoload.php';
          $recipients = $_POST['real_email'];
          $mail = new PHPMailer();
          $mail->CharSet = 'UTF-8';
          $mail->Encoding = "base64";
          $mail->IsSMTP();
          //get mail server options
          $result = $conn->query("SELECT * FROM $mailOptionsTable");
          $row = $result->fetch_assoc();
          if(!empty($row['username']) && !empty($row['password'])){
            $mail->SMTPAuth   = true;
            $mail->Username   = $row['username'];
            $mail->Password   = $row['password'];
          } else {
            $mail->SMTPAuth   = false;
          }
          if(empty($row['smptSecure'])){
            $mail->SMTPSecure = $row['smtpSecure'];
          }
          $content = "You have been registered to T-Time. <br> Your login information: <br><br> Login e-mail: $email <br> Password: $pass";

          $mail->Host       = $row['host'];
          $mail->Port       = $row['port'];

          $mail->setFrom($row['sender']);

          $mail->addAddress($recipients);
          $mail->isHTML(true);                       // Set email format to HTML
          $mail->Subject = "Your access to T-Time";
          $mail->Body    = $content;
          $mail->AltBody = "If you can read this, your E-Mail provider does not support HTML." . $content;
          $errorInfo = "";
          if(!$mail->send()){
            $errorInfo = $mail->ErrorInfo;
          }
          $conn->query("INSERT INTO $mailLogsTable(sentTo, messageLog) VALUES('$recipients', '$errorInfo')");
        } else { $recipients = ""; }

        //create user
        $psw = password_hash($pass, PASSWORD_BCRYPT);
        $sql = "INSERT INTO $userTable (firstname, lastname, psw, gender, email, beginningDate, real_email)
        VALUES ('$firstname', '$lastname', '$psw', '$gender', '$email', '$begin', '$recipients');";
        if($conn->query($sql)){
          $curID = mysqli_insert_id($conn);
          echo mysqli_error($conn);
          //create interval
          $sql = "INSERT INTO $intervalTable (mon, tue, wed, thu, fri, sat, sun, userID, vacPerYear, overTimeLump, pauseAfterHours, hoursOfrest, startDate)
          VALUES ($mon, $tue, $wed, $thu, $fri, $sat, $sun, $curID, '$vacDaysPerYear', '$overTimeLump','$pauseAfter', '$hoursOfRest', '$begin');";
          $conn->query($sql);
          echo mysqli_error($conn);
          //create roletable
          $sql = "INSERT INTO $roleTable (userID, isCoreAdmin, isProjectAdmin, isTimeAdmin, isReportAdmin, isERPAdmin, isFinanceAdmin, isDSGVOAdmin, canStamp, canBook, canEditTemplates, canUseSocialMedia)
          VALUES($curID, '$isCoreAdmin', '$isProjectAdmin', '$isTimeAdmin', '$isReportAdmin', '$isERPAdmin', '$isFinanceAdmin', '$isDSGVOAdmin', '$canStamp', '$canBook', '$canEdit', '$canUseSocialMedia');";
          $conn->query($sql);
          echo mysqli_error($conn);
          //create socialprofile
          $sql = "INSERT INTO socialprofile (userID, isAvailable, status) VALUES($curID, 'TRUE', '-');";
          $conn->query($sql);
          echo mysqli_error($conn);
          //add relationships
          if(isset($_POST['company'])){
            foreach($_POST['company'] as $cmp){
              $sql = "INSERT INTO $companyToUserRelationshipTable (userID, companyID) VALUES($curID, $cmp)";
              $conn->query($sql);
            }
          }
          if($conn->error){ echo $conn->error; } else {redirect('users');}
        }
      }
    }
  }
} //end if post
?>
<?php if($step == 1): ?>
<form method="POST">
<div class="container-fluid">
  <div class="col-md-8">
    <div class="row form-group">
      <div class="input-group">
        <span class="input-group-addon" style=min-width:150px><?php echo $lang['FIRSTNAME'] ?></span>
        <input type="text" class="form-control" name="firstname" value="<?php echo $firstname; ?>">
      </div>
    </div>
    <div class="row form-group">
      <div class="input-group">
        <span class="input-group-addon" style=min-width:150px><?php echo $lang['LASTNAME'] ?></span>
        <input type="text" class="form-control" name="lastname" value="<?php echo $lastname; ?>">
      </div>
    </div>
    <div class="row text-right">
      <button type="submit" class="btn btn-warning" name="createUser"><?php echo $lang['CONTINUE']; ?></button>
    </div>
  </div>
</div>
</form>
<?php else: ?>
  <form method=post>
    <input type="hidden" name="firstname" value="<?php echo $firstname; ?>"/><input type="hidden" name="lastname" value="<?php echo $lastname; ?>"/>
    <div class="container-fluid">
      <div class="container-fluid form-group">
        <div class="input-group">
          <span class="input-group-addon" style=min-width:150px><?php echo $lang['ENTRANCE_DATE'] ?></span>
          <input type="text" class="form-control datepicker" name="entryDate" value="<?php echo substr($begin,0,10); ?>">
        </div>
      </div>
      <div class="container-fluid form-group">
        <div class="input-group">
          <span class="input-group-addon" style=min-width:150px>Login E-Mail</span>
          <input type="text" class="form-control" name="mail" value="<?php echo $firstname . '.' . $lastname; ?>">
          <span class="input-group-addon" style=min-width:150px><?php echo $emailpostfix; ?></span>
        </div>
      </div>
      <div class="container-fluid form-group">
        <div class="input-group">
          <span class="input-group-addon" style=min-width:150px><?php echo $lang['NEW_PASSWORD']?></span>
          <input type="text" class="form-control" name="yourPas" placeholder="Password" value="<?php echo $pass; ?>">
        </div>
      </div>
    </div>
    <br><br>
    <div class=container-fluid>
      <div class=col-md-3>
        <?php echo $lang['VACATION_DAYS'].$lang['PER_YEAR']; ?>
        <input type="number" class="form-control" name="vacDaysPerYear" value="25">
      </div>
      <div class=col-md-3>
        <?php echo $lang['OVERTIME_ALLOWANCE']; ?>
        <input type="number" step="any" class="form-control" name="overTimeLump" value="0">
      </div>
      <div class=col-md-3>
        <?php echo $lang['HOURS_OF_REST']; ?>
        <input type="number" step="any" class="form-control" name="hoursOfRest" value="0.5">
      </div>
      <div class=col-md-3>
        <?php echo $lang['TAKE_BREAK_AFTER']; ?>
        <input type="number" step="any" class="form-control" name="pauseAfter" value="6.0">
      </div>
    </div>
    <br><br>
    <div class=container-fluid>
      <div class="col-md-3">
        <?php echo $lang['GENDER']; ?>: <br>
        <div class="radio">
          <label>
            <input type="radio" name="gender" value="female" checked>Female <br>
          </label>
        </div>
        <div class="radio">
          <label>
            <input type="radio" name="gender" value="male" >Male <br>
          </label>
        </div>
      </div>
      <div class="col-md-3">
        <?php echo $lang['ADMIN_MODULES']; ?>: <br>
        <div class="checkbox">
          <label><input type="checkbox" name="isCoreAdmin" /><?php echo $lang['ADMIN_CORE_OPTIONS'];?></label><br>
          <label><input type="checkbox" name="isTimeAdmin" /><?php echo $lang['ADMIN_TIME_OPTIONS']; ?></label><br>
          <label><input type="checkbox" name="isProjectAdmin" /><?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?></label><br>
          <label><input type="checkbox" name="isReportAdmin" /><?php echo $lang['REPORTS']; ?></label><br>
          <label><input type="checkbox" name="isERPAdmin" />ERP</label><br>
          <label><input type="checkbox" name="isFinanceAdmin" /><?php echo $lang['FINANCES']; ?></label><br>
          <label><input type="checkbox" name="isDSGVOAdmin" checked />DSGVO</label>
        </div>
      </div>
      <div class="col-md-3">
        <?php echo $lang['USER_MODULES']; ?>: <br>
        <div class="checkbox">
          <label><input type="checkbox" checked name="canStamp"><?php echo $lang['CAN_CHECKIN']; ?></label><br>
          <label><input type="checkbox" name="canBook"><?php echo $lang['CAN_BOOK']; ?></label><br>
          <label><input type="checkbox" name="canEditTemplates"><?php echo $lang['CAN_EDIT_TEMPLATES']; ?></label><br>
          <label><input type="checkbox" name="canUseSocialMedia"><?php echo $lang['CAN_USE_SOCIAL_MEDIA']; ?></label><br>
        </div>
      </div>
      <div class="col-md-3">
        <?php echo $lang['COMPANIES']; ?>: <br>
        <div class="checkbox">
          <?php
          $sql = "SELECT * FROM $companyTable";
          $companyResult = $conn->query($sql);
          while($companyRow = $companyResult->fetch_assoc()){
            echo "<label><input type='checkbox' name='company[]' value=" .$companyRow['id']. "> " . $companyRow['name'] ."</label><br>";
          }
          ?>
        </div>
      </div>
    </div>
    <br><br>
    <div class=container-fluid>
      <div class="col-md-3">
        <div class="input-group">
          <span class="input-group-addon">Mon</span>
          <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="mon" size=2 value='8.5'>
        </div>
      </div>
      <div class="col-md-3">
        <div class="input-group">
          <span class="input-group-addon">Tue</span>
          <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="tue" size=2 value='8.5'>
        </div>
      </div>
      <div class="col-md-3">
        <div class="input-group">
          <span class="input-group-addon">Wed</span>
          <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="wed" size=2 value='8.5'>
        </div>
      </div>
      <div class="col-md-3">
        <div class="input-group">
          <span class="input-group-addon">Thu</span>
          <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="thu" size=2 value='8.5'>
        </div>
      </div>
    </div>
    <br>
    <div class=container-fluid>
      <div class="col-md-3">
        <div class="input-group">
          <span class="input-group-addon">Fri</span>
          <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="fri" size=2 value='4.5'>
        </div>
      </div>
      <div class="col-md-3">
        <div class="input-group">
          <span class="input-group-addon">Sat</span>
          <input type="text" class="form-control" aria-describedby="sizing-addon2" name="sat" size=2 value='0'>
        </div>
      </div>
      <div class="col-md-3">
        <div class="input-group">
          <span class="input-group-addon">Sun</span>
          <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="sun" size=2 value='0'>
        </div>
      </div>
    </div>
    <br><br><br>
    <div class="container-fluid">
      <div class="col-md-8 col-md-offset-4">
        <div class="input-group">
          <input type="text" class="form-control" name="real_email" placeholder="E-Mail (Optional)" />
          <span class="input-group-btn">
            <button type="submit" class="btn btn-warning" name="create"><?php echo $lang['REGISTER_NEW_USER'] .' & '.$lang['SEND_ACCESS']; ?></button>
          </span>
        </div>
      </div>
    </div>
  </form>
<?php endif; ?>

<!-- /BODY -->
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
