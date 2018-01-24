<?php include dirname(__DIR__) . '/header.php'; ?>
<?php
$filterings = array("company" => 0, "supplier" => 0);
if(!empty($_POST['delete'])){
    $id = intval($_POST['delete']);
    $conn->query("DELETE FROM clientData WHERE id = $id");
    if($conn->error){
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
    } else {
        echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
    }
}
?>
<?php include dirname(__DIR__) . "/misc/new_supplier_buttonless.php"; ?>
<div class="page-header"><h3><?php echo $lang['SUPPLIERS']; ?><div class="page-header-button-group">
<?php include dirname(__DIR__).'/misc/set_filter.php'; ?>
<button type="button" class="btn btn-default" data-toggle="modal" data-target="#create_client" title="<?php echo $lang['NEW_CLIENT_CREATE']; ?>"><i class="fa fa-plus"></i></button>
</div></h3></div>


<form method="POST">
    <table class="table table-hover">
        <thead>
        <th><?php echo $lang['COMPANY']; ?></th>
        <th>Name </th>
        <th><?php echo $lang['NUMBER']; ?></th>
        <th><?php echo $lang['OPTIONS']; ?></th>
        </thead>
        <tbody>
        <?php
        $companyQuery = $clientQuery = $modals = "";
        if($filterings['company']){$companyQuery = " AND $clientTable.companyID = ".$filterings['company']; }
        if($filterings['supplier']){$clientQuery = " AND $clientTable.id = ".$filterings['supplier']; }

        $result = $conn->query("SELECT $clientTable.*, $companyTable.name AS companyName FROM $clientTable INNER JOIN $companyTable ON $clientTable.companyID = $companyTable.id
        WHERE isSupplier = 'TRUE' AND companyID IN (".implode(', ', $available_companies).") $companyQuery $clientQuery ORDER BY name ASC");
        while ($row = $result->fetch_assoc()) {
            $i = $row['id'];
            echo '<tr>';
            echo "<td>".$row['companyName']."</td>";
            echo "<td>".$row['name']."</td>";
            echo "<td>".$row['clientNumber']."</td>";
            echo '<td>';
            echo '<button type="submit" name="delete" value="" class="btn btn-default"><i class="fa fa-trash-o"></i></button> ';
            echo "<a class='btn btn-default' title='Details' href='../system/clientDetail?supID=$i'><i class='fa fa-arrow-right'></i></a>";
            echo '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>
</form>
<?php echo $modals; ?>

<script>
  $('.table').DataTable({
    autoWidth: false,
    order: [[ 1, "asc" ]],
    columns: [null, null, null, {orderable: false}],
    responsive: true,
    colReorder: true,
    language: {
      <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    }
  });
</script>

<?php include dirname(__DIR__) . '/footer.php'; ?>