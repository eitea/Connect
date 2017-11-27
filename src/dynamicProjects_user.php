<?php include 'header.php';
enableToDynamicProjects($userID); 
require "dynamicProjects_classes.php";

$forceCreate = false;
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deletenote"])){
    $id = $_POST["id"] ?? "";
    $id = $conn->real_escape_string($id);
    $conn->query("DELETE FROM dynamicprojectsnotes WHERE projectid = '$id'");
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["note"])){
    $id = $_POST["id"] ?? "";
    $id = $conn->real_escape_string($id);
    $text = $_POST["notetext"]??"error";
    $conn->query("INSERT INTO dynamicprojectsnotes (projectid,notetext,notecreator) VALUES ('$id','$text',$userID)");
    echo $conn->error;
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["deletenote"])){
   $note_id = $_POST["id"] ?? "";
   $conn->query("DELETE FROM dynamicprojectsnotes WHERE noteid = $note_id");
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])){
    require_once __DIR__. "/utilities.php";
    $project = $_POST["id"];
    $picture = uploadFile("image", 1, 0, 1);
    $picture = "data:image/jpeg;base64,".base64_encode( $picture );
    $stmt = $conn->prepare("INSERT INTO dynamicprojectspictures (projectid,picture) VALUES ('$project', ?)");
    echo $conn->error;
    $null = NULL;
    $stmt->bind_param("b", $null);
    $stmt->send_long_data(0, $picture);
    $stmt->execute();
    echo $stmt->error;
}



if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editDynamicProject"])){
    //edit deletes the project and recreates it
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
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST["dynamicProject"]) || $forceCreate)) {
    $connectIdentification = $conn->query("SELECT id FROM identification")->fetch_assoc()["id"];
    $id = $_POST["id"] ?? "";
    $name = $_POST["name"] ?? "missing name";
    $description = $_POST["description"] ?? "missing description";
    $company = $_POST["company"] ?? "";
    $color = $_POST["color"] ?? "#FFFFFF";
    $start = $_POST["start"] ?? date("Y-m-d");
    $end = $_POST["endradio"] ?? "";
    $status = $_POST["status"] ?? 'DRAFT';
    $priority = intval($_POST["priority"] ?? "3") ?? 3;
    $parent = $_POST["parent"] ?? "";
    $pictures = $_POST["imagesbase64"] ?? false;
    $owner = $_POST["owner"] ?? $userID ?? "";
    $clients = $_POST["clients"] ?? array();
    $employees = $_POST["employees"] ?? array();
    $optional_employees = $_POST["optionalemployees"] ?? array();
    $completed = (int)$_POST["completed"] ?? 0;
    //series one of: once daily_every_nth daily_every_weekday weekly monthly_day_of_month monthly_nth_day_of_week yearly_nth_day_of_month yearly_nth_day_of_week
    $series = $_POST["series"] ?? "once";
    if ($end == "no") {
        $end = "";
    }
    else if ($end == "number") {
        $end = $_POST["endnumber"] ?? "";
    }
    else if ($end == "date") {
        $end = $_POST["enddate"] ?? "";
    }
    $series = new ProjectSeries($series, $start, $end);
    $series->daily_days = (int)$_POST["daily_days"] ?? 1;
    $series->weekly_weeks = (int)$_POST["weekly_weeks"] ?? 1;
    $series->weekly_day = $_POST["weekly_day"] ?? "monday";
    $series->monthly_day_of_month_day = (int)$_POST["monthly_day_of_month_day"] ?? 1;
    $series->monthly_day_of_month_month = (int)$_POST["monthly_day_of_month_month"] ?? 1;
    $series->monthly_nth_day_of_week_nth = (int)$_POST["monthly_nth_day_of_week_nth"] ?? 1;
    $series->monthly_nth_day_of_week_day = $_POST["monthly_nth_day_of_week_day"] ?? "monday";
    $series->monthly_nth_day_of_week_month = (int)$_POST["monthly_nth_day_of_week_month"] ?? 1;
    $series->yearly_nth_day_of_month_nth = (int)$_POST["yearly_nth_day_of_month_nth"] ?? 1;
    $series->yearly_nth_day_of_month_month = $_POST["yearly_nth_day_of_month_month"] ?? "JAN";
    $series->yearly_nth_day_of_week_nth = (int)$_POST["yearly_nth_day_of_week_nth"] ?? 1;
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
    $conn->query("INSERT INTO dynamicprojects (projectid,projectname,projectdescription, companyid, projectcolor, projectstart,projectend,projectstatus,projectpriority, projectparent, projectowner, projectcompleted) VALUES ('$id','$name','$description', $company, '$color', '$start', '$end', '$status', '$priority', '$parent', '$owner', '$completed') ON DUPLICATE KEY UPDATE projectname='$name', projectdescription = '$description', companyid=$company, projectcolor='$color', projectstart='$start', projectend='$end', projectstatus='$status', projectpriority='$priority', projectparent='$parent', projectowner='$owner', projectcompleted='$completed'");
    echo $conn->error;
    // series
    $stmt = $conn->prepare("INSERT INTO dynamicprojectsseries (projectid,projectnextdate,projectseries) VALUES ('$id','$nextDate',?)");
    echo $conn->error;
    $null = NULL;
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
            $null = NULL;
            $stmt->bind_param("b", $null);
            $stmt->send_long_data(0, $picture);
            $stmt->execute();
            echo $stmt->error;
        }
    }
    foreach ($clients as $client) {
        $client = intval($client);
        $conn->query("INSERT INTO dynamicprojectsclients (projectid, clientid) VALUES ('$id',$client)");
    }
    foreach ($employees as $employee) {
        $employee = intval($employee);
        $conn->query("INSERT INTO dynamicprojectsemployees (projectid, userid) VALUES ('$id',$employee)");
    }
    foreach ($optional_employees as $optional_employee) {
        $optional_employee = intval($optional_employee);
        $conn->query("INSERT INTO dynamicprojectsoptionalemployees (projectid, userid) VALUES ('$id',$optional_employee)");
    }
}





