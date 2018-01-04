<?php include 'header.php';
isDynamicProjectAdmin($userID);?>
<!-- BODY -->
<?php
require "dynamicProjects_classes.php";

// //testing
// $retDate/*now*/ = new DateTime();
// echo $retDate->format("Y-m-d");
// $retDate->setTimestamp(strtotime("first day of march", $retDate->getTimestamp()));
// echo "<br>".$retDate->format("Y-m-d");

$forceCreate = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editDynamicProject"])) {
    $forceCreate = true;
    $id = $id = $_POST["id"] ?? "error - no id";
    $id = $conn->real_escape_string($id);
    $conn->query("DELETE FROM dynamicprojectsclients WHERE projectid = '$id'");
    echo $conn->error;
    $conn->query("DELETE FROM dynamicprojectsemployees WHERE projectid = '$id'");
    echo $conn->error;
    $conn->query("DELETE FROM dynamicprojectsoptionalemployees WHERE projectid = '$id'");
    echo $conn->error;
    $conn->query("DELETE FROM dynamicprojectspictures WHERE projectid = '$id'");
    echo $conn->error;
    $conn->query("DELETE FROM dynamicprojectsseries WHERE projectid = '$id'");
    echo $conn->error;
    $conn->query("DELETE FROM dynamicprojectsteams WHERE projectid = '$id'");
    echo $conn->error;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST["dynamicProject"]) || $forceCreate)) {
    $connectIdentification = $conn->query("SELECT id FROM identification")->fetch_assoc()["id"];
    $id = $_POST["id"] ?? "";
    $name = $_POST["name"] ?? "missing name";
    $description = $_POST["description"] ?? "missing description";
    $company = $_POST["company"] ?? false;
    $color = $_POST["color"] ?? "#FFFFFF";
    $start = $_POST["start"] ?? date("Y-m-d");
    $end = $_POST["endradio"] ?? "";
    $status = $_POST["status"] ?? 'DRAFT';
    $priority = intval($_POST["priority"] ?? "3") ?? 3;
    $parent = $_POST["parent"] ?? "";
    $pictures = $_POST["imagesbase64"] ?? false;
    $owner = $_POST["owner"] ?? $userID ?? "";
    $clients = $_POST["clients"] ?? array();
    $employees = $_POST["employees"] ?? array(); //can be "user;<id>" or "team;<id>"
    $optional_employees = $_POST["optionalemployees"] ?? array();
    $completed = (int) $_POST["completed"] ?? 0;
    //series one of: once daily_every_nth daily_every_weekday weekly monthly_day_of_month monthly_nth_day_of_week yearly_nth_day_of_month yearly_nth_day_of_week
    $series = $_POST["series"] ?? "once";
    if ($end == "no") {
        $end = "";
    } else if ($end == "number") {
        $end = $_POST["endnumber"] ?? "";
    } else if ($end == "date") {
        $end = $_POST["enddate"] ?? "";
    }
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
    // var_dump($series);
    echo "<br><br><br>";
    if ($parent == "none") {
        $parent = "";
    }
    if (empty($company) || !is_numeric($company)) {
        echo "Company not set";
    }
    if ($id == "") {
        $id = uniqid($connectIdentification);
        while ($conn->query("SELECT * FROM dynamicprojects WHERE projectid = 'asdf'")->num_rows != 0) {
            $id = uniqid($connectIdentification);
        }
    }
    $owner = intval($owner) ?? $userID;
    $nextDate = $series->get_next_date();
    // var_dump($series);
    $series = serialize($series);
    $series = base64_encode($series);

    $description = $conn->real_escape_string($description);
    $id = $conn->real_escape_string($id);
    $name = $conn->real_escape_string($name);
    $color = $conn->real_escape_string($color);
    $start = $conn->real_escape_string($start);
    $end = $conn->real_escape_string($end);
    $status = $conn->real_escape_string($status);
    $parent = $conn->real_escape_string($parent);

    foreach ($clients as $client) {
        $client = intval($client);
        $clientResult = $conn->query("SELECT * FROM projectData WHERE dynamicprojectid = '$id' AND clientID = $client");
        echo $conn->error;
        $clientExists = $clientResult->num_rows != 0;
        if (!$clientExists) {
            $conn->query("INSERT INTO projectData (clientID,name,dynamicprojectid) VALUES ($client, '$name', '$id')");
        } else {
            $conn->query("UPDATE projectData SET name = '$name' WHERE dynamicprojectid = '$id' AND clientID = $client");
        }
        echo $conn->error;
    }
    $clientsAsSQLList = "(";
    for ($i = 0; $i < count($clients); $i++) {
        $clientsAsSQLList .= $clients[$i];
        if ($i < count($clients) - 1) {
            $clientsAsSQLList .= ", ";
        }

    }
    $clientsAsSQLList .= ")";
    $clientsResult = $conn->query("DELETE FROM projectData WHERE dynamicprojectid = '$id' AND clientID NOT IN $clientsAsSQLList"); //remove all other projects

    $conn->query("INSERT INTO dynamicprojects (projectid,projectname,projectdescription, companyid, projectcolor, projectstart,projectend,projectstatus,projectpriority, projectparent, projectowner) VALUES ('$id','$name','$description', $company, '$color', '$start', '$end', '$status', '$priority', '$parent', '$owner') ON DUPLICATE KEY UPDATE projectname='$name', projectdescription = '$description', companyid=$company, projectcolor='$color', projectstart='$start', projectend='$end', projectstatus='$status', projectpriority='$priority', projectparent='$parent', projectowner='$owner'");
    echo $conn->error;
    if ($completed == 100) {
        $conn->query("UPDATE dynamicprojects SET projectstatus = 'COMPLETED' WHERE projectid = '$id'");
    }
    // series
    $stmt = $conn->prepare("INSERT INTO dynamicprojectsseries (projectid,projectnextdate,projectseries) VALUES ('$id','$nextDate',?)");
    echo $conn->error;
    $null = null;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $series);
    $stmt->execute();
    echo $stmt->error;
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
    foreach ($clients as $client) {
        $client = intval($client);
        $conn->query("INSERT INTO dynamicprojectsclients (projectid, clientid, projectcompleted) VALUES ('$id', $client, '$completed')");
    }
    foreach ($employees as $employee) {
        $emp_array = explode(";",$employee);
        if($emp_array[0] == "user"){
            $employee = intval($emp_array[1]);
            $conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid) VALUES ('$id',$employee)");
        }else{
            $team = intval($emp_array[1]);
            $conn->query("INSERT INTO dynamicprojectsteams (projectid, teamid) VALUES ('$id',$team)");
            echo $conn->error;
        }
    }
    foreach ($optional_employees as $optional_employee) {
        $optional_employee = intval($optional_employee);
        $conn->query("INSERT INTO dynamicprojectsoptionalemployees (projectid, userid) VALUES ('$id',$optional_employee)");
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["deleteDynamicProject"])) {
        $id = $id = $_POST["id"] ?? "error - no id";
        $id = $conn->real_escape_string($id);
        $conn->query("DELETE FROM dynamicprojects WHERE projectid = '$id'");
        //also delete the static projects
        $conn->query("DELETE FROM projectData WHERE dynamicprojectid = '$id'");
    }
}
?>
<br>


