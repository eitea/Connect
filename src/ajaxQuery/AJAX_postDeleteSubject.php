<?php
session_start();
require dirname(__DIR__) . "/connection.php";

$userID = $_SESSION["userid"] ?? -1;

if (isset($_GET["partner"], $_GET["subject"]) && !empty($_SESSION["userid"])) {
    $subject = test_input($_GET["subject"]);
    $partner = intval($_GET["partner"]);

    // delete subject
    $stmt = $conn->prepare('DELETE FROM messages WHERE subject = ? AND ((userid = ? AND partnerID = ?) OR (userid = ? AND partnerID = ?))');
    $stmt->bind_param('siiii', $subject, $userID, $partner, $partner, $userID);

    $stmt->execute();

    if(!$stmt->error){
        echo "success " . $subject . " " . $userID . " " . $partner;
    } else {
        echo $stmt->error;
    }

} else {
    die('Invalid Request');
}

function test_input($data){
    require dirname(__DIR__) . "/connection.php";
    $data = $conn->escape_string($data);
    $data = trim($data);
    return $data;
}
?>
