<?php
session_start();
if (!isset($_REQUEST["questionID"])) {
    echo "error";
    die();
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "utilities.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";

$questionID = intval($_REQUEST["questionID"]);
$result = $conn->query("SELECT title, survey FROM dsgvo_training_questions WHERE id = $questionID");
$row = $result->fetch_assoc();
$title = $row["title"];
$survey = $row["survey"] == 'TRUE';

$select_survey_answers_stmt = $conn->prepare("SELECT identifier from dsgvo_training_completed_questions_survey_answers WHERE questionID = $questionID AND userID = ?");
$select_survey_answers_stmt->bind_param("i", $user);

$result = $conn->query(
    "SELECT userID, correct, firstname, lastname, tcq.version, tries, tcq.duration, tcq.lastAnswered
     FROM dsgvo_training_questions tq
     INNER JOIN dsgvo_training_completed_questions tcq ON tcq.questionID = tq.id
     INNER JOIN UserData ON UserData.id = tcq.userID
     WHERE tq.id = $questionID"
);
echo $conn->error;
$nameArray = array();
$triesArray = array();
$timesArray = array();
$triesColorsArray = array();
$timesColorsArray = array();

?>
<form method="POST">
<div class="modal fade">
    <div class="modal-dialog modal-content modal-lg">
    <div class="modal-header"><?php echo $lang['RESULT_OF'] ?> <?php echo $title ?></div>
    <div class="modal-body" style="overflow:scroll;">
        <?php
        if (!$result || $result->num_rows == 0) {
            echo "Noch keine Daten vorhanden";
        } else {
            ?>
            <ul class="nav nav-tabs nav-justified" role="tablist">
                <li role="presentation" class="active"><a href="#question-results" aria-controls="question-results" role="tab" data-toggle="tab">Zusammenfassung</a></li>
                <li role="presentation"><a href="#question-table" aria-controls="question-table" role="tab" data-toggle="tab">Tabelle</a></li>
                <li role="presentation"><a href="#question-time-and-tries" aria-controls="question-time-and-tries" role="tab" data-toggle="tab">Zeit und Versuche</a></li>
            </ul>
            <div class="tab-content">
            <div role="tabpanel" class="tab-pane fade in active" id="question-results"><canvas id="myChart" width="600" height="300"></canvas></div>
                <div role="tabpanel" class="tab-pane fade" id="question-table">
                <table class="table">
            <thead>
                <tr>
                    <th><?php echo $lang['USERS'] ?></th>
                    <th><?php echo $lang['ANSWER'] ?></th>
                    <th>Version</th>
                    <th><?php echo $lang['TRIES'] ?></th>
                    <th><?php echo $lang['LAST_ANSWERED'] ?></th>
                </tr>
            </thead>
            <tbody>
       <?php
        $right = $wrong = $unanswered = 0;
        $answer_count = [];
        while ($row = $result->fetch_assoc()) {
            $name = "${row['firstname']} ${row['lastname']}";
            $user = $row["userID"];
            $correct = $lang['TRAINING_QUESTION_CORRECT'][$row['correct']];
            $tries = $row['tries'];
            $version = $row['version'];
            $lastAnswered = $row["lastAnswered"];
            if ($row['correct'] == 'TRUE') {
                $right++;
            } else {
                $wrong++;
            }
            $nameArray[] = $name;
            $triesArray[] = $tries;
            $timesArray[] = $row["duration"];
            $triesColorsArray[] = "orange";
            $timesColorsArray[] = "blue";
            $user_survey_answers = [];
            echo "<tr>";
            echo "<td>$name</td>";
            if ($survey) {
                $select_survey_answers_stmt->execute();
                showError($select_survey_answers_stmt->error);
                $answer_result = $select_survey_answers_stmt->get_result();
                while ($answer_row = $answer_result->fetch_assoc()) {
                    $user_survey_answers[] = $answer_row["identifier"];
                    if (isset($answer_count[$answer_row["identifier"]])) {
                        $answer_count[$answer_row["identifier"]]++;
                    } else {
                        $answer_count[$answer_row["identifier"]] = 1;
                    }
                }
                $answer_result->free();
                echo "<td>" . implode(", ", $user_survey_answers) . "</td>";
            } else {
                echo "<td>$correct</td>";
            }
            echo "<td>$version</td>";
            echo "<td>$tries</td>";
            echo "<td>$lastAnswered</td>";
            echo "</tr>";
        }

        //users who didn't answer yet
        $result = $conn->query(
            "SELECT dsgvo_training_user_relations.userID, ud.firstname, ud.lastname FROM dsgvo_training_user_relations
                 LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.trainingID = dsgvo_training_user_relations.trainingID
                 INNER JOIN UserData ud ON ud.id = dsgvo_training_user_relations.userID
                 WHERE dsgvo_training_questions.id = $questionID AND NOT EXISTS (SELECT questionID FROM dsgvo_training_completed_questions WHERE userID = ud.id AND questionID = $questionID)
                 UNION
                 SELECT relationship_team_user.userID, ud.firstname, ud.lastname FROM dsgvo_training_team_relations
                 INNER JOIN relationship_team_user ON relationship_team_user.teamID = dsgvo_training_team_relations.teamID
                 LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.trainingID = dsgvo_training_team_relations.trainingID
                 INNER JOIN UserData ud ON ud.id = relationship_team_user.userID
                 WHERE dsgvo_training_questions.id = $questionID AND NOT EXISTS (SELECT questionID FROM dsgvo_training_completed_questions WHERE userID = ud.id AND questionID = $questionID)
                 UNION
                 SELECT relationship_company_client.userID, firstname, lastname
                 FROM dsgvo_training_company_relations
                 INNER JOIN relationship_company_client
                 ON relationship_company_client.companyID = dsgvo_training_company_relations.companyID
                 LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.trainingID = dsgvo_training_company_relations.trainingID
                 INNER JOIN UserData ud ON ud.id = relationship_company_client.userID
                 WHERE dsgvo_training_questions.id = $questionID AND NOT EXISTS (SELECT questionID FROM dsgvo_training_completed_questions WHERE userID = ud.id AND questionID = $questionID)
                 "
        );
        echo $conn->error;
        while ($row = $result->fetch_assoc()) {
            $name = "${row['firstname']} ${row['lastname']}";
            $unanswered++;
            $correct = $lang['TRAINING_QUESTION_CORRECT']['UNANSWERED'];
            $nameArray[] = $name;
            $triesArray[] = 0;
            $timesArray[] = 0;
            $triesColorsArray[] = "orange";
            $timesColorsArray[] = "blue";
            echo "<tr>";
            echo "<td>$name</td>";
            echo "<td>$correct</td>";
            echo "<td>$correct</td>";
            echo "<td>$correct</td>";
            echo "<td>$correct</td>";
            echo "</tr>";
        }
        ?>
            </tbody>
       </table>
                </div>
                <div role="tabpanel" class="tab-pane fade" id="question-time-and-tries"><canvas id="triesChart" width="600" height="300"></canvas></div>
            </div>


<script src="plugins/chartsjs/Chart.min.js"></script>

<?php 
if ($survey) {
    $labels = [];
    $backgroundColors = [];
    $dataSet = [];
    arsort($answer_count);
    foreach ($answer_count as $answer => $count) {
        $labels[] = $answer;
        $backgroundColors[] = str_to_hsl_color($answer);
        $dataSet[] = $count;
    }
} else {
    $backgroundColors = ["green", "red", "grey"];
    $dataSet = [$right, $wrong, $unanswered];
    $labels = [$lang['TRAINING_QUESTION_CORRECT']['TRUE'], $lang['TRAINING_QUESTION_CORRECT']['FALSE'], $lang['TRAINING_QUESTION_CORRECT']['UNANSWERED']];
}
$labels = json_encode($labels);
$backgroundColors = json_encode($backgroundColors);
$dataSet = json_encode($dataSet);
?>
<script>
var ctx = document.getElementById("myChart").getContext('2d');
var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: <?php echo $dataSet ?>,
                backgroundColor: <?php echo $backgroundColors ?>,
            }],
            labels: <?php echo $labels ?>
        },
        options: {
            responsive: true,
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: '<?php echo $lang['RESULT'] ?>'
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    }
)
</script>
<script>
var ctx = document.getElementById("triesChart").getContext('2d');
var triesChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($nameArray) ?>,
        datasets: [{
            label: "<?php echo $lang['TRIES'] ?>",
            data: <?php echo json_encode($triesArray) ?>,
            backgroundColor: <?php echo json_encode($triesColorsArray) ?>,
            yAxisID: 'first-y-axis'
        },{
            label: "<?php echo $lang['ESTIMATED_TIME_SECONDS'] ?>",
            data: <?php echo json_encode($timesArray) ?>,
            backgroundColor: <?php echo json_encode($timesColorsArray) ?>,
            yAxisID: 'second-y-axis'
        }]
    },
    options: {
        scales: {
            xAxes: [
                {
                    stacked: false,
                    ticks: {
                        beginAtZero: true
                    },
                    gridLines: {
                        color: "rgba(0, 0, 0, 0)",
                    }
                }
            ],
            yAxes: [
                {
                    id: 'first-y-axis',
                    stacked: false,
                    position: 'left',
                    ticks: {
                        beginAtZero: true,
                        display:false
                    },
                    gridLines: {
                        display:false
                    }
                },
                {
                    id: 'second-y-axis',
                    stacked: false,
                    position: 'right',
                    ticks: {
                        beginAtZero: true,
                        display:false
                    }
                }
            ]
        },
        tooltips: {
            mode: 'nearest'
        }
    }
});
</script>
<?php 
} ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
    </div>
</div>
</form>
