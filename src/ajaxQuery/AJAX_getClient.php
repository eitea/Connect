<!DOCTYPE html>
<html>
<head>
</head>
<body>
<?php

require "../connection.php";
$q = intval($_GET['company']);
$p = intval($_GET['client']);

echo "<option name='act' value=0 >Select Client</option>";
$query = "SELECT * FROM $clientTable WHERE companyID = $q";
$result = mysqli_query($conn, $query);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $clientID = $row['id'];
    $clientName = $row['name'];
    $selected = "";
    if($p != 0 && $p == $clientID){
      $selected = "selected";
    }
    echo "<option $selected name='act' value=$clientID>$clientName</option>";
  }
}
?>
</body>
</html>
