<?php
require dirname(__DIR__)."/connection.php";

$cmpID = intval($_GET['companyID']);

if(isset($_GET['clientID'])){
  $clientID = intval($_GET['clientID']);
} else {
  $clientID = 0;
}
$result = mysqli_query($conn, "SELECT * FROM clientData WHERE companyID = $cmpID AND isSupplier = 'FALSE' ORDER BY name ASC ");
echo "<option name='clnt' value=0 >...</option>";

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $selected = $clientID == $row['id'] ? 'selected' : '';
    echo '<option '.$selected.' value="'.$row['id'].'">'.$row['name'].'</option>';
  }
}
?>
