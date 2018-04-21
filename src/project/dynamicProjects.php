<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['play'])){
        $ckIn_disabled = 'disabled';
    }
}
include dirname(__DIR__) . '/header.php';
require dirname(__DIR__) . "/misc/helpcenter.php";
require dirname(__DIR__) . "/Calculators/dynamicProjects_ProjectSeries.php";
function generate_progress_bar($current, $estimate, $referenceTime = 8){ //$referenceTime is the time where the progress bar overall length hits 100% (it can't go over 100%)
    $allHours = 0;
    $times = explode(' ', $estimate);
    foreach($times as $t){
        if(is_numeric($t) || substr($t, -1) == 'h'){
            $allHours += floatval($t);
        } elseif(substr($t, -1) == 'M'){
            $allHours += intval($t) * 730.5;
        } elseif(substr($t, -1) == 'w'){
            $allHours += intval($t) * 168;
        } elseif(substr($t, -1) == 't'){
            $allHours += intval($t) * 24;
        } elseif(substr($t, -1) == 'm'){
            $allHours += intval($t) / 60;
        }
    }

    if($current < $allHours){
        $yellowBar = $current/($allHours+0.0001);
        $greenBar = 1-$yellowBar;
        $timeLeft = $allHours - $current;
        $redBar = 0;
        $timeOver = 0;
    } else {
        $timeOver = $current - $allHours;
        $timeLeft = 0;
        $greenBar = 0;
        $yellowBar = ($allHours)/($timeLeft + $timeOver + $current + 0.0001);
        $redBar = 1-$yellowBar;
        $current = $allHours;
    }
    $greenBar *= 100;
    $yellowBar *= 100;
    $redBar *= 100;
    //TODO: change title
    $progressLength = min(($timeLeft + $timeOver + $current)/$referenceTime, 1);
    return '<div style="height:5px;margin-bottom:2px;width:'.($progressLength * 100).'%" class="progress">'
    .'<div data-toggle="tooltip" title="'.round($current, 2).' Stunden" class="progress-bar progress-bar-warning" style="height:10px;width:'.$yellowBar.'%"></div>'
    .'<div data-toggle="tooltip" title="'.round($timeLeft,2).' Stunden" class="progress-bar progress-bar-success" style="height:10px;width:'.$greenBar.'%"></div>'
    .'<div data-toggle="tooltip" title="'.round($timeOver,2).' Stunden" class="progress-bar progress-bar-danger" style="height:10px;width:'.$redBar.'%"></div></div>';
}

$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0, 'tasks' => 'ACTIVE', "priority" => 0, 'employees' => ["user;".$userID]); //set_filter requirement
$result = $conn->query("SELECT teamID FROM teamRelationshipData WHERE userID = $userID");
while($result && ( $row = $result->fetch_assoc())){
    $filterings['employees'][] = 'team;'.$row['teamID'];
}
?>
<div class="page-header-fixed">
<div class="page-header"><h3>Tasks<div class="page-header-button-group">
    <?php include dirname(__DIR__) . '/misc/set_filter.php'; ?>
    <?php if($isDynamicProjectsAdmin == 'TRUE'|| $canCreateTasks == 'TRUE'): ?>
        <div class="dropdown" style="display:inline;">
            <button class="btn btn-default dropdown-toggle" id="dropdownAddTask" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" type="button"><i class="fa fa-plus"></i></button>
            <ul class="dropdown-menu" aria-labelledby="dropdownAddTask" >
                <div class="container-fluid">
                    <li ><button class="btn btn-default btn-block" data-toggle="modal" data-target="#editingModal-" >New</button></li>
                    <li class="divider"></li>
                    <li ><button class="btn btn-default btn-block" data-toggle="modal" data-target="#template-list-modal" >From Template</button></li>
                </div>
            </ul>
        </div>
    <?php endif; ?>
