<?php
require "../connection.php";
$clientID = intval($_GET['client']);

if(isset($_GET['p'])){
  $p = intval($_GET['p']);
} else {
  $p = 0;
}
echo "<option name='act' value=0 >New Proposal</option>";
$result = mysqli_query($conn, "SELECT * FROM proposals WHERE clientID = $clientID");
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $clientID = $row['id'];
    $clientName = $row['id_number'];
    $selected = "";
    if($p != 0 && $p == $clientID){
      $selected = "selected";
    }
    echo "<option $selected value=$clientID>$clientName</option>";
  }
}
?>
