<?php
require dirname(__DIR__)."/connection.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST['id'])){
        $id = intval($_POST['id']);
        //echo $id;
        //var_dump($_POST);
        try{
            $result = $conn->query("SELECT * FROM taskemailrules WHERE id = $id");
            $row = $result->fetch_assoc();
            echo json_encode($row);
        }catch(Exception $e){
            echo "\n" . $e;
        }
    }
}
return;
?>