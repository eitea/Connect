<?php
session_start();
if (isset($_GET["partner"], $_GET["message"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $userID = $_SESSION["userid"];
    $message = test_input($_GET["message"]);
    require dirname(__DIR__) . "/connection.php";
    $conn->query("INSERT INTO socialmessages (userID, partner, message) VALUES ($userID, $partner, '$message')");
    echo $conn->error;
} elseif(isset($_GET["group"], $_GET["message"]) && !empty($_SESSION["userid"])){
    $group = intval($_GET["group"]);
    $userID = $_SESSION["userid"];
    $message = test_input($_GET["message"]);
    require dirname(__DIR__) . "/connection.php";
    $conn->query("INSERT INTO socialgroupmessages (userID, groupID, message, seen) VALUES ($userID, $group, '$message', '$userID')");
    echo $conn->error;
} else {
    die('Invalid Request');
}


function test_input($data)
{
    require dirname(__DIR__) . "/connection.php";
    $data = $conn->escape_string($data);
    $data = trim($data);
    return $data;
}