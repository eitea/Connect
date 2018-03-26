<?php 
session_start();
$userID = $_SESSION['userid'] or die("no user id");
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "validate.php";
enableToDSGVO($userID); 
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "misc" . DIRECTORY_SEPARATOR . "helpcenter.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";

$result = $conn->query("SELECT DISTINCT companyID FROM relationship_company_client WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = $row['companyID'];
}

if(empty($_GET['n']) || !in_array($_GET['n'], $available_companies)){
    echo "Invalid Access.";
    include dirname(__DIR__) . '/footer.php';
    die();
}


$cmpID = intval($_GET['n']);



?>
<form method="POST">
<div class="modal fade toggle-on-load-modal">
    <div class="modal-dialog modal-content modal-lg">
    <div class="modal-header"><?php echo $lang['PROCEDURE_DIRECTORY']; ?> - Templates</div>
    <div class="modal-body">
        <button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-template" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>    
    




<form method="POST">
<table class="table table-header">
<thead><tr>
<th>Name</th>
<th>Typ</th>
<th></th>
</tr></thead>
<tbody>
<?php
$modals = '';
$result = $conn->query("SELECT id, name, type FROM dsgvo_vv_templates WHERE companyID = $cmpID");
while($result && ($row = $result->fetch_assoc())){
    $template_type = $lang["VV_TEMPLATE_TYPES"][$row['type']];
    echo '<tr>';
    echo '<td>'.$row['name'].'</td>';
    echo '<td>'.$template_type.'</td>';
    echo '<td>';
    echo '<button type="button" class="btn btn-default" data-toggle="modal" data-target="#clone-temp-'.$row['id'].'" title="Duplizieren"><i class="fa fa-files-o"></i></button> ';
    if($row['name'] != 'Default'){
        echo '<button type="submit" name="delete_template" value="'.$row['id'].'" class="btn btn-default"><i class="fa fa-trash-o"></i></button> ';
        echo '<a href="editTemplate?t='.$row['id'].'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
    }
    echo '</td>';
    echo '</tr>';

    $modals .= '<div id="clone-temp-'.$row['id'].'" class="modal fade">
    <div class="modal-dialog modal-content modal-md"><form method="POST">
    <div class="modal-header h4">'.$row['name'].' Duplizieren</div>
    <div class="modal-body"><label>Duplizieren Als</label><input type="text" name="duplicate_template_name" class="form-control" value="'.$row['name'].' Clone" /></div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" onClick="$(\'#clone-temp-'.$row['id'].'\').modal(\'hide\')">Cancel</button>
        <button type="submit" name="duplicate_template" value="'.$row['id'].'" class="btn btn-warning">Duplizieren</button>
    </div></form></div></div>';
}
?>
</tbody>
</table>
</form>

<?php echo $modals; ?>
<div id="add-template" class="modal fade">
    <div class="modal-dialog modal-content modal-sm">
        <form method="POST">
            <div class="modal-header h4"><?php echo $lang['NEW']; ?></div>
            <div class="modal-body">
                <div class="container-fluid">
                    <div class="row">
                        <label>Name</label>
                        <input type="text" class="form-control" name="add_name" />
                        <br>
                        <label>Typ</label>
                        <select class="js-example-basic-single" name="add_type">
                            <option value="base"><?php echo $lang['VV_TEMPLATE_TYPES']['base']; ?></option>
                            <option value="app"><?php echo $lang['VV_TEMPLATE_TYPES']['app']; ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning" name="add_template"><?php echo $lang['SAVE']; ?></button>
            </div>
        </form>
    </div>
</div>







    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Schließen</button>
    </div>
    </div>
</div>
</form>