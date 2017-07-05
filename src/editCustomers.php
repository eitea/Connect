<?php include 'header.php'; ?>
<?php
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0); //set_filter requirement
if(isset($_GET['cmp'])){ $filterings['company'] = test_input($_GET['cmp']); }
if(isset($_GET['custID'])){ $filterings['client'] = test_input($_GET['custID']);}

if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['delete']) && isset($_POST['index'])){
    foreach($_POST["index"] as $x) {
      $sql = "DELETE FROM $clientTable WHERE id = $x;";
      if (!$conn->query($sql)) {
        echo mysqli_error($conn);
      }
    }
  } elseif(!empty($_POST['save_edit'])){
    $x = intval($_POST['save_edit']);
    $name = test_input($_POST['edit_name']);
    $companyID = intval($_POST['edit_company']);
    $number = test_input($_POST['edit_clientNumber']);
    $conn->query("UPDATE $clientTable SET name = '$name', companyID = $companyID, clientNumber = '$number' WHERE id = $x");
  }
}
?>

<div class="page-header">
  <h3><?php echo $lang['CLIENT']; ?>
    <div class="page-header-button-group">
      <?php include 'misc/set_filter.php'; ?>
      <button type="button" class="btn btn-default" data-toggle="modal" data-target="#create_client" title="<?php echo $lang['NEW_CLIENT_CREATE']; ?>"><i class="fa fa-plus"></i></button>
      <button type="submit" class="btn btn-default" form="mainForm" name="delete" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
    </div>
  </h3>
</div>

<?php include "misc/new_client_buttonless.php"; ?>

<form id="mainForm" method="POST">
  <?php
  $companyQuery = $clientQuery = $projectQuery = $productiveQuery = "";
  if($filterings['company']){$companyQuery = " AND $clientTable.companyID = ".$filterings['company']; }
  if($filterings['client']){$clientQuery = " AND $clientTable.id = ".$filterings['client']; }
  $query = "SELECT $clientTable.*, $companyTable.name AS companyName FROM $clientTable INNER JOIN $companyTable ON $clientTable.companyID = $companyTable.id
  WHERE companyID IN (".implode(', ', $available_companies).") $companyQuery $clientQuery ORDER BY name ASC";
  $result = $conn->query($query);
  if($result && $result->num_rows > 0):
    ?>
    <table id="clientTable" class="table table-hover">
      <thead>
        <th><?php echo $lang['DELETE']; ?></th>
        <th><?php echo $lang['COMPANY']; ?></th>
        <th>Name </th>
        <th><?php echo $lang['NUMBER']; ?></th>
        <th><?php echo $lang['OPTIONS']; ?></th>
      </thead>
      <tbody>
        <?php
        while ($row = $result->fetch_assoc()) {
          $i = $row['id'];
          echo '<tr>';
          echo "<td><input type='checkbox' name='index[]' value='$i'></td>";
          echo "<td>".$row['companyName']."</td>";
          echo "<td>".$row['name']."</td>";
          echo "<td>".$row['clientNumber']."</td>";
          echo '<td>';
          echo "<a class='btn btn-default' title='Edit' data-toggle='modal' data-target='.edit-client-$i'><i class='fa fa-pencil'></i></a> ";
          echo "<a class='btn btn-default' title='Details' href='editCustomer_detail.php?custID=$i'><i class='fa fa-arrow-right'></i></a>";
          echo '</td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
  <?php endif; ?>
</form>

<?php $result->data_seek(0); while ($row = $result->fetch_assoc()): ?>
<form method="POST">
  <div class="modal fade edit-client-<?php echo $row['id']; ?>">
    <div class="modal-dialog modal-content modal-md">
      <div class="modal-header"><h4><?php echo $lang['EDIT'].' - '.$row['name']; ?></h4></div>
      <div class="modal-body">
        <label>Name</label><input type="text" class="form-control" name="edit_name" value="<?php echo $row['name']; ?>" />
        <br>
        <div class="row">
          <div class="col-md-6">
            <label><?php echo $lang['COMPANY']; ?></label>
            <select class="js-example-basic-single" name="edit_company">
              <?php
              $res_cmp = $conn->query("SELECT * FROM companyData WHERE id IN (".implode(', ', $available_companies).")");
              while($row_cmp = $res_cmp->fetch_assoc()){
                $selected = ($row['companyID'] == $row_cmp['id']) ? 'selected' : '';
                echo '<option '.$selected.' value="'.$row_cmp['id'].'">'.$row_cmp['name'].'</option>';
              }
              ?>
            </select>
          </div>
          <div class="col-md-6">
            <label>Kundennummer</label><input type="text" class="form-control" maxlength="12" value="<?php echo $row['clientNumber']; ?>" name="edit_clientNumber" />
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-warning" name="save_edit" value="<?php echo $row['id']; ?>"><?php echo $lang['SAVE']; ?></button>
      </div>
    </div>
  </div>
</form>
<?php endwhile; ?>

<script>
$(document).ready(function() {
  $('.table').DataTable({
    autoWidth: false,
    order: [[ 2, "asc" ]],
    columns: [{orderable: false}, null, null, null, {orderable: false}],
    responsive: true,
    colReorder: true,
    language: {
      <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    }
  });
});
</script>
<!-- /BODY -->
<?php include 'footer.php'; ?>
