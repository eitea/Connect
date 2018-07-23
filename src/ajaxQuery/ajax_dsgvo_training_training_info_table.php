<?php
session_start();
if (!isset($_REQUEST["trainingID"])) {
    echo "error";
    die();
}
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "connection.php";
require dirname(__DIR__) . DIRECTORY_SEPARATOR . "language.php";

function formatPercent($num)
{
    $num = round($num * 1000) / 10;
    return "$num%";
}
function formatTime($num, $noAnswers = false)
{
    global $lang;
    if($noAnswers) return "N/A";
    return "$num ".$lang['SECONDS'];
}
function getColor($percent, $inverse = false, $noAnswers = false)
{
    if($noAnswers) return "#e2e2e2";
    switch (($inverse ? 10 - round($percent * 10) : round($percent * 10))) {
        case 10:
            return "#00FF00";
        case 9:
            return "#66FF00";
        case 8:
            return "#3FFF00";
        case 7:
            return "#7FFF00";
        case 6:
            return "#BFFF00";
        case 5:
            return "#FFF700";
        case 4:
            return "#FFBF00";
        case 3:
            return "#FFA500";
        case 2:
            return "#FF4500";
        case 1:
            return "#FF2400";
        case 0:
            return "#FF0000";
        default:
            return "#FF0000";
    }
}

$trainingID = $_REQUEST["trainingID"];
$row = $conn->query("SELECT * FROM dsgvo_training WHERE id = $trainingID")->fetch_assoc();
$name = $row["name"];
$version = $row["version"];
$companyID = $row["companyID"];
$onLogin = $row["onLogin"];

$result_user = $conn->query(
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
echo $conn->error;
$result = $conn->query("SELECT count(*) count
FROM dsgvo_training_questions
WHERE dsgvo_training_questions.trainingID = $trainingID");
$total = intval($result->fetch_assoc()["count"]);
?>
 <form method="POST">
 <div class="modal fade">
      <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header"><?php echo $lang['RESULT_OF'] ?> <?php echo $name ?></div>
        <div class="modal-body">
            <?php if ($total != 0) : ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th><?php echo $lang['TRAINING_QUESTION_CORRECT']['TRUE'] ?></th>
                        <th>% <?php echo $lang['TRAINING_QUESTION_CORRECT']['TRUE'] ?></th>
                        <th><?php echo $lang['TRAINING_QUESTION_CORRECT']['FALSE'] ?></td>
                        <th>% <?php echo $lang['TRAINING_QUESTION_CORRECT']['FALSE'] ?></td>
                        <th><?php echo $lang['TRAINING_QUESTION_CORRECT']['UNANSWERED'] ?></td>
                        <th>% <?php echo $lang['TRAINING_QUESTION_CORRECT']['UNANSWERED'] ?></td>
                        <th><?php echo $lang['TOTAL_TIME'] ?></td>
                        <th><?php echo $lang['TIME_PER_QUESTION'] ?></td>
                    </tr>
                </thead>
                <tbody>
<?php
while ($row = $result_user->fetch_assoc()) {
    $user = $row["userID"];
    $name = $row["firstname"] . " " . $row["lastname"];
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
    $unanswered = $total - $right - $wrong;
    $percentRight = ($right / $total);
    $percentWrong = ($wrong / $total);
    $percentUnanswered = ($unanswered / $total);
    $result = $conn->query("SELECT sum(duration) duration
    FROM dsgvo_training_questions
    INNER JOIN dsgvo_training_completed_questions ON dsgvo_training_completed_questions.questionID = dsgvo_training_questions.id
    WHERE userID = $user AND dsgvo_training_questions.trainingID = $trainingID");
    echo $conn->error;
    $time = intval($result->fetch_assoc()["duration"]);
    if($total - $unanswered == 0){
        $timePerQuestion = 0;
        $noAnswers = true;
    }else{
        $timePerQuestion = round($time / ($total - $unanswered));
        $noAnswers = false;
    }
    echo "<tr>";
    echo "<td>$user</td>";
    echo "<td>$name</td>";
    echo "<td style='background-color:" . getColor($percentRight, false, $noAnswers) . ";'>$right</td>";
    echo "<td style='background-color:" . getColor($percentRight, false, $noAnswers) . ";'>" . formatPercent($percentRight) . "</td>";
    echo "<td style='background-color:" . getColor($percentWrong, true, $noAnswers) . ";'>$wrong</td>";
    echo "<td style='background-color:" . getColor($percentWrong, true, $noAnswers) . ";'>" . formatPercent($percentWrong) . "</td>";
    echo "<td style='background-color:" . getColor($percentUnanswered, true, $noAnswers) . ";'>$unanswered</td>";
    echo "<td style='background-color:" . getColor($percentUnanswered, true, $noAnswers) . ";'>" . formatPercent($percentUnanswered) . "</td>";
    echo "<td>" . formatTime($time, $noAnswers) . "</td>";
    echo "<td>" . formatTime($timePerQuestion, $noAnswers) . "</td>";
    echo "</tr>";
}
?>
                </tbody>
            </table>
            <?php else : ?>
                Noch keine Daten vorhanden
            <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
</form>
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
