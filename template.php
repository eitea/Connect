<!DOCTYPE html>
<html lang="en">
<head>
<link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
</head>
<body>
<?php
$a = "Hello \ this is a' test";
$b = addslashes($a);
echo $b .'<br>';
echo stripslashes($b);

 ?>
</body>
</html>
