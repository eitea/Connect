<?php
if(isset($_GET['articleID'])){
  $articleID = intval($_GET['articleID']);
} else {
  die('Invalid Request');
}

require dirname(__DIR__)."/connection.php";
$result = $conn->query("SELECT * FROM articles WHERE id = $articleID");
$row = $result->fetch_assoc();
$mc = mc($row["iv"],$row["iv2"]);
$row["name"] = $mc->decrypt($row["name"]);
$row["description"] = $mc->decrypt($row["description"]);
echo implode('; ', $row);
?>
