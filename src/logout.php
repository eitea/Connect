<?php
//alles hat ein ende, nur mein haar hat drei
session_start();
session_destroy();
if(isset($_SESSION['dbConnect'])){
  $location = "Location: ../../index.php";
} else {
  $location = "Location: ../home.php";
}
header($location);
