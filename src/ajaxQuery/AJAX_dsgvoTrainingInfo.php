<?php
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
$pointsRightArray = array();
$pointsWrongArray = array();
$pointsUnansweredArray = array();
$rightColors = array();
$wrongColors = array();
$unansweredColors = array();
$rightColorsOutline = array();
$wrongColorsOutline = array();
$unansweredColorsOutline = array();
foreach ($userArray as $user) {
    $user = $user["id"];
    $result = $conn->query("SELECT count(*) count
    FROM dsgvo_training_questions
    INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
    WHERE userID = $user AND correct = 'TRUE' AND dsgvo_training_questions.trainingID = $trainingID");
    echo $conn->error;
    $right = intval($result->fetch_assoc()["count"]);
    $result = $conn->query("SELECT count(*) count
    FROM dsgvo_training_questions
    INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
    WHERE userID = $user AND correct = 'FALSE' AND dsgvo_training_questions.trainingID = $trainingID");
    $wrong = intval($result->fetch_assoc()["count"]);
    $unanswered = $numberOfQuestions - $right - $wrong;
    $pointsRightArray[] = $right;
    $pointsWrongArray[] = $wrong;
    $pointsUnansweredArray[] = $unanswered;
    $rightColorsOutline[] = "rgb(50, 173, 22)";
    $wrongColorsOutline[] = "rgb(244, 92, 65)";
    $unansweredColorsOutline[] = "rgb(153, 153, 153)";
    $rightColors[] = "rgba(50, 173, 22, 0.2)";
    $wrongColors[] = "rgba(244, 92, 65, 0.2)";
    $unansweredColors[] = "rgba(153, 153, 153, 0.2)";
}
?>
<script src="plugins/chartsjs/Chart.min.js"></script>
 <form method="POST">
 <div class="modal fade">
      <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header">Auswertung von <?php echo $name ?></div>
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
            data: <?php echo json_encode($pointsRightArray) ?>,
            backgroundColor: <?php echo json_encode($rightColors) ?>,
            borderColor: <?php echo json_encode($rightColorsOutline) ?>,
            borderWidth: 1
        },{
            label: "<?=$lang['TRAINING_QUESTION_CORRECT']['FALSE']?>",
            data: <?php echo json_encode($pointsWrongArray) ?>,
            backgroundColor: <?php echo json_encode($wrongColors) ?>,
            borderColor: <?php echo json_encode($wrongColorsOutline) ?>,
            borderWidth: 1
        },{
            label: "<?=$lang['TRAINING_QUESTION_CORRECT']['UNANSWERED']?>",
            data: <?php echo json_encode($pointsUnansweredArray) ?>,
            backgroundColor: <?php echo json_encode($unansweredColors) ?>,
            borderColor: <?php echo json_encode($unansweredColorsOutline) ?>,
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
          <button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
        </div>
      </div>
    </div>
</form>
