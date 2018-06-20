<?php 
session_start();
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";

isset($_SESSION["userid"]) or die("0");
$userID = $_SESSION["userid"];
$mode = "";
if (isset($_REQUEST["mode"])) {
    $mode = test_input($_REQUEST["mode"]);
}

if (isset($_REQUEST["partner"], $_REQUEST["subject"]) && $mode == "single") { // unread messages for a single chat
    $partner = intval($_REQUEST["partner"]);
    $subject = test_input($_REQUEST["subject"]);
    echo $conn->query("SELECT * FROM messages WHERE seen = 'FALSE' AND partnerID = $userID AND userID = $partner AND subject = '$subject' AND partner_deleted = 'FALSE'")->num_rows;
} elseif (isset($_REQUEST["partner"]) && $mode == "group") { // unread messages for a single group
    $groupID = intval($_REQUEST["partner"]);
    $result = $conn->query("SELECT count(*) AS count FROM groupmessages WHERE groupID = $groupID AND sender != $userID AND NOT EXISTS (SELECT seen FROM groupmessages_user WHERE groupmessages_user.messageID = id AND seen IS NOT NULL AND userID = $userID)");
    if (!$result || $result->num_rows === 0) die($conn->error);
    $row = $result->fetch_assoc();
    die($row["count"]);
} elseif (isset($_REQUEST["projects"]) && is_array($_REQUEST["projects"])) { // unread messages for projects
    $projects_result = array();
    $stmt_select_unread_count = $conn->prepare("SELECT count(*) count FROM taskmessages WHERE taskID = ?");
    $stmt_select_unread_count->bind_param('s', $projectID);
    foreach ($_REQUEST["projects"] as $projectID) {
        $projectID = test_input($projectID);
        $stmt_select_unread_count->execute();
        $result = $stmt_select_unread_count->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $projects_result[$projectID] = $row["count"];
            $result->free();
        }
    }
    echo json_encode($projects_result);
} else { // all unread messages (for header)
    $result = $conn->query("SELECT count(*) AS count FROM groupmessages INNER JOIN messagegroups_user ON messagegroups_user.groupID = groupmessages.groupID WHERE sender != $userID AND messagegroups_user.userID = $userID AND NOT EXISTS (SELECT seen FROM groupmessages_user WHERE groupmessages_user.messageID = id AND seen IS NOT NULL AND userID = $userID)");
    if (!$result || $result->num_rows === 0) die($conn->error);
    $row = $result->fetch_assoc();
    $groupBadgeCount = intval($row["count"]);
    $result = $conn->query("SELECT count(*) AS count FROM messages WHERE seen = 'FALSE' AND partnerID = $userID AND partner_deleted = 'FALSE'");
    if (!$result || $result->num_rows === 0) die($conn->error);
    $row = $result->fetch_assoc();
    $singleBadgeCount = intval($row["count"]);
    echo $groupBadgeCount + $singleBadgeCount;
}