<?php
session_start();

require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";

if (isset($_REQUEST["questionID"])) {
    $edit = true;
    $questionID = intval($_REQUEST["questionID"]);
    $result = $conn->query("SELECT * FROM dsgvo_training_questions WHERE id = $questionID");
    showError($conn->error);
    $row = $result->fetch_assoc();
    showError($conn->error);
    $title = $row["title"];
    $text = $row["text"];
    $version = $row["version"];
    $survey = $row["survey"];
    list(, $question_type,,, $parsed) = parse_question($text, $survey === 'TRUE');
    $text = strip_questions($text);
} else if (isset($_REQUEST["new"], $_REQUEST["trainingID"])) {
    $edit = false;
    $trainingID = intval($_REQUEST["trainingID"]);
    $title = "";
    $text = "";
    $version = 1;
    $survey = 'FALSE';
    $question_type = "boolean";
    $parsed = [];
} else {
    die("Invalid data");
}

?>
<form method="POST">
<div class="modal fade ajax-open-modal">
    <div class="modal-dialog modal-content modal-lg">
    <div class="modal-header"><?php echo $lang['TRAINING_BUTTON_DESCRIPTIONS'][$edit ? 'EDIT_QUESTION' : 'ADD_QUESTION'] ?></div>
    <div class="modal-body">
        <label for="title"><?php echo $lang['TITLE'] ?></label>
        <input type="text" name="title" class="form-control" placeholder="Title" value="<?php echo $title; ?>"></input>
        <?php if ($edit) : ?>
        <br />
        <label for="version">Version</label>
        <input type="number" min="<?php echo $version; ?>" step="1" placeholder="Version" name="version" value="<?php echo $version; ?>" class="form-control" />
        <?php endif; ?>
        <div class="checkbox">
            <label>
                <input type="checkbox" name="survey" value="TRUE" <?php if ($survey == 'TRUE') {
                                                                        echo "checked";
                                                                    } ?>> Umfrage
            </label>
        </div>
        <textarea name="question" class="form-control tinymce" placeholder="Question"><?php echo $text; ?></textarea><br />
        <!-- question editor -->
        <div class="form-group">
            <label for="question_type">Frage <a data-toggle="modal" data-target="#question-edit-info"><i class="fa fa-question-circle-o"></i></a></label>
            <div class="input-group" style="width: 100%">
                <select class="form-control" name="question_type" style="width: 30%">
                    <option>none</option>
                </select>
                <input type="text" name="question_text" class="form-control" placeholder="Frage" value="" style="width:70%">
            </div>
        </div> 
        <div id="question_answers" class="form-group"></div>
        <button type="button" class="btn btn-default" id="new_answer">Neue Antwort</button>
        <script>
            var questionTypes = {
                training:[ "boolean", "dropdown", "radiogroup" ],
                survey: [ "boolean", "dropdown", "radiogroup", "checkbox" ]
            }
            var questionNames = {
                "boolean": "Gelesen",
                "dropdown": "Dropdown",
                "radiogroup": "Radio Group",
                "checkbox": "Kontrollkästchen (Mehrfachauswahl)"
            }
            var surveyOrTraining = $("input[name=survey]")[0].checked?"survey":"training";
            var currentQuestionType = "<?php echo $question_type ?>";
            var currentAnswers = JSON.parse('<?php echo json_encode($parsed) ?>');
            $("input[name=survey]").change(function(event){
                if(this.checked){
                    surveyOrTraining = "survey";
                }else{
                    surveyOrTraining = "training";
                    if(currentQuestionType == "checkbox") currentQuestionType = "radiogroup"
                }
                updateQuestionAnswers()
            })
            function updateQuestionAnswers(){
                $("select[name=question_type]").html(questionTypes[surveyOrTraining].map(function(type){ return "<option value='"+type+"'>"+questionNames[type]+"</option>" }))
                var html = '';
                $('[name=question_type]').val(currentQuestionType);
                $('[name=question_text]').prop("disabled",currentQuestionType == "boolean")
                if(currentQuestionType == "boolean"){
                    $("#question_answers").html("");
                    return;
                }
                for(i = 0; i<currentAnswers.length; i++){
                    var operator = currentAnswers[i].operator;
                    var value = currentAnswers[i].value;
                    if(operator == "?"){
                        $('[name=question_text]').val(value);
                        continue;
                    }
                    if(operator == "#"){
                        continue;
                    }
                    html += '<div class="input-group" style="width: 100%; margin-bottom:5px;">';
                    if(surveyOrTraining == "training"){
                        html += '<select onchange="updateQuestion('+i+',this, \'operator\')" class="form-control" name="answer_operators[]" style="width:30%">';
                        html += '<option value="-"' + (operator == "-"?' selected ':'') + '>Falsch</option>';
                        html += '<option value="+"' + (operator == "+"?' selected ':'') + '>Richtig</option>';
                        html += '</select>';
                    }else{
                        if(operator == "+") operator = "yes";
                        if (operator == "-") operator = "no"; 
                        html += '<input onchange="updateQuestion('+i+',this, \'operator\')" type="text" name="answer_operators[]" class="form-control" placeholder="Wert" value="' + operator + '" style="width:30%">'
                    }
                    html += '<input type="text" onchange="updateQuestion('+i+',this, \'value\')" name="answer_values[]" class="form-control" placeholder="Antwort" value="' + value + '" style="width:70%">';
                    html += '<span class="input-group-btn"><button type="button" onclick="removeAnswer('+i+')" class="btn btn-danger">Entfernen</button></span>';
                    html += '</div>';
                }
                $("#question_answers").html(html);
            }
            function updateQuestion(index, target, key){
                currentAnswers[index][key] = target.value;
            }
            function removeAnswer(index){
                currentAnswers.splice(index,1);
                if(currentAnswers.filter(function(e){return e.operator != "?" && e.operator != "#"}).length == 0) currentQuestionType = "boolean";
                updateQuestionAnswers();
            }
            updateQuestionAnswers();
            $("select[name=question_type]").change(function(){
                currentQuestionType = this.value;
                if(currentQuestionType != "boolean" && currentAnswers.filter(function(e){return e.operator != "?" && e.operator != "#"}).length == 0) $("#new_answer").click();
                updateQuestionAnswers();
            })
            $("#new_answer").click(function(){
                currentAnswers.push({operator: surveyOrTraining == "survey"?"option":"+", value: surveyOrTraining == "survey"?"Eine Option":"Das ist richtig"})
                if(currentQuestionType == "boolean") currentQuestionType = "radiogroup";
                updateQuestionAnswers();
            })
            console.log(currentQuestionType, currentAnswers);
        </script>
        <!-- /question editor -->
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
        <button type="submit" class="btn btn-warning" name="<?php echo $edit ? 'editQuestion' : 'addQuestion' ?>" value="<?php echo $edit ? $questionID : $trainingID; ?>"><?php echo $lang[$edit ? 'EDIT' : 'ADD'] ?></button>
    </div>
    </div>
