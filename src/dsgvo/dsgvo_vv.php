<?php
include dirname(__DIR__) . '/header.php'; enableToDSGVO($userID);
require dirname(__DIR__) . "/misc/helpcenter.php";
if(empty($_GET['n']) || !in_array($_GET['n'], $available_companies)){ //eventually STRIKE
    $conn->query("UPDATE UserData SET strikeCount = strikecount + 1 WHERE id = $userID");
    showError($lang['ERROR_STRIKE']);
    include dirname(__DIR__) . '/footer.php';
    die();
}

$cmpID = intval($_GET['n']);

$stmt_insert_vv_log = $conn->prepare("INSERT INTO dsgvo_vv_logs (user_id,short_description,long_description,scope) VALUES ($userID,?,?,?)");
showError($conn->error);
$stmt_insert_vv_log->bind_param("sss", $stmt_insert_vv_log_short_description, $stmt_insert_vv_log_long_description, $stmt_insert_vv_log_scope);
function insertVVLog($short,$long){
    global $stmt_insert_vv_log;
    global $stmt_insert_vv_log_short_description;
    global $stmt_insert_vv_log_long_description;
    global $stmt_insert_vv_log_scope;
    global $userID;
    global $privateKey;
    $stmt_insert_vv_log_short_description = secure_data('DSGVO', $short, 'encrypt', $userID, $privateKey, $encryptionError);
    $stmt_insert_vv_log_long_description = secure_data('DSGVO',  $userID.' '.$long, 'encrypt', $userID, $privateKey, $encryptionError);
    $stmt_insert_vv_log_scope = secure_data('DSGVO', "VV", 'encrypt', $userID, $privateKey, $encryptionError);
    if($encryptionError){
        showError($encryptionError);
    }
    $stmt_insert_vv_log->execute();
    showError($stmt_insert_vv_log->error);
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(isset($_POST['add_app']) && !empty($_POST['add_app_name']) && !empty($_POST['add_app_template'])){
        $name = test_input($_POST['add_app_name']);
        $val = intval($_POST['add_app_template']);
        if($name && $val){
            $conn->query("INSERT INTO dsgvo_vv(templateID, name) VALUES ($val, '$name') ");
            insertVVLog("INSERT","Add app from template '$val' with name '$name'");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_ADD']);
            }
        } else {
            showError($lang['ERROR_INVALID_CHARACTER']);
        }
    } elseif(!empty($_POST['delete_app'])){
        $val = intval($_POST['delete_app']);
        $conn->query("DELETE FROM dsgvo_vv WHERE id = $val");
        if($conn->error){
            showError($conn->error);
        } else {
            showSuccess($lang['OK_DELETE']);
        }
    } elseif(!empty($_POST['edit_app']) && !empty($_POST['edit_app_name'])){ //5b166972057a4
		$val = intval($_POST['edit_app']);
		$newname = test_input($_POST['edit_app_name']);
		$conn->query("UPDATE dsgvo_vv SET name = '$newname' WHERE id = $val");
		if($conn->error){
            showError($conn->error);
        } else {
            showSuccess($lang['OK_SAVE']);
        }
	}

    if(!empty($_POST['delete_template'])){
        $val = intval($_POST['delete_template']);
        $conn->query("DELETE FROM dsgvo_vv_templates WHERE id = $val");
        insertVVLog("DELETE","Delete template with id $val");
        if($conn->error){
            showError($conn->error);
        } else {
            showSuccess($lang['OK_DELETE']);
        }
    } elseif(!empty($_POST['duplicate_template']) && !empty($_POST['duplicate_template_name'])){
        $val = intval($_POST['duplicate_template']);
        $name = test_input($_POST['duplicate_template_name']);
        $conn->query("INSERT INTO dsgvo_vv_templates (companyID, name, type) SELECT $cmpID, '$name', type FROM dsgvo_vv_templates WHERE id = $val");
        $templateID = $conn->insert_id;
        $conn->query("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr, opt_status) SELECT $templateID, opt_name, opt_descr, opt_status FROM dsgvo_vv_template_settings WHERE templateID = $val");
        insertVVLog("CLONE","Clone template $val as '$name' with id '$templateID'");
        if($conn->error){
            showError($conn->error);
        } else {
            showSuccess($lang['OK_ADD']);
        }
    } elseif(isset($_POST['add_template']) && !empty($_POST['add_name']) && $_POST['add_name'] != 'Default' && !empty($_POST['add_type'])){
        $type = ($_POST['add_type']);
        $name = test_input($_POST['add_name']);
        if($type == 'base' || $type == 'app'){
            $conn->query("INSERT INTO dsgvo_vv_templates (companyID, name, type) VALUES($cmpID, '$name', '$type')");
            $templateID = $conn->insert_id;
            $conn->query("INSERT INTO dsgvo_vv_template_settings(templateID, opt_name, opt_descr) VALUES($templateID, 'DESCRIPTION', '')");
            insertVVLog("INSERT","Create template '$name' with id '$templateID' (type: '$type', company: '$cmpID')");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_ADD']);
            }
        } else {
            $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
            showError("<strong>Invalid Access.</strong> ".$lang['ERROR_STRIKE']);
        }
    } elseif(isset($_POST['add_template'])) {
        showError($lang['ERROR_MISSING_FIELDS']);
    } elseif(!empty($_POST["change_template"])){
        $vv_id = intval($_POST["change_template"]);
        $template_id = intval($_POST["template_id"]);
        if($vv_id && $template_id){
            $conn->query("UPDATE dsgvo_vv SET templateID = $template_id WHERE id = $vv_id");
            insertVVLog("UPDATE","Change template of app '$vv_id' to '$template_id'");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_SAVE']);
            }
        } else {
            showError($lang['ERROR_INVALID_CHARACTER']);
        }
    }

	if(!empty($_POST['add_folder_name'])){
	    $val = test_input($_POST['add_folder_name']);
	    $conn->query("INSERT INTO folder_default_sturctures(category, name, categoryID) VALUES ('DSGVO', '$val', '$cmpID')");
	    if($conn->error){
	      showError($conn->error);
	    } else {
	      showSuccess($lang['OK_ADD']);
	    }
	} elseif(!empty($_POST['folder_delete'])){
	    $val = intval($_POST['folder_delete']);
	    $conn->query("DELETE FROM folder_default_sturctures WHERE id = $val AND category = 'DSGVO'");
	    if($conn->error){
	        showError($conn->error);
	    } else {
	        showSuccess($lang['OK_DELETE']);
	    }
	}
} //endif POST
?>
<div class="page-header-fixed">
    <div class="page-header"><h3><?php echo $lang['PROCEDURE_DIRECTORY']; ?>
        <div class="page-header-button-group">
            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-app">+ <?php echo $lang['NEW_PROCESS'] ?></button>
            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#list-templates"><?php echo $lang['MANAGE_TEMPLATES']; ?></button>
            <a href="data-matrix?n=<?php echo $cmpID; //5acb74765fddc ?>" class="btn btn-default" ><?php echo $lang['DATA_MATRIX'] ?></a>
			<button type="button" class="btn btn-default" data-toggle="modal" data-target="#list-default-folders"><?php echo $lang['ARCHIVE_STRUCTURE'] ?></button>
			<form method="POST" target="_blank" action="pdfDownload" style="display: inline;">
				<button type="submit" name="downloadVVs" value="<?php echo $cmpID; ?>" class="btn btn-default" title="Startet Download"><i class="fa fa-download"></i> PDF Download</button>
			</form>
        </div>
    </h3></div>
