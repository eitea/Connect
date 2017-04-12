<?php

require "../connection.php";
$p = $q = 0;
if(empty($_GET['q'])){
  $p = $_GET['p']; //companyID
} else {
  $q = intval($_GET['q']); //project
}

if($p){
  $result = $conn->query("SELECT id FROM $projectTable WHERE clientID = $p");
  $row = $result->fetch_assoc();
  $q = $row['id'];
}

$sql="SELECT $companyExtraFieldsTable.*, $projectTable.field_1, $projectTable.field_2, $projectTable.field_3, $clientTable.companyID
FROM $companyExtraFieldsTable, $clientTable, $projectTable
WHERE $companyExtraFieldsTable.isActive = 'TRUE'
AND $companyExtraFieldsTable.companyID = $clientTable.companyID
AND $clientTable.id = $projectTable.clientID
AND $projectTable.id = $q";

$result = mysqli_query($conn,$sql);
while($result && ($row = $result->fetch_assoc())) { //this should probably return 3 rows.
  if($row['field_1'] == 'TRUE' && $row['id'] == ($row['companyID'] * 3 - 2)){
    echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input type="text" class="form-control" name="optional_1" placeholder="'.$row['description'].'" /></div>';
  }
  if($row['field_2'] == 'TRUE' && $row['id'] == ($row['companyID'] * 3 - 1)){
    echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input type="text" class="form-control" name="optional_2" placeholder="'.$row['description'].'" /></div>';
  }
  if($row['field_3'] == 'TRUE' && $row['id'] == ($row['companyID'] * 3)){
    echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input type="text" class="form-control" name="optional_3" placeholder="'.$row['description'].'" /></div>';
  }
}
?>
