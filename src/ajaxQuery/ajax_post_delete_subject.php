<?php
session_start();
require dirname(__DIR__) . "/connection.php";

$userID = $_SESSION["userid"] ?? -1;

if (isset($_GET["partner"], $_GET["subject"]) && !empty($_SESSION["userid"])) {
    $subject = test_input($_GET["subject"]);
    $partner = intval($_GET["partner"]);

    $stmt = $conn->prepare("UPDATE messages SET user_deleted = 'TRUE' WHERE subject = ? AND userID = ? AND partnerID = ?");
    $stmt2 = $conn->prepare("UPDATE messages SET partner_deleted = 'TRUE' WHERE subject = ? AND userID = ? AND partnerID = ?");
    $stmt->bind_param("sii", $subject, $userID, $partner);
    $stmt2->bind_param("sii", $subject, $partner, $userID);
    $stmt->execute();
    $stmt2->execute();
    if (!$stmt->error) {
        echo "success " . $subject . " " . $userID . " " . $partner;
    } else {
        echo $stmt->error;
    }
    if (!$stmt2->error) {
        echo "success " . $subject . " " . $userID . " " . $partner;
    } else {
        echo $stmt2->error;
    }
    $conn->query("DELETE FROM messages WHERE user_deleted = 'TRUE' AND partner_deleted = 'TRUE'");
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
?>