</div>
<div class="page-content-fixed-130">
    <?php
    $result = $conn->query("SELECT dsgvo_vv.id FROM dsgvo_vv, dsgvo_vv_templates WHERE dsgvo_vv.name='Basis' AND templateID = dsgvo_vv_templates.id AND dsgvo_vv_templates.companyID = $cmpID");
    $row = $result->fetch_assoc();
    ?>
        <form method="POST">
            <div class="row">
                <div class="panel panel-default col-sm-6"><a href="vDetail?v=<?php echo $row['id']; ?>&n=<?php echo $cmpID; ?>" class="btn btn-link"> <?php echo "Stammblatt" ?> </a></div>
                <div class="col-sm-5">
                    <select name="change_basic_template" class="js-example-basic-single">
                        <?php
                        $res = $conn->query("SELECT id, name FROM dsgvo_vv_templates WHERE companyID = $cmpID AND type = 'base' ");
                        while($temp_row = $res->fetch_assoc()){
                            echo '<option value="'.$temp_row['id'].'">'.$temp_row['name'].' - Basis</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="col-xs-1">
                    <button type="submit" class="btn btn-warning"><i class="fa fa-floppy-o"></i></button>
                </div>
            </div>
        </form>
    <br>
    <?php
    $template_select = '';
    $select_result = $conn->query("SELECT id, name FROM dsgvo_vv_templates WHERE companyID = $cmpID AND type = 'app'");
    while($select_row = $select_result->fetch_assoc()){
        $template_select .=' <option value="'.$select_row['id'].'">'.$select_row['name'].' - App</option>';
    }

    $result = $conn->query("SELECT dsgvo_vv.id, dsgvo_vv.name, dsgvo_vv.templateID FROM dsgvo_vv, dsgvo_vv_templates
		WHERE dsgvo_vv_templates.type='app' AND templateID = dsgvo_vv_templates.id AND dsgvo_vv_templates.companyID = $cmpID ORDER BY dsgvo_vv.id");
    while($row = $result->fetch_assoc()):
        $id = $row['id'];
        $name = $row['name'];
        ?>
        <div class="row">
            <form method="POST">
                <div class="panel panel-default col-sm-5 col-sm-offset-1">
                    <a href="vDetail?v=<?php echo $id; ?>&n=<?php echo $cmpID; ?>" class="btn btn-link"><?php echo $name; ?></a>
					<a type="button" data-toggle="modal" href="#edit-app" data-name="<?php echo $name; ?>" data-valid="<?php echo $id; ?>"><i class="fa fa-pencil"></i></a>
                </div>
                <div class="col-sm-5">
                    <div class="input-group">
                        <select name="template_id" class="js-example-basic-single">
                            <?php echo str_replace('<option value="'.$row['templateID'].'"', '<option selected value="'.$row['templateID'].'"' , $template_select); ?>
                        </select>
                        <div class="input-group-btn">
                            <button type="submit" class="btn btn-default" name="change_template" value="<?php echo $id; ?>"><i class="fa fa-floppy-o"></i></button>
                        </div>
                    </div>
                </div>
                <div class="col-sm-1">
                    <button type="submit" class="btn btn-default" name="delete_app" value="<?php echo $id; ?>"><i class="fa fa-trash-o"></i></button>
                </div>
            </form>
        </div>
    <?php endwhile; ?>
</div>

<div id="add-app" class="modal fade">
    <div class="modal-dialog modal-content modal-sm">
        <form method="POST">
            <div class="modal-header h4"><?php echo $lang['NEW_PROCESS'] ?></div>
            <div class="modal-body">
                <div class="row">
                    <label>Name</label>
                    <input type="text" name="add_app_name" class="form-control" />
                    <br>
                    <label><?php echo $lang['TEMPLATE'] ?></label>
                    <select name="add_app_template" class="js-example-basic-single">
                        <?php
                        $res = $conn->query("SELECT id, name FROM dsgvo_vv_templates WHERE companyID = $cmpID AND type = 'app' ");
                        while($temp_row = $res->fetch_assoc()){
                            echo '<option value="'.$temp_row['id'].'">'.$temp_row['name'].'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL'] ?></button>
                <button type="submit" class="btn btn-warning" name="add_app"><?php echo $lang['SAVE']; ?></button>
            </div>
        </form>
    </div>
</div>

<div id="edit-app" class="modal fade">
    <div class="modal-dialog modal-content modal-sm">
        <form method="POST">
            <div class="modal-header h4"><?php echo $lang['EDIT_PROCESS'] ?></div>
            <div class="modal-body">
                <div class="row">
                    <label>Name</label>
                    <input type="text" name="edit_app_name" class="form-control" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                <button type="submit" class="btn btn-warning" name="edit_app"><?php echo $lang['SAVE']; ?></button>
            </div>
        </form>
    </div>
</div>

	<div id="list-default-folders" class="modal fade">
		<div class="modal-dialog modal-content modal-md">
			<form method="POST">
				<div class="modal-header h4"><?php echo $lang['DEFAULT_FOLDERS'] ?>
					<div class="page-header-button-group">
						<button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-folder" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
					</div>
				</div>
				<div class="modal-body">
					<table class="table table-hover">
						<thead><tr>
							<th>Name</th>
							<th></th>
						</tr></thead>
						<tbody>
							<?php
							$result = $conn->query("SELECT id, name FROM folder_default_sturctures WHERE category = 'DSGVO' AND categoryID = '$cmpID'");
							while($result && ($row = $result->fetch_assoc())){
								echo '<tr>';
								echo '<td>'.$row['name'].'</td>';
								echo '<td><form method="POST"><button type="submit" class="btn btn-default" name="folder_delete" value="'.$row['id'].'"><i class="fa fa-trash-o"></i></button></form></td>';
								echo '</tr>';
							}
							?>
						</tbody>
					</table>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-warning"><?php echo $lang['SAVE']; ?></button>
				</div>
			</form>
		</div>
	</div>

	<div id="add-folder" class="modal fade">
		<div class="modal-dialog modal-content modal-sm">
			<form method="POST">
				<div class="modal-header h4">Neuer Ordner</div>
				<div class="modal-body">
					<label>Name</label>
					<input type="text" name="add_folder_name" class="form-control" placeholder="Name..."/>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn btn-warning"><?php echo $lang['ADD']; ?></button>
				</div>
			</form>
		</div>
	</div>

	<div id="list-templates" class="modal fade">
    <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header h4">
            <?php echo $lang['PROCEDURE_DIRECTORY']; ?> - Templates
            <button type="button" class="btn btn-default" data-toggle="modal" data-target="#add-template" title="<?php echo $lang['ADD']; ?>" ><i class="fa fa-plus"></i></button>
        </div>
        <div class="modal-body">
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
                                echo '<a href="editTemplate?t='.$row['id'].'&n='.$cmpID.'" class="btn btn-default"><i class="fa fa-pencil"></i></a>';
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

<script>
$("button[name='delete_app']").click(function() {
    return confirm("Are you sure you want to delete this item?");
});

$('#edit-app').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  $(this).find('input[name=edit_app_name]').val(button.data('name'));
  $(this).find('button[name=edit_app]').val(button.data('valid'));
});
</script>
<?php include dirname(__DIR__) . '/footer.php'; ?>
