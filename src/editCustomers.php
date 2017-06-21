<?php include 'header.php'; ?>
<!-- BODY -->
<?php
if(isset($_GET['custID'])){
  $customerID = test_input($_GET['custID']);
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
  if(isset($_POST['delete']) && isset($_POST['index'])){
    foreach($_POST["index"] as $x) {
      $sql = "DELETE FROM $clientTable WHERE id = $x;";
      if (!$conn->query($sql)) {
        echo mysqli_error($conn);
      }
    }
  }
}
?>

<div class="page-header">
  <h3><?php echo $lang['CLIENT']; ?>
    <div class="page-header-button-group">
      <button type="button" class="btn btn-default" data-toggle="modal" data-target="#create_client" title="<?php echo $lang['NEW_CLIENT_CREATE']; ?>"><i class="fa fa-plus"></i></button>
      <button type="submit" class="btn btn-default" form="mainForm" name="delete" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
    </div>
  </h3>
</div>

<?php include "misc/new_client_buttonless.php"; ?>

<form id="mainForm" method="POST">
  <?php
  $query = "SELECT $clientTable.*, $companyTable.name AS companyName FROM $clientTable INNER JOIN $companyTable ON $clientTable.companyID = $companyTable.id
  WHERE companyID IN (".implode(', ', $available_companies).") ORDER BY name ASC";
  $result = mysqli_query($conn, $query);
  if ($result && $result->num_rows > 0):
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
          $checked = "";
          if(isset($_POST['index']) && $_POST['index'][0] == $i){
            $checked = "checked";
          }
          echo '<tr>';
          echo "<td><input type='checkbox' $checked name='index[]' value='$i'></td>";
          echo "<td>".$row['companyName']."</td>";
          echo "<td>".$row['name']."</td>";
          echo "<td>".$row['clientNumber']."</td>";
          echo '<td>';
          echo "<a class='btn btn-default' title='Edit' href='editProjects.php?custID=$i'><i class='fa fa-pencil'></i> <small>".$lang['PROJECT']."</small></a> ";
          echo "<a class='btn btn-default' title='Detail' href='editCustomer_detail.php?custID=$i'><i class='fa fa-search'></i> <small>Detail</small></a>";
          echo '</td>';
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
  <?php endif; ?>
</form>

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
