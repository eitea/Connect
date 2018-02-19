<?php

//new Rule
require dirname(__DIR__) . "/connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['id'])) {
        $id = intval($_POST['id']);
        echo $id;
        //var_dump($_POST);
        try {
            if (isset($_POST['Identifier']) && isset($_POST['Company']) && isset($_POST['Client']) && isset($_POST['Color']) && isset($_POST['Status']) && isset($_POST['Priority']) && isset($_POST['Parent']) && isset($_POST['Owner']) && isset($_POST['Employees']) && isset($_POST['Leader'])) {
                $Identifier = $_POST['Identifier'];
                $Company = $_POST['Company'];
                $Client = $_POST['Client'];
                $ClientProject = $_POST['ClientProject'];
                $Color = $_POST['Color'];
                $Status = $_POST['Status'];
                $Priority = $_POST['Priority'];
                $Parent = $_POST['Parent'];
                if ($Parent === "0")
                    $Parent = null;
                $Owner = $_POST['Owner'];
                $EmployeesCollection = $_POST['Employees'];
                $Employees = '';
                foreach ($EmployeesCollection as $Employee) {
                    $Employees = $Employees . $Employee . ',';
                }
                $OEmployees = '';
                if (isset($_POST['OEmployees'])) {
                    $OEmployeesCollection = $_POST['OEmployees'];
                    foreach ($OEmployeesCollection as $Employee) {
                        $OEmployees = $OEmployees . $Employee . ',';
                    }
                }
                $Leader = $_POST['Leader'];
                $conn->query("INSERT INTO taskemailrules VALUES(null,'$Identifier','$Company','$Client','$ClientProject','$Color','$Status','$Priority','$Parent','$Owner','$Employees','$OEmployees','$id','$Leader')");
                if ($conn->error) {
                    echo $conn->error;
                }
            }
        } catch (Exception $e) {
            echo "\n" . $e;
        }
    }
}
return;
?>