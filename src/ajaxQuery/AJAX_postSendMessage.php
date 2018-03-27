<?php
session_start();

$userID = $_SESSION["userid"];

if (isset($_GET["partner"], $_GET["subject"], $_GET["message"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $subject = test_input($_GET["subject"]);
    $message = test_input($_GET["message"]);

    require dirname(__DIR__) . "/connection.php";

    // insert a new message into the database
    $conn->query("INSERT INTO messages (userID, partnerID, subject, message) VALUES ($userID, $partner, '$subject', '$message')");
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