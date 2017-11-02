<?php include 'header.php';
enableToDynamicProjects($userID); ?>
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
        $color = $row["projectcolor"];
        $employeesResult = $conn->query("SELECT * FROM dynamicprojectsemployees INNER JOIN UserData ON UserData.id = dynamicprojectsemployees.userid WHERE projectid='$id'");
        $optional_employeesResult = $conn->query("SELECT * FROM dynamicprojectsoptionalemployees INNER JOIN UserData ON UserData.id = dynamicprojectsoptionalemployees.userid WHERE projectid='$id'");
        $owner = $row["projectowner"];
        $owner_name = $conn->query("SELECT * FROM UserData WHERE id='$owner'")->fetch_assoc();
        $owner_name = "${owner_name['firstname']} ${owner_name['lastname']}";
        $employees = array();
        $optional_employees = array();
        $isParticipatingInProject = $owner == $userID;
        while ($employeeRow = $employeesResult->fetch_assoc()) {
            array_push($employees, "${employeeRow['firstname']} ${employeeRow['lastname']}");
            if($employeeRow["id"] == $userID){
                $isParticipatingInProject = true;
            }
        }
        while ($optional_employeeRow = $optional_employeesResult->fetch_assoc()) {
            array_push($optional_employees, "${optional_employeeRow['firstname']} ${optional_employeeRow['lastname']}");
            if($employeeRow["id"] == $userID){
                $isParticipatingInProject = true;
            }
        }
        if(!$isParticipatingInProject){
            continue;
        }
        echo "<tr>";
        echo "<td style='background-color:$color;'>$name</td>";
        echo "<td>$description</td>";
        echo "<td>$owner_name</td>";
        echo "<td>";
        foreach ($employees as $employee) {
            echo "$employee, ";
        }
        echo "</td>";
        echo "<td>";
        foreach ($optional_employees as $employee) {
            echo "$employee, ";
        }
        echo "</td>";
        echo "</tr>";
    }
    ?>
</tbody>
</table>
<!-- /BODY -->
<?php include 'footer.php'; ?>