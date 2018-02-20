<?php 
if (!isset($_REQUEST["trainingID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

function formatPercent($num){
    $num = round($num * 1000)/10;
    return "$num%";
}
function getColor($percent,$inverse = false){
    switch(($inverse?10-round($percent * 10):round($percent * 10))){
        case 10:return "#00FF00";
        case 9: return "#66FF00";
        case 8: return "#3FFF00";
        case 7: return "#7FFF00";
        case 6: return "#BFFF00";
        case 5: return "#FFF700";
        case 4: return "#FFBF00";
        case 3: return "#FFA500";
        case 2: return "#FF4500";
        case 1: return "#FF2400";
        case 0: return "#FF0000";
        default:return "#FF0000";
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
     SELECT teamRelationshipData.userID, firstname, lastname
     FROM dsgvo_training_team_relations 
     INNER JOIN teamRelationshipData 
     ON teamRelationshipData.teamID = dsgvo_training_team_relations.teamID
     INNER JOIN UserData ON UserData.id = teamRelationshipData.userID
     WHERE dsgvo_training_team_relations.trainingID = $trainingID"
);
$result = $conn->query("SELECT count(*) count
FROM dsgvo_training_questions
WHERE dsgvo_training_questions.trainingID = $trainingID");
$total = intval($result->fetch_assoc()["count"]);
?>
 <form method="POST">
 <div class="modal fade">
      <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header">Auswertung von <?php echo $name ?></div>
        <div class="modal-body">
            <?php if ($total != 0): ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Richtig</th>
                        <th>% Richtig</th>
                        <th>Falsch</td>
                        <th>% Falsch</td>
                        <th>Keine Antwort</td>
                        <th>% Keine Antwort</td>
                        <th>Gesamtzeit</td>
                        <th>Durchschnittliche Zeit pro Frage</td>
                    </tr>
                </thead>
                <tbody>
<?php
while($row = $result_user->fetch_assoc()){
    $user = $row["userID"];
    $name = $row["firstname"]." ".$row["lastname"];
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
    $timePerQuestion = round($time / ($total - $unanswered));
    echo "<tr>";
    echo "<td>$user</td>";
    echo "<td>$name</td>";
    echo "<td style='background-color:".getColor($percentRight).";'>$right</td>";
    echo "<td style='background-color:".getColor($percentRight).";'>".formatPercent($percentRight)."</td>";
    echo "<td style='background-color:".getColor($percentWrong,true).";'>$wrong</td>";
    echo "<td style='background-color:".getColor($percentWrong,true).";'>".formatPercent($percentWrong)."</td>";
    echo "<td style='background-color:".getColor($percentUnanswered,true).";'>$unanswered</td>";
    echo "<td style='background-color:".getColor($percentUnanswered,true).";'>".formatPercent($percentUnanswered)."</td>";
    echo "<td>$time Sekunden</td>";
    echo "<td>$timePerQuestion Sekunden</td>";
    echo "</tr>";
}
?>
                </tbody>
            </table>
            <?php else: ?>
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