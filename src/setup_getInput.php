<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
  <script src="../plugins/jQuery/jquery-3.1.0.min.js"></script>
  <script src="../bootstrap/js/bootstrap.min.js"></script>

  <link rel="stylesheet" type="text/css" href="../plugins/select2/css/select2.min.css">
  <script src='../plugins/select2/js/select2.js'></script>
</head>
<script>
$(document).ready(function() {
  $(".js-example-basic-single").select2();
});
</script>

<?php
require_once "validate.php"; denyToCloud();
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

//leave only numbers and letters
function clean($string) {
  return preg_replace('/[^\.A-Za-z0-9\-]/', '', $string);
}
function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if(!empty($_POST['companyName']) && !empty($_POST['adminPass']) && !empty($_POST['firstname']) && !empty($_POST['type']) && !empty($_POST['localPart']) && !empty($_POST['domainPart'])){
    $myfile = fopen('connection_config.php', 'w');
    $txt = '<?php
    $servername = "'.test_input($_POST['serverName']).'";
    $username = "'.test_input($_POST['mysqlUsername']).'";
    $password = "'.test_input($_POST['pass']).'";
    $dbName = "'.test_input($_POST['dbName']).'";';
    fwrite($myfile, $txt);
    fclose($myfile);

    if(!file_exists('connection_config.php')){
      die('Permission denied. Please grant PHP permission to create files.');
    } else {
      $psw = password_hash($_POST['adminPass'], PASSWORD_BCRYPT);
      $companyName = rawurlencode(test_input($_POST['companyName']));
      $companyType = rawurlencode(test_input($_POST['type']));
      $firstname = rawurlencode(test_input($_POST['firstname']));
      $lastname = rawurlencode(test_input($_POST['lastname']));
      $loginname = rawurlencode(clean($_POST['localPart']) .'@'.clean($_POST['domainPart']));

      redirect("setup.php?companyName=$companyName&companyType=$companyType&psw=$psw&first=$firstname&last=$lastname&login=$loginname");
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
      <div class="col-xs-3 col-xs-offset-3">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon" style=min-width:150px>
              Firstname
            </span>
            <input type="text" class="form-control" name="firstname" placeholder="Firstname..">
          </div>
        </div>
      </div>
      <div class="col-xs-3">
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
      <div class="col-xs-6 col-xs-offset-3">
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
      <div class="col-xs-4 col-xs-offset-3">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon text-warning" style=min-width:150px>
              Company Name
            </span>
            <input type='text' class="form-control" name='companyName' placeholder='Company Name'>
          </div>
        </div>
      </div>
      <div class="col-xs-2">
        <div class="form-group">
          <select name="type" class="js-example-basic-single btn-block">
            <option selected>...</option>
            <option value="GmbH">GmbH</option>
            <option value="AG">AG</option>
            <option value="OG">OG</option>
            <option value="KG">KG</option>
            <option value="EU">EU</option>
            <option value="-">Sonstiges</option>
          </select>
        </div>
      </div>
    </div>
    <br><br>

    <p>Your Login E-Mail</p>
    <div class="row">
      <div class="col-xs-6 col-xs-offset-3">
        <div class="form-group">
          <div class="input-group">
            <input type='text' class="form-control" name='localPart' placeholder='name'  style=width:400px >
            <span class="input-group-addon text-warning">
              @
            </span>
            <input type='text' class="form-control" name='domainPart' placeholder="domain.com">
          </div>
        </div>
        <small> * The Domain will be used for every login adress that will be created. Cannot be changed afterwards.<br><b> May not contain any special characters! </b></small>
      </div>
    </div>
    <br><hr><br>

    <h1>MySQL Database Connection</h1><br><br>

    <div class="row">
      <div class="col-xs-6 col-xs-offset-3">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon" style=min-width:150px>
              Server Address
            </span>
            <input type="text" class="form-control" name='serverName' value = "localhost">
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-6 col-xs-offset-3">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon" style=min-width:150px>
              Username
            </span>
            <input type="text" class="form-control" name='mysqlUsername' value = 'root'>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-6 col-xs-offset-3">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon" style=min-width:150px>
              Password
            </span>
            <input type="text" class="form-control" name='pass' value = ''>
          </div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xs-6 col-xs-offset-3">
        <div class="form-group">
          <div class="input-group">
            <span class="input-group-addon" style=min-width:150px>
              DB Name
            </span>
            <input type="text" class="form-control" name='dbName' value = 'Zeit1'>
          </div>
        </div>
      </div>
    </div>


    <br><hr><br>

    <div class="container">
      <div class="col-xs-3 col-xs-offset-9">
        <button type='submit' name'submit' class="btn btn-warning">Continue</button>
      </div>
    </div>
  </form>
</body>
