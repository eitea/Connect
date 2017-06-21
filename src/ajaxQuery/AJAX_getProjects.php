<?php
require "../connection.php";
$q = intval($_GET['clientID']);

if(isset($_GET['projectID'])){
  $p = intval($_GET['projectID']);
} else {
  $p = 0;
}
$sql="SELECT * FROM $projectTable WHERE clientID = $q";
$result = mysqli_query($conn,$sql);
if($result && $result->num_rows > 1){
  echo "<option name='prj' value=0 >...</option>";
}
if($result && $result->num_rows > 0){
  while($row = mysqli_fetch_array($result)) {
    $selected = "";
    if($p != 0 && $p == $row['id']){
      $selected = "selected";
    }
    echo "<option $selected name='prj' value='".$row['id']."'>". $row['name']."</option>";
  }
}
?>
