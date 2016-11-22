<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <style>
  @font-face {
  font-family: 'Montserrat';
  font-style: normal;
  font-weight: 400;
  src: local('Montserrat-Regular'), url(http://fonts.gstatic.com/s/montserrat/v7/zhcz-_WihjSQC0oHJ9TCYPk_vArhqVIZ0nv9q090hN8.woff2) format('woff2');
}

#footer {
  clear: both;
  position:fixed;
  bottom:20%;
  width:100%;
  right;
  padding-top:20px;
  padding-bottom:20px;
  text-align: right;
  background-color: rgba(74, 70, 138, 0.37);
  border-top:solid;
  border-bottom:solid;
}

body{
  color:white;
  overflow:hidden;
  background-image:url(../images/linz.jpg);
  background-repeat: no-repeat;
  background-origin: content-box;
  background-attachment: fixed;
}

label{
  color:white;
  font-family:sans-serif;
  font-size:10pt;
  letter-spacing:1px;
}

input[name="cancelButton"]{
  background-color:#ab325a;
}
input[name="login"]{
  background-color:#51a33c;
  margin-right:10%;
}

input[type=submit]{
  border:none;
  color:white;
  width:100px;
  padding:8px;
  font-family:'Montserrat';
  text-transform: uppercase;
  font-weight: 400;
  box-shadow: 0 3px 5px #282828;
  font-size:8pt;
  letter-spacing:1px;
}

input[type=text], input[type=password]{
  margin-bottom:5px;
  width:240px;
  margin-right:10%;
}

  </style>
</head>

<?php
include 'version_number.php';
if(!file_exists('connection_config.php')){
  header("Location: setup_getInput.php");
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
      $_SESSION['firstname'] = $row['firstname'];
      $_SESSION['language'] = $row['preferredLang'];
      $timeZone = $_POST['funZone'];
      $_SESSION['timeToUTC'] = $timeZone;
      $_SESSION['posttimer'] = time();

      require "language.php";
      if ($row['id'] == 1){
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

  <input type="text" name="invalidLogin" style="border:0; background:0; color:white; text-align:right;" value="<?php echo $invalidLogin; ?>">
  <input type="number" id="funZone" name="funZone" style="display:none" readonly>
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
    <a href=http://www.eitea.at target='_blank' style="color:white;text-decoration: none;">EI-TEA Partner GmbH - <?php echo $VERSION_TEXT; ?></a>
</div>
</body>
</html>
</div>
</body>
</html>
