<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/homeMenu.css">

<style>
td{
  width:50%;
}
</style>
</head>

<body>
<form method=post>
<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}

require 'connection.php';
require 'createTimestamps.php';
require 'language.php';

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
    $allowAccess = $_POST['enableProjecting'];
    $begin = $_POST['entryDate'] ." 05:00:00";

    if(!empty($_POST['vacDaysPerYear']) && is_numeric($_POST['vacDaysPerYear'])){
      $vacDaysPerYear = $_POST['vacDaysPerYear'];
    } else {
      $vacDaysPerYear = 25;
    }

    if(!empty($_POST['vacDaysCredit']) && is_numeric($_POST['vacDaysCredit'])){
      $vacDaysCredit = $_POST['vacDaysCredit'];
    }

    if(!empty($_POST['overTimeLump']) && is_numeric($_POST['overTimeLump'])){
      $overTimeLump = $_POST['overTimeLump'];
    }

    if(!empty($_POST['pauseAfter']) && is_numeric($_POST['pauseAfter'])){
      $pauseAfter = $_POST['pauseAfter'];
    } else {
      $pauseAfter = 6;
    }
    if(!empty($_POST['hoursOfRest']) && is_numeric($_POST['hoursOfRest'])){
      $hoursOfRest = $_POST['hoursOfRest'];
    } else {
      $hoursOfRest = 0.5;
    }

    if (isset($_POST['mon']) && is_numeric($_POST['mon'])) {
      $mon = $_POST['mon'];
    } else {
      $mon = 8.5;
    }

    if (isset($_POST['tue']) && is_numeric($_POST['tue'])) {
      $tue = $_POST['tue'];
    } else {
      $tue = 8.5;
    }

    if (isset($_POST['wed']) && is_numeric($_POST['wed'])) {
      $wed = $_POST['wed'];
    } else {
      $wed = 8.5;
    }

    if (isset($_POST['thu']) && is_numeric($_POST['thu'])) {
      $thu = $_POST['thu'];
    } else {
      $thu = 8.5;
    }

    if (isset($_POST['fri']) && is_numeric($_POST['fri'])) {
      $fri = $_POST['fri'];
    } else {
      $fri = 4.5;
    }

    if (isset($_POST['sat']) && is_numeric($_POST['sat'])) {
      $sat = $_POST['sat'];
    } else {
      $sat = 0;
    }

    if (isset($_POST['sun']) && is_numeric($_POST['sun'])) {
      $sun = $_POST['sun'];
    } else {
      $sun = 0;
    }

  } else {
    $accept = false;
  }

  if($accept){
    //create user
    $psw = password_hash($pass, PASSWORD_BCRYPT);
    $sql = "INSERT INTO $userTable (firstname, lastname, psw, gender, email, overTimeLump, pauseAfterHours, hoursOfRest, beginningDate, enableProjecting)
    VALUES ('$firstname', '$lastname', '$psw', '$gender', '$email', '$overTimeLump','$pauseAfter', '$hoursOfRest', '$begin', '$allowAccess');";
    $conn->query($sql);
    $userID = mysqli_insert_id($conn);

    //create bookingtable
    $sql = "INSERT INTO $bookingTable (mon, tue, wed, thu, fri, sat, sun, userID) VALUES ($mon, $tue, $wed, $thu, $fri, $sat, $sun, $userID);";
    $conn->query($sql);

    //create vacationtable
    $sql = "INSERT INTO $vacationTable (userID, vacationHoursCredit, daysPerYear) VALUES($userID, '$vacDaysCredit', '$vacDaysPerYear');";
    $conn->query($sql);

    //add relationships
    if(isset($_POST['company'])){
      foreach($_POST['company'] as $cmp){
        $sql = "INSERT INTO $companyToUserRelationshipTable (userID, companyID) VALUES($userID, $cmp)";
        $conn->query($sql);
      }
    }

    //check if entry date lies before/after today
    //future: just reset unlogs and vacationcredit on that day, instead of creating the user on that date. (my gosh...)
    //past: re-calculate vacationcredit until today and insert unlogs
    $difference = timeDiff_Hours(substr(getCurrentTimestamp(),0,10) . ' 05:00:00', $begin);
    if($difference > 0){ //future
      $sql = "CREATE EVENT create$userID ON SCHEDULE AT '$begin'
      ON COMPLETION NOT PRESERVE ENABLE
      COMMENT 'Removing unlogs on entry date'
      DO
      BEGIN
      DELETE FROM $negative_logTable WHERE userID = $userID;
      UPDATE $vacationTable SET vacationHoursCredit = 0 WHERE userID = $userID;
      END
      ";
      $conn->query($sql);
      echo mysqli_error($conn);

    } elseif($difference < 0) { //past
      $credit = ($vacDaysPerYear/365) * timeDiff_Hours($begin, substr(getCurrentTimestamp(),0,10) . ' 05:00:00');
      $sql = "INSERT INTO $vacationTable SET vacationHoursCredit = $credit WHERE userID = $userID";
      $conn->query($sql);
      $i = $begin;
      while(substr($i, 0, 10) != substr(getCurrentTimestamp(), 0, 10)){
        $sql = "INSERT INTO $negative_logTable (time, userID, mon, tue, wed, thu, fri, sat, sun)
        VALUES('$i', $userID, $mon, $tue, $wed, $thu, $fri, $sat, $sun)";
        $conn->query($sql);
        $i = carryOverAdder_Hours($i, 24);
      }
    }
    header('refresh:0;url=editUsers.php');
  }

}

