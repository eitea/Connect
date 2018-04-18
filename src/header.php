<?php
session_start();
if (empty($_SESSION['userid'])) {
    die('Please <a href="../login/auth">login</a> first.');
}
$userID = $_SESSION['userid'];
$timeToUTC = $_SESSION['timeToUTC'];
$privateKey = $_SESSION['privateKey'];

$setActiveLink = 'class="active-link"';
$unlockedPGP = '';
require __DIR__ . "/connection.php";
require __DIR__ . "/utilities.php";
require __DIR__ . "/validate.php";
require __DIR__ . "/language.php";

$result = $conn->query("SELECT * FROM roles WHERE userID = $userID");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $isCoreAdmin = $row['isCoreAdmin'];
    $isTimeAdmin = $row['isTimeAdmin'];
    $isProjectAdmin = $row['isProjectAdmin'];
    $isReportAdmin = $row['isReportAdmin'];
    $isERPAdmin = $row['isERPAdmin'];
    $isFinanceAdmin = $row['isFinanceAdmin'];
    $isDSGVOAdmin = $row['isDSGVOAdmin'];
    $isDynamicProjectsAdmin = $row['isDynamicProjectsAdmin'];
    $canBook = $row['canBook'];
    $canStamp = $row['canStamp'];
    $canEditTemplates = $row['canEditTemplates'];
    $canUseSocialMedia = $row['canUseSocialMedia'];
    $canUseClients = $row['canUseClients'];
    $canEditClients = $row['canEditClients'];
    $canUseSuppliers = $row['canUseSuppliers'];
    $canEditSuppliers = $row['canEditSuppliers'];
    $canCreateTasks = $row['canCreateTasks'];
    $canUseArchive = $row['canUseArchive'];
    $canUseWorkflow = $row['canUseWorkflow']; //5ab7ae7596e5c
} else {
    $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = $isReportAdmin = $isERPAdmin = $isFinanceAdmin = $isDSGVOAdmin = $isDynamicProjectsAdmin = false;
    $canBook = $canStamp = $canEditTemplates = $canUseSocialMedia = $canCreateTasks  = $canUseSuppliers = $canUseClients = $canEditClients = false;
    $canEditSuppliers = $canUseArchive = $canUseWorkflow = false;
}
if ($userID == 1) { //superuser
    $isCoreAdmin = $isTimeAdmin = $isProjectAdmin = $isReportAdmin = $isERPAdmin = $isFinanceAdmin = $isDSGVOAdmin = $isDynamicProjectsAdmin = 'TRUE';
    $canStamp = $canBook = $canUseSocialMedia = $canCreateTasks  = $canUseClients = $canUseSuppliers = $canEditSuppliers = $canEditClients = $canUseArchive = $canUseWorkflow ='TRUE';
}
if($isERPAdmin == 'TRUE'){
    $canEditClients = $canEditSuppliers = 'TRUE';
}
$result = $conn->query("SELECT psw, lastPswChange, forcedPwdChange, publicPGPKey, birthday, displayBirthday FROM UserData WHERE id = $userID");
if($result && ($userdata = $result->fetch_assoc())) {
    $userPasswordHash = $userdata['psw'];
    $publicKey = $userdata['publicPGPKey'];
    $forcedPwdChange = ($userdata['forcedPwdChange'] === '1');
} else {
    echo $conn->error;
}
$result = $conn->query("SELECT enableReadyCheck, firstTimeWizard FROM configurationData");
if ($result) {
    $row = $result->fetch_assoc();
    if($row['firstTimeWizard'] == 'FALSE'){ redirect('../setup/wizard'); }
    $showReadyPlan = $row['enableReadyCheck'];
}
$result = $conn->query("SELECT id, CONCAT(firstname,' ', lastname) as name FROM UserData")->fetch_all(MYSQLI_ASSOC);
$userID_toName = array_combine( array_column($result, 'id'), array_column($result, 'name'));

