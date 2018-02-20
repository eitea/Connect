<?php

require dirname(__DIR__)."/connection.php";
$p = 0;
if(!empty($_GET['projectID'])){
  $p = $_GET['projectID'];
}

$sql="SELECT $companyExtraFieldsTable.*, $projectTable.field_1, $projectTable.field_2, $projectTable.field_3, $clientTable.companyID
FROM $companyExtraFieldsTable, $clientTable, $projectTable
WHERE $companyExtraFieldsTable.isActive = 'TRUE'
AND $companyExtraFieldsTable.companyID = $clientTable.companyID
AND $clientTable.id = $projectTable.clientID
AND $projectTable.id = $p";

$result = mysqli_query($conn,$sql);
while($result && ($row = $result->fetch_assoc())){ //this should probably return 3 rows.
  if($row['field_1'] == 'TRUE' && $row['id'] == ($row['companyID'] * 3 - 2)){
    if($row['isRequired'] == 'TRUE'){
      echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input id="pro_field_1" type="text" class="form-control required-field" onkeypress="return event.keyCode != 13;" name="required_1" placeholder="'.$row['description'].'" maxlength="50" /></div>';
    } else {
      echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input id="pro_field_1" type="text" class="form-control" onkeypress="return event.keyCode != 13;" name="optional_1" placeholder="'.$row['description'].'" maxlength="50" /></div>';
    }
  }
  if($row['field_2'] == 'TRUE' && $row['id'] == ($row['companyID'] * 3 - 1)){
    if($row['isRequired'] == 'TRUE'){
      echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input id="pro_field_2" type="text" class="form-control required-field" onkeypress="return event.keyCode != 13;" name="required_2" placeholder="'.$row['description'].'" maxlength="50" /></div>';
    } else {
      echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input id="pro_field_2" type="text" class="form-control" onkeypress="return event.keyCode != 13;" name="optional_2" placeholder="'.$row['description'].'" maxlength="50" /></div>';
    }
  }
  if($row['field_3'] == 'TRUE' && $row['id'] == ($row['companyID'] * 3)){
    if($row['isRequired'] == 'TRUE'){
      echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input id="pro_field_3" type="text" class="form-control required-field" onkeypress="return event.keyCode != 13;" name="required_3" placeholder="'.$row['description'].'" maxlength="50" /></div>';
    } else {
      echo '<div class="col-sm-4"><label>'.$row['name'].'</label><input id="pro_field_3" type="text" class="form-control" onkeypress="return event.keyCode != 13;" name="optional_3" placeholder="'.$row['description'].'" maxlength="50" /></div>';
    }
  }
}
?>
