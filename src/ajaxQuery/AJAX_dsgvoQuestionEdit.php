<?php 
if (!isset($_REQUEST["questionID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

$questionID = $_REQUEST["questionID"];
$row = $conn->query("SELECT * FROM dsgvo_training_questions WHERE id = $questionID")->fetch_assoc();
$title = $row["title"];
$text = $row["text"];
?>
<form method="POST">
<div class="modal fade">
    <div class="modal-dialog modal-content modal-md">
    <div class="modal-header">Bestehende Aufgabenstellung/Schulung bearbeiten <?php echo $questionID; ?></div>
    <div class="modal-body">
        <input type="text" name="title" class="form-control" placeholder="Title" value="<?php echo $title; ?>"></input><br/>
        <input type="text" name="question" class="form-control tinymce" placeholder="Question" value="<?php echo $text; ?>"></input>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        <button type="submit" class="btn btn-warning" name="editQuestion" value="<?php echo $questionID; ?>">Bearbeiten</button>
    </div>
    </div>
</div>
</form>