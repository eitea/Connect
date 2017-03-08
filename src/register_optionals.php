<?php include 'header.php'; ?>
<?php enableToCore($userID); ?>
<!-- BODY -->

<div class="page-header">
  <h3>Register</h3>
</div>

<?php
$firstname = test_input($_GET['gn']);
$lastname = test_input($_GET['sn']);
$begin = substr(getCurrentTimestamp(),0,10) . ' 05:00:00';

$pass = randomPassword();

$result = $conn->query("SELECT email FROM $userTable");
$row = $result->fetch_assoc();
$emailpostfix = strrchr($row['email'], "@");

if(empty($emailpostfix)){
  echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
  echo 'Could not split Domain: Please check the email Adress of your admin Account in DB.</div>';
  die();
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $accept = true;
  if(!empty($_POST['entryDate']) && test_Date($_POST['entryDate'] ." 05:00:00")){
    $begin = $_POST['entryDate'] ." 05:00:00";
  } else {
    $accept = false;
  }
  $gender = $_POST['gender'];

  if(!empty($_POST['mail']) && filter_var($_POST['mail'].$emailpostfix, FILTER_VALIDATE_EMAIL)){
    $email = test_input($_POST['mail']) .$emailpostfix;
  } else {
    $accept = false;
    echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo 'Invalid E-Mail Address.</div>';
  }

  if(!empty($_POST['yourPas']) && match_passwordpolicy($_POST['yourPas'])){
    $pass = test_input($_POST['yourPas']);
  } else {
    $accept = false;
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Failed: </strong>Invalid Password. Password must be at least 8 characters long and contain at least one Capital Letter, one number and one special character.';
    echo '</div>';
  }

  if(!empty($_POST['vacDaysPerYear']) && is_numeric($_POST['vacDaysPerYear'])){
    $vacDaysPerYear = $_POST['vacDaysPerYear'];
  } else {
    $accept = false;
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Failed: </strong>Invalid vacation value.';
    echo '</div>';
  }

  if(is_numeric($_POST['overTimeLump'])){
    $overTimeLump = $_POST['overTimeLump'];
  } else {
    $accept = false;
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Failed: </strong>Invalid overtime value.';
    echo '</div>';
  }

  if(!empty($_POST['pauseAfter']) && is_numeric($_POST['pauseAfter'])){
    $pauseAfter = $_POST['pauseAfter'];
  } else {
    $accept = false;
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Failed: </strong>Invalid Lunchbreak value.';
    echo '</div>';
  }
  if(!empty($_POST['hoursOfRest']) && is_numeric($_POST['hoursOfRest'])){
    $hoursOfRest = $_POST['hoursOfRest'];
  } else {
    $accept = false;
    echo '<div class="alert alert-danger fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Failed: </strong>Invalid hours of lunchbreak.';
    echo '</div>';
  }

  if (is_numeric($_POST['mon'])) {
    $mon = $_POST['mon'];
  } else {
    $accept = false;
  }
  if (is_numeric($_POST['tue'])) {
    $tue = $_POST['tue'];
  } else {
    $accept = false;
  }
  if (is_numeric($_POST['wed'])) {
    $wed = $_POST['wed'];
  } else {
    $accept = false;
  }
  if (is_numeric($_POST['thu'])) {
    $thu = $_POST['thu'];
  } else {
    $accept = false;
  }
  if (is_numeric($_POST['fri'])) {
    $fri = $_POST['fri'];
  } else {
    $accept = false;
  }
  if (is_numeric($_POST['sat'])) {
    $sat = $_POST['sat'];
  } else {
    $accept = false;
  }
  if (is_numeric($_POST['sun'])) {
    $sun = $_POST['sun'];
  } else {
    $accept = false;
  }

  $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = 'FALSE';
  $canBook = $canStamp = 'FALSE';
  if(isset($_POST['isCoreAdmin'])){
    $isCoreAdmin = 'TRUE';
  }
  if(isset($_POST['isTimeAdmin'])){
    $isTimeAdmin = 'TRUE';
  }
  if(isset($_POST['isProjectAdmin'])){
    $isProjectAdmin = 'TRUE';
  }
  if(isset($_POST['canStamp'])){
    $canStamp = 'TRUE';
  }
  if(isset($_POST['canStamp']) && isset($_POST['canBook'])){
    $canBook = 'TRUE';
  }

  if(isset($_POST['create'])){
    if($accept){
      //send accessdata if user gets created
      if(!empty($_POST['real_email']) && filter_var($_POST['real_email'], FILTER_VALIDATE_EMAIL)){
        require_once "../plugins/phpMailer/class.phpmailer.php";
        require_once "../plugins/phpMailer/class.smtp.php";
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
      } else {$recipients = ""; }

      //create user
      $psw = password_hash($pass, PASSWORD_BCRYPT);
      $sql = "INSERT INTO $userTable (firstname, lastname, psw, gender, email, beginningDate, real_email)
      VALUES ('$firstname', '$lastname', '$psw', '$gender', '$email', '$begin', '$recipients');";
      $conn->query($sql);
      $curID = mysqli_insert_id($conn);
      echo mysqli_error($conn);
      //create interval
      $sql = "INSERT INTO $intervalTable (mon, tue, wed, thu, fri, sat, sun, userID, vacPerYear, overTimeLump, pauseAfterHours, hoursOfrest, startDate)
      VALUES ($mon, $tue, $wed, $thu, $fri, $sat, $sun, $curID, '$vacDaysPerYear', '$overTimeLump','$pauseAfter', '$hoursOfRest', '$begin');";
      $conn->query($sql);
      echo mysqli_error($conn);
      //create roletable
      $sql = "INSERT INTO $roleTable (userID, isCoreAdmin, isProjectAdmin, isTimeAdmin, canStamp, canBook) VALUES($curID, '$isCoreAdmin', '$isProjectAdmin', '$isTimeAdmin', '$canStamp', '$canBook');";
      $conn->query($sql);
      echo mysqli_error($conn);
      //add relationships
      if(isset($_POST['company'])){
        foreach($_POST['company'] as $cmp){
          $sql = "INSERT INTO $companyToUserRelationshipTable (userID, companyID) VALUES($curID, $cmp)";
          $conn->query($sql);
        }
      }
      echo mysqli_error($conn);
      redirect('editUsers.php');
    }
  }
} //end if post
?>

