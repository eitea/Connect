<?php
session_start();
$userID = $_SESSION['userid'] or die("no user signed in");
$trainingID = isset($_POST["trainingID"]) or die("no training id");
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";
$trainingID = intval($_POST["trainingID"]);
$onLogin = false;
$doneSurveys = false;
// $hasQuestions = false; // some questions are not valid (invalid syntax)

function strip_questions($html){ // this will be the question
    $regexp = '/\{.*?\}/s';
    return preg_replace($regexp, "", $html);
}

function parse_questions($html){ // this will return an array of questions
    $questionRegex = '/\{.*?\}/s';
    $htmlRegex = '/\<\/*.+?\/*\>/s';
    global $hasQuestions;
    $html = preg_replace($htmlRegex,"",$html); // strip all html tags
    preg_match($questionRegex,$html,$matches);
    // I only parse the first question for now
    if(sizeof($matches)==0) return array();
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    $answerRegex = '/\[([+-])\]([^\[\}]+)/s';
    preg_match_all($answerRegex,$question,$matches);
    if(sizeof($matches)==0) return array();
    $ret_array = array();
    foreach ($matches[2] as $key => $value) {
        $ret_array[] = array("value"=>$key,"text"=>html_entity_decode($value));
    }
    if(sizeof($ret_array) > 0){
        $hasQuestions = true;
    }
    return $ret_array;
}

$trainingArray = array();
$result = $conn->query("SELECT * FROM dsgvo_training WHERE id = $trainingID");
$row = $result->fetch_assoc();
$questionArray = array();
$random = $row["random"];
$onLogin = $row["onLogin"];
$result_questions = $conn->query("SELECT id, text FROM dsgvo_training_questions WHERE trainingID = $trainingID"); 
while($row_question = $result_questions->fetch_assoc()){
    $questionArray = array();
    $questionArray[] = array(
        "type"=>"html",
        "name"=>"question",
        "html"=>strip_questions($row_question["text"])
    );
    $choices = parse_questions($row_question["text"]);
    if(sizeof($choices) == 0){
        $choices =  array(array("value"=>0,"text"=>"Ich habe den Text gelesen"));
    }
    $questionArray[] = array(
        "type"=>"radiogroup",
        "name"=>$row_question["id"],
        "title"=>"Welche dieser Antworten ist richtig?",
        "isRequired"=>$onLogin == 'TRUE',
        "colCount"=>1,
        "choicesOrder"=>$random == 'TRUE'?"random":"none",
        "choices"=>$choices
    );
    $trainingArray[] = array(
        "name"=>$trainingID,
        "title"=>$row["name"],
        "elements"=>$questionArray,
    );
}

?>
    <script src='../plugins/node_modules/survey-jquery/survey.jquery.min.js'></script>

    <div class="modal fade survey-modal">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header">Bitte beantworten Sie folgende Fragen 
            <a data-toggle="modal" data-target="#explain-surveys"><i class="fa fa-question-circle-o"></i></a>
            <span id="timeElement"></span>
            </div>
            <div class="modal-body">
                <div id="surveyElement"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
            </div>
        </div>
    </div>

    <script>
        Survey.Survey.cssType = "bootstrap";
        Survey.defaultBootstrapCss.navigationButton = "btn btn-warning";
        var json = <?php echo json_encode(array(
            "pages"=> $trainingArray,
            "showProgressBar"=>"top",
            "requiredText"=>"(".$lang['REQUIRED_FIELD'].") ",
            "showPageNumbers"=>true,
            "locale"=>$lang['LOCALE']
        )) ?>;
        window.survey = new Survey.Model(json);
        survey
            .onComplete
            .add(function (result) {
                $("#timeElement").hide()
                clearInterval(timerID);
                $.ajax({
                    url: 'ajaxQuery/AJAX_validateTrainingSurvey.php',
                    data: { result: JSON.stringify(result.data),test:true },
                    type: 'post',
                    success: function (resp) {
                        $("#surveyElement").html(resp) //stats
                        $(".survey-modal .modal-footer").html('<button type="button" class="btn btn-default" data-dismiss="modal">OK</button>')
                        // $(".survey-modal").modal("hide");
                    },
                    error: function (resp) { 
                        $("#surveyElement").html(resp) //stats
                        $(".survey-modal .modal-footer").html('<button type="button" class="btn btn-default" data-dismiss="modal">OK</button>')
                        // $(".survey-modal").modal("hide");
                    }
                });
            });
            var timeElement = $("#timeElement");
            var timerID = null;
            function padZero(number){
                number = (number || '0')+"";
                return (number.length == 1)?("0"+number):number
            }
            function renderTime(seconds) {
                var hours = Math.floor(seconds / 3600);
                var minutes = Math.floor((seconds - (hours*3600)) / 60);
                seconds = Math.floor(seconds % 60);
                timeElement.html("Zeit auf der Seite: "+ padZero(hours) + ":" + padZero(minutes) + ":" + padZero(seconds));
            }
            function timerCallback() {
                var page = survey.currentPage;
                if(!page) return;
                var valueName = "training;" + page.name; // training id
                var seconds = survey.getValue(valueName);
                if(seconds == null) seconds = 0;
                else seconds ++;
                survey.setValue(valueName, seconds);
                renderTime(seconds)
            }
            survey.onCurrentPageChanged.add(timerCallback);
            timerID = window.setInterval(timerCallback, 1000);
            $("#surveyElement").Survey({ model: survey });
    </script>