<?php
require dirname(__DIR__)."/connection.php";

$companyID = intval($_GET['companyID']);

$result = $conn->query("SELECT id, name FROM clientData WHERE companyID = $companyID ORDER BY name ASC ");
if($result && $result->num_rows > 1){
  echo "<option value='0' >...</option>";
}
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $name = $row['name'];
    echo "<option value='$id'>$name</option>";
}
?>
