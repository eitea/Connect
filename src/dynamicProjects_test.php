<?php include 'header.php';
$result = $conn->query("SELECT * FROM modules");
var_dump($result->fetch_assoc());

include "footer.php";