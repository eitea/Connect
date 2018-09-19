<?php $userID = $_SESSION["userid"] or die(); require_once dirname(dirname(__DIR__)) . '/validate.php'; ?>
<a href="../system/password">Next</a><br>
<?php echo nl2br(file_get_contents("./cryptlog.txt")); ?>
