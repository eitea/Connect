<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php';
enableToDSGVO($userID); ?>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . "misc" . DIRECTORY_SEPARATOR . "helpcenter.php"; ?>
<script src='../plugins/tinymce/tinymce.min.js'></script>

<?php
// A module is a group of trainings                 (renamed to Set)
// A training is a group of questions (set)         (renamed to Modul)
// A question is a text with different answers      (renamed to Frage)

$trainingID = 0;
if (isset($_REQUEST["trainingid"])) {
    $trainingID = intval($_REQUEST["trainingid"]);
}
if (!isset($_REQUEST["n"])) {
    showError("no company");
    include dirname(__DIR__) . '/footer.php';
    die();
}
$companyID = intval($_REQUEST['n']);
$moduleID = 0;

$stmt_insert_vv_log = $conn->prepare("INSERT INTO dsgvo_vv_logs (user_id,short_description,long_description,scope) VALUES ($userID,?,?,?)");
showError($conn->error);
$stmt_insert_vv_log->bind_param("sss", $stmt_insert_vv_log_short_description, $stmt_insert_vv_log_long_description, $stmt_insert_vv_log_scope);
function insertVVLog($short, $long)
{
    global $stmt_insert_vv_log;
    global $stmt_insert_vv_log_short_description;
    global $stmt_insert_vv_log_long_description;
    global $stmt_insert_vv_log_scope;
    global $userID;
    global $privateKey;
    $stmt_insert_vv_log_short_description = secure_data('DSGVO', $short, 'encrypt', $userID, $privateKey, $encryptionError);
    $stmt_insert_vv_log_long_description = secure_data('DSGVO', $long, 'encrypt', $userID, $privateKey, $encryptionError);
    $stmt_insert_vv_log_scope = secure_data('DSGVO', "TRAINING", 'encrypt', $userID, $privateKey, $encryptionError);
    if ($encryptionError) {
        showError($encryptionError);
    }
    $stmt_insert_vv_log->execute();
    showError($stmt_insert_vv_log->error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['createTraining']) && !empty($_POST['name'])) {
        $name = test_input($_POST['name']);
        $moduleID = intval($_POST["module"]);
        $conn->query("INSERT INTO dsgvo_training (name,companyID, moduleID) VALUES('$name', $companyID, $moduleID)");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_ADD"]);
        }
        $trainingID = mysqli_insert_id($conn);
        insertVVLog("INSERT", "Create new training '$name' with id '$trainingID'");
    } elseif (isset($_POST['removeTraining'])) {
        $trainingID = intval($_POST['removeTraining']);
        $conn->query("DELETE FROM dsgvo_training WHERE id = $trainingID");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_DELETE"]);
        }
        insertVVLog("DELETE", "Delete training with id '$trainingID'");
    } elseif (isset($_POST['addQuestion']) && !empty($_POST['question']) && !empty($_POST["title"])) {
        $trainingID = intval($_POST['addQuestion']);
        $title = test_input($_POST["title"]);
        $text = $_POST["question"]; // todo: test input
        $version = 1;
        $stmt = $conn->prepare("INSERT INTO dsgvo_training_questions (trainingID, text, title, version) VALUES ($trainingID, ?, '$title', $version)");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_ADD"]);
        }
        $stmt->bind_param("s", $text);
        $stmt->execute();
        showError($stmt->error);
        insertVVLog("INSERT", "Add new question with title '$title'");
    } elseif (isset($_POST["removeQuestion"])) {
        $trainingID = $_POST["trainingID"];
        $questionID = intval($_POST["removeQuestion"]);
        $conn->query("DELETE FROM dsgvo_training_questions WHERE id = $questionID");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_DELETE"]);
        }
        insertVVLog("DELETE", "Delete question with id '$questionID'");
    } elseif (isset($_POST["editQuestion"])) {
        $questionID = intval($_POST["editQuestion"]);
        $title = test_input($_POST["title"]);
        $text = $_POST["question"]; //todo: test input
        $version = intval($_POST["version"]);
        $stmt = $conn->prepare("UPDATE dsgvo_training_questions SET text = ?, title = '$title', version = $version WHERE id = $questionID");
        showError($conn->error);
        $stmt->bind_param("s", $text);
        $stmt->execute();
        $conn->query("SELECT trainingID FROM dsgvo_training_questions WHERE id = $questionID");
        if ($stmt->error) {
            showError($stmt->error);
        } else {
            showSuccess($lang["OK_SAVE"]);
        }
        insertVVLog("UPDATE", "Edit question with id '$questionID'");
    } elseif (isset($_POST["editTraining"])) {
        $trainingID = $_POST["editTraining"];
        $version = 1;
        if (isset($_POST["version"])) {
            $version = intval($_POST["version"]);
        }
        $name = test_input($_POST["name"]);
        $onLogin = test_input($_POST["onLogin"]);
        $allowOverwrite = test_input($_POST["allowOverwrite"]);
        $random = test_input($_POST["random"]);
        $moduleID = intval($_POST["module"]);
        $answerEveryNDays = 0; // 0 means no interval
        if (isset($_POST["answerEveryNDays"])) {
            $answerEveryNDays = intval($_POST["answerEveryNDays"]);
        }
        if ($onLogin == 'FALSE' || $allowOverwrite == 'FALSE') {
            $answerEveryNDays = 0; // 0 means no interval
        }
        $conn->query("UPDATE dsgvo_training SET version = $version, name = '$name', onLogin = '$onLogin', allowOverwrite = '$allowOverwrite', random = '$random', moduleID = $moduleID, answerEveryNDays = $answerEveryNDays WHERE id = $trainingID");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_SAVE"]);
        }
        $conn->query("DELETE FROM dsgvo_training_user_relations WHERE trainingID = $trainingID");
        showError($conn->error);
        $conn->query("DELETE FROM dsgvo_training_team_relations WHERE trainingID = $trainingID");
        showError($conn->error);
        $conn->query("DELETE FROM dsgvo_training_company_relations WHERE trainingID = $trainingID");
        showError($conn->error);
        if (isset($_POST["employees"])) {
            $employeeID = $teamID = "";
            $stmtUser = $conn->prepare("INSERT INTO dsgvo_training_user_relations (trainingID, userID) VALUES ($trainingID, ?)");
            showError($conn->error);
            $stmtTeam = $conn->prepare("INSERT INTO dsgvo_training_team_relations (trainingID, teamID) VALUES ($trainingID, ?)");
            showError($conn->error);
            $stmtCompany = $conn->prepare("INSERT INTO dsgvo_training_company_relations (trainingID, companyID) VALUES ($trainingID, ?)");
            showError($conn->error);
            $stmtUser->bind_param("i", $employeeID);
            $stmtTeam->bind_param("i", $teamID);
            $stmtCompany->bind_param("i", $cmpID);
            foreach ($_POST["employees"] as $employee) {
                $emp_array = explode(";", $employee);
                if ($emp_array[0] == "user") {
                    $employeeID = intval($emp_array[1]);
                    $stmtUser->execute();
                    showError($stmtUser->error);
                } else if ($emp_array[0] == "team") { //team
                    $teamID = intval($emp_array[1]);
                    $stmtTeam->execute();
                    showError($stmtTeam->error);
                } else {
                    $cmpID = intval($emp_array[1]);
                    $stmtCompany->execute();
                    showError($stmtCompany->error);
                }
            }
        }
        insertVVLog("UPDATE", "Update training '$name'");
    } elseif (isset($_POST["createModule"])) {
        $name = test_input($_POST['name']);
        $conn->query("INSERT INTO dsgvo_training_modules (name) VALUES('$name')");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_ADD"]);
        }
        $moduleID = mysqli_insert_id($conn);
        insertVVLog("INSERT", "Create module '$name'");
    } elseif (isset($_POST['removeModule'])) {
        $moduleID = intval($_POST['removeModule']);
        $conn->query("DELETE FROM dsgvo_training_modules WHERE id = $moduleID");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_DELETE"]);
        }
        insertVVLog("DELETE", "Remove module with id '$moduleID'");
    } elseif (isset($_POST["jsonImport"])) {
        $replace_old = isset($_POST["replace_old"]);
        $error_happened = false;
        $json = json_decode($_POST["jsonImport"], true);
        $select_module_stmt = $conn->prepare("SELECT id from dsgvo_training_modules where name = ?");
        $select_module_stmt->bind_param("s", $name);
        $select_training_stmt = $conn->prepare("SELECT id from dsgvo_training WHERE name = ? AND moduleID = ?");
        $select_training_stmt->bind_param("si", $name, $moduleID);
        $select_question_stmt = $conn->prepare("SELECT id from dsgvo_training_questions WHERE title = ? AND trainingID = ?");
        $select_question_stmt->bind_param("si", $title, $trainingID);
        $insert_module_stmt = $conn->prepare("INSERT INTO dsgvo_training_modules (name) VALUES( ? )");
        $insert_module_stmt->bind_param("s", $name);
        $insert_training_stmt = $conn->prepare("INSERT INTO dsgvo_training (name,companyID,moduleID,version,onLogin,allowOverwrite,random) VALUES(?, ?, ?, ?, ?, ?, ?)");
        $insert_training_stmt->bind_param("siiisss", $name, $companyID, $moduleID, $version, $onLogin, $allowOverwrite, $random);
        $update_question_stmt = $conn->prepare("UPDATE dsgvo_training_questions SET text = ? WHERE id = ?");
        $update_question_stmt->bind_param("si", $text, $questionID);
        $insert_question_stmt = $conn->prepare("INSERT INTO dsgvo_training_questions (trainingID, text, title) VALUES(?, ?, ?)");
        $insert_question_stmt->bind_param("iss", $trainingID, $text, $title);
        if ($json) {
            foreach ($json as $module) {
                $name = test_input($module["module"]);
                $sets = $module["sets"];
                $moduleID = false;
                if ($replace_old) {
                    $select_module_stmt->execute();
                    $result = $select_module_stmt->get_result();
                    if ($result && $row = $result->fetch_assoc()) {
                        $moduleID = $row["id"];
                        $result->free();
                    }
                }
                showError($conn->error);
                if (!$moduleID) {
                    $insert_module_stmt->execute();
                    $moduleID = $insert_module_stmt->insert_id;
                }
                showError($conn->error);
                if ($conn->error) $error_happened = true;
                foreach ($sets as $set) {
                    $name = test_input($set["set"]);
                    $version = intval($set["version"]);
                    $onLogin = test_input($set["onlogin"]);
                    $allowOverwrite = test_input($set["allowoverwrite"]);
                    $random = test_input($set["random"]);
                    $questions = $set["questions"];
                    $trainingID = false;
                    if ($replace_old) {
                        $moduleID = intval($moduleID);
                        $select_training_stmt->execute();
                        $result = $select_training_stmt->get_result();
                        if ($result && $row = $result->fetch_assoc()) {
                            $trainingID = $row["id"];
                            $result->free();
                        }
                    }
                    showError($conn->error);
                    if (!$trainingID) {
                        $companyID = intval($companyID);
                        $moduleID = intval($moduleID);
                        $version = intval($version);
                        $insert_training_stmt->execute();
                        $trainingID = $insert_training_stmt->insert_id;
                    }
                    showError($conn->error);
                    if ($conn->error) $error_happened = true;
                    foreach ($questions as $question) {
                        $title = test_input($question["title"]);
                        $text = $question["text"];
                        $questionID = false;
                        if ($replace_old) {
                            $trainingID = intval($trainingID);
                            $select_question_stmt->execute();
                            $result = $select_question_stmt->get_result();
                            if ($result && $row = $result->fetch_assoc()) {
                                $questionID = $row["id"];
                            }
                        }
                        showError($conn->error);
                        if (!$questionID) {
                            $trainingID = intval($trainingID);
                            $insert_question_stmt->execute();
                            if ($insert_question_stmt->error) $error_happened = true;
                        } else {
                            $questionID = intval($questionID);
                            $update_question_stmt->execute();
                            if ($update_question_stmt->error) $error_happened = true;
                        }
                    }
                }
                insertVVLog("IMPORT", "Import module '$name'");
            }
        } else {
            showError($lang["ERROR_MISSING_FIELDS"]);
            $error_happened = true;
        }
        if (!$error_happened) {
            showSuccess($lang["OK_IMPORT"]);
        }
    } elseif (isset($_POST["editModule"])) {
        $name = test_input($_POST['name']);
        $moduleID = intval($_POST["editModule"]);
        $conn->query("UPDATE dsgvo_training_modules SET name = '$name' WHERE id=$moduleID");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_SAVE"]);
        }
        $moduleID = mysqli_insert_id($conn);
        insertVVLog("UPDATE", "Change module name to '$name' (id: '$moduleID')");
    }
}
$activeTab = $trainingID;
$activeModule = $moduleID;
//todo select module where trainingid and make that active
showError($conn->error);
?>
<div class="page-header-fixed">
<div class="page-header">
    <h3>Schulungen
        <div class="page-header-button-group">
            <span data-container="body" data-toggle="tooltip" title="Hinzufügen eines neuen Sets (Container für Module)">
                <button type="button" data-toggle="modal" data-target="#newModuleModal" class="btn btn-default"><i class="fa fa-cubes"></i> neuses Set</button>
            </span>
            <!-- Moved to panels -->
            <!-- <span data-container="body" data-toggle="tooltip" title="Hinzufügen eines neuen Moduls (Container für Fragen)">
                <button type="button" data-toggle="modal" data-target="#newTrainingModal" class="btn btn-default"><i class="fa fa-cube"></i> neues Modul</button>
            </span> -->
            <span data-container="body" data-toggle="tooltip" title="Importieren von exportierten Sets">
                <button type="button" name="importExport" value="import" class="btn btn-default"><i class="fa fa-upload"></i> Import</button>
            </span>
            <span data-container="body" data-toggle="tooltip" title="Exportieren aller Sets">
                <button type="button" name="importExport" value="export" class="btn btn-default"><i class="fa fa-download"></i> Export</button>
            </span>
        </div>
    </h3>
