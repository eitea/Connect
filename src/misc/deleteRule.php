<?php
//new Rule
require dirname(__DIR__)."/connection.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(!empty($_POST['ruleid'])){
        $id = intval($_POST['ruleid']);
        try{
            $conn->query("DELETE FROM taskemailrules WHERE id = $id");
            if($conn->error){
                echo $conn->error;
            }
        }catch(Exception $e){
            echo "\n" . $e;
        }
        if(!empty($_POST['emailid'])){
            echo $_POST['emailid'];
        }
    }
}
return;
?>