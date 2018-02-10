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
    if(sizeof($matches)==0) return array();
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    $answerRegex = '/\[([+-])\]([^\[\}]+)/s';
    preg_match_all($answerRegex,$question,$matches);
    if(sizeof($matches)==0) return array();
    if(!isset($matches[1][$answer])) return false;
    if($matches[1][$answer] == "+") return true;
    return false;
}

$right = $wrong = 0;
foreach ($result as $questionID => $answer) {
    $html = $conn->query("SELECT text FROM dsgvo_training_questions WHERE id = $questionID")->fetch_assoc()["text"];
    $questionRight = validate_questions($html, $answer);
    if($questionRight){
        $right++;
    }else{
        $wrong++;
    }
    $questionRight = $questionRight?"TRUE":"FALSE";
    $conn->query("INSERT INTO dsgvo_training_completed_questions (questionID,userID,correct) VALUES ($questionID, $userID, '$questionRight')");    
}
?>

<table class="table table-hover">
    <tr class="success">
        <th>Right</th>
        <td><?php echo $right ?></td>
    </tr>
    <tr class="danger">
        <th>Wrong</th>
        <td><?php echo $wrong ?></td>
    </tr>
</table>