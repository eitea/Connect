<?php
if(isset($_GET['articleID'])){
  $articleID = intval($_GET['articleID']);
} else {
  die('Invalid Request');
}

require dirname(__DIR__)."/connection.php";
require dirname(__DIR__)."/utilities.php";

session_start();
$result = $conn->query("SELECT * FROM articles WHERE id = $articleID");
$row = $result->fetch_assoc();
$mc = new MasterCrypt($_SESSION['masterpassword'], $row["iv"],$row["iv2"]);
$row["name"] = $mc->decrypt($row["name"]);
$row["description"] = $mc->decrypt($row["description"]);
echo implode('; ', $row);
?>
