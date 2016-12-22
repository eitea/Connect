<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['PROJECT']; ?></h3>
</div>

<?php
if(isset($_GET['custID']) && is_numeric($_GET['custID'])){
  $customerID = test_input($_GET['custID']);
} else {
  $customerID = 0;
}

if(isset($_POST['add']) && !empty($_POST['name']) && $customerID != 0){
  $name = test_input($_POST['name']);
  if(isset($_POST['status'])){
    $status = "checked";
  } else {
    $status = "";
  }
  $hourlyPrice = floatval(test_input($_POST['hourlyPrice']));
  $hours = floatval(test_input($_POST['hours']));

  $sql="INSERT INTO $projectTable (clientID, name, status, hours, hourlyPrice) VALUES($customerID, '$name', '$status', '$hours', '$hourlyPrice')";
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
    $hours = floatval(test_input($_POST['boughtHours'][$i]));
    $hourlyPrice = floatval(test_input($_POST['pricedHours'][$i]));
    if(in_array($projectID, $_POST['statii'])){
      $status = "checked";
    } else {
      $status = "";
    }
    $sql = "UPDATE $projectTable SET hours = '$hours', hourlyPrice = '$hourlyPrice', status='$status' WHERE id = $projectID";
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}
?>

<form method="post">
  <table class="table table-hover">
    <thead>
      <tr>
        <th><?php echo $lang['DELETE']; ?></th>
        <th>Name</th>
        <th><?php echo $lang['PRODUCTIVE']; ?></th>
        <th><?php echo $lang['HOURS']; ?></th>
        <th><?php echo $lang['HOURLY_RATE']; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $customerQuery = ($customerID==0)?'':"WHERE clientID = $customerID";
      $sql = "SELECT * FROM $projectTable $customerQuery";
      $result = $conn->query($sql);

      echo mysqli_error($conn);
      while($row = $result->fetch_assoc()){
        echo '<tr>';
        echo '<td><input type=checkbox name=index[] value='. $row['id'].'> </td>';
        echo '<td>'. $row['name'] .'</td>';
        echo '<td><div class="checkbox text-center"><input type="checkbox" name="statii[]" '. $row['status'] .' value="'.$row['id'].'"> <i class="fa fa-tags"></i></div></td>';
        echo '<td><input type="number" class="form-control" step="any" name="boughtHours[]" value="'. $row['hours'] .'"></td>';
        echo '<td><input type="number" class="form-control" step="any" name="pricedHours[]" value="'. $row['hourlyPrice'] .'"></td>';
        echo '<td><input type="text" class="hidden" name="projectIndeces[]" value="'.$row['id'].'"></td>';
        echo '</tr>';
      }
      ?>
    </tbody>
  </table>

  <a href="editCustomers.php?custID=<?php echo $customerID; ?>" class="btn btn-info"><i class="fa fa-arrow-left"></i> Return</a><br>

  <br><br>

  <div class="row">
    <div class="col-md-4">
      <button class="btn btn-warning" type="button" data-toggle="collapse" data-target="#newProjectDrop" aria-expanded="false" aria-controls="collapseExample">
        New Project <i class="fa fa-caret-down"></i>
      </button>
    </div>
    <div class="text-right">
      <button type="submit" class="btn btn-danger" name='delete'>Delete</button>
      <button type="submit" class="btn btn-warning" name='save'>Save Changes</button>
    </div>
  </div>

  <br><br>
  <div class="collapse col-md-5 well" id="newProjectDrop">
    <form method="post">
      <br>
      <input type=text class="form-control" name='name' placeholder='Name'>
      <br>
      <div class="row">
        <div class="col-md-6">
          <input type=number class="form-control" name='hours' placeholder='Hours' step="any">
        </div>
        <div class="col-md-6">
          <input type=number class="form-control" name='hourlyPrice' placeholder='Price per Hour' step="any">
        </div>
      </div>
      <br>
      <div style="margin-left:25px">
        <div class="checkbox"><input type="checkbox" name="status" value="checked"> <i class="fa fa-tags"></i> <?php echo $lang['PRODUCTIVE']; ?></div>
      </div>
      <br>
      <div class="text-right">
        <button type=submit class="btn btn-warning" name='add'> <?php echo $lang['ADD']; ?> </button>
      </div>
    </form>
  </div>
</form>

<!-- /BODY -->
<?php include 'footer.php'; ?>
