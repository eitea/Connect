<?php include 'header.php'; ?>
<?php include 'validate.php'; enableToCore($userID);?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['PROJECT']; ?></h3>
</div>

<?php
$x = test_input($_GET['cmp']);
if(isset($_POST['createNewProject']) && !empty($_POST['name'])){
  $name = test_input($_POST['name']);
  $status = test_input($_POST['status']);
  $hourlyPrice = floatval(test_input($_POST['hourlyPrice']));
  $hours = floatval(test_input($_POST['hours']));

  $sql = "INSERT INTO $companyDefaultProjectTable(companyID, name, status, hourlyPrice, hours) VALUES($x, '$name', '$status', '$hourlyPrice', '$hours')";
  if($conn->query($sql)){
    echo '<div class="alert alert-success fade in">';
    echo '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
    echo '<strong>Success: </strong>New default project was created.';
    echo '</div>';
  }
  echo mysqli_error($conn);
}
?>
<table class="table table-hover table-condensed">
  <thead>
    <tr>
      <th>Name</th>
      <th>Status</th>
      <th><?php echo $lang['HOURS']; ?></th>
      <th><?php echo $lang['HOURLY_RATE']; ?></th>
    </tr>
  </thead>
  <tbody>
    <?php
    $query = "SELECT * FROM $companyDefaultProjectTable WHERE companyID = $x";
    $projectResult = mysqli_query($conn, $query);
    if ($projectResult && $projectResult->num_rows > 0) {
      while ($projectRow = $projectResult->fetch_assoc()) {
        $i = $projectRow['id'];
        echo "<tr><td>".$projectRow['name']."</td>";
        echo "<td>".$projectRow['status']."</td>";
        echo "<td>".$projectRow['hours']."</td>";
        echo "<td>".$projectRow['hourlyPrice']."</td></tr>";
      }
    }
    ?>
  </tbody>
</table>
<br><br>
<a href="editCompanies.php" class="btn btn-info"><i class="fa fa-arrow-left"></i> Return</a>
<br><br><br><br>
Create a new Project: <br><br>
<div class="row">
  <form method="post">
    <div class="col-md-3">
      <input type="text" class="form-control" name="name" placeholder="Name">
    </div>
    <div class="col-md-3">
      <input type="text" class="form-control" name="status" placeholder="Status... (Optional)">
    </div>
    <div class="col-md-2">
      <input type="number" step="any" class="form-control" name="hourlyPrice" placeholder="Price/Hour">
    </div>
    <div class="col-md-2">
      <input type="number" step="any" class="form-control" name="hours" placeholder="Hours">
    </div>
    <button type="submit" class="btn btn-warning" name="createNewProject">+</button>
  </form>
</div>
