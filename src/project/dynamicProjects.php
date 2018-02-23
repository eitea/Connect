<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['play'])){
        $ckIn_disabled = 'disabled';
    }
}
include dirname(__DIR__) . '/header.php';
require dirname(__DIR__) . "/misc/helpcenter.php";
require dirname(__DIR__) . "/Calculators/dynamicProjects_ProjectSeries.php";

function formatPercent($num){ return ($num * 100)."%"; }
function generate_progress_bar($current,$estimate, $referenceTime = 8){ //both times in hours (float), $referenceTime is the time where the progress bar overall length hits 100% (it can't go over 100%)
    if($current<$estimate){
        $yellowBar = $current/($estimate+0.0001);
        $greenBar = 1-$yellowBar;
        $timeLeft = $estimate - $current;
        $redBar = 0;
        $timeOver = 0;
    }else{
        $timeOver = $current - $estimate;
        $timeLeft = 0;
        $greenBar = 0;
        $yellowBar = ($estimate)/($timeLeft + $timeOver + $current + 0.0001);
        $redBar = 1-$yellowBar;
        $current = $estimate;
    }
    $progressLength = min(($timeLeft + $timeOver + $current)/$referenceTime, 1);
    $bar = "<div style='height:5px;margin-bottom:2px;width:".formatPercent($progressLength)."' class='progress'>";
    $bar .= "<div data-toggle='tooltip' title='".round($current,2)." Stunden' class='progress-bar progress-bar-warning' style='height:10px;width:".formatPercent($yellowBar)."'></div>";
    $bar .= "<div data-toggle='tooltip' title='".round($timeLeft,2)." Stunden' class='progress-bar progress-bar-success' style='height:10px;width:".formatPercent($greenBar)."'></div>";
    $bar .= "<div data-toggle='tooltip' title='".round($timeOver,2)." Stunden' class='progress-bar progress-bar-danger' style='height:10px;width:".formatPercent($redBar)."'></div>";
    return "$bar</div>";
}

$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0, "project" => 0, 'tasks' => 'ACTIVE', "priority" => 0, "employees" => ["user;".$userID]); //set_filter requirement
?>
<div class="page-header-fixed">
<div class="page-header"><h3>Tasks<div class="page-header-button-group">
    <?php include dirname(__DIR__) . '/misc/set_filter.php';?>
    <?php if($isDynamicProjectsAdmin == 'TRUE'|| $canCreateTasks == 'TRUE'): ?>
            <div class="dropdown" style="display:inline;">
                <button class="btn btn-default dropdown-toggle" id="dropdownAddTask" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" type="button"><i class="fa fa-plus"></i></button>
                <ul class="dropdown-menu" aria-labelledby="dropdownAddTask" >
                    <div class="container-fluid">
                        <li ><button class="btn btn-default form-control" data-toggle="modal" data-target="#editingModal-" >New</button></li>
                        <li class="divider"></li>
                        <li ><button class="btn btn-default form-control" data-toggle="modal" data-target="#template-list-modal" >From Template</button></li>
                    </div>
                </ul>
            </div>
 <?php endif; ?>
