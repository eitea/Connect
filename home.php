<?php
session_start();
if (isset($_SESSION['userid'])) {
  if ($_SESSION['userid'] == 1){
    header("refresh:0;url=src/adminHome.php" );
  } else {
    header( "refresh:0;url=src/userHome.php" );
  }
} else {
  header( "refresh:0;url=src/login.php" );
}
