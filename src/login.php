<!DOCTYPE html>
<html>
<head>
  <title>Tea-time</title>
  <link rel="stylesheet" href="../css/login.css">
  <link rel="stylesheet" href="../css/homeMenu.css">
</head>


<?php
require 'version_number.php';

if(!file_exists('connection_config.php')){
  header("refresh:0;url=setup_getInput.php");
}

$invalidLogin = "";
  if (!empty($_POST['loginName']) && !empty($_POST['password']) && !isset($_POST['cancelButton'])) {
    require 'connection.php';
    require 'createTimestamps.php';

    $query = "SELECT * FROM  $userTable  WHERE email = '" . strip_input($_POST['loginName']) . "' ";
    $result = mysqli_query($conn, $query);
    if($result){
      $row = $result->fetch_assoc();
    }

    if (crypt($_POST['password'], $row['psw']) == $row['psw']) {
      session_start();
      $_SESSION['userid'] = $row['id'];
      $_SESSION['language'] = $row['preferredLang'];
      $timeZone = $_POST['funZone'];
      $_SESSION['timeToUTC'] = $timeZone;

      require "language.php";

      if ($row['id'] != 1){
        header( "refresh:0;url=userHome.php?link=userSummary.php");
        die ($lang['AUTOREDIRECT'] . '<a href="userHome.php?link=userSummary.php">redirect</a>');
      } else {
        $sql = "SELECT * FROM $adminLDAPTable;";
        $result = mysqli_query($conn, $sql);
          $row = $result->fetch_assoc();
          if($row['version'] < $VERSION_NUMBER){
            header("refresh:3;url=doUpdate.php");
            die ($lang['UPDATE_REQUIRED']. $lang['AUTOREDIRECT']. '<a href="doUpdate.php">update</a>');
          } else {
           header("refresh:0;url=adminHome.php");
            die ($lang['AUTOREDIRECT']. '<a href="adminHome.php">redirect</a>');
          }
        }
    } else {
      $invalidLogin = "Invalid Username/ Password!";
    }
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
  <label for="in">E-Mail: </label>  <input id="in" type="text" name="loginName" value=""></input><br>

  <label for="pw">Password: </label> <input id="pw" type="password" name="password" value=""></input><input type="submit" name="textEnterSubmitsThis" style="visibility:hidden; display:none;" value="Cancel"><br><br>

  <input type="submit" name="cancelButton" value="Cancel"> <input type="submit" name="login" value="Submit"><br>

  <input type="text" name="invalidLogin" style="border:0; background:0; color:white; text-align:right;" value="<?php echo $invalidLogin; ?>"> <input type="text" id="funZone" name="funZone" style="display:none" value="">
  </form>
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
    <a href=http://www.eitea.at style="color:white;text-decoration: none;">EI-TEA Partner GmbH</a>
</div>
</body>
</html>
