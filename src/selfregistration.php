<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>
  <title>Register</title>
</head>
<style>
.robot-control{
  display:none;
}
body{
  margin: 100px 0px 0px 10%;
}
</style>
<!-- navbar -->
<nav class="navbar navbar-default navbar-fixed-top hidden-xs">
  <div class="container-fluid">
    <div class="navbar-header">
      <a class="navbar-brand" href="home.php">T-Time</a>
    </div>
  </div>
</nav>
<!-- /navbar -->
<?php
if(!empty($_POST['captcha'])){
  die("");
} else {
  require 'language.php';
}

if (!empty($_POST["firstname"]) && !empty($_POST['lastname'])) {
  $firstname = test_input($_POST["firstname"]);
  $lastname = test_input($_POST['lastname']);
  header("Location: selfregistration_2.php?gn=$firstname&sn=$lastname" );
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>

<body>
  <div class="page-header">
    <h3><?php echo $lang['REGISTER_NEW_USER']; ?></h3>
  </div>
  Please enter your Name: <small>(All fields required)</small>
  <br><br>
  <form method="post">
    <div class=container>
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
    <div class="robot-control"> <input type="text" name="captcha" value="" /></div>
  </form>
</body>
</html>
