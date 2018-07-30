<?php

/****************************************************************************
 * Common variables, queries and methods for everything related to training *
 ****************************************************************************/

#region queries

/**
 * Test if surveys are suspended for today. (cached)
 * 
 * @param string|int $userID The user id
 * @return array [string $error, bool suspended]
 */
function surveys_are_suspended_query($userID) : array
{
    global $conn;
    static $cached_result = 0; // 0 == no cached result
    if ($cached_result !== 0) {
        return array("", $cached_result);
    }
    $result = $conn->query("SELECT suspension_count FROM dsgvo_training_user_suspension WHERE userID = $userID AND TIMESTAMPDIFF(DAY, last_suspension, CURRENT_TIMESTAMP) = 0");
    if (!$result || $conn->error) {
        $cached_result = 0;
        return array($conn->error, false);
    }
    $cached_result = $result && $result->num_rows != 0;
    return array("", $cached_result);
}

/**
 * Test if user is assigned to any survey. (cached)
 * 
 * @param string|int $userID The user id
 * @return array [string $error, int $count]
 */
function user_has_surveys_query($userID) : array
{
    global $conn;
    static $cached_result = false;
    if ($cached_result !== false) {
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
    if (!$result || $conn->error) {
        $cached_result = false;
        return array($conn->error, 0);
    }
    $cached_result = intval($result->fetch_assoc()["count"]);
    return array("", $cached_result);
}

/**
 * Test if any survey hasn't been answered by the user yet. (cached)
 * 
 * @param string|int $userID The user id
 * @return array [string $error, int $count]
 */
function user_has_unanswered_surveys_query($userID) : array
{
    global $conn;
    static $cached_result = false;
    if ($cached_result !== false) {
        return array("", $cached_result);
    }
    list($sql_error, $userHasSurveys) = user_has_surveys_query($userID); // don't execute long query when we already know that the user doesn't have any surveys
    if ($sql_error) {
        $cached_result = false;
        return array($sql_error, 0);
    }
    if (!$userHasSurveys) {
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
    if (!$result || $conn->error) {
        $cached_result = false;
        return array($conn->error, 0);
    }
    $cached_result = intval($result->fetch_assoc()["count"]);
    return array("", $cached_result);
}

/**
 * Test if any survey that needs to be answered after login hasn't been answered by the user yet. (cached)
 * 
 * @param string|int $userID The user id
 * @return array [string $error, int $count]
 */
function user_has_unanswered_on_login_surveys_query($userID) : array
{
    global $conn;
    static $cached_result = false;
    if ($cached_result !== false) {
        return array("", $cached_result);
    }
    list($sql_error, $userHasUnansweredSurveys) = user_has_unanswered_surveys_query($userID); // don't execute long query when we already know that the user doesn't have any surveys
    if ($sql_error) {
        $cached_result = false;
        return array($sql_error, 0);
    }
    if (!$userHasUnansweredSurveys) {
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
    if (!$result || $conn->error) {
        $cached_result = false;
        return array($conn->error, 0);
    }
    $cached_result = intval($result->fetch_assoc()["count"]);
    return array("", $cached_result);
}

#endregion queries

$default_answer_choices = array(array("value" => 0, "text" => "Ich habe den Text gelesen"));
$question_regex = '/\{.*?\}/s';
$html_regex = '/\<\/*.+?\/*\>/s';
$question_inner_regex = '/\[(\S+)\]([^\[\}]+)/s';

/**
 * Removes questions (enclosed in '{}') from html.
 */
function strip_questions(string $html) : string
{
    return preg_replace('/\{.*?\}/s', "", $html);
}

/**
 * Removes html and returns information needed for survey.js
 */
function parse_question(string $html, bool $survey) : array
{
    global $default_answer_choices;
    global $question_regex;
    global $html_regex;
    global $question_inner_regex;
    $title = $survey ? "Auswählen" : "Welche dieser Antworten ist richtig?";
    $html = preg_replace($html_regex, "", $html); // strip all html tags
    $question_type = "boolean";
    preg_match($question_regex, $html, $matches);
    // only parse the first question
    if (sizeof($matches) == 0) return [$default_answer_choices, $question_type, "Ich habe den Text gelesen", $title, []];
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    preg_match_all($question_inner_regex, $question, $matches);
    if (sizeof($matches) == 0) return [$default_answer_choices, $question_type, "Ich habe den Text gelesen", $title, []];
    $ret_array = array();
    $question_type = "radiogroup";
    $parsed = [];
    for ($i = 0; $i < count($matches[0]); $i++) {
        $operator = $matches[1][$i]; // + ... right, - ... wrong, ? ... question, # ... type, ...
        $value = trim($matches[2][$i]);
        $parsed[] = ["operator" => $operator, "value" => $value];
        if ($operator == "-" || $operator == "+") {
            $ret_array[] = array("value" => $i, "text" => html_entity_decode($value));
        } else if ($operator == "?") {
            $title = html_entity_decode($value);
        } else if ($operator == "#") {
            if ($survey) {
                if (in_array($value, ["radiogroup", "dropdown", "checkbox"])) {
                    $question_type = $value;
                }
            } else {
                if (in_array($value, ["radiogroup", "dropdown"])) { // checkbox is not allowed since it would allow multiple answers
                    $question_type = $value;
                }
            }
        } else if ($survey) { // either number or string (used to track changes when the text is modified)
            $ret_array[] = array("value" => $i, "text" => html_entity_decode($value));
        }
    }
    if (sizeof($ret_array) == 0) {
        $question_type = "boolean";
        return [$default_answer_choices, $question_type, "Ich habe den Text gelesen", $title, $parsed];
    }
    return [$ret_array, $question_type, "", $title, $parsed];
}

/**
 * Generates a page for survey.js (1 html + 1 question)
 * 
 * @see https://surveyjs.io/Examples/Library/
 */
function generate_survey_page(array $options) : array
{
    list($choices, $question_type, $label, $title) = parse_question($options["text"], $options["survey"]);
    $question = [
        "type" => $question_type,
        "name" => $options["id"],
        "title" => $title,
        "label" => $label,
        "isRequired" => $options["required"],
        "colCount" => 1,
        "choicesOrder" => $options["random"] == 'TRUE' ? "random" : "none",
        "choices" => $choices,
        "defaultValue" => "indeterminate",
        "category" => $options["category"]
    ];
    return [
        [
            "type" => "html",
            "name" => "question",
            "html" => strip_questions($options["text"])
        ],
        $question
    ];
}

/**
 * Checks if the user answered right
 */
function validate_question(string $html, $answer, bool $survey)
{
    global $question_regex;
    global $html_regex;
    global $question_inner_regex;
    $html = preg_replace($html_regex, "", $html); // strip all html tags
    preg_match($question_regex, $html, $matches);
    // only parse the first question
    if (sizeof($matches) == 0) return $survey ? ($answer?[true, ["read"]]:[false,["not read"]]) : $answer == true;
    $question = $matches[0]; // eg "{[-]wrong answer[+]right answer}"
    preg_match_all($question_inner_regex, $question, $matches);
    if (sizeof($matches) == 0) return $survey ? ($answer?[true, ["read"]]:[false,["not read"]]) : $answer == true;
    if ($survey) {
        $survey_answers = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $operator = $matches[1][$i]; // + ... right, - ... wrong, ? ... question, # ... type, ...
            if ($operator == "?" || $operator == "#" || $operator == "-" || $operator == "+") {
                // will be ignored
                continue;
            } else { // answer identifier
                if (is_array($answer)) { // checkboxes (multiple)
                    if (in_array($i, $answer)) {
                        $survey_answers[] = $operator;
                    }
                } else { // radiogroup, dropdown (single)
                    if ($i === intval($answer)) {
                        $survey_answers[] = $operator;
                    }
                }
            }
        }
        // return that user has read the question and answers
        if(!count($survey_answers)){
            $survey_answers[] = "read";
        }
        return [true, $survey_answers];
    } else {
        $has_answers = false;
        for ($i = 0; $i < count($matches[0]); $i++) {
            $operator = $matches[1][$i];
            if ($operator == "-" || $operator == "+") {
                $has_answers = true;
                continue;
            }
        }
        if($has_answers){
            if (!isset($matches[1][$answer])) return false;
            if ($matches[1][$answer] == "+") return true;
        }else{
            // 'I have read' checkbox
            return true;
        }
    }
}

/**
 * always returns the same color for the same string
 */
function str_to_hsl_color($str, $saturation = "75%", $luminosity = "50%")
{
    srand(crc32($str));
    $num = rand(0, 359);
    srand();
    return "hsl($num, $saturation, $luminosity)";
}

/**
 * get a color based on a percentage (0% ... red, 100% ... green)
 * @param float $percent Number between 0 and 1
 */
function percentage_to_color($percent, $inverse = false, $gray = false): string
{
    if ($gray) return "#e2e2e2";
    if ($inverse) $percent = 1 - $percent;
    $hue = $percent * 120;
    // hue 0 ... red
    // hue 120 ... green
    return "hsl($hue, 75%, 50%)";
}

/**
 * adds ... if string is too long
 */
function str_ellipsis(string $str, $len = 50){
   return strlen($str) > $len ? substr($str,0,$len)."..." : $str;
}

?>