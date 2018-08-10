<?php
session_start();
isset($_SESSION["userid"]) or die("no user logged in");
$userID = $_SESSION['userid'];

require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";

if (isset($_POST["suspend"])) {
    $result = $conn->query("SELECT suspension_count FROM dsgvo_training_user_suspension WHERE userID = $userID");
    if ($result && $result->num_rows == 0) {
        $allowSuspension = true;
        $suspensionLeft = 3;
    } else {
        $row = $result->fetch_assoc();
        $suspensionLeft = 3 - intval($row["suspension_count"]);
        $allowSuspension = intval($suspensionLeft) > 0;
    }
    if ($allowSuspension) {
        $suspensionLeft--;
        $conn->query("INSERT INTO dsgvo_training_user_suspension (suspension_count, userID) VALUES (1,$userID) ON DUPLICATE KEY UPDATE suspension_count = suspension_count + 1, last_suspension = CURRENT_TIMESTAMP");
        if ($conn->error) {
            echo $conn->error; // showError doesn't work here
        } else {
            switch ($suspensionLeft) {
                case 0:
                    echo "Erfolgreich aufgeschoben. Es sind keine weiteren Aufschiebungen erlaubt";
                    break;
                case 1:
                    echo "Erfolgreich aufgeschoben. Sie können noch 1 Aufschiebung durchführen";
                    break;
                default:
                    echo "Erfolgreich aufgeschoben. Sie können noch $suspensionLeft Aufschiebungen durchführen";
            }
        }
    } else {
        echo "Keine weiteren Aufschiebungen erlaubt";
    }
    die();
}

isset($_POST["result"]) or die("no result");
$result = json_decode($_POST["result"]);
$test = false; // test is true when a user submits a training with the play button
if (isset($_POST["test"]) && $_POST["test"] == true) {
    $test = true;
    $test_stats = [];
}

$select_training_id_stmt = $conn->prepare("SELECT trainingID FROM dsgvo_training_questions WHERE id = ?");
$select_training_id_stmt->bind_param("i", $questionID);
$select_training_stmt = $conn->prepare("SELECT version,allowOverwrite FROM dsgvo_training WHERE id = ?");
$select_training_stmt->bind_param("i", $trainingID);
$select_question_stmt = $conn->prepare("SELECT version,text,trainingID,survey,title from dsgvo_training_questions WHERE id = ?");
$select_question_stmt->bind_param("i", $questionID);
$select_completed_question_stmt = $conn->prepare("SELECT questionID FROM dsgvo_training_completed_questions WHERE questionID = ? AND userID = ?");
$select_completed_question_stmt->bind_param("ii", $questionID, $userID);
$insert_survey_answers = $conn->prepare("INSERT INTO dsgvo_training_completed_questions_survey_answers (questionID, identifier, userID) VALUES(?, ?, $userID) ON DUPLICATE KEY UPDATE identifier = ?");
$insert_survey_answers->bind_param("iss", $questionID, $identifier, $identifier);
$delete_survey_answers = $conn->prepare("DELETE FROM dsgvo_training_completed_questions_survey_answers WHERE questionID = ? AND userID = $userID");
$delete_survey_answers->bind_param("i", $questionID);

$times = array();
$numberOfAnsweredQuestions = array(); // per set (for average time)
foreach ($result as $formVal => $time) {
    $arr = explode(";", $formVal);
    if (sizeof($arr) == 2) {
        $times[intval($arr[1])] = intval($time);
    } else {
        $questionID = intval($formVal);
        $select_training_id_stmt->execute();
        showError($select_training_id_stmt->error);
        $training_id_result = $select_training_id_stmt->get_result();
        $training_id_row = $training_id_result->fetch_assoc();
        $trainingID = $training_id_row["trainingID"];
        $training_id_result->free();
        $numberOfAnsweredQuestions[$trainingID] = 1 + (isset($numberOfAnsweredQuestions[$trainingID]) ? $numberOfAnsweredQuestions[$trainingID] : 0);
    }
}

