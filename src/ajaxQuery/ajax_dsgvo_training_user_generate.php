<?php
session_start();
$userID = $_SESSION['userid'] or die("no user signed in");
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";
$onLogin = false;
$doneSurveys = false;
if (isset($_REQUEST["onLogin"])) {
    $onLogin = true;
}
if (isset($_REQUEST["done"])) {
    $doneSurveys = true;
}
if (isset($_REQUEST["test"])) {
    $test = true;
}
if ($test) {
    if (isset($_POST["questionID"])) {
        $questionID = intval($_POST["questionID"]);
    } else if (isset($_POST["trainingID"])) {
        $trainingID = intval($_POST["trainingID"]);
    } else {
        die("no training or question id");
    }
    if ($questionID) {
        $result = $conn->query("SELECT trainingID FROM dsgvo_training_questions WHERE id = $questionID");
        showError($conn->error);
        $trainingID = $result->fetch_assoc()["trainingID"];
        $extra = "AND dsgvo_training_questions.id = $questionID";
    } else {
        $extra = "";
    }
    $allowSuspension = false;
    $result = $conn->query("SELECT * FROM dsgvo_training WHERE id = $trainingID");
    $row = $result->fetch_assoc();
    $questionArray = array();
    $random = $row["random"];
    $onLogin = $row["onLogin"];
    $result_question = $conn->query("SELECT dsgvo_training_questions.id, text, title, survey, dsgvo_training.name tname, dsgvo_training_modules.name mname FROM dsgvo_training_questions INNER JOIN dsgvo_training ON dsgvo_training_questions.trainingID = dsgvo_training.id INNER JOIN dsgvo_training_modules ON dsgvo_training.moduleID = dsgvo_training_modules.id WHERE trainingID = $trainingID $extra");
} else {
    $result = $conn->query("SELECT userID FROM dsgvo_training_user_suspension WHERE userID = $userID AND suspension_count >= 3");
    $allowSuspension = !$result || $result->num_rows == 0;
    list($sql_error, $userHasUnansweredSurveys) = user_has_unanswered_surveys_query($userID);
    $error_output .= showError($sql_error);
    if (!$userHasUnansweredSurveys && !$doneSurveys) {
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
    $result = $conn->query( // this gets all trainings the user can complete
        "SELECT tur.trainingID id, tr.name, tr.random
         FROM dsgvo_training_user_relations tur
         INNER JOIN dsgvo_training tr
         ON tr.id = tur.trainingID
         WHERE tur.userID = $userID
         UNION
         SELECT ttr.trainingID id, tr.name, tr.random
         FROM dsgvo_training_team_relations ttr
         INNER JOIN relationship_team_user trd
         ON trd.teamID = ttr.teamID
         INNER JOIN dsgvo_training tr
         ON tr.id = ttr.trainingID
         WHERE trd.userID = $userID
         UNION
         SELECT ttr.trainingID id, tr.name, tr.random
         FROM dsgvo_training_company_relations ttr
         INNER JOIN relationship_company_client trd
         ON trd.companyID = ttr.companyID
         INNER JOIN dsgvo_training tr
         ON tr.id = ttr.trainingID
         WHERE trd.userID = $userID"
    );
    showError($conn->error);
    while ($row = $result->fetch_assoc()) {
        echo "<script>console.debug(`%c available training: ",print_r($row),"`, 'color: purple')</script>";
        $questionArray = array();
        $trainingID = $row["id"];
        $random = $row["random"];
        $result_question = false;
        if (!$doneSurveys) {
            $result_question = $conn->query(
                "SELECT tq.id, tq.text, t.onLogin, tq.title, tq.survey, t.name tname, dsgvo_training_modules.name mname FROM dsgvo_training_questions tq
                INNER JOIN dsgvo_training t ON t.id = tq.trainingID
                INNER JOIN dsgvo_training_modules ON t.moduleID = dsgvo_training_modules.id
                WHERE tq.trainingID = $trainingID AND
                NOT EXISTS (
                    SELECT userID
                    FROM dsgvo_training_completed_questions
                    LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                    LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                    WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
                )"
            ); // only select not completed questions
        } else {
            $result_question = $conn->query(
                "SELECT tq.id, tq.text, t.onLogin, tq.title, tq.survey, t.name tname, dsgvo_training_modules.name mname FROM dsgvo_training_questions tq
                INNER JOIN dsgvo_training t ON t.id = tq.trainingID
                INNER JOIN dsgvo_training_modules ON t.moduleID = dsgvo_training_modules.id
                WHERE tq.trainingID = $trainingID AND
                EXISTS (
                    SELECT userID
                    FROM dsgvo_training_completed_questions
                    LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                    LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                    WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 )
                )"
            ); //only select completed questions
        }
    }
}
showError($conn->error);
$trainingArray = array(); // those are the survey pages
while ($row_question = $result_question->fetch_assoc()) {
    echo "<script>console.debug(`%c available question: ",print_r($row_question),"`, 'color: green')</script>";    
    $trainingArray[] = array(
        "name" => $trainingID,
        "title" => str_ellipsis($row_question["title"], 30) . ($row_question["survey"] == 'TRUE'?" (Umfrage)":""),
        "elements" => generate_survey_page(
            [
                "text" => $row_question["text"],
                "id" => $row_question["id"],
                "required" => $test ? ($onLogin == 'TRUE') : ($row_question["onLogin"] == 'TRUE' && !$doneSurveys),
                "random" => $random,
                "survey" => $row_question["survey"] == 'TRUE',
                "category" => str_ellipsis($row_question["mname"]. " / ". $row_question["tname"], 35)
            ]
        ),
    );
}

