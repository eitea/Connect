<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/homeMenu.css">

  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>

  <style>
  .error {
    color: #FF0000;
  }

  .floating-box {
    float: left;
    margin: 25px;
  }

  .fakeButtonLink {
    background:0;
    border:0;
    color:rgb(83, 137, 186);
    margin:5px;
  }

  .removeButton {
    background:url(../images/minus_circle.png);
    background-size:cover;
    width:15px;
    height:15px;
    border:0;
  }

  </style>

</head>
<body>
  <?php
  session_start();
  if (!isset($_SESSION['userid'])) {
    die('Please <a href="login.php">login</a> first.');
  }
  if ($_SESSION['userid'] != 1) {
    die('Access denied. <a href="logout.php"> return</a>');
  }
  require "connection.php";
  require "createTimestamps.php";
  require "language.php";
  ?>

  <?php
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $query = "SELECT id FROM $userTable;";
    $result = mysqli_query($conn, $query);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $x = $row['id'];
        if (isset($_POST['dele_'.$x])) {
          if ($x != 1) {
            $sql = "DELETE FROM $userTable WHERE id = $x;";
            $conn->query($sql);
          } else {
            echo $lang['ADMIN_DELETE'] ."<br>";
          }
          break;
        }

        if (isset($_POST['submit'.$x])) {
          $userid = $x;

          //TODO: get submitDate -> date restriction (is in future)
          //create event that changes desired values on that day, at idk.. 1am?
          if($_POST['submitDate'. $x] != substr(getCurrentTimestamp(), 0, 10)){

          }

          if (!empty($_POST['firstname'.$x])) {
            $firstname = $_POST['firstname'.$x];
            $sql = "UPDATE $userTable SET firstname= '$firstname' WHERE id = '$userid';";
            $conn->query($sql);
          }

          if (!empty($_POST['lastname'.$x])) {
            $lastname = $_POST['lastname'.$x];
            $sql = "UPDATE $userTable SET lastname= '$lastname' WHERE id = '$userid';";
            $conn->query($sql);
          }

          if (!empty($_POST['gender'.$x])) {
            $gender = $_POST['gender'.$x];
            $sql = "UPDATE $userTable SET gender= '$gender' WHERE id = '$userid';";
            $conn->query($sql);
          }

          if (!empty($_POST['enableProjecting'.$x])) {
            $enableProjecting = $_POST['enableProjecting'.$x];
            $sql = "UPDATE $userTable SET enableProjecting= '$enableProjecting' WHERE id = '$userid';";
            $conn->query($sql);
          }

          if (!empty($_POST['email'.$x]) && filter_var(test_input($_POST['email'.$x]), FILTER_VALIDATE_EMAIL)){
            $email = test_input($_POST['email'.$x]);
            $sql = "UPDATE $userTable SET email = '$email' WHERE id = '$userid';";
            $conn->query($sql);
          }

/*
          if (isset($_POST['coreTime'.$x])){
            $coreTime = $_POST['coreTime'.$x];
            $sql = "UPDATE $userTable SET coreTime = '$coreTime' WHERE id = '$userid';";
            $conn->query($sql);
          }
*/
          if (!empty($_POST['vacDays'.$x]) && is_numeric($_POST['vacDays'.$x])) {
            $vacDaysPerYear = $_POST['vacDays'.$x];
            $sql = "UPDATE $vacationTable SET daysPerYear= '$vacDays' WHERE userID = '$userid';";
            $conn->query($sql);
            echo mysqli_error($conn);
          }

          if (isset($_POST['overTimeAll'.$x]) && is_numeric(str_replace(',','.',$_POST['overTimeAll'.$x]))) {
            $overTimeAll = str_replace(',','.',$_POST['overTimeAll'.$x]);
            $sql = "UPDATE $userTable SET overTimeLump= '$overTimeAll' WHERE id = '$userid';";
            $conn->query($sql);
          }

          if (isset($_POST['vacDaysCredit'.$x]) && is_numeric($_POST['vacDaysCredit'.$x])) {
            $vacDaysCredit = $_POST['vacDaysCredit'.$x];
            $sql = "UPDATE $vacationTable SET vacationHoursCredit= '$vacDaysCredit' WHERE userID = '$userid';";
            $conn->query($sql);
          }

          if (isset($_POST['pauseAfter'.$x]) && is_numeric($_POST['pauseAfter'.$x])) {
            $pauseAfter = $_POST['pauseAfter'.$x];
            $sql = "UPDATE $userTable SET pauseAfterHours= '$pauseAfter' WHERE id = '$userid';";
            $conn->query($sql);
          }

          if (isset($_POST['rest'.$x]) && is_numeric($_POST['rest'.$x])) {
            $rest = $_POST['rest'.$x];
            $sql = "UPDATE $userTable SET hoursOfRest= '$rest' WHERE id = '$userid';";
            $conn->query($sql);
          }

          if(isset($_POST['mon'.$x])){
            $mon = test_input($_POST['mon'.$x]);
            $sql = "UPDATE $bookingTable SET mon='$mon' WHERE userID = '$userid'";
            $conn->query($sql);
          }

          if (isset($_POST['tue'.$x])){
            $tue = test_input($_POST['tue'.$x]);
            $sql = "UPDATE $bookingTable SET tue='$tue' WHERE userID = '$userid'";
            $conn->query($sql);
          }

          if (isset($_POST['wed'.$x])){
            $wed = test_input($_POST['wed'.$x]);
            $sql = "UPDATE $bookingTable SET wed='$wed' WHERE userID = '$userid'";
            $conn->query($sql);
          }

          if (isset($_POST['thu'.$x])){
            $thu = test_input($_POST['thu'.$x]);
            $sql = "UPDATE $bookingTable SET thu='$thu' WHERE userID = '$userid'";
            $conn->query($sql);
          }

          if (isset($_POST['fri'.$x])){
            $fri = test_input($_POST['fri'.$x]);
            $sql = "UPDATE $bookingTable SET fri='$fri' WHERE userID = '$userid'";
            $conn->query($sql);
          }

          if (isset($_POST['sat'.$x])){
            $sat = test_input($_POST['sat'.$x]);
            $sql = "UPDATE $bookingTable SET sat='$sat' WHERE userID = '$userid'";
            $conn->query($sql);
          }

          if (isset($_POST['sun'.$x])){
            $sun = test_input($_POST['sun'.$x]);
            $sql = "UPDATE $bookingTable SET sun='$sun' WHERE userID = '$userid'";
            $conn->query($sql);
          }

          if (!empty($_POST['password'.$x]) && !empty($_POST['passwordConfirm'.$x])) {
            $password = $_POST['password'.$x];
            $passwordConfirm = $_POST['passwordConfirm'.$x];
            if (strcmp($password, $passwordConfirm) == 0) {
              $psw = password_hash($password, PASSWORD_BCRYPT);
              $sql = "UPDATE $userTable SET psw = '$psw' WHERE id = '$userid';";
              $conn->query($sql);
            } else {
              $passErr = "*Password did not match.";
            }
          }
          if (isset($_POST['company'.$x])){
            $sql = "SELECT * FROM $companyTable";
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()){
              //just completely delete the relationship from table
              $sql = "DELETE FROM $companyToUserRelationshipTable WHERE userID = $x AND companyID = " . $row['id'];
              $conn->query($sql);
              if(in_array($row['id'], $_POST['company'.$x])){  //if company is checked, insert again to avoid duplicate entries.
                $sql = "INSERT INTO $companyToUserRelationshipTable (companyID, userID) VALUES (".$row['id'].", $x)";
                $conn->query($sql);
              }
            }
          }
        } //end submitX
      }
    }
  }
  ?>

  <script> //oh noo....
    parent.document.getElementById("myFrame").height = '2000px';
  </script>

  <form action="#" name="myForm" method="POST" onsubmit="return confirm('Are you sure you want to proceed?');">
    <?php
    $mon=$tue=$wed=$thu=$fri=$sat=$sun=0;
    $firstname=$lastname=$email=$gender=$vacDays=$overTimeAll = $vacDaysCredit = $pauseAfter = $rest = $begin = $passErr= $coreTime = "";

    $query = "SELECT *, $userTable.id AS userID FROM $userTable INNER JOIN $bookingTable ON $userTable.id = $bookingTable.userID INNER JOIN $vacationTable ON $userTable.id = $vacationTable.userID ORDER BY $userTable.id ASC";
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
        $coreTime = $row['coreTime'];
        $vacDaysPerYear = $row['daysPerYear'];
        $vacDaysCredit = $row['vacationHoursCredit'];
        $overTimeAll = $row['overTimeLump'];
        $pauseAfter = $row['pauseAfterHours'];
        $rest = $row['hoursOfRest'];
        $begin = $row['beginningDate'];
        $enableProjecting = $row['enableProjecting'];

        echo "<input id='delButton' type='submit' name='dele_$x' value=' ' class='removeButton' ></input>";

        $eOut = "ID: $x - $firstname $lastname";
        echo "<button type='button' class='fakeButtonLink' data-toggle='collapse' data-target='#toggleEdit".$x."'>$eOut</button>";
        echo "<br>";
        echo "<div id='toggleEdit".$x."'class='collapse'>";
        ?>

        <br><?php echo $lang['WARNING_BLANK_FIELDS_WONT_OVERWRITE']?><br>
        <fieldset>
          <div class="floating-box">
            <?php echo $lang['FIRSTNAME']?>: <br>
            <input type="text" name="firstname<?php echo $x; ?>" value="<?php echo $firstname; ?>"/><br><br>

            <?php echo $lang['LASTNAME']?>: <br>
            <input type="text" name="lastname<?php echo $x; ?>" value="<?php echo $lastname; ?>"/><br><br>

            E-Mail: <br>
            <input type="text" name="email<?php echo $x; ?>" value="<?php echo $email; ?>"/><br><br>

            <?php //echo $lang['CORE_TIME']?>
            <!--input type="time" name="coreTime<?php echo $x; ?>" value="<?php echo $coreTime; ?>"/-->

            <?php echo $lang['ENTRANCE_DATE'] .':<br>'. substr($begin,0,10); ?>
          </div>


          <div class="floating-box">
            <?php echo $lang['VACATION_DAYS_PER_YEAR']?>: <br>
            <input type="number" name="vacDays<?php echo $x; ?>" value="<?php echo $vacDaysPerYear; ?>"/><br><br>

            <?php echo $lang['AMOUNT_VACATION_DAYS']?>: <br>
            <input type="number" step=any  name="vacDaysCredit<?php echo $x; ?>" value="<?php echo $vacDaysCredit; ?>"/><br><br>

            <?php echo $lang['OVERTIME_ALLOWANCE']?>: <br>
            <input type="number" name="overTimeAll<?php echo $x; ?>" value="<?php echo $overTimeAll; ?>"/><br><br>

            <?php echo $lang['TAKE_BREAK_AFTER']?>: <br>
            <input type="number" step=any  name="pauseAfter<?php echo $x; ?>" value="<?php echo $pauseAfter; ?>"/><br><br>

            <?php echo $lang['HOURS_OF_REST']?>: <br>
            <input type="number" step=any  name="rest<?php echo $x; ?>" value="<?php echo $rest; ?>"/><br><br>
          </div>
          
          <div class="floating-box">
            <?php echo $lang['GENDER']?>: <br>
            <input type="radio" name="gender<?php echo $x; ?>" value="female" <?php if($gender == 'female'){echo 'checked';} ?> />Female <br>
            <input type="radio" name="gender<?php echo $x; ?>" value="male" <?php if($gender == 'male'){echo 'checked';} ?> />Male <br>

            <br><br>
            <?php echo $lang['ALLOW_PRJBKING_ACCESS']?>: <br>
            <input type="radio" name="enableProjecting<?php echo $x; ?>" value="TRUE" <?php if($enableProjecting == 'TRUE'){echo 'checked';} ?>>Yes <br>
            <input type="radio" name="enableProjecting<?php echo $x; ?>" value="FALSE" <?php if($enableProjecting == 'FALSE'){echo 'checked';} ?>>No <br>
            <br>

            Assign: <br>

            <?php
            $sql = "SELECT * FROM $companyTable";
            $companyResult = $conn->query($sql);
              while($companyRow = $companyResult->fetch_assoc()){
                $sql = "SELECT * FROM $companyToUserRelationshipTable WHERE companyID = " . $companyRow['id'] . " AND userID = $x";
                $resultset2 = $conn->query($sql);
                if($resultset2 && $resultset2->num_rows >0){
                  $selected = 'checked';
                } else {
                  $selected = '';
                }
                echo "<input type='checkbox' $selected name='company".$x."[]' value=" .$companyRow['id']. ">" . $companyRow['name'] ."<br>";
              }
            ?>
            <br><br>

          </div>

          <div class="floating-box">
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
                <td> <input type="text" name="mon<?php echo $x; ?>" size=2 value= <?php echo $mon; ?> </td>
                <td> <input type="text" name="tue<?php echo $x; ?>" size=2 value= <?php echo $tue; ?> </td>
                <td> <input type="text" name="wed<?php echo $x; ?>" size=2 value= <?php echo $wed; ?> </td>
                <td> <input type="text" name="thu<?php echo $x; ?>" size=2 value= <?php echo $thu; ?> </td>
                <td> <input type="text" name="fri<?php echo $x; ?>" size=2 value= <?php echo $fri; ?> </td>
                <td> <input type="text" name="sat<?php echo $x; ?>" size=2 value= <?php echo $sat; ?> </td>
                <td> <input type="text" name="sun<?php echo $x; ?>" size=2 value= <?php echo $sun; ?> </td>
              </tr>
            </table><br><br>

            <?php echo $lang['NEW_PASSWORD']?>: <br>
            <input type="password" name="password<?php echo $x; ?>"/><br>

            <?php echo $lang['NEW_PASSWORD_CONFIRM']?>: <br>
            <input type="password" name="passwordConfirm<?php echo $x; ?>"> <span class="error"><?php echo $passErr; ?></span><br>

          </div>

        </fieldset>

        <br> <br>
        <input type="submit" name="submit<?php echo $x; ?>" value="Submit"/> <br><br>
      </div>
      <?php
    }
  }
  ?>
<br><br>
<a href='register_choice.php'>Register New User</a>
</form>
</body>
