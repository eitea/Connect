<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
<h3>Navigation</h3>
</div>

<form method='post'>
<?php

if(isset($_GET['customerID']) && is_numeric($_GET['customerID'])){
  $customerID = $_GET['customerID'];
} else {
  $customerID = 0;
}

if(isset($_POST['add']) && !empty($_POST['name']) && $customerID != 0){
  $name = test_input($_POST['name']);
  $hours = (empty($_POST['hours']))?0:$_POST['hours'];
  $price = (empty($_POST['price']))?0:$_POST['price'];

  $sql="INSERT INTO $projectTable (clientID, name, hours, hourlyPrice) VALUES($customerID, '$name', '$hours', '$price')";
  $conn->query($sql);

}

if (isset($_POST['delete']) && isset($_POST['index'])) {
  $index = $_POST["index"];
  foreach ($index as $x) {
    $sql = "DELETE FROM $projectTable WHERE id = $x;";
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    }
  }
}

if(isset($_POST['save']) && isset($_POST['projectIndeces'])){
  for($i = 0; $i < count($_POST['projectIndeces']); $i++){
  $projectID = test_input( $_POST['projectIndeces'][$i]);
  $hours = test_input($_POST['boughtHours'][$i]);
  $hourlyPrice = test_input($_POST['pricedHours'][$i]);
  $sql = "UPDATE $projectTable SET hours = '$hours', hourlyPrice = '$hourlyPrice' WHERE id = $projectID";
  $conn->query($sql);
  echo mysqli_error($conn);
  }
}

//?companyID=$customerID vlavla..
echo "<h1>"."<a href=editCustomers.php><img src='../images/return.png' alt='return' style='width:35px;height:35px;border:0;margin-bottom:5px'></a>" . $lang['PROJECT'] ."</h1><br><br>";
?>

<table class="table table-striped">
  <tr>
    <th>Select</th>
    <th>Name</th>
    <th><?php echo $lang['HOURS']?></th>
    <th><?php echo $lang['HOURLY_RATE']?></th>
  </tr>

  <?php
  $customerQuery = ($customerID==0)?'':"WHERE clientID = $customerID";
  $sql = "SELECT * FROM $projectTable $customerQuery";
  // $sql = "SELECT $projectTable.name AS projectName, $projectTable.id AS projectID, $projectTable.hours, $projectTable.hourlyPrice, $clientTable.name  FROM $projectTable INNER JOIN $clientTable ON $projectTable.clientID = $clientTable.id $customerQuery";
  $result = $conn->query($sql);

  echo mysqli_error($conn);
  while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td><input type=checkbox name=index[] value='. $row['id'].'> </td>';
    echo '<td>'. $row['name'] .'</td>';
    echo '<td><input type="number" class="form-control" step="any" name="boughtHours[]" value="'. $row['hours'] .'"></td>';
    echo '<td><input type="number" class="form-control" step="any" name="pricedHours[]" value="'. $row['hourlyPrice'] .'"></td>';
    echo '<td><input type="text" class="hidden" name="projectIndeces[]" value="'.$row['id'].'"></td>';
    echo '</tr>';
  }
  ?>

</table>

<br><br>
<div class="container-fluid">
  <div class="col-md-2">
      <input type=text class="form-control" name='name' placeholder='projectname'>
  </div>
  <div class="col-md-2">
    <input type=number class="form-control" name='price' placeholder='price' step="any">
  </div>
  <div class="col-md-2">
    <div class="input-group">
      <input type=number class="form-control" name='hours' placeholder='hours' step="any">
      <span class="input-group-btn">
        <button type=submit class="btn btn-warning" name='add'> + </button>
      </span>
    </div>
  </div>

  <div class="text-right">
      <button type="submit" class="btn btn-danger" name='delete'>Delete</button>
      <button type="submit" class="btn btn-warning" name='save'>Save Changes</button>
  </div>
</div><br><br>


</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