$numberOfSocialAlerts = 0;
$result = $conn->query("SELECT userID FROM socialmessages WHERE seen = 'FALSE' AND partner = $userID "); echo $conn->error;
if($result) $numberOfSocialAlerts += $result->num_rows;
$result = $conn->query("SELECT seen FROM socialgroupmessages INNER JOIN socialgroups ON socialgroups.groupID = socialgroupmessages.groupID WHERE socialgroups.userID = '$userID' AND NOT ( seen LIKE '%,$userID,%' OR seen LIKE '$userID,%' OR seen LIKE '%,$userID' OR seen = '$userID')"); echo $conn->error;
if($result) $numberOfSocialAlerts += $result->num_rows;
if ($isTimeAdmin) {
    $numberOfAlerts = 0;
    //requests
    $result = $conn->query("SELECT id FROM $userRequests WHERE status = '0'");
    if ($result && $result->num_rows > 0) {$numberOfAlerts += $result->num_rows;}
    //forgotten checkouts
    $result = $conn->query("SELECT indexIM FROM logs WHERE (TIMESTAMPDIFF(MINUTE, time, timeEnd)/60) > 22 OR TIMESTAMPDIFF(MINUTE, time, timeEnd) < 0");
    if ($result && $result->num_rows > 0) {$numberOfAlerts += $result->num_rows;}
    //gemini date in logs
    $result = $conn->query("SELECT id FROM logs l1, $userTable WHERE l1.userID = $userTable.id AND EXISTS(SELECT * FROM logs l2 WHERE DATE(DATE_ADD(l1.time, INTERVAL timeToUTC  hour)) = DATE(DATE_ADD(l2.time, INTERVAL timeToUTC  hour)) AND l1.userID = l2.userID AND l1.indexIM != l2.indexIM) ORDER BY l1.time DESC");
    if ($result && $result->num_rows > 0) {$numberOfAlerts += $result->num_rows;}
    //lunchbreaks
    $result = $conn->query("SELECT l1.indexIM FROM logs l1
    INNER JOIN UserData ON l1.userID = UserData.id INNER JOIN intervalData ON UserData.id = intervalData.userID
    WHERE (status = '0' OR status ='5') AND endDate IS NULL AND timeEnd != '0000-00-00 00:00:00' AND TIMESTAMPDIFF(MINUTE, time, timeEnd) > (pauseAfterHours * 60)
    AND hoursOfRest * 60 > (SELECT IFNULL(SUM(TIMESTAMPDIFF(MINUTE, start, end)),0) as breakCredit FROM projectBookingData WHERE bookingType = 'break' AND timestampID = l1.indexIM)");
    if ($result && $result->num_rows > 0) {$numberOfAlerts += $result->num_rows;}
}
$result = $conn->query("SELECT DISTINCT companyID FROM relationship_company_client WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = $row['companyID'];
}
$result = $conn->query("SELECT DISTINCT userID FROM relationship_company_client WHERE companyID IN(" . implode(', ', $available_companies) . ") OR $userID = 1");
$available_users = array('-1');
while ($result && ($row = $result->fetch_assoc())) {
    $available_users[] = $row['userID'];
}
$validation_output = $error_output = '';
$result = $conn->query( /* Test if user has any questions */
    "SELECT count(*) count FROM (
        SELECT userID FROM dsgvo_training_user_relations tur LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = tur.trainingID WHERE userID = $userID
        UNION
        SELECT tr.userID userID FROM dsgvo_training_team_relations dtr INNER JOIN teamRelationshipData tr ON tr.teamID = dtr.teamID
        LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = dtr.trainingID WHERE tr.userID = $userID
        UNION
        SELECT relationship_company_client.userID userID FROM dsgvo_training_company_relations INNER JOIN relationship_company_client ON relationship_company_client.companyID = dsgvo_training_company_relations.companyID
        LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.trainingID = dsgvo_training_company_relations.trainingID WHERE relationship_company_client.userID = $userID
    ) temp"
);
$error_output .= showError($conn->error, 1);
$userHasUnansweredSurveys = $userHasSurveys = 0;
if($result) $userHasUnansweredSurveys = $userHasSurveys = intval($result->fetch_assoc()["count"]) !== 0;
if(!$userHasSurveys){
    $result = $conn->query( /* Test if user has unanswered questions to answer */
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
            INNER JOIN teamRelationshipData tr ON tr.teamID = dtr.teamID
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
            LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.trainingID = dsgvo_training_company_relations.trainingID 
            WHERE relationship_company_client.userID = $userID 
            AND NOT EXISTS (
                SELECT userID
                FROM dsgvo_training_completed_questions
                LEFT JOIN dsgvo_training_questions dtq ON dtq.id = dsgvo_training_completed_questions.questionID
                LEFT JOIN dsgvo_training ON dsgvo_training.id = dtq.trainingID
                WHERE questionID = dtq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
            )
        ) temp"
    );
    $error_output .= showError($conn->error, 1);
    if($result) $userHasSurveys = intval($result->fetch_assoc()["count"]) !== 0;
}
$userHasUnansweredOnLoginSurveys = false;
if($userHasUnansweredSurveys){ /* Test if user has unanswered questions that should be shown after checkin */
    $result = $conn->query(
        "SELECT count(*) count FROM (
            SELECT userID FROM dsgvo_training_user_relations tur INNER JOIN dsgvo_training t on t.id = tur.trainingID LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = tur.trainingID WHERE userID = $userID AND onLogin = 'TRUE' AND NOT EXISTS (
                SELECT userID
                FROM dsgvo_training_completed_questions
                LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
             )
            UNION
            SELECT tr.userID userID FROM dsgvo_training_team_relations dtr INNER JOIN teamRelationshipData tr ON tr.teamID = dtr.teamID INNER JOIN dsgvo_training t on t.id = dtr.trainingID LEFT JOIN dsgvo_training_questions tq ON tq.trainingID = dtr.trainingID WHERE tr.userID = $userID AND onLogin = 'TRUE' AND NOT EXISTS (
                SELECT userID
                FROM dsgvo_training_completed_questions
                LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.id = dsgvo_training_completed_questions.questionID
                LEFT JOIN dsgvo_training ON dsgvo_training.id = dsgvo_training_questions.trainingID
                WHERE questionID = tq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
             )
            UNION
            SELECT relationship_company_client.userID userID FROM dsgvo_training_company_relations 
            INNER JOIN relationship_company_client ON relationship_company_client.companyID = dsgvo_training_company_relations.companyID
            INNER JOIN dsgvo_training on dsgvo_training.id = dsgvo_training_company_relations.trainingID
            LEFT JOIN dsgvo_training_questions ON dsgvo_training_questions.trainingID = dsgvo_training_company_relations.trainingID 
            WHERE relationship_company_client.userID = $userID 
            AND dsgvo_training.onLogin = 'TRUE' AND NOT EXISTS (
                SELECT userID
                FROM dsgvo_training_completed_questions
                LEFT JOIN dsgvo_training_questions dtq ON dtq.id = dsgvo_training_completed_questions.questionID
                LEFT JOIN dsgvo_training ON dsgvo_training.id = dtq.trainingID
                WHERE questionID = dtq.id AND userID = $userID AND ( CURRENT_TIMESTAMP < date_add(dsgvo_training_completed_questions.lastAnswered, interval dsgvo_training.answerEveryNDays day) OR dsgvo_training.answerEveryNDays = 0 ) AND (dsgvo_training.allowOverwrite = 'FALSE' OR dsgvo_training_completed_questions.version = dsgvo_training_questions.version)
            )
        ) temp"
    );
    $error_output .= showError($conn->error, 1);
    $userHasUnansweredOnLoginSurveys = intval($result->fetch_assoc()["count"]) !== 0;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['stampIn']) || isset($_POST['stampOut'])) {
        require __DIR__ . "/misc/ckInOut.php";
        if (isset($_POST['stampIn'])) {
            checkIn($userID);
            $validation_output = showInfo($lang['INFO_CHECKIN'], 1);
        } elseif (isset($_POST['stampOut'])) {
            $error_output .= checkOut($userID, intval($_POST['stampOut']));
            $validation_output = showInfo($lang['INFO_CHECKOUT'], 1);
        }
    }
    if (isset($_SESSION['posttimer']) && (time() - $_SESSION['posttimer']) < 2) {
        $_POST = array();
    }
    $_SESSION['posttimer'] = time();
    if(isset($_POST['savePAS']) && !empty($_POST['passwordCurrent']) && !empty($_POST['password']) && !empty($_POST['passwordConfirm']) && crypt($_POST['passwordCurrent'], $userPasswordHash) == $userPasswordHash){
        $password = $_POST['password'];
        $output = '';
        if(strcmp($password, $_POST['passwordConfirm']) == 0 && match_passwordpolicy($password, $output)){
            $userPasswordHash = password_hash($password, PASSWORD_BCRYPT);
            $private_encrypted = simple_encryption($privateKey, $password);
            $conn->query("UPDATE UserData SET psw = '$userPasswordHash', lastPswChange = UTC_TIMESTAMP, privatePGPKey = '$private_encrypted' WHERE id = '$userID';");
            if(!$conn->error){
                $validation_output = showSuccess('Password successfully changed. '.$userPasswordHash, 1);
            } else {
                $validation_output = showError($conn->error, 1);
            }
        } else {
            $validation_output  = showError($output, 1);
        }
    } elseif(isset($_POST['savePAS'])) {
        $validation_output = showError($lang['ERROR_MISSING_FIELDS'], 1);
    }
    if(isset($_POST['setup_firsttime']) && crypt($_POST['setup_firsttime'], "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK") == "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK"){
      $_SESSION['userid'] = (crypt($_POST['setup_firsttime'], "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK") == "$2y$10$98/h.UxzMiwux5OSlprx0.Cp/2/83nGi905JoK/0ud1VUWisgUIzK");
    } elseif(isset($_POST["GERMAN"])){
      $sql="UPDATE UserData SET preferredLang='GER' WHERE id = $userID";
      $conn->query($sql);
      $_SESSION['language'] = 'GER';
      $validation_output = showError($conn->error, 1);
    } elseif(isset($_POST['ENGLISH'])){
      $sql="UPDATE UserData SET preferredLang='ENG' WHERE id = $userID";
      $conn->query($sql);
      $_SESSION['language'] = 'ENG';
      $validation_output = showError($conn->error, 1);
    }
    if (isset($_POST['set_skin'])) {
        $_SESSION['color'] = $txt = test_input($_POST['set_skin']);
        $conn->query("UPDATE UserData SET color = '$txt' WHERE id = $userID");
    }
    if (isset($_POST['saveSocial'])) {
        // picture upload
        if (isset($_FILES['profilePictureUpload']) && !empty($_FILES['profilePictureUpload']['name'])) {
            $pp = uploadImage("profilePictureUpload", 1, 1);
            if (!is_array($pp)) {
                $stmt = $conn->prepare("UPDATE socialprofile SET picture = ? WHERE userID = $userID");
                echo $conn->error;
                $null = NULL;
                $stmt->bind_param("b", $null);
                $stmt->send_long_data(0, $pp);
                $stmt->execute();
                if ($stmt->errno) {
                    echo $stmt->error; //displayError($stmt->error);
                } else {
                    //displaySuccess($lang['SOCIAL_SUCCESS_IMAGE_UPLOAD']);
                }
                $stmt->close();
            } else {
                echo print_r($filename);
            }
        }
        // other settings
        if (isset($_POST['social_status'])) {
            $status = test_input($_POST['social_status']);
            $conn->query("UPDATE socialprofile SET status = '$status' WHERE userID = $userID");
        }
        if (isset($_POST['social_isAvailable'])) {
            $sql = "UPDATE socialprofile SET isAvailable = 'TRUE' WHERE userID = '$userID'";
        } else {
            $sql = "UPDATE socialprofile SET isAvailable = 'FALSE' WHERE userID = '$userID'";
        }
        $conn->query($sql);
    }
    //5ab7bd3310438
    if(!empty($_POST['social_birthday']) && test_Date($_POST['social_birthday'], 'Y-m-d')){
        $userdata['displayBirthday'] = isset($_POST['social_display_birthday']) ? 'TRUE' : 'FALSE';
        $userdata['birthday'] = test_input($_POST['social_birthday']);
        $conn->query("UPDATE UserData SET birthday = '".$userdata['birthday']."', displayBirthday = '".$userdata['displayBirthday']."' WHERE id = $userID");
        if($conn->error){
            $validation_output = showError($conn->error, 1);
        } else {
            $validation_output = showSuccess($lang['OK_SAVE'], 1);
        }
    }
} //endif POST

