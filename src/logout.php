<?php
//alles hat ein ende, nur mein haar hat drei
session_start();
session_destroy();
header( "Location: ../home.php");
