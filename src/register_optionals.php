<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID); ?>
<!-- BODY -->

<div class="page-header">
<h3>Register</h3>
</div>

<form method=post>
<?php

$firstname = $_GET['gn'];
$lastname = $_GET['sn'];
$email = $_GET['mail'];
$begin = substr(getCurrentTimestamp(),0,10) . ' 05:00:00';

$vacDaysCredit = $overTimeLump = 0;
$allowAccess = $gender = "";

$pass = randomPassword();

if(isset($_POST['create'])){
  $accept = true;
  if(!empty($_POST['entryDate']) && test_Date($_POST['entryDate'] ." 05:00:00")){
    $gender = $_POST['gender'];
    $begin = $_POST['entryDate'] ." 05:00:00";

    if(!empty($_POST['mail']) && filter_var(test_input($_POST['mail']), FILTER_VALIDATE_EMAIL)){
      $email = $_POST['mail'];
    }

    if(!empty($_POST['yourPas'])){
      $pass = test_input($_POST['yourPas']);
    } else {
      $accept = false;
      echo '<div class="alert alert-danger fade in">';
      echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
      echo '<strong>Failed: </strong>Missing Password.';
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

  if($accept){
    //create user
    $psw = password_hash($pass, PASSWORD_BCRYPT);
    $sql = "INSERT INTO $userTable (firstname, lastname, psw, gender, email, overTimeLump, pauseAfterHours, hoursOfRest, beginningDate, enableProjecting)
    VALUES ('$firstname', '$lastname', '$psw', '$gender', '$email', '$overTimeLump','$pauseAfter', '$hoursOfRest', '$begin', '$allowAccess');";
    $conn->query($sql);
    $curID = mysqli_insert_id($conn);

    //create bookingtable
    $sql = "INSERT INTO $bookingTable (mon, tue, wed, thu, fri, sat, sun, userID) VALUES ($mon, $tue, $wed, $thu, $fri, $sat, $sun, $curID);";
    $conn->query($sql);

    //create vacationtable
    $sql = "INSERT INTO $vacationTable (userID, vacationHoursCredit, daysPerYear) VALUES($curID, '$vacDaysCredit', '$vacDaysPerYear');";
    $conn->query($sql);

    //create roletable
    $sql = "INSERT INTO $roleTable (userID, isCoreAdmin, isProjectAdmin, isTimeAdmin, canStamp, canBook) VALUES($curID, '$isCoreAdmin', '$isProjectAdmin', '$isTimeAdmin', '$canStamp', '$canBook');";
    $conn->query($sql);

    //add relationships
    if(isset($_POST['company'])){
      foreach($_POST['company'] as $cmp){
        $sql = "INSERT INTO $companyToUserRelationshipTable (userID, companyID) VALUES($curID, $cmp)";
        $conn->query($sql);
      }
    }
    //check if entry date lies before/after today
    //future: just reset unlogs and vacationcredit on that day, instead of creating the user on that date. (my gosh...)
    //past: re-calculate vacationcredit until today and insert unlogs
    $difference = timeDiff_Hours(substr(getCurrentTimestamp(),0,10) . ' 05:00:00', $begin);
    if($difference > 0){ //future
      $sql = "CREATE EVENT create$curID ON SCHEDULE AT '$begin'
      ON COMPLETION NOT PRESERVE ENABLE
      COMMENT 'Removing unlogs on entry date'
      DO
      BEGIN
      DELETE FROM $negative_logTable WHERE userID = $curID;
      UPDATE $vacationTable SET vacationHoursCredit = 0 WHERE userID = $curID;
      END
      ";
      $conn->query($sql);
      echo mysqli_error($conn);

    } elseif($difference < 0) { //past
      $credit = ($vacDaysPerYear/365) * timeDiff_Hours($begin, substr(getCurrentTimestamp(),0,10) . ' 05:00:00');
      $sql = "INSERT INTO $vacationTable SET vacationHoursCredit = $credit WHERE userID = $curID";
      $conn->query($sql);
      $i = $begin;
      while(substr($i, 0, 10) != substr(getCurrentTimestamp(), 0, 10)){
        $sql = "INSERT INTO $negative_logTable (time, userID, mon, tue, wed, thu, fri, sat, sun)
        VALUES('$i', $curID, $mon, $tue, $wed, $thu, $fri, $sat, $sun)";
        $conn->query($sql);
        $i = carryOverAdder_Hours($i, 24);
      }
    }
    redirect('editUsers.php');
  } //end if accept
} //end if post

?>

<div class=container-fluid>
  <div class="row form-group">
    <div class="input-group">
      <span class="input-group-addon" style=min-width:150px><?php echo $lang['ENTRANCE_DATE'] ?></span>
      <input type="date" class="form-control" name="entryDate" value="<?php echo substr($begin,0,10); ?>">
    </div>
  </div>
  <div class="row form-group">
    <div class="input-group">
      <span class="input-group-addon" style=min-width:150px>E-Mail</span>
      <input type="email" class="form-control" name="mail" value="<?php echo $email; ?>">
    </div>
  </div>
  <div class="row form-group">
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
      <label>
        <input type="checkbox" name="isCoreAdmin"><?php echo $lang['ADMIN_CORE_OPTIONS'];?><br>
      </label>
      <label>
        <input type="checkbox" name="isTimeAdmin"><?php echo $lang['ADMIN_TIME_OPTIONS']; ?><br>
      </label>
      <label>
        <input type="checkbox" name="isProjectAdmin"><?php echo $lang['ADMIN_PROJECT_OPTIONS']; ?><br>
      </label>
    </div>
  </div>
  <div class="col-md-3">
    <?php echo $lang['ALLOW_PRJBKING_ACCESS']; ?>: <br>
    <div class="checkbox">
      <label>
        <input type="checkbox" checked name="canStamp">Can Checkin <br>
      </label>
      <label>
        <input type="checkbox" name="canBook">Can Book
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
 <br><br>

 <button type="submit" class="btn btn-warning" name=create><?php echo $lang['REGISTER_NEW_USER']; ?></button>
</form>
</body>
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