</div></h3></div>
</div>
<div class="page-content-fixed-100">
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['play']) || !empty($_POST['play-take'])){
        if(!empty($_POST['play'])){
            $x = test_input($_POST['play'], true);
        } else {
            $x = test_input($_POST['play-take'], true);
            $conn->query("UPDATE dynamicprojects SET projectleader = $userID WHERE projectid = '$x'");
            $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'DUTY', $userID)");
        }
        $result = $conn->query("SELECT id FROM projectBookingData WHERE `end` = '0000-00-00 00:00:00' AND dynamicID = '$x'");
        if($result->num_rows < 1){
            $result = mysqli_query($conn, "SELECT indexIM FROM logs WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00' LIMIT 1");
            if($result && ($row = $result->fetch_assoc())){
                $indexIM = $row['indexIM'];
                //TODO: make sure play does not overlap an existing booking and user does not have a lücke in the previous buchung
                //$result = $conn->query("SELECT start, end FROM projectBookingData WHERE timestampID = $indexIM");
                $conn->query("INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType, dynamicID)
                VALUES(UTC_TIMESTAMP, '0000-00-00 00:00:00', $indexIM, 'Begin of Task $x' , 'project', '$x')");
                if($conn->error){
                    showError($conn->error);
                } else {
                    showSuccess('Task wurde gestartet. Solange dieser läuft, ist Ausstempeln nicht möglich.');
                }
            } else {
                showError('<strong>Bitte einstempeln.</strong> Tasks können nur angenommen werden, sofern man eingestempelt ist.');
            }
        } else {
            showError('<strong>Task besetzt.</strong> Dieser Task gehört schon jemandem.');
        }
    } elseif(!empty($_POST['task-plan-date']) && (!empty($_POST['task-plan']) || !empty($_POST['task-plan-take']))){
        if(!empty($_POST['task-plan-take'])){
            $x = test_input($_POST['task-plan-take'], 1);
            $conn->query("UPDATE dynamicprojects SET projectleader = $userID WHERE projectid = '$x'");
        } else {
            $x = test_input($_POST['task-plan'], 1);
        }
        if(test_Date($_POST['task-plan-date'].':00')){
            $date = carryOverAdder_Hours($_POST['task-plan-date'].':00', $timeToUTC * -1);
            $conn->query("UPDATE dynamicprojects SET projectstart = '$date' WHERE projectid = '$x'");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_SAVE']);
            }
        } else {
            showError('Datum ungültig. Format YYYY-MM-DD HH:mm');
        }
    } elseif(!empty($_POST['take_task'])){
        $x = test_input($_POST['take_task'], true);
        $conn->query("UPDATE dynamicprojects SET projectleader = $userID WHERE projectid = '$x'");
        if($conn->error){
            showError($conn->error);
        } else {
            $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'DUTY', $userID)");
            showSuccess($lang['OK_ADD']);
        }
    }
    if(!empty($_POST['createForgottenBooking']) && !empty($_POST['description']) && isset($_POST["time-range"])){
        $timeRange =  explode(";",$_POST["time-range"]); //format date;start;end;timeToUTC;indexIM
        $date = $timeRange[0];
        $start = $timeRange[1];
        $end = $timeRange[2];
        $timeToUTC = intval($timeRange[3]);
        $indexIM = intval($timeRange[4]);
        $x = $dynamicID = test_input($_POST["createForgottenBooking"], true);
        $result = $conn->query("SELECT clientprojectid, needsreview FROM dynamicprojects WHERE projectid = '$x'");
        if(!test_Time($end) || !test_Time($start) || !$result){
            echo "invalid time format or project id";
        } else {
            $row = $result->fetch_assoc();
            $projectID = $row['clientprojectid'] ? $row['clientprojectid'] : intval($_POST['bookDynamicProjectID']);
            $description = test_input($_POST['description']);
            $startDate = $date." ".$start;
            $startDate = carryOverAdder_Hours($startDate, $timeToUTC * -1);
            $endDate = $date." ".$end;
            $endDate = carryOverAdder_Hours($endDate, $timeToUTC * -1);
            if(timeDiff_Hours($startDate, $endDate) < 0){
                $endDate = carryOverAdder_Hours($endDate, 24);
                $date = substr($endDate, 0, 10);
            }
            if(timeDiff_Hours($startDate, $endDate) > 0 && timeDiff_Hours($startDate, $endDate) < 12){
                $percentage = intval($_POST['bookCompleted']);
                if($percentage == 100 || isset($_POST['bookCompletedCheckbox'])){
                    $percentage = 100; //safety
                    if($row['needsreview'] == 'TRUE'){
                        $conn->query("UPDATE dynamicprojects SET projectstatus = 'REVIEW' WHERE projectid = '$dynamicID'");
                    } else {
                        $conn->query("UPDATE dynamicprojects SET projectstatus = 'COMPLETED' WHERE projectid = '$dynamicID'");
                    }
                }
                $conn->query("UPDATE dynamicprojects SET projectpercentage = $percentage WHERE projectid = '$dynamicID'");
                //normal booking
                $sql = "INSERT INTO projectBookingData (start, end, projectID, timestampID, infoText, internInfo, bookingType, dynamicID)
                VALUES('$startDate', '$endDate', $projectID, $indexIM, '$description', '$percentage% Abgeschlossen', 'project', '$dynamicID')";
                $conn->query($sql);
                echo $conn->error;
            } else {
                echo "invalid time";
            }
        }
    }
    if(!empty($_POST['createBooking']) && (!empty($_POST['completeWithoutBooking']) || !empty($_POST['description']))){
        $bookingID = test_input($_POST['createBooking']);
        $result = $conn->query("SELECT dynamicID, clientprojectid, needsreview FROM projectBookingData p, dynamicprojects d WHERE id = $bookingID AND d.projectid = p.dynamicID");
        if($row = $result->fetch_assoc()){
            $dynamicID = $row['dynamicID'];
            $projectID = $row['clientprojectid'];
            if(!$projectID && !empty($_POST['bookDynamicProjectID'])) $projectID = intval($_POST['bookDynamicProjectID']); //5acaff282ced0
            if($projectID || !empty($_POST['completeWithoutBooking'])){
                $result->free();
                $percentage = intval($_POST['bookCompleted']);
                if($percentage == 100 || isset($_POST['bookCompletedCheckbox'])){
                    $percentage = 100; //safety
                    if($row['needsreview'] == 'TRUE'){
                        $conn->query("UPDATE dynamicprojects SET projectstatus = 'REVIEW' WHERE projectid = '$dynamicID'");
                    } else {
                        $conn->query("UPDATE dynamicprojects SET projectstatus = 'COMPLETED' WHERE projectid = '$dynamicID'");
                    }
                }
                $conn->query("UPDATE dynamicprojects SET projectpercentage = $percentage WHERE projectid = '$dynamicID'");
                $microtasks = $conn->query("SELECT microtaskid, ischecked FROM microtasks WHERE projectid='$dynamicID'");
                if($microtasks){
                    while($microrow = $microtasks->fetch_assoc()){
                        if($microrow['ischecked']=='FALSE'){
                            if(isset($_POST["mtask".$microrow['microtaskid']])){ // IS ALLWAYS SET?
                                $conn->query("UPDATE microtasks SET ischecked='TRUE', finisher = $userID, completed = CURRENT_TIMESTAMP WHERE microtaskid = '".$microrow['microtaskid']."'");
                            }
                        }
                    }
                }
                if(!empty($_POST['completeWithoutBooking'])){
                    $conn->query("DELETE FROM projectBookingData WHERE id = $bookingID");
                } else {
                    $description = $dynamicID.' - '.test_input($_POST['description']); //5ac9e5167c616
                    $conn->query("UPDATE projectBookingData SET end = UTC_TIMESTAMP, infoText = '$description', projectID = '$projectID', internInfo = '$percentage% von $dynamicID Abgeschlossen' WHERE id = $bookingID");
                }
                if($conn->error){
                    showError($conn->error);
                } else {
                    redirect('view'); //to refresh the disabled check out button. i know.
                }
            } else {
                showError($lang['ERROR_MISSING_SELECTION'].' (Projekt)');
            }
        } else { //STRIKE
            $conn->query("UPDATE UserData SET strikeCount = strikecount + 1 WHERE id = $userID");
            showError('<strong>Project not Available.</strong> '.$lang['ERROR_STRIKE']);
        }
    }
    if($isDynamicProjectsAdmin == 'TRUE' || $canCreateTasks == 'TRUE'){
        if(!empty($_POST['deleteProject'])){
            $val = test_input($_POST['deleteProject']);
            $conn->query("DELETE FROM dynamicprojectslogs WHERE projectid = '$val'");
            $conn->query("DELETE FROM dynamicprojects WHERE projectid = '$val'");
            if($conn->error){
                showError($conn->error);
            } else {
                showSuccess($lang['OK_DELETE']);
            }
        }
        if(isset($_POST['editDynamicProject'])){ //new projects
            if(isset($available_companies[1]) && !empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['owner']) && test_Date($_POST['start'], 'Y-m-d') && !empty($_POST['employees'])){
                $id = uniqid();
                if(!empty($_POST['editDynamicProject'])){ //existing
                    $id =  test_input($_POST['editDynamicProject']);
                    $conn->query("DELETE FROM dynamicprojects WHERE projectid = '$id'"); echo $conn->error; //fk does the rest
                    $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$id', 'EDITED', $userID)");
                } else { //new
                    $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$id', 'CREATED', $userID)");
                }
                $null = null;
                $name = test_input($_POST["name"]);
                $description = $_POST["description"];
                if(preg_match_all("/\[([^\]]*)\]\s*\{([^\[]*)\}/m",$description,$matches)&&count($matches[0])>0){
                    for($i = 0;$i<count($matches[0]);$i++){
                        $mname = strip_tags($matches[1][$i]);
                        $info = strip_tags($matches[2][$i]);
                        $mid = uniqid();
                        $conn->query("INSERT INTO microtasks VALUES('$id','$mid','$mname','FALSE',null,null)");
                        $checkbox = "<input type='checkbox' id='$mid' disabled title=''><b>".$mname."</b><br>".$info."</input>";
                        $mname = preg_quote($mname);
                        $description = preg_replace("/\[($mname)\]\s*\{([^\[]*)\}/m",$checkbox,$description,1);
                        if($conn->error){
                            showError($conn->error);
                        }
                    }
                }
                $company = $_POST["filterCompany"] ?? $available_companies[1];
                $client = isset($_POST['filterClient']) ? intval($_POST['filterClient']) : '';
                $project = isset($_POST['filterProject']) ? intval($_POST['filterProject']) : '';
                $color = $_POST["color"] ? test_input($_POST['color']) : '#FFFFFF';
                $start = $_POST["start"];
                $end = $_POST["endradio"];
                $status = $_POST["status"];
                $priority = intval($_POST["priority"]); //1-5
                $owner = $_POST['owner'] ? intval($_POST["owner"]) : $userID;
                $leader = isset($_POST['leader']) ? intval($_POST['leader']) : '';
                $percentage = intval($_POST['completed']);
                $estimate = test_input($_POST['estimatedHours']);
                $isTemplate = isset($_POST['isTemplate']) ? 'TRUE' : 'FALSE';
                if($isDynamicProjectsAdmin == 'TRUE'){
                    $skill = intval($_POST['projectskill']);
                    $parent = test_input($_POST["parent"]); //dynamproject id
                }else{
                    $skill = 0;
                    $parent = null;
                }
                if($status == 'COMPLETED') $percentage = 100;
                if(!empty($_POST['projecttags'])){
                    $tags = implode(',', array_map( function($data){ return preg_replace("/[^A-Za-z0-9]/", '', $data); }, $_POST['projecttags'])); //strictly map and implode the tags
                } else {
                    $tags = '';
                }
                if ($end == "number") {
                    $end = $_POST["endnumber"] ?? "";
                } elseif ($end == "date") {
                    $end = $_POST["enddate"] ?? "";
                }
                $series = $_POST["series"] ?? "once";
                $series = new ProjectSeries($series, $start, $end);
                $series->daily_days = (int) $_POST["daily_days"] ?? 1;
                $series->weekly_weeks = (int) $_POST["weekly_weeks"] ?? 1;
                $series->weekly_day = $_POST["weekly_day"] ?? "monday";
                $series->monthly_day_of_month_day = (int) $_POST["monthly_day_of_month_day"] ?? 1;
                $series->monthly_day_of_month_month = (int) $_POST["monthly_day_of_month_month"] ?? 1;
                $series->monthly_nth_day_of_week_nth = (int) $_POST["monthly_nth_day_of_week_nth"] ?? 1;
                $series->monthly_nth_day_of_week_day = $_POST["monthly_nth_day_of_week_day"] ?? "monday";
                $series->monthly_nth_day_of_week_month = (int) $_POST["monthly_nth_day_of_week_month"] ?? 1;
                $series->yearly_nth_day_of_month_nth = (int) $_POST["yearly_nth_day_of_month_nth"] ?? 1;
                $series->yearly_nth_day_of_month_month = $_POST["yearly_nth_day_of_month_month"] ?? "JAN";
                $series->yearly_nth_day_of_week_nth = (int) $_POST["yearly_nth_day_of_week_nth"] ?? 1;
                $series->yearly_nth_day_of_week_day = $_POST["yearly_nth_day_of_week_day"] ?? "monday";
                $series->yearly_nth_day_of_week_month = $_POST["yearly_nth_day_of_week_month"] ?? "JAN";
                $nextDate = $series->get_next_date();
                $series = base64_encode(serialize($series));
                // PROJECT
                $stmt = $conn->prepare("INSERT INTO dynamicprojects(projectid, projectname, projectdescription, companyid, clientid, clientprojectid, projectcolor, projectstart, projectend, projectstatus,
                    projectpriority, projectparent, projectowner, projectleader, projectnextdate, projectseries, projectpercentage, estimatedHours, level, projecttags, isTemplate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssbiiissssisiisbisiss", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $leader, $nextDate, $null, $percentage, $estimate, $skill, $tags, $isTemplate);
                $stmt->send_long_data(2, $description);
                $stmt->send_long_data(12, $series);
                $stmt->execute();
                if(!$stmt->error){
                    $stmt->close();
                    //EMPLOYEES
                    $stmt = $conn->prepare("INSERT INTO dynamicprojectsemployees (projectid, userid, position) VALUES ('$id', ?, ?)"); echo $conn->error;
                    $stmt->bind_param("is", $employee, $position);
                    $position = 'normal';
                    foreach ($_POST["employees"] as $employee) {
                        $emp_array = explode(";", $employee);
                        if ($emp_array[0] == "user") {
                            $employee = intval($emp_array[1]);
                            $stmt->execute();
                        } else {
                            $team = intval($emp_array[1]);
                            $conn->query("INSERT INTO dynamicprojectsteams (projectid, teamid) VALUES ('$id',$team)");
                        }
                    }
                    if(isset($_POST['optionalemployees']) && !empty($_POST['optionalemployees'])){
                        $position = 'optional';
                        foreach ($_POST['optionalemployees'] as $optional_employee) {
                            $employee = intval($optional_employee);
                            $stmt->execute();
                        }
                    }
                    showSuccess($lang['OK_ADD']);
                } else {
                    showError($stmt->error);
                }
                $stmt->close();
            } else {
                showError($lang['ERROR_MISSING_FIELDS']);
                if(empty($_POST['owner'])) showError('Fehlend: Projektbesitzer');
                if(empty($_POST['employees'])) showError('Fehlend: Mitarbeiter');
            }
        }
    } // end if dynamic Admin
} //end if POST
$completed_tasks = file_get_contents('task_changelog.txt', true);
?>
<form method="POST">
<?php
if($filterings['tasks'] == 'ACTIVE_PLANNED'){
    echo '<div class="text-right"><button type="submit" formaction="../tasks/icalDownload" formtarget="_blank" class="btn btn-warning">Als .ical Downloaden</button></div>';
}
?>
<table class="table table-hover">
    <thead>
        <tr>
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_NAME"]; ?></th>
            <th><?php echo $lang["DESCRIPTION"]; ?></th>
            <th><?php echo $lang["COMPANY"].' - '.$lang["CLIENT"].' - '.$lang["PROJECT"]; ?></th>
            <th><?php echo $lang["BEGIN"]; ?></th>
            <th><?php echo $lang["END"]; ?></th>
            <th>Routine</th>
            <th>Status</th>
            <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?></th>
            <th><?php echo $lang["EMPLOYEE"]; ?></th>
            <th>Review</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $modals = $occupation = $query_filter = '';
        $filter_emps = $filter_team = '0';
        $priority_color = ['', '#2a5da1', '#0c95d9', '#9d9c9c', '#ff7600', '#ff0000'];
        if($filterings['company']){ $query_filter .= "AND d.companyid = ".intval($filterings['company']); }
        if($filterings['client']){ $query_filter .= " AND d.clientid = ".intval($filterings['client']); }
        if($filterings['project']){ $query_filter .= " AND d.clientprojectid = ".intval($filterings['project']); }
        if($filterings['tasks']){
            if($filterings['tasks'] != 'ACTIVE_PLANNED'){
                $query_filter .= " AND d.projectstart <= UTC_TIMESTAMP ";
            }
            if($filterings['tasks'] == 'ACTIVE_PLANNED'){
                $query_filter .= " AND d.projectstart > UTC_TIMESTAMP ";
            } elseif($filterings['tasks'] == 'REVIEW_1'){
                $query_filter .= " AND d.projectstatus = 'REVIEW' AND needsreview = 'TRUE' ";
            } elseif($filterings['tasks'] == 'REVIEW_2'){
                $query_filter .= " AND d.projectstatus = 'REVIEW' AND needsreview = 'FALSE' ";
            } else {
                $query_filter .= " AND d.projectstatus = '".test_input($filterings['tasks'])."' ";
            }
        }
        if($filterings['priority'] > 0){ $query_filter .= " AND d.projectpriority = ".$filterings['priority']; }
        foreach($filterings['employees'] as $val){
            $arr = explode(';', $val);
            if($arr[0] == 'user') $filter_emps .= " OR conemployees LIKE '%{$arr[1]}%'";
            if($arr[0] == 'team') $filter_team .= " OR conteamsids LIKE '%{$arr[1]}%'";
        }

        if($filter_emps || $filter_team) $query_filter .= " AND ($filter_emps OR $filter_team)";

        $result = $conn->query("SELECT id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND logs.userID = $userID AND `end` = '0000-00-00 00:00:00' LIMIT 1");
        $hasActiveBooking = $result->num_rows;
        if($isDynamicProjectsAdmin == 'TRUE'){ //see all access-legal tasks
            $result = $conn->query("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus,
                projectpriority, projectowner, projectleader, projectpercentage, projecttags, d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName,
                clientData.name AS clientName, projectData.name AS projectDataName, needsreview, estimatedHours, tbl.conemployees, tbl2.conteams, tbl2.conteamsids, tbl3.currentHours,
                tbl5.userID AS workingUser, tbl5.start AS workStart, tbl5.id AS workingID
                FROM dynamicprojects d
                LEFT JOIN ( SELECT projectid, GROUP_CONCAT(userid SEPARATOR ' ') AS conemployees FROM dynamicprojectsemployees GROUP BY projectid ) tbl ON tbl.projectid = d.projectid
                LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN projectData ON projectData.id = clientprojectid
                LEFT JOIN ( SELECT t.projectid, GROUP_CONCAT(teamData.name SEPARATOR ',<br>') AS conteams, GROUP_CONCAT(teamData.id SEPARATOR ' ') AS conteamsids FROM dynamicprojectsteams t
                    LEFT JOIN teamData ON teamData.id = t.teamid GROUP BY t.projectid ) tbl2 ON tbl2.projectid = d.projectid
                LEFT JOIN ( SELECT p.dynamicID, SUM(IFNULL(TIMESTAMPDIFF(SECOND, p.start, p.end)/3600,TIMESTAMPDIFF(SECOND, p.start, UTC_TIMESTAMP)/3600)) AS currentHours
                    FROM projectBookingData p GROUP BY dynamicID) tbl3 ON tbl3.dynamicID = d.projectid
                LEFT JOIN ( SELECT userID, dynamicID, p.id, p.start FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00') tbl5
                    ON tbl5.dynamicID = d.projectid
                WHERE d.isTemplate = 'FALSE' AND d.companyid IN (0, ".implode(', ', $available_companies).") $query_filter
                ORDER BY projectpriority DESC, projectstatus, projectstart ASC");
        } else {
            $result = $conn->query("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus,
                projectpriority, projectowner, projectleader, projectpercentage, projecttags, d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName,
                clientData.name AS clientName, projectData.name AS projectDataName, needsreview, estimatedHours, tbl.conemployees, tbl2.conteams, tbl2.conteamsids, tbl3.currentHours,
                tbl5.userID AS workingUser, tbl5.start AS workStart, tbl5.id AS workingID
                FROM dynamicprojects d
                LEFT JOIN ( SELECT projectid, GROUP_CONCAT(userid SEPARATOR ' ') AS conemployees FROM dynamicprojectsemployees GROUP BY projectid ) tbl ON tbl.projectid = d.projectid
                LEFT JOIN ( SELECT t.projectid, GROUP_CONCAT(teamData.name SEPARATOR ',<br>') AS conteams, GROUP_CONCAT(teamData.id SEPARATOR ' ') AS conteamsids FROM dynamicprojectsteams t
                    LEFT JOIN teamData ON teamData.id = t.teamid GROUP BY t.projectid ) tbl2 ON tbl2.projectid = d.projectid
                LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN projectData ON projectData.id = clientprojectid
                LEFT JOIN ( SELECT p.dynamicID, SUM(IFNULL(TIMESTAMPDIFF(SECOND, p.start, p.end)/3600,TIMESTAMPDIFF(SECOND, p.start, UTC_TIMESTAMP)/3600)) AS currentHours
                    FROM projectBookingData p GROUP BY dynamicID) tbl3 ON tbl3.dynamicID = d.projectid
                LEFT JOIN ( SELECT userID, dynamicID, p.id, p.start FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00') tbl5
                    ON tbl5.dynamicID = d.projectid
                LEFT JOIN dynamicprojectsemployees ON dynamicprojectsemployees.projectid = d.projectid
                LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid LEFT JOIN teamRelationshipData ON teamRelationshipData.teamID = dynamicprojectsteams.teamid
                WHERE d.isTemplate = 'FALSE' AND (dynamicprojectsemployees.userid = $userID OR d.projectowner = $userID OR (teamRelationshipData.userID = $userID AND teamRelationshipData.skill >= d.level))
                $query_filter ORDER BY projectpriority DESC, projectstatus, projectstart ASC");
        }

        /*
        //TODO: will be optimized later, below LEFT (!) join will make query return only 1 company ID, reason unknown
        LEFT JOIN ( SELECT activity, projectid FROM dynamicprojectslogs WHERE ((activity = 'VIEWED' AND userID = $userID) OR ((activity = 'CREATED' OR activity = 'EDITED') AND userID != $userID))
            AND id IN ( SELECT MAX(id) FROM dynamicprojectslogs GROUP BY projectid)) tbl4 ON tbl4.projectid = d.projectid
        */
        echo $conn->error;
        while($result && ($row = $result->fetch_assoc())){
            $x = $row['projectid'];
            $projectName = $row['projectname'];

            //$rowStyle = ($row['activity'] && $row['activity'] != 'VIEWED') ? 'style="color:#1689e7; font-weight:bold;"' : '';
            echo '<tr>';
            echo '<td>';
            //echo $row['activity'];
            if($row['estimatedHours'] || $row['currentHours']) echo generate_progress_bar($row['currentHours'], $row['estimatedHours']);
            echo '<i style="color:'.$row['projectcolor'].'" class="fa fa-circle"></i> '.$row['projectname'].' <div>';
            foreach(explode(',', $row['projecttags']) as $tag){
                if($tag) echo '<span class="badge">'.$tag.'</span> ';
            }
            echo '</div></td>';
            echo '<td><button type="button" class="btn btn-default view-modal-open" value="'.$x.'" >View</button></td>';
            echo '<td>'.$row['companyName'].'<br>'.$row['clientName'].'<br>'.$row['projectDataName'].'</td>';
            $A = substr(carryOverAdder_Hours($row['projectstart'], $timeToUTC),0,10);
            $B = $row['projectend'] == '0000-00-00 00:00:00' ? '' : substr($row['projectend'],0,10);
            echo '<td>'.$A.'</td>';
            echo '<td>'.$B.'</td>';
            if($row['projectseries']){
                echo '<td><i class="fa fa-clock-o"></i></td>';
            } else {
                echo '<td><i class="fa fa-times" style="color:red" title="Keine Routine"></i></td>';
            }
            echo '<td>';
            if($row['workingUser']){ echo 'WORKING<br><small>'.$userID_toName[$row['workingUser']].'</small>'; } else { echo $row['projectstatus']; }
            if($row['projectstatus'] != 'COMPLETED'){ echo ' ('.$row['projectpercentage'].'%)'; }
            echo '<br><small style="color:transparent;">'.$x.'</small>';
            echo '</td>';
            echo '<td style="color:white;"><span class="badge" style="background-color:'.$priority_color[$row['projectpriority']].'" title="'.$lang['PRIORITY_TOSTRING'][$row['projectpriority']].'">'.$row['projectpriority'].'</span></td>';
            echo '<td>'; //employees
            if($row['projectleader'] == $row['projectowner']){
                echo '<u title="Besitzer und Verantwortlicher Mitarbeiter"><b>'. $userID_toName[$row['projectowner']].'</b></u>,<br>';
            } else {
                echo '<b title="Besitzer">'. $userID_toName[$row['projectowner']].'</b>,<br>';
                if($row['projectleader']) echo '<u title="Verantwortlicher Mitarbeiter">'.$userID_toName[$row['projectleader']].'</u>,<br>';
            }
            echo implode(',<br>', array_map(function($val){ global $userID_toName; if(isset($userID_toName[$val])) return $userID_toName[$val]; }, explode(' ',$row['conemployees'])));
            //foreach(explode(' ',$row['conemployees']) as $val){ if(isset($userID_toName[$val])) echo $userID_toName[$val].',<br>'; };
            if($row['conemployees'] && $row['conteams']) echo ',<br>';
            echo $row['conteams'];
            echo '</td>';
            echo '<td>';
            if(($isDynamicProjectsAdmin == 'TRUE' || $row['projectowner'] == $userID)){
                $checked = $row['needsreview'] == 'TRUE' ? 'checked' : '';
                echo '<input type="checkbox" onchange="reviewChange(event,\''.$x.'\')" '.$checked.'/>';
            }
            if(strpos($completed_tasks, $x) !== false) echo '<i class="fa fa-check" style="color:#00cf65" title="In aktueller Version vorhanden"></i>';
            echo '</td>';
            echo '<td>';
            if($row['workingUser'] == $userID) {
                $disabled = (time() - strtotime($row['workStart']) > 60) ? 'title="Task stoppen"' : 'disabled title="1 Minute Wartezeit"';
                echo '<button class="btn btn-default" '.$disabled.' type="button" value="" data-toggle="modal" data-target="#dynamic-booking-modal" name="pauseBtn"><i class="fa fa-pause"></i></button> ';
                $occupation = array('bookingID' => $row['workingID'], 'dynamicID' => $x, 'companyid' => $row['companyid'], 'clientid' => $row['clientid'], 'projectid' => $row['clientprojectid'], 'percentage' => $row['projectpercentage']);
            } elseif(strtotime($A) < time() && $row['projectstatus'] == 'ACTIVE' && !$row['workingUser'] && !$hasActiveBooking){
                if(!$row['projectleader']){
                    echo "<button class='btn btn-default' type='button' title='Task starten' data-toggle='modal' data-target='#play-take-$x'><i class='fa fa-play'></i></button>";
                } else {
                    echo "<button class='btn btn-default' type='submit' title='Task starten' name='play' value='$x'><i class='fa fa-play'></i></button> ";
                }
            }
            if(!$row['workingUser']){ //5acdfb19c0e84
                echo " <button type='button' class='btn btn-default' title='Task Planen' data-toggle='modal' data-target='#task-plan-$x'><i class='fa fa-clock-o'></i></button> ";
                if($isDynamicProjectsAdmin == 'TRUE' || $row['projectowner'] == $userID) { //don't show edit tools for trainings
                    echo '<button type="submit" name="deleteProject" value="'.$x.'" class="btn btn-default" title="Löschen"><i class="fa fa-trash-o"></i></button> ';
                    echo '<button type="button" name="editModal" value="'.$x.'" class="btn btn-default" title="Bearbeiten"><i class="fa fa-pencil"></i></button> ';
                }
            }
            if($filterings['tasks'] == 'ACTIVE_PLANNED') echo '<label><input type="checkbox" name="icalID[]" value="'.$x.'" checked /> .ical</label>';
            
            // always show the messages button (5ac63505c0ecd)
            echo "<button type='button' class='btn btn-default' title='Nachrichten' data-toggle='modal' data-target='#messages-$x'><i class='fa fa-commenting-o'></i></button>";

            echo '</td>';
            echo '</tr>';

            if(!$row['projectleader']){
                $modals .= '<div id="play-take-'.$x.'" class="modal fade" style="z-index:1500;">
                <div class="modal-dialog modal-content modal-sm">
                <div class="modal-header h4">Task Übernehmen</div>
                <div class="modal-body">Wollen Sie den Task dabei auch gleichzeitig als verantwortlichen Mitarbeiter übernehmen?</div>
                <div class="modal-footer"><form method="POST">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button class="btn btn-default" type="submit" title="Task normal starten" name="play" value="'.$x.'">Nein</button>
                <button class="btn btn-warning" type="submit" title="Task als Verantwortlicher starten" name="play-take" value="'.$x.'">Ja</button>
                </form></div></div></div>';
            }
            if(!$row['workingUser']){
                $modals .= '<div id="task-plan-'.$x.'" class="modal fade" style="z-index:1500;">
                <div class="modal-dialog modal-content modal-sm"><form method="POST">
                <div class="modal-header h4">Task Planen</div>
                <div class="modal-body"> Wollen Sie diesen Task auf ein anderes Datum verschieben? <br>
                Geplante Tasks werden automatisch übernommen und kehren mit dem eingestellten Datum automatisch wieder.<br><br>
                <input type="text" class="form-control datetimepicker" name="task-plan-date" value="'.$row['projectstart'].'" placeholder="z.B. 2018-12-24 10:30"/><br>
                </div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>';
                if($row['projectleader']){
                    $modals .= '<button class="btn btn-warning" type="submit" title="Task Verschieben" name="task-plan" value="'.$x.'">Verschieben</button>';
                } else {
                    $modals .= '<button class="btn btn-warning" type="submit" title="Task Verschieben" name="task-plan-take" value="'.$x.'">Verschieben</button>';
                }
                $modals .= '</div></form></div></div>';
            }

            //########################################
            //      messages modal (5ac63505c0ecd)
            //########################################
            $modals .= '<div id="messages-'.$x.'" class="modal fade" style="z-index:1500;">
                <div class="modal-dialog modal-content modal-md"><form method="POST" autocomplete="off">

                <div class="modal-header h4">'.$lang["MESSAGES"].'</div>

                <div class="modal-body">';

            // AJAX scripts must be here or a reference error will be shown
            $modals .= '    
                <script>
                // AJAX Scripts
                function getMessages(taskID, target, scroll = false, limit = 50) {
                    if(taskID.length == 0) {
                        return;
                    }
                
                    $.ajax({
                        url: "ajaxQuery/AJAX_postGetMessage.php",
                        data: {
                            taskID: taskID,
                            limit: limit,
                        },
                        type: "GET",
                        success: function (response) {
                            if(response != "no messages") {
                                $("#subject_bar'.$x.'").show();
                                $("#messages-div-'.$x.'").show();

                                $(target).html(response);

                                //Scroll down
                                if (scroll) $(target).scrollTop($(target)[0].scrollHeight)                
                            }else{
                                // hide the messages div and subject bar, when no messages available
                                $("#subject_bar'.$x.'").hide();
                                $("#messages-div-'.$x.'").hide();
                            }    
                        },
                        error: function (response) {
                            $(target).html(response);
                        },
                    });
                }

                function sendMessage(taskID, taskName, message, target, limit = 50) {
                    if(taskID.length == 0 || message.length == 0){
                        return;
                    }
                
                    $.ajax({
                        url: "ajaxQuery/AJAX_postSendMessage.php",
                        data: {
                            taskID: taskID,
                            taskName: taskName,
                            message: message,
                        },
                        type: "GET",
                        success: function (response) {
                            getMessages("'.$x.'", target, true, messageLimit'.$x.');
                        },
                    })
                }
                </script>
                ';

            // subject bar, message div and textinput field
            $modals .= '
                <div id="subject_bar'.$x.'" style="background-color: whitesmoke; border: 1px gainsboro solid; border-bottom: none; max-height: 10vh; padding: 10px;">'.$projectName.' - ' .$x.'</div>
                <div id="messages-div-'.$x.'" class="pre-scrollable" style="background-color: white; overflow: auto; overflow-x: hidden; border: 1px solid gainsboro; max-height: 55vh; padding-top: 5px"></div>
                <input id="message-'.$x.'" type="text" required class="form-control" name="message" placeholder="'.$lang["TYPE_A_MESSAGE"].'"/><br>';

            // styling
            $modals .= '<script>
                // immediately get the messages, so theres no delay
                $(document).on("show.bs.modal", "#messages-'.$x.'", function (e) {
                    getMessages("'.$x.'", "#messages-div-'.$x.'", true, 10);

                    messageLimit'.$x.' = 10;
                    buttonIntervalID'.$x.' = setInterval(function() {
                        getMessages("'.$x.'", "#messages-div-'.$x.'", false, messageLimit'.$x.');
                    }, 1000);
                });

                // always scroll down (when the modal gets reopened)
                $(document).on("shown.bs.modal", "#messages-'.$x.'", function (e) {
                    $("#messages-div-'.$x.'").scrollTop($("#messages-div-'.$x.'")[0].scrollHeight)             
                });

                // clear the interval
                $(document).on("hidden.bs.modal", "#messages-'.$x.'", function (e) {
                    clearInterval(buttonIntervalID'.$x.');
                    window.onbeforeunload = null;
                });

                //scroll
                $("#messages-div-'.$x.'").scroll(function(){
                    if($(this).scrollTop() == 0){
                        $(this).scrollTop(1);
                        messageLimit'.$x.' += 1        
                        getMessages("'.$x.'", "#messages-div-'.$x.'", false, messageLimit'.$x.');
                    }
                });
                
                //submit
                $( "#messages-'.$x.'" ).submit(function( event ) {
                    event.preventDefault();
                    messageLimit'.$x.'++;
                    sendMessage("'.$x.'", "'.$projectName.'", $("#message-'.$x.'").val(), "#messages-div-'.$x.'", messageLimit'.$x.');
                    $("#message-'.$x.'").val("");
                  });
                </script>';
            //footer  
            $modals .= '</div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button class="btn btn-warning" type="submit" title="'.$lang["SEND"].'" name="task-plan" value="'.$x.'">'.$lang["SEND"].'</button>
                </div></form></div></div>';

        }
        ?>
        <!--training-->
        <?php if($userHasUnansweredSurveys): ?>
            <tr>
                <td><i style="color: #efefef" class="fa fa-circle"></i>Unbeantwortete Schulung<div></div></td>
                <td><a type="button" class="btn btn-default openSurvey">View</a></td>
                <td>-</td>
                <td><?=date("Y-m-d")?></td>
                <td></td>
                <td><i class="fa fa-times" style="color:red" title="Keine Routine"></i></td>
                <td>ACTIVE</td>
                <td style="color:white;"><span class="badge" style="background-color:<?=$priority_color[1]?>" title="<?=$lang['PRIORITY_TOSTRING'][1]?>">1</span></td>
                <td>-</td>
                <td>-</td>
                <td><input type="checkbox" disabled /></td>
                <td><a type="button" class="btn btn-default openSurvey"><i class="fa fa-question-circle"></i></a></td>
            </tr>
        <?php endif;
        if($userHasSurveys): ?>
            <tr>
                <td><i style="color: #efefef" class="fa fa-circle"></i>Bereits beantwortete Schulungen erneut ansehen<div></div></td>
                <td><a type="button" class="btn btn-default openDoneSurvey">View</a></td>
                <td>-</td>
                <td><?=date("Y-m-d")?></td>
                <td></td>
                <td><i class="fa fa-times" style="color:red" title="Keine Routine"></i></td>
                <td>ACTIVE</td>
                <td style="color:white;"><span class="badge" style="background-color:<?=$priority_color[1]?>" title="<?=$lang['PRIORITY_TOSTRING'][1]?>">1</span></td>
                <td>-</td>
                <td>-</td>
                <td><input type="checkbox" disabled /></td>
                <td><a type="button" class="btn btn-default openDoneSurvey"><i class="fa fa-question-circle"></i></a></td>
            </tr>
        <?php endif; ?>
        <!--/training-->
    </tbody>
</table>
</form>
<div id="editingModalDiv">
    <?php echo $modals; ?>
    <div id="selectTemplate" >
        <div class="modal fade" id="template-list-modal">
            <form method="POST" onsubmit=' return setUpDeleteTemplate()'>
            <div class="modal-dialog modal-content modal-md">
                <div class="modal-header h4"><button type="button" class="close"><span>&times;</span></button><?php echo "Templates" ?></div>
                <div class="modal-body">
                    <div class="col-md-12">
                        <label>Select Template</label>
                    </div>
                    <div class="col-md-9">
                        <select id="templateSelect" class="form-control select2-templates" >
                            <option value="-1" >New...</option>
                            <?php $tempresult = $conn->query("SELECT projectname,projectid FROM dynamicprojects WHERE isTemplate = 'TRUE'");
                                  while($tempresult && ($template = $tempresult->fetch_assoc())){
                                      echo '<option value="'.$template['projectid'].'" >'.$template['projectname'].'</option>';
                                  }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-warning" onclick="editTemplate()" ><i class="fa fa-pencil"></i></button>
                        <button type="submit" name="deleteProject" class="btn btn-warning" ><i class="fa fa-trash-o"></i></button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="activateTemplate(event)" ><?php echo $lang['APPLY']; ?></button>
                </div>
            </div>
            </form>
        </div>
    </div>
    <?php if($occupation): ?>
    <div class="modal fade" id="dynamic-booking-modal">
        <div class="modal-dialog modal-content modal-md">
            <form method="POST">
                <div class="modal-header h4"><button type="button" class="close"><span>&times;</span></button><?php echo $lang["DYNAMIC_PROJECTS_BOOKING_PROMPT"]; ?></div>
                <div class="modal-body">
                    <div class="row" id="occupation_booking_fields">
                        <?php if(!$occupation['companyid'] && count($available_companies) > 2): ?>
                            <div class="col-md-4">
                                <label><?php echo $lang['COMPANY']; ?></label>
                                <select class="js-example-basic-single" onchange="showClients(this.value, <?php echo intval($occupation['clientid']); ?>, 'book-dynamic-clientHint')">
                                    <?php
                                    $result = $conn->query("SELECT id, name FROM companyData WHERE id IN (".implode(', ', $available_companies).") ");
                                    echo '<option value="0"> ... </option>';
                                    while($row = $result->fetch_assoc()){
                                        echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
                                    }
                                    ?>
                                </select><br><br>
                            </div>
                        <?php endif; if(!$occupation['clientid']): ?>
                            <div class="col-md-4">
                                <label><?php echo $lang['CLIENT']; ?></label>
                                <select id="book-dynamic-clientHint" class="js-example-basic-single" onchange="showProjects(this.value, '<?php echo intval($occupation['projectid']); ?>', 'book-dynamic-projectHint')" >
                                    <option value="0"> ... </option>
                                <?php
                                $result = $conn->query("SELECT id, name FROM clientData WHERE isSupplier = 'FALSE' AND companyID IN (".implode(', ', $available_companies).")");
                                if ($occupation['companyid']){
                                    $result = $conn->query("SELECT id, name FROM clientData WHERE isSupplier = 'FALSE' AND companyID = ".$occupation['companyid']);
                                }
                                while ($row = $result->fetch_assoc()){
                                    echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
                                }
                                ?>
                                </select><br><br>
                            </div>
                        <?php endif; if(!$occupation['projectid']): ?>
                            <div class="col-md-4">
                                <label><?php echo $lang['PROJECT']; ?></label>
                                <select id="book-dynamic-projectHint" class="js-example-basic-single" name="bookDynamicProjectID">
                                    <?php if($occupation['clientid']){
                                        $result = $conn->query("SELECT id, name FROM projectData WHERE clientID = ".$occupation['clientid']);
                                        echo '<option value="0"> ... </option>';
                                        while($row = $result->fetch_assoc()){
                                            echo '<option value="'.$row['id'].'">'.$row['name'].'</option>';
                                        }
                                    } ?>
                                </select><br><br>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-12">
                            <textarea name="description" rows="4" class="form-control" style="max-width:100%; min-width:100%" placeholder="Info..."></textarea><br>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label><input type="checkbox" name="completeWithoutBooking" value="1" id="occupation_booking_fields_toggle">Task ohne Buchung abschließen</label><br><br>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <input id="bookRanger" type="range" min="1" max="100" value="<?php echo $occupation['percentage']; ?>" oninput="document.getElementById('bookCompleted').value = this.value;"><br>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <input type="number" class="form-control" name="bookCompleted" id="bookCompleted" min="0" max="100" value="<?php echo $occupation['percentage']; ?>" />
                                <span class="input-group-addon">%</span>
                            </div><br>
                        </div>
                        <div class="col-md-3">
                            <label><input type="checkbox" name="bookCompletedCheckbox" id="bookCompletedCheckbox"> Task erledigt</label><br>
                        </div>
                    </div>
                    <?php
                    $microtasks = $conn->query("SELECT microtaskid, title, ischecked FROM microtasks WHERE projectid = '".$occupation['dynamicID']."' WHERE ischecked = 'FALSE'");
                    if($microtasks && $microtasks->num_rows > 0):
                        ?>
                        <div class="row">
                            <div class="col-md-12">
                                <table id="microlist" class="dataTable table">
                                    <thead><tr>
                                        <th>Completed</th>
                                        <th>Micro Task</th>
                                    </tr></thead>
                                    <tbody>
                                        <?php
                                        while($mtask = $microtasks->fetch_assoc()){
                                            $mid = $mtask['microtaskid'];
                                            $title = $mtask['title'];
                                            echo '<tr>';
                                            echo '<td><input type="checkbox" name="mtask'.$mid.'" title="'.$title.'"></input></td>';
                                            echo '<td><label>'.$title.'</label></td>';
                                            echo '</tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <div class="pull-left"><?php echo $occupation['dynamicID']; ?></div>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" name="createBooking" value="<?php echo $occupation['bookingID']; ?>"><?php echo $lang['SAVE']; ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; //endif occupation ?>
</div>

<script src="plugins/rtfConverter/rtf.js-master/samples/cptable.full.js"></script>
<script src="plugins/rtfConverter/rtf.js-master/samples/symboltable.js"></script>
<script src="plugins/rtfConverter/rtf.js-master/rtf.js"></script>
<script src='../plugins/tinymce/tinymce.min.js'></script>
<script>
$('button[name="deleteProject"]').click(function(){
    var c = confirm("Wollen Sie diesen Task wirklich löschen?");
    return c;
});
$("#bookCompletedCheckbox").change(function(){
    $("#bookCompleted").attr('readonly', this.checked);
    $("#bookRanger").attr('disabled', this.checked);
    if(this.checked){
        $("#bookRanger").val(100);
        $("#bookCompleted").val(100);
    }
});
$('#occupation_booking_fields_toggle').change(function(){
    if(this.checked){
        $("#occupation_booking_fields").hide();
    } else {
        $("#occupation_booking_fields").show();
    }
});
function formatState (state) {
    if (!state.id) { return state.text; }
    var $state = $(
        '<span><i class="fa fa-' + state.element.dataset.icon + '"></i> ' + state.text + '</span>'
    );
    return $state;
};
function dynamicOnLoad(modID){
    if(typeof modID === 'undefined') modID = '';
    $(".select2-team-icons").select2({
        templateResult: formatState,
        templateSelection: formatState
    });
    $(".js-example-tokenizer").select2({
        tags: true,
        tokenSeparators: [',', ' ']
    });
    $(".select2-templates").select2({
        minimumResultsForSearch: Infinity
    });
    $('[data-toggle="tooltip"]').tooltip();
    $('button[name="take_task"]').on('click', function(){
        var c = confirm("Wollen Sie als Verantwortlicher Mitarbeiter eingetragen werden?");
        return c;
    });
    tinymce.init({
        selector: '.projectDescriptionEditor',
        plugins: 'image code paste emoticons table',
        relative_urls: false,
        paste_data_images: true,
        menubar: false,
        statusbar: false,
        height: 300,
        browser_spellcheck: true,
        toolbar: 'undo redo | cut copy paste | styleselect | link image file media | code table | InsertMicroTask | emoticons',
        setup: function(editor){
            editor.addButton("InsertMicroTask",{
                tooltip: "Insert MicroTask",
                icon: "template",
                onclick: function(){
                    var html = "<p>[<label style='color: red;font-weight:bold'>MicroTaskName</label>] { </p><p> MicrotaskDescription here </p><p> }</p>";
                    editor.insertContent(html);
                },
            });
        },
        // enable title field in the Image dialog
        image_title: true,
        // enable automatic uploads of images represented by blob or data URIs
        automatic_uploads: true,
        // URL of our upload handler (for more details check: https://www.tinymce.com/docs/configure/file-image-upload/#images_upload_url)
        // images_upload_url: 'postAcceptor.php',
        // here we add custom filepicker only to Image dialog
        file_picker_types: 'file image media',
        init_instance_callback: function (editor) {
            editor.on('paste', function (e) {
                //console.log(e.clipboardData.types.includes("text/rtf"));
                if(e.clipboardData.types.includes("text/rtf")){
                    var clipboardData, pastedData;
                // Stop data actually being pasted into div
                e.preventDefault();
                // Get pasted data via clipboard API
                clipboardData = e.clipboardData || window.clipboardData;
                pastedData = clipboardData.getData('text/rtf');
                var stringToBinaryArray = function(txt) {
                    var buffer = new ArrayBuffer(txt.length);
                    var bufferView = new Uint8Array(buffer);
                    for (var i = 0; i < txt.length; i++) {
                        bufferView[i] = txt.charCodeAt(i);
                    }
                    return buffer;
                }
                var settings = {};
                var doc = new RTFJS.Document(stringToBinaryArray(pastedData), settings);
                var part = doc.render();
                //console.log(part);
                for(i=0;i<part.length;i++){
                    part[i][0].innerHTML = part[i][0].innerHTML.replace("[Unsupported image format]","");
                    this.execCommand("mceInsertContent",false,part[i][0].innerHTML);
                }
                }
            });
        },
        // and here's our custom image picker
        file_picker_callback: function(cb, value, meta) {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', '*');
            input.onchange = function() {
                var file = this.files[0];
                var reader = new FileReader();
                reader.onload = function () {
                    // Note: Now we need to register the blob in TinyMCEs image blob
                    // registry. In the next release this part hopefully won't be
                    // necessary, as we are looking to handle it internally.
                    var id = 'blobid' + (new Date()).getTime();
                    var blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                    //console.log(reader.result.split(";")[0].split(":")[1]) //mime type
                    var base64 = reader.result.split(',')[1];
                    alert("Base64 size: "+base64.length+" chars")
                    var blobInfo = blobCache.create(id, file, base64);
                    blobCache.add(blobInfo);
                    // call the callback and populate the Title field with the file name
                    cb(blobInfo.blobUri(), { title: file.name, text:file.name,alt:file.name,source:"images/Question_Circle.jpg",poster:"images/Question_Circle.jpg" });
                };
                reader.readAsDataURL(file);
            };
            input.click();
        }
    });
} //end dynamicOnLoad()
function appendModal(index){
    $.ajax({
    url:'ajaxQuery/AJAX_dynamicEditModal.php',
    data:{projectid: index,isDPAdmin: "<?php echo $isDynamicProjectsAdmin ?>"},
    type: 'post',
    success : function(resp){
      $("#editingModalDiv").append(resp);
      existingModals.push(index);
      onPageLoad();
      dynamicOnLoad(index);
    },
    error : function(resp){},
    complete: function(resp){
        if(index){
            $('#editingModal-'+index).modal('show');
        }
        $("#projectForm"+index).rememberState();
    }
   });
}
var existingModals = new Array();
appendModal('');
var existingModals_info = new Array();
$('.view-modal-open').click(function(){
    var index = $(this).val();
  if(existingModals_info.indexOf(index) == -1){
      $.ajax({
      url:'ajaxQuery/AJAX_dynamicInfo.php',
      data:{projectid: index},
      type: 'get',
      success : function(resp){
        $("#editingModalDiv").append(resp);
        existingModals_info.push(index);
        onPageLoad();
        dynamicOnLoad(index);
      },
      error : function(resp){},
      complete: function(resp){
          if(index){
              $('#infoModal-'+index).modal('show');
          }
      }
     });
     $(this).parent().parent().removeAttr('style');
  } else {
    $('#infoModal-'+index).modal('show');
  }
});
function showClients(company, client, place){
    if(company != ""){
        $.ajax({
            url:'ajaxQuery/AJAX_getClient.php',
            data:{companyID:company, clientID:client},
            type: 'get',
            success : function(resp){
                $("#"+place).html(resp);
            },
            error : function(resp){}
        });
    }
}
function showProjects(client, project, place){
    if(client != ""){
        $.ajax({
            url:'ajaxQuery/AJAX_getProjects.php',
            data:{clientID:client, projectID:project},
            type: 'get',
            success : function(resp){
                $("#"+place).html(resp);
            },
            error : function(resp){}
        });
    }
}
function activateTemplate(event){
    id = $(".select2-templates").select2('data');
    if(id[0].id == -1){
        //Create new Template
        $("#template-list-modal").modal('hide');
        $("#editingModal- .modal-title")[0].innerText = "Template editieren";
        isTemplate = document.createElement("input");
        isTemplate.name = "isTemplate";
        isTemplate.id = "isTemplate";
        isTemplate.style = "visibility: hidden; height:1px; width:1px";
        $("#editingModal- form")[0].appendChild(isTemplate);
        $("#editingModal-").modal('show');
    } else {
        $("#template-list-modal").modal('hide');
        var index = id[0].id;
        if(existingModals.indexOf(index) == -1){
            appendModal(index);
        } else {
            $('#editingModal-'+index).modal('show');
        }
    }
}
function setUpDeleteTemplate(){
    id =  $(".select2-templates").select2("data");
    if(id[0].id==-1){
        return false;
    } else {
        $("#selectTemplate button[name=deleteProject]")[0].value = id[0].id;
    }
}
function editTemplate(){
    id =  $(".select2-templates").select2("data");
    if(id[0].id==-1){
     return false;
    }else{
        $("#template-list-modal").modal('hide');
        $.ajax({
        url:'ajaxQuery/AJAX_dynamicEditModal.php',
        data:{projectid: id[0].id,isDPAdmin: "<?php echo $isDynamicProjectsAdmin ?>"},
        type: 'post',
        success : function(resp){
            resp = resp.replace('name="editDynamicProject" value=""','name="editDynamicProject" value="'+id[0].id+'"');
            resp = resp.replace('</form>','<input name="isTemplate" style="visibility:hidden;height:1px;width:1px;" ></input></form>');
            $("#editingModalDiv").append(resp);
            //existingModals.push(index);
            onPageLoad();
            dynamicOnLoad();
        },
        error : function(resp){},
        complete: function(resp){
            if(id[0].id){
                $('#tempeditingModal-'+id[0].id).modal('show');
            }
        }
        });
    }
}
$('.table').on('click', 'button[name=editModal]', function(){
    var index = $(this).val();
  if(existingModals.indexOf(index) == -1){
      appendModal(index);
  } else {
    $('#editingModal-'+index).modal('show');
  }
});
// function resetNewTask(){
//     $("#editingModal- .modal-title")[0].innerText = "Task editieren";
//     isTemplate = $("#editingModal- #isTemplate")[0];
//     $("#editingModal- form")[0].removeChild(isTemplate);
// }
function reviewChange(event,id){
    projectid = id;
    needsReview = event.target.checked ? 'TRUE' : 'FALSE';
    $.post("ajaxQuery/AJAX_db_utility.php",{
        needsReview: needsReview,
        function: "changeReview",
        projectid: projectid
    }, function(data){
        //console.log(data);
    });
}
$(".openDoneSurvey").click(function(){ // answer already done surveys/trainings again
    $.ajax({
        url:'ajaxQuery/AJAX_getTrainingSurvey.php',
        data:{done:true},
        type: 'get',
        success : function(resp){
            $("#currentSurveyModal").html(resp);
        },
        error : function(resp){console.error(resp)},
        complete: function(resp){
            $("#currentSurveyModal .survey-modal").modal("show");
        }
   });
});
setTimeout( function(){
    $('button[name="pauseBtn"]').prop("disabled", false);
}, 60000 );
$(document).ready(function() {
    dynamicOnLoad();
    $('.table').DataTable({
        ordering:false,
        language: {
            <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
        },
        responsive: true,
        autoWidth: false,
        fixedHeader: {
            header: true,
            headerOffset: 150,
            zTop: 1
        },
        paging: true
    });
    setTimeout(function(){
        window.dispatchEvent(new Event('resize'));
        $('.table').trigger('column-reorder.dt');
    }, 500);
});
</script>
</div>
<?php include dirname(__DIR__) . '/footer.php'; ?>
