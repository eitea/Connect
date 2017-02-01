<?php include 'header.php'; ?>
<!-- BODY -->

<link rel="stylesheet" type="text/css" href="../plugins/datatables/css/dataTables.bootstrap.min.css">
<script src="../plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables/js/dataTables.bootstrap.min.js"></script>

<div class="page-header">
  <h3><?php echo $lang['CLIENT']; ?></h3>
</div>

<?php
$filterCompanyID = 0;

if(isset($_GET['custID'])){
  $customerID = test_input($_GET['custID']);
  $result = $conn->query("SELECT companyID FROM $clientTable WHERE id = $customerID");
  $row = $result->fetch_assoc();
  $filterCompanyID = $row['companyID'];
}

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
    $companyID = intval($_POST['createCompany']);

    $sql = "INSERT INTO $clientTable (name, companyID, clientNumber) VALUES('$name', $companyID, '".$_POST['clientNumber']."')";
    if($conn->query($sql)){//if ok, give him default projects
      $id = $conn->insert_id;
      $sql = "INSERT INTO $projectTable (clientID, name, status, hours)
      SELECT '$id', name, status, hours FROM $companyDefaultProjectTable WHERE companyID = $companyID";
      $conn->query($sql);

      //and his details
      $conn->query("INSERT INTO $clientDetailTable (clientID) VALUES($id)");
      echo mysqli_error($conn);
    } else {
      echo mysqli_error($conn);
    }
  }
}
?>
<br>
<form method="post">
  <select name="filterCompany" class="js-example-basic-single" style="width:200px">
    <option value=0><?php echo $lang['COMPANY']; ?> ... </option>
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
  &nbsp <button type="submit" class="btn btn-warning btn-sm" name='filter'> Filter</button>

  <br><br>
  <?php $query = "SELECT * FROM $clientTable $filterCompanyQuery ORDER BY name ASC";
  $result = mysqli_query($conn, $query);
  if ($result && $result->num_rows > 0):
  ?>
    <br><br>

    <table id="clientTable" class="table table-striped table-bordered" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th style="width:10%;"> Delete </th>
          <th>Name </th>
          <th><?php echo $lang['NUMBER']; ?></th>
          <th><?php echo $lang['OPTIONS']; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        while ($row = $result->fetch_assoc()) {
          $i = $row['id'];
          $checked = "";
          if(isset($_POST['index']) && $_POST['index'][0] == $i){
            $checked = "checked";
          }
          echo '<tr>';
          echo "<td><input type='checkbox' $checked name='index[]' value= $i></td>";
          echo "<td>" .$row['name'] ."</td>";
          echo "<td>" .$row['clientNumber']."</td>";
          echo '<td>';
          echo "<a class='btn btn-default' title='Edit' href='editProjects.php?custID=$i'><i class='fa fa-pencil'></i></a> ";
          echo "<a class='btn btn-default' title='Detail' href='editCustomer_detail.php?custID=$i'><i class='fa fa-search'></i></a> ";
          echo '</td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
    <br><br>
  <?php else: ?>
    <div class="alert alert-info" role="alert">
      <strong>No Clients yet: </strong> Please create a client first, so you can start assigning projects.<br>
      Clients can only be assigned to one company each. <br>
      For project inheritance from company to client, visit <a href="editCompanies.php" class="alert-link"> the default project creation page</a>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-3">
      <button class="btn btn-warning btn-block" type="button" data-toggle="collapse" data-target="#newcompanyDrop" aria-expanded="false" aria-controls="collapseExample">
        <?php echo $lang['NEW_CLIENT_CREATE']; ?> <i class="fa fa-caret-down"></i>
      </button>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-danger" name="delete">Delete</button>
    </div>
  </div>
  <br><br>

  <div class="collapse col-md-5 well" id="newcompanyDrop">
    <form method="post">
      <br>
      <input type="text" class="form-control" name="name" placeholder="Name..." onkeydown="if (event.keyCode == 13) return false;">
      <br>
      <div class="row">
        <div class="col-md-6">
          <select name="createCompany" class="js-example-basic-single btn-block" onchange="showClients(this.value)" style="width:150px">
            <?php
            $query = "SELECT * FROM $companyTable";
            $result = mysqli_query($conn, $query);
            if ($result && $result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $cmpnyID = $row['id'];
                $cmpnyName = $row['name'];
                echo "<option name='cmp' value=$cmpnyID>$cmpnyName</option>";
              }
            }
            ?>
          </select>
        </div>
        <div class="col-md-6">
          <input type="text" class="form-control" name="clientNumber" placeholder="#" >
          <small> &nbsp Kundennummer - Optional</small>
        </div>
      </div>
      <br>
      <div class="text-right">
        <br>
        <button type="submit" class="btn btn-warning btn-sm" name="create"> <?php echo $lang['ADD']; ?></button>
      </div>

    </form>
  </div>
</form>

<script>
$(document).ready(function() {
  $('#clientTable').DataTable({
    "order": [[ 1, "asc" ]],
    "language": {
      "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/German.json"
    }
  });
});
</script>
<!-- /BODY -->
<?php include 'footer.php'; ?>
