<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'header.php'; ?>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . "misc" . DIRECTORY_SEPARATOR . "helpcenter.php"; ?>
<script src='../plugins/tinymce/tinymce.min.js'></script>

<?php
// A module is a group of trainings                 (renamed to Set)
// A training is a group of questions (set)         (renamed to Modul)
// A question is a text with different answers      (renamed to Frage)

if ($userHasUnansweredOnLoginSurveys) {
    $userHasUnansweredOnLoginSurveys = false;// do not display surveys when editing them
    showInfo("Da Sie gerade Schulungen bearbeiten, wurde eine fällige Schulung unterdrückt");
}
$trainingID = 0;
if (isset($_REQUEST["trainingid"])) {
    $trainingID = intval($_REQUEST["trainingid"]);
}
if (!isset($_REQUEST["cmp"])) {
    showError("no company");
    include dirname(__DIR__) . '/footer.php';
    die();
}
$companyID = intval($_REQUEST['cmp']);
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

function parse_question_form()
{
    $text = "";
    if (isset($_POST["question_type"]) && $_POST["question_type"] != "boolean") {
        $text .= "{";
        if (isset($_POST["question_type"])) {
            $text .= " [#]" . test_input($_POST["question_type"]);
        }
        if (isset($_POST["question_text"])) {
            if (strlen(trim(test_input($_POST["question_text"]))))
                $text .= " [?]" . test_input($_POST["question_text"]);
        }
        if (isset($_POST["answer_operators"], $_POST["answer_values"]) && is_array($_POST["answer_operators"]) && is_array($_POST["answer_operators"])) {
            foreach ($_POST["answer_operators"] as $index => $operator) {
                $value = isset($_POST["answer_values"][$index]) ? test_input($_POST["answer_values"][$index]) : "";
                $operator = test_input($operator);
                $text .= " [" . $operator . "]" . $value;
            }
        }
        $text .= "}";
    }
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && Permissions::has("TRAINING.WRITE")) {
    if (isset($_POST['undoSuspension'])) {
        $user = intval($_POST['undoSuspension']);
        $conn->query("DELETE FROM dsgvo_training_user_suspension WHERE userID = $user");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_SAVE"]);
        }
    } elseif (isset($_POST['fastForwardSuspension'])) {
        $user = intval($_POST['fastForwardSuspension']);
        $conn->query("INSERT INTO dsgvo_training_user_suspension (suspension_count, userID, last_suspension) VALUES (3,$user,TIMESTAMP(DATE_SUB(NOW(), INTERVAL 3 day))) ON DUPLICATE KEY UPDATE suspension_count = 3, last_suspension = TIMESTAMP(DATE_SUB(NOW(), INTERVAL 3 day))");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_SAVE"]);
        }
    } elseif (isset($_POST['createTraining']) && !empty($_POST['name'])) {
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
        $survey = isset($_POST["survey"]) ? 'TRUE' : 'FALSE';
        $text = $_POST["question"]; // todo: test input
        $version = 1;
        $text .= parse_question_form();
        $stmt = $conn->prepare("INSERT INTO dsgvo_training_questions (trainingID, text, title, version, survey) VALUES ($trainingID, ?, '$title', $version, '$survey')");
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
    } elseif (isset($_POST["removeQuestionAnswers"])) {
        $trainingID = $_POST["trainingID"];
        $questionID = intval($_POST["removeQuestionAnswers"]);
        $conn->query("DELETE FROM dsgvo_training_completed_questions WHERE questionID = $questionID");
        if ($conn->error) {
            showError($conn->error);
        } else {
            showSuccess($lang["OK_DELETE"]);
        }
        insertVVLog("DELETE", "Delete question answers with id '$questionID'");
    } elseif (isset($_POST["editQuestion"])) {
        $questionID = intval($_POST["editQuestion"]);
        $title = test_input($_POST["title"]);
        $text = $_POST["question"]; //todo: test input
        $text .= parse_question_form();
        $version = intval($_POST["version"]);
        $survey = isset($_POST["survey"]) ? 'TRUE' : 'FALSE';
        $stmt = $conn->prepare("UPDATE dsgvo_training_questions SET text = ?, title = '$title', version = $version, survey = '$survey' WHERE id = $questionID");
        showError($conn->error);
        $stmt->bind_param("s", $text);
        $stmt->execute();
        if ($stmt->error) {
            showError($stmt->error);
        } else {
            showSuccess($lang["OK_SAVE"]);
        }
        $result = $conn->query("SELECT trainingID FROM dsgvo_training_questions WHERE id = $questionID");
        if ($result && ($row = $result->fetch_assoc())) {
            $trainingID = $row["trainingID"];
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
        $update_question_stmt = $conn->prepare("UPDATE dsgvo_training_questions SET text = ? WHERE id = ?"); // TODO: add support for survey
        $update_question_stmt->bind_param("si", $text, $questionID);
        $insert_question_stmt = $conn->prepare("INSERT INTO dsgvo_training_questions (trainingID, text, title) VALUES(?, ?, ?)"); // TODO: add support for survey
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
showError($conn->error);
?>
<div class="page-header-fixed">
    <div class="page-header">
        <h3>
            <?php echo $lang['TRAINING'] ?>
            <div class="page-header-button-group">
                <?php if (Permissions::has("TRAINING.WRITE")) : ?>
                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang["NEW_SET_DESCRIPTION"] ?>">
                    <button type="button" data-toggle="modal" data-target="#newModuleModal" class="btn btn-default">
                        <i class="fa fa-cubes"></i>
                        <?php echo $lang['NEW_SET'] ?>
                    </button>
                </span>
                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang["IMPORT_DESCRIPTION"] ?>">
                    <button type="button" name="importExport" value="import" class="btn btn-default">
                        <i class="fa fa-upload"></i> Import</button>
                </span>
                <?php endif ?>
                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang["EXPORT_ALL_SETS"] ?>">
                    <button type="button" name="importExport" value="export" class="btn btn-default">
                        <i class="fa fa-download"></i> Export</button>
                </span>
                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang["SUSPENDED_SURVEYS"] ?>">
                    <button type="button" name="suspendedSurveys" class="btn btn-default">
                        <i class="fa fa-hourglass-half"></i> Aufgeschoben</button>
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
                    <div class="col-xs-6">
                        <a data-toggle="collapse" href="#moduleCollapse-<?php echo $moduleID; ?>">
                            <i style="margin-left:-10px" class="fa fa-cubes"></i>
                            <?php echo $moduleName ?>
                        </a>
                    </div>
                </span>
                <div class="col-xs-6 text-right" style="padding-right: 30px">
                    <form method="post">
                        <!-- <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['STATS_GRAPH'] ?>">
                            <button type="button" style="background:none;border:none;" name="infoModule" value="<?php echo $moduleID; ?>">
                                <i class="fa fa-fw fa-area-chart"></i>
                            </button>
                        </span> -->
                        <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['EXPORT_SET'] ?>">
                            <button type="button" style="background:none;border:none;" name="export" value="<?php echo $moduleID; ?>">
                                <i class="fa fa-fw fa-download"></i>
                            </button>
                        </span>
                         <?php if (Permissions::has("TRAINING.WRITE")) : ?>
                        <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['NEW_MODULE'] ?>">
                            <button type="button" style="background:none;border:none;" name="addTraining" value="<?php echo $moduleID; ?>">
                                <i class="fa fa-fw fa-plus"></i>
                            </button>
                        </span>
                        <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['EDIT_SET'] ?>">
                            <button type="button" style="background:none;border:none;" name="editModule" value="<?php echo $moduleID; ?>">
                                <i class="fa fa-fw fa-pencil"></i>
                            </button>
                        </span>
                        <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['DELETE_SET'] ?>">
                            <button onclick="return (confirm('<?php echo $lang['ARE_YOU_SURE'] ?>') === true)" type="submit" style="background:none;border:none;color:#d90000;" name="removeModule" value="<?php echo $moduleID; ?>">
                                <i class="fa fa-fw fa-trash-o"></i>
                            </button>
                        </span>
                        <?php endif ?>
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
                                    <div class="col-xs-6">
                                        <a data-toggle="collapse" href="#trainingCollapse-<?php echo $trainingID; ?>">
                                            <i style="margin-left:-10px" class="fa fa-cube"></i>
                                            <?php echo $row['name']; ?>
                                        </a>
                                    </div>
                                </span>
                                <div class="col-xs-6 text-right">
                                    <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['STATS_GRAPH'] ?>">
                                        <button type="button" style="background:none;border:none;" name="infoTraining" value="<?php echo $trainingID; ?>">
                                            <i class="fa fa-fw fa-bar-chart-o"></i>
                                        </button>
                                    </span>
                                    <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['TRY'] ?>">
                                        <button type="button" style="background:none;border:none;" name="testTraining" value="<?php echo $trainingID; ?>">
                                            <i class="fa fa-fw fa-play"></i>
                                        </button>
                                    </span>
                                    <?php if (Permissions::has("TRAINING.WRITE")) : ?>
                                    <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['ADD_QUESTION'] ?>">
                                        <button type="button" style="background:none;border:none;" name="addQuestion" value="<?php echo $trainingID; ?>">
                                            <i class="fa fa-fw fa-plus"></i>
                                        </button>
                                    </span>
                                    <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['EDIT_MODULE'] ?>">
                                        <button type="button" style="background:none;border:none;" name="editTraining" value="<?php echo $trainingID; ?>">
                                            <i class="fa fa-fw fa-pencil"></i>
                                        </button>
                                    </span>
                                    <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['DELETE_MODULE'] ?>">
                                        <button onclick="return (confirm('<?php echo $lang['ARE_YOU_SURE'] ?>') === true)" type="submit" style="background:none;border:none;color:#d90000;" name="removeTraining" value="<?php echo $trainingID; ?>">
                                            <i class="fa fa-fw fa-trash-o"></i>
                                        </button>
                                    </span>
                                    <?php endif ?>
                                </div>
                            </div>
                            <div class="collapse <?php if ($trainingID == $activeTab) {
                                                    echo 'in';
                                                } ?>" id="trainingCollapse-<?php echo $trainingID; ?>">
                                <div class="panel-body container-fluid">
                                    <?php

                                    $result_question = $conn->query("SELECT id,title,survey, count(dsgvo_training_completed_questions.questionID) answer_count FROM dsgvo_training_questions LEFT JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id WHERE trainingID = $trainingID GROUP BY dsgvo_training_questions.id");
                                    while ($row_question = $result_question->fetch_assoc()) :
                                        $questionID = $row_question["id"];
                                    $title = $row_question["title"];
                                    $isSurvey = $row_question["survey"] == 'TRUE';
                                    $answer_count = $row_question["answer_count"];
                                    if ($trainingID == $activeTab) {
                                        echo "<script>$('#moduleCollapse-$moduleID').addClass('in')</script>";
                                    }
                                    ?>
                                    <div class="panel panel-default">
                                        <div class=" panel-heading clearfix">

                                            <span class="text-left col-xs-6" style="padding-left: 0">
                                                <span class="label label-default"><?php echo "$answer_count " . ($answer_count == 1 ? 'Antwort' : 'Antworten') ?></span>&nbsp;
                                                <?php if ($isSurvey) : ?>
                                                <span class="label label-default">Umfrage</span>&nbsp;
                                                <?php endif; ?>
                                                <br />
                                                <?php echo $title ?>
                                            </span>
                                            <div class="text-right col-xs-6" style="padding-right: 0px;">
                                                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['TRY_QUESTION'] ?>">
                                                    <button type="button" style="background:none;border:none" name="testQuestion" value="<?php echo $questionID; ?>">
                                                        <i class="fa fa-fw fa-play"></i>
                                                    </button>
                                                </span>
                                                <?php if ($answer_count != 0) : ?>
                                                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['QUESTION_STATS'] ?>">
                                                    <button type="button" style="background:none;border:none" name="infoQuestion" value="<?php echo $questionID; ?>">
                                                        <i class="fa fa-fw fa-pie-chart"></i>
                                                    </button>
                                                </span>
                                                <?php endif ?>
                                                <?php if (Permissions::has("TRAINING.WRITE")) : ?>
                                                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['EDIT_QUESTION'] ?>">
                                                    <button type="button" style="background:none;border:none" name="editQuestion" value="<?php echo $questionID; ?>">
                                                        <i class="fa fa-fw fa-pencil"></i>
                                                    </button>
                                                </span>
                                                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['DELETE_QUESTION'] ?>">
                                                    <button onclick="return (confirm('<?php echo $lang['ARE_YOU_SURE'] ?>') === true)" type="submit" style="background:none;border:none;color:#d90000;" name="removeQuestion" value="<?php echo $questionID; ?>">
                                                        <i class="fa fa-fw fa-trash-o"></i>
                                                    </button>
                                                </span>
                                                <?php if ($answer_count != 0) : ?>
                                                <span data-container="body" data-toggle="tooltip" title="<?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['DELETE_QUESTION_ANSWERS'] ?>">
                                                    <button onclick="return (confirm('<?php echo $lang['ARE_YOU_SURE'] ?>') === true)" type="submit" style="background:none;border:none;color:#d90000;" name="removeQuestionAnswers" value="<?php echo $questionID; ?>">
                                                        <i class="fa fa-fw fa-eraser"></i>
                                                    </button>
                                                </span>
                                                <?php endif ?>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    endwhile;

                                    ?>

                                    <div class="col-md-12 float-right">
                                        <div class="btn-group float-right" style="float:right!important">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php 
    } ?>
    </div>

    <!-- new training modal -->

    <form method="post">
        <div id="newTrainingModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-cube"></i>
                            <?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['NEW_MODULE'] ?>
                        </h4>
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
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo $lang['CANCEL']; ?>
                        </button>
                        <button type="submit" class="btn btn-warning" name="createTraining" value="true">
                            <?php echo $lang['ADD']; ?>
                        </button>
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
                        <h4 class="modal-title">
                            <i class="fa fa-cubes"></i> Neues Set</h4>
                    </div>
                    <div class="modal-body">
                        <label>Name*</label>
                        <input type="text" class="form-control" name="name" placeholder="Name des Sets" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <?php echo $lang['CANCEL']; ?>
                        </button>
                        <button type="submit" class="btn btn-warning" name="createModule" value="true">
                            <?php echo $lang['ADD']; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- /new module modal -->

    <div id="currentQuestionModal"></div>
    <!-- for question and training edit modals and question info -->

    <script>
        function setCurrentModal(data, type, url, complete) {
            $.ajax({
                url: url,
                data: data,
                type: type,
                success: function (resp) {
                    $("#currentQuestionModal").html(resp);
                },
                error: function (resp) { console.error(resp) },
                complete: function (resp) {
                    if (complete) complete(resp);
                    else $("#currentQuestionModal .modal").modal('show');
                    onModalLoad();
                }
            });
        }
        $("button[name=editQuestion]").click(function () {
            setCurrentModal({ questionID: $(this).val() }, 'get', 'ajaxQuery/ajax_dsgvo_training_question_edit.php', function () {
                $("#currentQuestionModal .ajax-open-modal").modal();
            })
        })
        $("button[name=editTraining]").click(function () {
            setCurrentModal({ trainingID: $(this).val() }, 'get', 'ajaxQuery/ajax_dsgvo_training_edit.php')
        })
        $("button[name=infoQuestion]").click(function () {
            setCurrentModal({ questionID: $(this).val() }, 'get', 'ajaxQuery/ajax_dsgvo_training_question_info.php')
        })
        $("button[name=addQuestion]").click(function () {
            setCurrentModal({ new: true, trainingID: $(this).val() }, 'get', 'ajaxQuery/ajax_dsgvo_training_question_edit.php', function () {
                $("#currentQuestionModal .ajax-open-modal").modal();
            })
        })
        $("button[name=infoTraining]").click(function () {
            setCurrentModal({ trainingID: $(this).val() }, 'get', 'ajaxQuery/ajax_dsgvo_training_training_info.php')
        })
        $("button[name=importExport]").click(function () {
            setCurrentModal({ operation: $(this).val() }, 'post', 'ajaxQuery/ajax_dsgvo_training_import_export.php')
        })
        $("button[name=export]").click(function () {
            setCurrentModal({ operation: "export", module: $(this).val() }, 'post', 'ajaxQuery/ajax_dsgvo_training_import_export.php')
        })
        $("button[name=testTraining]").click(function () {
            setCurrentModal({ trainingID: $(this).val(), test: true }, 'post', 'ajaxQuery/ajax_dsgvo_training_user_generate.php', function () {
                $("#currentQuestionModal .survey-modal").modal();
            })
        })
        $("button[name=testQuestion]").click(function () {
            setCurrentModal({ questionID: $(this).val(), test: true }, 'post', 'ajaxQuery/ajax_dsgvo_training_user_generate.php', function () {
                $("#currentQuestionModal .survey-modal").modal();
            })
        })
        $("button[name=editModule]").click(function () {
            setCurrentModal({ moduleID: $(this).val() }, 'get', 'ajaxQuery/ajax_dsgvo_training_module_edit.php')
        })
        $("button[name=addTraining]").click(function () {
            setCurrentModal({ moduleID: $(this).val() }, 'get', 'ajaxQuery/ajax_dsgvo_training_training_add.php')
        })
        $("button[name=suspendedSurveys]").click(function () {
            setCurrentModal({}, 'post', 'ajaxQuery/ajax_dsgvo_training_suspended_surveys.php')
        }) 
        // $("button[name=infoModule]").click(function () {
        //     setCurrentModal({ moduleID: $(this).val() }, 'get', 'ajaxQuery/ajax_dsgvo_training_module_info.php')
        // })
    </script>

    <script>
        function formatState(state) {
            if (!state.id) { return state.text; }
            var $state = $(
                '<span><i class="fa fa-fw fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
            );
            return $state;
        };
        var insertCustomQuestionButton = []
        function onModalLoad() {
            tinymce.init({
                selector: '.tinymce',
                plugins: "image autolink emoticons lists advlist textcolor charmap colorpicker visualblocks",
                file_picker_types: 'image',
                toolbar: 'undo redo | cut copy paste | styleselect numlist bullist forecolor backcolor | link image emoticons charmap | visualblocks | --mybutton',
                setup: function (editor) {
                    editor.addButton('mybutton', {
                        type: 'listbox',
                        text: '<?php echo $lang['INSERT_CUSTOM_QUESTION'] ?>',
                        icon: false,
                        onselect: function (e) {
                            editor.insertContent(this.value());
                        },
                        values: [
                            { text: 'Schulung: nur Antwortmöglichkeiten', value: '<p>{ </p><p>[-] Eine falsche Antwort </p><p>[+] Eine richtige Antwort </p><p> }</p>' },
                            { text: 'Schulung: mit Frage', value: '<p>{ </p><p>[?] Welche dieser Antworten ist richtig? </p><p>[-] Eine falsche Antwort </p><p>[+] Eine richtige Antwort </p><p> }</p>' },
                            { text: 'Schulung: mit Frage (Dropdown)', value: '<p>{ </p><p>[#]dropdown</p><p>[?] Welche dieser Antworten ist richtig? </p><p>[-] Eine falsche Antwort </p><p>[+] Eine richtige Antwort </p><p> }</p>' },
                            { text: 'Umfrage: nur Antwortmöglichkeiten', value: '<p>{ </p><p>[?] Wie ist ihre Meinung? </p><p>[ja] Das finde ich toll </p><p>[nein] Das ist keine gute Idee </p><p> }</p>' },
                            { text: 'Umfrage: gleiche Antwortmöglichkeiten', value: '<p>{ </p><p>[?] Wie ist ihre Meinung? </p><p>[ja] Das finde ich toll </p><p>[ja] Das ist eine gute Idee </p><p>[nein] Das ist keine gute Idee </p><p>[nein] Das würde ich nicht machen </p><p> }</p>' },
                            { text: 'Umfrage: mit Frage (Dropdown)', value: '<p>{ </p><p>[#]dropdown</p><p>[?] Wie ist ihre Meinung? </p><p>[ja] Das finde ich toll </p><p>[nein] Das ist keine gute Idee </p><p>[unentschlossen] Keine Ahnung </p><p> }</p>' },
                            { text: 'Umfrage: mehrere Antwortmöglichkeiten', value: '<p>{ </p><p>[#]checkbox</p><p>[?] Wie ist ihre Meinung? </p><p>[1] Option 1 </p><p>[2] Option 2 </p><p>[3] Option 3 </p><p> }</p>' },
                        ],
                        onPostRender: function () {
                            insertCustomQuestionButton.push(this);
                        }
                    });
                },
                height: "200",
                menubar: false,
                statusbar: false,
                file_picker_callback: function (cb, value, meta) {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    input.onchange = function () {
                        var file = this.files[0];
                        var reader = new FileReader();
                        reader.onload = function () {
                            // Note: Now we need to register the blob in TinyMCEs image blob
                            // registry. In the next release this part hopefully won't be
                            // necessary, as we are looking to handle it internally.
                            var id = 'blobid' + (new Date()).getTime();
                            var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                            //console.log(reader.result.split(";")[0].split(":")[1]) //mime type
                            var base64 = reader.result.split(',')[1];
                            var blobInfo = blobCache.create(id, file, base64);
                            blobCache.add(blobInfo);
                            // call the callback and populate the Title field with the file name
                            cb(blobInfo.blobUri(), { title: file.name, text: file.name, alt: file.name, source: "images/Question_Circle.jpg", poster: "images/Question_Circle.jpg" });
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