<?php 
session_start();
require dirname(__DIR__) . "/connection.php";
$userID = $_SESSION["userid"] or die("0");
if (isset($_REQUEST["partner"])) {
    $partner = intval($_REQUEST["partner"]);
    echo $conn->query("SELECT * FROM socialmessages WHERE seen = 'FALSE' AND partner = $userID AND userID = $partner")->num_rows;
} elseif(isset($_REQUEST["group"])){
    $group = intval($_REQUEST["group"]);
    echo $conn->query("SELECT * FROM socialgroupmessages WHERE groupID = $group AND NOT ( seen LIKE '%,$userID,%' OR seen LIKE '$userID,%' OR seen LIKE '%,$userID' OR seen =  '$userID')")->num_rows;
} else {
    $private = $conn->query("SELECT * FROM socialmessages WHERE seen = 'FALSE' AND partner = $userID ")->num_rows;
    $group = $conn->query("SELECT * FROM socialgroupmessages WHERE NOT ( seen LIKE '%,$userID,%' OR seen LIKE '$userID,%' OR seen LIKE '%,$userID' OR seen =  '$userID')")->num_rows;
    echo $private+$group;
}