<?php
// variables for easy reuse for editing existing dynamic projects
$modal_title = $lang['DYNAMIC_PROJECTS_NEW'];
$modal_name = "";
$modal_company = ""; //id
$modal_description = $lang['DYNAMIC_PROJECTS_DEFAULT_DESCRIPTION'];
$modal_color = "#ededed";
$modal_start = date("Y-m-d");
$modal_end = ""; // Possibilities: ""(none);number (repeats); Y-m-d (date)
$modal_status = "ACTIVE"; // Possibiilities: "ACTIVE","DEACTIVATED","DRAFT","COMPLETED"
$modal_priority = 3;
$modal_id = ""; // empty => generate new
$modal_pictures = array();
$modal_parent = ""; //default: "none" or ""
$modal_completed = 0; //0-100
$modal_clients = array(); //array of ids
$modal_owner = "";
$modal_employees = array();
$modal_optional_employees = array();
$modal_series = new ProjectSeries("", "", "");
$modal_isAdmin = true;
$modal_project_data_id = "";
$modal_symbol = "fa fa-plus";
require "dynamicProjects_template.php";

?>


<!--
*************************************************************************************
                              List all dynamic projects
*************************************************************************************
-->

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
        <th style="white-space: nowrap;width: 1%;"></th> <!-- space for edit button -->
        <!-- <th>Parent</th> -->
        <!-- <th>Pictures</th> -->
        <!-- <th>Clients</th>
        <th>Employees</th>
        <th>Optional Employees</th> -->
    </tr>
</thead>
<tbody>
    <?php
$result = $conn->query("SELECT * FROM dynamicprojects");
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
    $clientsResult = $conn->query("SELECT * FROM dynamicprojectsclients INNER JOIN  $clientTable ON  $clientTable.id = dynamicprojectsclients.clientid  WHERE projectid='$id'");
    $employeesResult = $conn->query("SELECT * FROM dynamicprojectsemployees INNER JOIN UserData ON UserData.id = dynamicprojectsemployees.userid WHERE projectid='$id'");
    $teamsResult = $conn->query("SELECT * FROM dynamicprojectsteams INNER JOIN $teamTable ON $teamTable.id = dynamicprojectsteams.teamid WHERE projectid='$id'");
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
    $completed = intval($completed / ((count($clients) > 0) ? count($clients) : 1)); // average completion
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
        array_push($employees, "user;".$employeeRow["id"]);
        $employee = "${employeeRow['firstname']} ${employeeRow['lastname']}";
        echo "$employee, ";
    }
    while($teamRow = $teamsResult->fetch_assoc()){
        array_push($employees, "team;".$teamRow["id"]);
        $team = $teamRow["name"];
        echo "$team, ";
    }
    while ($optional_employeeRow = $optional_employeesResult->fetch_assoc()) {
        array_push($optional_employees, $optional_employeeRow["id"]);
        $optional_employee = "${optional_employeeRow['firstname']} ${optional_employeeRow['lastname']}";
        echo "$optional_employee, ";
    }
    echo "</td>";
    echo "<td>";
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
    $modal_completed = $completed;
    $modal_symbol = "fa fa-cog";
    require "dynamicProjects_template.php";
    echo "</td>";
    echo "</tr>";
}
?>
</tbody>
</table>





<!-- /BODY -->
<?php
include 'footer.php';?>