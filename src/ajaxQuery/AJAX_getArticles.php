<?php
if(isset($_GET['articleID'])){
  $articleID = intval($_GET['articleID']);
} else {
  die('Invalid Request');
}

require dirname(__DIR__)."/connection.php";

$result = $conn->query("SELECT * FROM articles WHERE id = $articleID");
$row = $result->fetch_assoc();
echo $row["name"] .'; '.$row["description"] .'; '. $row['price'] .'; '.$row['unit'].'; '.$row['taxID'].'; '.$row['purchase'];
?>