<form method=post>
  <div class=container-fluid>
    <div class="container-fluid form-group">
      <div class="input-group">
        <span class="input-group-addon" style=min-width:150px><?php echo $lang['ENTRANCE_DATE'] ?></span>
        <input type="date" class="form-control" name="entryDate" value="<?php echo substr($begin,0,10); ?>">
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
      <?php echo $lang['VACATION_DAYS_PER_YEAR']; ?>
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
      Module: <br>
      <div class="checkbox">
        <input type="checkbox" name="isCoreAdmin" /><?php echo $lang['ADMIN_CORE_OPTIONS'];?><br>
        <input type="checkbox" name="isTimeAdmin" /><?php echo $lang['ADMIN_TIME_OPTIONS']; ?><br>
        <input type="checkbox" name="isProjectAdmin" /><?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?><br>
      </div>
    </div>
    <div class="col-md-3">
      <?php echo $lang['ALLOW_PRJBKING_ACCESS']; ?>: <br>
      <div class="checkbox">
        <input type="checkbox" checked name="canStamp">Can Checkin <br>
        <input type="checkbox" name="canBook">Can Book <br>
      </div>
    </div>
    <div class="col-md-3">
      <?php echo $lang['COMPANIES']; ?>: <br>
      <div class="checkbox">
        <?php
        $sql = "SELECT * FROM $companyTable";
        $companyResult = $conn->query($sql);
        while($companyRow = $companyResult->fetch_assoc()){
          echo "<input type='checkbox' name='company[]' value=" .$companyRow['id']. "> " . $companyRow['name'] ."<br>";
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
          <button type="submit" class="btn btn-warning" name=create><?php echo $lang['REGISTER_NEW_USER'] .' & '.$lang['SEND_ACCESS']; ?></button>
        </span>
      </div>
    </div>
  </div>
</form>
<?php
function randomPassword(){
  $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
  $psw = array();
  $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
  for ($i = 0; $i < 8; $i++) {
    $n = rand(0, $alphaLength);
    $psw[] = $alphabet[$n];
  }
  return implode($psw); //turn the array into a string
}
?>
<!-- /BODY -->
<?php include 'footer.php'; ?>
