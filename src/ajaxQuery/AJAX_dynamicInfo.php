<?php
require dirname(__DIR__) . "/connection.php";
require dirname(__DIR__) . "/language.php";
require dirname(__DIR__) . "/utilities.php";

session_start();
$userID = $_SESSION["userid"] or die("Session died");
$x = test_input($_GET['projectid'], 1);

$result = $conn->query("SELECT activity, userID FROM dynamicprojectslogs WHERE projectid = '$x' AND
    ((activity = 'VIEWED' AND userid = $userID) OR ((activity = 'CREATED' OR activity = 'EDITED') AND userID != $userID)) ORDER BY logTime DESC LIMIT 1"); echo $conn->error;
if (($row = $result->fetch_assoc()) && $row['activity'] != 'VIEWED') {
    $conn->query("INSERT INTO dynamicprojectslogs (projectid, activity, userID) VALUES ('$x', 'VIEWED', $userID)");
}
$showMissingBookings = true;
$missingBookingsArray = array();

//see open tasks user is part of
$result = $conn->query("SELECT d.projectid, projectname, projectdescription, projectcolor, projectstart, projectend, projectseries, projectstatus, projectpriority, projectowner, projectleader,
    projectpercentage, projecttags, d.companyid, d.clientid, d.clientprojectid, companyData.name AS companyName, clientData.name AS clientName, projectData.name AS projectDataName, needsreview, estimatedHours
    FROM dynamicprojects d LEFT JOIN companyData ON companyData.id = d.companyid LEFT JOIN clientData ON clientData.id = clientid LEFT JOIN projectData ON projectData.id = clientprojectid
    LEFT JOIN dynamicprojectsemployees ON dynamicprojectsemployees.projectid = d.projectid
    LEFT JOIN dynamicprojectsteams ON dynamicprojectsteams.projectid = d.projectid LEFT JOIN teamRelationshipData ON teamRelationshipData.teamID = dynamicprojectsteams.teamid
    WHERE (dynamicprojectsemployees.userid = $userID OR d.projectowner = $userID OR (teamRelationshipData.userID = $userID AND teamRelationshipData.skill >= d.level))
    AND d.projectstart <= UTC_TIMESTAMP and d.projectid = '$x'");
$row = $result->fetch_assoc();
$projectleader = $row['projectleader'];

// 5ac63505c0ecd
$projectname = $row['projectname'];

$stmt_booking = $conn->prepare("SELECT userID, p.id FROM projectBookingData p, logs WHERE p.timestampID = logs.indexIM AND `end` = '0000-00-00 00:00:00' AND dynamicID = ?");
$stmt_booking->bind_param('s', $x);
$stmt_booking->execute();
$isInUse = $stmt_booking->get_result(); //max 1 row
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
                <?php if(false): ?><li><a data-toggle="tab" href="#projectMessages<?php echo $x; ?>" id="projectMessagesTab<?php echo $x; ?>">Messages</a></li><?php endif; ?>
                <?php if($showMissingBookings): ?><li><a data-toggle="tab" href="#projectForgottenBooking<?php echo $x; ?>">Zeit nachbuchen</a></li><?php endif; ?>
            </ul>
            <div class="tab-content">
                <div id="projectDescription<?php echo $x; ?>" class="tab-pane fade in active"><br>
                    <?php
                    $result = $conn->query("SELECT projectdescription, projectstatus, projectstart FROM dynamicprojects WHERE projectid = '$x'");
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
                <?php if(false): ?>
                <!-- Project Messages -->
                <div id="projectMessages<?php echo $x; ?>" class="tab-pane fade"><br>
                    <!-- Subject bar -->
                    <div id="subject_bar<?php echo $x; ?>" style="background-color: whitesmoke; border: 1px gainsboro solid; border-bottom: none; max-height: 10vh; padding: 10px;"><?php echo $projectname; ?></div>
                    
                    <!-- Messages -->
                    <div class="pre-scrollable" id="messages<?php echo $x; ?>" style="background-color: white; overflow: auto; overflow-x: hidden; border: 1px solid gainsboro; max-height: 55vh; padding-top: 5px"></div>

                    <!-- Response Field -->
                    <div id="chatinput<?php echo $x; ?>" style="padding-top: 5px;">
                        <form name="chatInputForm<?php echo $x; ?>" autocomplete="off">
                            <div class="input-group">
                                <!-- TODO: set resize to none and create a js listerner to auto resize the textarea -->
                                <textarea id="message<?php echo $x; ?>" wrap="hard" placeholder="Type a message" class="form-control" style="max-height: 11vh; resize: vertical;"></textarea>
                                <span id="sendButton<?php echo $x; ?>" class="input-group-addon btn btn-default" type="submit"><?php echo $lang['SEND'] ?></span>
                            </div>
                        </form>


                        <script>
                            messageLimit<?php echo $x; ?> = 10;
                            intervalID<?php echo $x; ?> = setInterval(function() {
                                getMessages("<?php echo $x; ?>", "<?php echo $projectname ?>", "#messages<?php echo $x; ?>", false, 10);
                            }, 1000);

                            // scroll down when the tab gets shown
                            $("#projectMessagesTab<?php echo $x; ?>").on('shown.bs.tab', function (e) {
                                $("#messages<?php echo $x; ?>").scrollTop($("#messages<?php echo $x; ?>")[0].scrollHeight)
                            });

                            // remove the interval, when leaving the tab
                            $("#projectMessagesTab<?php echo $x; ?>").on('hide.bs.tab', function (e) {
                                clearInterval(intervalID<?php echo $x; ?>);
                            });
                                        
                            // send on enter
                            var shiftPressed<?php echo "$x" ?> = false;
                            $("#message<?php echo $x; ?>").keydown(function(event) {
                                //shift + enter?
                                if(shiftPressed<?php echo $x ?> && event.which == 13) {
                                    $("#message<?php echo $x; ?>").append("<br>");
                                } else {
                                    // update shiftPressed when not pressed shift+enter
                                    if(event.which == 16) shiftPressed<?php echo $x ?> = true; else shiftPressed<?php echo $x ?> = false;
                                }
                                
                                if(event.which == 13 && !shiftPressed<?php echo $x ?>){
                                    event.preventDefault();

                                    if($("#message<?php echo $x; ?>").val().trim().length != 0){
                                        messageLimit<?php echo $x; ?>++;
                                        sendMessage("<?php echo $x; ?>", "<?php echo $projectname ?>", $("#message<?php echo $x; ?>").val(), "#messages<?php echo $x; ?>", messageLimit<?php echo $x; ?>);
                                        $("#message<?php echo $x; ?>").val("")
                                    }
                                }
                            });
                                
                            //submit
                            $("#chatinput<?php echo $x; ?>").submit(function (e) {
                                //prevent enter
                                e.preventDefault()

                                // send the message
                                messageLimit<?php echo $x; ?>++;
                                sendMessage("<?php echo $x; ?>", "<?php echo $projectname ?>", $("#message<?php echo $x; ?>").val(), "#messages<?php echo $x; ?>", messageLimit<?php echo $x; ?>);

                                // clear the field
                                $("#message<?php echo $x; ?>").val("")

                                // prevent form redirect
                                return false;
                            })

                            //scroll
                            $("#messages<?php echo $x; ?>").scroll(function(){
                                if($(this).scrollTop() == 0){
                                    $(this).scrollTop(1);
                                    messageLimit<?php echo $x; ?> += 1
                                    getMessages("<?php echo $x; ?>", "<?php echo $projectname ?>", "#messages<?php echo $x; ?>", false, messageLimit<?php echo $x; ?>);
                                }

                            })

                            //removes "do you really want to leave this site message"
                            $(document).ready(function() {
                                $(":input", document.chatInputForm+"<?php echo $x; ?>").bind("click", function() {
                                    window.onbeforeunload = null;
                                });
                            });

                            //###############################
                            //         AJAX Scripts
                            //###############################
                            function getMessages(taskID, taskName, target, scroll = false, limit = 50) {
                                if(taskID.length == 0 || taskName.length == 0) {
                                    return;
                                }

                                $.ajax({
                                    url: 'ajaxQuery/ajax_post_get_messages.php',
                                    data: {
                                        taskID: taskID,
                                        taskName: taskName,
                                        limit: limit,
                                    },
                                    type: 'GET',
                                    success: function (response) {
                                        if(response != "no messages"){
                                            $(target).html(response);

                                            //Scroll down
                                            if (scroll) $("#messages<?php echo $x; ?>").scrollTop($("#messages<?php echo $x; ?>")[0].scrollHeight)
                                        }else{
                                            $(target).html('<div style="padding: 20px" class="text-center">No messages available</div>')
                                        }
                                    },
                                    error: function (response) {
                                        $(target).html(response);
                                    },
                                });
                            }

                            function sendMessage(taskID, taskName, message, target, limit = 50) {
                                if(taskID.length == 0 || taskName.length == 0 || message.length == 0){
                                    return;
                                }

                                $.ajax({
                                    url: 'ajaxQuery/ajax_post_send_message.php',
                                    data: {
                                        taskID: taskID,
                                        taskName: taskName,
                                        message: message,
                                    },
                                    type: 'GET',
                                    success: function (response) {
                                        getMessages("<?php echo $x; ?>", "<?php echo $projectname ?>", "#messages<?php echo $x; ?>", true, limit);
                                    },
                                })
                            }
                        </script>
                    </div>
                </div>
                <?php endif; ?>

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
