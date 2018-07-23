<?php
session_start();
if (!isset($_REQUEST["questionID"])) {
    echo "error";
    die();
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";

$questionID = $_REQUEST["questionID"];
$row = $conn->query("SELECT * FROM dsgvo_training_questions WHERE id = $questionID")->fetch_assoc();
showError($conn->error);
$title = $row["title"];
$text = $row["text"];
$version = $row["version"];
$survey = $row["survey"];
?>
<form method="POST">
<div class="modal fade">
    <div class="modal-dialog modal-content modal-lg">
    <div class="modal-header"><?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS']['EDIT_QUESTION'] ?></div>
    <div class="modal-body">
        <label for="title"><?php echo $lang['TITLE'] ?></label>
        <input type="text" name="title" class="form-control" placeholder="Title" value="<?php echo $title; ?>"></input><br />
        <label for="version">Version</label>
        <input type="number" min="<?php echo $version; ?>" step="1" placeholder="Version" name="version" value="<?php echo $version; ?>" class="form-control" />
        <div class="checkbox">
            <label>
                <input type="checkbox" name="survey" value="TRUE" <?php if($survey =='TRUE') {echo "checked"; } ?>> Umfrage
            </label>
        </div>
        <textarea name="question" class="form-control tinymce" placeholder="Question"><?php echo $text; ?></textarea>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
        <button type="submit" class="btn btn-warning" name="editQuestion" value="<?php echo $questionID; ?>"><?php echo $lang['EDIT'] ?></button>
    </div>
    </div>
</div>
</form>