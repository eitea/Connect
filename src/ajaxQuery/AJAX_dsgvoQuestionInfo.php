<?php 
if (!isset($_REQUEST["questionID"])){
    echo "error";
    die();
}
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

$questionID = $_REQUEST["questionID"];
$result = $conn->query(
    "SELECT userID, correct, firstname, lastname, tcq.version, tries, tcq.duration
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
    <div class="modal-dialog modal-content modal-md">
    <div class="modal-header">Statistik</div>
    <div class="modal-body">
        <?php
        if(!$result || $result->num_rows == 0){
            echo "Noch keine Daten vorhanden";
        }else{
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th><?php echo $lang['USERS']?></th>
                    <th><?php echo $lang['ANSWER']?></th>
                    <th>Version</th>
                    <th><?php echo $lang['TRIES']?></th>
                </tr>
            </thead>
            <tbody>
       <?php 
            $right = $wrong = $unanswered = 0;
            while($row = $result->fetch_assoc()){
                $name = "${row['firstname']} ${row['lastname']}";
                $correct = $lang['TRAINING_QUESTION_CORRECT'][$row['correct']];
                $tries = $row['tries'];
                $version = $row['version'];
                if($row['correct'] == 'TRUE'){
                    $right++;
                }else{
                    $wrong++;
                }
                $nameArray[] = $name;
                $triesArray[] = $tries;
                $timesArray[] = $row["duration"];
                $triesColorsArray[] = "orange";
                $timesColorsArray[] = "blue";
                echo "<tr>";
                echo "<td>$name</td>";
                echo "<td>$correct</td>";
                echo "<td>$version</td>";
                echo "<td>$tries</td>";
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
                echo "</tr>";
            }
       ?>
            </tbody>
       </table>

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
<canvas id="triesChart" width="600" height="300"></canvas>
<script>
var ctx = document.getElementById("triesChart").getContext('2d');
var triesChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($nameArray) ?>,
        datasets: [{
            label: "<?php echo $lang['TRIES']?>",
            data: <?php echo json_encode($triesArray) ?>,
            backgroundColor: <?php echo json_encode($triesColorsArray) ?>,
            yAxisID: 'first-y-axis'
        },{
            label: "Geschätzte Zeit in Sekunden",
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
<?php } ?>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
    </div>
    </div>
</div>
</form>