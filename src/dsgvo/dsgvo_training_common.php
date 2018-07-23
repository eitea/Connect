<?php

/****************************************************************************
 * Common variables, queries and methods for everything related to training *
 ****************************************************************************/

/**
 * Test if surveys are suspended for today.
 * 
 * @param string|int $userID The user id
 * @return array [string $error, boolean suspended]
 */
function surveys_are_suspended_query($userID): array {
    global $conn;
    static $cached_result = 0; // 0 == no cached result
    if($cached_result !== 0){
        return array("", $cached_result);
    }
    $result = $conn->query("SELECT suspension_count FROM dsgvo_training_user_suspension WHERE userID = $userID AND TIMESTAMPDIFF(DAY, last_suspension, CURRENT_TIMESTAMP) = 0");
    if(!$result || $conn->error){
        $cached_result = 0;
        return array($conn->error, false);
    }
    $cached_result = $result && $result->num_rows != 0;
    return array("", $cached_result);
}

/**
 * Test if user is assigned to any survey.
 * 
 * @param string|int $userID The user id
 * @return array [string $error, int $count]
 */
function user_has_surveys_query($userID): array {
    global $conn;
    static $cached_result = false;
    if($cached_result !== false){
        return array("", $cached_result);
    }
    $result = $conn->query(
        "SELECT count(*) count FROM (
            SELECT userID FROM dsgvo_training_user_relations tur LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = tur.trainingID WHERE userID = $userID
            UNION
            SELECT tr.userID userID FROM dsgvo_training_team_relations dtr INNER JOIN relationship_team_user tr ON tr.teamID = dtr.teamID
            LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = dtr.trainingID WHERE tr.userID = $userID
            UNION
            SELECT relationship_company_client.userID userID FROM dsgvo_training_company_relations INNER JOIN relationship_company_client ON relationship_company_client.companyID = dsgvo_training_company_relations.companyID
            LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.trainingID = dsgvo_training_company_relations.trainingID WHERE relationship_company_client.userID = $userID
        ) temp"
    );
    if(!$result || $conn->error){
        $cached_result = false;
        return array($conn->error, 0);
    }
    $cached_result = intval($result->fetch_assoc()["count"]);
    return array("", $cached_result);
}

/**
 * Test if any survey hasn't been answered by the user yet.
 * 
 * @param string|int $userID The user id
 * @return array [string $error, int $count]
 */
function user_has_unanswered_surveys_query($userID): array {
    global $conn;
    static $cached_result = false;
    if($cached_result !== false){
        return array("", $cached_result);
    }
    list($sql_error, $userHasSurveys) = user_has_surveys_query($userID); // don't execute long query when we already know that the user doesn't have any surveys
    if($sql_error){
        $cached_result = false;
        return array($sql_error, 0);
    }
    if(!$userHasSurveys){
        return array("", 0);
    }
    $result = $conn->query(
        "SELECT count(*) count FROM (
            SELECT userID FROM dsgvo_training_user_relations tur
            LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = tur.trainingID
            WHERE userID = $userID
            AND NOT EXISTS (
                 SELECT userID
                 FROM dsgvo_training_completed_questions
                 LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                 LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                 WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
                 )
            UNION
            SELECT tr.userID userID FROM dsgvo_training_team_relations dtr
            INNER JOIN relationship_team_user tr ON tr.teamID = dtr.teamID
            LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = dtr.trainingID
            WHERE tr.userID = $userID
            AND NOT EXISTS (
                 SELECT userID
                 FROM dsgvo_training_completed_questions
                 LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                 LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                 WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
                 )
            UNION
            SELECT relationship_company_client.userID userID FROM dsgvo_training_company_relations
                INNER JOIN relationship_company_client ON relationship_company_client.companyID = dsgvo_training_company_relations.companyID
                LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = dsgvo_training_company_relations.trainingID
                WHERE relationship_company_client.userID = $userID
                AND NOT EXISTS (
                    SELECT userID
                    FROM dsgvo_training_completed_questions
                    LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                    LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                    WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
                )
        ) temp"
    );
    if(!$result || $conn->error){
        $cached_result = false;
        return array($conn->error, 0);
    }
    $cached_result = intval($result->fetch_assoc()["count"]);
    return array("", $cached_result);
}

/**
 * Test if any survey that needs to be answered after login hasn't been answered by the user yet.
 * 
 * @param string|int $userID The user id
 * @return array [string $error, int $count]
 */
