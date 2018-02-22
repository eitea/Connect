<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";
require dirname(__DIR__) . "/utilities.php";
$x = preg_replace("/[^A-Za-z0-9]/", '', $_GET['projectid']);

session_start();
$userID = $_SESSION["userid"] or die("Session died");
$x = preg_replace("/[^A-Za-z0-9]/", '', $_GET['projectid']);
$result = $conn->query("SELECT activity, userID FROM dynamicprojectslogs WHERE projectID = '$x' AND
    ((activity = 'VIEWED' AND userid = $userID) OR ((activity = 'CREATED' OR activity = 'EDITED') AND userID != $userID)) ORDER BY logTime DESC LIMIT 1"); //changes here have to be synced with dynamicProjects.php
if (($row = $result->fetch_assoc()) && $row['activity'] != 'VIEWED') {
    $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'VIEWED', $userID)");
}
$showMissingBookings = true;
$missingBookingsArray = array();

//copied from dynamicProjects (when something changes there, update it here too)
//see open tasks user is part of
$result = $conn->query("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus, projectpriority, projectowner, projectleader,
    projectpercentage, projecttags, d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName, clientData.name AS clientName, projectData.name AS projectDataName, needsreview, estimatedHours
    FROM dynamicprojects d LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN projectData ON projectData.id = clientprojectid
    LEFT JOIN dynamicprojectsemployees ON dynamicprojectsemployees.projectid = d.projectid
    LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid LEFT JOIN teamRelationshipData ON teamRelationshipData.teamID = dynamicprojectsteams.teamid
    WHERE (dynamicprojectsemployees.userid = $userID OR d.projectowner = $userID OR (teamRelationshipData.userID = $userID AND teamRelationshipData.skill >= d.level))
    AND d.projectstart <= UTC_TIMESTAMP and d.projectid = '$x'");
$row = $result->fetch_assoc();
$stmt_booking = $conn->prepare("SELECT userID, p.id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00' AND dynamicID = ?");
$stmt_booking->bind_param('s', $x);
$stmt_booking->execute();
$isInUse = $stmt_booking->get_result(); //max 1 row
$useRow = $isInUse->fetch_assoc();

if($useRow){
    $showMissingBookings = false;
}

if($showMissingBookings){
    echo $conn->error;
    $occupation = array('bookingID' => $useRow['id'], 'dynamicID' => $x, 'companyid' => $row['companyid'], 'clientid' => $row['clientid'], 'projectid' => $row['clientprojectid'], 'percentage' => $row['projectpercentage']);
    // /copied from dynamicProjects

    // from userProjecting:
    $result = mysqli_query($conn, "SELECT * FROM $configTable");
    if ($result && ($row = $result->fetch_assoc())) {
        $cd = $row['cooldownTimer'];
        $bookingTimeBuffer = $row['bookingTimeBuffer'];
    } else {
        $bookingTimeBuffer = 5;
    }

    //first of the day
    $result = mysqli_query($conn, "SELECT * FROM $logTable WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $start = substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']), 11, 5);
        $end = substr(carryOverAdder_Hours(getCurrentTimestamp(), $row['timeToUTC']), 11, 5);
        $date = substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']), 0, 10);
        $indexIM = $row['indexIM']; //this value must not change
        $timeToUTC = $row['timeToUTC']; //just in case.
        $missingBookingsArray[] = array("start" => $start, "end" => $end, "date" => $date, "indexIM" => $indexIM, "timeToUTC" => $timeToUTC);
    } else {
        echo "no valid timestamp found";
        $showMissingBookings = false;
    }
}

if ($showMissingBookings) {
    //last booking
    $result = $conn->query("SELECT *, $projectTable.name AS projectName, $projectBookingTable.id AS bookingTableID FROM $projectBookingTable
    LEFT JOIN $projectTable ON ($projectBookingTable.projectID = $projectTable.id)
    LEFT JOIN $clientTable ON ($projectTable.clientID = $clientTable.id)
    WHERE $projectBookingTable.timestampID = $indexIM ORDER BY end DESC LIMIT 1;");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $date = substr(carryOverAdder_Hours($row['end'], $timeToUTC), 0, 10);
        $start = substr(carryOverAdder_Hours($row['end'], $timeToUTC), 11, 5);
    }

    //addendums
    $request_addendum = false;
    $result = $conn->query("SELECT * FROM logs WHERE userID = $userID AND DATE('" . carryOverAdder_Hours($start, -182) . "') < time"); //192h = 8 days
    // echo "SELECT * FROM logs WHERE userID = $userID AND DATE('".carryOverAdder_Hours($start, -182)."') < time;";
    while ($result && ($row = $result->fetch_assoc())) {
        $has_bookings = false;
        $i = $row['indexIM'];
        $A = $row['time'];
        $res_b = $conn->query("SELECT * FROM projectBookingData WHERE timestampID = $i ORDER BY start ASC");
        while ($row_b = $res_b->fetch_assoc()) { //changes must be carried over to addendum page
            $has_bookings = true;
            $B = $row_b['start'];
            if (timeDiff_Hours($A, $B) > $bookingTimeBuffer / 60) {
                $request_addendum = $i;
                //to process the next ADD
                $date = substr(carryOverAdder_Hours($A, $timeToUTC), 0, 10);
                $indexIM = $i;
                $timeToUTC = $row['timeToUTC'];
                $start = substr(carryOverAdder_Hours($A, $timeToUTC), 11, 5);
                $end = substr(carryOverAdder_Hours($B, $timeToUTC), 11, 5);
                $missingBookingsArray[] = array("start" => $start, "end" => $end, "date" => $date, "indexIM" => $indexIM, "timeToUTC" => $timeToUTC);
                //   break;
            }
            $A = $row_b['end'];
        }
        //   if($request_addendum) break;
        $B = $row['timeEnd'];
        if ($has_bookings && $B != '0000-00-00 00:00:00' && timeDiff_Hours($A, $B) > $bookingTimeBuffer / 60) { //also check end
            $request_addendum = $i;
            $date = substr($A, 0, 10);
            $indexIM = $i;
            $timeToUTC = $row['timeToUTC'];
            $start = substr(carryOverAdder_Hours($A, $timeToUTC), 11, 5);
            $end = substr(carryOverAdder_Hours($B, $timeToUTC), 11, 5);
            $missingBookingsArray[] = array("start" => $start, "end" => $end, "date" => $date, "indexIM" => $indexIM, "timeToUTC" => $timeToUTC);
            // break;
        }
    }
}

// /userProjecting
echo $conn->error;
if (sizeof($missingBookingsArray) == 0) {
    $showMissingBookings = false;
}
// missingBookingsArray now contains all available timestamps where booking data is missing
?>

<div id="infoModal-<?php echo $x; ?>" class="modal fade">
    <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header h4">Verlauf</div>
        <div class="modal-body">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#projectDescription<?php echo $x; ?>">Beschreibung</a></li>
                <li><a data-toggle="tab" href="#projectInfoBookings<?php echo $x; ?>">Buchungen</a></li>
                <li><a data-toggle="tab" href="#projectInfoLogs<?php echo $x; ?>">Logs</a></li>
                <?php if($showMissingBookings): ?><li><a data-toggle="tab" href="#projectForgottenBooking<?php echo $x; ?>">Zeit nachbuchen</a></li><?php endif; ?>
            </ul>
            <div class="tab-content">
                <div id="projectDescription<?php echo $x; ?>" class="tab-pane fade in active"><br>
                    <?php
                    $result = $conn->query("SELECT projectdescription, projectstatus FROM dynamicprojects WHERE projectid = '$x'");
                    $dynrow =  $result->fetch_assoc();
                    $description = $dynrow['projectdescription'];
                    $micro = $conn->query("SELECT * FROM microtasks WHERE projectid = '$x'");
                    if($micro){
                        while($nextmtask = $micro->fetch_assoc()){
                            if($nextmtask['ischecked'] == 'TRUE'){
                                $mtaskid = $nextmtask['microtaskid'];
                                $description = preg_replace("/id=.$mtaskid./","id=\"$mtaskid\" checked",$description);
                                $user = $nextmtask['finisher'];
                                $username = $conn->query("SELECT CONCAT(firstname,CONCAT(' ',lastname)) AS name FROM userdata WHERE id = '$user'");
                                if($username){
                                    $username = $username->fetch_assoc()['name'];
                                    $description = preg_replace("/id=.$mtaskid. checked disabled title=../","id=\"$mtaskid\" checked disabled title=\"$username\"",$description);
                                }
                            }
                        }
                    }
                    echo $description;
                    ?>
                </div>
                <div id="projectInfoBookings<?php echo $x; ?>" class="tab-pane fade"><br>
                    <table class="table table-hover">
                        <thead><tr>
                                <th>Benutzer</th>
                                <th>Datum</th>
                                <th>Von</th>
                                <th>Bis</th>
                                <th>Infotext</th>
                                <th>%</th>
                            </tr></thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT p.start, p.end, infoText, internInfo, firstname, lastname, timeToUTC
                            FROM projectBookingData p INNER JOIN logs ON logs.indexIM = p.timestampID LEFT JOIN UserData ON logs.userID = UserData.id WHERE p.dynamicID = '$x' ORDER BY p.start DESC");
                            while($result && ($row = $result->fetch_assoc())){
                                $A = carryOverAdder_Hours($row['start'],$row['timeToUTC']);
                                $B = 'Gerade in Arbeit';
                                if ($row['end'] != '0000-00-00 00:00:00') $B = substr(carryOverAdder_Hours($row['end'],$row['timeToUTC']), 11, 5);
                                echo '<tr>';
                                echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
                                echo '<td>'.substr($A,0,10).'</td>';
                                echo '<td>'.substr($A, 11, 5).'</td>';
                                echo '<td>'.$B.'</td>';
                                echo '<td>'.$row['infoText'].'</td>';
                                echo '<td>'.$row['internInfo'].'</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div id="projectInfoLogs<?php echo $x; ?>" class="tab-pane fade"><br>
                    <table class="table table-striped">
                        <thead><tr>
                                <th>Zeit <small>(System-Zeit)</small></th>
                                <th>Benutzer</th>
                                <th>Aktivität</th>
                        </tr></thead>
                        <tbody>
                            <?php
                            $result = $conn->query("SELECT firstname, lastname, p.activity, logTime FROM dynamicprojectslogs p LEFT JOIN UserData ON p.userID = UserData.id WHERE projectid = '$x'");
                            echo $conn->error;
                            while($result && ($row = $result->fetch_assoc())){
                                echo '<tr>';
                                echo '<td>'.$row['logTime'].'</td>';
                                echo '<td>'.$row['firstname'].' '.$row['lastname'].'</td>';
                                echo '<td>'.$row['activity'].'</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php if($showMissingBookings): ?>
                <div id="projectForgottenBooking<?php echo $x; ?>" class="tab-pane fade"><br>
                <form method="POST">
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
                        <!-- time chooser -->
                        <div class="col-md-4">
                            <label><?php echo $lang['TIME']; ?></label>
                            <select class="js-example-basic-single" name="time-range">
                            <?php 
                            $lastDate = "";
                            foreach ($missingBookingsArray as $booking) {
                                if($lastDate != $booking["date"]){
                                    echo "</optgroup><optgroup label='".$booking["date"]."'>"; //groups dates
                                    $lastDate = $booking["date"];
                                }
                                echo "<option value='".$booking["date"].";".$booking["start"].";".$booking["end"].";".$booking["timeToUTC"].";".$booking["indexIM"]."' >".$booking["start"]." - ".$booking["end"]."</option>";
                            }
                            ?>
                            </select>
                        </div>
                        <!-- /time chooser -->
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-warning" name="createForgottenBooking" value="<?php echo $occupation['dynamicID']; ?>"><?php echo $lang['BOOK']; ?></button>
                        </div>
                    </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal-footer">
            <form method="POST">
                <div class="pull-left"><?php echo $x; ?></div>
                <?php
                $result = $conn->query("SELECT id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND logs.userID = $userID AND `end` = '0000-00-00 00:00:00' LIMIT 1");
                $hasActiveBooking = $result->num_rows;
                $result = $conn->query("SELECT p.id FROM projectBookingData p WHERE `end` = '0000-00-00 00:00:00' AND dynamicID = '$x'");
                if($dynrow['projectstatus'] == 'ACTIVE' && $result->num_rows < 1 && !$hasActiveBooking){
                    echo "<button class='btn btn-default' type='submit' title='Task starten' name='play' value='$x'><i class='fa fa-play'></i></button>";
                }
                 ?>
                <button type="button" class="btn btn-default" data-dismiss="modal">O.K.</button>
            </form>
        </div>
    </div>
</div>
