<?php 
if (!isset($_REQUEST["questionID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

$questionID = $_REQUEST["questionID"];
$result = $conn->query(
    "SELECT userID, correct, firstname, lastname, tcq.version
     FROM dsgvo_training_questions tq 
     INNER JOIN dsgvo_training_completed_questions tcq ON tcq.questionID = tq.id 
     INNER JOIN UserData ON UserData.id = tcq.userID
     WHERE tq.id = $questionID"
);
echo $conn->error;
?>
<form method="POST">
<div class="modal fade">
    <div class="modal-dialog modal-content modal-md">
    <div class="modal-header">Statistik</div>
    <div class="modal-body">
        <?php
        if(!$result || $result->num_rows == 0){
            echo "no data yet";
        }else{
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Correct</th>
                    <th>Version</th>
                </tr>
            </thead>
            <tbody>
       <?php 
            $right = $wrong = $unanswered = 0;
            while($row = $result->fetch_assoc()){
                $name = "${row['firstname']} ${row['lastname']}";
                $correct = $lang['TRAINING_QUESTION_CORRECT'][$row['correct']];
                if($row['correct'] == 'TRUE'){
                    $right++;
                }else{
                    $wrong++;
                }
                echo "<tr>";
                echo "<td>$name</td>";
                echo "<td>$correct</td>";
                echo "<td>${row['version']}</td>";
                echo "</tr>";
            }
            $result = $conn->query(
                "SELECT ud.id, firstname, lastname
                 FROM dsgvo_training_questions tq 
                 INNER JOIN UserData ud
                 WHERE tq.id = $questionID AND NOT EXISTS (SELECT questionID FROM dsgvo_training_completed_questions WHERE userID = ud.id AND questionID = tq.id)"
            );
            while($row = $result->fetch_assoc()){
                $name = "${row['firstname']} ${row['lastname']}";
                $unanswered++;
                $correct = $lang['TRAINING_QUESTION_CORRECT']['UNANSWERED'];
                echo "<tr>";
                echo "<td>$name</td>";
                echo "<td>$correct</td>";
                echo "<td>$correct</td>";
                echo "</tr>";
            }
       ?>
            </tbody>
       </table>
        <?php } ?>

<script src="plugins/chartsjs/Chart.min.js"></script>
<canvas id="myChart" width="600" height="300"></canvas>
<script>
var ctx = document.getElementById("myChart").getContext('2d');
var myChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [
                    <?php echo $right ?>,
                    <?php echo $wrong ?>,
                    <?php echo $unanswered ?>
                ],
                backgroundColor: [
                    "green","red","grey"
                ],
            }],
            labels: [
                "<?=$lang['TRAINING_QUESTION_CORRECT']['TRUE']?>","<?=$lang['TRAINING_QUESTION_CORRECT']['FALSE']?>","<?=$lang['TRAINING_QUESTION_CORRECT']['UNANSWERED']?>"
            ]
        },
        options: {
            responsive: true,
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Results'
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        }
    }
)
</script>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
    </div>
</div>
</form>