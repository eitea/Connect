<?php
session_start();

$userID = $_SESSION["userid"];

if (isset($_GET["partner"], $_GET["subject"], $_GET["message"]) && !empty($_SESSION["userid"])) {
    $partner = intval($_GET["partner"]);
    $subject = test_input($_GET["subject"]);
    $message = test_input($_GET["message"]);

    require dirname(__DIR__) . "/connection.php";

    // insert a new message into the database
    $sql = "INSERT INTO messages (userID, partnerID, subject, message, sent, seen) VALUES ($userID, $partner, '$subject', '$message', CURRENT_TIMESTAMP, 'FALSE')";
    $conn->query($sql);

    if(!$conn->error)
        showInfo($lang['MESSAGE_SENT']);
    else
        showError($conn->error);
        
} elseif(isset($_GET["taskID"], $_GET["taskName"], $_GET["message"]) && !empty($_SESSION["userid"])) {
    $taskID = test_input($_GET["taskID"]);
    $taskName = test_input($_GET["taskName"]);
    $message = test_input($_GET["message"]);

    require dirname(__DIR__) . "/connection.php";

    $conn->query("INSERT INTO taskmessages (userID, taskID, taskName, message) VALUES ($userID, '$taskID', '$taskName', '$message')");
    echo $conn->error;
} else {
    die('Invalid Request');
}

function test_input($data) {
    require dirname(__DIR__) . "/connection.php";
    $data = $conn->escape_string($data);
    $data = trim($data);
    return $data;
}