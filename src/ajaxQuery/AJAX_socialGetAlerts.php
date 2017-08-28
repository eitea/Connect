<?php 
session_start();
require dirname(__DIR__) . "/connection.php";
$userID = $_SESSION["userid"] or die("0");
if(!isset($_REQUEST["partner"])){
    echo $conn->query("SELECT * FROM socialmessages WHERE seen = 'FALSE' AND partner = $userID ")->num_rows;    
}else{
    $partner = intval($_REQUEST["partner"]);
    echo $conn->query("SELECT * FROM socialmessages WHERE seen = 'FALSE' AND partner = $userID AND userID = $partner")->num_rows;    
}