</div>
</div>
<div class="page-content-fixed-130">
<div class="container-fluid">
    <?php
    $result_module = $conn->query("SELECT dsgvo_training_modules.id, dsgvo_training_modules.name FROM dsgvo_training_modules LEFT JOIN dsgvo_training ON dsgvo_training.moduleID = dsgvo_training_modules.id WHERE dsgvo_training.companyID = $companyID OR dsgvo_training.companyID IS NULL GROUP BY dsgvo_training_modules.id");
    while ($result_module && ($row_module = $result_module->fetch_assoc())) {
        $moduleID = $row_module["id"];
        $moduleName = $row_module["name"];
        ?>
<div class="panel panel-default">
    <div class="panel-heading container-fluid">
    <span data-container="body" data-toggle="tooltip" title="Set">    
        <div class="col-xs-6"><a data-toggle="collapse" href="#moduleCollapse-<?php echo $moduleID; ?>"><i style="margin-left:-10px" class="fa fa-cubes"></i> <?php echo $moduleName ?></a></div>
    </span>
    <div class="col-xs-6 text-right">
        <form method="post">
            <span data-container="body" data-toggle="tooltip" title="Hinzufügen eines neuen Moduls">
                <button type="button" style="background:none;border:none;color:black;" name="addTraining" value="<?php echo $moduleID; ?>"><i class="fa fa-plus"></i></button>
            </span>
            <span data-container="body" data-toggle="tooltip" title="Set bearbeiten">                   
                <button type="button" style="background:none;border:none;color:black;" name="editModule" value="<?php echo $moduleID; ?>"><i class="fa fa-pencil-square-o"></i></button>
            </span>   
            <span data-container="body" data-toggle="tooltip" title="Exportieren eines einzelnen Sets">
                <button type="button" style="background:none;border:none;color:black;" name="export" value="<?php echo $moduleID; ?>"><i class="fa fa-download"></i></button>
            </span>
            <span data-container="body" data-toggle="tooltip" title="Gesamtes Set mit allen Modulen und Fragen löschen">            
                <button type="submit" style="background:none;border:none;color:#d90000;" name="removeModule" value="<?php echo $moduleID; ?>"><i class="fa fa-trash-o"></i></button>
            </span>
        </form>
    </div>
    </div>
    <div class="collapse <?= $moduleID == $activeModule ? 'in' : '' ?>" id="moduleCollapse-<?php echo $moduleID; ?>">
    <div class="panel-body container-fluid">
    <?php
    $result = $conn->query("SELECT * FROM dsgvo_training WHERE companyID = $companyID AND moduleID = $moduleID");
    while ($result && ($row = $result->fetch_assoc())) :
        $trainingID = $row['id'];
    ?>
<form method="post">
    <input type="hidden" name="trainingID" value="<?php echo $trainingID; ?>" />
<div class="panel panel-default">
    <div class="panel-heading container-fluid">
    <span data-container="body" data-toggle="tooltip" title="Modul">            
        <div class="col-xs-6"><a data-toggle="collapse" href="#trainingCollapse-<?php echo $trainingID; ?>"><i style="margin-left:-10px" class="fa fa-cube"></i> <?php echo $row['name']; ?></a></div>
    </span>
    <div class="col-xs-6 text-right">
        <span data-container="body" data-toggle="tooltip" title="Gesamtes Modul mit allen Fragen löschen">            
            <button type="submit" style="background:none;border:none;color:#d90000;" name="removeTraining" value="<?php echo $trainingID; ?>"><i class="fa fa-trash-o"></i></button>
        </span>        
    </div>
    </div>
    <div class="collapse <?php if ($trainingID == $activeTab) {
                            echo 'in';
                        } ?>" id="trainingCollapse-<?php echo $trainingID; ?>">
						                <div class="panel-body container-fluid">
						                    <?php

                            $result_question = $conn->query("SELECT * FROM dsgvo_training_questions WHERE trainingID = $trainingID");
                            while ($row_question = $result_question->fetch_assoc()) :
                                $questionID = $row_question["id"];
                            $title = $row_question["title"];
                            $text = $row_question["text"];
                            if ($trainingID == $activeTab) {
                                echo "<script>$('#moduleCollapse-$moduleID').addClass('in')</script>";
                            }
                            ?>
            <div class="col-md-12">
                <span data-container="body" data-toggle="tooltip" title="Frage löschen">   
                    <button type="submit" style="background:none;border:none" name="removeQuestion" value="<?php echo $questionID; ?>"><i class="fa fa-trash"></i></button>
                </span>
                <span data-container="body" data-toggle="tooltip" title="Frage bearbeiten">   
                    <button type="button" style="background:none;border:none" name="editQuestion" value="<?php echo $questionID; ?>"><i class="fa fa-edit"></i></button>
                </span>
                <span data-container="body" data-toggle="tooltip" title="Einzelauswertung der Frage">                   
                    <button type="button" style="background:none;border:none" name="infoQuestion" value="<?php echo $questionID; ?>"><i class="fa fa-pie-chart"></i></button>
                </span>
    <?php echo $title ?></div>
    <?php
    endwhile;

    ?>

            <div class="col-md-12 float-right">
            <div class="btn-group float-right" style="float:right!important">
                <span data-container="body" data-toggle="tooltip" title="Frage hinzufügen">                   
                    <button type="button" class="btn btn-default" data-toggle="modal" data-target="#addQuestionModal_<?php echo $trainingID; ?>"><i class="fa fa-plus"></i></button>
                </span>
                <span data-container="body" data-toggle="tooltip" title="Auswertung des Moduls als Graph">                   
                    <button type="button" class="btn btn-default" name="infoTraining" value="<?php echo $trainingID; ?>"><i class="fa fa-bar-chart-o"></i></button>
                </span>
                <span data-container="body" data-toggle="tooltip" title="Auswertung des Moduls als Tabelle">                   
                    <button type="button" class="btn btn-default" name="detailedInfoTraining" value="<?php echo $trainingID; ?>"><i class="fa fa-list-alt"></i></button>
                </span>
                <span data-container="body" data-toggle="tooltip" title="Modul aus der Sicht des Benutzers abspielen">                   
                    <button type="button" class="btn btn-default" name="testTraining" value="<?php echo $trainingID; ?>"><i class="fa fa-play"></i></button>
                </span>
                <span data-container="body" data-toggle="tooltip" title="Modul bearbeiten">                   
                    <button type="button" class="btn btn-warning" name="editTraining" value="<?php echo $trainingID; ?>"><i class="fa fa-pencil-square-o"></i></button>
                </span>
            </div>
            </div>
        </div>
        </div>
    </div>
    <!-- question add modal -->
        <div class="modal fade" id="addQuestionModal_<?php echo $trainingID; ?>">
        <div class="modal-dialog modal-content modal-md">
        <div class="modal-header">Neue Frage</div>
        <div class="modal-body">
            <input type="text" name="title" class="form-control" placeholder="Title"></input><br/>
            <textarea name="question" class="form-control tinymce" placeholder="Question"></textarea>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            <button type="submit" class="btn btn-warning" name="addQuestion" value="<?php echo $trainingID; ?>">Frage erstellen</button>
        </div>
        </div>
    </div>
    <!-- /question add modal -->