function user_has_unanswered_on_login_surveys_query($userID): array {
    global $conn;
    static $cached_result = false;
    if($cached_result !== false){
        return array("", $cached_result);
    }
    list($sql_error, $userHasUnansweredSurveys) = user_has_unanswered_surveys_query($userID); // don't execute long query when we already know that the user doesn't have any surveys
    if($sql_error){
        $cached_result = false;
        return array($sql_error, 0);
    }
    if(!$userHasUnansweredSurveys){
        return array("", 0);
    }
    $result = $conn->query(
        "SELECT count(*) count FROM (
            SELECT userID FROM dsgvo_training_user_relations tur
            INNER JOIN dsgvo_training t ON t.id = tur.trainingID
            LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = tur.trainingID
            WHERE userID = $userID AND onLogin = 'TRUE' AND NOT EXISTS (
                SELECT userID
                FROM dsgvo_training_completed_questions
                LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
             )
            UNION
            SELECT tr.userID userID FROM dsgvo_training_team_relations dtr
            INNER JOIN relationship_team_user tr ON tr.teamID = dtr.teamID
            INNER JOIN dsgvo_training t ON t.id = dtr.trainingID
            LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = dtr.trainingID
            WHERE tr.userID = $userID AND onLogin = 'TRUE' AND NOT EXISTS (
                SELECT userID
                FROM dsgvo_training_completed_questions
                LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
             )
            UNION
            SELECT relationship_company_client.userID userID FROM dsgvo_training_company_relations
            INNER JOIN relationship_company_client ON relationship_company_client.companyID = dsgvo_training_company_relations.companyID
            INNER JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_company_relations.trainingID
            LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = dsgvo_training_company_relations.trainingID
            WHERE relationship_company_client.userID = $userID
            AND dsgvo_training.onLogin = 'TRUE' AND NOT EXISTS (
                SELECT userID
                FROM dsgvo_training_completed_questions
                LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
            )
        ) temp"
    );
    if(!$result || $conn->error){
        $cached_result = false;
        return array($conn->error, 0);
    }
    $cached_result = intval($result->fetch_assoc()["count"]);
    return array("", $cached_result);
}

/**
 * Choices to use when user didn't specify any.
 */
$defaultAnswerChoices = array(array("value"=>0,"text"=>"Ich habe den Text gelesen"));

/**
 * Removes questions (enclosed in '{}') from html.
 */
function strip_questions(string $html){
    $regexp = '/\{.*?\}/s';
    return preg_replace($regexp, "", $html);
}

/**
 * Removes html and returns an array of answers for survey.js
 */
function parse_question_answers(string $html){ // this will return an array of questions
    global $defaultAnswerChoices;
    $questionRegex = '/\{.*?\}/s';
    $htmlRegex = '/\<\/*.+?\/*\>/s';
    $html = preg_replace($htmlRegex,"",$html); // strip all html tags
    preg_match($questionRegex,$html,$matches);
    // only parse the first question
    if(sizeof($matches)==0) return $defaultAnswerChoices;
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    $answerRegex = '/\[([+-])\]([^\[\}]+)/s';
    preg_match_all($answerRegex,$question,$matches);
    if(sizeof($matches)==0) return $defaultAnswerChoices;
    $ret_array = array();
    foreach ($matches[2] as $key => $value) {
        $ret_array[] = array("value"=>$key,"text"=>html_entity_decode($value));
    }
    if(sizeof($ret_array) == 0){
        return $defaultAnswerChoices;
    }
    return $ret_array;
}

/**
 * Removes html and returns the question title for survey.js
 */
function parse_question_title($html){
    $questionRegex = '/\{.*?\}/s';
    $htmlRegex = '/\<\/*.+?\/*\>/s';
    $html = preg_replace($htmlRegex,"",$html); // strip all html tags
    preg_match($questionRegex,$html,$matches);
    // only parse the first question
    if(sizeof($matches)==0) return "Welche dieser Antworten ist richtig?";
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    $answerRegex = '/\[([\?])\]([^\[\}]+)/s';
    preg_match_all($answerRegex,$question,$matches);
    if(sizeof($matches)==0) return "Welche dieser Antworten ist richtig?";
    foreach ($matches[2] as $key => $value) {
        return html_entity_decode($value);
    }
    return "Welche dieser Antworten ist richtig?";
}

?>