<?php
session_start();
$userID = $_SESSION['userid'];
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

"SELECT * FROM dsgvo_training_user_relations tur INNER JOIN dsgvo_training tr ON tr.id = tur.trainingID WHERE tur.userID = 1"

//this is currently only one survey
$result = $conn->query(
    "SELECT tur.trainingID, tr.name FROM dsgvo_training_user_relations tur 
    INNER JOIN dsgvo_training_team_relations ttr 
    ON tur.trainingID = ttr.trainingID 
    INNER JOIN teamRelationshipData trd 
    ON trd.teamID = ttr.teamID 
    INNER JOIN dsgvo_training tr 
    ON tr.id = tur.trainingID 
    WHERE trd.userID = $userID OR tur.userID = $userID"
);
$trainingArray = array(); // a training contains many questions
while ($row = $result->fetch_assoc()){
    $trainingArray[] = array("id"=>$row["trainingID"],"name"=>$row["name"]);
}
var_dump($trainingArray);
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

    var json = {
        questions: [
            {
                type: "html",
                name: "info",
                html: "<table><body><row><td><img src='/Content/Images/examples/26178-20160417.jpg' width='100px' /></td><td style='padding:20px'>You may put here any html code. For example images, <b>text</b> or <a href='https://surveyjs.io/Survey/Builder'  target='_blank'>links</a></td></row></body></table>"
            },
            {
                type: "radiogroup",
                name: "car",
                title: "What car are you driving?",
                isRequired: true,
                colCount: 1,
                choices: [
                    "None",
                    "Ford",
                    "Vauxhall",
                    "Volkswagen"
                ]
            }
        ]
    };

    window.survey = new Survey.Model(json);

    survey
        .onComplete
        .add(function (result) {
            alert("result: " + JSON.stringify(result.data));
        });


    $("#surveyElement").Survey({ model: survey });
</script>