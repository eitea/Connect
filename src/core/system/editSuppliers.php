<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToSuppliers($userID); ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
$filterings = array("savePage" => $this_page, "company" => 0, "supplier" => 0);
if(isset($_GET['cmp'])){ $filterings['company'] = test_input($_GET['cmp']); }
if(isset($_GET['custID'])){ $filterings['supplier'] = test_input($_GET['custID']);}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST['saveID'])){
        $filterClient = intval($_POST['saveID']);
        require dirname(dirname(__DIR__)).DIRECTORY_SEPARATOR.'misc'.DIRECTORY_SEPARATOR.'client_backend.php';
    }

    if(!empty($_POST['delete'])){
        $conn->query("DELETE FROM clientData WHERE id = ".intval($_POST['delete']));
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
        }
    }
}
?>

<div class="page-header">
    <h3><?php echo $lang['SUPPLIERS']; ?>
        <div class="page-header-button-group">
            <?php include dirname(dirname(__DIR__)).'/misc/set_filter.php'; ?>
            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#create_client" title="<?php echo $lang['NEW_CLIENT_CREATE']; ?>"><i class="fa fa-plus"></i></button>
        </div>
    </h3>
</div>

<?php include dirname(dirname(__DIR__)) . "/misc/new_supplier_buttonless.php"; ?>

<?php
$companyQuery = $clientQuery = "";
if($filterings['company']){$companyQuery = "AND clientData.companyID = ".$filterings['company']; }
if($filterings['supplier']){$clientQuery = "AND clientData.id = ".$filterings['supplier']; }

$result = $conn->query("SELECT $clientTable.*, $companyTable.name AS companyName FROM $clientTable INNER JOIN $companyTable ON $clientTable.companyID = $companyTable.id
WHERE isSupplier = 'TRUE' AND companyID IN (".implode(', ', $available_companies).") $companyQuery $clientQuery ORDER BY name ASC");
?>
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
    while ($row = $result->fetch_assoc()) {
        echo '<tr class="clicker">';
        echo "<td>".$row['companyName']."</td>";
        echo "<td>".$row['name']."</td>";
        echo "<td>".$row['clientNumber']."</td>";
        echo '<td>';
        echo '<button type="button" class="btn btn-default" name="deleteModal" value="'.$row['id'].'" title="'.$lang['DELETE'].'" ><i class="fa fa-trash-o"></i></button>';
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

<div id="editingModalDiv">
    <?php echo $modals; ?>
</div>

<script>
// $('.table').on('click', 'button[name=deleteModal]', function(e){
//     $('#delete-confirm-'+$(this).val()).modal('show');
//     event.stopPropagation();
// });
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
$('button[name=deleteModal]').click(function(){
    $('#delete-confirm-'+$(this).val()).modal('show');
    event.stopPropagation();
});

$('.clicker').click(function(){
    checkAppendModal($(this).find('button[name=editModal]:first').val());
    event.stopPropagation();
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
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
