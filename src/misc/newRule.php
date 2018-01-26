<?php
//new Rule
require dirname(__DIR__)."/connection.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST['id'])){
        $id = intval($_POST['id']);
        echo $id;
        try{
            if(isset($_POST['Identifier'])&&isset($_POST['Company'])&&isset($_POST['Client'])&&isset($_POST['Color'])&&isset($_POST['Status'])&&isset($_POST['Priority'])&&isset($_POST['Parent'])&&isset($_POST['Owner'])&&isset($_POST['Employees'])&&isset($_POST['OEmployees'])&&isset($_POST['Leader'])){
                $Identifier = $_POST['Identifier'];
                $Company = $_POST['Company'];
                $Client = $_POST['Client'];
                $Color = $_POST['Color'];
                $Status = $_POST['Status'];
                $Priority = $_POST['Priority'];
                $Parent = $_POST['Parent'];
                $Owner = $_POST['Owner'];
                $Employees = $_POST['Employees'];
                $OEmployees = $_POST['OEmployees'];
                $Leader = $_POST['Leader'];
                $conn->query("INSERT INTO taskemailrules VALUES(null,'$Identifier','$Company','$Client','$Color','$Status','$Priority','$Parent','$Owner','$Employees','$OEmployees','$id','$Leader')");
                if($conn->error){
                    echo $conn->error;
                }
            }
        }catch(Exception $e){
            echo "\n" . $e;
        }
    }
}
return;
?>