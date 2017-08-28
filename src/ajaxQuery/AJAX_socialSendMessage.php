<?php
session_start();
if (isset($_GET["partner"], $_GET["message"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $userID = $_SESSION["userid"];
    $message = test_input($_GET["message"]);
}
else {
    die('Invalid Request');
}

$userID = $_SESSION["userid"];
require dirname(__DIR__) . "/connection.php";
$conn->query("INSERT INTO socialmessages (userID, partner, message) VALUES ($userID, $partner, '$message')");
echo $conn->error;

function test_input($data)
{
    $data = preg_replace("~[^A-Za-z0-9\-?!=:.,/@€§$%()+*öäüÖÄÜß_ ]~", "", $data);
    $data = trim($data);
    return $data;
}