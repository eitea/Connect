<?php
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])){
  require "../src/connection.php";

  $headers = apache_request_headers();
  $userAgentHead =  $headers['User-Agent'];

  //EI-TEA Zeiterfassung v13 Code3A5B
  $sql = "SELECT * FROM $piConnTable WHERE header = '$userAgenthead'";
  if($result = $conn->query($sql) && $result->num_rows > 0){
    header("refresh:0;url=checkin_step1_select.php");
  } else {
    header("refresh:0;url='../src/login.php'");
  }
  /*
  foreach ($headers as $header => $value) {
      echo "$header: $value <br />\n";
  }
  */
}
?>

<script>
document.getElementById('loginSubmit').submit();
</script>


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

<body>
<form method='post'>
<input type='submit' id='loginSubmit' name='submit' value='Continue'>
<input type="text" id="funZone" name="funZone" style="display:none" value="">
</form>
</body>