</form>

    <?php endwhile; ?>
</div></div></div>
<?php 
} ?>
</div>

<!-- new training modal -->

<form method="post">
  <div id="newTrainingModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><i class="fa fa-cube"></i> Neues Modul</h4>
        </div>
        <div class="modal-body">
        <label>Name*</label>
        <input type="text" class="form-control" name="name" placeholder="Name des Moduls" required/>
        <label>Set*</label>
        <select class="js-example-basic-single" name="module" required>
            <?php
            $result = $conn->query("SELECT * FROM dsgvo_training_modules");
            while ($result && ($row = $result->fetch_assoc())) {
                $name = $row["name"];
                $id = $row["id"];
                echo "<option value='$id'>$name</option>";
            }
            ?>
            </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-warning" name="createTraining" value="true"><?php echo $lang['ADD']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- /new training modal -->

<!-- new module modal -->

<form method="post">
  <div id="newModuleModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title"><i class="fa fa-cubes"></i> Neues Set</h4>
        </div>
        <div class="modal-body">
        <label>Name*</label>
        <input type="text" class="form-control" name="name" placeholder="Name des Sets" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-warning" name="createModule" value="true"><?php echo $lang['ADD']; ?></button>
        </div>
      </div>
    </div>
  </div>
</form>

<!-- /new module modal -->

