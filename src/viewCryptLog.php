<?php session_start(); $userID = $_SESSION["userid"] or die(); require 'validate.php'; enableToCore($userID);?>
<a href="../system/password">Next</a><br>
<?php
$cryptlog = file_get_contents("./cryptlog.txt");
$cryptlog = str_replace("\r\n","<br>\n",$cryptlog);
echo $cryptlog;
?>