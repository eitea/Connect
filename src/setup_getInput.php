<?php
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
      header("Location: setup.php?companyName=$companyName&psw=$psw");
    }
  } else {
    echo 'Missing Fields. <br><br>';
  }
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}
?>
<head>
    <link rel="stylesheet" href="../bootstrap/css/bootstrap.min.css">
    <style>
    body{
      text-align: center;
    }
    </style>
</head>
<body>
  <form method='post'>
  <h1>Login Data</h1><br><br>


  Login Username: Admin <br><br>

  * Login Password: <br>
  <input type='password' name='adminPass' value=''> <br><br>

  * Company Name: <br>
  <input type="text" name='companyName' placeholder='Company Name' >  <br><br>


  <br><hr><div style='text-align:right; margin-right:200px'><small>* required</small></div><br>

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
