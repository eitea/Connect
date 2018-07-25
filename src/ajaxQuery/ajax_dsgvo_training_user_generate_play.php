<?php
session_start();
$userID = $_SESSION['userid'] or die("no user signed in");
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";
$onLogin = false;
$doneSurveys = false;
if(isset($_POST["questionID"])){
    $questionID = intval($_POST["questionID"]);
}else if (isset($_POST["trainingID"])){
    $trainingID = intval($_POST["trainingID"]);
}else{
    die("no training or question id");
}
if($questionID){
    $result = $conn->query("SELECT trainingID FROM dsgvo_training_questions WHERE id = $questionID");
    showError($conn->error);
    $trainingID = $result->fetch_assoc()["trainingID"];
    $extra = "AND id = $questionID";
}else{
    $extra = "";
}

$trainingArray = array();
$result = $conn->query("SELECT * FROM dsgvo_training WHERE id = $trainingID");
$row = $result->fetch_assoc();
$questionArray = array();
$random = $row["random"];
$onLogin = $row["onLogin"];
$result_questions = $conn->query("SELECT id, text, title, survey FROM dsgvo_training_questions WHERE trainingID = $trainingID $extra");
while ($row_question = $result_questions->fetch_assoc()) {
    $trainingArray[] = array(
        "name" => $trainingID, 
        "title" => $row["name"] . " - " . $row_question["title"],
        "elements" => generate_survey_page(
            [
                "text" => $row_question["text"],
                "id" => $row_question["id"],
                "required" => $onLogin == 'TRUE',
                "random" => $random,
                "survey" => $row_question["survey"] == 'TRUE'
            ]
        ),
    );
}

?>
    <script src='../plugins/node_modules/survey-jquery/survey.jquery.min.js'></script>

    <div class="modal fade survey-modal">
        <div class="modal-dialog modal-content modal-md">
            <div class="modal-header"><?php echo $lang['PLEASE_ANSWER_QUESTIONS'] ?> 
            <a data-toggle="modal" data-target="#explain-surveys"><i class="fa fa-question-circle-o"></i></a>
            <span id="timeElement"></span>
            </div>
            <div class="modal-body">
                <div id="surveyElement"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
            </div>
        </div>
    </div>

    <script>
        Survey.Survey.cssType = "bootstrap";
        Survey.defaultBootstrapCss.navigationButton = "btn btn-warning";
        var json = <?php echo json_encode(array(
                        "pages" => $trainingArray,
                        "showProgressBar" => "top",
                        "requiredText" => "(" . $lang['REQUIRED_FIELD'] . ") ",
                        "showPageNumbers" => true,
                        "showQuestionNumbers" => "off",
                        "locale" => $lang['LOCALE']
                    )) ?>;
        window.survey = new Survey.Model(json);
        survey
            .onComplete
            .add(function (result) {
                $("#timeElement").hide()
                clearInterval(timerID);
                $.ajax({
                    url: 'ajaxQuery/ajax_dsgvo_training_user_submit.php',
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
                timeElement.html("<?php echo $lang['TIME_ON_PAGE'] ?>: "+ padZero(hours) + ":" + padZero(minutes) + ":" + padZero(seconds));
            }
            function timerCallback() {
                setLinkTargets();
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
            clearInterval(timerID);
            timerID = window.setInterval(timerCallback, 1000);
            $("#surveyElement").Survey({ model: survey });
    </script>
    <script>
        function setLinkTargets(){
            $("#surveyElement a").attr("target","_blank");
        }
    </script>