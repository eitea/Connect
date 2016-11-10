<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" href="../css/mobile.css">
</head>

<body>
<?php
  require "../src/connection.php";
  $headers = apache_request_headers();
  $userAgentHead =  $headers['User-Agent'];
  //EI-TEA Zeiterfassung v13 Code3A5B
  $sql = "SELECT * FROM $piConnTable WHERE header = '$userAgentHead'";
  $result = $conn->query($sql);
  if(!$result || $result->num_rows <= 0){
    die('Access denied. <a href="../src/logout.php"> return</a>');
  }

  $sql = "SELECT firstname, lastname, id FROM $userTable WHERE id != 1 ORDER BY firstname ASC";
  $result = $conn->query($sql);
  while($row = $result->fetch_assoc()){
    echo '<a href=checkin_step2_enterPIN.php?id='. $row['id'] .' >' . $row['firstname'] .' '. $row['lastname'] . '</a><br><br>';
  }
?>
</body>
