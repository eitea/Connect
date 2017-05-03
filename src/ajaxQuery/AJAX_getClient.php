<?php
require "../connection.php";
include "../language.php";

$cmpID = intval($_GET['companyID']);

if(isset($_GET['clientID'])){
  $p = intval($_GET['clientID']);
} else {
  $p = 0;
}
$result = mysqli_query($conn, "SELECT * FROM $clientTable WHERE companyID = $cmpID");
if($result && $result->num_rows > 1){
  echo "<option name='clnt' value=0 >".$lang['CLIENT']."...</option>";
}
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $clientID = $row['id'];
    $clientName = $row['name'];
    $selected = "";
    if($p != 0 && $p == $clientID){
      $selected = "selected";
    }
    echo "<option $selected name='clnt' value=$clientID>$clientName</option>";
  }
}
?>
