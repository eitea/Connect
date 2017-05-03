<?php

require "../connection.php";

$companyID = intval($_POST['companyID']);
$clientID = intval($_POST['clientID']);


echo "<option value='0'>Select Client</option>";
$query = "SELECT * FROM $clientTable WHERE companyID = $companyID";
$result = mysqli_query($conn, $query);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $cur_clientID = $row['id'];
    $clientName = $row['name'];
    $selected = "";
    if($clientID == $cur_clientID){
      $selected = "selected";
    }
    echo "<option $selected value=$cur_clientID>$clientName</option>";
  }
}
?>
