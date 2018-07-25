<?php
session_start();
if (!isset($_REQUEST["trainingID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";

$trainingID = $_REQUEST["trainingID"];
$row = $conn->query("SELECT * FROM dsgvo_training WHERE id = $trainingID")->fetch_assoc();
$name = $row["name"];
$version = $row["version"];
$companyID = $row["companyID"];
$onLogin = $row["onLogin"];

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
$numberOfQuestions = $conn->query("SELECT count(*) count FROM dsgvo_training_questions WHERE trainingID = $trainingID")->fetch_assoc()["count"];
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
    $user = $user["id"];
    $result = $conn->query("SELECT count(*) count
        FROM dsgvo_training_questions
        INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
        WHERE userID = $user AND correct = 'TRUE' AND survey = 'FALSE' AND dsgvo_training_questions.trainingID = $trainingID");
    echo $conn->error;
    $right = intval($result->fetch_assoc()["count"]);
    $result = $conn->query("SELECT count(*) count
        FROM dsgvo_training_questions
        INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
        WHERE userID = $user AND correct = 'FALSE' AND survey = 'FALSE' AND dsgvo_training_questions.trainingID = $trainingID");
    $wrong = intval($result->fetch_assoc()["count"]);
    $result = $conn->query("SELECT count(*) count
        FROM dsgvo_training_questions
        INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
        WHERE userID = $user AND survey = 'TRUE' AND dsgvo_training_questions.trainingID = $trainingID");
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
        <div class="modal-body">

        <canvas id="myChart" width="600" height="300"></canvas>
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
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
        </div>
      </div>
    </div>
</form>
