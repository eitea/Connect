<?php
require dirname(__DIR__)."/connection.php";

$cmpID = intval($_GET['companyID']);

if(isset($_GET['supplierID'])){
  $p = intval($_GET['supplierID']);
} else {
  $p = 0;
}
$result = mysqli_query($conn, "SELECT * FROM $clientTable WHERE companyID = $cmpID AND isSupplier = 'TRUE' ");
echo "<option value=0 >...</option>";
echo "<option value='new' >+ Neu</option>";
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $clientID = $row['id'];
    $clientName = $row['name'];
    $selected = "";
    if($p && $p == $clientID){
      $selected = "selected";
    }
    echo "<option $selected value=$clientID>$clientName</option>";
  }
}
?>
