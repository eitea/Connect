<?php
session_start();
if (!isset($_REQUEST["trainingID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "dsgvo" . DIRECTORY_SEPARATOR . "dsgvo_training_common.php";

function formatPercent($num)
{
    $num = round($num * 1000) / 10;
    return "$num%";
}
function formatTime($num, $noAnswers = false)
{
    global $lang;
    if ($noAnswers) return "N/A";
    return "$num " . $lang['SECONDS'];
}

$trainingID = $_REQUEST["trainingID"];
$row = $conn->query("SELECT * FROM dsgvo_training WHERE id = $trainingID")->fetch_assoc();
$name = $row["name"];
$version = $row["version"];
$companyID = $row["companyID"];
$onLogin = $row["onLogin"];
$numberOfQuestions = $conn->query("SELECT count(*) count FROM dsgvo_training_questions WHERE trainingID = $trainingID")->fetch_assoc()["count"];


$userArray = array();
$result = $conn->query(
    "SELECT userID, firstname, lastname FROM dsgvo_training_user_relations
     INNER JOIN UserData ON UserData.id = dsgvo_training_user_relations.userID
     WHERE trainingID = $trainingID
     UNION
     SELECT relationship_team_user.userID, firstname, lastname
     FROM dsgvo_training_team_relations
     INNER JOIN relationship_team_user
     ON relationship_team_user.teamID = dsgvo_training_team_relations.teamID
     INNER JOIN UserData ON UserData.id = relationship_team_user.userID
     WHERE dsgvo_training_team_relations.trainingID = $trainingID
     UNION
     SELECT relationship_company_client.userID, firstname, lastname
     FROM dsgvo_training_company_relations
     INNER JOIN relationship_company_client
     ON relationship_company_client.companyID = dsgvo_training_company_relations.companyID
     INNER JOIN UserData ON UserData.id = relationship_company_client.userID
     WHERE dsgvo_training_company_relations.trainingID = $trainingID"
);
while($row = $result->fetch_assoc()){
    $userArray[] = array("id"=>$row["userID"],"name"=>$row["firstname"]." ".$row["lastname"]);
}
$nameArray = array();
foreach ($userArray as $user) {
    $nameArray[] = $user["name"];
}
$dataSet = [
    "count" => [
        "right" => [],
        "wrong" => [],
        "unanswered" => [],
        "survey" => []
    ],
    "color" => [
        "right" => [],
        "wrong" => [],
        "unanswered" => [],
        "survey" => []
    ],
    "outline" => [
        "right" => [],
        "wrong" => [],
        "unanswered" => [],
        "survey" => []
    ]
];
foreach ($userArray as $user) {
    $id = $user["id"];
    $result = $conn->query("SELECT count(*) count
        FROM dsgvo_training_questions
        INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
        WHERE userID = $id AND correct = 'TRUE' AND survey = 'FALSE' AND dsgvo_training_questions.trainingID = $trainingID");
    echo $conn->error;
    $right = intval($result->fetch_assoc()["count"]);
    $result = $conn->query("SELECT count(*) count
        FROM dsgvo_training_questions
        INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
        WHERE userID = $id AND correct = 'FALSE' AND survey = 'FALSE' AND dsgvo_training_questions.trainingID = $trainingID");
    $wrong = intval($result->fetch_assoc()["count"]);
    $result = $conn->query("SELECT count(*) count
        FROM dsgvo_training_questions
        INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
        WHERE userID = $id AND survey = 'TRUE' AND dsgvo_training_questions.trainingID = $trainingID");
    $survey = intval($result->fetch_assoc()["count"]);
    $unanswered = $numberOfQuestions - $right - $wrong - $survey;
    $dataSet["count"]["right"][] = $right;
    $dataSet["count"]["wrong"][] = $wrong;
    $dataSet["count"]["unanswered"][] = $unanswered;
    $dataSet["count"]["survey"][] = $survey;
    $dataSet["outline"]["right"][] ="rgb(50, 173, 22)";
    $dataSet["outline"]["wrong"][] ="rgb(244, 92, 65)";
    $dataSet["outline"]["unanswered"][] ="rgb(153, 153, 153)";
    $dataSet["outline"]["survey"][] ="rgb(22, 77, 173)";
    $dataSet["color"]["right"][] ="rgba(50, 173, 22, 0.2)";
    $dataSet["color"]["wrong"][] ="rgba(244, 92, 65, 0.2)";
    $dataSet["color"]["unanswered"][] ="rgba(153, 153, 153, 0.2)";
    $dataSet["color"]["survey"][] ="rgba(22, 77, 173, 0.2)";
}
?>
<script src="plugins/chartsjs/Chart.min.js"></script>
 <form method="POST">
 <div class="modal fade">
      <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header"><?php echo $lang['RESULT_OF'] ?> <?php echo $name ?></div>
        <div class="modal-body" style="">
        <ul class="nav nav-tabs nav-justified">
   <li class="active"><a href="#training-results" data-toggle="tab">Zusammenfassung</a></li>
   <li><a href="#training-table" data-toggle="tab">Tabelle</a></li>
   <li><a href="#training-questions" data-toggle="tab">Beantwortete Fragen</a></li>
</ul>

<div class="tab-content">
   <div class="tab-pane fade in active" id="training-results"><canvas id="myChart" width="600" height="300"></canvas></div>
   <div class="tab-pane fade" id="training-table"><?php if ($numberOfQuestions != 0) : ?>
            <table class="table table-hover text-center vertical-align" >
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th><?php echo $lang['TRAINING_QUESTION_CORRECT']['TRUE'] ?></th>
                        <th><?php echo $lang['TRAINING_QUESTION_CORRECT']['FALSE'] ?></th>
                        <th><?php echo $lang['TRAINING_QUESTION_CORRECT']['UNANSWERED'] ?></th>
                        <th><?php echo $lang['TRAINING_QUESTION_CORRECT']['SURVEY'] ?></th>
                        <th><?php echo $lang['TOTAL_TIME'] ?></th>
                        <th><?php echo $lang['TIME_PER_QUESTION'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($userArray as $user) {
                        $id = $user["id"];
                        $name = $user["name"];
                        $result = $conn->query("SELECT count(*) count
                            FROM dsgvo_training_questions
                            INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
                            WHERE userID = $id AND correct = 'TRUE' AND survey = 'FALSE' AND dsgvo_training_questions.trainingID = $trainingID");
                        echo $conn->error;
                        $right = intval($result->fetch_assoc()["count"]);
                        $result = $conn->query("SELECT count(*) count
                            FROM dsgvo_training_questions
                            INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
                            WHERE userID = $id AND correct = 'FALSE' AND survey = 'FALSE' AND dsgvo_training_questions.trainingID = $trainingID");
                        $wrong = intval($result->fetch_assoc()["count"]);
                        $result = $conn->query("SELECT count(*) count
                            FROM dsgvo_training_questions
                            INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
                            WHERE userID = $id AND survey = 'TRUE' AND dsgvo_training_questions.trainingID = $trainingID");
                        $survey = intval($result->fetch_assoc()["count"]);
                        $unanswered = $numberOfQuestions - $right - $wrong - $survey;
                        $base = ($numberOfQuestions - $survey);
                        $percentRight = ($right / ($base?$base:1));
                        $percentWrong = ($wrong / ($base?$base:1));
                        $percentUnanswered = ($unanswered / $numberOfQuestions);
                        $result = $conn->query("SELECT sum(duration) duration
                        FROM dsgvo_training_questions
                        INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
                        WHERE userID = $id AND dsgvo_training_questions.trainingID = $trainingID");
                        echo $conn->error;
                        $time = intval($result->fetch_assoc()["duration"]);
                        if ($numberOfQuestions - $unanswered == 0) {
                            $timePerQuestion = 0;
                            $noAnswers = true;
                        } else {
                            $timePerQuestion = round($time / ($numberOfQuestions - $unanswered));
                            $noAnswers = false;
                        }
                        echo "<tr>";
                        echo "<td>$id</td>";
                        echo "<td>$name</td>";
                        echo "<td style='background-color:" . percentage_to_color($percentRight, false, $noAnswers || $base == 0) . ";'>$right (".formatPercent($percentRight). ")</td>";
                        echo "<td style='background-color:" . percentage_to_color($percentWrong, true, $noAnswers || $base == 0) . ";'>$wrong (".formatPercent($percentWrong). ")</td>";
                        echo "<td style='background-color:" . percentage_to_color($percentUnanswered, true, $noAnswers) . ";'>$unanswered (".formatPercent($percentUnanswered). ")</td>";
                        echo "<td style='background-color:#5086e5;'>" . $survey . "</td>";
                        echo "<td>" . formatTime($time, $noAnswers) . "</td>";
                        echo "<td>" . formatTime($timePerQuestion, $noAnswers) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <?php else : ?>
                Noch keine Daten vorhanden
            <?php endif; ?></div>
            <div class="tab-pane fade" id="training-questions">
                <?php 
                    $result = $conn->query("SELECT title, survey, id, version FROM dsgvo_training_questions WHERE trainingID = $trainingID");
                    if($result){
                        ?>
                            <table class="table table-hover vertical-align" >
                                <thead>
                                    <tr>
                                        <th>Titel</th>
                                        <th>Anzahl der Antworten</th>
                                        <th>Version</th>
                                        <th><?php echo $lang['TRAINING_QUESTION_CORRECT']['SURVEY'] ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                while($result && ($row = $result->fetch_assoc())){
                                    $title = $row["title"];
                                    $survey = $lang[$row["survey"] == 'TRUE'?'YES':'NO'];
                                    $version = $row["version"];
                                    $questionID = $row["id"];
                                    $total = count($userArray);
                                    $percentage = $total != 0? "(".formatPercent($answers/$total).")":"";
                                    
                                    $answer_result = $conn->query("SELECT count(*) answers FROM dsgvo_training_completed_questions WHERE questionID = $questionID");
                                    echo $conn->error;
                                    $answers =$answer_result?$answer_result->fetch_assoc()["answers"]:0;

                                    echo "<tr style='background-color:" . percentage_to_color($answers/($total?$total:1)) . "'>";
                                    echo "<td>$title</td>";
                                    echo "<td>$answers/$total $percentage</td>";
                                    echo "<td>$version</td>";
                                    echo "<td>$survey</td>";
                                    echo "</tr>";
                                }
                                ?>
                                </tbody>
                            </table>
                        <?php
                    }else{
                        echo "No data";
                    }
                ?>
            </div>
</div>
        
        
<script>
var ctx = document.getElementById("myChart").getContext('2d');
var myChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($nameArray) ?>,
        datasets: [{
            label: "<?=$lang['TRAINING_QUESTION_CORRECT']['TRUE']?>",
            data: <?php echo json_encode($dataSet["count"]["right"]) ?>,
            backgroundColor: <?php echo json_encode($dataSet["color"]["right"]) ?>,
            borderColor: <?php echo json_encode($dataSet["outline"]["right"]) ?>,
            borderWidth: 1
        },{
            label: "<?=$lang['TRAINING_QUESTION_CORRECT']['FALSE']?>",
            data: <?php echo json_encode($dataSet["count"]["wrong"]) ?>,
            backgroundColor: <?php echo json_encode($dataSet["color"]["wrong"]) ?>,
            borderColor: <?php echo json_encode($dataSet["outline"]["wrong"]) ?>,
            borderWidth: 1
        },{
            label: "<?=$lang['TRAINING_QUESTION_CORRECT']['SURVEY']?>",
            data: <?php echo json_encode($dataSet["count"]["survey"]) ?>,
            backgroundColor: <?php echo json_encode($dataSet["color"]["survey"]) ?>,
            borderColor: <?php echo json_encode($dataSet["outline"]["survey"]) ?>,
            borderWidth: 1
        },{
            label: "<?=$lang['TRAINING_QUESTION_CORRECT']['UNANSWERED']?>",
            data: <?php echo json_encode($dataSet["count"]["unanswered"]) ?>,
            backgroundColor: <?php echo json_encode($dataSet["color"]["unanswered"]) ?>,
            borderColor: <?php echo json_encode($dataSet["outline"]["unanswered"]) ?>,
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            xAxes: [{
                stacked: true,ticks: {
                    beginAtZero:true
                }
            }],
            yAxes: [{
                stacked: true
            }]
        },
        tooltips: {
            mode: 'nearest'
        }
    }
});
</script>
<script>
$(function(){
    $('.table').DataTable({
        ordering: true,
        language: { <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?> },
        responsive: true,
        dom: 'tf',
        autoWidth: false,
        fixedHeader: {
            header: true,
            headerOffset: 50,
            zTop: 1
        },
        paging: false
    });
})

</script>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
        </div>
      </div>
    </div>
</form>
