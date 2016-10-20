<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}

require "connection.php";
$sql = "SELECT * FROM $adminGitHubTable WHERE sslVerify = 'TRUE'";
$result = $conn->query($sql);

$repositoryPath = dirname(dirname(realpath("pullGitRepo.php")));

if(!$result || $result->num_rows <= 0){ //sslVerify is False -> disable, else do nothing
  $command = 'git -C ' .$repositoryPath. ' config http.sslVerify "false" 2>&1';
  exec($command, $output, $returnValue);
  echo implode('<br>', $output) .'<br><br>';
}

$command = 'git -C ' .$repositoryPath. ' pull --force 2>&1';
exec($command, $output, $returnValue);
echo implode('<br>', $output) .'<br><br>';

session_destroy();
?>

<script type='text/javascript'>
//window.close();
</script>
