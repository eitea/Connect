<?php include dirname(__DIR__) . '/header.php'; enableToDSGVO($userID); ?>
<?php require dirname(__DIR__) . "/misc/helpcenter.php"; ?>
<div class="page-header-fixed">
    <div class="page-header"><h3><?php echo $lang['PROCEDURE_DIRECTORY']; ?> - Templates <div class="page-header-button-group">
    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-template" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
    </div></h3></div>
</div>
<div class="page-content-fixed-130">
<?php
if(empty($_GET['n']) || !in_array($_GET['n'], $available_companies)){
    echo "Invalid Access.";
    include dirname(__DIR__) . '/footer.php';
    die();
}
$cmpID = intval($_GET['n']);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['delete_template'])){
        $val = intval($_POST['delete_template']);
        $conn->query("DELETE FROM dsgvo_vv_templates WHERE id = $val");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }        
    }
    if(!empty($_POST['duplicate_template']) && !empty($_POST['duplicate_template_name'])){
        $val = intval($_POST['duplicate_template']);
        $name = test_input($_POST['duplicate_template_name']);
        $conn->query("INSERT INTO dsgvo_vv_templates (companyID, name, type) SELECT companyID, '$name', type FROM dsgvo_vv_templates WHERE id = $val");
        $templateID = $conn->insert_id;        
        $conn->query("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr, opt_status) SELECT $templateID, opt_name, opt_descr, opt_status FROM dsgvo_vv_template_settings WHERE templateID = $val");
        if($conn->error){
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
        } else {
            echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
        }
    }
    if(isset($_POST['add_template']) && !empty($_POST['add_name']) && $_POST['add_name'] != 'Default' && !empty($_POST['add_type'])){
        $type = ($_POST['add_type']);
        $name = test_input($_POST['add_name']);
        if($type == 'base' || $type == 'app'){
            $conn->query("INSERT INTO dsgvo_vv_templates (companyID, name, type) VALUES($cmpID, '$name', '$type')");
            $templateID = $conn->insert_id;
            $conn->query("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr) VALUES($templateID, 'DESCRIPTION', '')");
            if($conn->error){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            } else {
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
            }
        } else {
            $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Invalid Access.</strong> '.$lang['ERROR_STRIKE'].'</div>';
        }
    } elseif(isset($_POST['add_template'])) {
        echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'. Default nicht erlaubt.</div>';
    }
}
?>

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
    echo '<tr>';
    echo '<td>'.$row['name'].'</td>';
    echo '<td>'.$row['type'].'</td>';
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
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
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
                            <option value="base">Basis</option>
                            <option value="app">App</option>
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
<?php include dirname(__DIR__) . '/footer.php'; ?>