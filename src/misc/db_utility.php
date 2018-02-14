<?php
require dirname(__DIR__)."/connection.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    if($_POST['function']==="forcePwdChange"){
        $id = intval($_POST['userid']);
        try{
            $conn->query("UPDATE userdata SET forcedPwdChange = 1 WHERE id=$id");
            if($conn->error){
                echo $conn->error;
            }
        }catch(Exception $e){
            echo "\n" . $e;
        }
    }
    
}
?>