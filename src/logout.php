<?php
//alles hat ein ende, nur mein haar hat drei
session_start();
if(isset($_SESSION['dbConnect'] = $dbName;)){
  $location = "Location: ../../home.php";
} else {
  $location = "Location: ../home.php";
}
session_destroy();
header($location);
