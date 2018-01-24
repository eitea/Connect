<?php
if(!empty($_POST['captcha'])){
  header('HTTP/1.0 403 Forbidden');
  die("Malicious bot detected. IP has been detected, aborting all operations.");
} else {
  require '../language.php';
  require "../connection.php";
  include '../utilities.php';
}

use PHPMailer\PHPMailer\PHPMailer;

$proceed = false;
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  $result = $conn->query("SELECT email FROM UserData");
  $row = $result->fetch_assoc();
  $emailpostfix = strrchr($row['email'], "@");
  if (!empty($_POST["firstname"]) && !empty($_POST['lastname'])) {
    $proceed = true;
    $firstname = test_input($_POST["firstname"]);
    $lastname = test_input($_POST['lastname']);
  }
  if(isset($_POST['create'])){
    $proceed = $accept = true;
    //last monday or first of month
    $t = strtotime(carryOverAdder_Hours(getCurrentTimestamp(), 24));
    $begin = date('Y-m-d', strtotime('last Monday', $t)). ' 01:00:00';
    if(substr($begin, 5, 2) != substr(getCurrentTimestamp(), 5, 2)){ //different month
      $begin = date('Y-m-01'). ' 01:00:00';
    }
    $gender = $_POST['gender'];
    $pass = randomPassword();
    if(!empty($_POST['mail']) && filter_var($_POST['mail'].$emailpostfix, FILTER_VALIDATE_EMAIL)){
      $email = test_input($_POST['mail']) .$emailpostfix;
      $mail_result = $conn->query("SELECT email FROM $userTable WHERE email = '$email'");
      if($mail_result && $mail_result->num_rows > 0){
        $accept = false;
        echo '<div class="alert alert-danger fade in"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>'.$lang['ERROR_EXISTING_EMAIL'].'</div>';
      }
    } else {
      $accept = false;
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_EMAIL'].'</div>';
    }
    if(!empty($_POST['real_email']) && filter_var($_POST['real_email'], FILTER_VALIDATE_EMAIL)){
      $real_email = test_input($_POST['real_email']);
    } else {
      $accept = false;
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_EMAIL'].'</div>';
    }
    if(is_numeric($_POST['overTimeLump'])){
      $overTimeLump = $_POST['overTimeLump'];
    } else {
      $accept = false;
      echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_INVALID_DATA'].'</div>';
    }
    if (is_numeric($_POST['mon'])) { $mon = $_POST['mon']; } else { $accept = false; }
    if (is_numeric($_POST['tue'])) { $tue = $_POST['tue']; } else { $accept = false; }
    if (is_numeric($_POST['wed'])) { $wed = $_POST['wed']; } else { $accept = false; }
    if (is_numeric($_POST['thu'])) { $thu = $_POST['thu']; } else { $accept = false; }
    if (is_numeric($_POST['fri'])) { $fri = $_POST['fri']; } else { $accept = false; }
    if (is_numeric($_POST['sat'])) { $sat = $_POST['sat']; } else { $accept = false; }
    if (is_numeric($_POST['sun'])) { $sun = $_POST['sun']; } else { $accept = false; }

    $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = 'FALSE';
    $canBook = $canStamp = 'FALSE';
    if(isset($_POST['canStamp'])){
      $canStamp = 'TRUE';
    }
    if(isset($_POST['canStamp']) && isset($_POST['canBook'])){
      $canBook = 'TRUE';
    }

    if($accept && $real_email){
      //create user
      $psw = password_hash($pass, PASSWORD_BCRYPT);
      $sql = "INSERT INTO $userTable (firstname, lastname, psw, gender, email, beginningDate, real_email)
      VALUES ('$firstname', '$lastname', '$psw', '$gender', '$email', '$begin', '$real_email');";
      //if user was successfully created, insert the rest and send an email.
      if($conn->query($sql)){
        $curID = mysqli_insert_id($conn);
        echo mysqli_error($conn);
        //create interval
        $sql = "INSERT INTO $intervalTable (mon, tue, wed, thu, fri, sat, sun, userID, vacPerYear, overTimeLump, pauseAfterHours, hoursOfrest, startDate)
        VALUES ($mon, $tue, $wed, $thu, $fri, $sat, $sun, $curID, '25', '$overTimeLump','6', '0.5', '$begin');";
        $conn->query($sql);
        echo mysqli_error($conn);
        //create roletable
        $sql = "INSERT INTO $roleTable (userID, isCoreAdmin, isProjectAdmin, isTimeAdmin, canStamp, canBook) VALUES($curID, '$isCoreAdmin', '$isProjectAdmin', '$isTimeAdmin', '$canStamp', '$canBook');";
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
        if(isset($_POST['team'])){
          foreach($_POST['team'] as $cmp){
            $sql = "INSERT INTO $teamRelationshipTable (userID, teamID) VALUES($curID, $cmp)";
            $conn->query($sql);
          }
        }
        //create request
        $conn->query("INSERT INTO $userRequests(userID, fromDate, status, requestType) VALUES($curID, UTC_TIMESTAMP, '0', 'acc')");

        //send accessdata
        require dirname(dirname(__DIR__)).'/plugins/phpMailer/autoload.php';
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
        $content = "You have been registered to Connect. <br> Your login information: <br><br> Login e-mail: $email <br> Password: $pass";

        $mail->Host       = $row['host'];
        $mail->Port       = $row['port'];
        $mail->setFrom($row['sender']);
        $mail->addAddress($real_email);
        $mail->isHTML(true);
        $mail->Subject = "Your access to Connect";
        $mail->Body    = $content;
        $mail->AltBody = "If you can read this, your E-Mail provider does not support HTML." . $content;
        if(!$mail->send()){
          $errorInfo = $mail->ErrorInfo;
          $conn->query("INSERT INTO $mailLogsTable(sentTo, messageLog) VALUES('$real_email', '$errorInfo')");
        } else {
          redirect('auth');
        }
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="../plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <script src="../plugins/jQuery/jquery-3.2.1.min.js"></script>
  <script src="../plugins/bootstrap/js/bootstrap.min.js"></script>
  <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
  <link href="plugins/homeMenu/homeMenu_green.css" rel="stylesheet" />
  <title>Register</title>
</head>
<style>
.robot-control{
  display:none;
}
</style>

<nav class="navbar navbar-default navbar-fixed-top hidden-xs">
  <div class="container-fluid">
    <div class="navbar-header"><a class="navbar-brand" href="auth"><img alt="Connect" src="images/logo.png" height="30px" ></a></div>
  </div>
</nav>

<body>
  <div class="page-header" style="padding-left: 10%;">
    <h3><?php echo $lang['REGISTER_NEW_USER']; ?></h3>
  </div>
  <br><br>
  <form method="post">
    <?php if($proceed): ?>
      <input type="hidden" name="firstname" value="<?php echo $firstname; ?>" />
      <input type="hidden" name="lastname" value="<?php echo $lastname; ?>" />
      <div class=container-fluid>
        <div class="container-fluid form-group">
          <div class="input-group">
            <span class="input-group-addon" style=min-width:150px>Login E-Mail</span>
            <input type="text" class="form-control" name="mail" value="<?php echo $firstname . '.' . $lastname; ?>">
            <span class="input-group-addon" style=min-width:150px><?php echo $emailpostfix; ?></span>
          </div>
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
          <?php echo $lang['ALLOW_PRJBKING_ACCESS']; ?>: <br>
          <div class="checkbox">
            <label><input type="checkbox" checked name="canStamp">Can Checkin <br></label>
          </div>
          <div class="checkbox">
            <label><input type="checkbox" name="canBook">Can Book <br></label>
          </div>
        </div>
        <div class="col-md-3">
          <?php
          echo $lang['COMPANIES'].': <br>';
          $companyResult = $conn->query("SELECT * FROM companyData");
          while($companyRow = $companyResult->fetch_assoc()){
            echo "<div class='checkbox'><label><input type='checkbox' name='company[]' value=" .$companyRow['id']. " /> " . $companyRow['name'] ."<br></label></div>";
          }
          ?>
        </div>
        <div class="col-md-3">
          Team: <br>
          <?php
          $companyResult = $conn->query("SELECT * FROM teamData");
          while($companyRow = $companyResult->fetch_assoc()){
            echo "<div class='checkbox'><label><input type='checkbox' name='team[]' value=" .$companyRow['id']. " /> " . $companyRow['name'] ."<br></label></div>";
          }
          ?>
        </div>
      </div>
      <br><br>
      <div class="container-fluid">
        <div class=col-md-3>
          <div class="input-group">
            <span class="input-group-addon"><?php echo $lang['OVERTIME_ALLOWANCE']; ?></span>
            <input type="number" step="any" class="form-control" name="overTimeLump" value="0">
          </div>
        </div>
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
      </div>
      <br>
      <div class="container-fluid">
        <div class="col-md-3">
          <div class="input-group">
            <span class="input-group-addon">Thu</span>
            <input type="number" step="any" class="form-control" aria-describedby="sizing-addon2" name="thu" size=2 value='8.5'>
          </div>
        </div>
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
        <div class="col-md-7 col-md-offset-5">
          <div class="input-group">
            <input type="text" class="form-control" name="real_email" placeholder="E-Mail" />
            <span class="input-group-btn">
              <button type="submit" class="btn btn-warning" name="create"><?php echo $lang['REGISTER_NEW_USER'] .' & '.$lang['SEND_ACCESS']; ?></button>
            </span>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="container" style="margin-left: 10%;">
        <div class="row">Please enter your Name: <small>(All fields required)</small></div>
        <div class="col-md-8">
          <div class="row form-group">
            <div class="input-group">
              <span class="input-group-addon" style=min-width:150px><?php echo $lang['FIRSTNAME'] ?></span>
              <input type="text" class="form-control" name="firstname" value="">
            </div>
          </div>
          <div class="row form-group">
            <div class="input-group">
              <span class="input-group-addon" style=min-width:150px><?php echo $lang['LASTNAME'] ?></span>
              <input type="text" class="form-control" name="lastname" value="">
            </div>
          </div>
          <div class="row text-right">
            <button type="submit" class="btn btn-warning" name="createUser">Next</button>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <div class="robot-control"> <input type="text" name="captcha" value="" /></div>
  </form>
</body>
</html>
