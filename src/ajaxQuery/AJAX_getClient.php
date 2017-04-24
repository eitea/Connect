
<?php
require "../connection.php";
$cmpID = intval($_GET['company']);

if(isset($_GET['p'])){
  $p = intval($_GET['p']);
} else {
  $p = 0;
}
echo "<option name='act' value=0 >Select Client</option>";
$result = mysqli_query($conn, "SELECT * FROM $clientTable WHERE companyID = $cmpID");
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
