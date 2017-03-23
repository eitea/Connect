<!DOCTYPE html>
<html>
<meta http-equiv="Cache-Control" content="max-age=600, must-revalidate">
<link href="../plugins/homeMenu/loginMenu.css" rel="stylesheet">
<head>
  <title>Login</title>
</head>

<?php
//TODO: put a brute-force stopper in here
if(!empty($_POST['captcha'])){
  die("");
}

require 'connection.php';
require 'createTimestamps.php';
include 'version_number.php';
require 'validate.php'; denyToCloud();

//check if this is the first time this app runs
if(!file_exists('connection_config.php')){
  header("Location: setup_getInput.php");
}
$invalidLogin = "";
if (!empty($_POST['loginName']) && !empty($_POST['password']) && !isset($_POST['cancelButton'])) {
  $query = "SELECT * FROM  $userTable  WHERE email = '" . strip_input($_POST['loginName']) . "' ";
  $result = mysqli_query($conn, $query);
  if($result){
    $row = $result->fetch_assoc();
  }
  if (crypt($_POST['password'], $row['psw']) == $row['psw']) {
    session_destroy();
    session_start();
    $_SESSION['userid'] = $row['id'];
    $_SESSION['firstname'] = $row['firstname'];
    $_SESSION['language'] = $row['preferredLang'];
    $timeZone = $_POST['funZone'];
    $_SESSION['timeToUTC'] = $timeZone;

    //check for updates, if core admin
    require "language.php";
    $sql = "SELECT * FROM $roleTable WHERE userID = ".$row['id']." AND isCoreAdmin = 'TRUE'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
      $sql = "SELECT * FROM $adminLDAPTable;";
      $result = mysqli_query($conn, $sql);
      $row = $result->fetch_assoc();
      if($row['version'] < $VERSION_NUMBER){
        header("refresh:3;url=doUpdate.php");
        die ($lang['UPDATE_REQUIRED']. $lang['AUTOREDIRECT']. '<a href="doUpdate.php">update</a>');
      }
    }
    header('Location: home.php');
  } else {
    $invalidLogin = "Invalid Username/ Password!";
  }
}

$rowConfigTable['enableReg'] = FALSE;
$result = $conn->query("SELECT enableReg FROM $configTable");
if($result && $result->num_rows > 0){
  $rowConfigTable = $result->fetch_assoc();
}


function strip_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  $data = str_replace(' ', '', $data);
  return $data;
}
?>

<body>
  <div id="footer">
    <form method="POST" action="login.php">
      <label for="in">E-Mail: </label>  <input id="in" type="text" name="loginName" value="" autofocus></input><br>

      <label for="pw">Password: </label> <input id="pw" type="password" name="password" value=""></input><input type="submit" name="textEnterSubmitsThis" style="visibility:hidden; display:none;" value="Cancel"><br><br>

      <input type="submit" name="cancelButton" value="Cancel"> <input type="submit" name="login" value="Submit"><br>

      <input type="text" readonly name="invalidLogin" style="border:0; background:0; color:white; text-align:right;" value="<?php echo $invalidLogin; ?>">
      <input type="number" id="funZone" name="funZone" style="display:none" readonly><div class="robot-control"> <input type="text" name="captcha" value="" /></div>
    </form>
    <?php if($rowConfigTable['enableReg'] == 'TRUE'){echo '<a href="selfregistration.php">Register</a>';} ?>
  </div>

  <script>
  var today = new Date();
  var timeZone = today.getTimezoneOffset() /(-60);
  if(today.dst){timeZone--;}
  document.getElementById("funZone").value = timeZone;
  Date.prototype.stdTimezoneOffset = function() {
    var jan = new Date(this.getFullYear(), 0, 1);
    var jul = new Date(this.getFullYear(), 6, 1);
    return Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());
  }
  Date.prototype.dst = function() {
    return this.getTimezoneOffset() < this.stdTimezoneOffset();
  }
  </script>

  <div style="position: absolute; bottom: 5px;">
    <a href=http://www.eitea.at target='_blank'>EI-TEA Partner GmbH - <?php echo $VERSION_TEXT; ?></a>
  </div>
</body>
</html>
