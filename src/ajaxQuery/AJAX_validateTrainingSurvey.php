<?php
session_start();
isset($_POST["result"]) or die("no result");
isset($_SESSION["userid"]) or die("no user logged in");

require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";

$userID = $_SESSION['userid'];
$result = json_decode($_POST["result"]);

function validate_questions($html, $answer){ // this will true or false (will work with multiple right questions)
    $answer = intval($answer);
    $questionRegex = '/\{.*?\}/s';
    $htmlRegex = '/<\/*\w+\/*>/s';
    $html = preg_replace($htmlRegex,"",$html); // strip all html tags
    preg_match($questionRegex,$html,$matches);
    // I only parse the first question for now
    if(sizeof($matches)==0) return false;
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    $answerRegex = '/\[([+-])\]([^\[\}]+)/s';
    preg_match_all($answerRegex,$question,$matches);
    if(sizeof($matches)==0) return false;
    if(!isset($matches[1][$answer])) return false;
    if($matches[1][$answer] == "+") return true;
    return false;
}

$right = $wrong = $rightNoOverwrite = $wrongNoOverwrite = 0;
foreach ($result as $questionID => $answer) {
    $question_row = $conn->query("SELECT text,trainingID FROM dsgvo_training_questions WHERE id = $questionID")->fetch_assoc();
    $html = $question_row["text"];
    $trainingID = $question_row["trainingID"];
    $training_row = $conn->query("SELECT version,allowOverwrite FROM dsgvo_training WHERE id = $trainingID")->fetch_assoc();
    $version = $training_row["version"];
    $allowOverwrite = $training_row["allowOverwrite"] === "TRUE";
    $questionRight = validate_questions($html, $answer);
    $questionExists = $conn->query("SELECT questionID FROM dsgvo_training_completed_questions WHERE questionID = $questionID AND userID = $userID")->num_rows > 0;
    if($allowOverwrite || !$questionExists){
        if($questionRight){
            $right++;
        }else{
            $wrong++;
        }
        $questionRightQuery = $questionRight?"TRUE":"FALSE";
        $conn->query("INSERT INTO dsgvo_training_completed_questions (questionID,userID,correct,version) VALUES ($questionID, $userID, '$questionRightQuery',$version)
            ON DUPLICATE KEY UPDATE correct = '$questionRightQuery', version = $version");
    } else {
        if($questionRight){
            $rightNoOverwrite++;
        }else{
            $wrongNoOverwrite++;
        }
    }
}
?>

<script src="plugins/chartsjs/Chart.min.js"></script>
<canvas id="myChart" width="600" height="300"></canvas>
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
                    <?php echo $wrongNoOverwrite ?>
                ],
                backgroundColor: [
                    "green","rgb(127, 198, 135)","red","rgb(173, 109, 109)"
                ],
            }],
            labels: [
                "Right","Right, but not updated","Wrong","Wrong, but not updated"
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