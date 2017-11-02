<?php include 'header.php';
enableToDynamicProjects($userID); ?>
<!-- BODY -->
<?php



?>


<table class="table">
<thead>
    <tr>
        <th>Name</th>
        <th>Description</th>
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
        echo "<tr>";
        echo "<td style='background-color:$color;'>$name</td>";
        echo "<td>$description</td>";
        echo "<td>";
        while ($employeeRow = $employeesResult->fetch_assoc()) {
            echo "${employeeRow['firstname']} ${employeeRow['lastname']}, ";
        }
        echo "</td>";
        echo "<td>";
        while ($optional_employeeRow = $optional_employeesResult->fetch_assoc()) {
            echo "${optional_employeeRow['firstname']} ${optional_employeeRow['lastname']}, ";
        }
        echo "</td>";
        echo "</tr>";
    }
    ?>
</tbody>
</table>



<!-- /BODY -->
<?php include 'footer.php'; ?>