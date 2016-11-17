<?php
require "../src/connection.php";
require "../src/createTimestamps.php";
require "../src/ckInOut.php";


$headers = apache_request_headers();
$userAgentHead =  $headers['User-Agent'];

$sql = "SELECT * FROM $piConnTable WHERE header = '$userAgentHead'";
$result = $conn->query($sql);
if(!$result || $result->num_rows <= 0){
  die('Access denied. <a href="../src/logout.php"> return</a>');
}

$userID = $_GET['id'];
if(isset($_POST['cancel'])){
  header("Location: checkin_step1_select.php");
}

if(isset($_POST['enter']) && !empty($_POST['mvar'])){
  session_start();
  $_SESSION['timeToUTC'] = $_POST['funZone'];

  $pin = $_POST['mvar'];
  $sql = "SELECT id FROM $userTable WHERE id = $userID AND terminalPin = '$pin'";
  $result = $conn->query($sql);
  if($result && $result->num_rows > 0){ //accept Pinz
    $sql = "SELECT * FROM $logTable WHERE userID = $userID AND time LIKE '". substr(getCurrentTimestamp(),0,10) . " %' ";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){ //determine checkin or checkout
      checkOut($userID);
    } else {
      checkIn($userID);
    }
    session_destroy();
    echo "<div style=color:green;font-size:large > O.K </div>";
    header("refresh:2;url=checkin_step1_select.php");
  } else {
    echo 'false pin';
  }
}

?>

<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" href="../css/mobile.css">

  <style>
  input[type=button]{
    width:150px;
    height:75px;
    border-width:1px;
    border-color:#246b8c;
    display: inline-block;
    font-size: 28px;
    font-weight:lighter;
    color:#3a95d0;
    background-color:white;
  }

  input[type=text]{
    width:500px;
    height:60px;
    text-align: center;
    border:none;
    display: inline-block;
    font-size: 30px;
    color:#3a95d0;
    background-color:white;
  }

  input[type="submit"]{
    background-color: white;
    border-style:double;
    display:inline-block;
    color:#4089cc;
    padding:5px 10px;
    font-weight: lighter;
    font-size:25px;
  }

  </style>
</head>

<body>
<form  method="post">

PIN: <input id=mvar type="text" value="" readonly name="mvar" />
<br>
<input type="button" class="fbutton" value="1" id="1" onClick=addNumber(this); ><input type="button" class="fbutton" value="2" id="2" onClick=addNumber(this); ><input type="button" class="fbutton" value="3" id="3" onClick=addNumber(this); > <br>

<input type="button" class="fbutton" value="4" id="4" onClick=addNumber(this); ><input type="button" class="fbutton" value="5" id="5" onClick=addNumber(this); ><input type="button" class="fbutton"   value="6" id="6" onClick=addNumber(this); > <br>

<input type="button" class="fbutton" value="7" id="7" onClick=addNumber(this); ><input type="button" class="fbutton" value="8" id="8" onClick=addNumber(this); ><input type="button" class="fbutton" value="9" id="9" onClick=addNumber(this); > <br>

<input type="button" class="fbutton" value="*" id='S' onClick=addNumber(this); ><input type="button" class="fbutton" value="0" id="0" onClick=addNumber(this); ><input type="button" class="fbutton" value="Del" onClick=addNumber(this); > <br>

<script>
function addNumber(element){
  if(element.value == 'Del'){
    document.getElementById('mvar').value = document.getElementById('mvar').value.slice(0,-1);
  } else {
    document.getElementById('mvar').value = document.getElementById('mvar').value+element.value;
  }
}
</script>

<div style=float:left>
<input type=submit name=cancel value=Return>
</div>

<div style=float:right>
<input type=submit name=enter value=Submit>
<input type="text" id="funZone" name="funZone" style="display:none" value="">
</div>

</form>
</body>

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
