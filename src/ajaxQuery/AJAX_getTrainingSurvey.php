<?php
session_start();
$userID = $_SESSION['userid'];
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

function strip_questions($html){ // this will be the html type for the survey
    $regexp = '/\{.*?\}/';
    return preg_replace($regexp, "", $html);
}
function parseQuestions($html){ // this will return an array of questions

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
    $result_question = $conn->query("SELECT * FROM dsgvo_training_questions WHERE trainingID = $trainingID");
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
            "choices"=>array(
                array("value"=>"1","text"=>"This is the first answer"),
                array("value"=>"2","text"=>"This is the second answer"),                
            )
        );
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
    }
    $trainingArray[] = array("id"=>$row["id"],"name"=>$row["name"]);
}
// var_dump($trainingArray);
// $questionArray[] = array("type"=>"radiogroup","name"=>"car","choices"=>array("none","one","two"),"title"=>"title","colCount"=>1);
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

    var json = <?php echo json_encode(array("questions"=>$questionArray)) ?>;

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
            alert("result: " + JSON.stringify(result.data));
        });


    $("#surveyElement").Survey({ model: survey });
</script>