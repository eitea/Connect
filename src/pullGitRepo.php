<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}
require "connection.php";

include "../vendor/gitWrapper/Git.php";

$repositoryPath = realpath(dirname(dirname(realpath("pullGitRepo.php"))));

$command = 'git GIT_SSL_NO_VERIFY=true -C ' . escapeshellarg($this->repositoryPath) . ' pull';
if (DIRECTORY_SEPARATOR == '/') {
    $command = 'LC_ALL=en_US.UTF-8 ' . $command;
}
exec($command, $output, $returnValue);

session_destroy();
?>

<script type='text/javascript'>
//window.close();
</script>
