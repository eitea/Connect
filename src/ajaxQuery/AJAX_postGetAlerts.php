<?php 
session_start();
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/utilities.php";

$userID = $_SESSION["userid"] ?? -1;

if (isset($_REQUEST["partner"], $_REQUEST["subject"])  && !empty($_SESSION["userid"])) {
    $partner = intval($_REQUEST["partner"]);
    $subject = test_input($_REQUEST["subject"]);
    echo $conn->query("SELECT * FROM messages WHERE seen = 'FALSE' AND partnerID = $userID AND userID = $partner AND subject = '$subject'")->num_rows;
    echo $conn->error;
} elseif (!empty($_SESSION["userid"])) {
    $sql = "SELECT * FROM messages WHERE seen = 'FALSE' AND partnerID = $userID";
    echo $conn->query($sql)->num_rows;
    echo $conn->error;
} else {
    echo "asdf";
}