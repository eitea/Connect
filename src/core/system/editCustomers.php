<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToClients($userID); ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
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
  }
}
?>

<div class="page-header">
  <h3><?php echo $lang['CLIENT']; ?>
    <div class="page-header-button-group">
      <?php include dirname(dirname(__DIR__)) . '/misc/set_filter.php'; ?>
      <button type="button" class="btn btn-default" data-toggle="modal" data-target="#create_client" title="<?php echo $lang['NEW_CLIENT_CREATE']; ?>"><i class="fa fa-plus"></i></button>
      <button type="submit" class="btn btn-default" form="mainForm" name="delete" title="<?php echo $lang['DELETE']; ?>"><i class="fa fa-trash-o"></i></button>
    </div>
  </h3>
</div>

<?php include dirname(dirname(__DIR__)) . "/misc/new_client_buttonless.php"; ?>

<form id="mainForm" method="POST">
  <?php
  $companyQuery = $clientQuery = "";
  if($filterings['company']){$companyQuery = " AND $clientTable.companyID = ".$filterings['company']; }
  if($filterings['client']){$clientQuery = " AND $clientTable.id = ".$filterings['client']; }
  $query = "SELECT clientData.*, companyData.name AS companyName FROM clientData INNER JOIN companyData ON clientData.companyID = companyData.id
  WHERE companyID IN (".implode(', ', $available_companies).") AND clientData.isSupplier = 'FALSE' $companyQuery $clientQuery ORDER BY name ASC";
  $result = $conn->query($query);
  if($result && $result->num_rows > 0):
    ?>
    <table class="table table-hover">
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
          echo "<td><a class='btn btn-default' title='Bearbeiten' href='../system/clientDetail?custID=$i'><i class='fa fa-pencil'></i></a></td>";
          echo '</tr>';
        }
        ?>
      </tbody>
    </table>
  <?php endif; echo $conn->error; ?>
</form>

<script>
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
</script>
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
