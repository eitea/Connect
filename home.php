<?php
session_start();
if (isset($_SESSION['userid'])) {
  header("Location: ../src/home.php" );
}

if(!file_exists('src/connection_config.php')){
  header("Location: src/setup_getInput.php");
}


$headers = apache_request_headers();
$userAgentHead =  $headers['User-Agent'];

//EI-TEA Zeiterfassung v13 Code3A5B
include 'src/connection.php';
$sql = "SELECT * FROM $piConnTable WHERE header = '$userAgentHead'";
$result = $conn->query($sql);

if($result && $result->num_rows > 0){
  header("Location: src_pi/checkin_step1_select.php");
} else {
  header("Location: src/login.php");
}