?>
<!-- BODY -->
<table class="table">
<thead>
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Owner</th>
        <th>Employees</th>
        <th>Optional Employees</th>
    </tr>
</thead>
<tbody>
    <?php
    $dateNow = date('Y-m-d');
    $result = $conn->query("SELECT dynamicprojects.*, dynamicprojectsemployees.*,dynamicprojectsoptionalemployees.* 
    FROM dynamicprojects,dynamicprojectsemployees, dynamicprojectsoptionalemployees 
    WHERE dynamicprojects.projectid = dynamicprojectsemployees.projectid 
    AND dynamicprojectsoptionalemployees.projectid = dynamicprojects.projectid
    AND ( dynamicprojectsoptionalemployees.userid = $userID OR dynamicprojectsemployees.userid = $userID OR dynamicprojects.projectowner = $userID )
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
        $completed = $row["projectcompleted"];
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
        }
        else {
            echo "series couldn't be unserialized";
        }

        echo "<tr>";
        echo "<td style='background-color:$color;'>$name</td>";
        echo "<td>$description</td>";
        echo "<td>$companyName</td>";
        echo "<td>$start</td>";
        echo "<td>$end</td>";
        echo "<td>$status</td>";
        echo "<td>$priority</td>";
        echo "<td>$parent</td>";
        echo "<td>$owner</td>";
        echo "<td>";
        while ($pictureRow = $pictureResult->fetch_assoc()) {
            $picture = $pictureRow["picture"];
            array_push($pictures, $picture);
            echo "<img  height='50' src='$picture'>";
        }
        echo "</td>";
        echo "<td>";
        while ($clientRow = $clientsResult->fetch_assoc()) {
            array_push($clients, $clientRow["id"]);
            $client = $clientRow["name"];
            echo "$client, ";
        }
        echo "</td>";
        echo "<td>";
        while ($employeeRow = $employeesResult->fetch_assoc()) {
            array_push($employees, $employeeRow["id"]);
            $employee = "${employeeRow['firstname']} ${employeeRow['lastname']}";
            echo "$employee, ";
        }
        echo "</td>";
        echo "<td>";
        while ($optional_employeeRow = $optional_employeesResult->fetch_assoc()) {
            array_push($optional_employees, $optional_employeeRow["id"]);
            $optional_employee = "${optional_employeeRow['firstname']} ${optional_employeeRow['lastname']}";
            echo "$optional_employee, ";
        }
        echo "</td>";

        echo "<td>";
        $modal_title = "Edit Dynamic Project";
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
        require "dynamicProjects_template.php";
        $modal_title = "Notes";
        require "dynamicProjects_comments.php";
        echo "</td>";
        echo "</tr>";
    }
    ?>
</tbody>
</table>
<!-- /BODY -->
<?php include 'footer.php'; ?>