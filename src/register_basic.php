<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">

<style>
.error {
  color: #FF0000;
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

  require 'connection.php';
  require 'language.php';
  require 'createTimestamps.php';

  $accept = TRUE;
  $firstname = $lastname = $email = "";
  $firstnameErr = $lastnameErr = $emailErr = "";

  if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty($_POST["firstname"])) {
      $firstnameErr = "*Name is required";
      $accept = FALSE;
    } else {
      $firstname = test_input($_POST["firstname"]);
    }

    if (empty($_POST["lastname"])) {
      $lastnameErr = "*Last name is required";
      $accept = FALSE;
    } else {
      $lastname = test_input($_POST['lastname']);
    }

    if (!empty($_POST["email"]) && filter_var(test_input($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
      $email = test_input($_POST["email"]);
      $result = $conn->query("SELECT * FROM $userTable WHERE email = $email");
      if($result->num_rows > 0 ){
        $accept = FALSE;
        $emailErr = "*Email already in Use";
      }
    } else {
      $emailErr = "*Email invalid";
      $accept = FALSE;
    }

    if ($accept) {
      header("refresh:0;url=register_optionals.php?gn=$firstname&sn=$lastname&mail=$email" );
    }
  }

  ?>
<form method="post">
  <div style="text-align:center">
    <h1><?php echo $lang['REGISTER_NEW_USER']; ?></h1> <br><br><br>

      <?php echo $lang['FIRSTNAME']; ?>: <br>
      <input type="text" name="firstname" value="<?php echo $firstname; ?>">
      <span class="error"> <?php echo $firstnameErr; ?></span> <br><br>

      <?php echo $lang['LASTNAME']; ?>: <br>
      <input type="text" name="lastname" value="<?php echo $lastname; ?>">
      <span class="error"> <?php echo $lastnameErr; ?></span> <br><br>

      E-Mail: <br>
      <input type="text" name="email" value="<?php echo $email; ?>">
      <span class="error"> <?php echo $emailErr; ?></span> <br><br><br>
<br>
  </div>
  <br><br><br>
  <div style="text-align:right">
  <input type="submit" name="createUser" value="Next"> <small> * All fields required</small>
</div>
</form>
</body>
</html>
