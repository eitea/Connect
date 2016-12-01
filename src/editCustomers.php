<?php include 'header.php'; ?>
<?php include 'validate.php'; ?>
<!-- BODY -->

<div class="page-header">
  <h3><?php echo $lang['CLIENT']; ?></h3>
</div>

<?php
$filterCompanyID = 0;
if($_SERVER["REQUEST_METHOD"] == "POST"){
  $filterCompanyID = $_POST['filterCompany'];
  if (isset($_POST['delete']) && isset($_POST['index'])) {
    $index = $_POST["index"];
    foreach ($index as $x) {
      $sql = "DELETE FROM $clientTable WHERE id = $x;";
      if (!$conn->query($sql)) {
        echo mysqli_error($conn);
      }
    }
  } elseif(isset($_POST['create']) && !empty($_POST['name']) && $_POST['createCompany'] != 0){
    $name = test_input($_POST['name']);
    $companyID = $_POST['createCompany'];

    $sql = "INSERT INTO $clientTable (name, companyID, clientNumber) VALUES('$name', $companyID, '".$_POST['clientNumber']."')";
    if($conn->query($sql)){
      $id = $conn->insert_id;
      $sql = "INSERT INTO $projectTable (clientID, name, status, hours)
      SELECT '$id', name, status, hours FROM $companyDefaultProjectTable WHERE companyID = $companyID";
      $conn->query($sql);
    }
    echo mysqli_error($conn);
  }
}
?>
<br>

<form method="post">
  <select name="filterCompany" class="js-example-basic-single">
    <option value=0>Select Company..</option>
    <?php
    $filterCompanyQuery = "";
    $query = "SELECT * FROM $companyTable";
    $result = mysqli_query($conn, $query);
    if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $i = $row['id'];
        $checked = "";
        if($filterCompanyID == $i){
          $checked = "selected";
          $filterCompanyQuery = "WHERE companyID = ".$i;
        } elseif(isset($_POST['selectCompany']) && $_POST['selectCompany'] == $row['id']){
          $checked = "selected";
        }
        echo "<option $checked value= $i> ".$row['name']."</option>";
      }
    }
    ?>
  </select>

  <button type="submit" class="btn btn-sm btn-primary" name='filter'>Filter</button><br>
  <br>
  <br>

  <table id="clientTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
    <thead>
      <tr>
        <th style="width:10%;"> Delete </th>
        <th>Name </th>
        <th><?php echo $lang['NUMBER']; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php
      $query = "SELECT * FROM $clientTable $filterCompanyQuery ORDER BY name ASC";
      $result = mysqli_query($conn, $query);
      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $i = $row['id'];
          $checked = "";
          if(isset($_POST['index']) && $_POST['index'][0] == $i){
            $checked = "checked";
          }
          echo '<tr>';
          echo "<td><input type='checkbox' $checked name='index[]' value= $i></td>";
          echo "<td><a href='editProjects.php?customerID=$i'>" .$row['name'] ."</a></td>";
          echo "<td>" .$row['clientNumber']."</td>";
          echo '</tr>';
        }
      }
      ?>
    </tbody>
  </table>
  <br><br>

  <div class="container-fluid">
    <div class="col-md-1">
      <button type="submit" class="btn btn-primary" name="delete">Delete</button>
    </div>
  </div>

    <br>
    <div class="container-fluid">
      <div class="col-md-4">
        <div class="input-group">
          <span class="input-group-addon">Create New Client: </span>
          <input type="text" class="form-control" name="name" placeholder="Name" onkeydown="if (event.keyCode == 13) return false;" >
        </div>
      </div>
      <div class="col-md-3">
        <select name="createCompany"  class="js-example-basic-single btn-block" class="" onchange="showClients(this.value)">
          <option name=cmp value=0>Company...</option>
          <?php
          $query = "SELECT * FROM $companyTable";
          $result = mysqli_query($conn, $query);
          if ($result && $result->num_rows > 1) {
            while ($row = $result->fetch_assoc()) {
              $cmpnyID = $row['id'];
              $cmpnyName = $row['name'];
              echo "<option name='cmp' value=$cmpnyID>$cmpnyName</option>";
            }
          }
          ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="text" class="form-control" name="clientNumber" placeholder="#" >
      </div>
      <div class="col-md-1">
        <button type="submit" class="btn btn-primary" name="create"> + </button>
      </div>
    </div>

<br><br>

</form>
<script>
$(document).ready(function() {
  $('#clientTable').DataTable({
    "order": [[ 1, "asc" ]]
  });
} );
</script>
<!-- /BODY -->
<?php include 'footer.php'; ?>
