<?php include 'header.php';
enableToDynamicProjects($userID); 
require "dynamicProjects_classes.php";


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