$right = $wrong = $rightNoOverwrite = $wrongNoOverwrite = $survey_data = $survey_data_no_overwrite = 0;
foreach ($result as $formVal => $answer) {
    $arr = explode(";", $formVal);
    if (sizeof($arr) == 2) { //formVal can contain "training;<id>" (for time) or "<id>" (for answer)
        continue;
    }
    $questionID = intval($formVal);
    $select_question_stmt->execute();
    showError($select_question_stmt->error);
    $question_result = $select_question_stmt->get_result();
    $question_row = $question_result->fetch_assoc();
    $html = $question_row["text"];
    $trainingID = $question_row["trainingID"];
    $select_training_stmt->execute();
    showError($select_training_stmt->error);
    $training_row_result = $select_training_stmt->get_result();
    $training_row = $training_row_result->fetch_assoc();
    $training_row = $training_row_result->fetch_assoc();
    $version = $question_row["version"];
    $survey = $question_row["survey"] == 'TRUE';
    $allowOverwrite = $training_row["allowOverwrite"] === "TRUE";
    if ($survey) {
        list($questionRight, $answers) = validate_question($html, $answer, $survey);
        if ($test) {
            $test_stats[] = ["title" => $question_row["title"], "answers" => $answers, "color" => "#5086e5"];
        }
    } else {
        $questionRight = validate_question($html, $answer, $survey);
        if ($test) {
            $test_stats[] = ["title" => $question_row["title"], "answers" => [$questionRight?"RIGHT":"WRONG"], "color" => percentage_to_color($questionRight?1:0)];
        }
    }
    $select_completed_question_stmt->execute();
    showError($select_completed_question_stmt->error);
    $question_exists_result = $select_completed_question_stmt->get_result();
    $questionExists = $result && $result->num_rows > 0;
    $time = 0;
    if (isset($times[$trainingID], $numberOfAnsweredQuestions[$trainingID])) {
        $time = round($times[$trainingID] / $numberOfAnsweredQuestions[$trainingID]);
    }
    if (($allowOverwrite || !$questionExists) && !$test) {
        if ($survey) {
            $survey_data++;
        } else if ($questionRight) {
            $right++;
        } else {
            $wrong++;
        }
        if (!$test) {
            $questionRightQuery = $questionRight ? "TRUE" : "FALSE";
            $conn->query("INSERT INTO dsgvo_training_completed_questions (questionID,userID,correct,version,duration,lastAnswered) VALUES ($questionID, $userID, '$questionRightQuery', $version, $time, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE correct = '$questionRightQuery', version = $version, tries = tries + 1, duration = $time, lastAnswered = CURRENT_TIMESTAMP");
            showError($conn->error);
            if ($survey) {
                $delete_survey_answers->execute();
                showError($delete_survey_answers->error);
                foreach ($answers as $identifier) {
                    $insert_survey_answers->execute();
                    showError($insert_survey_answers->error);
                }
            }
        }
    } else {
        if ($survey) {
            $survey_data_no_overwrite++;
        } else if ($questionRight) {
            $rightNoOverwrite++;
        } else {
            $wrongNoOverwrite++;
        }
    }
    $question_result->free();
    $training_row_result->free();
    $question_exists_result->free();
}

// since the user answered the survey, suspension can be reset
$conn->query("UPDATE dsgvo_training_user_suspension SET suspension_count = 0 WHERE userID = $userID");
?>

<script src="plugins/chartsjs/Chart.min.js"></script>
<?php if ($test) : ?>
 <ul class="nav nav-tabs nav-justified">
   <li class="active"><a href="#survey-results" data-toggle="tab">Zusammenfassung</a></li>
   <li><a href="#survey-answers" data-toggle="tab">Ihre Antworten</a></li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade in active" id="survey-results">
<?php endif; ?>
       <canvas id="myChart" width="600" height="300"></canvas>
<?php if ($test) : ?>
    </div>
    <div class="tab-pane fade" id="survey-answers">
        <table class="table table-hover vertical-align" >
            <thead>
                <tr>
                    <th>Frage</th>
                    <th>Antwort(en)</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($test_stats as $stat) {
                $title = $stat["title"];
                $answers = implode(", ", $stat["answers"]);
                $color = $stat["color"];
                echo "<tr style='background-color: $color'>";
                echo "<td>$title</td>";
                echo "<td>$answers</td>";
                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<script>
var ctx = document.getElementById("myChart").getContext('2d');
var myChart = new Chart(ctx, {
        type: 'pie',
        data: {
            datasets: [{
                data: [
                    <?php echo $right ?>,
                    <?php echo $rightNoOverwrite ?>,
                    <?php echo $wrong ?>,
                    <?php echo $wrongNoOverwrite ?>,
                    <?php echo $survey_data ?>,
                    <?php echo $survey_data_no_overwrite ?>
                ],
                backgroundColor: [
                    "green","rgb(127, 198, 135)","red","rgb(173, 109, 109)", "blue", "rgb(127, 164, 198)"
                ],
            }],
            labels: [
                "<?php echo $lang["TRAINING_RESULT"]["RIGHT"] ?>",
                "<?php echo $lang["TRAINING_RESULT"]["RIGHT_NO_UPDATE"] ?>",
                "<?php echo $lang["TRAINING_RESULT"]["WRONG"] ?>",
                "<?php echo $lang["TRAINING_RESULT"]["WRONG_NO_UPDATE"] ?>",
                "<?php echo $lang["TRAINING_RESULT"]["SURVEY"] ?>",
                "<?php echo $lang["TRAINING_RESULT"]["SURVEY_NO_UPDATE"] ?>"
            ]
        },
        options: {
            responsive: true,
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: '<?php echo $lang["RESULT"] ?>'
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    }
)
</script>