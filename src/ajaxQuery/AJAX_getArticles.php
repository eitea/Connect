<?php
if(isset($_GET['articleID'])){
  $articleID = intval($_GET['articleID']);
} else {
  die('Invalid Request');
}

require "../connection.php";
$result = $conn->query("SELECT * FROM articles WHERE id = $articleID");
$row = $result->fetch_assoc();
echo implode('; ', $row);
?>
