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
    $stmt_select_unread_count = $conn->prepare("SELECT count(*) count FROM taskmessages WHERE taskID = ? AND userID != $userID AND NOT EXISTS (SELECT seen FROM taskmessages_user WHERE taskmessages_user.messageID = id AND seen IS NOT NULL AND userID = $userID )");
    echo $conn->error;
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
    echo json_encode($projects_result); // send all at once, not separate requests
} elseif (isset($_REQUEST["allProjects"])) {
    $result = $conn->query("SELECT d.projectid
    FROM dynamicprojects d
    LEFT JOIN ( SELECT projectid, GROUP_CONCAT(userid SEPARATOR ' ') AS conemployees FROM dynamicprojectsemployees GROUP BY projectid ) tbl ON tbl.projectid = d.projectid
    LEFT JOIN ( SELECT t.projectid, GROUP_CONCAT(teamData.name SEPARATOR ',<br>') AS conteams, GROUP_CONCAT(teamData.id SEPARATOR ' ') AS conteamsids FROM dynamicprojectsteams t
        LEFT JOIN teamData ON teamData.id = t.teamid GROUP BY t.projectid ) tbl2 ON tbl2.projectid = d.projectid
    LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN projectData ON projectData.id = clientprojectid
    LEFT JOIN ( SELECT p.dynamicID, SUM(IFNULL(TIMESTAMPDIFF(SECOND, p.start, p.end)/3600,TIMESTAMPDIFF(SECOND, p.start, UTC_TIMESTAMP)/3600)) AS currentHours
        FROM projectBookingData p GROUP BY dynamicID) tbl3 ON tbl3.dynamicID = d.projectid
    LEFT JOIN ( SELECT userID, dynamicID, p.id, p.start FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00') tbl5
        ON tbl5.dynamicID = d.projectid
    LEFT JOIN dynamicprojectsemployees ON dynamicprojectsemployees.projectid = d.projectid
    LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid LEFT JOIN relationship_team_user ON relationship_team_user.teamID = dynamicprojectsteams.teamid
    WHERE d.isTemplate = 'FALSE' AND (dynamicprojectsemployees.userid = $userID OR d.projectowner = $userID OR (relationship_team_user.userID = $userID AND relationship_team_user.skill >= d.level))
    ORDER BY projectpriority DESC, projectstatus, projectstart ASC"); // select all relevant projects
    echo $conn->error;
    $results = $result->fetch_all();
    $projects = array();
    foreach ($results as $value) {
        $projects[] = "'" . $value[0] . "'";
    }
    // var_dump($projects);
    $result = $conn->query("SELECT count(*) count FROM taskmessages WHERE userID != $userID AND taskID IN (" . join(",", $projects) . ") AND NOT EXISTS (SELECT seen FROM taskmessages_user WHERE taskmessages_user.messageID = id AND seen IS NOT NULL AND userID = $userID )");
    if (!$result || $result->num_rows === 0) die($conn->error);
    $row = $result->fetch_assoc();
    $projectsBadgeCount = intval($row["count"]);
    echo $projectsBadgeCount;
} else { // all unread messages for all groups and single chats (for header)
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