<div id="currentQuestionModal"></div> <!-- for question and training edit modals and question info -->

<script>
function setCurrentModal(data, type, url){
    $.ajax({
        url: url,
        data: data,
        type: type,
        success : function(resp){
            $("#currentQuestionModal").html(resp);
        },
        error : function(resp){console.error(resp)},
        complete: function(resp){
            onModalLoad();
            $("#currentQuestionModal .modal").modal('show');
        }
   });
}
$("button[name=editQuestion]").click(function(){
    setCurrentModal({questionID: $(this).val()},'get', 'ajaxQuery/ajax_dsgvo_training_question_edit.php')
})
$("button[name=editTraining]").click(function(){
    setCurrentModal({trainingID: $(this).val()},'get', 'ajaxQuery/ajax_dsgvo_training_edit.php')
})
$("button[name=infoQuestion]").click(function(){
    setCurrentModal({questionID: $(this).val()},'get', 'ajaxQuery/AJAX_dsgvoQuestionInfo.php')
})
$("button[name=infoTraining]").click(function(){
    setCurrentModal({trainingID: $(this).val()},'get', 'ajaxQuery/AJAX_dsgvoTrainingInfo.php')
})
$("button[name=detailedInfoTraining]").click(function(){
    setCurrentModal({trainingID: $(this).val()},'get', 'ajaxQuery/AJAX_dsgvoDetailedTrainingInfo.php')
})
$("button[name=importExport]").click(function(){
    setCurrentModal({operation: $(this).val()}, 'post', 'ajaxQuery/ajax_dsgvo_training_import_export.php')
})
$("button[name=export]").click(function(){
    setCurrentModal({operation:"export",module: $(this).val()}, 'post', 'ajaxQuery/ajax_dsgvo_training_import_export.php')
})
$("button[name=testTraining]").click(function(){
    setCurrentModal({trainingID: $(this).val()}, 'post', 'ajaxQuery/ajax_dsgvo_training_user_generate_play.php')
})
$("button[name=editModule]").click(function(){
    setCurrentModal({moduleID: $(this).val()},'get', 'ajaxQuery/AJAX_dsgvoModuleEdit.php')
})
$("button[name=addTraining]").click(function(){
    setCurrentModal({moduleID: $(this).val()},'get', 'ajaxQuery/ajax_dsgvo_training_training_add.php')
})
</script>

