<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<link rel="stylesheet" type="text/css" href="../plugins/datatables/css/dataTables.bootstrap.min.css">
<script src="../plugins/datatables/js/jquery.js"></script>
<script src="../plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables/js/dataTables.bootstrap.min.js"></script>

<div class="page-header">
  <h3><?php echo $lang['HOLIDAYS']; ?></h3>
</div>

<?php
if(isset($_POST['holidayDelete']) && isset($_POST['checkingIndeces'])) {
  $index = $_POST["checkingIndeces"];
  foreach ($index as $x) {
    $sql = "DELETE FROM " . $holidayTable . " WHERE begin='$x';";
    if (!$conn->query($sql)) {
      echo mysqli_error($conn);
    }
  }
} elseif(isset($_POST['holidayAdd'])){
  if(!empty($_POST['holidayName']) && isset($_POST['holidayStart'])) {
    $holidayName = test_input($_POST['holidayName']);
    $holidayStart = $_POST['holidayStart'];
    $sql = "INSERT INTO $holidayTable (name, begin, end) VALUES('$holidayName', '$holidayStart', '$holidayStart')";
    $conn->query($sql);
    echo mysqli_error($conn);
  }
}
?>

<form method="post">
  <table id="holidayTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
    <thead>
      <tr>
        <th>Delete</th>
        <th>Occasion</th>
        <th>Date</th>
      </tr>
    </thead>
    <?php
    $query = "SELECT * FROM $holidayTable";
    $result = mysqli_query($conn, $query);
    while ($row = $result->fetch_assoc()) {
      echo "<tr>";
      echo "<td><input type=checkbox name='checkingIndeces[]' value='".$row['begin']."' /></td>";
      echo "<td> ". $row['name']."</td><td> ". substr($row['begin'],0,10)."</td>";
      echo "</tr>";
    }
    ?>

  </table>
  <br><br><br>

  <div class="container">
    <div class="col-md-1">
      <button type="submit" class="btn btn-primary" name="holidayDelete">Delete</button>
    </div>
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-addon">Date</span>
        <input type="date" class="form-control" name="holidayStart" >
      </div>
    </div>
    <div class="col-md-4">
      <div class="input-group">
        <span class="input-group-addon" id="sizing-addon2">Name</span>
        <input type="text" class="form-control" name="holidayName" >
        <span class="input-group-btn">
          <button class="btn btn-primary" type="submit" name="holidayAdd"> + </button>
        </span>
      </div>
    </div>
  </div>


    <script>
    $(document).ready(function() {
      $('#holidayTable').DataTable({
        "order": [[ 2, "asc" ]]
      });
    });
    </script>

    <!-- /BODY -->
    <?php include 'footer.php'; ?>
