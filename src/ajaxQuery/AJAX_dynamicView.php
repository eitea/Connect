<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";
session_start();
$userID = $_SESSION["userid"] or die("Session died");

$x = preg_replace("/[^A-Za-z0-9]/", '', $_GET['projectid']);
$result = $conn->query("SELECT activity, userID FROM dynamicprojectslogs WHERE projectID = '$x' AND (activity != 'VIEWED' OR userID = $userID) ORDER BY logTime DESC LIMIT 1");
if($result && ($row = $result->fetch_assoc())){
    if($row['activity'] == 'EDITED' || $row['activity'] == 'CREATED'){
        $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'VIEWED', $userID)");
    }
}
echo $conn->error;
?>
