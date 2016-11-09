<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <link rel="stylesheet" href="../css/homeMenu.css">
  <link rel="stylesheet" type="text/css" href="../css/submitButt.css">
</head>
<form method=post>

<?php
session_start();
if (!isset($_SESSION['userid'])) {
  die('Please <a href="login.php">login</a> first.');
}
if ($_SESSION['userid'] != 1) {
  die('Access denied. <a href="logout.php"> return</a>');
}
require "connection.php";
require 'language.php';


if(isset($_POST['imtotallyFineWithThisOK'])):

$sql = "SELECT * FROM $adminGitHubTable WHERE sslVerify = 'TRUE'";
$result = $conn->query($sql);

$repositoryPath = dirname(dirname(realpath("pullGitRepo.php")));

if(!$result || $result->num_rows <= 0){ //sslVerify is False -> disable, else do nothing
  $command = 'git -C ' .$repositoryPath. ' config http.sslVerify "false" 2>&1';
  exec($command, $output, $returnValue);
  echo implode('<br>', $output) .'<br><br>';
}

$command = "git -C $repositoryPath fetch --force 2>&1";
exec($command, $output, $returnValue);

$command = "git -C $repositoryPath reset --hard origin/master 2>&1";
exec($command, $output, $returnValue);


echo implode('<br>', $output);

session_destroy();
die($lang['LOGOUT_MESSAGE']);
?>
<input type=submit name=okey value='O.K & Continue' />
<?php endif;

echo $lang['DO_YOU_REALLY_WANT_TO_UPDATE'] .'<br><br>';

echo $lang['MAY_TAKE_A_WHILE'] .'<br><br>';

?>

<input type=submit name=imtotallyFineWithThisOK value="<?php echo $lang['YES_I_WILL']?>" />
</form>
