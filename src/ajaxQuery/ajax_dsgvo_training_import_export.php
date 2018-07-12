<?php
session_start();
if (!isset($_REQUEST["operation"])) {
    echo "error (no operation)";
    die();
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
$operation = $_REQUEST["operation"];
if ($operation == "export") {
    $output = array();
    $extra = "";
    if(isset($_REQUEST["module"])){
        $moduleID = intval($_REQUEST["module"]);
        $extra = "WHERE id=$moduleID";
    }
    $result = $conn->query("SELECT * FROM dsgvo_training_modules $extra");
    while ($result && $row = $result->fetch_assoc()) {
        $sets = array();
        $moduleID = $row["id"];
        $result_sets = $conn->query("SELECT * FROM dsgvo_training WHERE moduleID = $moduleID");
        while ($result_sets && $row_sets = $result_sets->fetch_assoc()) {
            $questions = array();
            $trainingID = $row_sets["id"];
            $result_questions = $conn->query("SELECT * FROM dsgvo_training_questions WHERE trainingID = $trainingID");
            while ($result_questions && $row_questions = $result_questions->fetch_assoc()) {
                $questions[] = array("title" => $row_questions["title"], "text" => $row_questions["text"]);
            }
            $sets[] = array("set" => $row_sets["name"], "version" => $row_sets["version"], "onlogin" => $row_sets["onLogin"], "allowoverwrite" => $row_sets["allowOverwrite"], "random" => $row_sets["random"], "questions" => $questions);
        }
        $output[] = array("module" => $row["name"], "sets" => $sets);
    }
    $json = json_encode($output, JSON_PRETTY_PRINT);
}
?>
<!-- Export -->
<?php if ($operation == "export"): ?>
<form method="POST">
<div class="modal fade">
    <div class="modal-dialog modal-content modal-md">
    <div class="modal-header">Daten exportieren</div>
    <div class="modal-body">
        <div class="row">
        <textarea name="question" class="form-control" id="copyToClipboard" readonly style="max-width:100%;min-width:100%;height:70vh;"><?php echo $json; ?></textarea>
        </div>
        <div class="row">
        <button class="btn btn-primary" type="button" id="copyToClipboardBtn">In Zwischenablage kopieren</button>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
    </div>
</div>
</form>

<script>
$("#copyToClipboardBtn").click(function(){
    var text = document.getElementById("copyToClipboard")
    text.select();
    document.execCommand("Copy");
    $("#copyToClipboardBtn").addClass("btn-success").removeClass("btn-primary").html("Erfolgreich kopiert");
    setTimeout(function(){
        $("#copyToClipboardBtn").addClass("btn-primary").removeClass("btn-success").html("In Zwischenablage kopieren");
    }, 1000);
})
</script>

<!-- IMPORT -->
<?php else: ?>
<form method="POST">
<div class="modal fade">
    <div class="modal-dialog modal-content modal-md">
    <div class="modal-header">Daten importieren</div>
    <div class="modal-body">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="replace_old" value="TRUE"> Alte Sets/Module/Fragen überschreiben
            </label>
        </div>
        <textarea name="jsonImport" id="importArea" class="form-control" rows="10" placeholder="JSON Text hier einfügen" style="max-width:100%;min-width:100%;height:60vh;"></textarea>
        <br><div id="jsonError"></div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
        <input type="submit" class="btn btn-primary" id="importSubmit" name="import" value="Import"/>
    </div>
    </div>
</div>
</form>
<script>
$("body").on("input propertychange", "#importArea" ,function(event){
    if(jsonValid(event.target.value)){
        $("#importSubmit").prop("disabled",false).addClass("btn-success").removeClass("btn-primary").removeClass("btn-danger");
    }else{
        $("#importSubmit").prop("disabled",true).addClass("btn-danger").removeClass("btn-success").removeClass("btn-primary");
    }
})
function jsonValid(text){
    try{
        JSON.parse(text);
        $("#jsonError").html("")
        return true;
    }catch(error){
        $("#jsonError").html("<div class='alert alert-danger'><strong>Error </strong> "+error+"</div>").addClass("danger");
        return false;
    }
}

</script>
<?php endif;?>