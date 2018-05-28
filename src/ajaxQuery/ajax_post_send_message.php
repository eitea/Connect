<?php
session_start();

isset($_SESSION["userid"]) or die("Not logged in");
$userID = $_SESSION["userid"];

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";

if (isset($_GET["partner"], $_GET["subject"], $_GET["message"])) {
    $partner = intval($_GET["partner"]);
    $subject = test_input($_GET["subject"]);
    $message = test_input($_GET["message"]);

    $conn->query("INSERT INTO messages (userID, partnerID, subject, message, sent, seen) VALUES ($userID, $partner, '$subject', '$message', CURRENT_TIMESTAMP, 'FALSE')");

    if (!$conn->error)
        echo ($lang['MESSAGE_SENT']);
    else
        showError($conn->error);

} elseif (isset($_GET["taskID"], $_GET["taskName"], $_GET["message"])) {
    $taskID = test_input($_GET["taskID"]);
    $taskName = test_input($_GET["taskName"]);
    $message = test_input($_GET["message"]);

    //TODO: encrypt message

    $conn->query("INSERT INTO taskmessages (userID, taskID, taskName, message) VALUES ($userID, '$taskID', '$taskName', '$message')");
    echo $conn->error;
} elseif (isset($_REQUEST["partner"], $_REQUEST["subject"], $_FILES["picture"])) {
    $partner = intval($_REQUEST["partner"]);
    $picture = uploadImage("picture", 0, 1);
    $subject = test_input($_REQUEST["subject"]);
    $stmt = $conn->prepare("INSERT INTO messages (userID, partnerID, subject, picture, sent, seen) VALUES ($userID, $partner, '$subject', ?, CURRENT_TIMESTAMP, 'FALSE')");
    echo $conn->error;
    $null = null;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $picture);
    $stmt->execute();
    echo $stmt->error;
} elseif (isset($_REQUEST["taskID"], $_REQUEST["taskName"], $_FILES["picture"])) {
    $taskID = test_input($_REQUEST["taskID"]);
    $taskName = test_input($_REQUEST["taskName"]);
    $picture = uploadImage("picture", 0, 1);
    $stmt = $conn->prepare("INSERT INTO taskmessages (userID, taskID, taskName, picture) VALUES ($userID, '$taskID', '$taskName', ?)");
    echo $conn->error;
    $null = null;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $picture);
    $stmt->execute();
    echo $stmt->error;
} else {
    die('Invalid Request');
}