?>
    <style>
    div.stretchy-wrapper {
        width: 100%;
        padding-bottom: 56.25%; /* 16:9 */
        position: relative;
        background-color: black;
    }

    div.stretchy-wrapper > iframe {
        position: absolute;
        top: 0; bottom: 0; left: 0; right: 0;
        width: 100%; height: 100%;
    }
    </style>
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
                <button data-toggle="modal" data-target="#explain-surveys" class="btn btn-default" type="button"><?php echo $lang['HELP'] ?></a>
                <?php if ($onLogin && $allowSuspension) : ?>
                  <button type="button" class="btn btn-default" id="suspend_trainings_btn"><?php echo $lang['POSTPONE_ONE_DAY'] ?></button>
                <?php endif; ?>
                <?php if (!$onLogin || $test) : ?>  <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button> <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        Survey.Survey.cssType = "bootstrap";
        Survey.defaultBootstrapCss.navigationButton = "btn btn-warning";
        var json = <?php echo json_encode(array(
                        "pages" => $trainingArray,
                        "showTitle" => true,
                        "title" => $trainingArray[0]["elements"][1]["category"],
                        "showProgressBar" => "top",
                        "requiredText" => "(" . $lang['REQUIRED_FIELD'] . ") ",
                        "showPageNumbers" => true,
                        "showQuestionNumbers" => "off",
                        "locale" => $lang['LOCALE']
                    )) ?>;
        Survey.JsonObject.metaData.addProperty("questionbase", "category");
        window.survey = new Survey.Model(json);
        survey
            .onComplete
            .add(function (result) {
                $("#timeElement").hide()
                clearInterval(timerID);
                <?php if ($test) : ?>
                var data = { result: JSON.stringify(result.data), test: true };
                <?php else : ?>
                var data = { result: JSON.stringify(result.data) };
                <?php endif; ?>
                $.ajax({
                    url: 'ajaxQuery/ajax_dsgvo_training_user_submit.php',
                    data: data,
                    type: 'post',
                    success: function (resp) {
                        $("#surveyElement").html(resp) //stats
                        $(".survey-modal .modal-footer").html('<button type="button" class="btn btn-default" data-dismiss="modal">OK</button>')
                    },
                    error: function (resp) {
                        $("#surveyElement").html(resp) //stats
                        $(".survey-modal .modal-footer").html('<button type="button" class="btn btn-default" data-dismiss="modal">OK</button>')
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
            survey.onCurrentPageChanged.add(function(){
                survey.title = survey.getCurrentPageQuestions()[1].category;
            });
            survey.onCurrentPageChanged.add(timerCallback);
            clearInterval(timerID);
            timerID = window.setInterval(timerCallback, 1000);
            $("#surveyElement").Survey({ model: survey });
            $("#suspend_trainings_btn").click(function(){
                $.ajax({
                    url: 'ajaxQuery/ajax_dsgvo_training_user_submit.php',
                    data: { suspend: "true" },
                    type: 'post',
                    success: function (resp) {
                        showSuccess(resp)
                        $(".survey-modal").modal("hide");
                    },
                    error: function (resp) {
                        showError(resp)
                        $(".survey-modal").modal("hide");
                    }
                });
            })
            $(".sv_next_btn").addClass("pull-right");
            $(".sv_complete_btn").addClass("pull-right");
            $(".sv_next_btn").parent().addClass("clearfix");
    </script>
    <script>
        function setLinkTargets(){
            $("#surveyElement a").attr("target","_blank");
        }
    </script>



<div id="explain-surveys" class="modal fade">
  <div class="modal-dialog modal-content modal-sm">
    <div class="modal-header h4"><?php echo $lang['TRAINING'] ?></div>
    <div class="modal-body">
        <?php echo $lang['TRAINING_HELP'] ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
  </div>
</div>
