<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
    <script src="../bootstrap/js/bootstrap.min.js"></script>

</head>

<?php
function redirect($url){
  if (!headers_sent()) {
    header('Location: '.$url);
    exit;
  } else {
    echo '<script type="text/javascript">';
    echo 'window.location.href="'.$url.'";';
    echo '</script>';
    echo '<noscript>';
    echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
    echo '</noscript>'; exit;
  }
}
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['companyName']) && !empty($_POST['adminPass'])){
    $myfile = fopen('connection_config.php', 'w');
    $txt = '<?php
            $servername = "'.test_input($_POST['serverName']).'";
            $username = "'.test_input($_POST['userName']).'";
            $password = "'.test_input($_POST['pass']).'";
            $dbName = "'.test_input($_POST['dbName']).'";';
    fwrite($myfile, $txt);
    fclose($myfile);

    if(!file_exists('connection_config.php')){
      die('Permission denied. Please grant PHP permission to create files.');
    } else {
      $psw = password_hash($_POST['adminPass'], PASSWORD_BCRYPT);
      $companyName = $_POST['companyName'];
      redirect("setup.php?companyName=$companyName&psw=$psw");
    }
  } else {
    echo 'Missing Fields. <br><br>';
  }
}
?>

<body class="text-center">
  <form method='post'>
  <h1>Login Data</h1><br><br>

<div class="row">
  <div class="col-md-6 col-md-offset-3">
    <div class="form-group">
    <div class="input-group">
      <span class="input-group-addon" style=min-width:150px>
        Firstname
      </span>
      <input type="text" class="form-control" name="firstname" placeholder="Firstname..">
    </div>
  </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6 col-md-offset-3">
    <div class="form-group">
    <div class="input-group">
      <span class="input-group-addon" style=min-width:150px>
        Lastname
      </span>
      <input type="text" class="form-control" name="lastname" placeholder="Lastname..">
    </div>
  </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6 col-md-offset-3">
    <div class="form-group">
    <div class="input-group">
      <span class="input-group-addon text-warning" style=min-width:150px>
        Login Name:
      </span>
      <input type="text" class="form-control" name="userName" value="Admin">
    </div>
  </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6 col-md-offset-3">
    <div class="form-group">
    <div class="input-group">
      <span class="input-group-addon text-warning" style=min-width:150px>
        Login Password
      </span>
        <input type='password' class="form-control" name='adminPass' value=''>
    </div>
  </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6 col-md-offset-3">
    <div class="form-group">
    <div class="input-group">
      <span class="input-group-addon text-warning" style=min-width:150px>
        Company Name
      </span>
        <input type='text' class="form-control" name='companyName' placeholder='Company Name'>
    </div>
  </div>
  </div>
</div>

  <br><hr><br>

  <h1>MySQL Database Connection</h1><br><br>

  Server Address: <br>
  <input type='text' name='serverName' value = "localhost"> <br><br>

  Username: <br>
  <input type='text' name='userName' value = 'root'> <br><br>

  Password: <br>
  <input type='password' name='pass' value = ''> <br><br>

  DB Name: <br>
  <input type='text' name='dbName' value = 'Zeit1'> <br><br>

  <input type='submit' name'submit' value = 'Continue'>

</form>
</body>
