<?php
session_start();
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";

$userID = $_SESSION["userid"] or die("Not logged in");
isset($_REQUEST["group"]) or die("No group selected");
$groupID = intval($_REQUEST["group"]);

$conn->query("DELETE FROM messagegroups_user WHERE userID = $userID AND groupID = $groupID");
echo $conn->error;
$conn->query("DELETE FROM messagegroups WHERE id NOT IN (SELECT groupID FROM messagegroups_user)"); // remove groups with no members
echo $conn->error;
