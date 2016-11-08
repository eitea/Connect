<?php
session_start();
if (isset($_SESSION['userid'])) {
  if ($_SESSION['userid'] == 1){
    header("refresh:0;url=src/adminHome.php" );
  } else {
    header( "refresh:0;url=src/userHome.php" );
  }
}

if(!file_exists('src/connection_config.php')){
  header("Location: src/setup_getInput.php");
}

require 'src/connection.php';

$headers = apache_request_headers();
$userAgentHead =  $headers['User-Agent'];

//EI-TEA Zeiterfassung v13 Code3A5B
$sql = "SELECT * FROM $piConnTable WHERE header = '$userAgentHead'";
$result = $conn->query($sql);

if($result && $result->num_rows > 0){
  header("refresh:0;url=src_pi/checkin_step1_select.php");
} else {
  header("refresh:0;url='src/login.php'");
}
