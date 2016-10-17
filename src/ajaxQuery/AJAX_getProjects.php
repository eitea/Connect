<!DOCTYPE html>
<html>
<head>
</head>
<body>
<?php

require "../connection.php";
$q = intval($_GET['q']);
$p = intval($_GET['p']);
$sql="SELECT * FROM $projectTable WHERE clientID = $q";
$result = mysqli_query($conn,$sql);
if($result && $result->num_rows >0){
  while($row = mysqli_fetch_array($result)) {
    $selected = "";
    if($p != 0 && $p == $row['id']){
      $selected = "selected";
    }
      echo "<option $selected name='prj' value='".$row['id']."'>". $row['name']."</option>";
  }
}
?>
</body>
</html>
