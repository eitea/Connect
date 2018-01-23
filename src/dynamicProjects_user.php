<?php include 'header.php';
enableToDynamicProjects($userID);
require __DIR__ . "/misc/dynamicProjects_functions.php";
require __DIR__ . "/misc/dynamicProjects_ProjectSeries.php";

#region form_post
if ($_SERVER["REQUEST_METHOD"] == "POST"){
    if (isset($_POST["deletenote"])) {
        $id = $_POST["id"] ?? "";
        $id = $conn->real_escape_string($id);
        $conn->query("DELETE FROM dynamicprojectsnotes WHERE projectid = '$id'");
    }
    if (isset($_POST["note"])) {
        $id = $_POST["id"] ?? "";
        $id = $conn->real_escape_string($id);
        $text = $_POST["notetext"] ?? "error";
        $conn->query("INSERT INTO dynamicprojectsnotes (projectid,notetext,notecreator) VALUES ('$id','$text',$userID)");
        echo $conn->error;
    }
    if (isset($_POST["deletenote"])) {
        $note_id = $_POST["id"] ?? "";
        $conn->query("DELETE FROM dynamicprojectsnotes WHERE noteid = $note_id");
    }
    if (isset($_FILES["image"])) {
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
    if ((isset($_POST["dynamicProject"]) || isset($_POST["editDynamicProject"]))) {
        $id = $_POST["id"] ?? "";
        $pictures = $_POST["imagesbase64"] ?? false;
        $completed = (int) $_POST["completed"] ?? 0;
        $id = $conn->real_escape_string($id);
        if ($pictures) {
            foreach ($pictures as $picture) {
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
        echo $conn->error;
        echo "completed: ", $completed, "completed == 100", $completed == 100;
        if ($completed == 100) {
            $conn->query("UPDATE dynamicprojects SET projectstatus = 'COMPLETED' WHERE projectid = '$id'");
        }
    }


    $projectID = $_POST["id"] ?? "";
    $clientID = $_POST["client"] ?? -1;
    $completed = intval($_POST["completed"] ?? 0);
    if (isset($_POST["play"])) {
        $conn->query("INSERT INTO dynamicprojectsbookings (projectid, userid) VALUES ('$projectID', $userID)");
    } else if (isset($_POST["pause"])) {
        //insert into dynamicprojectsbookings, dynamicprojectsclients and projectBookingData
        $bookingtext = $_POST["description"] ?? "no description";
        $conn->query(
            "UPDATE dynamicprojectsbookings SET bookingend = CURRENT_TIMESTAMP, bookingtext = '$bookingtext',bookingclient = '$clientID'
             WHERE userid = $userID AND projectid = '$projectID' AND bookingend IS NULL"
        );
        echo $conn->error;
        $projectDataId = $conn->query("SELECT id FROM projectData WHERE dynamicprojectid = '$projectID' AND clientID = $clientID ")->fetch_assoc()["id"];
        $startEndArray = $conn->query(
            "SELECT bookingstart, bookingend FROM dynamicprojectsbookings
             WHERE projectid = '$projectID' AND userid = $userID
             ORDER BY bookingend DESC LIMIT 1"
        )->fetch_assoc();
        $bookingStart = $startEndArray["bookingstart"];
        $bookingEnd = $startEndArray["bookingend"];
        echo $conn->error;
        $internInfo = "Client ID $clientID, completed $completed%";
        $conn->query(
            "UPDATE dynamicprojectsclients SET projectcompleted = '$completed'
             WHERE projectid = '$projectID' AND clientid = '$clientID'"
        );
        $indexIM = mysqli_query($conn, "SELECT * FROM $logTable WHERE userID = $userID AND timeEnd = '0000-00-00 00:00:00'")->fetch_assoc()["indexIM"];
        echo $conn->error;
        $conn->query(
            "INSERT INTO projectBookingData (start, end, projectID, timestampID, infoText, internInfo, bookingType)
             VALUES('$bookingStart', '$bookingEnd', $projectDataId, $indexIM, '$bookingtext', '$internInfo', 'project' )"
        );
        echo $conn->error;
        //if all clients have 100 completion, set the project status to 'COMPLETED'
        $num_clients = $conn->query("SELECT count(*) count FROM dynamicprojectsclients WHERE projectid = '$projectDataId'")->fetch_assoc()["count"];
        $num_clients_completed = $conn->query("SELECT count(*) count FROM dynamicprojectsclients WHERE projectid = '$projectDataId' AND projectcompleted = 100")->fetch_assoc()["count"];
        if ($num_clients == $num_clients_completed) {
            $conn->query("UPDATE dynamicprojects SET projectstatus = 'COMPLETED' WHERE projectid = '$projectID'");
            echo $conn->error;
        }
    }
}
#endregion
#region filter
$filterings = array("savePage" => $this_page, "company" => 0, "client" => 0); //"project" => 0); //set_filter requirement
if (isset($_GET['custID']) && is_numeric($_GET['custID'])) {
    $filterings['client'] = test_input($_GET['custID']);
}
if (isset($_POST['filterClient'])) {
    $filterings['client'] = intval($_POST['filterClient']);
}
#endregion
?>
<?php include 'misc/set_filter.php';?>
<!-- BODY -->
<script src='../plugins/tinymce/tinymce.min.js'></script>
<style>
    table .form-control, .form-inline .form-control, .form-inline .input-group {
        width:100%;
    }
    table .form-control, .form-inline .form-control {
        display: block;
    }
</style>
<table class="table table-hover">
<thead>
    <tr>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_NAME"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_DESCRIPTION"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_COMPANY"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_CLIENTS"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_START"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_END"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_SERIES"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_PRIORITY"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_OWNER"]; ?></th>
        <th><?php echo $lang["DYNAMIC_PROJECTS_PROJECT_EMPLOYEES"]; ?></th>
        <th style="white-space: nowrap;width: 1%;"></th> <!-- space for edit, bookings and play/pause -->
    </tr>
</thead>
<tbody>
    <?php
$companyQuery = $clientQuery = "";
if ($filterings['company']) {$companyQuery = " AND dynamicprojects.companyid = " . $filterings['company'];}
if ($filterings['client']) {$clientQuery = " AND dynamicprojectsclients.clientid = " . $filterings['client'];}

$dateNow = date('Y-m-d');
$result = $conn->query(
    "SELECT dynamicprojects.*
    FROM dynamicprojects
    LEFT JOIN dynamicprojectsemployees
    ON dynamicprojects.projectid = dynamicprojectsemployees.projectid
    LEFT JOIN dynamicprojectsoptionalemployees
    ON dynamicprojects.projectid = dynamicprojectsoptionalemployees.projectid
    LEFT JOIN dynamicprojectsclients
    ON dynamicprojectsclients.projectid = dynamicprojects.projectid
    WHERE (
        dynamicprojectsoptionalemployees.userid = $userID
        OR dynamicprojectsemployees.userid = $userID
        OR dynamicprojects.projectowner = $userID
    )
    AND dynamicprojects.projectstatus = 'ACTIVE'
    AND dynamicprojects.projectstart <= '$dateNow'
    AND (
        dynamicprojects.projectend = ''
        OR dynamicprojects.projectend > 0
        OR dynamicprojects.projectend <= '$dateNow'
    )
    $companyQuery $clientQuery
    GROUP BY dynamicprojects.projectid
    ORDER BY dynamicprojects.projectstart"
);
echo $conn->error;
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
    $owner = $conn->query("SELECT * FROM UserData WHERE id='$ownerId'")->fetch_assoc();
    $owner = "${owner['firstname']} ${owner['lastname']}";
    $clientsResult = $conn->query(
        "SELECT * FROM dynamicprojectsclients
        INNER JOIN  $clientTable ON  $clientTable.id = dynamicprojectsclients.clientid
        WHERE projectid='$id'"
    );
    echo $conn->error;
    $employeesResult = $conn->query(
        "SELECT * FROM dynamicprojectsemployees
        INNER JOIN UserData ON UserData.id = dynamicprojectsemployees.userid
        WHERE projectid='$id' AND UserData.id NOT IN (SELECT t.userID FROM dynamicprojectsteams d JOIN teamrelationshipdata t ON d.teamid = t.teamid WHERE d.projectid = '$id')"
    );
    $teamsResult = $conn->query("SELECT * FROM dynamicprojectsteams INNER JOIN $teamTable ON $teamTable.id = dynamicprojectsteams.teamid WHERE projectid='$id'");
    echo $conn->error;
    $optional_employeesResult = $conn->query(
        "SELECT * FROM dynamicprojectsoptionalemployees
        INNER JOIN UserData ON UserData.id = dynamicprojectsoptionalemployees.userid
        WHERE projectid='$id'"
    );
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
    echo "<td><button class='btn btn-default' type='button' onClick='showDescription(\"$id\")' data-toggle='modal' data-target='#projectDescription'><i class='fa fa-file-text-o'></i></button></td>";
    echo "<td>$companyName</td>";
    echo "<td>";
    $completed = 0; //percentage of overall project completed 0-100
    $string = '';
    while ($clientRow = $clientsResult->fetch_assoc()) {
        array_push($clients, $clientRow["id"]);
        $completed += intval($clientRow["projectcompleted"]);
        $client = $clientRow["name"];
        $string .= "$client, ";
    }
    echo rtrim($string,", ");
    $completed = intval($completed / ((count($clients) > 0) ? count($clients) : 1)); // average completion
    echo "</td>";
    echo "<td>$start</td>";
    echo "<td>$end</td>"; // no end = ""
    echo "<td>$series</td>";
    // echo "<td>$status</td>"; //all projects are active anyway
    echo "<td>$priority</td>";
    echo "<td>$owner</td>";
    echo "<td>";
    $string = '';
    while ($teamRow = $teamsResult->fetch_assoc()) {
        array_push($employees, "team;" . $teamRow["id"]);
        $team = $teamRow["name"];
        $string .= "$team, ";
    }
    while ($employeeRow = $employeesResult->fetch_assoc()) {
        array_push($employees, "user;" . $employeeRow["id"]);
        $employee = "${employeeRow['firstname']} ${employeeRow['lastname']}";
        $string .= "$employee, ";
    }
    while ($optional_employeeRow = $optional_employeesResult->fetch_assoc()) {
        array_push($optional_employees, $optional_employeeRow["id"]);
        $optional_employee = "${optional_employeeRow['firstname']} ${optional_employeeRow['lastname']}";
        $string .= "$optional_employee, ";
    }
    echo rtrim($string,", ");
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
        require __DIR__ . "/misc/dynamicProjects_Booking_modal.php";       
}

    echo "</form>";
    $modal_symbol = "fa fa-cog";
    require __DIR__ . "/misc/dynamicProjects_ProjectEditor_modal.php";
    $modal_title = $lang["DYNAMIC_PROJECTS_NOTES"];
    require __DIR__ . "/misc/dynamicProjects_UserNotes_modal.php";
    echo "</td>";
    echo "</tr>";

    ?>
        <?php
}
?>
</tbody>
</table>
<div class="modal fade" id="projectDescription" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="projectDescriptionId"></h4>
            </div>
            <br>
            <div id="projectDescriptionBody" class="modal-body">
            </div>
        </div>
    </div>
</div>
<script>
function showDescription(pId){
    var header = document.getElementById('projectDescriptionId');
    var body = document.getElementById('projectDescriptionBody');
    $.ajax({
      url: 'ajaxQuery/AJAX_getDescription.php',
      type: 'POST',
      data: {pId:pId},
      success: function(res){
        res = JSON.parse(res);
        header.innerHTML = res['projectname'];
        body.innerHTML = res['projectdescription'];
      }
    });
}


$('.table').DataTable({
  order: [[ 8, "asc" ]],
  columns: [null, {orderable: false}, null, null, null,null,{orderable: false}, null,  {orderable: false},{orderable: false},{orderable: false}],
  deferRender: true,
  responsive: true,
  colReorder: true,
  autoWidth: false,
  language: {
    <?php echo $lang['DATATABLES_LANG_OPTIONS']; ?>
  }
});
</script>


<!-- /BODY -->
<?php include 'footer.php';?>