<script>
function formatState (state) {
    if (!state.id) { return state.text; }
    var $state = $(
        '<span><i class="fa fa-fw fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
    );
    return $state;
};
function onModalLoad(){
    tinymce.init({
        selector: '.tinymce',
        plugins: "image autolink emoticons lists advlist",
        file_picker_types: 'image',
        toolbar: 'undo redo | cut copy paste | styleselect numlist bullist | link image emoticons | insertquestion insertquestion2',
        setup: function(editor){
            function insertQuestion(){
                var html = "<p>{ </p><p>[?] Welche dieser Antworten ist richtig? </p><p>[-] Eine falsche Antwort </p><p>[+] Eine richtige Antwort </p><p> }</p>";
                editor.insertContent(html);
            }

            editor.addButton("insertquestion",{
                tooltip: "Benutzerdefinierte Frage einfügen",
                icon: "template",
                onclick: insertQuestion,
            });
            function insertQuestion2(){
                var html = "<p>{ </p><p>[-] Eine falsche Antwort </p><p>[+] Eine richtige Antwort </p><p> }</p>";
                editor.insertContent(html);
            }

            editor.addButton("insertquestion2",{
                tooltip: "Frage einfügen",
                icon: "template",
                onclick: insertQuestion2,
            });
        },
        height : "480",
        menubar: false,
        statusbar: false,
        file_picker_callback: function(cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.onchange = function() {
                var file = this.files[0];
                var reader = new FileReader();
                reader.onload = function () {
                    // Note: Now we need to register the blob in TinyMCEs image blob
                    // registry. In the next release this part hopefully won't be
                    // necessary, as we are looking to handle it internally.
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    //console.log(reader.result.split(";")[0].split(":")[1]) //mime type
                    var base64 = reader.result.split(',')[1];
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    // call the callback and populate the Title field with the file name
                    cb(blobInfo.blobUri(), { title: file.name, text:file.name,alt:file.name,source:"images/Question_Circle.jpg",poster:"images/Question_Circle.jpg" });
                };
                reader.readAsDataURL(file);
            };
            input.click();
        }
    });
    $(".select2-team-icons").select2({
        templateResult: formatState,
        templateSelection: formatState
    });
    $(".js-example-basic-single").select2();
    $('[data-toggle="tooltip"]').tooltip(); 
}
onModalLoad();
</script>
</div>
<!-- /BODY -->
<?php include dirname(__DIR__) . '/footer.php'; ?>
