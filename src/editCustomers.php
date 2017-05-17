<?php include 'header.php'; ?>
<!-- BODY -->

<link rel="stylesheet" type="text/css" href="../plugins/datatables/css/dataTables.bootstrap.min.css">
<script src="../plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables/js/dataTables.bootstrap.min.js"></script>


<?php
$filterCompanyID = 0;
if(isset($_GET['custID'])){
  $customerID = test_input($_GET['custID']);
  $result = $conn->query("SELECT companyID FROM $clientTable WHERE id = $customerID");
  $row = $result->fetch_assoc();
  $filterCompanyID = $row['companyID'];
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
  $filterCompanyID = $_POST['filterCompanyID'];
  if(isset($_POST['delete']) && isset($_POST['index'])){
    $index = $_POST["index"];
    foreach($index as $x) {
      $sql = "DELETE FROM $clientTable WHERE id = $x;";
      if (!$conn->query($sql)) {
        echo mysqli_error($conn);
      }
    }
  }
}
?>

<div class="page-header">
  <div class="row">
    <div class="col-xs-10">
      <h3><?php echo $lang['CLIENT']; ?></h3>
    </div>
    <div class="col-xs-2 ">
      <br>
      <?php include "new_client.php"; ?>
    </div>
  </div>
</div>

<br>
<form method="post">
  <select name="filterCompanyID" class="js-example-basic-single" style="width:200px" onchange="$('#create_client_company').val(this.value).trigger('change');">
    <?php
    $query = "SELECT * FROM $companyTable WHERE id IN (".implode(', ', $available_companies).") ";
    $result = mysqli_query($conn, $query);
    if ($result && $result->num_rows > 0) {
      if($result && $result->num_rows > 1) {
        echo '<option value="0">'.$lang['COMPANY'].'...</option>';
      } else {
        $filterCompanyID = $available_companies[1];
      }
      while ($row = $result->fetch_assoc()) {
        $i = $row['id'];
        $checked = "";
        if($filterCompanyID == $i){
          $checked = "selected";
        } elseif(isset($_POST['selectCompany']) && $_POST['selectCompany'] == $row['id']){
          $checked = "selected";
        }
        echo "<option $checked value= $i> ".$row['name']."</option>";
      }
    }
    ?>
  </select>
  &nbsp <button type="submit" class="btn btn-warning " name='filter'> Filter</button>

  <br><br>
  <?php $query = "SELECT * FROM $clientTable WHERE companyID = $filterCompanyID AND companyID IN (".implode(', ', $available_companies).")  ORDER BY name ASC";
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
          echo "<a class='btn btn-default' title='Edit' href='editProjects.php?custID=$i'><i class='fa fa-pencil'></i><small> ".$lang['PROJECT']."</small></a>";
          echo " <a class='btn btn-default' title='Detail' href='editCustomer_detail.php?custID=$i'><i class='fa fa-search'></i> <small> Detail</small></a> ";
          echo '</td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
    <br><br>
  <?php elseif($filterCompanyID): ?>
    <div class="alert alert-info" role="alert">
      <?php echo $lang['WARNING_NO_CLIENTS']; ?>
    </div>
  <?php endif; ?>

  <div class="text-right">
    <button type="submit" class="btn btn-danger" name="delete"><?php echo $lang['DELETE']; ?></button>
  </div>
  <br>
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