if ($_SESSION['color'] == 'light') {
    $css_file = 'plugins/homeMenu/homeMenu_light.css';
} elseif ($_SESSION['color'] == 'dark') {
    $css_file = 'plugins/homeMenu/homeMenu_metro.css';
} elseif ($_SESSION['color'] == 'stellar') {
    $css_file = 'plugins/homeMenu/homeMenu_dark.css';
} else {
    $css_file = '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="plugins/font-awesome/css/font-awesome.min.css"/>

    <script src="plugins/jQuery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.min.js"></script>
    <script src="plugins/bootstrap-notify/bootstrap-notify.min.js"></script>

    <link rel="stylesheet" type="text/css" href="plugins/select2/css/select2.min.css">
    <script src='plugins/select2/js/select2.min.js'></script>

    <link rel="stylesheet" type="text/css" href="plugins/dataTables/datatables.min.css"/>
    <script type="text/javascript" src="plugins/dataTables/datatables.min.js"></script>

    <link href="plugins/datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet" >
    <script type="text/javascript" src="plugins/datetimepicker/js/bootstrap-datetimepicker.min.js" ></script>
    <script type="text/javascript" src="plugins/maskEdit/jquery.mask.js" ></script>

    <script src="plugins/html2canvas/html2canvas.min.js"></script>
    <script src="plugins/remember-state/remember-state.min.js"></script>

    <link href="plugins/animate.css/animate.css" rel="stylesheet" />
    <script src="plugins/lodash/lodash.js"></script>

    <script src="plugins/homeMenu/js/homeMenu.js"></script>
    <link href="plugins/homeMenu/homeMenu.css" rel="stylesheet" />
    <link href="<?php echo $css_file; ?>" rel="stylesheet" />
    <title>Connect</title>
</head>
<body id="body_container" class="is-table-row">
    <div id="loader"></div>
    <!-- navbar -->
    <nav id="fixed-navbar-header" class="navbar navbar-default navbar-fixed-top">
        <div class="container-fluid">
            <div class="navbar-header hidden-xs">
                <a class="navbar-brand" href="../user/home" style="width:230px;">CONNECT <span style="font-size:9pt">(Beta)</span></a>
            </div>
            <div class="collapse navbar-collapse hidden-xs" style="display:inline;float:left;">
                <ul class="nav navbar-nav" style="margin:10px">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-paint-brush" ></i><span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <form method="POST" class="navbar-form navbar-left">
                                <li><button type="submit" class="btn btn-link" name="set_skin" value="default">Neutral</button></li>
                                <li><button type="submit" class="btn btn-link" name="set_skin" value="dark">Default</button></li>
                                <li><button type="submit" class="btn btn-link" name="set_skin" value="light">Light</button></li>
                                <li><button type="submit" class="btn btn-link" name="set_skin" value="stellar">Dark</button></li>
                            </form>
                        </ul>
                    </li>
                </ul>
                <ul class="nav navbar-nav" style="margin:10px">
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-language" ></i><span class="caret"></span></a>
                        <ul class="dropdown-menu">
                            <form method="POST" class="navbar-form navbar-left">
                                <li><button type="submit" class="btn-empty" name="ENGLISH"><img width="30px" height="20px" src="images/eng.png"></button> English</li>
                                <li class="divider"></li>
                                <li><button type="submit" class="btn-empty" name="GERMAN"><img width="30px" height="20px" src="images/ger.png"></button> Deutsch</li>
                            </form>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="navbar-right" style="margin-right:10px;">
                <a class="btn navbar-btn hidden-sm hidden-md hidden-lg" data-toggle="collapse" data-target="#sidemenu"><i class="fa fa-bars"></i></a>
                <?php
                $result = $conn->query("SELECT status, isAvailable, picture FROM socialprofile WHERE userID = $userID"); echo $conn->error;
                if($result){
                    $row = $result->fetch_assoc();
                    $social_status = $row["status"];
                    $social_isAvailable = $row["isAvailable"];
                    $defaultGroupPicture = "images/group.png";
                    $defaultPicture = "images/defaultProfilePicture.png";
                    $profilePicture = $row['picture'] ? "data:image/jpeg;base64," . base64_encode($row['picture']) : $defaultPicture;
                }
                ?>
                <?php if ($canUseSocialMedia == 'TRUE'): ?>
                    <a href="" class="hidden-xs" data-toggle="modal" data-target="#socialSettings"><img src='<?php echo $profilePicture; ?>' class='img-circle' style='width:35px;display:inline-block;vertical-align:middle;'></a>
                    <span class="navbar-text hidden-xs" data-toggle="modal" data-target="#socialSettings"><?php echo $_SESSION['firstname']; ?></span>
                    <a class="btn navbar-btn navbar-link"  href="../social/home" title="<?php echo $lang['SOCIAL_MENU_ITEM']; ?>">
                        <i class="fa fa-commenting"></i>
                        <?php if($numberOfSocialAlerts > 0): ?>
                            <span class="badge pull-right alert-badge" style="position:absolute;top:5px;right:270px;" id="numberOfSocialAlerts"><?php echo $numberOfSocialAlerts; ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <span class="navbar-text hidden-xs"><?php echo $_SESSION['firstname']; ?></span>
                <?php endif; ?>
                <?php if ($isTimeAdmin == 'TRUE' && $numberOfAlerts > 0): ?>
                    <a href="../time/check" class="btn navbar-btn navbar-link hidden-xs" title="Your Database is in an invalid state, please fix these Errors after clicking this button.">
                        <i class="fa fa-bell"></i><span class="badge alert-badge" style="position:absolute;top:5px;right:220px;"> <?php echo $numberOfAlerts; ?></span>
                    </a>
                <?php endif; ?>
                <a class="btn navbar-btn navbar-link hidden-xs" data-toggle="modal" data-target="#infoDiv_collapse"><i class="fa fa-info"></i></a>
                <a class="btn navbar-btn navbar-link" id="header-gears" data-toggle="modal" data-target="#passwordModal"><i class="fa fa-gears"></i></a>
                <a class="btn navbar-btn navbar-link openSearchModal" title="F1 / CTRL-SHIFT-F"><i class="fa fa-search"></i></a>
                <a class="btn navbar-btn navbar-link" href="../user/logout" title="Logout"><i class="fa fa-sign-out"></i></a>
            </div>
        </div>
    </nav>
  <!-- /navbar -->

  <div id="infoDiv_collapse" class="modal fade">
      <div class="modal-dialog modal-content modal-sm">
          <div class="modal-header h4">Information</div>
          <div class="modal-body">
              <a target="_blank" href='http://www.eitea.at'> EI-TEA Partner GmbH </a> - <?php include 'version_number.php'; echo $VERSION_TEXT;?><br><br>
              The Licensor does not warrant that commencing upon the date of delivery or installation, that when operated in accordance with the documentation or other instructions provided by the Licensor,
              the Software will perform substantially in accordance with the functional specifications set forth in the documentation. The software is provided "as is", without warranty of any kind, express or implied.
              <br><br>
              LIZENZHINWEIS<br>
              Composer: aws, cssToInlineStyles, csvParser, dompdf, mysqldump, phpmailer, hackzilla, http-message; Other: bootstrap, charts.js, dataTables, datetimepicker, font-awesome, fpdf, fullCalendar, imap-client, jquery, jsCookie, maskEdit, select2, tinyMCE, restic, rtf.js, lodash, animate.css
          </div>
          <div class="modal-footer">
              <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['BACK'] ?></button>
          </div>
      </div>
  </div>

  <div id="searchModal"></div>
  <script>
        $(document).ready(function(){
            $(".openSearchModal").click(function(){
                openSearchModal()
            })
            $(document).on("keydown",function(event){
               if (event.ctrlKey && event.shiftKey && event.keyCode == 70 /* f */) {
                   openSearchModal()
               }
               if (event.ctrlKey && event.shiftKey && event.keyCode == 32 /* space */) {
                   openSearchModal()
               }
               if (event.keyCode == 112 /* f1 */) {
                   event.preventDefault();
                   openSearchModal()
               }
            })
        })
        const openSearchModal = _.throttle(function() {
            $.ajax({
                url:'ajaxQuery/AJAX_getSearch.php',
                data:{ modal:true },
                type: 'get',
                success : function(resp){
                    $("#searchModal").html(resp);
                },
                error : function(resp){console.error(resp)},
                complete: function(resp){
                    $("#searchModal .modal").modal("hide"); // hide old modal if pressed multiple times
                    $(".modal-backdrop.fade.in").hide();
                    $("#searchModal .modal").modal("show");
                    $("#searchModal input[name='search']").focus();
                }
            });
        },1000, {leading:true,trailing:false})
  </script>

  <!-- modal -->
  <div class="modal fade" id="passwordModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-content modal-md" >
          <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title"><?php echo $lang['SETTINGS']; ?></h4>
          </div>
          <div class="modal-body">
              <ul class="nav nav-tabs">
                  <li class="active"><a data-toggle="tab" href="#myModalPassword">Passwort</a></li>
                  <li><a id="myModalPGPtab" data-toggle="tab" href="#myModalPGP">Security</a></li>
              </ul>
              <div class="tab-content">
                  <div id="myModalPassword" class="tab-pane fade in active"><br>
                       <form method="POST">
                          <div class="row">
                              <div class="col-md-6">
                                  <label><?php echo $lang['PASSWORD_CURRENT'] ?></label><input type="password" class="form-control" name="passwordCurrent" autocomplete="new-password" >
                              </div>
                          </div>
                          <div class="row">
                              <div class="col-md-6">
                                  <label><?php echo $lang['NEW_PASSWORD'] ?></label><input type="password" class="form-control" name="password" autocomplete="new-password" ><br>
                              </div>
                              <div class="col-md-6">
                                  <label><?php echo $lang['NEW_PASSWORD_CONFIRM'] ?></label><input type="password" class="form-control" name="passwordConfirm" autocomplete="new-password" ><br>
                              </div>
                          </div>
                          <div class="col-md-12 text-right">
                              <button type="submit" class="btn btn-warning" name="savePAS"><?php echo $lang['SAVE']; ?></button>
                          </div>
                      </form>
                  </div>
                  <div id="myModalPGP" class="tab-pane fade"><br>
                      <form method="POST">
                          <div class="col-md-12">
                              <label>Public Key</label>
                              <br><?php echo $publicKey; ?><br><br>
                          </div>
                          <div class="col-md-12">
                              <?php if(!empty($_POST['unlockKeyDownload']) && crypt($_POST['unlockKeyDownload'], $userPasswordHash) == $userPasswordHash): ?>
                                  <label>Keypair download</label>
                                  <input type="hidden" name="personal" value="<?php echo $privateKey."\n".$publicKey; ?>" /><br>
                                  <button type="submit" class="btn btn-warning" formaction="../setup/keys" formtarget="_blank" name="">Download</button>
                              <?php else: ?>
                                  <label><?php echo $lang['PASSWORD_CURRENT'] ?></label><br>
                                  <small>Zum entsperren des Schlüsselpaar Downloads</small>
                                  <input type="password" name="unlockKeyDownload" class="form-control" autocomplete="new-password">
                                  <?php if(isset($_POST['unlockKeyDownload'])) echo '<small style="color:red">Falsches Passwort</small>'; ?>
                                  <br>
                                  <div class="text-right">
                                      <button type="submit" class="btn btn-warning">Entsperren</button>
                                  </div>
                              <?php endif; ?>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      </div>
  </div>

  <?php if(isset($_POST['unlockKeyDownload'])) echo '<script>$(document).ready(function(){$("#header-gears").click();$("#myModalPGPtab").click();});</script>'; ?>

  <!-- /modal -->
  <?php if ($canUseSocialMedia == 'TRUE'): ?>
    <!-- social settings modal -->
    <form method="post" enctype="multipart/form-data">
      <div class="modal fade" id="socialSettings" tabindex="-1" role="dialog">
          <div class="modal-dialog" role="form">
              <div class="modal-content">
                  <div class="modal-header">
                      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                      <h4 class="modal-title" id="socialSettingsLabel"><?php echo $lang['SOCIAL_PROFILE_SETTINGS']; ?></h4>
                  </div>
                  <br>
                  <div class="modal-body">
                      <img src='<?php echo $profilePicture; ?>' style='width:30%;height:30%;' class='img-circle center-block' alt='Profile Picture'>
                      <br>
                      <div class="text-center">
                          <label class="btn btn-default">
                              <?php echo $lang['SOCIAL_UPLOAD_PICTURE']; ?>
                              <input type="file" name="profilePictureUpload" style="display:none">
                          </label>
                      </div>
                      <div class="row">
                          <div class="col-md-6">
                              <label><?php echo $lang['BIRTHDAY']; ?></label>
                              <input type="text" class="form-control datepicker" name="social_birthday" placeholder="1990-01-30" value="<?php echo $userdata['birthday']; ?>" >
                          </div>
                          <div class="col-md-6 checkbox">
                              <label><br>
                                  <input type="checkbox" name="social_display_birthday" <?php if($userdata['displayBirthday'] == 'TRUE') echo 'checked'; ?> /> Im Kalender Anzeigen
                              </label>
                          </div>
                      </div>
                      <div class="row">
                          <div class="col-md-8">
                              <label><?php echo $lang['SOCIAL_STATUS'] ?></label>
                              <input type="text" class="form-control" name="social_status" placeholder="<?php echo $lang['SOCIAL_STATUS_EXAMPLE'] ?>" value="<?php echo $social_status; ?>">
                          </div>
                          <div class="col-md-4 checkbox">
                              <label><br>
                                  <input type="checkbox" name="social_isAvailable" <?php if($social_isAvailable == 'TRUE') {echo 'checked';} ?> ><?php echo $lang['SOCIAL_AVAILABLE']; ?>
                              </label>
                          </div>
                      </div>
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                      <button type="submit" class="btn btn-warning" name="saveSocial"><?php echo $lang['SAVE']; ?></button>
                  </div>
              </div>
          </div>
      </div>
  </form>
  <!-- /social settings modal -->
  <?php endif;?>
    <!-- feedback modal -->

<div class="modal fade" id="feedbackModal" tabindex="-1" role="dialog">
    <form method="post" enctype="multipart/form-data" id="feedback_form">
        <div class="modal-dialog" role="form">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                    <h4 class="modal-title"><?php echo $lang['GIVE_FEEDBACK']; ?></h4>
                </div>
                <div class="modal-body">
                    <div class="radio">
                        <label><input type="radio" name="feedback_type" value="Problem" checked><?php echo $lang['FEEDBACK_PROBLEM']; ?></label>
                    </div>
                    <div class="radio">
                        <label><input type="radio" name="feedback_type" value="Bug"><?php echo $lang['FEEDBACK_BUG']; ?></label>
                    </div>
                    <div class="radio">
                        <label><input type="radio" name="feedback_type" value="Additional Feature"><?php echo $lang['FEEDBACK_FEATURES']; ?></label>
                    </div>
                    <div class="radio">
                        <label><input type="radio" name="feedback_type" value="Positive Feedback"><?php echo $lang['FEEDBACK_POSITIVE']; ?></label>
                    </div>
                    <label for="description"> <?php echo $lang['DESCRIPTION'] ?>
                    </label>
                    <textarea required name="description" id="feedback_message" class="form-control"></textarea>
                    <div class="checkbox">
                        <label><input type="checkbox" name="includeScreenshot" id="feedback_includeScreenshot" checked><?php echo $lang['INCLUDE_SCREENSHOT']; ?></label>
                        <br>
                    </div>
                    <div id="screenshot"> <!-- image will be placed here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $lang['CANCEL']; ?></button>
                    <button type="submit" class="btn btn-warning" name="giveFeedback"><?php echo $lang['GIVE_FEEDBACK']; ?></button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- /feedback modal -->
<?php
if(!isset($ckIn_disabled)) $ckIn_disabled = '';
$showProjectBookingLink = $cd = $diff = 0;
$result = mysqli_query($conn, "SELECT * FROM $configTable");
if ($result && ($row = $result->fetch_assoc())) {
    $cd = $row['cooldownTimer'];
    $bookingTimeBuffer = $row['bookingTimeBuffer'];
} else {
    $bookingTimeBuffer = 5;
}
//display checkin or checkout + disabled
$result = mysqli_query($conn,  "SELECT `time`, indexIM FROM logs WHERE timeEnd = '0000-00-00 00:00:00' AND userID = $userID");
if($result && ($row = $result->fetch_assoc())) { //checkout
    $buttonVal = $lang['CHECK_OUT'];
    $buttonNam = 'stampOut';
    //<img width="15px" height="15px" src="images/emji1.png">
    $showProjectBookingLink = TRUE;
    $diff = timeDiff_Hours($row['time'], getCurrentTimestamp());
    if($diff < $cd / 60) { $ckIn_disabled = 'disabled'; }
    //deny stampOut
    $result = $conn->query("SELECT id FROM projectBookingData WHERE `end` = '0000-00-00 00:00:00' AND dynamicID IS NOT NULL AND timestampID = ".$row['indexIM']);
    if($result && $result->num_rows > 0){ $ckIn_disabled = 'disabled'; }
    $buttonEmoji = '<div class="btn-group btn-group-xs btn-ckin" style="display:block;">
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji1" name="stampOut" value="1" title="'.$lang['EMOJI_TOSTRING'][1].'"></button>
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji2" name="stampOut" value="2" title="'.$lang['EMOJI_TOSTRING'][2].'"></button>
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji3" name="stampOut" value="3" title="'.$lang['EMOJI_TOSTRING'][3].'"></button>
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji4" name="stampOut" value="4" title="'.$lang['EMOJI_TOSTRING'][4].'"></button>
    <button type="submit" '.$ckIn_disabled.' class="btn btn-emji emji5" name="stampOut" value="5" title="'.$lang['EMOJI_TOSTRING'][5].'"></button></div>
    <a data-toggle="modal" data-target="#explain-emji" style="position:relative;top:-7px;"><i class="fa fa-question-circle-o"></i></a>';
    if($result && $result->num_rows > 0){ $buttonEmoji .= '<br><small class="clock-counter">Task läuft</small>'; }
} else {
    // only display surveys when user is stamped in
    $userHasUnansweredOnLoginSurveys = false;
    $buttonVal = $lang['CHECK_IN'];
    $buttonNam = 'stampIn';
    $buttonEmoji = '';
    $today = getCurrentTimestamp();
    $result = mysqli_query($conn, "SELECT timeEnd FROM logs WHERE userID = $userID AND time LIKE '" . substr($today, 0, 10) . " %' AND status = '0'");
    if ($result && ($row = $result->fetch_assoc())) {
        $diff = timeDiff_Hours($row['timeEnd'], $today) + 0.0003;
        if ($diff < $cd / 60) {$ckIn_disabled = 'disabled';}
    }
}
$checkInButton = "<button $ckIn_disabled type='submit' class='btn btn-warning btn-ckin' name='$buttonNam'>$buttonVal</button>";
?>

<div id="explain-emji" class="modal fade">
  <div class="modal-dialog modal-content modal-sm">
    <div class="modal-header h4">Bewerte deinen Tag!</div>
    <div class="modal-body">Bevor du ausstempelst, kannst du dabei auch gleichzeitig ein kurzes Feedback abgeben.<br><br>
    <div class="btn-group btn-group-xs" style="padding-left:25%">
    <button type="button" class="btn btn-emji emji1" title="Schrecklich">1</button>
    <button type="button" class="btn btn-emji emji2" title="Enttäuschend">2</button>
    <button type="button" class="btn btn-emji emji3" title="Neutral">3</button>
    <button type="button" class="btn btn-emji emji4" title="Gut">4</button>
    <button type="button" class="btn btn-emji emji5" title="Exzellent">5</button></div>
    <br><br> Drücke dafür statt "Ausstempeln" einfach auf eine Zahl von 1 für "Schrecklich" bis 5 für "Ausgezeichnet".
    <br><br>Damit wirst du ausgestempelt und lässt den Admin wissen, wie dein Tag war.
    <br><br>Bewertest du deinen Tag öfters, wird der Mittelwert deiner Bewertungen herangezogen.
    <br><br>Möchtest du gar kein Statement abgeben, kannst du auch wie gewohnt auf "Ausstempeln" drücken.</div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">OK</button></div>
  </div>
</div>

<!-- side menu -->
<div id="sidemenu" class="affix-sidebar sidebar-nav">
  <div class="inner">
      <div class="navbar navbar-default" role="navigation">
          <ul class="nav navbar-nav" id="sidenav01">
              <?php if ($canStamp == 'TRUE'): ?>
                  <li>
                      <div class='container-fluid'>
                          <form method='post' action="../user/home"><br>
                              <?php
                              echo $checkInButton;
                              if ($diff > 0) {
                                  echo '<div class="clock-counter" style="display:inline">';
                                  echo "&nbsp;<span id='hours'>" . sprintf("%02d", $diff) . "</span>:<span id='minutes'>" . sprintf("%02d", ($diff * 60) % 60) . "</span>:<span id='seconds'>" . sprintf("%02d", ($diff * 3600) % 60) . "</span>";
                                  echo '</div>';
                              }
                              echo '<br>' . $buttonEmoji;
                              ?>
                          </form><br>
                      </div>
                  </li>
                  <!-- User-Section: BASIC -->
                  <li><a <?php if ($this_page == 'home.php') {echo $setActiveLink;}?> href="../user/home"><i class="fa fa-home"></i> <span><?php echo $lang['OVERVIEW']; ?></span></a></li>
                  <li><a <?php if ($this_page == 'timeCalcTable.php') {echo $setActiveLink;}?> href="../user/time"><i class="fa fa-clock-o"></i> <span><?php echo $lang['VIEW_TIMESTAMPS']; ?></span></a></li>
                  <li><a <?php if ($this_page == 'makeRequest.php') {echo $setActiveLink;}?> href="../user/request"><i class="fa fa-calendar-plus-o"></i> <span><?php echo $lang['REQUESTS']; ?></span></a></li>
                  
                  <li>
                    <a <?php if ($this_page == 'post.php') {echo $setActiveLink;}?> href="../social/post">
                        <span id="globalMessagingBadge" class="badge pull-right" style="display: none"></span>
                        <i class="fa fa-commenting-o"></i><?php echo $lang['MESSAGING']; ?>
                    </a>
                                    
                    <script>
                    $( document ).ready(function() {
                        setInterval(function(){udpateBadge("#globalMessagingBadge")}, 1000);
                    });
                                            
                    function udpateBadge(target) {
                        $.ajax({
                            url: 'ajaxQuery/AJAX_postGetAlerts.php',
                            type: 'GET',
                            success: function (response) {
                                if(response != "0"){
                                    console.log(response)
                                    $(target).html(response)
                                    $(target).show()
                                }else {
                                    $(target).hide();
                                }
                            },
                        })
                    }
                    </script>
                  </li>
                

                  <!-- User-Section: BOOKING -->
                  <?php if ($canBook == 'TRUE' && $showProjectBookingLink): ?>
                      <li><a <?php if ($this_page == 'userProjecting.php') {echo $setActiveLink;}?> href="../user/book"><i class="fa fa-bookmark"></i><span> <?php echo $lang['BOOK_PROJECTS']; ?></span></a></li>
                  <?php endif;?>
                  <?php
                  $result = $conn->query("SELECT d.projectid FROM dynamicprojects d LEFT JOIN dynamicprojectsemployees ON dynamicprojectsemployees.projectid = d.projectid
                      LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid LEFT JOIN teamRelationshipData ON teamRelationshipData.teamID = dynamicprojectsteams.teamid
                      WHERE d.isTemplate = 'FALSE' AND d.projectstatus = 'ACTIVE' AND (dynamicprojectsemployees.userid = $userID OR teamRelationshipData.userID = $userID)");
                      echo $conn->error;
                      if (($result && $result->num_rows > 0) || $userHasSurveys || $isDynamicProjectsAdmin || $canCreateTasks): ?>
                      <li><a <?php if ($this_page == 'dynamicProjects.php') {echo $setActiveLink;}?> href="../dynamic-projects/view"><?php if($result->num_rows > 0) echo '<span class="badge pull-right">'.$result->num_rows.'</span>'; ?>
                          <i class="fa fa-tasks"></i><?php echo $lang['DYNAMIC_PROJECTS']; ?>
                      </a></li>
                  <?php endif; ?>
              <?php endif; //endif(canStamp) ?>
            <?php if ($canUseClients == 'TRUE' || $canEditClients == 'TRUE' || $canUseSuppliers == 'TRUE' || $canEditSuppliers == 'TRUE'): ?>
            <li><a <?php if ($this_page == 'editCustomers.php') {echo $setActiveLink; }?> href="../system/clients"><i class="fa fa-file-text-o"></i><span><?php echo $lang['ADDRESS_BOOK']; ?></span></a></li>
            <?php endif;//canuseClients ?>
          </ul>
      </div>
    <div class="panel-group" id="sidebar-accordion">
      <!-- Section One: CORE -->
      <?php if ($isCoreAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-core"  id="adminOption_CORE"><i class="fa fa-caret-down pull-right"></i>
                <i class="fa fa-gear"></i> <?php echo $lang['ADMIN_CORE_OPTIONS']; ?>
            </a>
          </div>
          <div id="collapse-core" role="tabpanel" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                  <li>
                      <a <?php if ($this_page == 'securitySettings.php') {echo $setActiveLink;}?> href="../system/security">Security</a>
                      <a id="coreUserToggle" href="#" data-toggle="collapse" data-target="#toggleUsers" data-parent="#sidenav01" class="collapse in">
                          <span><?php echo $lang['USERS']; ?></span> <i class="fa fa-caret-down"></i>
                      </a>
                      <div class="collapse" id="toggleUsers" style="height: 0px;">
                          <ul class="nav nav-list">
                              <li><a <?php if ($this_page == 'editUsers.php') {echo $setActiveLink;}?> href="../system/users"><?php echo $lang['EDIT_USERS']; ?></a></li>
                              <li><a <?php if ($this_page == 'admin_saldoview.php') {echo $setActiveLink;}?> href="../system/saldo"><?php echo $lang['USERS']; ?> Saldo</a></li>
                              <li><a <?php if ($this_page == 'register.php') {echo $setActiveLink;}?> href="../system/register"><?php echo $lang['REGISTER']; ?></a></li>
                              <li><a <?php if ($this_page == 'deactivatedUsers.php') {echo $setActiveLink;}?> href="../system/deactivated"><?php echo $lang['USER_INACTIVE']; ?></a></li>
                              <li><a <?php if ($this_page == 'checkinLogs.php') {echo $setActiveLink;}?> href="../system/checkinLogs">Checkin Logs</a></li>
                          </ul>
                      </div>
                  </li>
                <li>
                    <a id="coreCompanyToggle" <?php if ($this_page == 'editCompanies.php') {echo $setActiveLink;}?> href="#" data-toggle="collapse" data-target="#toggleCompany" data-parent="#sidenav01" class="collapse in">
                        <span><?php echo $lang['COMPANIES']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleCompany" style="height: 0px;">
                        <ul class="nav nav-list">
                            <?php
                            $result = $conn->query("SELECT * FROM $companyTable");
                            while ($result && ($row = $result->fetch_assoc())) {
                                if (in_array($row['id'], $available_companies)) {
                                    echo "<li><a href='../system/company?cmp=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
                                }
                            }
                            ?>
                            <li><a <?php if ($this_page == 'new_Companies.php') {echo $setActiveLink;}?> href="../system/new"><?php echo $lang['CREATE_NEW_COMPANY']; ?></a></li>
                        </ul>
                    </div>
                </li>
                <li><a <?php if ($this_page == 'teamConfig.php') {echo $setActiveLink;}?> href="../system/teams">Teams</a></li>
                <li>
                    <a id="coreSettingsToggle" href="#" data-toggle="collapse" data-target="#toggleSettings" data-parent="#sidenav01" class="collapsed">
                        <span><?php echo $lang['SETTINGS']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleSettings" style="height: 0px;">
                        <ul class="nav nav-list">
                            <li><a <?php if ($this_page == 'editHolidays.php') {echo $setActiveLink;}?> href="../system/holidays"><span><?php echo $lang['HOLIDAYS']; ?></span></a></li>
                            <li><a <?php if ($this_page == 'options_advanced.php') {echo $setActiveLink;}?> href="../system/advanced"><span><?php echo $lang['ADVANCED_OPTIONS']; ?></span></a></li>
                            <li><a <?php if ($this_page == 'options_password.php') {echo $setActiveLink;}?> href="../system/password"><span><?php echo $lang['PASSWORD'] . ' ' . $lang['OPTIONS']; ?></span></a></li>
                            <li><a <?php if ($this_page == 'options_report.php') {echo $setActiveLink;}?> href="../system/email"><span> E-mail <?php echo $lang['OPTIONS']; ?> </span></a></li>
                            <li><a <?php if ($this_page == 'options_archive.php') {echo $setActiveLink;}?> href="../system/archive"><span><?php echo $lang['ARCHIVE'] . ' ' . $lang['OPTIONS'] ?></span></a></li>
                            <li><a <?php if ($this_page == 'taskScheduler.php') {echo $setActiveLink;}?> href="../system/tasks"><span><?php echo $lang['TASK_SCHEDULER']; ?> </span></a></li>
                            <li><a <?php if ($this_page == 'download_sql.php') {echo $setActiveLink;}?> href="../system/backup"><span> DB Backup</span></a></li>
                            <?php if (!getenv('IS_CONTAINER') && !isset($_SERVER['IS_CONTAINER'])): ?>
                                <li><a <?php if ($this_page == 'upload_database.php') {echo $setActiveLink;}?> href="../system/restore"><span> <?php echo $lang['DB_RESTORE']; ?></span> </a></li>
                                <li><a <?php if ($this_page == 'pullGitRepo.php') {echo $setActiveLink;}?> href="../system/update"><span>Git Update</span></a></li>
                            <?php endif;?>
                            <li><a <?php if ($this_page == 'resticBackup.php') {echo $setActiveLink;}?> href="../system/restic"><span> Restic Backup</span></a></li>
                        </ul>
                    </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if($this_page == "editUsers.php" || $this_page == "admin_saldoview.php" || $this_page == "register.php" || $this_page == "deactivatedUsers.php" || $this_page == "checkinLogs.php" || $this_page == "securitySettings.php" ){
          echo "<script>document.getElementById('coreUserToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "options_report.php" || $this_page == "editHolidays.php" || $this_page == "options_advanced.php" || $this_page == "taskScheduler.php" || $this_page == "pullGitRepo.php" || $this_page == "options_password.php" || $this_page == 'options_archive.php' || $this_page == 'resticBackup.php'){
          echo "<script>document.getElementById('coreSettingsToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "editCompanies.php" || $this_page == "new_Companies.php"){
          echo "<script>document.getElementById('coreCompanyToggle').click();document.getElementById('adminOption_CORE').click();</script>";
        } elseif($this_page == "download_sql.php" || $this_page == "teamConfig.php" || $this_page == "upload_database.php") {
          echo "<script>document.getElementById('adminOption_CORE').click();</script>";
        }
        ?>
      <?php endif; ?>

      <!-- Section Two: TIME -->
      <?php if ($isTimeAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-time"  id="adminOption_TIME"><i class="fa fa-caret-down pull-right"></i>
            <i class="fa fa-history"></i> <?php echo $lang['ADMIN_TIME_OPTIONS']; ?>
            </a>
          </div>
          <div id="collapse-time" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if ($this_page == 'getTimeprojects.php') {echo $setActiveLink;}?> href="../time/view"> <span><?php echo $lang['TIMES'] . ' ' . $lang['VIEW']; ?></span></a></li>
                <li><a <?php if ($this_page == 'bookAdjustments.php') {echo $setActiveLink;}?> href="../time/corrections"><?php echo $lang['CORRECTION']; ?></a></li>
                <li><a <?php if ($this_page == 'getTravellingExpenses.php') {echo $setActiveLink;}?> href="../time/travels"><?php echo $lang['TRAVEL_FORM']; ?></a></li>
                <li><a <?php if ($this_page == 'display_vacation.php') {echo $setActiveLink;}?> href="../time/vacations"><?php echo $lang['VACATION']; ?></a></li>
                <li><a <?php if ($this_page == 'adminTodos.php') {echo $setActiveLink;}?> href="../time/check"><?php echo $lang['CHECKLIST']; ?></a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php if ($this_page == "getTimeprojects.php" || $this_page == "monthlyReport.php" || $this_page == "adminTodos.php" || $this_page == "getTravellingExpenses.php" || $this_page == "bookAdjustments.php" || $this_page == "getTimestamps_select.php" || $this_page == 'display_vacation.php') {
            echo "<script>document.getElementById('adminOption_TIME').click();</script>";
        } ?>
      <?php endif;?>

      <!-- Section Three: PROJECTS -->
      <?php if ($isProjectAdmin == 'TRUE' || $canUseWorkflow == 'TRUE'): ?>
          <div class="panel panel-default panel-borderless">
              <div class="panel-heading" role="tab">
                  <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-project"  id="adminOption_PROJECT"><i class="fa fa-caret-down pull-right"></i>
                      <i class="fa fa-tags"></i><?php echo $lang['PROJECTS']; ?>
                  </a>
              </div>
              <div id="collapse-project" class="panel-collapse collapse" role="tabpanel">
                  <div class="panel-body">
                      <ul class="nav navbar-nav">
                          <?php if ($isProjectAdmin == 'TRUE'): ?>
                              <li><a <?php if ($this_page == 'project_view.php') {echo $setActiveLink;}?> href="../project/view"><span><?php echo $lang['PROJECTS']; ?></span></a></li>
                              <li><a <?php if ($this_page == 'audit_projectBookings.php') {echo $setActiveLink;}?> href="../project/log"><span><?php echo $lang['PROJECT_LOGS']; ?></span></a></li>
                          <?php endif; ?>
                          <li><a <?php if ($this_page == 'options.php') {echo $setActiveLink;}?> href="../project/options"><span>Workflow</span></a></li>
                      </ul>
                  </div>
              </div>
          </div>
          <?php if ($this_page == 'project_view.php' || $this_page == 'project_detail.php' || $this_page == "audit_projectBookings.php" || $this_page == "options.php") {
              echo "<script>$('#adminOption_PROJECT').click();</script>";
          } ?>
      <?php endif; ?>

      <!-- Section Four: REPORTS -->
      <?php if ($isReportAdmin == 'TRUE' || $canEditTemplates == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-report"  id="adminOption_REPORT"><i class="fa fa-caret-down pull-right"></i>
            <i class="fa fa-bar-chart"></i><?php echo $lang['REPORTS']; ?>
            </a>
          </div>
          <div id="collapse-report" class="panel-collapse collapse" role="tabpanel" >
            <div class="panel-body">
              <ul class="nav navbar-nav">
              <?php if ($isReportAdmin == 'TRUE'): ?>
                <li><a target="_blank" href="../report/send"><span> Send E-Mails </span></a></li>
                <li><a <?php if ($this_page == 'report_productivity.php') {echo $setActiveLink;}?> href="../report/productivity"><span><?php echo $lang['PRODUCTIVITY']; ?></span></a></li>
              <?php endif;?>
                <li><a <?php if ($this_page == 'templateSelect.php') {echo $setActiveLink;}?> href="../report/designer"><span>Report Designer</span> </a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php if ($this_page == "report_productivity.php" || $this_page == 'templateSelect.php') {
            echo "<script>$('#adminOption_REPORT').click();</script>";
        } ?>
      <?php endif; ?>
      <!-- Section Five: ERP -->
      <?php if ($isERPAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading" role="tab">
            <a role="button" data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-erp"  id="adminOption_ERP"><i class="fa fa-caret-down pull-right"></i><i class="fa fa-file-text-o"></i> ERP</a>
          </div>
          <div id="collapse-erp" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if ($this_page == 'offer_proposal_edit.php') {echo $setActiveLink;}?> href="../erp/view"><span><?php echo $lang['PROCESS']; ?></span></a></li>
                <li><a id="erpClients" href="#" data-toggle="collapse" data-target="#toggleERPClients" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['CLIENTS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleERPClients">
                      <ul class="nav nav-list">
                          <li><a <?php if (isset($_GET['t']) && $_GET['t'] == 'ang') { echo $setActiveLink; } ?> href="../erp/view?t=ang"><span><?php echo $lang['PROPOSAL_TOSTRING']['ANG']; ?></span></a></li>
                          <li><a href="../erp/view?t=aub"><span><?php echo $lang['PROPOSAL_TOSTRING']['AUB']; ?></span></a></li>
                          <li><a href="../erp/view?t=re"><span><?php echo $lang['PROPOSAL_TOSTRING']['RE']; ?></span></a></li>
                          <li><a href="../erp/view?t=lfs"><span><?php echo $lang['PROPOSAL_TOSTRING']['LFS']; ?></span></a></li>
                          <li><a href="../system/clients"><span><?php echo $lang['ADDRESS_BOOK']; ?></span></a></li>
                      </ul>
                  </div>
                </li>
                <li><a id="erpSuppliers" href="#" data-toggle="collapse" data-target="#toggleERPSuppliers" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['SUPPLIERS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleERPSuppliers">
                    <ul class="nav nav-list">
                      <li><a disabled href=""><span><?php echo $lang['ORDER']; ?></span></a></li>
                      <li><a disabled href=""><span><?php echo $lang['INCOMING_INVOICE']; ?></span></a></li>
                      <li><a href="../system/clients"><span><?php echo $lang['ADDRESS_BOOK']; ?></span></a></li>
                    </ul>
                  </div>
                </li>
                <li>
                    <a id="articleToggle" <?php if($this_page =='product_articles.php'){echo $setActiveLink;}?> href="#" data-toggle="collapse" data-target="#toggleArticle" data-parent="#sidenav01" class="collapse in">
                        <span><?php echo $lang['ARTICLE']; ?></span> <i class="fa fa-caret-down"></i>
                    </a>
                    <div class="collapse" id="toggleArticle" style="height: 0px;">
                        <ul class="nav nav-list">
                            <?php
                            $result = $conn->query("SELECT * FROM $companyTable");
                            while ($result && ($row = $result->fetch_assoc())) {
                                if (in_array($row['id'], $available_companies)) {
                                    echo "<li><a href='../erp/articles?cmp=" . $row['id'] . "'>" . $row['name'] . "</a></li>";
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </li>
                <li><a <?php if($this_page =='receiptBook.php'){echo $setActiveLink;}?> href="../erp/receipts"><span><?php echo $lang['RECEIPT_BOOK']; ?></span></a></li>
                <li><a disabled href=""><span><?php echo $lang['VACANT_POSITIONS']; ?></span></a></li>

                <li><a id="erpSettings" href="#" data-toggle="collapse" data-target="#toggleERPSettings" data-parent="#sidenav01" class="collapsed">
                    <span><?php echo $lang['SETTINGS']; ?></span> <i class="fa fa-caret-down"></i>
                  </a>
                  <div class="collapse" id="toggleERPSettings">
                    <ul class="nav nav-list">
                      <li><a <?php if ($this_page == 'editTaxes.php') {echo $setActiveLink;}?> href="../erp/taxes"><span><?php echo $lang['TAX_RATES']; ?></span></a></li>
                      <li><a <?php if ($this_page == 'editUnits.php') {echo $setActiveLink;}?> href="../erp/units"><span><?php echo $lang['UNITS']; ?></span></a></li>
                      <li><a <?php if ($this_page == 'editPaymentMethods.php') {echo $setActiveLink;}?> href="../erp/payment"><span><?php echo $lang['PAYMENT_METHODS']; ?></span></a></li>
                      <li><a <?php if ($this_page == 'editShippingMethods.php') {echo $setActiveLink;}?> href="../erp/shipping"><span><?php echo $lang['SHIPPING_METHODS']; ?></span></a></li>
                      <li><a <?php if ($this_page == 'editRepres.php') {echo $setActiveLink;}?> href="../erp/representatives"><span><?php echo $lang['REPRESENTATIVE']; ?></span></a></li>
                    </ul>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if ($this_page == "erp_view.php" || $this_page == "erp_process.php") {
            echo "<script>$('#adminOption_ERP').click();$('#erpClients').click();</script>";
        } elseif ($this_page == "editTaxes.php" || $this_page == "editUnits.php" || $this_page == "editPaymentMethods.php" || $this_page == "editShippingMethods.php" || $this_page == "editRepres.php") {
            echo "<script>$('#adminOption_ERP').click();$('#erpSettings').click();</script>";
        } elseif ($this_page == "product_articles.php" || $this_page == "receiptBook.php") {
            echo "<script>document.getElementById('articleToggle').click();$('#adminOption_ERP').click();</script>";
        }
        ?>
      <?php endif;?>
      <!-- Section Six: FINANCES -->
      <?php if ($isFinanceAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-finance"  id="adminOption_FINANCE"><i class="fa fa-caret-down pull-right"></i><i class="fa fa-book"></i><?php echo $lang['FINANCES']; ?></a>
          </div>
          <div id="collapse-finance" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <?php
                if(count($available_companies) == 2){
                    echo '<li><a href="../finance/plan?n='.$available_companies[1].'">'.$lang['ACCOUNT_PLAN'].'</a></li>';
                    echo '<li><a href="../finance/journal?n='.$available_companies[1].'">'.$lang['ACCOUNT_JOURNAL'].'</a></li>';
                    $acc_res = $conn->query("SELECT id, name, companyID FROM accounts WHERE manualBooking='TRUE' AND companyID = ".$available_companies[1]);
                    while($acc_res && ($acc_row = $acc_res->fetch_assoc())){
                        echo '<li><a href="../finance/account?v='.$acc_row['id'].'">'.$acc_row['name'].'</a></li>';
                        if($this_page == 'accounting.php' && !empty($_GET['v']) && $_GET['v'] == $acc_row['id']){
                            echo "<script>$('#finance-click-".$acc_row['companyID']."').click();</script>";
                        }
                    }
                } else {
                    $result = $conn->query("SELECT id, name FROM $companyTable WHERE id IN (".implode(', ', $available_companies).")");
                    while($result && ($row = $result->fetch_assoc())){
                        echo '<li>';
                        echo '<a id="finance-click-'.$row['id'].'" href="#" data-toggle="collapse" data-target="#tfinances-'.$row['id'].'" data-parent="#sidenav01" class="collapsed">'.$row['name'].' <i class="fa fa-caret-down"></i></a>';
                        echo '<div class="collapse" id="tfinances-'.$row['id'].'" >';
                        echo '<ul class="nav nav-list">';
                        echo '<li><a href="../finance/plan?n='.$row['id'].'">'.$lang['ACCOUNT_PLAN'].'</a></li>';
                        echo '<li><a href="../finance/journal?n='.$row['id'].'">'.$lang['ACCOUNT_JOURNAL'].'</a></li>';
                        $acc_res = $conn->query("SELECT id, name, companyID FROM accounts WHERE manualBooking='TRUE' AND companyID = ".$row['id']);
                        while($acc_res && ($acc_row = $acc_res->fetch_assoc())){
                            echo '<li><a href="../finance/account?v='.$acc_row['id'].'">'.$acc_row['name'].'</a></li>';
                            if($this_page == 'accounting.php' && !empty($_GET['v']) && $_GET['v'] == $acc_row['id']){
                                echo "<script>$('#finance-click-".$acc_row['companyID']."').click();</script>";
                            }
                        }
                        echo '</ul></div></li>';
                    }
                }
                ?>
                <li><a <?php if($this_page == 'editTaxes.php'){echo $setActiveLink;}?> href="../erp/taxes"><span><?php echo $lang['TAX_RATES']; ?></span></a></li>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if ($this_page == "accounting.php" || $this_page == 'accountPlan.php' || $this_page == 'accountJournal.php') {
            echo "<script>$('#adminOption_FINANCE').click();";
            if (isset($_GET['n'])) {
                echo "$('#finance-click-" . $_GET['n'] . "').click();";
            }
            echo '</script>';
        }
        ?>
      <?php endif;?>
      <!-- Section Six: DSGVO -->
      <?php if ($isDSGVOAdmin == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-dsgvo"  id="adminOption_DSGVO"><i class="fa fa-caret-down pull-right"></i><strong style="padding: 0px 6px;"> § </strong>DSGVO</a>
          </div>
          <div id="collapse-dsgvo" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <?php
                if(count($available_companies) == 2){
                  $isActivePanel = true;
                  $isActive = ($isActivePanel && $this_page == 'dsgvo_view.php') ? $setActiveLink : "";
                  echo '<li><a '.$isActive.' href="../dsgvo/documents?n='.$available_companies[1].'">'.$lang['DOCUMENTS'].'</a></li>';
                  $isActive = ($isActivePanel && ($this_page == 'dsgvo_vv.php' || $this_page == "dsgvo_edit.php" || $this_page == "dsgvo_vv_detail.php" || $this_page == "dsgvo_vv_templates.php" || $this_page == "dsgvo_vv_template_edit.php" || $this_page == 'dsgvo_data_matrix.php')) ? $setActiveLink : "";
                  echo '<li><a '.$isActive.' href="../dsgvo/vv?n='.$available_companies[1].'" >'.$lang['PROCEDURE_DIRECTORY'].'</a></li>';
                  $isActive = ($isActivePanel && $this_page == 'dsgvo_mail.php') ? $setActiveLink : "";
                  echo '<li><a '.$isActive.' href="../dsgvo/templates?n='.$available_companies[1].'">E-Mail Templates</a></li>';
                  $isActive = ($isActivePanel && $this_page == 'dsgvo_training.php') ? $setActiveLink : "";
                  echo '<li><a '.$isActive.' href="../dsgvo/training?n='.$available_companies[1].'" >Schulung</a></li>';
                  $isActive = ($isActivePanel && $this_page == 'dsgvo_log.php') ? $setActiveLink : "";
                  echo '<li><a '.$isActive.' href="../dsgvo/log?n='.$row['id'].'" >Logs</a></li>';
                } else {
                  $result = $conn->query("SELECT id, name FROM $companyTable WHERE id IN (".implode(', ', $available_companies).")");
                  while($result && ($row = $result->fetch_assoc())){
                    $isActivePanel = isset($_GET['n']) && ($row['id'] ==  $_GET['n']); //only highlight the current page in the tab for the current company
                    echo '<li>';
                    echo '<a href="#" data-toggle="collapse" data-target="#tdsgvo-'.$row['id'].'" data-parent="#sidenav01" class="collapsed">'.$row['name'].' <i class="fa fa-caret-down"></i></a>';
                    echo '<div class="collapse" id="tdsgvo-'.$row['id'].'" >';
                    echo '<ul class="nav nav-list">';
                    $isActive = ($isActivePanel && $this_page == 'dsgvo_view.php') ? $setActiveLink : "";
                    echo '<li><a '.$isActive.' href="../dsgvo/documents?n='.$row['id'].'">'.$lang['DOCUMENTS'].'</a></li>';
                    $isActive = ($isActivePanel && ($this_page == 'dsgvo_vv.php' || $this_page == "dsgvo_edit.php" || $this_page == "dsgvo_vv_detail.php" || $this_page == "dsgvo_vv_templates.php" || $this_page == "dsgvo_vv_template_edit.php" || $this_page == 'dsgvo_data_matrix.php')) ? $setActiveLink : "";
                    echo '<li><a '.$isActive.' href="../dsgvo/vv?n='.$row['id'].'" >'.$lang['PROCEDURE_DIRECTORY'].'</a></li>';
                    $isActive = ($isActivePanel && $this_page == 'dsgvo_mail.php') ? $setActiveLink : "";
                    echo '<li><a '.$isActive.' href="../dsgvo/templates?n='.$row['id'].'">E-Mail Templates</a></li>';
                    $isActive = ($isActivePanel && $this_page == 'dsgvo_training.php') ? $setActiveLink : "";
                    echo '<li><a '.$isActive.' href="../dsgvo/training?n='.$row['id'].'" >Schulung</a></li>';
                    $isActive = ($isActivePanel && $this_page == 'dsgvo_log.php') ? $setActiveLink : "";
                    echo '<li><a '.$isActive.' href="../dsgvo/log?n='.$row['id'].'" >Logs</a></li>';
                    echo '</ul></div></li>';
                  }
                }
                ?>
              </ul>
            </div>
          </div>
        </div>
        <?php
        if ($this_page == "dsgvo_view.php" || $this_page == "dsgvo_edit.php" || $this_page == "dsgvo_mail.php" || $this_page == "dsgvo_vv.php"
            || $this_page == "dsgvo_vv_detail.php" || $this_page == "dsgvo_vv_templates.php" || $this_page == "dsgvo_vv_template_edit.php"
            || $this_page == "dsgvo_training.php" || $this_page == "dsgvo_log.php" || $this_page == 'dsgvo_data_matrix.php') {
            echo "<script>$('#adminOption_DSGVO').click();";
            if (isset($_GET['n'])) {
                echo "$('#tdsgvo-" . $_GET['n'] . "').toggle();";
            }
            echo '</script>';
        }
        ?>
      <?php endif;?>
      <!-- Section Seven: ARCHIVE -->
      <?php if ($canUseArchive == 'TRUE'): ?>
        <div class="panel panel-default panel-borderless">
          <div class="panel-heading">
            <a data-toggle="collapse" data-parent="#sidebar-accordion" href="#collapse-archives"  id="adminOption_ARCHIVE"><i class="fa fa-caret-down pull-right"></i><i class="fa fa-folder-open-o"></i><?php echo $lang['ARCHIVE'] ?></a>
          </div>
          <div id="collapse-archives" class="panel-collapse collapse">
            <div class="panel-body">
              <ul class="nav navbar-nav">
                <li><a <?php if ($this_page == 'archive_share.php') {echo $setActiveLink;}?> href="../archive/share" data-parent="#sidenav01" class="collapsed"><?php echo $lang['SHARE'] ?></a></li>
                <li><a <?php if ($this_page == 'private_view.php') {echo $setActiveLink;}?> href="../archive/private" data-parent="#sidenav01" class="collapsed"><?php echo $lang['PRIVATE'] ?></a></li>
              </ul>
            </div>
          </div>
        </div>
      <?php
        if ($this_page == "archive_share.php" || $this_page == "private_view.php") {
            echo "<script>$('#adminOption_ARCHIVE').click();</script>";
        }
        ?>
      <?php endif;?>
      <!-- END SECTIONS -->
      <br><br>
    </div> <!-- /accordions -->
    <br><br><br>
  </div>
</div>
<!-- /side menu -->

<div id="bodyContent" style="display:none;" >
  <div class="affix-content">
    <div class="container-fluid">
      <span><?php echo $validation_output; ?></span>
      <span><?php echo $error_output; ?></span>

      <?php
      $result = $conn->query("SELECT expiration, expirationDuration, expirationType FROM policyData"); echo $conn->error;
      $row = $result->fetch_assoc();
      if($row['expiration'] == 'TRUE' || $forcedPwdChange){ //can a password expire?
          $pswDate = date('Y-m-d', strtotime("+".$row['expirationDuration']." months", strtotime($userdata['lastPswChange'])));
          if(timeDiff_Hours($pswDate, getCurrentTimestamp()) > 0 || $forcedPwdChange){ //has my password actually expired?
              showError('<strong>Your Password has expired. </strong> Please change it by clicking on the gears in the top right corner.');
              if($row['expirationType'] == 'FORCE' || $forcedPwdChange){ //force the change
                  include 'footer.php';
                  die();
              }
          }
      }
      $user_agent = $_SERVER["HTTP_USER_AGENT"];
      if (strpos($user_agent, 'MSIE') || strpos($user_agent, 'Trident/7') || strpos($user_agent, 'Edge')) {
          showError('Der Browser den Sie verwenden ist veraltet oder unterstützt wichtige Funktionen nicht. Wenn Sie Probleme mit der Anzeige oder beim Interagieren bekommen, versuchen sie einen anderen Browser.');
      }
      ?>