</div></h3></div>
</div>
<div class="page-content-fixed-100">
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    if(!empty($_POST['play'])){
        $x = test_input($_POST['play'], true);
        $result = $conn->query("SELECT id FROM projectBookingData WHERE `end` = '0000-00-00 00:00:00' AND dynamicID = '$x'");
        if($result->num_rows < 1){
            $result = mysqli_query($conn, "SELECT indexIM FROM logs WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00' LIMIT 1");
            if($result && ($row = $result->fetch_assoc())){
                $indexIM = $row['indexIM'];
                //TODO: make sure play does not overlap an existing booking and user does not have a lücke in the previous buchung
                //$result = $conn->query("SELECT start, end FROM projectBookingData WHERE timestampID = $indexIM");
                $conn->query("INSERT INTO projectBookingData (start, end, timestampID, infoText, bookingType, dynamicID) VALUES(UTC_TIMESTAMP, '0000-00-00 00:00:00', $indexIM, 'Begin of Task $x' , 'project', '$x')");
                if($conn->error){
                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                } else {
                    echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a> Task wurde gestartet. Solange dieser läuft, ist Ausstempeln nicht möglich.</div>';
                }
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Bitte einstempeln.</strong> Tasks können nur angenommen werden, sofern man eingestempelt ist.</div>';
            }
        } else {
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Task besetzt.</strong> Dieser Task gehört schon jemandem.</div>';
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
    if(!empty($_POST['createBooking']) && !empty($_POST['description'])){
        $bookingID = test_input($_POST['createBooking']);
        $result = $conn->query("SELECT dynamicID, clientprojectid, needsreview FROM projectBookingData p, dynamicprojects d WHERE id = $bookingID AND d.projectid = p.dynamicID");
        if($row = $result->fetch_assoc()){
            $dynamicID = $row['dynamicID'];
            $projectID = $row['clientprojectid'] ? $row['clientprojectid'] : intval($_POST['bookDynamicProjectID']);
            if($projectID){
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

                $description = test_input($_POST['description']);
                $conn->query("UPDATE projectBookingData SET end = UTC_TIMESTAMP, infoText = '$description', projectID = '$projectID', internInfo = '$percentage% Abgeschlossen'  WHERE id = $bookingID");

                if($conn->error){
                	echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
                } else {
                	redirect('view'); //to refresh the disabled check out button
                }
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_SELECTION'].' (Projekt)</div>';
            }
        } else { //STRIKE
            $conn->query("UPDATE userdata SET strikeCount = strikecount + 1 WHERE id = $userID");
            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a><strong>Project not Available.</strong> '.$lang['ERROR_STRIKE'].'</div>';
        }
    }
    if($isDynamicProjectsAdmin == 'TRUE' || $canCreateTasks == 'TRUE'){
        if(!empty($_POST['deleteProject'])){
            $val = test_input($_POST['deleteProject']);
            $conn->query("DELETE FROM dynamicprojectslogs WHERE projectid = '$val'");
            $conn->query("DELETE FROM dynamicprojects WHERE projectid = '$val'");
            if($conn->error){
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
            } else {
                echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_DELETE'].'</div>';
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
                echo "<script>console.log('".strlen($description)."')</script>";
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
                            echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$conn->error.'</div>';
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
                $leader = $_POST['leader'] ? intval($_POST['leader']) : $userID;
                $percentage = intval($_POST['completed']);
                $estimate = test_input($_POST['estimatedHours']);
                if($isDynamicProjectsAdmin == 'TRUE'){
                    $skill = intval($_POST['projectskill']);
                    $parent = test_input($_POST["parent"]); //dynamproject id
                }else{
                    $skill = 0;
                    $parent = null;
                }
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
                    projectpriority, projectparent, projectowner, projectleader, projectnextdate, projectseries, projectpercentage, estimatedHours, level, projecttags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param("ssbiiissssisiisbisis", $id, $name, $null, $company, $client, $project, $color, $start, $end, $status, $priority, $parent, $owner, $leader, $nextDate, $null, $percentage, $estimate, $skill, $tags);
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
                    echo '<div class="alert alert-success"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['OK_ADD'].'</div>';
                } else {
                    echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$stmt->error.'</div>';
                }
                $stmt->close();
            } else {
                echo '<div class="alert alert-danger"><a href="#" data-dismiss="alert" class="close">&times;</a>'.$lang['ERROR_MISSING_FIELDS'].'</div>';
            }
        }
    } // end if dynamic Admin
} //end if POST
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
            <th><?php echo $lang["OWNER"]; ?></th>
            <th><?php echo $lang["EMPLOYEE"]; ?></th>
            <th>Review</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $occupation = $query_filter = '';
        $priority_color = ['', '#2a5da1', '#0c95d9', '#6b6b6b', '#ff7600', '#ff0000'];
        if($filterings['tasks']){
            if($filterings['tasks'] == 'REVIEW_1'){
                $query_filter = "AND d.projectstatus = 'REVIEW' AND needsreview = 'TRUE' ";
            } elseif($filterings['tasks'] == 'REVIEW_2'){
                $query_filter = "AND d.projectstatus = 'REVIEW' AND needsreview = 'FALSE' ";
            } else {
                $query_filter = "AND d.projectstatus = '".test_input($filterings['tasks'], true)."' ";
            }
        }
        if($filterings['priority'] > 0){ $query_filter .= " AND d.projectpriority = ".$filterings['priority']; }

        $stmt_team = $conn->prepare("SELECT name, teamid FROM dynamicprojectsteams INNER JOIN teamData ON teamid = teamData.id WHERE projectid = ?");
        $stmt_team->bind_param('s', $x);
        $stmt_viewed = $conn->prepare("SELECT activity FROM dynamicprojectslogs WHERE projectid = ? AND
            ((activity = 'VIEWED' AND userid = $userID) OR ((activity = 'CREATED' OR activity = 'EDITED') AND userID != $userID)) ORDER BY logTime DESC LIMIT 1"); //changes here have to be synced with AJAX_dynamicInfo.php
        $stmt_viewed->bind_param('s', $x);
        $stmt_employee = $conn->prepare("SELECT userid FROM dynamicprojectsemployees WHERE projectid = ? ");
        $stmt_employee->bind_param('s', $x);
        $stmt_booking = $conn->prepare("SELECT userID, p.id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00' AND dynamicID = ?");
        $stmt_booking->bind_param('s', $x);
        $stmt_time = $conn->prepare("SELECT SUM(IFNULL(TIMESTAMPDIFF(SECOND, p.start, p.end)/3600,TIMESTAMPDIFF(SECOND, p.start, UTC_TIMESTAMP)/3600)) current FROM projectBookingData p INNER JOIN logs ON logs.indexIM = p.timestampID WHERE p.dynamicID = ?");
        $stmt_time->bind_param('s', $x); // this statement gets the time in hours booked for a project
        $result = $conn->query("SELECT id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND logs.userID = $userID AND `end` = '0000-00-00 00:00:00' LIMIT 1");
        $hasActiveBooking = $result->num_rows;
        if($isDynamicProjectsAdmin == 'TRUE'){ //see all access-legal tasks
            $result = $conn->query("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus, projectpriority, projectowner, projectleader,
                projectpercentage, projecttags, d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName, clientData.name AS clientName, projectData.name AS projectDataName, needsreview, estimatedHours
                FROM dynamicprojects d LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN projectData ON projectData.id = clientprojectid
                WHERE d.companyid IN (0, ".implode(', ', $available_companies).") $query_filter ORDER BY projectpriority DESC, projectstatus, projectstart ASC");
        } else { //see open tasks user is part of  (update AJAX_dynamicInfo if changed)
            $result = $conn->query("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus, projectpriority, projectowner, projectleader,
                projectpercentage, projecttags, d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName, clientData.name AS clientName, projectData.name AS projectDataName, needsreview, estimatedHours
                FROM dynamicprojects d LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN projectData ON projectData.id = clientprojectid
                LEFT JOIN dynamicprojectsemployees ON dynamicprojectsemployees.projectid = d.projectid
                LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid LEFT JOIN teamRelationshipData ON teamRelationshipData.teamID = dynamicprojectsteams.teamid
                WHERE (dynamicprojectsemployees.userid = $userID OR d.projectowner = $userID OR (teamRelationshipData.userID = $userID AND teamRelationshipData.skill >= d.level))
                AND d.projectstart <= UTC_TIMESTAMP $query_filter ORDER BY projectpriority DESC, projectstatus, projectstart ASC");
        }
        echo $conn->error;
        while($result && ($row = $result->fetch_assoc())){
            $x = $row['projectid'];

            $selection = array('user;'.$row['projectowner'], 'user;'.$row['projectleader']);
            $employees = array();
            $stmt_team->execute();
            $emp_result = $stmt_team->get_result();
            while(($emp_row = $emp_result->fetch_assoc()) && $emp_row['teamid']){
                $employees[] = $emp_row['name'];
                $selection[] = 'team;'.$emp_row['teamid'];
            }

            $stmt_employee->execute();
            $emp_result = $stmt_employee->get_result();
            while(($emp_row = $emp_result->fetch_assoc()) && $emp_row['userid']){
                $employees[] = $userID_toName[$emp_row['userid']];
                $selection[] = 'user;'.$emp_row['userid'];
            }

            if(!array_intersect($filterings['employees'], $selection)) continue;

            $stmt_viewed->execute();
            $viewed_result = $stmt_viewed->get_result();
            $rowStyle = $tags = '';

            foreach(explode(',', $row['projecttags']) as $tag){
                if($tag) $tags .= '<span class="badge">'.$tag.'</span> ';
            }
            if (($viewed = $viewed_result->fetch_assoc()) && $viewed['activity'] != 'VIEWED'){ $rowStyle = 'style="color:#1689e7; font-weight:bold;"'; }
            echo '<tr '.$rowStyle.'>';
            echo '<td>';
            $stmt_time->execute();
            $currentTime = 0;
            if($timeResult = $stmt_time->get_result()){ $currentTime = $timeResult->fetch_assoc()["current"]; } // in hours with precision 4
            echo generate_progress_bar(floatval($currentTime),floatval($row["estimatedHours"])); // generate_progress_bar($cur,$estimate): string
            echo '<i style="color:'.$row['projectcolor'].'" class="fa fa-circle"></i> '.$row['projectname'].' <div>'.$tags.'</div></td>';
            echo '<td><button type="button" class="btn btn-default view-modal-open" value="'.$x.'" >View</button></td>';
            echo '<td>'.$row['companyName'].'<br>'.$row['clientName'].'<br>'.$row['projectDataName'].'</td>';
            echo '<td>'.$row['projectstart'].'</td>';
            echo '<td>'.$row['projectend'].'</td>';

            if($row['projectseries']){
                echo '<td><i class="fa fa-clock-o"></i></td>';
            } else {
                echo '<td><i class="fa fa-times" style="color:red" title="Keine Routine"></i></td>';
            }

            $stmt_booking->execute();
            $isInUse = $stmt_booking->get_result(); //max 1 row

            echo '<td>';
            if($useRow = $isInUse->fetch_assoc()){ echo 'WORKING<br><small>'.$userID_toName[$useRow['userID']].'</small>'; } else { echo $row['projectstatus']; }
            if($row['projectstatus'] != 'COMPLETED'){ echo ' ('.$row['projectpercentage'].'%)'; }
            echo '</td>';

            echo '<td style="color:white;"><span class="badge" style="background-color:'.$priority_color[$row['projectpriority']].'" title="'.$lang['PRIORITY_TOSTRING'][$row['projectpriority']].'">'.$row['projectpriority'].'</span></td>';
            echo '<td>'.$userID_toName[$row['projectowner']].'</td>';

            echo '<td>'; //employees
            echo '<u title="Verantwortlicher Mitarbeiter">'.$userID_toName[$row['projectleader']].'</u><br>';

            echo implode(',<br>', $employees);
            echo '</td>';

            echo '<td>';
            $review = '<input type="checkbox" ';
            ($isDynamicProjectsAdmin == 'FALSE' && $row['projectowner'] != $userID) ? $review= $review.' disabled ' : $review= $review.' onchange="reviewChange(event,\''.$x.'\')" ' ;
            if($row['needsreview'] == 'TRUE') $review= $review.'checked ';
            $review= $review.'></input>';
            echo $review;
            echo '</td>';
            echo '<td><form method="POST">';
            if($useRow && $useRow['userID'] == $userID) { //if this task IsInUse and this user is the one using it
                echo '<button class="btn btn-default" onclick="checkMicroTasks()" type="button" value="" data-toggle="modal" data-target="#dynamic-booking-modal"><i class="fa fa-pause"></i></button> ';
                $occupation = array('bookingID' => $useRow['id'], 'dynamicID' => $x, 'companyid' => $row['companyid'], 'clientid' => $row['clientid'], 'projectid' => $row['clientprojectid'], 'percentage' => $row['projectpercentage']);
            } elseif($row['projectstatus'] == 'ACTIVE' && $isInUse->num_rows < 1 && !$hasActiveBooking){ //only if project is active, this task is not already in use and this user has no other active bookings
                echo "<button class='btn btn-default' type='submit' title='Task starten' name='play' value='$x'><i class='fa fa-play'></i></button> ";
            }
            if($isDynamicProjectsAdmin == 'TRUE' || $row['projectowner'] == $userID) { //don't show edit tools for trainings
                echo '<button type="button" name="editModal" value="'.$x.'" class="btn btn-default" title="Bearbeiten"><i class="fa fa-pencil"></i></button> ';
                echo '<button type="submit" name="deleteProject" value="'.$x.'" class="btn btn-default" title="Löschen"><i class="fa fa-trash-o"></i></button> ';
            }

            echo '</form></td>';
            echo '</tr>';
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
    <div id="selectTemplate" >
        <div class="modal fade" id="template-list-modal">
            <div class="modal-dialog modal-content modal-sm">
                <div class="modal-header h4"><button type="button" class="close"><span>&times;</span></button><?php echo "TEMPLATES" ?></div>
                <div class="modal-body">
                    <div class="col-sm-12">
                        PLACEHOLDER
<!--                        <label>Select Template</label>
                        <select class="form-control select2-templates" >
                            <option value="-1" >New...</option>
                        </select>-->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="activateTemplate(event)" ><?php echo $lang['APPLY']; ?></button>
                </div>
            </div>
        </div>
    </div>
<div id="editingModalDiv">
    <?php if($occupation): ?>
    <div class="modal fade" id="dynamic-booking-modal">
        <div class="modal-dialog modal-content modal-md">
            <form method="POST">
                <div class="modal-header h4"><button type="button" class="close"><span>&times;</span></button><?php echo $lang["DYNAMIC_PROJECTS_BOOKING_PROMPT"]; ?></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <textarea name="description" rows="4" class="form-control" style="max-width:100%; min-width:100%" placeholder="Info..."></textarea><br>
                        </div>
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
                    <div class="row">
                        <div class="col-md-12">
                            <?php
                            $microtasks = $conn->query("SELECT microtaskid, title, ischecked FROM microtasks WHERE projectid = '".$occupation['dynamicID']."' WHERE ischecked = 'FALSE'");
                            if($microtasks && $microtasks->num_rows > 0){
                                echo '<table id="microlist" class="dataTable table">';
                                echo '<thead><tr>';
                                echo '<th>Completed</th>';
                                echo '<th>Micro Task</th>';
                                echo '</tr></thead>';
                                echo '<tbody>';
                                while($mtask = $microtasks->fetch_assoc()){
                                    $mid = $mtask['microtaskid'];
                                    $title = $mtask['title'];
                                    echo '<tr>';
                                    echo '<td><input type="checkbox" name="mtask'.$mid.'" title="'.$title.'"></input></td>';
                                    echo '<td><label>'.$title.'</label></td>';
                                    echo '</tr>';
                                }
                                echo '</tbody>';
                                echo '</table>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="row">
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
                                </select>
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
                                </select>
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
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
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
$("#projectForm").on("submit",function(){
    console.log("here");

})
$("#bookCompletedCheckbox").change(function(event){
    $("#bookCompleted").attr('readonly', this.checked);
    $("#bookRanger").attr('disabled', this.checked);
    if(this.checked){
        $("#bookRanger").val(100);
        $("#bookCompleted").val(100);
    }
});
function checkMicroTasks(){
    if(document.getElementById("microlist").tBodies[0].firstElementChild.firstElementChild.className=="dataTables_empty"){
        $("#bookCompletedCheckbox").attr('disabled',false);
    } else {
        $("#bookCompletedCheckbox").attr('disabled',true);
        $("#bookCompleted").attr('max',99);
        $("#bookRanger").attr('max',99);
    }
}

$("#microlist input[type='checkbox']").change(function(){
    var allisgood = true;
    $("#microlist input[type='checkbox']").each(function(){
        if(!(this.checked)) allisgood = false;
    });
    if(allisgood){
        $("#bookCompletedCheckbox").attr('disabled',false);
        $("#bookCompleted").attr('max',100);
        $("#bookRanger").attr('max',100);
    } else {
        $("#bookCompletedCheckbox").attr('disabled',true);
        $("#bookCompleted").attr('max',99);
        $("#bookRanger").attr('max',99);
    }
});

$("#bookCompleted").keyup(function(event){
    if($("#bookCompleted").val() == 100){
        if(document.getElementById("microlist").tBodies[0].firstElementChild.firstElementChild.className=="dataTables_empty"){
            $("#bookCompletedCheckbox").prop('checked', true);
        } else {
            $("#bookCompleted").prop('value',99);
        }
    } else {
        $("#bookCompletedCheckbox").prop('checked', false);
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
    tinymce.init({
        selector: '.projectDescriptionEditor',
        plugins: 'image code paste emoticons table',
        relative_urls: false,
        paste_data_images: true,
        menubar: false,
        statusbar: false,
        browser_spellcheck: true,
        height: 300,
        toolbar: 'undo redo | cut copy paste | styleselect | link image file media | code table | InsertMicroTask | emoticons',
        setup: function(editor){
            function insertMicroTask(){
                var html = "<p>[<label style='color: red;font-weight:bold'>MicroTaskName</label>] { </p><p> MicrotaskDescription here </p><p> }</p>";
                editor.insertContent(html);
            }

            editor.addButton("InsertMicroTask",{
                tooltip: "Insert MicroTask",
                icon: "template",
                onclick: insertMicroTask,
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
                console.log('Here');

                console.log(e.clipboardData.types.includes("text/rtf"));
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
                console.log(part);
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
                    console.log(reader.result.split(";")[0].split(":")[1]) //mime type
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
} //end dnymaicOnLoad()

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
    }
   });
}
var existingModals = new Array();
$('button[name=editModal]').click(function(){
    var index = $(this).val();
  if(existingModals.indexOf(index) == -1){
      appendModal(index);
  } else {
    $('#editingModal-'+index).modal('show');
  }
});
appendModal('');

$("tbody").on("click",function(){
    $('button[name=editModal]').click(function(){
            var index = $(this).val();
        if(existingModals.indexOf(index) == -1){
            appendModal(index);
        } else {
            $('#editingModal-'+index).modal('show');
        }
        });
        appendModal('');
})

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

$(document).ready(function() {
    dynamicOnLoad();
    $('.table').DataTable({
        ordering:false,
        language: {
            <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
        },
        responsive: true,
        dom: 'tf',
        autoWidth: false,
        fixedHeader: {
            header: true,
            headerOffset: 150,
            zTop: 1
        },
        paging: false
    });
    setTimeout(function(){
        window.dispatchEvent(new Event('resize'));
        $('.table').trigger('column-reorder.dt');
    }, 500);
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
    if(id === -1){
        //Create new Template
    }else{
        $("#template-list-modal").modal('hide');
        //Get Template Data
        //Fill editingModal- with data
        //open editingModal-
    }
 }
  function checkInput(event){
      //check Input
    console.log(event);
    if(tinymce.activeEditor.getContent()==""){
        alert("<?php echo $lang["ERROR_MISSING_FIELDS"] ?>");
        return false;
    }
    
    if(tinymce.activeEditor.getContent().length>(<?php
        $max = $conn->query("SHOW VARIABLES LIKE 'max_allowed_packet';");
        $maxSQL = $max->fetch_assoc();
        echo $maxSQL['Value'] ?>-500) || tinymce.activeEditor.getContent().length>16777215){
        alert("Description Too Big");
        return false;
    }
    <?php if($canCreateTasks == 'TRUE') echo '$("#projectForm :disabled ").each(function(){this.disabled = false});'; ?>
  }
  function reviewChange(event,id){
      console.log(event);
      projectid = id;
      needsReview = event.target.checked ? 'TRUE' : 'FALSE';
      $.post("ajaxQuery/AJAX_db_utility.php",{
          needsReview: needsReview,
          function: "changeReview",
          projectid: projectid
      },function(data){
          console.log(data);
      });
  }
</script>
<script>
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
})

</script>
</div>
<?php include dirname(__DIR__) . '/footer.php'; ?>
