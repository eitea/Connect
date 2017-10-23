<?php
require dirname(__DIR__)."/connection.php";

$cmpID = intval($_GET['companyID']);

if(isset($_GET['supplierID'])){
  $p = intval($_GET['supplierID']);
} else {
  $p = 0;
}
$result = mysqli_query($conn, "SELECT * FROM $clientTable WHERE companyID = $cmpID AND isSupplier = 'TRUE' ");
if($result && $result->num_rows > 1){
  echo "<option name='clnt' value=0 >...</option>";
}
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $clientID = $row['id'];
    $clientName = $row['name'];
    $selected = "";
    if($p && $p == $clientID){
      $selected = "selected";
    }
    echo "<option $selected name='clnt' value=$clientID>$clientName</option>";
  }
}
?>
