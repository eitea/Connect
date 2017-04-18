<?php
include 'connection.php';
$conn->query("INSERT INTO auditlogs (changeTime, changeStatement) VALUES(UTC_TIMESTAMP, 'This thingy here as been run')");
?>