?>
<?php echo $lang['ENTRANCE_DATE']; ?>: <input type="date" value="<?php echo substr($begin,0,10); ?>" name="entryDate">

<table>
  <tr>
    <td><?php echo $lang['FIRSTNAME'] .': </td><td>' . $firstname; ?> </td> <br>
  </tr>

  <tr>
    <td><?php echo $lang['LASTNAME'] .': </td><td>' . $lastname; ?> </td> <br>
  </tr>

  <tr>
    <td> E-Mail: </td><td> <?php echo $email; ?> </td> <br>
  </tr>

  <tr>
    <td> Psw.: </td><td> <?php echo $pass; ?> </td> <br>
  </tr>
</table><br><br>

<table>
<tr>
  <td>

 <?php echo $lang['VACATION_DAYS_PER_YEAR']?>: <br>

 <input type="number" name="vacDaysPerYear" value="25" ><br><br>

 <?php echo $lang['AMOUNT_VACATION_DAYS']?>: <br>
 <input type="number" step="any" name="vacDaysCredit" value="0.0" ><br><br>

 <?php echo $lang['OVERTIME_ALLOWANCE']?>: <br>
 <input type="number" step="any" name="overTimeLump" value="0" ><br><br>

 <?php echo $lang['TAKE_BREAK_AFTER']?>: <br>
 <input type="number" name="pauseAfter" value="6" ><br><br>

 <?php echo $lang['HOURS_OF_REST']?>: <br>
 <input type="number" step="any" name="hoursOfRest" value="0.5" ><br><br>

</td>

<td> <br>
  <table>
    <tr>
      <th>Mon</th>
      <th>Tue</th>
      <th>Wed</th>
      <th>Thu</th>
      <th>Fri</th>
      <th>Sat</th>
      <th>Sun</th>
    </tr>
    <tr>
      <td> <input type="text" name="mon" size=2 value='8.5' ></td>
      <td> <input type="text" name="tue" size=2 value='8.5' ></td>
      <td> <input type="text" name="wed" size=2 value='8.5' ></td>
      <td> <input type="text" name="thu" size=2 value='8.5' ></td>
      <td> <input type="text" name="fri" size=2 value='4.5' ></td>
      <td> <input type="text" name="sat" size=2 value=0 ></td>
      <td> <input type="text" name="sun" size=2 value=0 ></td>
    </tr>
  </table><br>

 <?php echo $lang['GENDER']?>: <br>
 <input type="radio" name="gender" value="female" checked>Female <br>
 <input type="radio" name="gender" value="male" >Male <br>

 <br>
 <?php echo $lang['ALLOW_PRJBKING_ACCESS']?>: <br>
 <input type="radio" name="enableProjecting" value="TRUE" >Yes <br>
 <input type="radio" name="enableProjecting" value="FALSE" checked >No <br>
 <br>

 Assign: <br>

 <?php
 $sql = "SELECT * FROM $companyTable";
 $companyResult = $conn->query($sql);
 while($companyRow = $companyResult->fetch_assoc()){
   echo "<input type='checkbox' name='company[]' value=" .$companyRow['id']. "> " . $companyRow['name'] ."<br>";
 }
 ?>
 <br><br>

</td>
</tr>
</table>

 <br><br>

 <input type="submit" value="Create User" name="create">
</form>
</body>
<?php
function randomPassword() {
  $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
  $pass = array(); //remember to declare $pass as an array
  $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
  for ($i = 0; $i < 8; $i++) {
    $n = rand(0, $alphaLength);
    $pass[] = $alphabet[$n];
  }
  return implode($pass); //turn the array into a string
}
 ?>
