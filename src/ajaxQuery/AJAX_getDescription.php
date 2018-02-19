<?php

require dirname(__DIR__) . "/connection.php";

$projectID = $_POST['pId'];

$result = $conn->query("SELECT projectname,projectdescription FROM dynamicprojects WHERE projectid = '$projectID'");
if ($result) {
    $pack = $result->fetch_assoc();
    echo json_encode($pack);
}
?>
