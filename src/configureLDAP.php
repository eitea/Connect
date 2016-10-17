<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">


</head>
<body>
  <?php
  require "connection.php";
  require "connectionLDAP.php";
  session_start();
  if (!isset($_SESSION['userid'])) {
    die('Please <a href="login.php">login</a> first.');
  }
  if ($_SESSION['userid'] != 1) {
    die('Access denied. <a href="logout.php"> return</a>');
  }

  $query = "SELECT * FROM $configTable;";
  $row = mysqli_query($conn, $query)->fetch_assoc();
  $cd = $row['cooldownTimer'];
  $bufferTime = $row['bookingTimeBuffer'];

  $newConn = $ldapConnect;
  $newUser = $ldap_username;
  $newPass = $ldap_password;

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['ldapDomain'])) {
      $newConn = $_POST['ldapDomain'];
    }
    if (isset($_POST['ldap_user'])) {
      $newUser = $_POST['ldap_user'];
    }
    if (isset($_POST['ldap_pass'])) {
      $newPass = $_POST['ldap_pass'];
    }

    $sql = "UPDATE $adminLDAPTable SET ldapConnect = '$newConn', ldapPassword = '$newPass', ldapUsername = '$newUser' WHERE adminID = 1";
    if ($conn->query($sql)) {
      echo '<div class="alert alert-success fade in"> <strong>O.K. - </strong>Change was successful. </div>';
    } else {
      echo mysqli_error($conn);
    }

    if(isset($_POST['cd'])){
      $cd = $_POST['cd'];
      $sql = "UPDATE $configTable SET cooldownTimer = '$cd';";
      $conn->query($sql);
    }
    if(isset($_POST['bufferTime'])){
      $bufferTime = $_POST['bufferTime'];
      $sql = "UPDATE $configTable SET bookingTimeBuffer = '$bufferTime';";
      $conn->query($sql);
    }
  }
  ?>

  <h1>LDAP</h1>

  <form method="post">

    LDAP Configuration Settings<br>

    <fieldset><br><br>

      Ldap Domain:    <input type="text" name="ldapDomain" value="<?php echo $newConn; ?>"><br><br>

      Ldap Username:  <input type="text" name="ldap_user" value="<?php echo $newUser; ?>"><br><br>

      Ldap Password:  <input type="password" name="ldap_pass" value="<?php echo $newPass; ?>"><br><br><br>


      <input type="submit" name= "ldapSubmit" value="Submit"><br>
    </fieldset>
    <br>
    <fieldset><br><br>

      Disable-time for In/Out Buttons:    <input type="number" name="cd" value="<?php echo $cd; ?>"><br><br>

      Project-Time Buffer:                <input type="number" name="bufferTime" value="<?php echo $bufferTime ?>" ><br><br>


      <input type="submit" name="cdSubmit" value="Submit"> <br><br>
    </fieldset>
    <br><br>
  </form>
</body>
