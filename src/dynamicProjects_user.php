<?php include 'header.php';
enableToDynamicProjects($userID);
require "dynamicProjects_classes.php";
if (!function_exists('stripSymbols')) {
    function stripSymbols($s)
    {
        $result = "";
        foreach (str_split($s) as $char) {
            if (ctype_alnum($char)) {
                $result = $result . $char;
            }
        }
        return $result;
    }
}

$forceCreate = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deletenote"])) {
    $id = $_POST["id"] ?? "";
    $id = $conn->real_escape_string($id);
    $conn->query("DELETE FROM dynamicprojectsnotes WHERE projectid = '$id'");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["note"])) {
    $id = $_POST["id"] ?? "";
    $id = $conn->real_escape_string($id);
    $text = $_POST["notetext"] ?? "error";
    $conn->query("INSERT INTO dynamicprojectsnotes (projectid,notetext,notecreator) VALUES ('$id','$text',$userID)");
    echo $conn->error;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deletenote"])) {
    $note_id = $_POST["id"] ?? "";
    $conn->query("DELETE FROM dynamicprojectsnotes WHERE noteid = $note_id");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    require_once __DIR__ . "/utilities.php";
    $project = $_POST["id"];
    $picture = uploadFile("image", 1, 0, 1);
    $picture = "data:image/jpeg;base64," . base64_encode($picture);
    $stmt = $conn->prepare("INSERT INTO dynamicprojectspictures (projectid,picture) VALUES ('$project', ?)");
    echo $conn->error;
    $null = null;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $picture);
    $stmt->execute();
    echo $stmt->error;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editDynamicProject"])) {
    //edit deletes the project and recreates it
    $forceCreate = true;
    $id = $id = $_POST["id"] ?? "error - no id";
    $id = $conn->real_escape_string($id);
    // $conn->query("DELETE FROM dynamicprojectsclients WHERE projectid = '$id'");
    // echo $conn->error;
    // $conn->query("DELETE FROM dynamicprojectsemployees WHERE projectid = '$id'");
    // echo $conn->error;
    // $conn->query("DELETE FROM dynamicprojectsoptionalemployees WHERE projectid = '$id'");
    // echo $conn->error;
    // $conn->query("DELETE FROM dynamicprojectspictures WHERE projectid = '$id'");
    // echo $conn->error;
    // $conn->query("DELETE FROM dynamicprojectsseries WHERE projectid = '$id'");
    // echo $conn->error;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST["dynamicProject"]) || $forceCreate)) {
    // $connectIdentification = $conn->query("SELECT id FROM identification")->fetch_assoc()["id"];
    $id = $_POST["id"] ?? "";
    // $name = $_POST["name"] ?? "missing name";
    // $description = $_POST["description"] ?? "missing description";
    // $company = $_POST["company"] ?? "";
    // $color = $_POST["color"] ?? "#FFFFFF";
    // $start = $_POST["start"] ?? date("Y-m-d");
    // $end = $_POST["endradio"] ?? "";
    // $status = $_POST["status"] ?? 'DRAFT';
    // $priority = intval($_POST["priority"] ?? "3") ?? 3;
    // $parent = $_POST["parent"] ?? "";
    $pictures = $_POST["imagesbase64"] ?? false;
    // $owner = $_POST["owner"] ?? $userID ?? "";
    // $clients = $_POST["clients"] ?? array();
    // $employees = $_POST["employees"] ?? array();
    // $optional_employees = $_POST["optionalemployees"] ?? array();
    $completed = (int) $_POST["completed"] ?? 0;
    // //series one of: once daily_every_nth daily_every_weekday weekly monthly_day_of_month monthly_nth_day_of_week yearly_nth_day_of_month yearly_nth_day_of_week
    // $series = $_POST["series"] ?? "once";
    // if ($end == "no") {
    //     $end = "";
    // } else if ($end == "number") {
    //     $end = $_POST["endnumber"] ?? "";
    // } else if ($end == "date") {
    //     $end = $_POST["enddate"] ?? "";
    // }
    // $series = new ProjectSeries($series, $start, $end);
    // $series->daily_days = (int) $_POST["daily_days"] ?? 1;
    // $series->weekly_weeks = (int) $_POST["weekly_weeks"] ?? 1;
    // $series->weekly_day = $_POST["weekly_day"] ?? "monday";
    // $series->monthly_day_of_month_day = (int) $_POST["monthly_day_of_month_day"] ?? 1;
    // $series->monthly_day_of_month_month = (int) $_POST["monthly_day_of_month_month"] ?? 1;
    // $series->monthly_nth_day_of_week_nth = (int) $_POST["monthly_nth_day_of_week_nth"] ?? 1;
    // $series->monthly_nth_day_of_week_day = $_POST["monthly_nth_day_of_week_day"] ?? "monday";
    // $series->monthly_nth_day_of_week_month = (int) $_POST["monthly_nth_day_of_week_month"] ?? 1;
    // $series->yearly_nth_day_of_month_nth = (int) $_POST["yearly_nth_day_of_month_nth"] ?? 1;
    // $series->yearly_nth_day_of_month_month = $_POST["yearly_nth_day_of_month_month"] ?? "JAN";
    // $series->yearly_nth_day_of_week_nth = (int) $_POST["yearly_nth_day_of_week_nth"] ?? 1;
    // $series->yearly_nth_day_of_week_day = $_POST["yearly_nth_day_of_week_day"] ?? "monday";
    // $series->yearly_nth_day_of_week_month = $_POST["yearly_nth_day_of_week_month"] ?? "JAN";
    // echo "<br><br><br>";
    // if ($parent == "none") {
    //     $parent = "";
    // }
    // if (empty($company) || !is_numeric($company)) {
    //     echo "Company not set";
    // }
    // if ($id == "") {
    //     $id = uniqid($connectIdentification);
    //     while ($conn->query("SELECT * FROM dynamicprojects WHERE projectid = 'asdf'")->num_rows != 0) {
    //         $id = uniqid($connectIdentification);
    //     }
    // }
    // $owner = intval($owner) ?? $userID;
    // $nextDate = $series->get_next_date();
    // $series = serialize($series);
    // $series = base64_encode($series);

    // $description = $conn->real_escape_string($description);
    $id = $conn->real_escape_string($id);
    // $name = $conn->real_escape_string($name);
    // $color = $conn->real_escape_string($color);
    // $start = $conn->real_escape_string($start);
    // $end = $conn->real_escape_string($end);
    // $status = $conn->real_escape_string($status);
    // $parent = $conn->real_escape_string($parent);
    //$conn->query("INSERT INTO dynamicprojects (projectid,projectname,projectdescription, companyid, projectcolor, projectstart,projectend,projectstatus,projectpriority, projectparent, projectowner) VALUES ('$id','$name','$description', $company, '$color', '$start', '$end', '$status', '$priority', '$parent', '$owner') ON DUPLICATE KEY UPDATE projectname='$name', projectdescription = '$description', companyid=$company, projectcolor='$color', projectstart='$start', projectend='$end', projectstatus='$status', projectpriority='$priority', projectparent='$parent', projectowner='$owner'");
    // $conn->query("UPDATE dynamicprojects SET ");
    // echo $conn->error;
    // series
    // $stmt = $conn->prepare("INSERT INTO dynamicprojectsseries (projectid,projectnextdate,projectseries) VALUES ('$id','$nextDate',?)");
    // echo $conn->error;
    // $null = null;
    // $stmt->bind_param("b", $null);
    // $stmt->send_long_data(0, $series);
    // $stmt->execute();
    // echo $stmt->error;
    // /series
    if ($pictures) {
        foreach ($pictures as $picture) {
            // $conn->query("INSERT INTO dynamicprojectspictures (projectid,picture) VALUES ('$id','$picture')");
            $stmt = $conn->prepare("INSERT INTO dynamicprojectspictures (projectid,picture) VALUES ('$id',?)");
            echo $conn->error;
            $null = null;
            $stmt->bind_param("b", $null);
            $stmt->send_long_data(0, $picture);
            $stmt->execute();
            echo $stmt->error;
        }
    }
    $conn->query("UPDATE dynamicprojectsclients SET projectcompleted = '$completed' WHERE projectid = '$id'");
    // foreach ($employees as $employee) {
    //     $employee = intval($employee);
    //     $conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid) VALUES ('$id',$employee)");
    // }
    // foreach ($optional_employees as $optional_employee) {
    //     $optional_employee = intval($optional_employee);
    //     $conn->query("INSERT INTO dynamicprojectsoptionalemployees (projectid, userid) VALUES ('$id',$optional_employee)");
    // }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CREATE TABLE dynamicprojectsbookings(
    //     id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    //     bookingstart DATETIME,
    //     bookingend DATETIME,
    //     userid INT(6) UNSIGNED,
    //     bookingtext VARCHAR(1000)
    //   );
    $projectID = $_POST["id"] ?? "";
    $clientID = $_POST["client"] ?? -1;
    $completed = intval($_POST["completed"] ?? 0);
    if (isset($_POST["play"])) {
        $conn->query("INSERT INTO dynamicprojectsbookings (projectid, userid) VALUES ('$projectID', $userID)");
    } else if (isset($_POST["pause"])) {
        $bookingtext = $_POST["description"] ?? "no description";
        $conn->query("UPDATE dynamicprojectsbookings SET bookingend = CURRENT_TIMESTAMP, bookingtext = '$bookingtext',bookingclient = '$clientID' WHERE userid = $userID AND projectid = '$projectID' AND bookingend IS NULL");
        echo $conn->error;
        $projectDataId = $conn->query("SELECT id FROM projectData WHERE dynamicprojectid = '$projectID' AND clientID = $clientID ")->fetch_assoc()["id"];
        $startEndArray = $conn->query("SELECT bookingstart, bookingend FROM dynamicprojectsbookings WHERE projectid = '$projectID' AND userid = $userID ORDER BY bookingend DESC LIMIT 1")->fetch_assoc();
        $bookingStart = $startEndArray["bookingstart"];
        $bookingEnd = $startEndArray["bookingend"];
        echo $conn->error;
        $internInfo = "Client ID $clientID, completed $completed%";
        $conn->query("UPDATE dynamicprojectsclients SET projectcompleted = '$completed' WHERE projectid = '$projectID' AND clientid = '$clientID'");
        //$conn->query("INSERT INTO logs (time,timeEnd,userID) VALUES ('$bookingStart','$endDate',$userID)");
        //$indexIM  = $conn->query("SELECT indexIM FROM logs WHERE timeEnd = '$endDate' AND userID = $userID")->fetch_assoc()["indexIM"]; //log id
        $indexIM = mysqli_query($conn, "SELECT * FROM $logTable WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00'")->fetch_assoc()["indexIM"];
        echo $conn->error;
        $conn->query("INSERT INTO projectBookingData (start, end, projectID, timestampID, infoText, internInfo, bookingType)
        VALUES('$bookingStart', '$bookingEnd', $projectDataId, $indexIM, '$bookingtext', '$internInfo', 'project' )"); // '$bookingEnd' causes duplicate key restraint to fail
        echo $conn->error;
    } else if (isset($_POST["stop"])) {
        echo "stop not implemented";
    }
}

