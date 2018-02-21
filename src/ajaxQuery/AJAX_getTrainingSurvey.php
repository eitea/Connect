<?php
session_start();
$userID = $_SESSION['userid'] or die("no user signed in");
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";
$onLogin = false;
$doneSurveys = false;
if(isset($_REQUEST["onLogin"])){
    $onLogin = true;
}
if(isset($_REQUEST["done"])){
    $doneSurveys = true;
}

$result = $conn->query(
    "SELECT count(*) count FROM (
        SELECT userID FROM dsgvo_training_user_relations tur LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = tur.trainingID WHERE userID = $userID AND NOT EXISTS (
             SELECT userID 
             FROM dsgvo_training_completed_questions 
             WHERE questionID = tq.id AND userID = $userID
         )
        UNION 
        SELECT tr.userID userID FROM dsgvo_training_team_relations dtr INNER JOIN teamRelationshipData tr ON tr.teamID = dtr.teamID LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = dtr.trainingID WHERE tr.userID = $userID AND NOT EXISTS (
             SELECT userID 
             FROM dsgvo_training_completed_questions 
             WHERE questionID = tq.id AND userID = $userID
         )
    ) temp"
);
echo $conn->error;
$userHasUnansweredSurveys = intval($result->fetch_assoc()["count"]) !== 0;

if(!$userHasUnansweredSurveys && !$doneSurveys){
    ?>
        <div class="modal fade survey-modal">
            <div class="modal-dialog modal-content modal-md">
                <div class="modal-header">Sie haben keine offenen Fragen mehr</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    <?php
    die();
}

function strip_questions($html){ // this will be the question
    $regexp = '/\{.*?\}/s';
    return preg_replace($regexp, "", $html);
}

function parse_questions($html){ // this will return an array of questions
    $questionRegex = '/\{.*?\}/s';
    $htmlRegex = '/\<\/*.+?\/*\>/s';
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
    return $ret_array;
}

$result = $conn->query( // this gets all trainings the user can complete
    "SELECT tur.trainingID id, tr.name, tr.random
     FROM dsgvo_training_user_relations tur 
     INNER JOIN dsgvo_training tr 
     ON tr.id = tur.trainingID 
     WHERE tur.userID = $userID
     UNION
     SELECT ttr.trainingID id, tr.name, tr.random
     FROM dsgvo_training_team_relations ttr 
     INNER JOIN teamRelationshipData trd 
     ON trd.teamID = ttr.teamID  
     INNER JOIN dsgvo_training tr 
     ON tr.id = ttr.trainingID 
     WHERE trd.userID = $userID"
);
$trainingArray = array(); // those are the survey pages
while ($row = $result->fetch_assoc()){
    $questionArray = array();
    $trainingID = $row["id"];
    $random = $row["random"];
    $result_question = false;
    if(!$doneSurveys){
        $result_question = $conn->query(
            "SELECT tq.id, tq.text, t.onLogin FROM dsgvo_training_questions tq 
            INNER JOIN dsgvo_training t ON t.id = tq.trainingID
            WHERE tq.trainingID = $trainingID AND 
            NOT EXISTS (
                SELECT userID 
                FROM dsgvo_training_completed_questions 
                WHERE questionID = tq.id AND userID = $userID
            )"
        ); // only select not completed questions
    }else{
        $result_question = $conn->query(
            "SELECT tq.id, tq.text, t.onLogin FROM dsgvo_training_questions tq 
            INNER JOIN dsgvo_training t ON t.id = tq.trainingID
            WHERE tq.trainingID = $trainingID AND 
            EXISTS (
                SELECT userID 
                FROM dsgvo_training_completed_questions 
                WHERE questionID = tq.id AND userID = $userID
            )"
        ); //only select completed questions
    }
    while($row_question = $result_question->fetch_assoc()){
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
            "isRequired"=>($row_question["onLogin"] == 'TRUE' && !$doneSurveys),
            "colCount"=>1,
            "choicesOrder"=>$random == 'TRUE'?"random":"none",
            "choices"=>$choices
        );
    }
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
                <button data-toggle="modal" data-target="#explain-surveys" class="btn btn-default" type="button">Hilfe</a>
                <?php if(!$onLogin): ?>  <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button> <?php endif; ?>
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
                    data: { result: JSON.stringify(result.data) },
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




<div id="explain-surveys" class="modal fade">
  <div class="modal-dialog modal-content modal-sm">
    <div class="modal-header h4">Trainings</div>
    <div class="modal-body">
        <div>
            Jede dieser Seiten stellt ein Set von Fragen dar. 
            Jede Frage, die nicht mit (Pflichtfeld) markiert ist, ist optional und kann einfach übersprungen werden.
            Übersprungene Fragen können später jederzeit nachgeholt werden. 
        </div><br/>
        <div>
            Fragen können beliebig oft wiederholt werden, aber der Administrator kann auswählen, ob diese den vorhergehenden Versuch überschreiben.
        </div><br/>
        <div>
            Die Auswertung der Fragen erfolgt am Schluss, wobei die Anzahl der richtigen und falschen Fragen ersichtlich ist.
        </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
  </div>
</div>