<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <link rel="stylesheet" href="../css/homeMenu.css">
</head>

<body>
  <form method=post>
<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}

require 'connection.php';

if(isset($_POST['gitSubmit'])){
  if(isset($_POST['ssl'])){
    $status = 'TRUE';
  } else {
    $status = 'FALSE';
  }
  $sql = "UPDATE $adminGitHubTable SET sslVerify = '$status'";
  $conn->query($sql);
}

$sql = "SELECT * FROM $adminGitHubTable";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$checked = $row['sslVerify']=='TRUE'?'checked':'';

?>

<h1>GitHub</h1>

  <fieldset><br>

    <input <?php echo $checked; ?> type=checkbox name=ssl value='TRUE'> SSL Certificate Validation </input>

    <br><br>
    <input type="submit" name= "gitSubmit" value="Save"><br>
  </fieldset>
</form>
</body>
