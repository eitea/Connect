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

// CREATE TABLE dsgvo_training_completed_questions (
//     questionID int(6),
//     userID INT(6) UNSIGNED,
//     correct ENUM('TRUE', 'FALSE') DEFAULT 'FALSE',
//     PRIMARY KEY (questionID, userID),
//     FOREIGN KEY (questionID) REFERENCES dsgvo_training_questions(id) ON UPDATE CASCADE ON DELETE CASCADE,
//     FOREIGN KEY (userID) REFERENCES UserData(id) ON UPDATE CASCADE ON DELETE CASCADE
// )

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
echo "right:$right, wrong:$wrong";
?>