</div>
</form>
<div id="question-edit-info" class="modal fade">
  <div class="modal-dialog modal-content modal-md">
    <div class="modal-header">Fragentypen</div>
    <div class="modal-body">
        <h5>Schulung</h5>
        <span>In der Auswertung des Moduls sind Schulungen eine Farbe zwischen rot und grün, an der man erkennen kann, ob der Benutzer die gestellte Frage richtig oder falsch beantwortet hat. </span>
        <dl>
            <dt>Gelesen</dt>
            <dd>Die Frage wird als richtig beantwortet marktiert, wenn der User das ein Kontrollkästchen klickt.</dd>
            <dt>Dropdown</dt>
            <dd>Der User kann zwischen mehreren Auswahlmöglichkeiten entscheiden. Es kann mehrere richtige und falsche Antworten geben. Falls mehrere Antworten richtig sind, wird nicht angezeigt, welche Antwort der User ausgewählt hat. </dd>
            <dt>Radio Group</dt>
            <dd>Hier kann sich der User zwischen mehreren Auswahlmöglichkeiten entscheiden. In der Auswertung wird nur angezeigt, ob der User eine der richtigen Antwortmöglichkeiten ausgewählt hat. </dd>
        </dl>
        <hr>
        <h5>Umfrage</h5>
        <span>In der Auswertung des Moduls sind Umfragen blau. Man kann dabei nicht erkennen, welche Antworten der User gegeben hat. Genauere Auswertungen sind bei den Einzelauswertungen der Frage zu finden. </span>
        <dl>
            <dt>Gelesen</dt>
            <dd>Hier kann der User wie bei der Schulung antworten, aber die Antworten werden nicht als richtig oder falsch marktiert</dd>
            <dt>Dropdown</dt>
            <dd>Der User kann zwischen mehreren Auswahlmöglichkeiten entscheiden. Dabei kann aber nur eine ausgewählt werden. In der Auswertung wird angezeigt, welche Antwort der User gegeben hat. </dd>
            <dt>Radio Group</dt>
            <dd>Hier hat der User mehrere Antwortmöglichkeiten, kann aber nur eine auswählen. In der Auswertung wird angezeigt, welche Antwort der User gegeben hat. </dd>
            <dt>Kontrollkästchen</dt>
            <dd>Der User kann beliebig viele der Auswahlmöglichkeiten auswählen. Es wird angezeigt, welche Antworten der User auf die Frage gegeben hat. </dd>
        </dl>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
  </div>
</div>