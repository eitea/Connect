<?php include dirname(dirname(__DIR__)) . '/header.php'; enableToClients($userID); ?>
<?php require dirname(dirname(__DIR__)) . "/misc/helpcenter.php"; ?>
<?php
if(!($canUseClients == 'TRUE' || $canEditClients == 'TRUE')){
    echo "Access denied.";
    include dirname(dirname(__DIR__)) . '/footer.php';
    die();
}
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0); //set_filter requirement
if(isset($_GET['cmp'])){ $filterings['company'] = test_input($_GET['cmp']); }
if(isset($_GET['custID'])){ $filterings['client'] = test_input($_GET['custID']);}
if($_SERVER["REQUEST_METHOD"] == "POST"){
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
    <h3><?php echo $lang['CLIENT']; ?>
        <div class="page-header-button-group">
            <?php include dirname(dirname(__DIR__)) . '/misc/set_filter.php'; ?>
            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#create_client" title="<?php echo $lang['NEW_CLIENT_CREATE']; ?>"><i class="fa fa-plus"></i></button>
        </div>
    </h3>
</div>

<?php include dirname(dirname(__DIR__)) . "/misc/new_client_buttonless.php"; ?>

<?php
$companyQuery = $clientQuery = "";
if($filterings['company']){$companyQuery = "AND clientData.companyID = ".$filterings['company']; }
if($filterings['client']){$clientQuery = "AND clientData.id = ".$filterings['client']; }
$result = $conn->query("SELECT clientData.*, companyData.name AS companyName FROM clientData INNER JOIN companyData ON clientData.companyID = companyData.id
WHERE companyID IN (".implode(', ', $available_companies).") AND clientData.isSupplier = 'FALSE' $companyQuery $clientQuery ORDER BY name ASC"); echo $conn->error;
?>
<table class="table table-hover">
    <thead><tr>
        <th><?php echo $lang['COMPANY']; ?></th>
        <th>Name </th>
        <th><?php echo $lang['NUMBER']; ?></th>
        <th><?php echo $lang['OPTIONS']; ?></th>
    </tr></thead>
    <tbody>
        <?php
        $modals = '';
        while ($result && ($row = $result->fetch_assoc())) {
            echo '<tr class="clicker" value="'.$row['id'].'">';
            echo '<td>'.$row['companyName'].'</td>';
            echo '<td>'.$row['name'].'</td>';
            echo '<td>'.$row['clientNumber'].'</td>';
            echo '<td>';
            echo '<button type="button" class="btn btn-default" title="'.$lang['DELETE'].'" data-toggle="modal" data-target="#delete-confirm-'.$row['id'].'" ><i class="fa fa-trash-o"></i></button>';
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
    var existingModals = new Array();
    function checkAppendModal(index){
        if(existingModals.indexOf(index) == -1){
            $.ajax({
                url:'ajaxQuery/AJAX_customerDetailModal.php',
                data:{custid: index},
                type: 'POST',
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
<?php include dirname(dirname(__DIR__)) . '/footer.php'; ?>
