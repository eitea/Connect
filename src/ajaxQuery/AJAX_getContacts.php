<?php
require dirname(__DIR__)."/connection.php";

$clientID = intval($_GET['clientID']);

$result = $conn->query("SELECT id, firstname, lastname FROM contactPersons WHERE clientID = $clientID ORDER BY firstname ASC ");
if($result && $result->num_rows > 1){
  echo "<option value='0' >...</option>";
}
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $name = $row['firstname'].' '.$row['lastname'];
    echo "<option value='$id'>$name</option>";
}
?>
