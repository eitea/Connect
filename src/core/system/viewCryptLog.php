<?php session_start();
$userID = $_SESSION["userid"] or die();
require dirname(dirname(__DIR__)) . '/validate.php';
enableToCore($userID); ?>
<a href="../system/password">Next</a><br>
<?php echo nl2br(file_get_contents("./cryptlog.txt")); ?>
