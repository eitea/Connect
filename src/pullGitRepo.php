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

$repo = new \SebastianBergmann\Git\Git(dirname(dirname(realpath("pullGitRepo.php"))));
$repo->checkout("");

session_destroy();
?>

<script type='text/javascript'>
window.close();
</script>
