<?php
session_start();
$userID = $_SESSION['userid'];
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

function strip_questions($html){ // this will be the html type for the survey
    $regexp = '/\{.*?\}/';
    return preg_replace($regexp, "", $html);
}
function parse_questions($html){ // this will return an array of questions
    $questionRegex = '/\{.*?\}/';
    $htmlRegex = '/<\/*\w+\/*>/';
    $html = preg_replace($htmlRegex,"",$html); // strip all html tags
    preg_match($questionRegex,$html,$matches);
    // I only parse the first question for now
    if(sizeof($matches)==0) return array();
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    $answerRegex = '/\[([+-])\]([^\[\}]+)/';
    preg_match_all($answerRegex,$question,$matches);
    if(sizeof($matches)==0) return array();
    $ret_array = array();
    foreach ($matches[2] as $key => $value) {
        $ret_array[] = array("value"=>$key,"text"=>$value);
    }
    return $ret_array;
}

$result = $conn->query( // this gets all trainings the user can complete
    "SELECT tur.trainingID id, tr.name 
     FROM dsgvo_training_user_relations tur 
     INNER JOIN dsgvo_training tr 
     ON tr.id = tur.trainingID 
     WHERE tur.userID = $userID
     UNION
     SELECT ttr.trainingID id, tr.name 
     FROM dsgvo_training_team_relations ttr 
     INNER JOIN teamRelationshipData trd 
     ON trd.teamID = ttr.teamID  
     INNER JOIN dsgvo_training tr 
     ON tr.id = ttr.trainingID 
     WHERE trd.userID = $userID"
);
$trainingArray = array(); // a training contains many questions
$questionArray = array(); // could maybe be allocated in loop and added to trainingArray
while ($row = $result->fetch_assoc()){
    $trainingID = $row["id"];
    $result_question = $conn->query(
        "SELECT * FROM dsgvo_training_questions tq 
         WHERE tq.trainingID = $trainingID AND 
         NOT EXISTS (
             SELECT userID 
             FROM dsgvo_training_completed_questions 
             WHERE questionID = tq.id AND userID = $userID
         )"
    ); // only select not completed questions
    while($row_question = $result_question->fetch_assoc()){
        $questionArray[] = array(
            "type"=>"html",
            "name"=>"question",
            "html"=>strip_questions($row_question["text"])
        );
        $questionArray[] = array(
            "type"=>"radiogroup",
            "name"=>$row_question["id"],
            "title"=>"Welche dieser Antworten ist richtig?",
            "isRequired"=>true,
            "colCount"=>1,
            "choices"=>parse_questions($row_question["text"])
        );
    }
    $trainingArray[] = array("id"=>$row["id"],"name"=>$row["name"]);
}
?>
    <script src='../plugins/node_modules/survey-jquery/survey.jquery.min.js'></script>

    <div class="modal fade">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header">Bitte beantworten Sie folgende Fragen</div>
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

        var json = <?php echo json_encode(array("questions"=> $questionArray)) ?>;

        // var json = { // example
        //     questions: [
        //         {
        //             type: "html",
        //             name: "info",
        //             html: "<table><body><row><td><img src='/Content/Images/examples/26178-20160417.jpg' width='100px' /></td><td style='padding:20px'>You may put here any html code. For example images, <b>text</b> or <a href='https://surveyjs.io/Survey/Builder'  target='_blank'>links</a></td></row></body></table>"
        //         },
        //         {
        //             type: "radiogroup",
        //             name: "car",
        //             title: "What car are you driving?",
        //             isRequired: true,
        //             colCount: 1,
        //             choices: [
        //                 "None",
        //                 "Ford",
        //                 "Vauxhall",
        //                 "Volkswagen"
        //             ]
        //         }
        //     ]
        // };

        window.survey = new Survey.Model(json);

        survey
            .onComplete
            .add(function (result) {
                $.ajax({
                    url: 'ajaxQuery/AJAX_validateTrainingSurvey.php',
                    data: { result: JSON.stringify(result.data) },
                    type: 'post',
                    success: function (resp) {
                        // $("#currentSurveyModal").html(resp);
                        alert(resp);
                    },
                    error: function (resp) { 
                        alert(resp);
                    }
                });
                // alert("result: " + JSON.stringify(result.data));
            });


        $("#surveyElement").Survey({ model: survey });
    </script>