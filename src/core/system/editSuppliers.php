<?php include dirname(__DIR__) . '/header.php'; ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<?php
isset($canUseSuppliers, $canEditSuppliers) or die ("no permission (you need canUseSuppliers or canEditSuppliers)");
if(!($canUseSuppliers == 'TRUE' || $canEditSuppliers == 'TRUE')){
    echo "no permission (you need canUseSuppliers or canEditSuppliers)";
    include dirname(__DIR__) . '/footer.php';
    die();
}
$filterings = array("company" => 0, "supplier" => 0);
if(!empty($_POST['delete'])){
    if($canEditSuppliers == 'TRUE'){
         $id = intval($_POST['delete']);
        $conn->query("DELETE FROM clientData WHERE id = $id");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    }else{
        echo "no permission (you need canEditSuppliers)";
    }
}
?>
<?php include dirname(__DIR__) . "/misc/new_supplier_buttonless.php"; ?>
<div class="page-header-fixed">
<div class="page-header"><h3><?php echo $lang['SUPPLIERS']; ?><div class="page-header-button-group">
<?php include dirname(__DIR__).'/misc/set_filter.php'; ?>
<button type="button" class="btn btn-default" data-toggle="modal" data-target="#create_client" title="<?php echo $lang['NEW_CLIENT_CREATE']; ?>"><i class="fa fa-plus"></i></button>
</div></h3></div>
</div>
<div class="page-content-fixed-130">
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
        $modals = '';
        $companyQuery = $clientQuery = $modals = "";
        if($filterings['company']){$companyQuery = " AND $clientTable.companyID = ".$filterings['company']; }
        if($filterings['supplier']){$clientQuery = " AND $clientTable.id = ".$filterings['supplier']; }

        $result = $conn->query("SELECT $clientTable.*, $companyTable.name AS companyName FROM $clientTable INNER JOIN $companyTable ON $clientTable.companyID = $companyTable.id
        WHERE isSupplier = 'TRUE' AND companyID IN (".implode(', ', $available_companies).") $companyQuery $clientQuery ORDER BY name ASC");
        while ($row = $result->fetch_assoc()) {
            echo '<tr class="clicker">';
            echo "<td>".$row['companyName']."</td>";
            echo "<td>".$row['name']."</td>";
            echo "<td>".$row['clientNumber']."</td>";
            echo '<td>';
            echo '<button type="submit" name="delete" value="'.$row['id'].'" class="btn btn-default"><i class="fa fa-trash-o"></i></button> ';
            echo '<button type="button" class="btn btn-default" name="editModal" value="'.$row['id'].'" ><i class="fa fa-pencil"></i></button>';
            echo '</td>';
            echo '</tr>';
            $modals .= '<div id="delete-confirm-'.$row['id'].'" class="modal fade">
            <div class="modal-dialog modal-content modal-sm">
            <div class="modal-header h4">'.$lang['DELETE_SELECTION'].'</div><div class="modal-body">'.sprintf($lang['ASK_DELETE'], $row['name']).'</div>
            <div class="modal-footer"><form method="POST">
            <button type="button" class="btn btn-default" data-dismiss="modal">'.$lang['CONFIRM_CANCEL'].'</button>
            <button type="submit" class="btn btn-danger" name="delete" value="'.$row['id'].'">'.$lang['DELETE'].'</button>
            </form></div></div></div>';
        }
        ?>
        </tbody>
    </table>
</form>

<div id="editingModalDiv">
    <?php echo $modals; ?>
</div>

<script>
var existingModals = new Array();
function checkAppendModal(index){
    if(existingModals.indexOf(index) == -1){
        $.ajax({
            url:'ajaxQuery/AJAX_customerDetailModal.php',
            data:{supID: index},
            type: 'GET',
            success : function(resp){
                $("#editingModalDiv").append(resp);
                existingModals.push(index);
                onPageLoad();
            },
            error : function(resp){},
            complete: function(resp){
                if(index){
                    $('#editingModal-'+index).modal('show');
                }
            }
        });
    } else {
        $('#editingModal-'+index).modal('show');
    }
}

$('.table').on('click', 'button[name=editModal]', function(){
    checkAppendModal($(this).val());
    event.stopPropagation();
});
$('.clicker').click(function(){
    checkAppendModal($(this).find('button[name=editModal]:first').val());
});

$('.table').DataTable({
    autoWidth: false,
    order: [[ 2, "asc" ]],
    columns: [null, null, null, {orderable: false}],
    responsive: true,
    colReorder: true,
    language: {
        <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
    }
});
</script>
</div>
<?php include dirname(__DIR__) . '/footer.php'; ?>
