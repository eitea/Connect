<?php
session_start();

$userID = $_SESSION["userid"];

if (isset($_GET["partner"], $_GET["message"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $message = test_input($_GET["message"]);
    require dirname(__DIR__) . "/connection.php";

    // insert a new message into the database
    $conn->query("INSERT INTO socialmessages (userID, partner, message) VALUES ($userID, $partner, '$message')");
    echo $conn->error;  //print the error
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