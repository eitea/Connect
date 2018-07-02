<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";
require dirname(__DIR__) . "/utilities.php";

session_start();
$userID = $_SESSION["userid"] or die("Session died");
$privateKey = $_SESSION['privateKey'];
$x = test_input($_GET['projectid'], 1);

$result = $conn->query("SELECT activity, userID FROM dynamicprojectslogs WHERE projectid = '$x' AND
    ((activity = 'VIEWED' AND userid = $userID) OR ((activity = 'CREATED' OR activity = 'EDITED') AND userID != $userID)) ORDER BY logTime DESC LIMIT 1"); echo $conn->error;
if (($row = $result->fetch_assoc()) && $row['activity'] != 'VIEWED') {
    $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'VIEWED', $userID)");
}

$result = $conn->query("SELECT projectname, projectdescription, projectstart, projectstatus, projectleader,
	projectmailheader, projectpercentage, v2, d.companyid, d.clientid, d.clientprojectid
    FROM dynamicprojects d LEFT JOIN dynamicprojectsemployees ON dynamicprojectsemployees.projectid = d.projectid
    LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid LEFT JOIN relationship_team_user ON relationship_team_user.teamID = dynamicprojectsteams.teamid
    WHERE (dynamicprojectsemployees.userid = $userID OR d.projectowner = $userID OR (relationship_team_user.userID = $userID AND relationship_team_user.skill >= d.level))
    AND d.projectstart <= UTC_TIMESTAMP and d.projectid = '$x'");
$dynrow = $result->fetch_assoc();
$projectleader = $dynrow['projectleader'];

$showMissingBookings = true;
$missingBookingsArray = array();
$isInUse = $conn->query("SELECT userID, p.id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00' AND dynamicID = '$x' LIMIT 1");
$useRow = $isInUse->fetch_assoc();
if($useRow){
    $showMissingBookings = false;
}
$result = $conn->query("SELECT DISTINCT companyID FROM relationship_company_client WHERE userID = $userID OR $userID = 1");
$available_companies = array('-1'); //care
while ($result && ($row = $result->fetch_assoc())) {
    $available_companies[] = $row['companyID'];
}

if($showMissingBookings){
    echo $conn->error;
    $occupation = array('bookingID' => $useRow['id'],
	'dynamicID' => $x,
	'companyid' => $dynrow['companyid'],
	'clientid' => $dynrow['clientid'],
	'projectid' => $dynrow['clientprojectid'],
	'percentage' => $dynrow['projectpercentage']);
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
    $result = mysqli_query($conn, "SELECT * FROM logs WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $start = substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']), 11, 5);
        $end = substr(carryOverAdder_Hours(getCurrentTimestamp(), $row['timeToUTC']), 11, 5);
        $date = substr(carryOverAdder_Hours($row['time'], $row['timeToUTC']), 0, 10);
        $indexIM = $row['indexIM']; //this value must not change
        $timeToUTC = $row['timeToUTC']; //just in case.
        $missingBookingsArray[] = array("start" => $start, "end" => $end, "date" => $date, "indexIM" => $indexIM, "timeToUTC" => $timeToUTC);
    } else {
        // echo "no valid timestamp found";
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
        if($row['timeEnd'] == '0000-00-00 00:00:00'){
            $B = date('Y-m-d H:i:s');
        }else{
            $B = $row['timeEnd'];
        }
        if ($has_bookings && timeDiff_Hours($A, $B) > $bookingTimeBuffer / 60) { //also check end
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

// missingBookingsArray now contains all available timestamps where booking data is missing
echo $conn->error;
if (sizeof($missingBookingsArray) == 0) {
    $showMissingBookings = false;
}
$archiveResult = $conn->query("SELECT uniqID, name, uploadDate, type FROM archive WHERE category = 'TASK' AND categoryID = '$x'");
$messageResult = $conn->query("SELECT id FROM messenger_conversations WHERE category='task' AND categoryID='$x'");
?>

<div id="infoModal-<?php echo $x; ?>" class="modal fade">
    <div class="modal-dialog modal-content modal-lg">
        <div class="modal-header h4">Verlauf</div>
        <div class="modal-body">
            <ul class="nav nav-tabs">
                <li class="active"><a data-toggle="tab" href="#projectDescription<?php echo $x; ?>">Beschreibung</a></li>
                <li><a data-toggle="tab" href="#projectInfoBookings<?php echo $x; ?>">Buchungen</a></li>
                <li><a data-toggle="tab" href="#projectInfoLogs<?php echo $x; ?>">Logs</a></li>
				<?php if($archiveResult && $archiveResult->num_rows > 0): ?>
					<li><a data-toggle="tab" href="#projectInfoData<?php echo $x; ?>">Dateien
						<?php echo '<span class="badge badge-alert">'.$archiveResult->num_rows.'</span>'; //5b2b907550d12 ?></a>
					</li>
				<?php endif; ?>
                <?php if(false): ?><li><a data-toggle="tab" href="#projectMessages<?php echo $x; ?>" id="projectMessagesTab<?php echo $x; ?>">Messages</a></li><?php endif; ?>
                <?php if($showMissingBookings): ?><li><a data-toggle="tab" href="#projectForgottenBooking<?php echo $x; ?>">Zeit nachbuchen</a></li><?php endif; ?>
				<?php if($messageResult && $messageResult->num_rows > 0):
					$openChatID = $messageResult->fetch_assoc()['id'];
					$unreadMessages = getUnreadMessages($openChatID);
				?>
					<li><a data-toggle="tab" href="#projectMessagesTab<?php echo $x; ?>">Nachrichten
						<?php if($unreadMessages) echo '<span class="badge badge-alert">'.$unreadMessages.'</span>'; //5b2b907550d12 ?></a> 
					</li>
				<?php endif; ?>
            </ul>
            <div class="tab-content">
				<div id="projectInfoData<?php echo $x; ?>" class="tab-pane fade"><br>
					<table class="table table-hover">
						<thead>
							<tr>
								<th>Name</th>
								<th>Upload Datum</th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$result = $conn->query("SELECT id FROM identification LIMIT 1");
							if($row = $result->fetch_assoc()){
								$identifier = $row['id'];
							} else {
								$identifier = uniqid('');
								$conn->query("INSERT INTO identification (id) VALUES ('$identifier')");
							}
							$description = asymmetric_encryption('TASK', $dynrow['projectdescription'], $userID, $privateKey, $dynrow['v2']);

							if($archiveResult && $archiveResult->num_rows > 0){
								$bucket = $identifier .'-tasks';
								$s3 = getS3Object($bucket);
							}
							while($archiveResult && ($row = $archiveResult->fetch_assoc())){
								echo '<tr>';
								echo '<td>'.$row['name'].'.'.$row['type'].'</td>';
								echo '<td>'.$row['uploadDate'].'</td>';
								echo '<td><form method="POST" style="display:inline" action="../project/detailDownload" target="_blank"><input type="hidden" name="keyReference" value="TASK_'.$x.'" />
								<button type="submit" class="btn btn-default" name="download-file" value="'.$row['uniqID'].'"><i class="fa fa-download"></i></form></td>';
								echo '</tr>';
								if($row['type'] == 'jpg' || $row['type'] == 'jpeg'){
							        $object = $s3->getObject(array(
							            'Bucket' => $bucket,
							            'Key' => $row['uniqID']
							        ));

									if(strpos($description, $row['uniqID'])){
										$description = str_replace("cid:".$row['uniqID'], "data:image/jpeg;base64,".base64_encode(asymmetric_encryption('TASK', $object[ 'Body' ], $userID, $privateKey, $dynrow['v2'])), $description);
									} else {
										$description .= '<img style="width:80%;" src="data:image/jpeg;base64,'.base64_encode(asymmetric_encryption('TASK', $object[ 'Body' ], $userID, $privateKey, $dynrow['v2'])).'" />';
									}
								}
							}
							?>
						</tbody>
					</table>
				</div>
                <div id="projectDescription<?php echo $x; ?>" class="tab-pane fade in active"><br>
                    <?php
                    $micro = $conn->query("SELECT * FROM microtasks WHERE projectid = '$x'");
                    if($micro && $micro->num_rows > 0){
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
					<?php
					if($dynrow['projectmailheader']){ //5b1fe6dbb361a
						echo '<br><label>E-Mail Header</label><br><pre>'.asymmetric_encryption('TASK', $dynrow['projectmailheader'], $userID, $privateKey, $dynrow['v2']).'</pre>';
					}
					?>
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
                            <input type="hidden" name="time-range" id="real-time-range<?php echo $x; ?>" />
                            <select class="js-example-basic-single" id="time-range-chooser<?php echo $x; ?>">
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
                        <div class="col-md-4">
                            <label id="time-range-date<?php echo $x; ?>"></label>
                            <div class="input-group">
                                <input type="time" class="form-control" readonly onkeypress="return event.keyCode != 13;" id="time-range-start<?php echo $x; ?>" >
                                <span class="input-group-addon"> - </span>
                                <input type="time" class="form-control timepicker" onkeypress="return event.keyCode != 13;" id="time-range-end<?php echo $x; ?>"/>
                            </div>
                        </div>
                        <script>
                            (function(){ //creates own scope
                                function updateStartAndEnd(date,start,end){
                                    $("#time-range-date<?php echo $x; ?>").html(date);
                                    $("#time-range-start<?php echo $x; ?>").val(start);
                                    $("#time-range-end<?php echo $x; ?>").val(end);
                                }
                                var timeRange = $("#time-range-chooser<?php echo $x; ?>").val(); // format date;start;end;timeToUTC;indexIM
                                $("#real-time-range<?php echo $x; ?>").val(timeRange)
                                var arr = timeRange.split(";");
                                updateStartAndEnd(arr[0], arr[1], arr[2]);
                                $("#time-range-chooser<?php echo $x; ?>").change(function(){
                                    var timeRange = $("#time-range-chooser<?php echo $x; ?>").val();
                                    $("#real-time-range<?php echo $x; ?>").val(timeRange);
                                    var arr = timeRange.split(";");
                                    updateStartAndEnd(arr[0], arr[1], arr[2]);
                                });
                                $("#time-range-end<?php echo $x; ?>").change(function(){
                                    var timeRange = $("#time-range-chooser<?php echo $x; ?>").val();
                                    var newEnd = $("#time-range-end<?php echo $x; ?>").val();
                                    var arr = timeRange.split(";");
                                    if(Number(newEnd.replace(":",""))>Number(arr[2].replace(":",""))){
                                        newEnd = arr[2];
                                    }else if (Number(newEnd.replace(":",""))<Number(arr[1].replace(":",""))){
                                        newEnd = arr[1];
                                    }
                                    arr[2] = newEnd;
                                    $("#time-range-end<?php echo $x; ?>").val(newEnd);
                                    timeRange = arr.join(";");
                                    $("#real-time-range<?php echo $x; ?>").val(timeRange);
                                });
                            })()
                        </script>
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
				<?php if(!empty($openChatID)): ?>
					<div id="projectMessagesTab<?php echo $x; ?>" class="tab-pane fade"><br> <?php include dirname(__DIR__).'/social/chatwindow.php'; ?> </div>
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
                if(strtotime($dynrow['projectstart']) < time() && $dynrow['projectstatus'] == 'ACTIVE' && $result->num_rows < 1 && !$hasActiveBooking){
                    if(!$projectleader){
                        echo "<button type='button' class='btn btn-default' title='Task starten' data-toggle='modal' data-target='#play-take-$x'><i class='fa fa-play'></i></button>";
                    } else {
                        echo "<button type='submit' class='btn btn-default' title='Task starten' name='play' value='$x'><i class='fa fa-play'></i></button>";
                    }
                    echo "<button type='button' class='btn btn-default' title='Task Planen' data-toggle='modal' data-target='#task-plan-$x'><i class='fa fa-clock-o'></i></button>";
                }
                if(!$projectleader){
                    echo "<button class='btn btn-default' type='submit' title='Task übernehmen' name='take_task' value='$x'><i class='fa fa-address-card'></i></button>";
                }
                ?>
                <button type="button" class="btn btn-default" data-dismiss="modal">O.K.</button>
            </form>
        </div>
    </div>
</div>