?>
<!-- BODY -->
<table class="table">
<thead>
    <tr>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_NAME"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_DESCRIPTION"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COMPANY"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_CLIENTS"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_START"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_END"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_SERIES"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_STATUS"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OWNER"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_EMPLOYEES"]; ?></th>
        <th style="white-space: nowrap;width: 1%;"></th> <!-- space for play or pause -->
        <th style="white-space: nowrap;width: 1%;"></th> <!-- space for edit and bookings -->
    </tr>
</thead>
<tbody>
    <?php
$dateNow = date('Y-m-d');
$result = $conn->query("SELECT dynamicprojects.*, dynamicprojectsemployees.*,dynamicprojectsoptionalemployees.*
    FROM dynamicprojects 
    LEFT JOIN dynamicprojectsemployees ON dynamicprojects.projectid = dynamicprojectsemployees.projectid
    LEFT JOIN dynamicprojectsoptionalemployees ON dynamicprojects.projectid = dynamicprojectsoptionalemployees.projectid
    WHERE ( dynamicprojectsoptionalemployees.userid = $userID OR dynamicprojectsemployees.userid = $userID OR dynamicprojects.projectowner = $userID )
    AND dynamicprojects.projectstatus = 'ACTIVE'
    AND dynamicprojects.projectstart <= '$dateNow'
    AND (dynamicprojects.projectend = '' OR dynamicprojects.projectend > 0 OR dynamicprojects.projectend <= '$dateNow')
    GROUP BY dynamicprojects.projectid
    ORDER BY dynamicprojects.projectstart
    ");
    while ($row = $result->fetch_assoc()) {
    $id = $row["projectid"];
    $name = $row["projectname"];
    $description = $row["projectdescription"];
    $company = $row["companyid"];
    $companyName = $conn->query("SELECT name FROM companyData where id = $company")->fetch_assoc()["name"];
    $color = $row["projectcolor"];
    $start = $row["projectstart"];
    $end = $row["projectend"];
    $status = $row["projectstatus"];
    $priority = $row["projectpriority"];
    $pictureResult = $conn->query("SELECT picture FROM dynamicprojectspictures WHERE projectid='$id'");
    $seriesResult = $conn->query("SELECT projectseries FROM dynamicprojectsseries WHERE projectid='$id'");
    echo $conn->error;
    $parent = $row["projectparent"];
    $ownerId = $row["projectowner"];
    // $completed = $row["projectcompleted"];
    $owner = $conn->query("SELECT * FROM UserData WHERE id='$ownerId'")->fetch_assoc();
    $owner = "${owner['firstname']} ${owner['lastname']}";
    $clientsResult = $conn->query("SELECT * FROM dynamicprojectsclients INNER JOIN  $clientTable ON  $clientTable.id = dynamicprojectsclients.clientid  WHERE projectid='$id'");
    $employeesResult = $conn->query("SELECT * FROM dynamicprojectsemployees INNER JOIN UserData ON UserData.id = dynamicprojectsemployees.userid WHERE projectid='$id'");
    $optional_employeesResult = $conn->query("SELECT * FROM dynamicprojectsoptionalemployees INNER JOIN UserData ON UserData.id = dynamicprojectsoptionalemployees.userid WHERE projectid='$id'");
    $pictures = array();
    $clients = array();
    $employees = array();
    $optional_employees = array();
    if (!empty($parent)) {
        $parent = $conn->real_escape_string($parent);
        $parent = $conn->query("SELECT * FROM dynamicprojects WHERE projectid='$parent'")->fetch_assoc()["projectname"];
    }
    $series = null;
    if ($seriesResult) {
        $series = $seriesResult->fetch_assoc()["projectseries"];
        $series = base64_decode($series);
        $series = unserialize($series, array("allowed_classes" => array("ProjectSeries")));
    } else {
        echo "series couldn't be unserialized";
    }

    echo "<tr>";
    echo "<td style='background-color:$color;'>$name</td>";
    echo "<td>$description</td>";
    echo "<td>$companyName</td>";
    echo "<td>";
    $completed = 0; //percentage of overall project completed 0-100
    while ($clientRow = $clientsResult->fetch_assoc()) {
        array_push($clients, $clientRow["id"]);
        $completed += intval($clientRow["projectcompleted"]);
        $client = $clientRow["name"];
        echo "$client, ";
    }
    $completed = intval($completed / count($clients)); // average completion
    echo "</td>";
    echo "<td>$start</td>";
    echo "<td>$end</td>"; // no end = ""
    echo "<td>$series</td>";
    echo "<td>$status</td>";
    echo "<td>$priority</td>";
    // echo "<td>$parent</td>";
    echo "<td>$owner</td>";
    echo "<td>";
    while ($employeeRow = $employeesResult->fetch_assoc()) {
        array_push($employees, $employeeRow["id"]);
        $employee = "${employeeRow['firstname']} ${employeeRow['lastname']}";
        echo "$employee, ";
    }
    while ($optional_employeeRow = $optional_employeesResult->fetch_assoc()) {
        array_push($optional_employees, $optional_employeeRow["id"]);
        $optional_employee = "${optional_employeeRow['firstname']} ${optional_employeeRow['lastname']}";
        echo "$optional_employee, ";
    }
    echo "</td>";

    $modal_title = $lang["DYNAMIC_PROJECTS_EDIT_DYNAMIC_PROJECT"];
    $modal_name = $name;
    $modal_company = $company;
    $modal_description = $description;
    $modal_color = $color;
    $modal_start = $start;
    $modal_end = $end; // Possibilities: ""(none);number (repeats); Y-m-d (date)
    $modal_status = $status; // Possibiilities: "ACTIVE","DEACTIVATED","DRAFT","COMPLETED"
    $modal_priority = $priority;
    $modal_id = $id;
    $modal_pictures = $pictures;
    $modal_parent = $parent; //default: "none" or ""
    $modal_clients = $clients; //array of ids
    $modal_owner = $ownerId;
    $modal_employees = $employees;
    $modal_optional_employees = $optional_employees;
    $modal_series = $series;
    $modal_isAdmin = false;
    $modal_completed = $completed;
    echo "<td><form method='post'>";
    echo "<input type='hidden' name='id' value='$id'/>";
    $bookingActive = $conn->query("SELECT * FROM dynamicprojectsbookings WHERE userid = $userID AND projectid = '$modal_id' AND bookingend IS NULL")->num_rows > 0;
    if (!$bookingActive) {
        echo "<button class='btn btn-default' type='submit' name='play' value='true'><i class='fa fa-play' ></i></button>";
    } else {
        $strippedID = stripSymbols($id);
        echo $conn->error;
        echo "<button class='btn btn-default' type='button' data-toggle='modal' data-target='#bookDynamicProject$strippedID'><i class='fa fa-pause'></i></button>";
        //echo "<button class='btn btn-default' type='submit' name='stop'  value='true'><i class='fa fa-stop' ></i></button>";
        ?>

            <!-- booking modal -->
                <div class="modal fade" id="bookDynamicProject<?php echo stripSymbols($modal_id); ?>" tabindex="-1" role="dialog" aria-labelledby="bookDynamicProjectLabel<?php echo stripSymbols($modal_id); ?>">
                    <div class="modal-dialog" role="form">
                        <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="bookDynamicProjectLabel<?php echo stripSymbols($modal_id); ?>">
                                        <?php echo $lang["DYNAMIC_PROJECTS_BOOKING_PROMPT"]; ?>
                                    </h4>
                                </div>
                                <br>
                                <div class="modal-body">
                                    <!-- modal body -->
                                        <textarea name="description" required class="form-control" style="max-width:100%; min-width:100%"></textarea>
                                        <!-- client selection -->
                                        <?php if($conn->query("SELECT count(*) count FROM dynamicprojectsclients WHERE projectid = '$modal_id'")->fetch_assoc()["count"] > 1): ?>
                                        <label><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_CLIENT"]; ?>*:</label>
                                            <select class="form-control js-example-basic-single" name="client"
                                                id="bookDynamicProjectClient<?php echo stripSymbols($modal_id); ?>"  required>
                                                <?php
$modal_clientsResult = $conn->query("SELECT * FROM dynamicprojectsclients LEFT JOIN $clientTable ON $clientTable.id = dynamicprojectsclients.clientid WHERE projectid = '$modal_id'");
        while ($modal_clientRow = $modal_clientsResult->fetch_assoc()) {
            $modal_client_id = $modal_clientRow["id"];
            $modal_client = $modal_clientRow["name"];
            echo "<option value='$modal_client_id'>$modal_client</option>";
        }
        ?>
                                            </select>
                                        <?php else: ?> <!-- no selection if only one client -->
                                        <input id="bookDynamicProjectClient<?php echo stripSymbols($modal_id); ?>"  
                                        type="hidden" name="client" 
                                        value="<?php echo $conn->query("SELECT * FROM dynamicprojectsclients WHERE projectid = '$modal_id'")->fetch_assoc()["clientid"] ?>" />
                                        <?php endif; ?>
                                        <!-- /client selection -->
                                        <label><?php echo $lang["DYNAMIC_PROJECTS_PERCENTAGE_FINISHED"]; ?>*:</label>
                                        <input type="number" class="form-control" name="completed" min="0" max="100" id="bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>" />
                                        <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>">
                                            Abgeschlossen
                                        </label>
                                        </div>
                                        <script>
                                            $("#bookDynamicProjectClient<?php echo stripSymbols($modal_id); ?>").change(function(event){
                                                // console.log($(event.target).val())
                                                    $.ajax({
                                                        url: 'ajaxQuery/AJAX_getDynamicProjectClientsCompleted.php',
                                                        dataType: 'json',
                                                        data: {id:"<?php echo $modal_id; ?>",client:$(event.target).val()},
                                                        cache: false,
                                                        type: 'POST',
                                                        success: function (response) {
                                                            $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val(response.completed)
                                                            $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").keyup()
                                                        },
                                                        error: function(response){
                                                            console.error(response.error);
                                                        }
                                                    })
                                                }).change()
                                            $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").keyup(function(event){
                                                console.log(event,$("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val() == 100)
                                                if($("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val() == 100){
                                                    $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked', true);
                                                }else{
                                                    $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked', false);
                                                }
                                            }).keyup()
                                            $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").change(function(event){
                                                console.log(event,$("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val() == 100)
                                                if($("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val() == 100){
                                                    $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked', true);
                                                }else{
                                                    $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked', false);
                                                }
                                            }).change()
                                            $("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").change(function(event){
                                                console.log(event)
                                                if($("#bookDynamicProjectCompletedCheckbox<?php echo stripSymbols($modal_id); ?>").prop('checked')){
                                                    $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").val(100)
                                                }else{
                                                    $("#bookDynamicProjectClient<?php echo stripSymbols($modal_id); ?>").change()
                                                }
                                                $("#bookDynamicProjectCompleted<?php echo stripSymbols($modal_id); ?>").keyup()
                                            }).change()
                                        </script>
                                    <!-- /modal body -->
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary" name="pause" value="true"><?php echo $lang['SAVE']; ?></button>
                                </div>
                        </div>
                    </div>
                </div>
            <!-- /booking modal -->

            <?php
}

    echo "</form></td>";

    echo "<td>";
    $modal_symbol = "fa fa-cog";
    require "dynamicProjects_template.php";
    $modal_title = $lang["DYNAMIC_PROJECTS_NOTES"];
    require "dynamicProjects_comments.php";
    echo "</td>";
    echo "</tr>";

    ?>
        <!--<button data-toggle="modal" data-target='#bookDynamicProject<?php echo stripSymbols($modal_id); ?>' >Toggle_</button>-->


        <?php
}
?>
</tbody>
</table>
<!-- /BODY -->
<?php include 'footer.php';?>